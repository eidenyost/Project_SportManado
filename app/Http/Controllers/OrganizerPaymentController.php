<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Services\PaymentFinalizationService;
use Illuminate\Http\Request;

class OrganizerPaymentController extends Controller
{
    /** Handle Midtrans notifications; simulator responses are local only. */
    public function handleNotification(Request $request, PaymentFinalizationService $finalizer)
    {
        $payload = $request->all();
        $demo = config('services.midtrans.demo') === true;

        if ($demo) {
            abort_unless(app()->environment(['local', 'testing']) && !config('services.midtrans.is_production'),
                404, 'Demo mode hanya tersedia di lokal atau pengujian.');
            if (!$request->user()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }
            abort_unless(is_string($payload['order_id'] ?? null) && $payload['order_id'] !== '',
                422, 'Identitas transaksi tidak valid.');
            $ticket = Ticket::where('ticket_code', $payload['order_id'])->firstOrFail();
            abort_unless($request->user()->id === $ticket->user_id, 403);
            $finalizer->finalize($ticket, 'settlement');

            return response('OK');
        }

        // Never coerce untrusted arrays/numbers while constructing the signature.
        foreach (['order_id', 'status_code', 'gross_amount', 'transaction_status'] as $field) {
            abort_unless(is_string($payload[$field] ?? null) && $payload[$field] !== '',
                422, 'Data pembayaran tidak valid.');
        }
        $serverKey = config('services.midtrans.server_key');
        $signatureKey = $payload['signature_key'] ?? null;
        if (!is_string($serverKey) || trim($serverKey) === '' || !is_string($signatureKey) || $signatureKey === '') {
            return response()->json(['message' => 'Invalid signature'], 403);
        }
        $localSignature = hash('sha512', $payload['order_id'] . $payload['status_code'] . $payload['gross_amount'] . $serverKey);
        if (!hash_equals($localSignature, $signatureKey)) {
            return response()->json(['message' => 'Invalid signature'], 403);
        }

        $ticket = Ticket::where('ticket_code', $payload['order_id'])->first();
        if (!$ticket) {
            return response()->json(['message' => 'Ticket not found'], 404);
        }
        $finalizer->finalize($ticket, $payload['transaction_status'], [], false, $payload);

        return response()->json(['message' => 'Notification processed successfully']);
    }
}
