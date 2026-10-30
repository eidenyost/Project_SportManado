<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Services\PaymentReservationService;
use Illuminate\Console\Command;

class ExpirePaymentReservations extends Command
{
    protected $signature = 'payments:expire-reservations';
    protected $description = 'Reconcile expired unpaid reservations with Midtrans before releasing quota';

    public function handle(PaymentReservationService $service): int
    {
        $failures = 0;
        Payment::where('status', 'pending')->whereHas('ticket', fn ($q) => $q->where('status', 'pending'))
            ->where(fn ($q) => $q->whereNull('reservation_expires_at')->orWhere('reservation_expires_at', '<=', now()))
            ->chunkById(100, function ($payments) use ($service, &$failures) {
                foreach ($payments as $payment) {
                    try { $service->reconcile($payment); }
                    catch (\Throwable $exception) {
                        $failures++;
                        report($exception);
                        $this->warn('Reservasi pembayaran #'.$payment->id.' tetap disimpan; rekonsiliasi akan dicoba kembali.');
                    }
                }
            });
        $this->info('Pemeriksaan reservasi selesai.');
        return $failures ? self::FAILURE : self::SUCCESS;
    }
}
