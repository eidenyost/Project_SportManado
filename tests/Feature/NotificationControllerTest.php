<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\Organizer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Organizer $organizer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Organizer User',
            'email' => 'eo@test.com',
            'password' => bcrypt('password'),
            'role' => 'organizer',
        ]);

        $this->organizer = Organizer::create([
            'user_id' => $this->user->id,
            'organization_name' => 'Test EO',
            'phone' => '08123456789',
            'status' => 'approved',
        ]);
    }

    public function test_index_shows_notifications(): void
    {
        $this->actingAs($this->user);

        Notification::create([
            'user_id' => $this->user->id,
            'type' => 'event_approved',
            'title' => 'Event Disetujui',
            'message' => 'Event Anda telah disetujui.',
            'notifiable_type' => 'App\\Models\\Event',
            'notifiable_id' => 1,
        ]);

        Notification::create([
            'user_id' => $this->user->id,
            'type' => 'event_rejected',
            'title' => 'Event Ditolak',
            'message' => 'Event Anda ditolak.',
            'notifiable_type' => 'App\\Models\\Event',
            'notifiable_id' => 2,
            'is_read' => true,
        ]);

        $response = $this->get(route('organizer.notifications.index'));
        $response->assertStatus(200);
        $response->assertSee('Event Disetujui');
        $response->assertSee('Event Ditolak');
    }

    public function test_index_filter_unread(): void
    {
        $this->actingAs($this->user);

        Notification::create([
            'user_id' => $this->user->id,
            'type' => 'event_approved',
            'title' => 'Unread Notification',
            'message' => 'Unread',
            'notifiable_type' => 'App\\Models\\Event',
            'notifiable_id' => 1,
        ]);

        Notification::create([
            'user_id' => $this->user->id,
            'type' => 'event_rejected',
            'title' => 'Read Notification',
            'message' => 'Read',
            'notifiable_type' => 'App\\Models\\Event',
            'notifiable_id' => 2,
            'is_read' => true,
        ]);

        $response = $this->get(route('organizer.notifications.index', ['filter' => 'unread']));
        $response->assertStatus(200);
        $response->assertSee('Unread Notification');
        $response->assertDontSee('Read Notification');
    }

    public function test_mark_as_read(): void
    {
        $this->actingAs($this->user);

        $notification = Notification::create([
            'user_id' => $this->user->id,
            'type' => 'event_approved',
            'title' => 'Test Notification',
            'message' => 'Test',
            'notifiable_type' => 'App\\Models\\Event',
            'notifiable_id' => 1,
        ]);

        $this->assertFalse($notification->fresh()->is_read);

        $response = $this->post(route('organizer.notifications.read', $notification));
        $response->assertRedirect();

        $this->assertTrue($notification->fresh()->is_read);
    }

    public function test_mark_all_as_read(): void
    {
        $this->actingAs($this->user);

        Notification::create([
            'user_id' => $this->user->id,
            'type' => 'type_a',
            'title' => 'Notification A',
            'message' => 'A',
            'notifiable_type' => 'App\\Models\\Event',
            'notifiable_id' => 1,
        ]);

        Notification::create([
            'user_id' => $this->user->id,
            'type' => 'type_b',
            'title' => 'Notification B',
            'message' => 'B',
            'notifiable_type' => 'App\\Models\\Event',
            'notifiable_id' => 2,
        ]);

        $response = $this->post(route('organizer.notifications.read-all'));
        $response->assertRedirect();

        $this->assertEquals(0, Notification::where('user_id', $this->user->id)->where('is_read', false)->count());
    }

    public function test_unread_count_endpoint(): void
    {
        $this->actingAs($this->user);

        Notification::create([
            'user_id' => $this->user->id,
            'type' => 'type_a',
            'title' => 'Unread A',
            'message' => 'A',
            'notifiable_type' => 'App\\Models\\Event',
            'notifiable_id' => 1,
        ]);

        Notification::create([
            'user_id' => $this->user->id,
            'type' => 'type_b',
            'title' => 'Unread B',
            'message' => 'B',
            'notifiable_type' => 'App\\Models\\Event',
            'notifiable_id' => 2,
        ]);

        $response = $this->getJson(route('organizer.notifications.unread-count'));
        $response->assertStatus(200);
        $response->assertJson([
            'count' => 2,
        ]);
    }

    public function test_other_user_cannot_mark_notification_as_read(): void
    {
        $otherUser = User::create([
            'name' => 'Other User',
            'email' => 'other@test.com',
            'password' => bcrypt('password'),
            'role' => 'organizer',
        ]);

        $notification = Notification::create([
            'user_id' => $otherUser->id,
            'type' => 'event_approved',
            'title' => 'Other Notification',
            'message' => 'Secret',
            'notifiable_type' => 'App\\Models\\Event',
            'notifiable_id' => 1,
        ]);

        $this->actingAs($this->user);
        $response = $this->post(route('organizer.notifications.read', $notification));
        $response->assertStatus(403);
    }
}
