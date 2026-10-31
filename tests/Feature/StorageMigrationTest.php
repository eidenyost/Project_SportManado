<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class StorageMigrationTest extends TestCase
{
    private string $snapshot;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Storage::fake('local');
        $this->snapshot = storage_path('framework/testing/migration-'.Str::uuid());
        File::ensureDirectoryExists($this->snapshot.'/public/event-banners');
        File::ensureDirectoryExists($this->snapshot.'/private/organizer-ktps');
        File::put($this->snapshot.'/public/event-banners/banner.png', 'public-image');
        File::put($this->snapshot.'/private/organizer-ktps/identity.pdf', 'private-document');
        File::put($this->snapshot.'/private/.gitignore', '*');
    }

    protected function tearDown(): void
    {
        if (isset($this->snapshot)) File::deleteDirectory($this->snapshot);
        parent::tearDown();
    }

    public function test_dry_run_does_not_upload_or_change_source_files(): void
    {
        $this->artisan('storage:migrate', ['source' => $this->snapshot])->assertSuccessful();
        Storage::disk('public')->assertMissing('event-banners/banner.png');
        Storage::disk('local')->assertMissing('organizer-ktps/identity.pdf');
        $this->assertSame('private-document', File::get($this->snapshot.'/private/organizer-ktps/identity.pdf'));
    }

    public function test_upload_is_verified_separated_by_disk_and_can_be_resumed(): void
    {
        $report = $this->snapshot.'/report.json';
        $this->artisan('storage:migrate', ['source' => $this->snapshot, '--apply' => true, '--report' => $report])->assertSuccessful();
        $results = json_decode(File::get($report), true);
        $this->assertTrue($results['passed']);
        $this->assertCount(2, $results['files']);
        $this->assertSame('private-document', Storage::disk('local')->get('organizer-ktps/identity.pdf'));
        Storage::disk('public')->assertMissing('organizer-ktps/identity.pdf');
        Storage::disk('local')->assertMissing('.gitignore');
        $this->artisan('storage:migrate', ['source' => $this->snapshot, '--apply' => true])->assertSuccessful();
    }

    public function test_different_destination_file_is_never_overwritten(): void
    {
        Storage::disk('public')->put('event-banners/banner.png', 'existing-different-content');
        $this->artisan('storage:migrate', ['source' => $this->snapshot, '--apply' => true])->assertFailed();
        $this->assertSame('existing-different-content', Storage::disk('public')->get('event-banners/banner.png'));
    }
}
