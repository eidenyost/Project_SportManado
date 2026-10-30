<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\Organizer;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class SeedTrendingEvents extends Command
{
    protected $signature = 'app:seed-trending-events';

    protected $description = 'Buat akun EOSportManado + 8 event trending berdasarkan kategori';

    public function handle(): int
    {
        if (User::where('email', 'eosportmanado@gmail.com')->exists()) {
            $this->error('Akun eosportmanado@gmail.com sudah ada.');
            return Command::FAILURE;
        }

        $this->info('Membuat akun EOSportManado...');

        $user = User::create([
            'name' => 'EOSportManado',
            'email' => 'eosportmanado@gmail.com',
            'password' => 'password123',
            'role' => 'organizer',
        ]);

        $organizer = Organizer::create([
            'user_id' => $user->id,
            'organization_name' => 'EOSportManado Community',
            'phone' => '081234567890',
            'description' => 'Event organizer olahraga di Manado, Sulawesi Utara.',
            'status' => 'approved',
        ]);

        $this->info('Membuat placeholder files...');
        $files = $this->generateFiles();

        $events = [
            [
                'title' => 'Bung Karno Run 2026',
                'description' => "Bung Karno Run 2026 adalah event lari tahunan terbesar di Kota Manado yang diselenggarakan dalam rangka memperingati Bulan Bung Karno. Mengusung tema kebersamaan, sportivitas, dan semangat nasionalisme, event ini terbuka untuk seluruh masyarakat umum.\n\nTerdiri dari tiga kategori:\n• 3K Fun Run – untuk keluarga dan pemula\n• 5K Open – untuk pelari rekreasi\n• 10K Elite – untuk pelari profesional\n\nRute lari melintasi ikon-ikon kota Manado seperti kawasan Boulevard dan Megamas. Setiap peserta mendapatkan medali finisher, goodie bag, dan kaos event eksklusif.",
                'location' => 'Kawasan Megamas, Manado',
                'event_date' => '2026-11-08 06:00:00',
                'registration_deadline' => '2026-11-01 23:59:00',
                'max_participants' => 1500,
                'ticket_price' => 125000,
                'category' => 'Lari & Trail',
                'custom_fields' => [
                    ['label' => 'Ukuran Kaos', 'type' => 'radio', 'options' => ['S', 'M', 'L', 'XL'], 'image' => null],
                    ['label' => 'Kategori Lari', 'type' => 'radio', 'options' => ['3K Fun Run', '5K Open', '10K Elite'], 'image' => null],
                    ['label' => 'Nomor WhatsApp', 'type' => 'text', 'options' => [], 'image' => null],
                ],
            ],
            [
                'title' => 'Kawanua Padel Open 2026',
                'description' => "Kawanua Padel Open 2026 adalah turnamen padel pertama dan terbesar di Sulawesi Utara yang digelar di Lapangan Kawanua Padel, Tikala, Manado.\n\nKategori yang dipertandingkan:\n• Women's Beginner\n• Men's Beginner\n• Men's Bronze\n• Mixed Open\n\nTurnamen ini menjadi ajang silaturahmi sekaligus kompetisi bagi para pecinta padel di Sulawesi Utara. Cocok untuk pemula maupun pemain berpengalaman. Setiap tim mendapatkan sertifikat partisipasi dan hadiah menarik bagi para juara.",
                'location' => 'Lapangan Kawanua Padel, Tikala, Manado',
                'event_date' => '2026-09-12 08:00:00',
                'registration_deadline' => '2026-09-05 23:59:00',
                'max_participants' => 100,
                'ticket_price' => 100000,
                'category' => 'Padel / Tenis',
                'custom_fields' => [
                    ['label' => 'Kategori', 'type' => 'radio', 'options' => ["Women's Beginner", "Men's Beginner", "Men's Bronze", 'Mixed Open'], 'image' => null],
                    ['label' => 'Nama Pasangan', 'type' => 'text', 'options' => [], 'image' => null],
                    ['label' => 'Nomor WhatsApp', 'type' => 'text', 'options' => [], 'image' => null],
                ],
            ],
            [
                'title' => 'FBKM MLBB Championship 2026',
                'description' => "Festival Bung Karno Manado (FBKM) 2026 menghadirkan Mobile Legends: Bang Bang Championship! Turnamen e-sports terbesar di Manado yang memperebutkan total hadiah puluhan juta rupiah.\n\nSistem pertandingan:\n• Babak penyisihan: Best of 1\n• Semifinal & Final: Best of 3\n• Sistem double elimination\n\nSetiap tim terdiri dari 5 pemain inti + 1 pemain cadangan. Peserta akan mendapatkan jersey tim eksklusif dan merchandise limited edition.",
                'location' => 'Manado Town Square, Manado',
                'event_date' => '2026-07-15 09:00:00',
                'registration_deadline' => '2026-07-08 23:59:00',
                'max_participants' => 320,
                'ticket_price' => 75000,
                'category' => 'E-Sports',
                'custom_fields' => [
                    ['label' => 'Nama Tim', 'type' => 'text', 'options' => [], 'image' => null],
                    ['label' => 'Nickname Captain', 'type' => 'text', 'options' => [], 'image' => null],
                    ['label' => 'ID MLBB Captain', 'type' => 'text', 'options' => [], 'image' => null],
                    ['label' => 'Jumlah Pemain', 'type' => 'radio', 'options' => ['5 Pemain', '6 Pemain (1 Cadangan)'], 'image' => null],
                ],
            ],
            [
                'title' => 'Gowes Nusantara Manado 2026',
                'description' => "Gowes Nusantara Manado 2026 adalah event bersepeda santai yang menyusuri keindahan kota Manado. Mulai dari Boulevard hingga kawasan pesisir, peserta akan menikmati pemandangan laut dan kota yang spektakuler.\n\nTerdapat dua pilihan rute:\n• Rute Pendek (20 km) – cocok untuk keluarga dan pesepeda pemula\n• Rute Panjang (50 km) – untuk pesepeda yang ingin tantangan lebih\n\nFasilitas: Jersey eksklusif, makanan ringan di pos bantuan, mekanik di sepanjang rute, dan medali finisher.",
                'location' => 'Boulevard Manado (Start: Megamas)',
                'event_date' => '2026-08-20 05:30:00',
                'registration_deadline' => '2026-08-13 23:59:00',
                'max_participants' => 300,
                'ticket_price' => 50000,
                'category' => 'Bersepeda',
                'custom_fields' => [
                    ['label' => 'Ukuran Jersey', 'type' => 'radio', 'options' => ['S', 'M', 'L', 'XL', 'XXL'], 'image' => null],
                    ['label' => 'Pilihan Rute', 'type' => 'radio', 'options' => ['20 km (Santai)', '50 km (Tantangan)'], 'image' => null],
                    ['label' => 'Nomor WhatsApp', 'type' => 'text', 'options' => [], 'image' => null],
                ],
            ],
            [
                'title' => 'Liga Futsal Komunitas Manado',
                'description' => "Liga Futsal Antar Komunitas Manado 2026 adalah ajang kompetisi futsal yang mempertemukan komunitas-komunitas terbaik dari seluruh Manado dan sekitarnya.\n\nSistem pertandingan:\n• Babak grup: 4 grup, 3 tim per grup\n• Sistem kompetisi penuh\n• 2 tim teratas setiap grup lolos ke babak knockout\n\nSetiap tim minimal 8 pemain, maksimal 12 pemain. Seluruh pertandingan menggunakan bola futsal standar dan lapangan berstandar nasional.",
                'location' => 'GOR Futsal Manado',
                'event_date' => '2026-10-10 08:00:00',
                'registration_deadline' => '2026-10-03 23:59:00',
                'max_participants' => 144,
                'ticket_price' => 250000,
                'category' => 'Futsal',
                'custom_fields' => [
                    ['label' => 'Nama Tim', 'type' => 'text', 'options' => [], 'image' => null],
                    ['label' => 'Warna Jersey Utama', 'type' => 'text', 'options' => [], 'image' => null],
                    ['label' => 'Nama Kapten', 'type' => 'text', 'options' => [], 'image' => null],
                    ['label' => 'Nomor WhatsApp', 'type' => 'text', 'options' => [], 'image' => null],
                ],
            ],
            [
                'title' => '3x3 Street Basketball Manado',
                'description' => "3x3 Street Basketball Manado 2026 adalah turnamen basket 3 lawan 3 yang digelar di lapangan terbuka. Mengusung semangat street ball yang kompetitif namun santai, event ini cocok untuk semua kalangan.\n\nKategori:\n• Putra (16 tim)\n• Putri (8 tim) – jika kuota terpenuhi\n\nSetiap tim terdiri dari 3-4 pemain. Sistem pertandingan menggunakan format FIBA 3x3 dengan waktu 10 menit per game. Juara mendapatkan trophy, uang pembinaan, dan merchandise eksklusif.",
                'location' => 'Lapangan Basket Megamas, Manado',
                'event_date' => '2026-09-25 07:00:00',
                'registration_deadline' => '2026-09-18 23:59:00',
                'max_participants' => 64,
                'ticket_price' => 50000,
                'category' => 'Basket',
                'custom_fields' => [
                    ['label' => 'Nama Tim', 'type' => 'text', 'options' => [], 'image' => null],
                    ['label' => 'Nama Captain', 'type' => 'text', 'options' => [], 'image' => null],
                    ['label' => 'Kategori', 'type' => 'radio', 'options' => ['Putra', 'Putri'], 'image' => null],
                ],
            ],
            [
                'title' => 'Kejuaraan Bulutangkis Terbuka Sulut',
                'description' => "Kejuaraan Bulutangkis Terbuka Sulawesi Utara 2026 adalah event tahunan yang mempertemukan para pebulutangkis dari berbagai daerah di Sulawesi Utara.\n\nKategori yang dipertandingkan:\n• Tunggal Putra\n• Tunggal Putri\n• Ganda Putra\n• Ganda Putri\n• Ganda Campuran\n\nSistem pertandingan menggunakan format knock-out dengan rally point 3x21. Seluruh pertandingan menggunakan shuttlecock standar internasional. Pendaftaran gratis!",
                'location' => 'GOR Bulutangkis Manado',
                'event_date' => '2026-11-15 08:00:00',
                'registration_deadline' => '2026-11-08 23:59:00',
                'max_participants' => 128,
                'ticket_price' => 0,
                'category' => 'Bulutangkis',
                'custom_fields' => [
                    ['label' => 'Kategori', 'type' => 'radio', 'options' => ['Tunggal Putra', 'Tunggal Putri', 'Ganda Putra', 'Ganda Putri', 'Ganda Campuran'], 'image' => null],
                    ['label' => 'Nama Pasangan (jika ganda)', 'type' => 'text', 'options' => [], 'image' => null],
                    ['label' => 'Nomor WhatsApp', 'type' => 'text', 'options' => [], 'image' => null],
                ],
            ],
            [
                'title' => 'Boxing Night Manado 2026',
                'description' => "Boxing Night Manado 2026 adalah event tinju amatir yang digelar dalam rangkaian Festival Bung Karno Manado. Menampilkan pertandingan-pertandingan seru dari para petinju amatir terbaik di Sulawesi Utara.\n\nKelas yang dipertandingkan:\n• Kelas Ringan (Lightweight)\n• Kelas Medium (Welterweight)\n• Kelas Berat (Heavyweight)\n\nSetiap petinju wajib membawa perlengkapan sendiri (hand wrap, gum shield, boxing gloves). Perlengkapan tambahan disediakan panitia. Pertandingan menggunakan sistem 3 ronde x 2 menit.",
                'location' => 'GOR Olahraga Manado',
                'event_date' => '2026-07-20 18:00:00',
                'registration_deadline' => '2026-07-13 23:59:00',
                'max_participants' => 40,
                'ticket_price' => 100000,
                'category' => 'Olahraga Lainnya',
                'custom_fields' => [
                    ['label' => 'Kelas Berat', 'type' => 'radio', 'options' => ['Ringan (Lightweight)', 'Medium (Welterweight)', 'Berat (Heavyweight)'], 'image' => null],
                    ['label' => 'Nama Pelatih', 'type' => 'text', 'options' => [], 'image' => null],
                    ['label' => 'Nomor WhatsApp', 'type' => 'text', 'options' => [], 'image' => null],
                    ['label' => 'Berat Badan (kg)', 'type' => 'text', 'options' => [], 'image' => null],
                ],
            ],
        ];

        $this->info('Membuat 8 event...');
        $bar = $this->output->createProgressBar(count($events));
        $bar->start();

        foreach ($events as $data) {
            Event::create([
                'organizer_id' => $organizer->id,
                'title' => $data['title'],
                'description' => $data['description'],
                'location' => $data['location'],
                'event_date' => $data['event_date'],
                'registration_deadline' => $data['registration_deadline'],
                'max_participants' => $data['max_participants'],
                'ticket_price' => $data['ticket_price'],
                'category' => $data['category'],
                'banner' => $files['banner'],
                'proposal' => $files['proposal'],
                'permit' => $files['permit'],
                'ktp_image' => $files['ktp'],
                'status' => 'pending',
                'custom_fields' => $data['custom_fields'],
            ]);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info('✓ Akun EOSportManado telah dibuat!');
        $this->info('✓ 8 event telah didaftarkan dengan status pending.');
        $this->newLine();
        $this->warn('   Email: eosportmanado@gmail.com');
        $this->warn('   Password: password123');
        $this->newLine();
        $this->info('Login sebagai admin untuk approve event.');

        return Command::SUCCESS;
    }

    private function generateFiles(): array
    {
        $banner = $this->makeSvg('event-banners', 1280, 720, true);
        $proposal = $this->makeText('event-proposals');
        $permit = $this->makeText('event-permits');
        $ktp = $this->makeSvg('organizer-ktps', 640, 480, false);

        return compact('banner', 'proposal', 'permit', 'ktp');
    }

    private function makeSvg(string $dir, int $w, int $h, bool $public): string
    {
        $label = str_replace(['event-', 'organizer-', '-ktps', '-banners', '-proposals', '-permits'], ['', '', '', '', '', ''], $dir);
        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="{$w}" height="{$h}" viewBox="0 0 {$w} {$h}">
  <rect width="{$w}" height="{$h}" fill="#0d1527"/>
  <text x="50%" y="50%" font-family="Arial,sans-serif" font-size="36" fill="#ff6b00" text-anchor="middle" dominant-baseline="middle">SportManado - {$dir}</text>
</svg>
SVG;
        $disk = $public ? 'public' : 'local';
        $path = $dir . '/' . uniqid() . '.svg';
        Storage::disk($disk)->put($path, $svg);
        return $path;
    }

    private function makeText(string $dir): string
    {
        $path = $dir . '/' . uniqid() . '.txt';
        Storage::disk('local')->put($path, 'EOSportManado - Document: ' . $dir);
        return $path;
    }
}
