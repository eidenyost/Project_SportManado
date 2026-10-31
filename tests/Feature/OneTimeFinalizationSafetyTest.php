<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Organizer;
use App\Models\Payment;
use App\Models\Ticket;
use App\Models\User;
use App\Services\PaymentFinalizationService;
use Closure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/** Runs after SubstituteBindings, leaving the route model deliberately stale. */
class FinalizationUsedAfterBinding
{
    public static bool $sawPendingBinding = false;

    public function handle(Request $request, Closure $next)
    {
        $ticket = $request->route('ticket');
        self::$sawPendingBinding = $ticket instanceof Ticket && $ticket->status === 'pending';
        if (!self::$sawPendingBinding) {
            throw new \RuntimeException('Expected a bound pending ticket before the simulated competing write.');
        }
        Ticket::whereKey($ticket->id)->update(['status' => 'used', 'used_at' => '2026-10-01 12:00:00']);

        return $next($request);
    }
}

class OneTimeFinalizationSafetyTest extends TestCase
{
    use RefreshDatabase;

    private $originalDispatcher = null;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        config(['services.midtrans.demo' => true, 'services.midtrans.is_production' => false]);
        FinalizationUsedAfterBinding::$sawPendingBinding = false;
    }

    protected function tearDown(): void
    {
        // Restore the entire original dispatcher, not flushEventListeners(): that
        // would remove Ticket's real QR listener as well as this test's fault.
        if ($this->originalDispatcher !== null) {
            Payment::setEventDispatcher($this->originalDispatcher);
            $this->originalDispatcher = null;
        }
        parent::tearDown();
    }

    public function test_cancelled_ticket_cannot_be_reactivated_by_demo_webhook_or_demo_endpoint(): void
    {
        [$user, $event, $ticket, $payment] = $this->purchase();
        $ticket->update(['status' => 'cancelled']);
        $this->actingAs($user)->postJson(route('payments.notification'), ['order_id' => $ticket->ticket_code])->assertOk();
        $this->actingAs($user)->post(route('tickets.demo-pay', $ticket))->assertStatus(422);
        $this->assertSame('cancelled', $ticket->fresh()->status);
        $this->assertSame('pending', $payment->fresh()->status);
        $this->assertNull($ticket->fresh()->qr_code_path);
        Storage::disk('public')->assertMissing("qr-codes/{$ticket->ticket_code}.svg");
    }

    public function test_valid_pending_demo_payment_confirms_and_creates_qr(): void
    {
        [$user, $event, $ticket, $payment] = $this->purchase();
        $this->actingAs($user)->post(route('tickets.demo-pay', $ticket))->assertRedirect();
        $this->assertSame('active', $ticket->fresh()->status);
        $this->assertSame('confirmed', $payment->fresh()->status);
        $this->assertNull($ticket->fresh()->used_at);
        $this->assertNotEmpty($ticket->fresh()->qr_code_path);
        Storage::disk('public')->assertExists($ticket->fresh()->qr_code_path);
    }

    public function test_production_demo_webhook_is_blocked_without_mutation(): void
    {
        [$user, , $ticket, $payment] = $this->purchase();
        $previous = $this->app['env'];
        $this->app['env'] = 'production';
        try {
            $this->actingAs($user)->withSession(['_token' => 'safety-token'])->post(route('payments.notification'), [
                '_token' => 'safety-token', 'order_id' => $ticket->ticket_code,
            ])->assertNotFound();
        } finally {
            $this->app['env'] = $previous;
        }
        $this->assertSame('pending', $ticket->fresh()->status);
        $this->assertSame('pending', $payment->fresh()->status);
        $this->assertNull($ticket->fresh()->qr_code_path);
    }

    public function test_confirmed_active_settlement_at_capacity_preserves_allocation_and_metadata(): void
    {
        [$user, $event, $ticket, $payment] = $this->purchase();
        $event->update(['max_participants' => 1]);
        $ticket->update(['status' => 'active']);
        $payment->update(['status' => 'confirmed', 'payment_type' => 'bank_transfer',
            'midtrans_transaction_id' => 'original-id', 'notes' => 'Original confirmation']);
        $beforeTicket = $ticket->fresh()->getAttributes();
        $beforePayment = $payment->fresh()->getAttributes();
        $this->assertTrue($event->fresh()->isFull());
        $this->actingAs($user)->postJson(route('payments.notification'), ['order_id' => $ticket->ticket_code,
            'payment_type' => 'qris', 'transaction_id' => 'replay-id'])->assertOk();
        $this->assertSame($beforeTicket, $ticket->fresh()->getAttributes());
        $this->assertSame($beforePayment, $payment->fresh()->getAttributes());
        Storage::disk('public')->assertExists($ticket->fresh()->qr_code_path);
    }

    public function test_failure_after_qr_callback_rolls_back_ticket_and_payment_database_rows(): void
    {
        [, , $ticket, $payment] = $this->purchase();
        $beforeTicket = $ticket->fresh()->getAttributes();
        $beforePayment = $payment->fresh()->getAttributes();
        $this->originalDispatcher = Payment::getEventDispatcher();
        Payment::setEventDispatcher(clone $this->originalDispatcher);
        Payment::updating(function (Payment $updating) use ($ticket) {
            if ($updating->ticket_id === $ticket->id && $updating->status === 'confirmed') {
                $fresh = $ticket->fresh();
                $this->assertSame('active', $fresh->status);
                $this->assertNotEmpty($fresh->qr_code_path);
                Storage::disk('public')->assertExists($fresh->qr_code_path);
                throw new \RuntimeException('Synthetic failure after QR callback');
            }
        });
        try {
            app(PaymentFinalizationService::class)->finalize($ticket, 'settlement');
            $this->fail('The injected failure must escape the transaction.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Synthetic failure after QR callback', $exception->getMessage());
        }
        $this->assertSame($beforeTicket, $ticket->fresh()->getAttributes());
        $this->assertSame($beforePayment, $payment->fresh()->getAttributes());
        // Filesystem writes are not transactional: the orphan is a documented risk.
        Storage::disk('public')->assertExists("qr-codes/{$ticket->ticket_code}.svg");
    }

    public function test_organizer_approval_rechecks_used_status_after_route_binding(): void
    {
        $this->assertStaleOrganizerActionBlocked('approve');
    }

    public function test_organizer_rejection_rechecks_used_status_after_route_binding(): void
    {
        $this->assertStaleOrganizerActionBlocked('reject');
    }

    public function test_strict_demo_service_rechecks_stale_pending_ticket_before_finalizing(): void
    {
        [, , $ticket, $payment] = $this->purchase();
        Ticket::whereKey($ticket->id)->update(['status' => 'used', 'used_at' => '2026-10-01 12:00:00']);
        $this->assertSame('pending', $ticket->status, 'Caller deliberately holds a stale model.');
        try {
            app(PaymentFinalizationService::class)->finalize($ticket, 'settlement', [], true);
            $this->fail('Strict demo must reject fresh terminal state.');
        } catch (HttpException $exception) {
            $this->assertSame(422, $exception->getStatusCode());
        }
        $this->assertSame('used', $ticket->fresh()->status);
        $this->assertSame('2026-10-01 12:00:00', $ticket->fresh()->used_at->format('Y-m-d H:i:s'));
        $this->assertSame('pending', $payment->fresh()->status);
        $this->assertNull($ticket->fresh()->qr_code_path);
    }

    private function assertStaleOrganizerActionBlocked(string $action): void
    {
        [, $event, $ticket, $payment, $organizerUser] = $this->purchase();
        $event->update(['ticket_price' => 0]);
        $router = app('router');
        $router->aliasMiddleware('safety.used-after-binding', FinalizationUsedAfterBinding::class);
        $name = 'organizer.events.participants.' . $action;
        $router->getRoutes()->getByName($name)->middleware('safety.used-after-binding');
        $this->actingAs($organizerUser)->post(route($name, [$event, $ticket]))->assertStatus(422);
        $this->assertTrue(FinalizationUsedAfterBinding::$sawPendingBinding);
        $this->assertSame('used', $ticket->fresh()->status);
        $this->assertSame('2026-10-01 12:00:00', $ticket->fresh()->used_at->format('Y-m-d H:i:s'));
        $this->assertSame('pending', $payment->fresh()->status);
        $this->assertNull($ticket->fresh()->qr_code_path);
    }

    private function purchase(): array
    {
        $user = User::factory()->create(['role' => 'user']);
        $organizerUser = User::factory()->create(['role' => 'organizer']);
        $organizer = Organizer::create(['user_id' => $organizerUser->id,
            'organization_name' => 'Finalization Safety Organizer', 'phone' => '081122334455',
            'description' => 'Synthetic fixture', 'status' => 'approved']);
        $event = Event::create(['organizer_id' => $organizer->id, 'title' => 'Finalization Safety',
            'description' => 'Synthetic fixture', 'location' => 'Manado', 'event_date' => now()->addDays(10),
            'registration_deadline' => now()->addDays(5), 'max_participants' => 100,
            'ticket_price' => 50000, 'category' => 'Running', 'status' => 'active']);
        $ticket = Ticket::create(['user_id' => $user->id, 'event_id' => $event->id,
            'ticket_code' => 'FINALIZATION-SAFETY-001', 'status' => 'pending']);
        $payment = Payment::create(['ticket_id' => $ticket->id, 'user_id' => $user->id,
            'amount' => 50000, 'method' => 'ewallet', 'status' => 'pending']);

        return [$user, $event, $ticket, $payment, $organizerUser];
    }
}
