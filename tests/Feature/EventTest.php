<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Organizer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private User $organizerUser;
    private Organizer $organizer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Athlete User',
            'email' => 'athlete@example.com',
            'password' => bcrypt('password'),
            'role' => 'user',
        ]);

        $this->organizerUser = User::create([
            'name' => 'Organizer User',
            'email' => 'eo@example.com',
            'password' => bcrypt('password'),
            'role' => 'organizer',
        ]);

        $this->organizer = Organizer::create([
            'user_id' => $this->organizerUser->id,
            'organization_name' => 'Kawanua Sports EO',
            'phone' => '081122334455',
            'description' => 'EO description',
            'status' => 'approved',
        ]);
    }

    public function test_events_index_shows_only_active_events(): void
    {
        Event::create([
            'organizer_id' => $this->organizer->id,
            'title' => 'Futsal Championship',
            'description' => 'Desc',
            'location' => 'Koni Manado',
            'event_date' => now()->addDays(10),
            'registration_deadline' => now()->addDays(5),
            'max_participants' => 20,
            'ticket_price' => 0,
            'category' => 'Futsal',
            'status' => 'active',
        ]);

        Event::create([
            'organizer_id' => $this->organizer->id,
            'title' => 'Basket League',
            'description' => 'Desc',
            'location' => 'GOR Manado',
            'event_date' => now()->addDays(15),
            'registration_deadline' => now()->addDays(7),
            'max_participants' => 30,
            'ticket_price' => 50000,
            'category' => 'Basket',
            'status' => 'active',
        ]);

        Event::create([
            'organizer_id' => $this->organizer->id,
            'title' => 'Pending Event',
            'description' => 'Desc',
            'location' => 'Manado',
            'event_date' => now()->addDays(20),
            'registration_deadline' => now()->addDays(10),
            'max_participants' => 10,
            'ticket_price' => 0,
            'category' => 'Lari',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->user)->get(route('events.index'));

        $response->assertStatus(200);
        $response->assertSee('Futsal Championship');
        $response->assertSee('Basket League');
        $response->assertDontSee('Pending Event');
    }

    public function test_events_index_search_by_title(): void
    {
        Event::create([
            'organizer_id' => $this->organizer->id,
            'title' => 'Futsal Super Cup 2026',
            'description' => 'Turnamen futsal',
            'location' => 'Koni Manado',
            'event_date' => now()->addDays(10),
            'registration_deadline' => now()->addDays(5),
            'max_participants' => 20,
            'ticket_price' => 0,
            'category' => 'Futsal',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->user)->get(route('events.index', ['search' => 'Futsal']));

        $response->assertStatus(200);
        $response->assertSee('Futsal Super Cup 2026');
    }

    public function test_events_index_filter_by_category(): void
    {
        Event::create([
            'organizer_id' => $this->organizer->id,
            'title' => 'Basket Competition',
            'description' => 'Desc',
            'location' => 'GOR Manado',
            'event_date' => now()->addDays(10),
            'registration_deadline' => now()->addDays(5),
            'max_participants' => 20,
            'ticket_price' => 0,
            'category' => 'Basket',
            'status' => 'active',
        ]);

        Event::create([
            'organizer_id' => $this->organizer->id,
            'title' => 'Badminton Cup',
            'description' => 'Desc',
            'location' => 'Manado',
            'event_date' => now()->addDays(15),
            'registration_deadline' => now()->addDays(7),
            'max_participants' => 30,
            'ticket_price' => 25000,
            'category' => 'Bulutangkis',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->user)->get(route('events.index', ['category' => 'Basket']));

        $response->assertStatus(200);
        $response->assertSee('Basket Competition');
        $response->assertDontSee('Badminton Cup');
    }

    public function test_events_index_empty_state(): void
    {
        $response = $this->actingAs($this->user)->get(route('events.index'));

        $response->assertStatus(200);
        $response->assertSee('Tidak ada event ditemukan');
    }

    public function test_events_show_guarded_by_status(): void
    {
        $event = Event::create([
            'organizer_id' => $this->organizer->id,
            'title' => 'Pending Event',
            'description' => 'Desc',
            'location' => 'Manado',
            'event_date' => now()->addDays(20),
            'registration_deadline' => now()->addDays(10),
            'max_participants' => 10,
            'ticket_price' => 0,
            'category' => 'Lari',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->user)->get(route('events.show', $event));

        $response->assertRedirect(route('events.index'));
        $response->assertSessionHas('error', 'Event tidak ditemukan atau belum disetujui.');
    }

    public function test_events_show_allows_active_event(): void
    {
        $event = Event::create([
            'organizer_id' => $this->organizer->id,
            'title' => 'Active Event Detail',
            'description' => 'Desc',
            'location' => 'Koni Manado',
            'event_date' => now()->addDays(10),
            'registration_deadline' => now()->addDays(5),
            'max_participants' => 20,
            'ticket_price' => 0,
            'category' => 'Futsal',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->user)->get(route('events.show', $event));

        $response->assertStatus(200);
        $response->assertSee('Active Event Detail');
    }
}
