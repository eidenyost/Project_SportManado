<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Organizer;
use App\Models\Payment;
use App\Models\Ticket;
use App\Models\User;
use App\Services\PaymentFinalizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CapacityReservationTest extends TestCase
{
    use RefreshDatabase;

    private Organizer $organizer;
    private User $organizerUser;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->organizerUser = User::factory()->create(['role' => 'organizer', 'email_verified_at' => now()]);
        $this->organizer = Organizer::create([
            'user_id' => $this->organizerUser->id,
            'organization_name' => 'Capacity Organizer',
            'phone' => '08123456789',
            'status' => 'approved',
        ]);
    }

    public function test_pending_active_and_used_tickets_allocate_capacity_but_cancelled_does_not(): void
    {
        $event = $this->event(['max_participants' => 3]);
        $this->ticket($event, 'pending');
        $this->ticket($event, 'active');
        $this->ticket($event, 'used');
        $this->ticket($event, 'cancelled');

        $this->assertSame(3, $event->allocatedTicketCount());
        $this->assertSame(0, $event->remainingCapacity());
        $this->assertTrue($event->isFull());
    }

    public function test_paid_pending_reservation_blocks_the_last_slot_before_creating_ticket_or_payment(): void
    {
        $event = $this->event(['max_participants' => 1, 'ticket_price' => 50000]);
        $this->paidTicket($event, 'pending');
        $newUser = User::factory()->create(['role' => 'user', 'email_verified_at' => now()]);

        $this->actingAs($newUser)->post(route('tickets.submit-payment', $event))
            ->assertUnprocessable();

        $this->assertDatabaseCount('tickets', 1);
        $this->assertDatabaseCount('payments', 1);
    }

    public function test_expired_provider_payment_releases_its_pending_reservation_once(): void
    {
        $event = $this->event(['max_participants' => 1, 'ticket_price' => 50000]);
        [$ticket, $payment] = $this->paidTicket($event, 'pending');

        app(PaymentFinalizationService::class)->finalize($ticket, 'expire');
        app(PaymentFinalizationService::class)->finalize($ticket->fresh(), 'expire');

        $this->assertSame('cancelled', $ticket->fresh()->status);
        $this->assertSame('rejected', $payment->fresh()->status);
        $this->assertSame(0, $event->fresh()->allocatedTicketCount());
    }

    public function test_manual_free_approval_keeps_the_existing_pending_allocation(): void
    {
        $event = $this->event(['max_participants' => 1, 'ticket_price' => 0, 'auto_approve' => false]);
        $ticket = $this->ticket($event, 'pending');

        $this->actingAs($this->organizerUser)
            ->post(route('organizer.events.participants.approve', [$event, $ticket]))
            ->assertRedirect(route('organizer.events.participants', $event));

        $this->assertSame('active', $ticket->fresh()->status);
        $this->assertSame(1, $event->fresh()->allocatedTicketCount());
    }

    public function test_manual_rejection_releases_the_pending_reservation(): void
    {
        $event = $this->event(['max_participants' => 1, 'ticket_price' => 0, 'auto_approve' => false]);
        $ticket = $this->ticket($event, 'pending');

        $this->actingAs($this->organizerUser)
            ->post(route('organizer.events.participants.reject', [$event, $ticket]), ['notes' => 'Data tidak sesuai.'])
            ->assertRedirect(route('organizer.events.participants', $event));

        $this->assertSame('cancelled', $ticket->fresh()->status);
        $this->assertSame(0, $event->fresh()->allocatedTicketCount());
    }

    public function test_settlement_activates_its_existing_pending_reservation_without_a_second_slot(): void
    {
        $event = $this->event(['max_participants' => 1, 'ticket_price' => 50000]);
        [$ticket, $payment] = $this->paidTicket($event, 'pending');

        $status = app(PaymentFinalizationService::class)->finalize($ticket, 'settlement', [], true);

        $this->assertSame('active', $status);
        $this->assertSame('active', $ticket->fresh()->status);
        $this->assertSame('confirmed', $payment->fresh()->status);
        $this->assertSame(1, $event->fresh()->allocatedTicketCount());
    }

    public function test_late_settlement_of_released_reservation_is_recorded_without_reactivating_ticket(): void
    {
        $event = $this->event(['max_participants' => 1, 'ticket_price' => 50000]);
        [$ticket, $payment] = $this->paidTicket($event, 'cancelled');

        $status = app(PaymentFinalizationService::class)->finalize($ticket, 'settlement', [], false, [
            'order_id' => $ticket->ticket_code,
            'transaction_status' => 'settlement',
            'status_code' => '200',
            'gross_amount' => '50000.00',
            'payment_type' => 'bank_transfer',
            'transaction_id' => 'late-settlement-transaction',
        ]);

        $this->assertSame('cancelled', $status);
        $this->assertSame('cancelled', $ticket->fresh()->status);
        $this->assertSame('confirmed', $payment->fresh()->status);
        $this->assertStringContainsString('rekonsiliasi', $payment->fresh()->notes);
        $this->assertSame(0, $event->fresh()->allocatedTicketCount());
    }

    private function event(array $overrides = []): Event
    {
        return Event::create(array_replace([
            'organizer_id' => $this->organizer->id,
            'title' => 'Capacity Event',
            'description' => 'Capacity fixture',
            'location' => 'Manado',
            'event_date' => now()->addDays(10),
            'registration_deadline' => now()->addDays(5),
            'max_participants' => 10,
            'ticket_price' => 0,
            'category' => 'Futsal',
            'status' => 'active',
            'auto_approve' => true,
        ], $overrides));
    }

    private function ticket(Event $event, string $status): Ticket
    {
        return Ticket::create([
            'user_id' => User::factory()->create(['role' => 'user'])->id,
            'event_id' => $event->id,
            'ticket_code' => strtoupper(fake()->unique()->bothify('CAP-??????')),
            'status' => $status,
            'used_at' => $status === 'used' ? now() : null,
        ]);
    }

    private function paidTicket(Event $event, string $status): array
    {
        $ticket = $this->ticket($event, $status);
        $payment = Payment::create([
            'ticket_id' => $ticket->id,
            'user_id' => $ticket->user_id,
            'amount' => $event->ticket_price,
            'method' => 'ewallet',
            'status' => 'pending',
        ]);

        return [$ticket, $payment];
    }
}
