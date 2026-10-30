<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Organizer;
use App\Models\Event;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new \RuntimeException('Seeder pengembangan tidak boleh dijalankan di production. Gunakan app:create-admin.');
        }
        // 1. Create Organizer User
        $organizerUser = User::create([
            'name' => 'Manado Sports Organizer',
            'email' => 'organizer@sportmanado.com',
            'password' => Hash::make('password'),
            'role' => 'organizer',
        ]);

        // 2. Create Organizer Profile
        $organizerProfile = Organizer::create([
            'user_id' => $organizerUser->id,
            'organization_name' => 'Kawanua Sports Association',
            'phone' => '081122334455',
            'description' => 'Komunitas olahraga terpadu di Sulawesi Utara, berfokus pada pengembangan talenta muda dan penyempurnaan turnamen lokal di Kota Manado.',
        ]);

        // 3. Create General User (Participant)
        User::create([
            'name' => 'Eidenyost Wahani',
            'email' => 'user@sportmanado.com',
            'password' => Hash::make('password'),
            'role' => 'user',
        ]);

        // Create Admin User
        User::create([
            'name' => 'Admin SportManado',
            'email' => 'admin@sportmanado.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        // Seed sample events for development
        Event::factory()
            ->count(4)
            ->active()
            ->create(['organizer_id' => $organizerProfile->id]);

        Event::factory()
            ->count(1)
            ->pending()
            ->create(['organizer_id' => $organizerProfile->id]);
    }
}
