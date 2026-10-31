<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Organizer;
use App\Models\Payment;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ApproveFirstThenPayTest extends TestCase
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
            'name' => 'Athlete User',
            'email' => 'athlete@example.com',
            'password' => bcrypt('password'),
            'role' => 'user'
        ]);

        $this->organizerUser = User::create([
            'name' => 'Organizer User',
            'email' => 'eo@example.com',
            'password' => bcrypt('password'),
            'role' => 'organizer'
        ]);

        $this->organizer = Organizer::create([
            'user_id' => $this->organizerUser->id,
            'organization_name' => 'Kawanua Sports EO',
            'phone' => '081122334455',
            'description' => 'EO description',
            'status' => 'approved',
        ]);
    }

    public function test_user_can_register_for_paid_event_and_payment_is_created(): void
    {
        $event = Event::create([
            'organizer_id' => $this->organizer->id,
            'title' => 'Paid Marathon',
            'description' => 'Marathon description',
            'location' => 'Koni Sario',
            'event_date' => now()->addDays(10),
            'registration_deadline' => now()->addDays(5),
            'max_participants' => 100,
            'ticket_price' => 75000,
            'category' => 'Running',
            'status' => 'active'
        ]);

        $response = $this->actingAs($this->user)->post(route('tickets.submit-payment', $event), [
            'notes' => 'Looking forward to the run!',
        ]);

        $response->assertRedirect(route('tickets.show', Ticket::first()));

        // Ticket is created as pending with payment ready
        $ticket = Ticket::first();
        $this->assertNotNull($ticket);
        $this->assertEquals('pending', $ticket->status);
        $this->assertNotNull($ticket->payment);
        $this->assertEquals('pending', $ticket->payment->status);
        $this->assertEquals(75000, $ticket->payment->amount);
    }

    public function test_eo_approval_of_free_event_activates_ticket_and_generates_qr(): void
    {
        $event = Event::create([
            'organizer_id' => $this->organizer->id,
            'title' => 'Free Fun Run',
            'description' => 'Fun run description',
            'location' => 'Megamas',
            'event_date' => now()->addDays(10),
            'registration_deadline' => now()->addDays(5),
            'max_participants' => 100,
            'ticket_price' => 0,
            'category' => 'Running',
            'status' => 'active',
            'auto_approve' => false,
        ]);

        $ticket = Ticket::create([
            'user_id' => $this->user->id,
            'event_id' => $event->id,
            'ticket_code' => 'FUNRUNFREE',
            'status' => 'pending'
        ]);

        // EO approves free registration
        $response = $this->actingAs($this->organizerUser)
            ->post(route('organizer.events.participants.approve', [$event, $ticket]));

        $response->assertRedirect(route('organizer.events.participants', $event));

        // Ticket status becomes active and QR is generated, no payment created
        $ticket = $ticket->fresh();
        $this->assertEquals('active', $ticket->status);
        $this->assertNotNull($ticket->qr_code_path);
        $this->assertNull($ticket->payment);
    }
}
