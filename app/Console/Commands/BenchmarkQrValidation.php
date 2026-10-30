<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\Organizer;
use App\Models\Ticket;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BenchmarkQrValidation extends Command
{
    protected $signature = 'benchmark:qr-validation
                            {--iterations=20 : Jumlah percobaan}
                            {--event-id= : ID event yang memiliki tiket aktif (opsional)}';

    protected $description = '[BENCHMARK O2] Mengukur waktu proses validasi QR Code (validateQr) - termasuk sanitasi kode, DB::transaction, lockForUpdate, cek status, dan update status ke used.';

    private array $dummyUserIds = [];

    public function handle(): int
    {
        $iterations = (int) $this->option('iterations');

        $event = $this->resolveEvent();
        if (!$event) {
            $this->error('Tidak ditemukan event aktif dengan tiket untuk benchmark.');
            return self::FAILURE;
        }

        $this->info('=== BENCHMARK: Validasi QR Code (validateQr) ===');
        $this->line("Event   : [{$event->id}] {$event->title}");
        $this->line("Iterasi : {$iterations}x (tiket berbeda tiap percobaan)");
        $this->line("Metode  : sanitasi kode + DB::transaction + lockForUpdate + update status=used");
        $this->newLine();

        $benchmarkTickets = $this->prepareBenchmarkTickets($event, $iterations);

        if (count($benchmarkTickets) < $iterations) {
            $this->warn("Hanya {$iterations} tiket benchmark yang berhasil disiapkan.");
        }

        $results = [];

        foreach ($benchmarkTickets as $idx => $ticket) {
            $no = $idx + 1;
            try {
                $rawCode = strtoupper(preg_replace('/[^A-Za-z0-9\-]/', '', trim($ticket->ticket_code)));

                $start = microtime(true);

                DB::transaction(function () use ($rawCode, $event) {
                    $t = Ticket::with(['user', 'payment'])
                        ->where('event_id', $event->id)
                        ->where('ticket_code', $rawCode)
                        ->lockForUpdate()
                        ->first();

                    if (!$t) {
                        return ['valid' => false, 'message' => 'Tiket tidak ditemukan.', 'code' => 404];
                    }

                    if ($t->status === 'used') {
                        return ['valid' => false, 'message' => 'Tiket sudah digunakan.', 'code' => 422];
                    }

                    if ($t->status !== 'active') {
                        return ['valid' => false, 'message' => "Status: {$t->status}.", 'code' => 422];
                    }

                    $t->update(['status' => 'used', 'used_at' => now()]);

                    return ['valid' => true, 'message' => 'Tiket valid.', 'code' => 200];
                });

                $ms = round((microtime(true) - $start) * 1000, 2);

                $results[] = ['no' => $no, 'ms' => $ms, 'status' => 'OK'];
                $this->line(sprintf('  [%2d] %8.2f ms  OK  (%s)', $no, $ms, $ticket->ticket_code));

            } catch (\Throwable $e) {
                $results[] = ['no' => $no, 'ms' => null, 'status' => 'ERROR: ' . $e->getMessage()];
                $this->error(sprintf('  [%2d] ERROR: %s', $no, $e->getMessage()));
            }
        }

        $this->printSummary($results, $iterations);
        $this->cleanup($benchmarkTickets, $this->dummyUserIds);

        return self::SUCCESS;
    }

    private function resolveEvent(): ?Event
    {
        if ($id = $this->option('event-id')) {
            return Event::where('id', $id)->where('status', 'active')->first();
        }

        return Event::where('status', 'active')
            ->whereHas('tickets', fn ($q) => $q->where('status', 'active'))
            ->first()
            ?? Event::where('status', 'active')->first();
    }

    private function prepareBenchmarkTickets(Event $event, int $count): array
    {
        $this->line("[Setup] Menyiapkan {$count} tiket benchmark (user dummy + status active)...");

        $tickets     = [];
        $this->dummyUserIds = [];

        for ($i = 0; $i < $count; $i++) {
            $dummyUser = \App\Models\User::create([
                'name'              => "BenchQR_{$i}_" . time(),
                'email'             => "benchqr_{$i}_" . uniqid() . '@bench.test',
                'password'          => bcrypt('bench'),
                'role'              => 'user',
                'email_verified_at' => now(),
            ]);
            $this->dummyUserIds[] = $dummyUser->id;

            do {
                $code = 'BM-' . strtoupper(Str::random(7));
            } while (Ticket::where('ticket_code', $code)->exists());

            $ticket = Ticket::create([
                'user_id'        => $dummyUser->id,
                'event_id'       => $event->id,
                'ticket_code'    => $code,
                'qr_code_path'   => null,
                'status'         => 'active',
                'custom_answers' => [],
            ]);

            $tickets[] = $ticket->fresh();
        }

        $this->line("[Setup] {$count} tiket siap. Mulai benchmark...\n");
        return $tickets;
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
            sort($times);
            $count  = count($times);
            $avg    = round(array_sum($times) / $count, 2);
            $min    = min($times);
            $max    = max($times);
            $mid    = (int) floor($count / 2);
            $median = $count % 2 === 0
                ? round(($times[$mid - 1] + $times[$mid]) / 2, 2)
                : round($times[$mid], 2);

            $this->line(sprintf('│  Percobaan sukses : %d / %d%s│', $count, $iterations, str_repeat(' ', 27 - strlen((string)$count) - strlen((string)$iterations))));
            $this->line(sprintf('│  Rata-rata (avg)  : %8.2f ms%s│', $avg, str_repeat(' ', 18)));
            $this->line(sprintf('│  Median           : %8.2f ms%s│', $median, str_repeat(' ', 18)));
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

    private function cleanup(array $tickets, array $userIds = []): void
    {
        if (empty($tickets)) return;
        $ids = array_map(fn ($t) => $t->id, $tickets);
        $deleted = Ticket::whereIn('id', $ids)->delete();

        $qrFiles = array_filter(array_map(fn ($t) => $t->qr_code_path, $tickets));
        foreach ($qrFiles as $path) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($path);
        }
        $this->line("\n[Cleanup] {$deleted} tiket benchmark dihapus dari database.");

        if (!empty($userIds)) {
            \App\Models\User::whereIn('id', $userIds)->delete();
            $this->line("[Cleanup] " . count($userIds) . " user dummy dihapus.");
        }
    }
}
