<?php

namespace Midtrans {
    // ApiRequestor's built-in transport seam; never load the vendor PHPUnit suite.
    if (!class_exists(MT_Tests::class, false)) {
        class MT_Tests
        {
            public static $stubHttp = false;
            public static $stubHttpResponse;
            public static $lastHttpRequest;
        }
    }
}

namespace Tests\Feature {
    use App\Models\Event;
    use App\Models\Organizer;
    use App\Models\Payment;
    use App\Models\Ticket;
    use App\Models\User;
    use Illuminate\Foundation\Testing\RefreshDatabase;
    use Illuminate\Support\Facades\Storage;
    use Midtrans\MT_Tests;
    use Tests\TestCase;

    class OneTimePollingRegressionTest extends TestCase
    {
        use RefreshDatabase;

        protected function setUp(): void
        {
            parent::setUp();

            Storage::fake('public');
            config([
                'services.midtrans.server_key' => 'polling-regression-server-key',
                'services.midtrans.client_key' => 'polling-regression-client-key',
                'services.midtrans.is_production' => false,
                'services.midtrans.demo' => false,
            ]);
            MT_Tests::$stubHttp = true;
            MT_Tests::$lastHttpRequest = null;
            // Any unexpected SDK request still stays inside the fake transport.
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

        public function test_used_ticket_polling_never_calls_midtrans_or_reactivates_ticket(): void
        {
            [$user, $event, $ticket, $payment] = $this->pendingPurchase();
            $usedAt = now()->subMinutes(10)->startOfSecond();
            $ticket->update(['status' => 'used', 'used_at' => $usedAt]);

            $this->actingAs($user)->get(route('tickets.check-payment', $ticket))
                ->assertRedirect(route('tickets.show', $ticket));

            $this->assertNull(MT_Tests::$lastHttpRequest);
            $this->assertSame('used', $ticket->fresh()->status);
            $this->assertTrue($ticket->fresh()->used_at->equalTo($usedAt));
            $this->assertSame('pending', $payment->fresh()->status);
            $this->assertNull($ticket->fresh()->qr_code_path);
        }

        public function test_legitimate_pending_settlement_activates_ticket_and_creates_qr(): void
        {
            [$user, $event, $ticket, $payment] = $this->pendingPurchase();
            $response = $this->settlementResponse();

            $this->actingAs($user)->get(route('tickets.check-payment', $ticket))
                ->assertRedirect(route('tickets.show', $ticket));

            $this->assertStatusRequest($ticket, $response);
            $this->assertSame('active', $ticket->fresh()->status);
            $this->assertNull($ticket->fresh()->used_at);
            $this->assertSame('confirmed', $payment->fresh()->status);
            $this->assertSame('qris', $payment->fresh()->payment_type);
            $this->assertSame('polling-settlement-id', $payment->fresh()->midtrans_transaction_id);
            $this->assertNotEmpty($ticket->fresh()->qr_code_path);
            Storage::disk('public')->assertExists($ticket->fresh()->qr_code_path);
        }

        public function test_ticket_used_during_sdk_response_cannot_be_reactivated_from_stale_binding(): void
        {
            [$user, $event, $ticket, $payment] = $this->pendingPurchase();
            $usedAt = now()->subMinute()->startOfSecond();
            $response = $this->settlementResponse(function () use ($ticket, $usedAt) {
                // A different writer commits after route binding and the pending guard.
                Ticket::whereKey($ticket->id)->update(['status' => 'used', 'used_at' => $usedAt]);
            });

            $this->actingAs($user)->get(route('tickets.check-payment', $ticket))
                ->assertRedirect(route('tickets.show', $ticket));

            $this->assertStatusRequest($ticket, $response);
            $this->assertSame('used', $ticket->fresh()->status);
            $this->assertTrue($ticket->fresh()->used_at->equalTo($usedAt));
            $this->assertNull($ticket->fresh()->qr_code_path);
            Storage::disk('public')->assertMissing("qr-codes/{$ticket->ticket_code}.svg");
        }

        public function test_payment_confirmed_during_sdk_response_keeps_existing_confirmation_metadata(): void
        {
            [$user, $event, $ticket, $payment] = $this->pendingPurchase();
            $response = $this->settlementResponse(function () use ($payment) {
                Payment::whereKey($payment->id)->update([
                    'status' => 'confirmed',
                    'payment_type' => 'bank_transfer',
                    'midtrans_transaction_id' => 'already-confirmed-id',
                    'notes' => 'Existing confirmation must be preserved.',
                ]);
            });

            $this->actingAs($user)->get(route('tickets.check-payment', $ticket))
                ->assertRedirect(route('tickets.show', $ticket));

            $this->assertStatusRequest($ticket, $response);
            $this->assertDatabaseHas('payments', [
                'id' => $payment->id,
                'status' => 'confirmed',
                'payment_type' => 'bank_transfer',
                'midtrans_transaction_id' => 'already-confirmed-id',
                'notes' => 'Existing confirmation must be preserved.',
            ]);
            $this->assertSame('pending', $ticket->fresh()->status);
            $this->assertNull($ticket->fresh()->qr_code_path);
        }

        public function test_settlement_activates_its_existing_pending_reservation_without_a_second_capacity_check(): void
        {
            [$user, $event, $ticket, $payment] = $this->pendingPurchase();
            $event->update(['max_participants' => 1]);
            $response = $this->settlementResponse();

            $this->actingAs($user)->get(route('tickets.check-payment', $ticket))
                ->assertRedirect(route('tickets.show', $ticket));

            $this->assertStatusRequest($ticket, $response);
            $this->assertSame('confirmed', $payment->fresh()->status);
            $this->assertNull($payment->fresh()->notes);
            $this->assertSame('active', $ticket->fresh()->status);
            $this->assertNotNull($ticket->fresh()->qr_code_path);
            Storage::disk('public')->assertExists("qr-codes/{$ticket->ticket_code}.svg");
        }

        public function test_demo_endpoint_cannot_reactivate_used_ticket(): void
        {
            [$user, $event, $ticket, $payment] = $this->pendingPurchase();
            config(['services.midtrans.demo' => true]);
            $usedAt = now()->subMinute()->startOfSecond();
            $ticket->update(['status' => 'used', 'used_at' => $usedAt]);

            $this->actingAs($user)->post(route('tickets.demo-pay', $ticket))->assertStatus(422);

            $this->assertSame('used', $ticket->fresh()->status);
            $this->assertTrue($ticket->fresh()->used_at->equalTo($usedAt));
            $this->assertSame('pending', $payment->fresh()->status);
            $this->assertNull(MT_Tests::$lastHttpRequest);
        }

        public function test_demo_endpoint_is_blocked_in_production_even_when_demo_flag_is_enabled(): void
        {
            [$user, $event, $ticket, $payment] = $this->pendingPurchase();
            config(['services.midtrans.demo' => true]);
            $previousEnvironment = $this->app['env'];
            $this->app['env'] = 'production';

            try {
                $this->actingAs($user)->withSession(['_token' => 'synthetic-csrf'])
                    ->post(route('tickets.demo-pay', $ticket), ['_token' => 'synthetic-csrf'])->assertStatus(404);
            } finally {
                $this->app['env'] = $previousEnvironment;
            }

            $this->assertSame('pending', $ticket->fresh()->status);
            $this->assertSame('pending', $payment->fresh()->status);
            $this->assertNull($ticket->fresh()->qr_code_path);
            $this->assertNull(MT_Tests::$lastHttpRequest);
        }

        private function pendingPurchase(): array
        {
            $user = User::factory()->create(['role' => 'user']);
            $organizerUser = User::factory()->create(['role' => 'organizer']);
            $organizer = Organizer::create([
                'user_id' => $organizerUser->id,
                'organization_name' => 'Polling Regression Organizer',
                'phone' => '081122334455',
                'description' => 'Synthetic regression fixture',
                'status' => 'approved',
            ]);
            $event = Event::create([
                'organizer_id' => $organizer->id,
                'title' => 'Polling Regression Event',
                'description' => 'Synthetic regression fixture',
                'location' => 'Manado',
                'event_date' => now()->addDays(10),
                'registration_deadline' => now()->addDays(5),
                'max_participants' => 100,
                'ticket_price' => 50000,
                'category' => 'Running',
                'status' => 'active',
            ]);
            $ticket = Ticket::create([
                'user_id' => $user->id,
                'event_id' => $event->id,
                'ticket_code' => 'POLLING-REGRESSION-001',
                'status' => 'pending',
            ]);
            $payment = Payment::create([
                'ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'amount' => 50000,
                'method' => 'ewallet',
                'status' => 'pending',
            ]);

            return [$user, $event, $ticket, $payment];
        }

        private function settlementResponse(?\Closure $beforeDecode = null): object
        {
            // processStubed returns this object; json_decode coerces it to a string.
            // The callback therefore runs inside the actual SDK response, after
            // controller guards, without aliases, subprocesses, or network I/O.
            $response = new class($beforeDecode) {
                public int $reads = 0;

                public function __construct(private ?\Closure $beforeDecode)
                {
                }

                public function __toString(): string
                {
                    $this->reads++;
                    if ($this->reads === 1 && $this->beforeDecode !== null) {
                        ($this->beforeDecode)();
                    }

                    return '{"status_code":"200","order_id":"POLLING-REGRESSION-001","gross_amount":"50000.00","transaction_status":"settlement","payment_type":"qris","transaction_id":"polling-settlement-id"}';
                }
            };
            MT_Tests::$stubHttpResponse = $response;

            return $response;
        }

        private function assertStatusRequest(Ticket $ticket, object $response): void
        {
            $this->assertSame(1, $response->reads, 'The SDK must consume exactly one stub response.');
            $this->assertNotNull(MT_Tests::$lastHttpRequest);
            $this->assertSame('GET', MT_Tests::$lastHttpRequest['GET']);
            $this->assertSame(
                'https://api.sandbox.midtrans.com/v2/' . $ticket->ticket_code . '/status',
                MT_Tests::$lastHttpRequest['url']
            );
            $this->assertSame('polling-regression-server-key', MT_Tests::$lastHttpRequest['server_key']);
        }
    }
}
