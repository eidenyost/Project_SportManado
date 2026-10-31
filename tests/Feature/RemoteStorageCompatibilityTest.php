<?php

namespace Tests\Feature;

use App\Models\{Event, EventRegistrationRequest, GalleryPhoto, Organizer, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RemoteStorageCompatibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_uses_configured_storage_urls_for_banners_and_gallery(): void
    {
        config(['filesystems.disks.public.url' => 'https://media.example.test/public']);
        Storage::forgetDisk('public');
        [, $event] = $this->event();
        $event->update(['banner' => 'event-banners/banner.png']);
        GalleryPhoto::create(['image_path' => 'gallery-photos/photo.png']);

        $this->get('/')->assertOk()
            ->assertSee('https://media.example.test/public/event-banners/banner.png', false)
            ->assertSee('https://media.example.test/public/gallery-photos/photo.png', false);
    }

    public function test_event_document_is_only_available_to_its_owner_or_admin(): void
    {
        Storage::fake('local');
        [$owner, $event] = $this->event();
        $event->update(['proposal' => 'event-proposals/proposal.pdf']);
        Storage::disk('local')->put($event->proposal, '%PDF-private-proposal');
        $url = '/organizer/events/'.$event->id.'/documents/proposal';

        $this->get($url)->assertRedirect('/login');
        $this->actingAs(User::factory()->create(['role' => 'user']))->get($url)->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'organizer']))->get($url)->assertForbidden();
        $this->actingAs($owner)->get($url)->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertStreamedContent('%PDF-private-proposal');
        $this->actingAs(User::factory()->create(['role' => 'admin']))->get($url)->assertOk();
        $this->actingAs($owner)->get('/organizer/events/'.$event->id.'/documents/password')->assertNotFound();
    }

    public function test_registration_request_document_is_not_shared_with_other_participants(): void
    {
        Storage::fake('local');
        $owner = User::factory()->create(['role' => 'user']);
        $request = EventRegistrationRequest::create([
            'user_id' => $owner->id, 'organization_name' => 'Private test', 'phone' => '081234567890',
            'event_title' => 'Private request', 'event_description' => 'Test', 'event_location' => 'Manado',
            'event_date' => now()->addDays(10), 'registration_deadline' => now()->addDays(5),
            'max_participants' => 10, 'ticket_price' => 0, 'category' => 'Futsal',
            'proposal_file' => 'event-reg-proposals/proposal.pdf', 'permit_file' => 'event-reg-permits/permit.pdf',
            'status' => 'pending',
        ]);
        Storage::disk('local')->put($request->proposal_file, '%PDF-private-request');
        $url = '/event-registration/'.$request->id.'/documents/proposal';
        $this->actingAs(User::factory()->create(['role' => 'user']))->get($url)->assertForbidden();
        $this->actingAs($owner)->get($url)->assertOk()->assertStreamedContent('%PDF-private-request');
        $this->get(route('event-registration.status'))->assertOk()->assertSee($url, false)
            ->assertDontSee('/storage/event-reg-proposals/', false);
    }

    public function test_admin_document_stream_does_not_require_a_local_filesystem_path(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $disk = \Mockery::mock();
        $disk->shouldNotReceive('path');
        $disk->shouldReceive('exists')->once()->with('organizer-ktps/document.pdf')->andReturn(true);
        $disk->shouldReceive('response')->once()->with('organizer-ktps/document.pdf', null, \Mockery::type('array'))
            ->andReturn(response()->stream(fn () => print('private remote document'), 200, ['Cache-Control' => 'private, no-store']));
        Storage::shouldReceive('disk')->with('local')->andReturn($disk);

        $this->actingAs($admin)->get(route('admin.download-document', ['path' => 'organizer-ktps/document.pdf']))
            ->assertOk()->assertStreamedContent('private remote document');
    }

    public function test_admin_download_rejects_traversal_before_touching_storage(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Storage::shouldReceive('disk')->never();
        $this->actingAs($admin)->get(route('admin.download-document', ['path' => 'event-proposals/../../.env']))
            ->assertForbidden();
    }

    public function test_remote_storage_keeps_private_files_in_a_separate_bucket_without_public_urls(): void
    {
        $remote = $this->remoteConfig();
        $this->assertSame('s3', $remote['disks']['local']['driver']);
        $this->assertSame('sportmanado-private', $remote['disks']['local']['bucket']);
        $this->assertArrayNotHasKey('url', $remote['disks']['local']);
        $this->assertSame('sportmanado-public', $remote['disks']['public']['bucket']);
        $this->assertTrue($remote['disks']['local']['throw']);
        $this->assertTrue($remote['disks']['public']['throw']);
    }

    public function test_remote_storage_rejects_accidentally_shared_public_and_private_buckets(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->remoteConfig(['AWS_PRIVATE_BUCKET' => 'sportmanado-public']);
    }

    public function test_remote_storage_does_not_silently_fall_back_when_credentials_are_missing(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->remoteConfig(['AWS_SECRET_ACCESS_KEY' => '']);
    }

    private function remoteConfig(array $overrides = []): array
    {
        $values = array_merge([
            'MEDIA_STORAGE_DRIVER' => 's3', 'AWS_ACCESS_KEY_ID' => 'test-access-key',
            'AWS_SECRET_ACCESS_KEY' => 'test-secret-key', 'AWS_DEFAULT_REGION' => 'ap-northeast-1',
            'AWS_ENDPOINT' => 'https://storage.example.test/storage/v1/s3',
            'AWS_PUBLIC_URL' => 'https://storage.example.test/storage/v1/object/public/sportmanado-public',
            'AWS_PUBLIC_BUCKET' => 'sportmanado-public', 'AWS_PRIVATE_BUCKET' => 'sportmanado-private',
        ], $overrides);
        $before = [];
        foreach ($values as $key => $value) { $before[$key] = $_ENV[$key] ?? null; $_ENV[$key] = $value; }
        try {
            return require config_path('filesystems.php');
        } finally {
            foreach ($before as $key => $value) {
                if ($value === null) unset($_ENV[$key]); else $_ENV[$key] = $value;
            }
        }
    }

    private function event(): array
    {
        $owner = User::factory()->create(['role' => 'organizer']);
        $organizer = Organizer::create(['user_id' => $owner->id, 'organization_name' => 'Storage test', 'status' => 'approved']);
        $event = Event::create([
            'organizer_id' => $organizer->id, 'title' => 'Storage event', 'description' => 'Test', 'location' => 'Manado',
            'event_date' => now()->addDays(10), 'registration_deadline' => now()->addDays(5),
            'max_participants' => 10, 'ticket_price' => 0, 'category' => 'Futsal', 'status' => 'active', 'auto_approve' => true,
        ]);
        return [$owner, $event];
    }
}
