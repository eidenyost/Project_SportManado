<?php

namespace Tests\Feature;

use App\Models\Organizer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OrganizerVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');
    }

    public function test_user_can_submit_eo_registration_with_verifications(): void
    {
        $user = User::create([
            'name' => 'John Doe',
            'email' => 'johndoe@test.com',
            'password' => bcrypt('password'),
            'role' => 'user',
        ]);

        $this->actingAs($user);

        // Submit form
        $response = $this->post(route('organizer.store-setup'), [
            'organization_name' => 'Kawanua Club',
            'phone' => '081234567890',
            'description' => 'A community group',
            'ktp' => UploadedFile::fake()->create('ktp.jpg', 100, 'image/jpeg'),
            'selfie' => UploadedFile::fake()->create('selfie.jpg', 100, 'image/jpeg'),
        ]);

        $response->assertRedirect(route('organizer.pending'));

        // Assert record exists in database as pending
        $this->assertDatabaseHas('organizers', [
            'user_id' => $user->id,
            'organization_name' => 'Kawanua Club',
            'phone' => '081234567890',
            'status' => 'pending',
        ]);

        $organizer = Organizer::where('user_id', $user->id)->first();
        $this->assertNotNull($organizer->ktp_path);
        $this->assertNotNull($organizer->selfie_path);

        // Assert files exist in private storage
        Storage::disk('local')->assertExists($organizer->ktp_path);
        Storage::disk('local')->assertExists($organizer->selfie_path);
    }

    public function test_validation_rejects_invalid_whatsapp(): void
    {
        $user = User::create([
            'name' => 'John Doe',
            'email' => 'johndoe@test.com',
            'password' => bcrypt('password'),
            'role' => 'user',
        ]);

        $this->actingAs($user);

        // Submit with invalid phone
        $response = $this->post(route('organizer.store-setup'), [
            'organization_name' => 'Kawanua Club',
            'phone' => '12345',
            'ktp' => UploadedFile::fake()->create('ktp.jpg', 100, 'image/jpeg'),
            'selfie' => UploadedFile::fake()->create('selfie.jpg', 100, 'image/jpeg'),
        ]);

        $response->assertSessionHasErrors(['phone']);
    }

    public function test_eo_can_resubmit_after_rejection(): void
    {
        $user = User::create([
            'name' => 'John Doe',
            'email' => 'johndoe@test.com',
            'password' => bcrypt('password'),
            'role' => 'user',
        ]);

        $organizer = Organizer::create([
            'user_id' => $user->id,
            'organization_name' => 'Kawanua Club',
            'phone' => '081234567890',
            'status' => 'rejected',
            'admin_notes' => 'KTP is blur',
        ]);

        $this->actingAs($user);

        // Submit corrected documents
        $response = $this->post(route('organizer.store-setup'), [
            'organization_name' => 'Kawanua Club Updated',
            'phone' => '081234567890',
            'ktp' => UploadedFile::fake()->create('ktp_clear.jpg', 100, 'image/jpeg'),
            'selfie' => UploadedFile::fake()->create('selfie_clear.jpg', 100, 'image/jpeg'),
        ]);

        $response->assertRedirect(route('organizer.pending'));

        // Assert status updated to pending and name updated
        $organizer->refresh();
        $this->assertEquals('pending', $organizer->status);
        $this->assertEquals('Kawanua Club Updated', $organizer->organization_name);
        $this->assertNull($organizer->admin_notes); // Notes cleared
    }

    public function test_guest_can_register_as_organizer_with_verification_fields_in_one_step(): void
    {
        $response = $this->post('/register', [
            'name' => 'Jane Organizer',
            'email' => 'janeorganizer@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'organizer',
            'organization_name' => 'Jane Sports Community',
            'phone' => '081298765432',
            'description' => 'We organize community events',
            'ktp' => UploadedFile::fake()->create('jane_ktp.jpg', 100, 'image/jpeg'),
            'selfie' => UploadedFile::fake()->create('jane_selfie.jpg', 100, 'image/jpeg'),
        ]);

        $response->assertRedirect(route('organizer.pending'));

        // Assert user was created with default role user
        $user = User::where('email', 'janeorganizer@test.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals('user', $user->role);

        // Assert organizer profile exists in pending state
        $this->assertDatabaseHas('organizers', [
            'user_id' => $user->id,
            'organization_name' => 'Jane Sports Community',
            'phone' => '081298765432',
            'status' => 'pending',
        ]);

        $organizer = $user->organizer;
        $this->assertNotNull($organizer->ktp_path);
        $this->assertNotNull($organizer->selfie_path);

        Storage::disk('local')->assertExists($organizer->ktp_path);
        Storage::disk('local')->assertExists($organizer->selfie_path);
    }
}
