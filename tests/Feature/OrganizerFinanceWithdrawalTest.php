<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Organizer;
use App\Models\Payment;
use App\Models\Ticket;
use App\Models\User;
use App\Models\WithdrawalRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizerFinanceWithdrawalTest extends TestCase
{
    use RefreshDatabase;

    private User $organizerUser;
    private Organizer $organizer;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organizerUser = User::create([
            'name' => 'Organizer',
            'email' => 'eo@test.com',
            'password' => bcrypt('password'),
            'role' => 'organizer',
        ]);

        $this->organizer = Organizer::create([
            'user_id' => $this->organizerUser->id,
            'organization_name' => 'Test EO',
            'phone' => '08123456789',
            'status' => 'approved',
        ]);

        $this->admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);
    }

    public function test_withdraw_page_shows_balance(): void
    {
        $this->actingAs($this->organizerUser);

        $event = Event::create([
            'organizer_id' => $this->organizer->id,
            'title' => 'Event With Balance',
            'description' => 'Desc',
            'location' => 'Manado',
            'event_date' => now()->addDays(10),
            'registration_deadline' => now()->addDays(5),
            'max_participants' => 50,
            'ticket_price' => 100000,
            'category' => 'Futsal',
            'status' => 'active',
        ]);

        // Create a confirmed payment
        $user = User::create([
            'name' => 'Participant',
            'email' => 'participant@test.com',
            'password' => bcrypt('password'),
            'role' => 'user',
        ]);

        $ticket = Ticket::create([
            'user_id' => $user->id,
            'event_id' => $event->id,
            'ticket_code' => 'TICKET001',
            'status' => 'active',
        ]);

        Payment::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'amount' => 100000,
            'status' => 'confirmed',
            'method' => 'ewallet',
        ]);

        $response = $this->get(route('organizer.finances.withdraw.page', $event));
        $response->assertStatus(200);
        $response->assertSee('100.000');
    }

    public function test_organizer_can_submit_withdrawal(): void
    {
        $this->actingAs($this->organizerUser);

        $event = Event::create([
            'organizer_id' => $this->organizer->id,
            'title' => 'Withdrawable Event',
            'description' => 'Desc',
            'location' => 'Manado',
            'event_date' => now()->addDays(10),
            'registration_deadline' => now()->addDays(5),
            'max_participants' => 50,
            'ticket_price' => 100000,
            'category' => 'Futsal',
            'status' => 'active',
        ]);

        $user = User::create([
            'name' => 'Participant',
            'email' => 'part@test.com',
            'password' => bcrypt('password'),
            'role' => 'user',
        ]);

        $ticket = Ticket::create([
            'user_id' => $user->id,
            'event_id' => $event->id,
            'ticket_code' => 'TICKET002',
            'status' => 'active',
        ]);

        Payment::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'amount' => 100000,
            'status' => 'confirmed',
            'method' => 'ewallet',
        ]);

        $response = $this->post(route('organizer.finances.withdraw', $event), [
            'amount' => 50000,
            'bank_name' => 'BCA',
            'account_number' => '1234567890',
            'account_holder' => 'Test EO',
        ]);

        $response->assertRedirect(route('organizer.finances.finance', $event));

        $this->assertDatabaseHas('withdrawal_requests', [
            'event_id' => $event->id,
            'organizer_id' => $this->organizer->id,
            'amount' => 50000,
            'status' => 'pending',
        ]);
    }

    public function test_admin_can_approve_withdrawal(): void
    {
        $this->actingAs($this->admin);

        $event = Event::create([
            'organizer_id' => $this->organizer->id,
            'title' => 'Approved Withdrawal Event',
            'description' => 'Desc',
            'location' => 'Manado',
            'event_date' => now()->addDays(10),
            'registration_deadline' => now()->addDays(5),
            'max_participants' => 50,
            'ticket_price' => 100000,
            'category' => 'Futsal',
            'status' => 'active',
        ]);

        $payer=\App\Models\User::factory()->create(['role'=>'user']);
        $ticket=\App\Models\Ticket::create(['user_id'=>$payer->id,'event_id'=>$event->id,'ticket_code'=>'FUNDED-WITHDRAWAL','status'=>'active']);
        \App\Models\Payment::create(['user_id'=>$payer->id,'ticket_id'=>$ticket->id,'amount'=>100000,'method'=>'ewallet','status'=>'confirmed']);

        $withdrawal = WithdrawalRequest::create([
            'event_id' => $event->id,
            'organizer_id' => $this->organizer->id,
            'amount' => 50000,
            'bank_name' => 'BCA',
            'account_number' => '1234567890',
            'account_holder' => 'Test EO',
            'status' => 'pending',
        ]);

        $response = $this->post(route('admin.withdrawals.approve', $withdrawal),['transfer_proof'=>\Tests\Support\TransferProof::image()]);
        $response->assertRedirect(route('admin.dashboard', ['tab' => 'withdrawal_requests']));

        $this->assertDatabaseHas('withdrawal_requests', [
            'id' => $withdrawal->id,
            'status' => 'approved',
        ]);
    }

    public function test_admin_can_reject_withdrawal(): void
    {
        $this->actingAs($this->admin);

        $event = Event::create([
            'organizer_id' => $this->organizer->id,
            'title' => 'Rejected Withdrawal Event',
            'description' => 'Desc',
            'location' => 'Manado',
            'event_date' => now()->addDays(10),
            'registration_deadline' => now()->addDays(5),
            'max_participants' => 50,
            'ticket_price' => 0,
            'category' => 'Futsal',
            'status' => 'active',
        ]);

        $withdrawal = WithdrawalRequest::create([
            'event_id' => $event->id,
            'organizer_id' => $this->organizer->id,
            'amount' => 25000,
            'bank_name' => 'BNI',
            'account_number' => '9876543210',
            'account_holder' => 'Test EO',
            'status' => 'pending',
        ]);

        $response = $this->post(route('admin.withdrawals.reject', $withdrawal), [
            'admin_notes' => 'Dokumen tidak lengkap',
        ]);
        $response->assertRedirect(route('admin.dashboard', ['tab' => 'withdrawal_requests']));

        $this->assertDatabaseHas('withdrawal_requests', [
            'id' => $withdrawal->id,
            'status' => 'rejected',
            'admin_notes' => 'Dokumen tidak lengkap',
        ]);
    }
}
