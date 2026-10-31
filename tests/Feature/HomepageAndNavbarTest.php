<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Event;
use App\Models\Organizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomepageAndNavbarTest extends TestCase
{
    use RefreshDatabase;

    public function test_navbar_does_not_contain_jelajahi_event_and_tentang_for_guests()
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertDontSee('Jelajahi Event');
        $response->assertDontSee('Tentang');
    }

    public function test_login_button_is_hidden_on_mobile_header_but_visible_in_mobile_menu()
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        // Memastikan tombol masuk luar memiliki class pendukung penyembunyian mobile (hidden)
        $response->assertSee('class="hidden md:flex bg-accent', false);
        // Memastikan tombol masuk ada di dalam mobile menu (di render saat guest)
        $response->assertSee('Masuk');
    }

    public function test_cta_button_redirects_guest_to_organizer_registration()
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee(route('register.form', ['role' => 'organizer']));
    }

    public function test_cta_button_redirects_organizer_to_event_creation()
    {
        $user = User::factory()->create(['role' => 'organizer']);
        $organizer = Organizer::create([
            'user_id' => $user->id,
            'organization_name' => 'Test Community',
            'description' => 'Test',
            'phone' => '081234567890',
            'status' => 'approved',
        ]);
        $response = $this->actingAs($user)->get('/');
        $response->assertStatus(200);
        $response->assertSee(route('organizer.events.create'));
    }

    public function test_cta_button_redirects_participant_to_events_index()
    {
        $participant = User::factory()->create(['role' => 'user']);
        $response = $this->actingAs($participant)->get('/');
        $response->assertStatus(200);
        $response->assertSee(route('events.index'));
    }

    public function test_homepage_displays_dynamic_events()
    {
        $user = User::factory()->create(['role' => 'organizer']);
        $organizer = Organizer::create([
            'user_id' => $user->id,
            'organization_name' => 'Test Community',
            'description' => 'Test',
            'phone' => '081234567890',
            'status' => 'approved',
        ]);

        $event = Event::create([
            'organizer_id' => $organizer->id,
            'title' => 'Futsal Super Cup 2026',
            'description' => 'Turnamen futsal terbesar di Manado.',
            'location' => 'Koni Manado',
            'event_date' => now()->addDays(10),
            'registration_deadline' => now()->addDays(5),
            'max_participants' => 32,
            'ticket_price' => 50000,
            'category' => 'Futsal',
            'status' => 'active',
        ]);

        $response = $this->get('/');
        $response->assertSee('Futsal Super Cup 2026');
        $response->assertSee('Rp 50.000');
    }

    public function test_homepage_displays_dynamic_statistics_and_latest_registered_events()
    {
        // 1. Buat data dummy
        $user = User::factory()->create(['role' => 'organizer']);
        $organizer1 = Organizer::create([
            'user_id' => $user->id,
            'organization_name' => 'Komunitas A',
            'description' => 'Test',
            'phone' => '081234567890',
            'status' => 'approved',
        ]);
        $organizer2 = Organizer::create([
            'user_id' => User::factory()->create(['role' => 'organizer'])->id,
            'organization_name' => 'Komunitas B',
            'description' => 'Test',
            'phone' => '081234567891',
            'status' => 'approved',
        ]);

        // Buat event lama
        $eventOld = Event::create([
            'organizer_id' => $organizer1->id,
            'title' => 'Event Terlama',
            'description' => 'Lama',
            'location' => 'Koni',
            'event_date' => now()->addDays(2),
            'registration_deadline' => now()->addDay(),
            'max_participants' => 10,
            'ticket_price' => 0,
            'category' => 'Lari',
            'status' => 'active',
        ]);
        $eventOld->created_at = now()->subMinutes(10);
        $eventOld->save();

        // Buat event baru didaftarkan (created_at lebih baru)
        $eventNew = Event::create([
            'organizer_id' => $organizer2->id,
            'title' => 'Event Paling Baru Didaftarkan',
            'description' => 'Baru',
            'location' => 'Koni',
            'event_date' => now()->addDays(5),
            'registration_deadline' => now()->addDays(3),
            'max_participants' => 20,
            'ticket_price' => 0,
            'category' => 'Futsal',
            'status' => 'active',
        ]);

        $response = $this->get('/');
        $response->assertStatus(200);

        // Verifikasi variabel statistik dilempar ke view
        $response->assertViewHas('eventsActiveCount', 2);
        $response->assertViewHas('organizersCount', 2);

        // Verifikasi event terurut berdasarkan yang paling baru didaftarkan (created_at)
        $eventsInView = $response->original->getData()['events'];
        $this->assertEquals('Event Paling Baru Didaftarkan', $eventsInView->first()->title);
    }

    public function test_footer_contains_only_academic_credentials_and_no_dummy_menus()
    {
        $response = $this->get('/');
        $response->assertStatus(200);

        // Footer harus menampilkan platform branding
        $response->assertSee('Manajemen Event & E-Ticketing Olahraga');

        // Tidak boleh ada link menu dummy
        $response->assertDontSee('Informasi</span>', false);
        $response->assertDontSee('Kebijakan</span>', false);
        $response->assertDontSee('Langganan Berita</span>', false);
    }

    public function test_participant_navbar_links_and_footer_copyright()
    {
        $participant = User::factory()->create(['role' => 'user']);
        $response = $this->actingAs($participant)->get('/dashboard');
        $response->assertStatus(200);

        // Link baru yang harus ada
        $response->assertSee('Dashboard');
        $response->assertSee('Event');
        $response->assertSee('Tiket');
        $response->assertSee('Panduan');
        $response->assertSee('Galeri');

        // Link lama yang tidak boleh ada
        $response->assertDontSee('Tiket Saya');
        $response->assertDontSee('Cari Event');
        $response->assertDontSee('Jelajahi Event');
        $response->assertDontSee('Daftarkan Event');
        $response->assertDontSee('Menunggu Verifikasi');

        // Bagian tiket & statistik yang harus dihapus dari dashboard
        $response->assertDontSee('Event Diikuti');
        $response->assertDontSee('Tiket Aktif');
        $response->assertDontSee('Menunggu Bayar');
        $response->assertDontSee('E-Tiket Terbaru Anda');

        // Teks copyright lama harus sudah dihapus dari footer
        $response->assertDontSee('Sportmanado. Hak Cipta Dilindungi.');
    }
}
