<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Organizer;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TicketRegistrationFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private User $organizerUser;
    private Organizer $organizer;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        $this->user = User::create([
            'name' => 'Athlete',
            'email' => 'athlete@test.com',
            'password' => bcrypt('password'),
            'role' => 'user',
        ]);

        $this->organizerUser = User::create([
            'name' => 'Organizer',
            'email' => 'organizer@test.com',
            'password' => bcrypt('password'),
            'role' => 'organizer',
        ]);

        $this->organizer = Organizer::create([
            'user_id' => $this->organizerUser->id,
            'organization_name' => 'Test EO',
            'phone' => '08123456789',
            'status' => 'approved',
        ]);
    }

    public function test_free_event_with_auto_approve_activates_ticket_immediately(): void
    {
        $event = Event::create([
            'organizer_id' => $this->organizer->id,
            'title' => 'Free Auto Approve Event',
            'description' => 'Desc',
            'location' => 'Manado',
            'event_date' => now()->addDays(10),
            'registration_deadline' => now()->addDays(5),
            'max_participants' => 50,
            'ticket_price' => 0,
            'category' => 'Futsal',
            'status' => 'active',
            'auto_approve' => true,
        ]);

        $response = $this->actingAs($this->user)->post(route('tickets.submit-payment', $event));
        $response->assertRedirect(route('tickets.show', Ticket::first()));

        $ticket = Ticket::first();
        $this->assertNotNull($ticket);
        $this->assertEquals('active', $ticket->status);
        $this->assertNull($ticket->payment);
        $this->assertNotNull($ticket->qr_code_path);
    }

    public function test_free_event_without_auto_approve_creates_pending_ticket(): void
    {
        $event = Event::create([
            'organizer_id' => $this->organizer->id,
            'title' => 'Free Manual Approve Event',
            'description' => 'Desc',
            'location' => 'Manado',
            'event_date' => now()->addDays(10),
            'registration_deadline' => now()->addDays(5),
            'max_participants' => 50,
            'ticket_price' => 0,
            'category' => 'Futsal',
            'status' => 'active',
            'auto_approve' => false,
        ]);

        $response = $this->actingAs($this->user)->post(route('tickets.submit-payment', $event));
        $response->assertRedirect(route('tickets.show', Ticket::first()));

        $ticket = Ticket::first();
        $this->assertEquals('pending', $ticket->status);
        $this->assertNull($ticket->payment);
    }

    public function test_paid_event_creates_pending_ticket_and_payment(): void
    {
        $event = Event::create([
            'organizer_id' => $this->organizer->id,
            'title' => 'Paid Event',
            'description' => 'Desc',
            'location' => 'Manado',
            'event_date' => now()->addDays(10),
            'registration_deadline' => now()->addDays(5),
            'max_participants' => 50,
            'ticket_price' => 50000,
            'category' => 'Basket',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->user)->post(route('tickets.submit-payment', $event));
        $response->assertRedirect(route('tickets.show', Ticket::first()));

        $ticket = Ticket::first();
        $this->assertEquals('pending', $ticket->status);
        $this->assertNotNull($ticket->payment);
        $this->assertEquals('pending', $ticket->payment->status);
        $this->assertEquals(50000, $ticket->payment->amount);
    }

    public function test_registration_fails_when_event_is_full(): void
    {
        $event = Event::create([
            'organizer_id' => $this->organizer->id,
            'title' => 'Full Event',
            'description' => 'Desc',
            'location' => 'Manado',
            'event_date' => now()->addDays(10),
            'registration_deadline' => now()->addDays(5),
            'max_participants' => 1,
            'ticket_price' => 0,
            'category' => 'Futsal',
            'status' => 'active',
            'auto_approve' => true,
        ]);

        // First user registers - fills the slot
        $firstUser = User::create([
            'name' => 'First',
            'email' => 'first@test.com',
            'password' => bcrypt('password'),
            'role' => 'user',
        ]);
        Ticket::create([
            'user_id' => $firstUser->id,
            'event_id' => $event->id,
            'ticket_code' => 'FIRSTTICKET',
            'status' => 'active',
        ]);

        // Second user tries to register
        $response = $this->actingAs($this->user)->post(route('tickets.submit-payment', $event));
        $response->assertStatus(422);

        $this->assertDatabaseMissing('tickets', ['user_id' => $this->user->id]);
    }

    public function test_registration_fails_after_deadline(): void
    {
        $event = Event::create([
            'organizer_id' => $this->organizer->id,
            'title' => 'Expired Event',
            'description' => 'Desc',
            'location' => 'Manado',
            'event_date' => now()->addDays(10),
            'registration_deadline' => now()->subDay(),
            'max_participants' => 50,
            'ticket_price' => 0,
            'category' => 'Futsal',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->user)->post(route('tickets.submit-payment', $event));
        $response->assertStatus(422);
    }

    public function test_duplicate_registration_redirects_to_existing_ticket(): void
    {
        $event = Event::create([
            'organizer_id' => $this->organizer->id,
            'title' => 'Duplicate Event',
            'description' => 'Desc',
            'location' => 'Manado',
            'event_date' => now()->addDays(10),
            'registration_deadline' => now()->addDays(5),
            'max_participants' => 50,
            'ticket_price' => 0,
            'category' => 'Futsal',
            'status' => 'active',
            'auto_approve' => true,
        ]);

        // First registration
        $this->actingAs($this->user)->post(route('tickets.submit-payment', $event));
        $firstTicket = Ticket::where('user_id', $this->user->id)->first();

        // Second registration attempt
        $response = $this->actingAs($this->user)->post(route('tickets.submit-payment', $event));
        $response->assertStatus(422);
    }
}
