<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Organizer;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminDashboardActionsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');

        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);
    }

    public function test_delete_user_with_history_is_blocked_and_preserves_files(): void
    {
        $this->actingAs($this->admin);

        // Create an organizer user
        $user = User::create([
            'name' => 'Organizer User',
            'email' => 'organizer@test.com',
            'password' => bcrypt('password'),
            'role' => 'organizer',
        ]);

        $organizer = Organizer::create([
            'user_id' => $user->id,
            'organization_name' => 'Test EO',
            'phone' => '08123456789',
            'description' => 'Organizer desc',
            'logo' => 'organizer-logos/logo.png',
            'ktp_path' => 'organizer-ktps/ktp.png',
            'selfie_path' => 'organizer-selfies/selfie.png',
            'status' => 'approved',
        ]);

        // Place files
        Storage::disk('public')->put('organizer-logos/logo.png', 'logo content');
        Storage::disk('local')->put('organizer-ktps/ktp.png', 'ktp content');
        Storage::disk('local')->put('organizer-selfies/selfie.png', 'selfie content');

        // Create an event for this organizer
        $event = Event::create([
            'organizer_id' => $organizer->id,
            'title' => 'Test Event',
            'description' => 'Test Description',
            'location' => 'Test Location',
            'event_date' => now()->addDays(5),
            'registration_deadline' => now()->addDays(2),
            'max_participants' => 100,
            'ticket_price' => 0.00,
            'category' => 'Sport',
            'banner' => 'event-banners/banner.png',
            'proposal' => 'event-proposals/proposal.pdf',
            'permit' => 'event-permits/permit.pdf',
            'ktp_image' => 'event-ktps/ktp.png',
            'status' => 'active',
        ]);

        Storage::disk('public')->put('event-banners/banner.png', 'banner content');
        Storage::disk('local')->put('event-proposals/proposal.pdf', 'proposal content');
        Storage::disk('local')->put('event-permits/permit.pdf', 'permit content');
        Storage::disk('local')->put('event-ktps/ktp.png', 'ktp image content');

        // Create a ticket for a user that has a QR code path
        $ticket = Ticket::create([
            'user_id' => $user->id,
            'event_id' => $event->id,
            'ticket_code' => 'TCK-12345',
            'qr_code_path' => 'qr-codes/TCK-12345.svg',
            'status' => 'active',
        ]);

        Storage::disk('public')->put('qr-codes/TCK-12345.svg', 'qr content');

        // Verify files exist
        Storage::disk('public')->assertExists('qr-codes/TCK-12345.svg');
        Storage::disk('public')->assertExists('organizer-logos/logo.png');
        Storage::disk('local')->assertExists('organizer-ktps/ktp.png');
        Storage::disk('local')->assertExists('organizer-selfies/selfie.png');
        Storage::disk('public')->assertExists('event-banners/banner.png');
        Storage::disk('local')->assertExists('event-proposals/proposal.pdf');

        // Call delete user
        $response = $this->from(route('admin.dashboard', ['tab'=>'manage_users']))->delete(route('admin.users.delete', $user));
        $response->assertRedirect(route('admin.dashboard', ['tab' => 'manage_users']));

        // Assert database records are gone
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $user->id]);
        $this->assertDatabaseHas('tickets', ['id'=>$ticket->id]);
        $this->assertDatabaseHas('events', ['id'=>$event->id, 'deleted_at'=>null]);

        // Assert files are deleted
        Storage::disk('public')->assertExists('qr-codes/TCK-12345.svg');
        Storage::disk('public')->assertExists('organizer-logos/logo.png');
        Storage::disk('local')->assertExists('organizer-ktps/ktp.png');
        Storage::disk('local')->assertExists('organizer-selfies/selfie.png');
        Storage::disk('public')->assertExists('event-banners/banner.png');
        Storage::disk('local')->assertExists('event-proposals/proposal.pdf');
    }

    public function test_reject_event_does_soft_delete_and_preserves_files(): void
    {
        $this->actingAs($this->admin);

        $organizerUser = User::create([
            'name' => 'Organizer User',
            'email' => 'organizer@test.com',
            'password' => bcrypt('password'),
            'role' => 'organizer',
        ]);

        $organizer = Organizer::create([
            'user_id' => $organizerUser->id,
            'organization_name' => 'Test EO',
            'phone' => '08123456789',
            'status' => 'approved',
        ]);

        $event = Event::create([
            'organizer_id' => $organizer->id,
            'title' => 'Pending Event',
            'description' => 'Test Description',
            'location' => 'Test Location',
            'event_date' => now()->addDays(5),
            'registration_deadline' => now()->addDays(2),
            'max_participants' => 100,
            'ticket_price' => 0.00,
            'category' => 'Sport',
            'banner' => 'event-banners/banner.png',
            'proposal' => 'event-proposals/proposal.pdf',
            'status' => 'pending',
        ]);

        Storage::disk('public')->put('event-banners/banner.png', 'banner content');
        Storage::disk('local')->put('event-proposals/proposal.pdf', 'proposal content');

        // Call rejectEvent
        $response = $this->post(route('admin.events.reject', $event));
        $response->assertRedirect(route('admin.dashboard'));

        // Assert event is soft deleted (not in active query, but exists in database with deleted_at)
        $this->assertSoftDeleted('events', ['id' => $event->id]);

        // Assert files are still preserved
        Storage::disk('public')->assertExists('event-banners/banner.png');
        Storage::disk('local')->assertExists('event-proposals/proposal.pdf');
    }

    public function test_delete_event_does_soft_delete_and_cleans_up_files(): void
    {
        $this->actingAs($this->admin);

        $organizerUser = User::create([
            'name' => 'Organizer User',
            'email' => 'organizer@test.com',
            'password' => bcrypt('password'),
            'role' => 'organizer',
        ]);

        $organizer = Organizer::create([
            'user_id' => $organizerUser->id,
            'organization_name' => 'Test EO',
            'phone' => '08123456789',
            'status' => 'approved',
        ]);

        $event = Event::create([
            'organizer_id' => $organizer->id,
            'title' => 'Event to Delete',
            'description' => 'Test Description',
            'location' => 'Test Location',
            'event_date' => now()->addDays(5),
            'registration_deadline' => now()->addDays(2),
            'max_participants' => 100,
            'ticket_price' => 0.00,
            'category' => 'Sport',
            'banner' => 'event-banners/banner.png',
            'proposal' => 'event-proposals/proposal.pdf',
            'status' => 'active',
        ]);

        Storage::disk('public')->put('event-banners/banner.png', 'banner content');
        Storage::disk('local')->put('event-proposals/proposal.pdf', 'proposal content');

        // Call deleteEvent
        $response = $this->delete(route('admin.events.delete', $event));
        $response->assertRedirect(route('admin.dashboard'));

        // Assert event is soft-deleted (preserved in database for audit trail)
        $this->assertSoftDeleted('events', ['id' => $event->id]);

        // Assert files are deleted
        Storage::disk('public')->assertMissing('event-banners/banner.png');
        Storage::disk('local')->assertMissing('event-proposals/proposal.pdf');
    }

    public function test_download_document_null_path_guard(): void
    {
        $this->actingAs($this->admin);

        // Request with missing path parameter
        $response = $this->get(route('admin.download-document'));
        $response->assertStatus(400);

        // Request with empty path
        $response = $this->get(route('admin.download-document') . '?path=');
        $response->assertStatus(400);

        // Request with unauthorized path
        $response = $this->get(route('admin.download-document') . '?path=secrets/passwords.txt');
        $response->assertStatus(403);
    }
}
