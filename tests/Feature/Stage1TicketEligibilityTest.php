<?php

namespace Tests\Feature;

use App\Models\{Event, Organizer, Payment, Ticket, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Stage1TicketEligibilityTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private User $attendee;
    private Event $event;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->owner = User::factory()->create(['role' => 'organizer']);
        $this->attendee = User::factory()->create(['role' => 'user']);
        $organizer = Organizer::create([
            'user_id' => $this->owner->id, 'organization_name' => 'Stage 1 Sports',
            'phone' => '0800000000', 'description' => 'Synthetic fixture', 'status' => 'approved',
        ]);
        $this->event = Event::create([
            'organizer_id' => $organizer->id, 'title' => 'Stage 1 Eligibility',
            'description' => 'Synthetic fixture', 'location' => 'Manado',
            'event_date' => now()->addDays(10), 'registration_deadline' => now()->addDays(5),
            'max_participants' => 10, 'ticket_price' => 50000, 'category' => 'Running',
            'status' => 'active', 'auto_approve' => true,
        ]);
    }

    private function ticket(string $status = 'active'): Ticket
    {
        return Ticket::create([
            'user_id' => $this->attendee->id, 'event_id' => $this->event->id,
            'ticket_code' => 'SCAN01TEST', 'status' => $status,
        ]);
    }

    private function payment(Ticket $ticket, string $status = 'confirmed', ?int $userId = null): Payment
    {
        return Payment::create([
            'ticket_id' => $ticket->id, 'user_id' => $userId ?? $ticket->user_id,
            'amount' => 50000, 'method' => 'ewallet', 'status' => $status,
        ]);
    }

    private function scan(mixed $code)
    {
        return $this->actingAs($this->owner)->postJson(
            route('organizer.events.validate-qr', $this->event), ['ticket_code' => $code]
        );
    }

    private function snapshot(): array
    {
        return [
            Event::orderBy('id')->get()->toArray(),
            Ticket::orderBy('id')->get()->toArray(),
            Payment::orderBy('id')->get()->toArray(),
            Storage::disk('public')->allFiles(),
        ];
    }

    private function assertDeniedWithoutWrites(Ticket $ticket, int $status = 422): void
    {
        $before = $this->snapshot();
        $this->scan($ticket->ticket_code)->assertStatus($status)->assertJson(['valid' => false]);
        $this->assertSame($before, $this->snapshot());
    }

    public function test_scan01_active_free_ticket_can_enter_without_payment(): void
    {
        $this->event->update(['ticket_price' => 0]);
        $ticket = $this->ticket();
        $this->scan($ticket->ticket_code)->assertOk()->assertJson([
            'valid' => true, 'code' => 200, 'ticket_code' => $ticket->ticket_code,
            'attendee' => $this->attendee->name, 'email' => $this->attendee->email,
        ])->assertJsonStructure(['message', 'used_at']);
        $this->assertSame('used', $ticket->fresh()->status);
        $this->assertNotNull($ticket->fresh()->used_at);
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_scan02_paid_ticket_requires_matching_confirmed_payment_and_preserves_it(): void
    {
        $ticket = $this->ticket();
        $payment = $this->payment($ticket);
        $before = $payment->fresh()->toArray();
        $this->scan($ticket->ticket_code)->assertOk()->assertJson(['valid' => true]);
        $this->assertSame('used', $ticket->fresh()->status);
        $this->assertNotNull($ticket->fresh()->used_at);
        $this->assertSame($before, $payment->fresh()->toArray());
    }

    public function test_scan03_pending_payment_cannot_enter(): void
    {
        $ticket = $this->ticket();
        $this->payment($ticket, 'pending');
        $this->assertDeniedWithoutWrites($ticket);
    }

    public function test_scan04_rejected_payment_cannot_enter(): void
    {
        $ticket = $this->ticket();
        $this->payment($ticket, 'rejected');
        $this->assertDeniedWithoutWrites($ticket);
    }

    public function test_scan05_missing_payment_cannot_enter_paid_event(): void
    {
        $this->assertDeniedWithoutWrites($this->ticket());
    }

    public function test_scan06_duplicate_confirmed_payments_are_ambiguous_not_first_row_wins(): void
    {
        $ticket = $this->ticket();
        $this->payment($ticket);
        $this->payment($ticket);
        $this->assertDatabaseCount('payments', 2);
        $this->assertDeniedWithoutWrites($ticket);
    }

    public function test_scan07_payment_owned_by_another_user_cannot_enter(): void
    {
        $ticket = $this->ticket();
        $this->payment($ticket, 'confirmed', $this->owner->id);
        $this->assertDeniedWithoutWrites($ticket);
    }

    public function test_scan08_array_code_is_validation_error_without_writes(): void
    {
        $before = $this->snapshot();
        $this->scan(['SCAN01TEST'])->assertUnprocessable()->assertJsonValidationErrors('ticket_code');
        $this->assertSame($before, $this->snapshot());
    }

    public function test_scan09_nonstring_code_is_validation_error_without_writes(): void
    {
        $before = $this->snapshot();
        $this->scan(1234567890)->assertUnprocessable()->assertJsonValidationErrors('ticket_code');
        $this->assertSame($before, $this->snapshot());
    }

    public function test_scan10_excessively_long_code_is_validation_error_without_writes(): void
    {
        $before = $this->snapshot();
        $this->scan(str_repeat('A', 10000))->assertUnprocessable()->assertJsonValidationErrors('ticket_code');
        $this->assertSame($before, $this->snapshot());
    }

    public function test_scan11_pending_event_denies_valid_paid_ticket(): void
    {
        $ticket = $this->ticket();
        $this->payment($ticket);
        $this->event->update(['status' => 'pending']);
        $this->assertDeniedWithoutWrites($ticket);
    }

    public function test_scan12_cancelled_event_denies_valid_paid_ticket(): void
    {
        $ticket = $this->ticket();
        $this->payment($ticket);
        $this->event->update(['status' => 'cancelled']);
        $this->assertDeniedWithoutWrites($ticket);
    }

    public function test_scan13_draft_event_denies_valid_free_ticket(): void
    {
        $this->event->update(['ticket_price' => 0, 'status' => 'draft']);
        $this->assertDeniedWithoutWrites($this->ticket());
    }

    public function test_scan14_second_scan_preserves_first_entry_time_and_payment(): void
    {
        $ticket = $this->ticket();
        $this->payment($ticket);
        $this->scan($ticket->ticket_code)->assertOk();
        $this->travel(1)->minutes();
        $this->assertDeniedWithoutWrites($ticket);
    }

    public function test_scan15_code_from_another_event_is_not_consumed(): void
    {
        $otherEvent = $this->event->replicate();
        $otherEvent->save();
        $ticket = $this->ticket();
        $ticket->update(['event_id' => $otherEvent->id]);
        $this->payment($ticket);
        $this->assertDeniedWithoutWrites($ticket, 404);
    }

    public function test_scan16_nonactive_ticket_cannot_enter_even_with_confirmed_payment(): void
    {
        $ticket = $this->ticket('pending');
        $this->payment($ticket);
        $this->assertDeniedWithoutWrites($ticket);
        $ticket->update(['status' => 'cancelled']);
        $this->assertDeniedWithoutWrites($ticket);
    }

    public function test_scan17_scan_page_denies_pending_cancelled_and_draft_events_without_writes(): void
    {
        foreach (['pending', 'cancelled', 'draft'] as $status) {
            $this->event->update(['status' => $status]);
            $before = $this->snapshot();
            $this->actingAs($this->owner)->get(route('organizer.events.scan', $this->event))->assertStatus(422);
            $this->assertSame($before, $this->snapshot());
        }
    }
}
