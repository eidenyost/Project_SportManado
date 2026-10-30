<?php

namespace App\Services;

use App\Models\{Event, Payment, Ticket};
use Illuminate\Support\Facades\DB;

class PaymentReservationService
{
    public const MINUTES = 30;

    public function __construct(private ReservationGateway $gateway, private PaymentFinalizationService $finalizer) {}

    /** Claim token creation before I/O so expiry cannot release a request in flight. */
    public function beginCheckout(Ticket $ticket): ?Payment
    {
        return DB::transaction(function () use ($ticket) {
            Event::whereKey($ticket->event_id)->lockForUpdate()->firstOrFail();
            $lockedTicket = Ticket::whereKey($ticket->id)->lockForUpdate()->firstOrFail();
            $payment = Payment::where('ticket_id', $ticket->id)->lockForUpdate()->firstOrFail();
            if ($lockedTicket->status !== 'pending' || $payment->status !== 'pending'
                || $payment->reservation_expires_at?->isPast()) return null;
            if (!$payment->snap_token) {
                $payment->update([
                    'reservation_expires_at'=>$payment->reservation_expires_at ?? now()->addMinutes(self::MINUTES),
                    'snap_requested_at'=>$payment->snap_requested_at ?? now(),
                ]);
            }
            return $payment;
        });
    }

    public function snapExpiry(Payment $payment): array
    {
        return [
            'start_time'=>$payment->reservation_expires_at->copy()->subMinutes(self::MINUTES)->timezone('Asia/Jakarta')->format('Y-m-d H:i:s O'),
            'unit'=>'minutes', 'duration'=>self::MINUTES,
        ];
    }

    public function reconcile(Payment $payment): void
    {
        $payment->refresh();
        $ticket = $payment->ticket;
        if (!$ticket || $payment->status !== 'pending' || $ticket->status !== 'pending') return;

        // Existing reservations are not silently given a new provider expiry contract.
        if (!$payment->reservation_expires_at) {
            $payload = $this->gateway->status($ticket->ticket_code);
            $this->applyProvider($ticket, $payload);
            // Legacy Snap tokens have at most a seven-day validity. Never infer
            // that an old token is expired from a Core API 404 alone.
            if ($payload === null && $payment->updated_at->copy()->addDays(7)->isPast()) {
                $this->release($payment, true, true);
            }
            return;
        }
        if ($payment->reservation_expires_at->isFuture()) return;

        if (!$payment->snap_token && !$payment->midtrans_transaction_id && !$payment->snap_requested_at) {
            $this->release($payment);
            return;
        }

        // Provider I/O outside locks. Preserve the reservation on every ambiguous result.
        $payload = $this->gateway->status($ticket->ticket_code);
        if (($payload['transaction_status'] ?? null) === 'pending') {
            $this->gateway->expire($ticket->ticket_code);
            $payload = $this->gateway->status($ticket->ticket_code);
            if ($payload === null) return;
        }
        if ($payload !== null) {
            $this->applyProvider($ticket, $payload);
        } elseif ($payment->snap_requested_at && !$payment->midtrans_transaction_id
            && $payment->reservation_expires_at->copy()->addMinutes(15)->isPast()) {
            // Only new Snap requests have the matching absolute expiry. Legacy tokens do not.
            $this->release($payment, true);
        }
    }

    private function applyProvider(Ticket $ticket, ?array $payload): void
    {
        if ($payload !== null) $this->finalizer->finalize($ticket, $payload['transaction_status'], [], false, $payload);
    }

    private function release(Payment $payment, bool $providerAbsent = false, bool $legacyExpired = false): void
    {
        DB::transaction(function () use ($payment, $providerAbsent, $legacyExpired) {
            $ticketId = $payment->ticket_id;
            $eventId = Ticket::whereKey($ticketId)->value('event_id');
            Event::whereKey($eventId)->lockForUpdate()->firstOrFail();
            $ticket = Ticket::whereKey($ticketId)->lockForUpdate()->firstOrFail();
            $payments = Payment::where('ticket_id', $ticketId)->orderBy('id')->lockForUpdate()->get();
            if ($payments->count() !== 1) return;
            $locked = $payments->first();
            if ($locked->user_id !== $ticket->user_id) return;
            $pastDeadline = $locked->reservation_expires_at?->isPast()
                || ($legacyExpired && !$locked->reservation_expires_at && $locked->updated_at->copy()->addDays(7)->isPast());
            if ($ticket->status !== 'pending' || $locked->status !== 'pending'
                || !$pastDeadline
                || $locked->midtrans_transaction_id) return;
            if (!$providerAbsent && ($locked->snap_requested_at || $locked->snap_token)) return;
            $locked->update(['status'=>'rejected','notes'=>'Reservasi pembayaran kedaluwarsa.']);
            $ticket->update(['status'=>'cancelled','cancelled_at'=>now(),
                'cancellation_reason'=>'Batas waktu reservasi pembayaran telah berakhir.']);
        });
    }
}
