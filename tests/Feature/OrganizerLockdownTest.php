<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Organizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizerLockdownTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_organizer_is_locked_to_pending_page()
    {
        $user = User::factory()->create(['role' => 'user']);
        $organizer = Organizer::create([
            'user_id' => $user->id,
            'organization_name' => 'Pending EO Corp',
            'phone' => '081234567890',
            'status' => 'pending',
            'ktp_path' => 'dummy_ktp.jpg',
            'selfie_path' => 'dummy_selfie.jpg',
        ]);

        $this->actingAs($user);

        // Try accessing main user dashboard
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('organizer.pending'));

        // Accessing organizer pending page itself should be allowed
        $response = $this->get(route('organizer.pending'));
        $response->assertStatus(200);
    }

    public function test_rejected_organizer_is_redirected_to_setup_page()
    {
        $user = User::factory()->create(['role' => 'user']);
        $organizer = Organizer::create([
            'user_id' => $user->id,
            'organization_name' => 'Rejected EO Corp',
            'phone' => '081234567890',
            'status' => 'rejected',
            'admin_notes' => 'Invalid document scan',
            'ktp_path' => 'dummy_ktp.jpg',
            'selfie_path' => 'dummy_selfie.jpg',
        ]);

        $this->actingAs($user);

        // Accessing dashboard redirects to setup
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('organizer.setup'));
        $response->assertSessionHas('error', 'Profil Penyelenggara Anda ditolak: Invalid document scan');
    }

    public function test_regular_user_without_eo_profile_is_not_affected()
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user);

        // Accessing dashboard works fine
        $response = $this->get(route('dashboard'));
        $response->assertStatus(200);
    }
}
