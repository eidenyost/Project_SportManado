<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Payment;
use App\Models\Ticket;
use Illuminate\Support\Facades\DB;

class PaymentFinalizationService
{
    /**
     * Finalize every payment path using fresh rows and the same lock order:
     * event -> ticket -> payment. Never write status or used_at on a used ticket.
     * Provider calls must finish before entering this transaction.
     *
     * @return string The current ticket status after processing.
     */
    public function finalize(Ticket $ticket, string $transactionStatus, array $metadata = [], bool $requirePending = false, ?array $providerPayload = null): string
    {
        return DB::transaction(function () use ($ticket, $transactionStatus, $metadata, $requirePending, $providerPayload) {
            $event = Event::lockForUpdate()->findOrFail($ticket->event_id);
            $lockedTicket = Ticket::whereKey($ticket->id)->lockForUpdate()->firstOrFail();
            abort_if($lockedTicket->event_id !== $event->id, 409, 'Relasi kegiatan tiket telah berubah.');
            $payments = Payment::where('ticket_id', $lockedTicket->id)->orderBy('id')->lockForUpdate()->get();

            abort_if($payments->isEmpty(), 404, 'Data pembayaran tidak ditemukan.');
            abort_unless($payments->count() === 1, 422, 'Relasi pembayaran tidak unik.');
            $payment = $payments->first();
            abort_unless($payment->user_id === $lockedTicket->user_id, 422, 'Pemilik pembayaran tidak sesuai.');

            if ($providerPayload !== null) {
                $validator = new MidtransPaymentPayload;
                $validator->validate($providerPayload, $lockedTicket, $payment);
                $transactionStatus = $providerPayload['transaction_status'];
                if (in_array($transactionStatus, ['settlement', 'capture'], true) && !$validator->successful($providerPayload)) {
                    return $lockedTicket->status;
                }
                $metadata = [];
                if (array_key_exists('payment_type', $providerPayload)) {
                    $metadata['payment_type'] = $providerPayload['payment_type'];
                }
                if (array_key_exists('transaction_id', $providerPayload)) {
                    $metadata['midtrans_transaction_id'] = $providerPayload['transaction_id'];
                }
            }

            foreach (['payment_type', 'midtrans_transaction_id'] as $field) {
                if (array_key_exists($field, $metadata)) {
                    abort_unless(is_string($metadata[$field]) && $metadata[$field] !== '',
                        422, 'Metadata pembayaran tidak valid.');
                }
            }

            // The authenticated demo payment action keeps its pending-only contract.
            if ($requirePending) {
                abort_if($lockedTicket->status !== 'pending' || $payment->status !== 'pending', 422,
                    'Tiket atau pembayaran tidak dalam status menunggu.');
            }

            // A late successful provider notification for a reservation already
            // released must remain auditable, but must never revive the ticket.
            if ($lockedTicket->status === 'cancelled'
                && $providerPayload !== null
                && in_array($transactionStatus, ['settlement', 'capture'], true)
                && $payment->status !== 'confirmed') {
                $data = array_intersect_key($metadata, array_flip(['payment_type', 'midtrans_transaction_id']));
                $payment->update($data + [
                    'status' => 'confirmed',
                    'notes' => 'Pembayaran berhasil diterima setelah reservasi dilepaskan. Tiket tetap cancelled; diperlukan rekonsiliasi manual.',
                ]);

                return $lockedTicket->status;
            }

            // Terminal usage and confirmed payments are idempotent.
            if ($lockedTicket->status === 'used' || $lockedTicket->status === 'cancelled' || $payment->status === 'confirmed') {
                return $lockedTicket->status;
            }

            if (in_array($transactionStatus, ['settlement', 'capture'], true)) {
                if ($payment->status !== 'pending') {
                    return $lockedTicket->status;
                }

                $data = array_intersect_key($metadata, array_flip(['payment_type', 'midtrans_transaction_id']));
                $data['status'] = 'confirmed';

                // Pending already owns its reservation, so activation never
                // claims a second place or fails merely because the event is full.
                if ($lockedTicket->status === 'pending') {
                    $lockedTicket->update(['status' => 'active']);
                }

                $payment->update($data);
            } elseif (in_array($transactionStatus, ['expire', 'cancel', 'deny'], true)) {
                // Only an unpaid pending registration can be cancelled by a
                // failure notification. Late failures cannot reverse success.
                if ($lockedTicket->status === 'pending' && $payment->status === 'pending') {
                    $data = array_intersect_key($metadata, array_flip(['midtrans_transaction_id']));
                    $payment->update($data + ['status' => 'rejected']);
                    $lockedTicket->update(['status' => 'cancelled']);
                }
            }

            return $lockedTicket->status;
        });
    }
}
