<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Organizer;
use App\Models\Payment;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Midtrans\MT_Tests;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

// Reuse only the guarded SDK transport seam; do not include vendor test helpers.
require_once __DIR__ . '/OneTimePollingRegressionTest.php';

/**
 * Stage 1 payment trust-boundary regression contract.
 * PAY21 stale-state races remain covered by OneTimePollingRegressionTest.
 * PAY22 atomic rollback remains covered by OneTimeFinalizationSafetyTest::
 * test_failure_after_qr_callback_rolls_back_ticket_and_payment_database_rows.
 * Those tests deliberately are not duplicated here.
 */
class Stage1PaymentSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        config([
            'services.midtrans.server_key' => 'stage1-server-key',
            'services.midtrans.client_key' => 'stage1-client-key',
            'services.midtrans.demo' => false,
            'services.midtrans.is_production' => false,
        ]);
        MT_Tests::$stubHttp = true;
        MT_Tests::$lastHttpRequest = null;
        MT_Tests::$stubHttpResponse = '{"status_code":"404","status_message":"Unexpected SDK request"}';
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

    // PAY01: a correctly signed, exact-amount settlement (status_code 200).
    public function test_signed_settlement_confirms_and_creates_qr(): void
    {
        [, , $ticket, $payment] = $this->purchase();
        $this->notify($this->payload($ticket))->assertOk();
        $this->assertActivated($ticket, $payment);
    }

    // PAY02: signature rejection happens without any persisted mutation.
    public function test_invalid_signature_is_forbidden(): void
    {
        [, , $ticket] = $this->purchase();
        $before = $this->snapshot();
        $payload = $this->payload($ticket);
        $payload['signature_key'] = str_repeat('0', 128);
        $this->postJson(route('payments.notification'), $payload)->assertForbidden();
        $this->assertUnchanged($before);
    }

    // PAY03: an authenticated provider message for an unknown order is 404.
    public function test_unknown_signed_order_is_not_found(): void
    {
        [, , $ticket] = $this->purchase();
        $before = $this->snapshot();
        $this->notify(array_replace($this->payload($ticket), ['order_id' => 'UNKNOWN-ORDER']))->assertNotFound();
        $this->assertUnchanged($before);
    }

    public static function invalidAmounts(): array
    {
        return [
            'PAY04 smaller' => ['49999.99'],
            'PAY05 larger' => ['50000.01'],
            'PAY06 malformed' => ['not-an-amount'],
            'PAY07 array' => [['50000.00']],
            'PAY08 scientific notation' => ['5e4'],
            'PAY09 more than two decimals' => ['50000.000'],
        ];
    }

    #[DataProvider('invalidAmounts')]
    public function test_callback_rejects_invalid_amount_without_mutation(mixed $amount): void
    {
        [, , $ticket] = $this->purchase();
        $before = $this->snapshot();
        $this->notify(array_replace($this->payload($ticket), ['gross_amount' => $amount]))->assertUnprocessable();
        $this->assertUnchanged($before);
    }

    // PAY10: capture requires an explicitly accepted credit-card fraud verdict.
    #[DataProvider('unacceptedFraud')]
    public function test_capture_challenge_or_deny_never_activates(string $fraud): void
    {
        [, , $ticket] = $this->purchase();
        $before = $this->snapshot();
        $response = $this->notify(array_replace($this->payload($ticket), [
            'transaction_status' => 'capture', 'payment_type' => 'credit_card', 'fraud_status' => $fraud,
        ]));
        $this->assertContains($response->getStatusCode(), [200, 422]);
        $this->assertUnchanged($before);
    }

    public static function unacceptedFraud(): array
    {
        return ['challenge' => ['challenge'], 'deny' => ['deny']];
    }

    // PAY11: legitimate capture with accepted fraud is not blocked.
    public function test_accepted_credit_card_capture_is_legitimate(): void
    {
        [, , $ticket, $payment] = $this->purchase();
        $this->notify(array_replace($this->payload($ticket), [
            'transaction_status' => 'capture', 'payment_type' => 'credit_card', 'fraud_status' => 'accept',
        ]))->assertOk();
        $this->assertActivated($ticket, $payment, 'credit_card');
    }

    // PAY12: settlement without fraud_status is legitimate (covered also by PAY01).
    public function test_settlement_without_fraud_status_is_accepted(): void
    {
        [$user, , $ticket, $payment] = $this->purchase();
        $this->poll($user, $ticket, $this->payload($ticket))->assertRedirect(route('tickets.show', $ticket));
        $this->assertActivated($ticket, $payment);
    }

    // PAY13: provider cannot replace an already-bound transaction identifier.
    public function test_stored_transaction_id_mismatch_is_conflict(): void
    {
        [, , $ticket, $payment] = $this->purchase();
        $payment->update(['midtrans_transaction_id' => 'bound-transaction']);
        $before = $this->snapshot();
        $this->notify($this->payload($ticket))->assertStatus(409);
        $this->assertUnchanged($before);
    }

    // PAY14: never pick the first of multiple payment records silently.
    public function test_duplicate_payment_relation_is_unprocessable(): void
    {
        [, , $ticket, $payment] = $this->purchase();
        $duplicate = $payment->replicate();
        $duplicate->save();
        $before = $this->snapshot();
        $this->notify($this->payload($ticket))->assertUnprocessable();
        $this->assertUnchanged($before);
    }

    // PAY15: ticket owner and payment owner must agree.
    public function test_payment_owner_mismatch_is_unprocessable(): void
    {
        [, , $ticket, $payment] = $this->purchase();
        $payment->update(['user_id' => User::factory()->create()->id]);
        $before = $this->snapshot();
        $this->notify($this->payload($ticket))->assertUnprocessable();
        $this->assertUnchanged($before);
    }

    // PAY16: demo callback remains public in routing, but must authenticate owners.
    public function test_guest_demo_callback_returns_json_unauthorized(): void
    {
        [, , $ticket] = $this->purchase();
        config(['services.midtrans.demo' => true]);
        $before = $this->snapshot();
        $this->postJson(route('payments.notification'), ['order_id' => $ticket->ticket_code])
            ->assertUnauthorized()->assertHeader('content-type', 'application/json');
        $this->assertUnchanged($before);
    }

    public function test_nonowner_demo_callback_is_forbidden(): void
    {
        [, , $ticket] = $this->purchase();
        config(['services.midtrans.demo' => true]);
        $before = $this->snapshot();
        $this->actingAs(User::factory()->create())->postJson(route('payments.notification'), [
            'order_id' => $ticket->ticket_code,
        ])->assertForbidden();
        $this->assertUnchanged($before);
    }

    // PAY17: local simulation is an explicit opt-in and works for the owner.
    public function test_explicit_local_demo_callback_works_for_owner(): void
    {
        [$user, , $ticket, $payment] = $this->purchase();
        $previous = $this->app['env'];
        $this->app['env'] = 'local';
        config(['services.midtrans.demo' => true]);
        try {
            $this->actingAs($user)->postJson(route('payments.notification'), [
                'order_id' => $ticket->ticket_code,
            ])->assertOk();
        } finally {
            $this->app['env'] = $previous;
        }
        $this->assertSame('active', $ticket->fresh()->status);
        $this->assertSame('confirmed', $payment->fresh()->status);
        Storage::disk('public')->assertExists($ticket->fresh()->qr_code_path);
        $this->assertNull(MT_Tests::$lastHttpRequest);
    }

    // PAY18: either production guard independently disables simulation.
    #[DataProvider('productionModes')]
    public function test_demo_callback_is_not_found_in_production(string $environment, bool $providerProduction): void
    {
        [$user, , $ticket] = $this->purchase();
        config(['services.midtrans.demo' => true, 'services.midtrans.is_production' => $providerProduction]);
        $before = $this->snapshot();
        $previous = $this->app['env'];
        $this->app['env'] = $environment;
        try {
            $this->actingAs($user)->postJson(route('payments.notification'), [
                'order_id' => $ticket->ticket_code,
            ])->assertNotFound();
        } finally {
            $this->app['env'] = $previous;
        }
        $this->assertUnchanged($before);
    }

    public static function productionModes(): array
    {
        return ['app production' => ['production', false], 'provider production' => ['local', true]];
    }

    // PAY19: inspect the real source fallback, not the setUp config override.
    public function test_demo_config_source_defaults_to_false(): void
    {
        $source = file_get_contents(config_path('services.php'));
        $this->assertMatchesRegularExpression(
            '/[\'"]demo[\'"]\s*=>\s*\(bool\)\s*env\([\'"]MIDTRANS_DEMO[\'"]\s*,\s*false\s*\)/',
            $source
        );
    }

    // PAY20: rendered SDK script follows provider mode, even with a fake test token.
    #[DataProvider('snapModes')]
    public function test_pending_ticket_renders_matching_snap_script(bool $production, string $expected, string $other): void
    {
        [$user, , $ticket] = $this->purchase();
        config(['services.midtrans.is_production' => $production]);
        $this->actingAs($user)->get(route('tickets.show', $ticket))->assertOk()
            ->assertViewHas('snapToken', 'dummy-snap-token-123')
            ->assertSee($expected, false)->assertDontSee($other, false);
        $this->assertNull(MT_Tests::$lastHttpRequest);
    }

    public static function snapModes(): array
    {
        $live = 'https://app.midtrans.com/snap/snap.js';
        $sandbox = 'https://app.sandbox.midtrans.com/snap/snap.js';
        return ['live' => [true, $live, $sandbox], 'sandbox' => [false, $sandbox, $live]];
    }

    // PAY04-15 apply equally to SDK polling: bad evidence cannot mutate rows.
    #[DataProvider('invalidPollingEvidence')]
    public function test_polling_rejects_untrusted_evidence(array $changes, ?string $relation): void
    {
        [$user, , $ticket, $payment] = $this->purchase();
        if ($relation === 'transaction') {
            $payment->update(['midtrans_transaction_id' => 'bound-transaction']);
        } elseif ($relation === 'duplicate') {
            $payment->replicate()->save();
        } elseif ($relation === 'owner') {
            $payment->update(['user_id' => User::factory()->create()->id]);
        }
        $before = $this->snapshot();
        $this->poll($user, $ticket, array_replace($this->payload($ticket), $changes))
            ->assertRedirect(route('tickets.show', $ticket));
        $this->assertNotNull(MT_Tests::$lastHttpRequest);
        $this->assertUnchanged($before);
    }

    public static function invalidPollingEvidence(): array
    {
        $cases = [];
        foreach (self::invalidAmounts() as $name => [$amount]) {
            $cases[$name] = [['gross_amount' => $amount], null];
        }
        return $cases + [
            'unknown order' => [['order_id' => 'OTHER-ORDER'], null],
            'bad status code' => [['status_code' => '500'], null],
            'challenge' => [['transaction_status' => 'capture', 'payment_type' => 'credit_card', 'fraud_status' => 'challenge'], null],
            'deny' => [['transaction_status' => 'capture', 'payment_type' => 'credit_card', 'fraud_status' => 'deny'], null],
            'transaction conflict' => [[], 'transaction'],
            'ambiguous relation' => [[], 'duplicate'],
            'owner conflict' => [[], 'owner'],
        ];
    }

    public function test_polling_accepts_legitimate_credit_card_capture(): void
    {
        [$user, , $ticket, $payment] = $this->purchase();
        $this->poll($user, $ticket, array_replace($this->payload($ticket), [
            'transaction_status' => 'capture', 'payment_type' => 'credit_card', 'fraud_status' => 'accept',
        ]))->assertRedirect(route('tickets.show', $ticket));
        $this->assertActivated($ticket, $payment, 'credit_card');
    }

    /** Supplemental post-patch verification; scenario names, not PAY comments, identify coverage. */
    #[DataProvider('supplementalArrayFields')]
    public function test_supplemental_callback_array_fields_are_rejected(string $field): void
    {
        [, , $ticket] = $this->purchase();
        $before = $this->snapshot();
        $payload = $this->payload($ticket);
        $payload['signature_key'] = hash('sha512', $payload['order_id'] . $payload['status_code']
            . $payload['gross_amount'] . config('services.midtrans.server_key'));
        $payload[$field] = ['invalid-array'];
        $response = $this->postJson(route('payments.notification'), $payload);
        if ($field === 'signature_key') {
            $this->assertContains($response->getStatusCode(), [403, 422], 'Signature shape may be rejected by authentication or validation.');
        } else {
            $response->assertUnprocessable();
        }
        $this->assertUnchanged($before);
    }

    public static function supplementalArrayFields(): array
    {
        return [
            'order_id array' => ['order_id'],
            'status_code array' => ['status_code'],
            'signature array' => ['signature_key'],
            'transaction_status array' => ['transaction_status'],
            'transaction_id array' => ['transaction_id'],
            'payment_type array' => ['payment_type'],
        ];
    }

    public function test_supplemental_success_with_status_code_201_is_rejected(): void
    {
        [, , $ticket] = $this->purchase();
        $before = $this->snapshot();
        $this->notify(array_replace($this->payload($ticket), ['status_code' => '201']))->assertUnprocessable();
        $this->assertUnchanged($before);
    }

    #[DataProvider('supplementalSettlementFraud')]
    public function test_supplemental_settlement_with_unaccepted_fraud_never_mutates(string $fraud, bool $polling): void
    {
        [$user, , $ticket] = $this->purchase();
        $before = $this->snapshot();
        $payload = array_replace($this->payload($ticket), ['fraud_status' => $fraud]);
        if ($polling) {
            $this->poll($user, $ticket, $payload)->assertRedirect(route('tickets.show', $ticket));
            $this->assertNotNull(MT_Tests::$lastHttpRequest);
        } else {
            $response = $this->notify($payload);
            $this->assertContains($response->getStatusCode(), [200, 422]);
        }
        $this->assertUnchanged($before);
    }

    public static function supplementalSettlementFraud(): array
    {
        return [
            'callback challenge' => ['challenge', false],
            'callback deny' => ['deny', false],
            'polling challenge' => ['challenge', true],
            'polling deny' => ['deny', true],
        ];
    }

    public function test_supplemental_used_ticket_with_wrong_amount_is_rejected_before_idempotent_return(): void
    {
        [, , $ticket, $payment] = $this->purchase();
        $ticket->update(['status' => 'active']);
        $ticket->update(['status' => 'used', 'used_at' => now()->subMinute()->startOfSecond()]);
        $payment->update(['status' => 'confirmed', 'midtrans_transaction_id' => 'stage1-transaction']);
        $before = $this->snapshot();
        $this->notify(array_replace($this->payload($ticket), ['gross_amount' => '49999.99']))->assertUnprocessable();
        $this->assertUnchanged($before);
    }

    public function test_supplemental_staging_environment_refuses_explicit_demo_callback(): void
    {
        [$user, , $ticket] = $this->purchase();
        config(['services.midtrans.demo' => true]);
        $before = $this->snapshot();
        $previous = $this->app['env'];
        $this->app['env'] = 'staging';
        try {
            $this->actingAs($user)->postJson(route('payments.notification'), [
                'order_id' => $ticket->ticket_code,
            ])->assertNotFound();
        } finally {
            $this->app['env'] = $previous;
        }
        $this->assertUnchanged($before);
        $this->assertNull(MT_Tests::$lastHttpRequest);
    }

    public function test_supplemental_explicit_false_demo_flag_disables_demo_pay(): void
    {
        [$user, , $ticket] = $this->purchase();
        config(['services.midtrans.demo' => false]);
        $before = $this->snapshot();
        $this->actingAs($user)->postJson(route('tickets.demo-pay', $ticket))->assertNotFound();
        $this->assertUnchanged($before);
        $this->assertNull(MT_Tests::$lastHttpRequest);
    }

    private function payload(Ticket $ticket): array
    {
        return ['order_id' => $ticket->ticket_code, 'gross_amount' => '50000.00',
            'status_code' => '200', 'transaction_status' => 'settlement',
            'payment_type' => 'qris', 'transaction_id' => 'stage1-transaction'];
    }

    private function notify(array $payload)
    {
        // Arrays have no provider signature representation. Keep a synthetic
        // signature so malformed shape must fail validation, never PHP coercion.
        $amount = is_string($payload['gross_amount']) ? $payload['gross_amount'] : 'invalid-array';
        $payload['signature_key'] = hash('sha512', $payload['order_id'] . $payload['status_code']
            . $amount . config('services.midtrans.server_key'));
        return $this->postJson(route('payments.notification'), $payload);
    }

    private function poll(User $user, Ticket $ticket, array $payload)
    {
        MT_Tests::$stubHttpResponse = json_encode($payload, JSON_THROW_ON_ERROR);
        return $this->actingAs($user)->get(route('tickets.check-payment', $ticket));
    }

    private function snapshot(): array
    {
        return [Ticket::orderBy('id')->get()->map->getAttributes()->all(),
            Payment::orderBy('id')->get()->map->getAttributes()->all(),
            Storage::disk('public')->allFiles()];
    }

    private function assertUnchanged(array $before): void
    {
        $this->assertSame($before, $this->snapshot(), 'Rejected evidence must preserve every attribute and QR artifact.');
    }

    private function assertActivated(Ticket $ticket, Payment $payment, string $type = 'qris'): void
    {
        $this->assertSame('active', $ticket->fresh()->status);
        $this->assertNull($ticket->fresh()->used_at);
        $this->assertSame('confirmed', $payment->fresh()->status);
        $this->assertSame($type, $payment->fresh()->payment_type);
        $this->assertSame('stage1-transaction', $payment->fresh()->midtrans_transaction_id);
        $this->assertNotEmpty($ticket->fresh()->qr_code_path);
        Storage::disk('public')->assertExists($ticket->fresh()->qr_code_path);
    }

    private function purchase(): array
    {
        $user = User::factory()->create(['role' => 'user']);
        $organizerUser = User::factory()->create(['role' => 'organizer']);
        $organizer = Organizer::create(['user_id' => $organizerUser->id,
            'organization_name' => 'Stage 1 Security Organizer', 'phone' => '081122334455',
            'description' => 'Synthetic security fixture', 'status' => 'approved']);
        $event = Event::create(['organizer_id' => $organizer->id, 'title' => 'Stage 1 Payment Security',
            'description' => 'Synthetic security fixture', 'location' => 'Manado',
            'event_date' => now()->addDays(10), 'registration_deadline' => now()->addDays(5),
            'max_participants' => 100, 'ticket_price' => 50000, 'category' => 'Running', 'status' => 'active']);
        $ticket = Ticket::create(['user_id' => $user->id, 'event_id' => $event->id,
            'ticket_code' => 'STAGE1-SECURITY-001', 'status' => 'pending']);
        $payment = Payment::create(['ticket_id' => $ticket->id, 'user_id' => $user->id,
            'amount' => 50000, 'method' => 'ewallet', 'status' => 'pending']);
        return [$user, $event, $ticket, $payment];
    }
}
