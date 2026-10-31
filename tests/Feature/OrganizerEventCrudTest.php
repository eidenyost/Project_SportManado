<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Organizer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OrganizerEventCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $organizerUser;
    private Organizer $organizer;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Storage::fake('local');

        $this->organizerUser = User::create([
            'name' => 'Organizer User',
            'email' => 'eo@test.com',
            'password' => bcrypt('password'),
            'role' => 'organizer',
        ]);

        $this->organizer = Organizer::create([
            'user_id' => $this->organizerUser->id,
            'organization_name' => 'Community EO',
            'phone' => '08123456789',
            'status' => 'approved',
        ]);
    }

    public function test_organizer_can_create_event(): void
    {
        $this->actingAs($this->organizerUser);

        $response = $this->post(route('organizer.events.store'), [
            'title' => 'Futsal Turnamen',
            'description' => 'Description of the event',
            'location' => 'Lapangan Koni, Manado',
            'event_date' => now()->addDays(10)->format('Y-m-d H:i'),
            'registration_deadline' => now()->addDays(5)->format('Y-m-d H:i'),
            'max_participants' => 32,
            'ticket_price' => 25000,
            'category' => 'Futsal',
            'banner' => UploadedFile::fake()->create('banner.jpg', 500, 'image/jpeg'),
            'proposal' => UploadedFile::fake()->create('proposal.pdf', 500, 'application/pdf'),
            'permit' => UploadedFile::fake()->create('permit.pdf', 500, 'application/pdf'),
            'ktp_image' => UploadedFile::fake()->create('ktp.jpg', 500, 'image/jpeg'),
        ]);

        $response->assertRedirect(route('organizer.dashboard'));

        $this->assertDatabaseHas('events', [
            'title' => 'Futsal Turnamen',
            'organizer_id' => $this->organizer->id,
            'status' => 'pending',
        ]);

        $event = Event::where('title', 'Futsal Turnamen')->first();
        $this->assertNotNull($event->banner);
        $this->assertNotNull($event->proposal);
        $this->assertNotNull($event->permit);
        $this->assertNotNull($event->ktp_image);
    }

    public function test_organizer_can_update_event(): void
    {
        $this->actingAs($this->organizerUser);

        $event = Event::create([
            'organizer_id' => $this->organizer->id,
            'title' => 'Original Title',
            'description' => 'Original description',
            'location' => 'Manado',
            'event_date' => now()->addDays(15),
            'registration_deadline' => now()->addDays(7),
            'max_participants' => 20,
            'ticket_price' => 0,
            'category' => 'Basket',
            'status' => 'draft',
        ]);

        $response = $this->patch(route('organizer.events.update', $event), [
            'title' => 'Updated Title',
            'description' => 'Updated description',
            'location' => 'Updated Location',
            'event_date' => now()->addDays(20)->format('Y-m-d H:i'),
            'registration_deadline' => now()->addDays(10)->format('Y-m-d H:i'),
            'max_participants' => 30,
            'ticket_price' => 0,
            'category' => 'Bulutangkis',
            'status' => 'draft',
        ]);

        $response->assertRedirect(route('organizer.dashboard'));

        $event->refresh();
        $this->assertEquals('Updated Title', $event->title);
        $this->assertEquals('Bulutangkis', $event->category);
        $this->assertEquals(30, $event->max_participants);
    }

    public function test_organizer_can_cancel_event(): void
    {
        $this->actingAs($this->organizerUser);

        $event = Event::create([
            'organizer_id' => $this->organizer->id,
            'title' => 'Event to Cancel',
            'description' => 'Desc',
            'location' => 'Manado',
            'event_date' => now()->addDays(10),
            'registration_deadline' => now()->addDays(5),
            'max_participants' => 20,
            'ticket_price' => 0,
            'category' => 'Futsal',
            'status' => 'active',
        ]);

        $response = $this->delete(route('organizer.events.destroy', $event));
        $response->assertRedirect(route('organizer.dashboard'));

        $event->refresh();
        $this->assertEquals('cancelled', $event->status);
    }

    public function test_non_owner_cannot_edit_event(): void
    {
        $otherUser = User::create([
            'name' => 'Other EO',
            'email' => 'other@test.com',
            'password' => bcrypt('password'),
            'role' => 'organizer',
        ]);
        $otherOrganizer = Organizer::create([
            'user_id' => $otherUser->id,
            'organization_name' => 'Other EO',
            'phone' => '08123456788',
            'status' => 'approved',
        ]);

        $event = Event::create([
            'organizer_id' => $otherOrganizer->id,
            'title' => 'Other Event',
            'description' => 'Desc',
            'location' => 'Manado',
            'event_date' => now()->addDays(10),
            'registration_deadline' => now()->addDays(5),
            'max_participants' => 20,
            'ticket_price' => 0,
            'category' => 'Futsal',
            'status' => 'draft',
        ]);

        $this->actingAs($this->organizerUser);
        $response = $this->get(route('organizer.events.edit', $event));
        $response->assertStatus(403);
    }

    public function test_create_event_rejects_invalid_category(): void
    {
        $this->actingAs($this->organizerUser);

        $response = $this->post(route('organizer.events.store'), [
            'title' => 'Invalid Category Event',
            'description' => 'Desc',
            'location' => 'Manado',
            'event_date' => now()->addDays(10)->format('Y-m-d H:i'),
            'registration_deadline' => now()->addDays(5)->format('Y-m-d H:i'),
            'max_participants' => 20,
            'ticket_price' => 0,
            'category' => 'InvalidSport',
            'proposal' => UploadedFile::fake()->create('proposal.pdf', 500, 'application/pdf'),
            'permit' => UploadedFile::fake()->create('permit.pdf', 500, 'application/pdf'),
            'ktp_image' => UploadedFile::fake()->create('ktp.jpg', 500, 'image/jpeg'),
        ]);

        $response->assertSessionHasErrors(['category']);
    }
}
