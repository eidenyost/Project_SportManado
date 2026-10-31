<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Organizer;
use App\Models\Payment;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MidtransCheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_detail_page_shows_snap_token_for_pending_payment(): void
    {
        $user = User::create([
            'name' => 'User Test',
            'email' => 'user-test@example.com',
            'password' => bcrypt('password'),
            'role' => 'user'
        ]);
        $organizerUser = User::create([
            'name' => 'Organizer Test',
            'email' => 'organizer-test@example.com',
            'password' => bcrypt('password'),
            'role' => 'organizer'
        ]);
        $organizer = Organizer::create([
            'user_id' => $organizerUser->id,
            'organization_name' => 'Kawanua Sports Association',
            'phone' => '081122334455',
            'description' => 'EO description'
        ]);
        $event = Event::create([
            'organizer_id' => $organizer->id,
            'title' => 'Tournament Test',
            'description' => 'Tournament description',
            'location' => 'Manado',
            'event_date' => now()->addDays(10),
            'registration_deadline' => now()->addDays(5),
            'max_participants' => 100,
            'ticket_price' => 50000,
            'category' => 'Running',
            'status' => 'active'
        ]);
        $ticket = Ticket::create([
            'user_id' => $user->id,
            'event_id' => $event->id,
            'ticket_code' => 'TICKET-TEST-100',
            'status' => 'pending'
        ]);
        $payment = Payment::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'amount' => 50000,
            'status' => 'pending',
            'method' => 'ewallet'
        ]);

        $response = $this->actingAs($user)->get(route('tickets.show', $ticket));

        $response->assertStatus(200);
        $response->assertViewIs('tickets.show');
        $response->assertViewHas('snapToken');

        $this->assertNotNull($ticket->payment->fresh()->snap_token);
    }
}
