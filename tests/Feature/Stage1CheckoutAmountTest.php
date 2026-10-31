<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Organizer;
use App\Models\Payment;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Midtrans\MT_Tests;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

require_once __DIR__ . '/OneTimePollingRegressionTest.php';

class Stage1CheckoutAmountTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.midtrans.demo' => false, 'services.midtrans.is_production' => false,
            'services.midtrans.server_key' => 'checkout-test-key']);
        MT_Tests::$stubHttp = true;
        MT_Tests::$lastHttpRequest = null;
        MT_Tests::$stubHttpResponse = '{"token":"checkout-stub-token","redirect_url":"https://example.invalid/checkout"}';
    }

    protected function tearDown(): void
    {
        try {
            MT_Tests::$stubHttp = false;
            MT_Tests::$stubHttpResponse = null;
            MT_Tests::$lastHttpRequest = null;
        } finally {
            parent::tearDown();
        }
    }

    public static function checkoutEnvironments(): array
    {
        return ['testing dummy branch' => ['testing'], 'local SDK stub' => ['local']];
    }

    #[DataProvider('checkoutEnvironments')]
    public function test_fractional_pending_checkout_is_rejected_without_writes_or_sdk_request(string $environment): void
    {
        [$user, $event, $ticket, $payment] = $this->purchase('50000.50');
        $before = [$event->fresh()->getRawOriginal(), $ticket->fresh()->getRawOriginal(), $payment->fresh()->getRawOriginal()];
        $this->app->instance('env', $environment);

        $this->actingAs($user)->getJson(route('tickets.show', $ticket))
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Nominal pembayaran harus lebih dari nol dan dalam rupiah utuh. Silakan hubungi penyelenggara.');

        $this->assertNull(MT_Tests::$lastHttpRequest);
        $this->assertSame($before, [$event->fresh()->getRawOriginal(), $ticket->fresh()->getRawOriginal(), $payment->fresh()->getRawOriginal()]);
        $this->assertDatabaseCount('tickets', 1);
        $this->assertDatabaseCount('payments', 1);
    }

    #[DataProvider('checkoutEnvironments')]
    public function test_whole_rupiah_pending_checkout_preserves_existing_mode(string $environment): void
    {
        [$user, , $ticket, $payment] = $this->purchase('50000.00');
        $this->app->instance('env', $environment);

        $token = $environment === 'testing' ? 'dummy-snap-token-123' : 'checkout-stub-token';
        $this->actingAs($user)->get(route('tickets.show', $ticket))
            ->assertOk()->assertViewHas('snapToken', $token);

        $this->assertSame($token, $payment->fresh()->snap_token);
        $this->assertSame('50000.00', $payment->fresh()->amount);
        $this->assertSame('pending', $ticket->fresh()->status);
        if ($environment === 'testing') {
            $this->assertNull(MT_Tests::$lastHttpRequest);
        } else {
            $this->assertNotNull(MT_Tests::$lastHttpRequest);
        }
    }

    private function purchase(string $amount): array
    {
        $user = User::factory()->create(['role' => 'user']);
        $owner = User::factory()->create(['role' => 'organizer']);
        $organizer = Organizer::create(['user_id' => $owner->id, 'organization_name' => 'Checkout Test',
            'phone' => '081122334455', 'description' => 'Test organizer']);
        $event = Event::create(['organizer_id' => $organizer->id, 'title' => 'Checkout Test',
            'description' => 'Test event', 'location' => 'Manado', 'event_date' => now()->addDays(10),
            'registration_deadline' => now()->addDays(5), 'max_participants' => 100,
            'ticket_price' => $amount, 'category' => 'Running', 'status' => 'active']);
        $ticket = Ticket::create(['user_id' => $user->id, 'event_id' => $event->id,
            'ticket_code' => 'CHECKOUT-AMOUNT-TEST', 'status' => 'pending']);
        $payment = Payment::create(['ticket_id' => $ticket->id, 'user_id' => $user->id,
            'amount' => $amount, 'status' => 'pending', 'method' => 'ewallet']);

        return [$user, $event, $ticket, $payment];
    }
}
