<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventRegistrationRequest;
use App\Models\Organizer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EventRegistrationRequestFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Storage::fake('local');

        $this->user = User::create([
            'name' => 'Participant',
            'email' => 'participant@test.com',
            'password' => bcrypt('password'),
            'role' => 'user',
        ]);

        $this->admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);
    }

    public function test_user_can_submit_registration_request(): void
    {
        $this->actingAs($this->user);

        $response = $this->post(route('event-registration.store'), [
            'organization_name' => 'Komunitas Lari Manado',
            'phone' => '081234567890',
            'event_title' => 'Fun Run 2026',
            'event_description' => 'Event lari santai',
            'event_location' => 'Megamas, Manado',
            'event_date' => now()->addDays(20)->format('Y-m-d H:i'),
            'registration_deadline' => now()->addDays(10)->format('Y-m-d H:i'),
            'max_participants' => 100,
            'ticket_price' => 0,
            'category' => 'Lari & Trail',
            'proposal_file' => UploadedFile::fake()->create('proposal.pdf', 500, 'application/pdf'),
            'permit_file' => UploadedFile::fake()->create('permit.pdf', 500, 'application/pdf'),
        ]);

        $response->assertRedirect(route('event-registration.status'));

        $this->assertDatabaseHas('event_registration_requests', [
            'user_id' => $this->user->id,
            'organization_name' => 'Komunitas Lari Manado',
            'event_title' => 'Fun Run 2026',
            'status' => 'pending',
        ]);
    }

    public function test_registration_request_rejects_invalid_category(): void
    {
        $this->actingAs($this->user);

        $response = $this->post(route('event-registration.store'), [
            'organization_name' => 'Test',
            'phone' => '081234567890',
            'event_title' => 'Test Event',
            'event_description' => 'Desc',
            'event_location' => 'Manado',
            'event_date' => now()->addDays(20)->format('Y-m-d H:i'),
            'registration_deadline' => now()->addDays(10)->format('Y-m-d H:i'),
            'max_participants' => 10,
            'ticket_price' => 0,
            'category' => 'InvalidSport',
            'proposal_file' => UploadedFile::fake()->create('proposal.pdf', 500, 'application/pdf'),
            'permit_file' => UploadedFile::fake()->create('permit.pdf', 500, 'application/pdf'),
        ]);

        $response->assertSessionHasErrors(['category']);
    }

    public function test_status_page_shows_pending(): void
    {
        $this->actingAs($this->user);

        EventRegistrationRequest::create([
            'user_id' => $this->user->id,
            'organization_name' => 'Komunitas Lari Manado',
            'phone' => '081234567890',
            'event_title' => 'Fun Run 2026',
            'event_description' => 'Event lari santai',
            'event_location' => 'Megamas',
            'event_date' => now()->addDays(20),
            'registration_deadline' => now()->addDays(10),
            'max_participants' => 100,
            'ticket_price' => 0,
            'category' => 'Lari & Trail',
            'proposal_file' => 'event-reg-proposals/proposal.pdf',
            'permit_file' => 'event-reg-permits/permit.pdf',
            'status' => 'pending',
        ]);

        $response = $this->get(route('event-registration.status'));
        $response->assertStatus(200);
        $response->assertSee('Fun Run 2026');
        $response->assertSee('Sedang Diproses');
    }

    public function test_admin_can_approve_registration_request(): void
    {
        $this->actingAs($this->admin);

        $eventRequest = EventRegistrationRequest::create([
            'user_id' => $this->user->id,
            'organization_name' => 'Komunitas Lari Manado',
            'phone' => '081234567890',
            'event_title' => 'Fun Run 2026',
            'event_description' => 'Event lari santai',
            'event_location' => 'Megamas',
            'event_date' => now()->addDays(20),
            'registration_deadline' => now()->addDays(10),
            'max_participants' => 100,
            'ticket_price' => 0,
            'category' => 'Lari & Trail',
            'proposal_file' => 'event-reg-proposals/proposal.pdf',
            'permit_file' => 'event-reg-permits/permit.pdf',
            'status' => 'pending',
        ]);

        $response = $this->post(route('admin.event-requests.approve', $eventRequest));
        $response->assertRedirect(route('admin.dashboard'));

        // Request marked as approved
        $this->assertDatabaseHas('event_registration_requests', [
            'id' => $eventRequest->id,
            'status' => 'approved',
        ]);

        // User role upgraded to organizer
        $this->user->refresh();
        $this->assertEquals('organizer', $this->user->role);

        // Organizer profile created
        $this->assertDatabaseHas('organizers', [
            'user_id' => $this->user->id,
            'organization_name' => 'Komunitas Lari Manado',
        ]);

        // Event created from request
        $this->assertDatabaseHas('events', [
            'title' => 'Fun Run 2026',
            'status' => 'active',
        ]);
    }

    public function test_admin_can_reject_registration_request(): void
    {
        $this->actingAs($this->admin);

        $eventRequest = EventRegistrationRequest::create([
            'user_id' => $this->user->id,
            'organization_name' => 'Komunitas Lari Manado',
            'phone' => '081234567890',
            'event_title' => 'Fun Run 2026',
            'event_description' => 'Event lari santai',
            'event_location' => 'Megamas',
            'event_date' => now()->addDays(20),
            'registration_deadline' => now()->addDays(10),
            'max_participants' => 100,
            'ticket_price' => 0,
            'category' => 'Lari & Trail',
            'proposal_file' => 'event-reg-proposals/proposal.pdf',
            'permit_file' => 'event-reg-permits/permit.pdf',
            'status' => 'pending',
        ]);

        Storage::disk('public')->put($eventRequest->banner ?? 'dummy', 'content');

        $response = $this->post(route('admin.event-requests.reject', $eventRequest), [
            'admin_notes' => 'Proposal tidak sesuai ketentuan.',
        ]);
        $response->assertRedirect(route('admin.dashboard'));

        $this->assertDatabaseHas('event_registration_requests', [
            'id' => $eventRequest->id,
            'status' => 'rejected',
            'admin_notes' => 'Proposal tidak sesuai ketentuan.',
        ]);
    }

    public function test_organizer_redirected_from_registration_page(): void
    {
        $organizerUser = User::create([
            'name' => 'Existing EO',
            'email' => 'existing@test.com',
            'password' => bcrypt('password'),
            'role' => 'organizer',
        ]);

        Organizer::create([
            'user_id' => $organizerUser->id,
            'organization_name' => 'Existing EO',
            'phone' => '08123456788',
            'status' => 'approved',
        ]);

        $this->actingAs($organizerUser);

        $response = $this->get(route('event-registration.create'));
        $response->assertStatus(403);

        $response = $this->post(route('event-registration.store'), []);
        $response->assertStatus(403);
    }
}
