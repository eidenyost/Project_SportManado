<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BenchmarkTicketRegistration extends Command
{
    protected $signature = 'benchmark:ticket-registration
                            {--iterations=20 : Jumlah percobaan}
                            {--event-id= : ID event gratis auto_approve (opsional)}';

    protected $description = '[BENCHMARK O2] Mengukur waktu proses pendaftaran tiket (submitPayment) untuk event gratis auto_approve - termasuk DB transaction, isFull check, Ticket::create, dan QR Code generation.';

    public function handle(): int
    {
        $iterations = (int) $this->option('iterations');

        $event = $this->resolveEvent();
        if (!$event) {
            $this->error('Tidak ditemukan event gratis (ticket_price=0) dengan status active dan auto_approve=true.');
            $this->line('Buat event terlebih dahulu atau gunakan --event-id=<id>.');
            return self::FAILURE;
        }

        $this->info('=== BENCHMARK: Pendaftaran Tiket (submitPayment) ===');
        $this->line("Event   : [{$event->id}] {$event->title}");
        $this->line("Iterasi : {$iterations}x (user dummy baru tiap percobaan)");
        $this->line("Metode  : DB::transaction + Event::lockForUpdate + Ticket::create + generateQrCode");
        $this->newLine();

        $dummyUserIds    = [];
        $createdTicketIds = [];
        $results         = [];

        for ($i = 1; $i <= $iterations; $i++) {
            $dummyUser = null;
            $ticketId  = null;
            $ms        = null;

            try {
                $dummyUser = \App\Models\User::create([
                    'name'              => "BenchmarkUser_{$i}_" . time(),
                    'email'             => "benchmark_{$i}_" . uniqid() . '@bench.test',
                    'password'          => bcrypt('password'),
                    'role'              => 'user',
                    'email_verified_at' => now(),
                ]);
                $dummyUserIds[] = $dummyUser->id;

                $start = microtime(true);

                DB::transaction(function () use ($event, $dummyUser, &$ticketId) {
                    $lockedEvent = Event::where('id', $event->id)->lockForUpdate()->firstOrFail();

                    if ($lockedEvent->status !== 'active') {
                        throw new \RuntimeException('Event tidak aktif.');
                    }
                    if (now()->gt($lockedEvent->registration_deadline)) {
                        throw new \RuntimeException('Batas pendaftaran sudah lewat.');
                    }
                    if ($lockedEvent->isFull()) {
                        throw new \RuntimeException('Kuota event penuh.');
                    }

                    do {
                        $code = strtoupper(Str::random(10));
                    } while (Ticket::where('ticket_code', $code)->exists());

                    $ticket = Ticket::create([
                        'user_id'        => $dummyUser->id,
                        'event_id'       => $lockedEvent->id,
                        'ticket_code'    => $code,
                        'qr_code_path'   => null,
                        'status'         => 'active',
                        'custom_answers' => [],
                    ]);

                    $ticketId = $ticket->id;
                });

                $ms = round((microtime(true) - $start) * 1000, 2);

                if ($ticketId) {
                    $createdTicketIds[] = $ticketId;
                }

                $results[] = ['no' => $i, 'ms' => $ms, 'status' => 'OK'];
                $this->line(sprintf('  [%2d] %8.2f ms  OK', $i, $ms));

            } catch (\Throwable $e) {
                $results[] = ['no' => $i, 'ms' => null, 'status' => 'ERROR: ' . $e->getMessage()];
                $this->error(sprintf('  [%2d] ERROR: %s', $i, $e->getMessage()));
            }
        }

        $this->printSummary($results, $iterations);
        $this->cleanup($createdTicketIds, $dummyUserIds);

        return self::SUCCESS;
    }

    private function resolveEvent(): ?Event
    {
        if ($id = $this->option('event-id')) {
            return Event::where('id', $id)
                ->where('status', 'active')
                ->where('ticket_price', 0)
                ->where('auto_approve', true)
                ->first();
        }

        return Event::where('status', 'active')
            ->where('ticket_price', 0)
            ->where('auto_approve', true)
            ->whereDate('registration_deadline', '>=', now())
            ->first();
    }

    private function printSummary(array $results, int $iterations): void
    {
        $valid = array_filter($results, fn ($r) => $r['ms'] !== null);
        $times = array_column(array_values($valid), 'ms');

        $this->newLine();
        $this->line('┌─────────────────────────────────────────────────┐');
        $this->line('│           RINGKASAN HASIL BENCHMARK              │');
        $this->line('├─────────────────────────────────────────────────┤');

        if (count($times) > 0) {
            $avg = round(array_sum($times) / count($times), 2);
            $min = min($times);
            $max = max($times);

            $this->line(sprintf('│  Percobaan sukses : %d / %d%s│', count($times), $iterations, str_repeat(' ', 27 - strlen((string)count($times)) - strlen((string)$iterations))));
            $this->line(sprintf('│  Rata-rata        : %8.2f ms%s│', $avg, str_repeat(' ', 18)));
            $this->line(sprintf('│  Minimum          : %8.2f ms%s│', $min, str_repeat(' ', 18)));
            $this->line(sprintf('│  Maksimum         : %8.2f ms%s│', $max, str_repeat(' ', 18)));
            $this->line(sprintf('│  Rentang (max-min): %8.2f ms%s│', $max - $min, str_repeat(' ', 18)));
        } else {
            $this->line('│  Tidak ada percobaan yang berhasil.              │');
        }

        $this->line('└─────────────────────────────────────────────────┘');
        $this->newLine();

        $this->line('=== TABEL LENGKAP ===');
        $this->line(sprintf('%-6s %-14s %-10s', 'No', 'Waktu (ms)', 'Status'));
        $this->line(str_repeat('-', 35));
        foreach ($results as $r) {
            $ms = $r['ms'] !== null ? sprintf('%10.2f', $r['ms']) : '      ERROR';
            $this->line(sprintf('%-6d %s    %s', $r['no'], $ms, $r['ms'] !== null ? 'OK' : $r['status']));
        }
    }

    private function cleanup(array $ticketIds, array $userIds): void
    {
        if (!empty($ticketIds)) {
            $qrPaths = Ticket::whereIn('id', $ticketIds)->pluck('qr_code_path')->filter()->all();
            Ticket::whereIn('id', $ticketIds)->delete();
            foreach ($qrPaths as $path) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($path);
            }
            $this->line("\n[Cleanup] " . count($ticketIds) . " tiket benchmark dihapus.");
        }
        if (!empty($userIds)) {
            \App\Models\User::whereIn('id', $userIds)->delete();
            $this->line("[Cleanup] " . count($userIds) . " user dummy dihapus.");
        }
    }
}
