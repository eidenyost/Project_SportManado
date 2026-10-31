<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Organizer;
use App\Models\Payment;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MidtransWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_webhook_updates_status(): void
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
            'method' => 'transfer'
        ]);

        config(['services.midtrans.server_key' => 'test-server-key']);

        $orderId = 'TICKET-TEST-100';
        $statusCode = '200';
        $grossAmount = '50000.00';
        $serverKey = 'test-server-key';
        $signatureKey = hash("sha512", $orderId . $statusCode . $grossAmount . $serverKey);

        $response = $this->postJson(route('payments.notification'), [
            'order_id' => $orderId,
            'status_code' => $statusCode,
            'gross_amount' => $grossAmount,
            'signature_key' => $signatureKey,
            'transaction_status' => 'settlement',
            'payment_type' => 'qris',
            'transaction_id' => 'tx-midtrans-success-999'
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'confirmed',
            'payment_type' => 'qris',
            'midtrans_transaction_id' => 'tx-midtrans-success-999'
        ]);
        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'status' => 'active'
        ]);
    }
}
