<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Ticket;

class MidtransPaymentPayload
{
    /** Compare decimal IDR strings without binary floating point or rounding. */
    public function amount(mixed $value): ?string
    {
        if (!is_string($value) && !is_int($value)) {
            return null;
        }
        if (!preg_match('/\A([0-9]+)(?:\.([0-9]{1,2}))?\z/D', (string) $value, $matches)) {
            return null;
        }

        return (ltrim($matches[1], '0') ?: '0') . '.' . str_pad($matches[2] ?? '', 2, '0');
    }

    /** Must run against freshly locked records, before any mutation or no-op. */
    public function validate(array $payload, Ticket $ticket, Payment $payment): void
    {
        foreach (['order_id', 'transaction_status', 'status_code', 'gross_amount'] as $field) {
            abort_unless(is_string($payload[$field] ?? null) && $payload[$field] !== '',
                422, 'Data pembayaran tidak valid.');
        }
        foreach (['transaction_id', 'payment_type', 'fraud_status'] as $field) {
            if (array_key_exists($field, $payload)) {
                abort_unless(is_string($payload[$field]) && $payload[$field] !== '',
                    422, 'Data pembayaran tidak valid.');
            }
        }

        abort_unless($payload['order_id'] === $ticket->ticket_code, 422, 'Identitas transaksi tidak sesuai.');
        $amount = $this->amount($payload['gross_amount']);
        abort_if($amount === null || $amount !== $this->amount($payment->amount), 422, 'Nominal pembayaran tidak sesuai.');
        if ($payment->midtrans_transaction_id !== null && $payment->midtrans_transaction_id !== '') {
            abort_unless(($payload['transaction_id'] ?? null) === $payment->midtrans_transaction_id,
                409, 'Identitas transaksi pembayaran telah terikat.');
        }
        if (in_array($payload['transaction_status'], ['settlement', 'capture'], true)) {
            abort_unless($payload['status_code'] === '200', 422, 'Status pembayaran belum berhasil.');
        }
    }

    /** Capture requires acceptance; settlement alone may omit fraud_status. */
    public function successful(array $payload): bool
    {
        if (($payload['transaction_status'] ?? null) === 'capture') {
            return ($payload['fraud_status'] ?? null) === 'accept';
        }

        return ($payload['transaction_status'] ?? null) === 'settlement'
            && (!array_key_exists('fraud_status', $payload) || $payload['fraud_status'] === 'accept');
    }
}
