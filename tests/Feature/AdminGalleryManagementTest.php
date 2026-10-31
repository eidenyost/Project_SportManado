<?php

namespace Tests\Feature;

use App\Models\GalleryPhoto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminGalleryManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);
    }

    public function test_admin_can_upload_gallery_photo(): void
    {
        $this->actingAs($this->admin);

        $response = $this->post(route('admin.gallery.store'), [
            'image' => UploadedFile::fake()->create('photo.jpg', 500, 'image/jpeg'),
        ]);

        $response->assertRedirect(route('admin.dashboard', ['tab' => 'gallery']));

        $this->assertDatabaseCount('gallery_photos', 1);

        $photo = GalleryPhoto::first();
        $this->assertNotNull($photo->image_path);
        Storage::disk('public')->assertExists($photo->image_path);
    }

    public function test_upload_rejects_non_image(): void
    {
        $this->actingAs($this->admin);

        $response = $this->post(route('admin.gallery.store'), [
            'image' => UploadedFile::fake()->create('document.pdf', 500, 'application/pdf'),
        ]);

        $response->assertSessionHasErrors(['image']);
        $this->assertDatabaseCount('gallery_photos', 0);
    }

    public function test_admin_can_delete_gallery_photo(): void
    {
        $this->actingAs($this->admin);

        Storage::disk('public')->put('gallery-photos/test.jpg', 'image content');

        $photo = GalleryPhoto::create([
            'image_path' => 'gallery-photos/test.jpg',
        ]);

        $response = $this->delete(route('admin.gallery.destroy', $photo));
        $response->assertRedirect(route('admin.dashboard', ['tab' => 'gallery']));

        $this->assertDatabaseMissing('gallery_photos', ['id' => $photo->id]);
        Storage::disk('public')->assertMissing('gallery-photos/test.jpg');
    }
}
