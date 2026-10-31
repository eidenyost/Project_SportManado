<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Organizer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScannerFeedbackViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_scanner_page_exposes_persistent_accessible_feedback_for_success_and_rejection(): void
    {
        $organizerUser = User::factory()->create(['role' => 'organizer', 'email_verified_at' => now()]);
        $organizer = Organizer::create([
            'user_id' => $organizerUser->id,
            'organization_name' => 'Scanner Feedback Organizer',
            'phone' => '08123456789',
            'status' => 'approved',
        ]);
        $event = Event::create([
            'organizer_id' => $organizer->id,
            'title' => 'Scanner Feedback Event',
            'description' => 'Synthetic scanner fixture',
            'location' => 'Manado',
            'event_date' => now()->addDays(10),
            'registration_deadline' => now()->addDays(5),
            'max_participants' => 10,
            'ticket_price' => 0,
            'category' => 'Futsal',
            'status' => 'active',
            'auto_approve' => true,
        ]);

        $this->actingAs($organizerUser)
            ->get(route('organizer.events.scan', $event))
            ->assertOk()
            ->assertSee('id="scan-result"', false)
            ->assertSee('aria-live="assertive"', false)
            ->assertSee('Tiket berhasil divalidasi')
            ->assertSee('Check-in peserta berhasil dicatat.')
            ->assertSee('Tiket sudah digunakan')
            ->assertSee('QR Code ini telah divalidasi sebelumnya.')
            ->assertSee('Validasi belum dapat dipastikan. Silakan periksa koneksi dan coba lagi.')
            ->assertSee('textContent');
    }
}
