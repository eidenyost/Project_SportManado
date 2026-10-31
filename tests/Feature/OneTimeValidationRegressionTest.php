<?php

namespace Tests\Feature;

use App\Models\{Event, Organizer, Payment, Ticket, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OneTimeValidationRegressionTest extends TestCase
{
    use RefreshDatabase;

    private User $attendee;
    private User $owner;
    private Event $event;
    private int $sequence = 0;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        config(['services.midtrans.demo' => false, 'services.midtrans.server_key' => 'dummy-server-key']);
        $this->attendee = User::factory()->create(['role' => 'user']);
        $this->owner = User::factory()->create(['role' => 'organizer']);
        $organizer = Organizer::create(['user_id' => $this->owner->id, 'organization_name' => 'Synthetic Sports', 'phone' => '0800000000', 'description' => 'Synthetic fixture', 'status' => 'approved']);
        $this->event = Event::create(['organizer_id' => $organizer->id, 'title' => 'Synthetic One-Time Event', 'description' => 'Synthetic fixture', 'location' => 'Manado', 'event_date' => now()->addDays(10), 'registration_deadline' => now()->addDays(5), 'max_participants' => 10, 'ticket_price' => 50000, 'category' => 'Running', 'status' => 'active', 'auto_approve' => true]);
    }

    private function ticket(string $status = 'used', string $paymentStatus = 'confirmed'): Ticket
    {
        $ticket = Ticket::create(['user_id' => $this->attendee->id, 'event_id' => $this->event->id, 'ticket_code' => 'REG-'.(++$this->sequence), 'status' => $status, 'used_at' => $status === 'used' ? now()->subHour() : null]);
        Payment::create(['ticket_id' => $ticket->id, 'user_id' => $this->attendee->id, 'amount' => 50000, 'status' => $paymentStatus, 'method' => 'ewallet']);
        return $ticket;
    }

    private function webhook(Ticket $ticket, string $status = 'settlement', bool $valid = true)
    {
        $payload = ['order_id' => $ticket->ticket_code, 'status_code' => '200', 'gross_amount' => '50000.00', 'transaction_status' => $status, 'payment_type' => 'qris', 'transaction_id' => 'synthetic-transaction'];
        $payload['signature_key'] = $valid ? hash('sha512', $payload['order_id'].'20050000.00dummy-server-key') : 'invalid';
        return $this->postJson(route('payments.notification'), $payload);
    }

    private function assertUsedUnchanged(Ticket $ticket, string $usedAt): void
    {
        $this->assertSame('used', $ticket->fresh()->status, 'A consumed ticket must never become reusable.');
        $this->assertSame($usedAt, $ticket->fresh()->used_at->toDateTimeString());
        $this->assertSame('confirmed', $ticket->payment()->first()->status, 'Late notifications cannot revoke a confirmed consumed ticket.');
    }

    private function scan(Ticket $ticket)
    {
        return $this->actingAs($this->owner)->postJson(route('organizer.events.validate-qr', $this->event), ['ticket_code' => $ticket->ticket_code]);
    }

    public function test_reg01_settlement_preserves_used_ticket(): void
    {
        $ticket = $this->ticket(); $at = $ticket->used_at->toDateTimeString();
        $this->webhook($ticket)->assertOk();
        $this->assertUsedUnchanged($ticket, $at);
    }

    public function test_reg02_capture_preserves_used_ticket(): void
    {
        $ticket = $this->ticket(); $at = $ticket->used_at->toDateTimeString();
        $this->webhook($ticket, 'capture')->assertOk();
        $this->assertUsedUnchanged($ticket, $at);
    }

    public function test_reg03_full_event_preserves_used_ticket(): void
    {
        $this->event->update(['max_participants' => 1]);
        $ticket = $this->ticket(); $at = $ticket->used_at->toDateTimeString();
        $this->webhook($ticket)->assertOk();
        $this->assertUsedUnchanged($ticket, $at);
    }

    public function test_reg04_late_expire_preserves_used_ticket(): void
    {
        $ticket = $this->ticket(); $at = $ticket->used_at->toDateTimeString();
        $this->webhook($ticket, 'expire')->assertOk();
        $this->assertUsedUnchanged($ticket, $at);
    }

    public function test_reg05_late_cancel_preserves_used_ticket(): void
    {
        $ticket = $this->ticket(); $at = $ticket->used_at->toDateTimeString();
        $this->webhook($ticket, 'cancel')->assertOk();
        $this->assertUsedUnchanged($ticket, $at);
    }

    public function test_reg06_late_deny_preserves_used_ticket(): void
    {
        $ticket = $this->ticket(); $at = $ticket->used_at->toDateTimeString();
        $this->webhook($ticket, 'deny')->assertOk();
        $this->assertUsedUnchanged($ticket, $at);
    }

    public function test_reg07_duplicate_settlement_at_capacity_keeps_confirmed_active_ticket(): void
    {
        $this->event->update(['max_participants' => 1]);
        $ticket = $this->ticket('active');
        $this->webhook($ticket)->assertOk();
        $this->webhook($ticket)->assertOk();
        $this->assertSame('active', $ticket->fresh()->status);
        $this->assertSame('confirmed', $ticket->payment()->first()->status);
    }

    public function test_reg08_settlement_does_not_resurrect_cancelled_ticket(): void
    {
        $ticket = $this->ticket('cancelled', 'rejected');
        $this->webhook($ticket)->assertOk();
        $this->assertSame('cancelled', $ticket->fresh()->status);
    }

    public function test_reg09_demo_webhook_preserves_used_ticket(): void
    {
        config(['services.midtrans.demo' => true]);
        $ticket = $this->ticket(); $at = $ticket->used_at->toDateTimeString();
        $this->actingAs($this->attendee);
        $this->webhook($ticket)->assertOk();
        $this->assertUsedUnchanged($ticket, $at);
    }

    public function test_reg10_demo_payment_cannot_reactivate_used_ticket(): void
    {
        config(['services.midtrans.demo' => true]);
        $ticket = $this->ticket(); $at = $ticket->used_at->toDateTimeString();
        $this->actingAs($this->attendee)->post(route('tickets.demo-pay', $ticket))->assertStatus(422);
        $this->assertUsedUnchanged($ticket, $at);
    }

    public function test_reg11_invalid_signature_is_rejected_without_mutation(): void
    {
        $ticket = $this->ticket(); $at = $ticket->used_at->toDateTimeString();
        $this->webhook($ticket, 'settlement', false)->assertForbidden();
        $this->assertUsedUnchanged($ticket, $at);
    }

    public function test_reg12_legitimate_paid_settlement_activates_pending_ticket_with_qr(): void
    {
        $ticket = $this->ticket('pending', 'pending');
        $this->webhook($ticket)->assertOk();
        $this->assertSame('active', $ticket->fresh()->status);
        $this->assertSame('confirmed', $ticket->payment()->first()->status);
        $this->assertNotNull($ticket->fresh()->qr_code_path);
        Storage::disk('public')->assertExists($ticket->fresh()->qr_code_path);
    }

    public function test_reg13_legitimate_free_auto_registration_generates_qr_and_only_one_entry(): void
    {
        $this->event->update(['ticket_price' => 0, 'auto_approve' => true]);
        $this->actingAs($this->attendee)->post(route('tickets.submit-payment', $this->event))->assertRedirect();
        $ticket = Ticket::firstOrFail();
        $this->assertSame('active', $ticket->status);
        $this->assertNull($ticket->payment);
        Storage::disk('public')->assertExists($ticket->qr_code_path);
        $this->scan($ticket)->assertOk()->assertJson(['valid' => true]);
        $this->scan($ticket)->assertStatus(422)->assertJson(['valid' => false]);
    }

    public function test_reg14_legitimate_free_manual_approval_generates_qr(): void
    {
        $this->event->update(['ticket_price' => 0, 'auto_approve' => false]);
        $this->actingAs($this->attendee)->post(route('tickets.submit-payment', $this->event))->assertRedirect();
        $ticket = Ticket::firstOrFail();
        $this->assertSame('pending', $ticket->status);
        $this->actingAs($this->owner)->post(route('organizer.events.participants.approve', [$this->event, $ticket]))->assertRedirect();
        $this->assertSame('active', $ticket->fresh()->status);
        Storage::disk('public')->assertExists($ticket->fresh()->qr_code_path);
        $this->scan($ticket)->assertOk()->assertJson(['valid' => true]);
        $this->scan($ticket)->assertStatus(422)->assertJson(['valid' => false]);
    }

    public function test_reg15_used_qr_cannot_be_scanned_twice(): void
    {
        $ticket = $this->ticket('active');
        $this->scan($ticket)->assertOk()->assertJson(['valid' => true]);
        $at = $ticket->fresh()->used_at->toDateTimeString();
        $this->scan($ticket)->assertStatus(422)->assertJson(['valid' => false]);
        $this->assertUsedUnchanged($ticket, $at);
    }

    public function test_reg16_late_failure_does_not_revoke_confirmed_active_ticket(): void
    {
        $ticket = $this->ticket('active');
        $this->webhook($ticket, 'expire')->assertOk();
        $this->assertSame('active', $ticket->fresh()->status);
        $this->assertSame('confirmed', $ticket->payment()->first()->status);
    }

    public function test_repeated_identical_settlement_preserves_first_used_timestamp(): void
    {
        $ticket = $this->ticket();
        $at = $ticket->used_at->toDateTimeString();
        for ($i = 0; $i < 5; $i++) {
            $this->travel(1)->minutes();
            $this->webhook($ticket)->assertOk();
            $this->assertUsedUnchanged($ticket, $at);
        }
    }

    public function test_failed_pending_payment_is_cancelled_atomically(): void
    {
        foreach (['expire', 'cancel', 'deny'] as $status) {
            $ticket = $this->ticket('pending', 'pending');
            $this->webhook($ticket, $status)->assertOk();
            $this->assertSame('cancelled', $ticket->fresh()->status);
            $this->assertSame('rejected', $ticket->payment()->first()->status);
            $this->assertNull($ticket->fresh()->used_at);
            $this->assertNull($ticket->fresh()->qr_code_path);
            $ticket->payment()->delete();
            $ticket->delete();
        }
    }

    public function test_e2e_registration_payment_scan_duplicate_notification_second_scan_rejected(): void
    {
        $this->actingAs($this->attendee)->post(route('tickets.submit-payment', $this->event))->assertRedirect();
        $ticket = Ticket::firstOrFail();
        $this->assertSame('pending', $ticket->status);
        $this->assertSame('pending', $ticket->payment()->first()->status);
        $this->assertNull($ticket->qr_code_path);
        $this->webhook($ticket)->assertOk();
        $this->assertSame('active', $ticket->fresh()->status);
        $this->assertSame('confirmed', $ticket->payment()->first()->status);
        $this->assertNotNull($ticket->fresh()->qr_code_path);
        Storage::disk('public')->assertExists($ticket->fresh()->qr_code_path);
        $this->scan($ticket)->assertOk()->assertJson(['valid' => true]);
        $this->assertSame('used', $ticket->fresh()->status);
        $this->assertNotNull($ticket->fresh()->used_at);
        $at = $ticket->fresh()->used_at->toDateTimeString();
        $this->webhook($ticket)->assertOk();
        $this->assertUsedUnchanged($ticket, $at);
        $this->scan($ticket)->assertStatus(422)->assertJson(['valid' => false]);
        $this->assertUsedUnchanged($ticket, $at);
    }
}
