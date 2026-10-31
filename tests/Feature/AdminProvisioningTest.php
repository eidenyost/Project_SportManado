<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminProvisioningTest extends TestCase
{
    use RefreshDatabase;

    public function test_interactive_admin_is_created_with_a_hashed_password(): void
    {
        $this->artisan('app:create-admin')->expectsQuestion('Nama admin','Administrator Demo')
            ->expectsQuestion('Email admin','ADMIN@EXAMPLE.TEST')
            ->expectsQuestion('Password (minimal 12 karakter)','Unique-test-pass-2026!')
            ->expectsQuestion('Ulangi password','Unique-test-pass-2026!')->assertSuccessful();
        $user=User::where('email','admin@example.test')->firstOrFail();
        $this->assertSame('admin',$user->role);
        $this->assertTrue(Hash::check('Unique-test-pass-2026!',$user->password));
    }

    public function test_existing_participant_is_not_silently_promoted(): void
    {
        $user=User::factory()->create(['email'=>'member@example.test','role'=>'user']);
        $this->artisan('app:create-admin')->expectsQuestion('Nama admin','Demo')
            ->expectsQuestion('Email admin','MEMBER@EXAMPLE.TEST')
            ->expectsQuestion('Password (minimal 12 karakter)','Unique-test-pass-2026!')
            ->expectsQuestion('Ulangi password','Unique-test-pass-2026!')->assertFailed();
        $this->assertSame('user',$user->fresh()->role);
    }
}
