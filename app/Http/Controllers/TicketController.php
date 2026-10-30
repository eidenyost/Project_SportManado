<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Organizer;
use App\Models\Payment;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class TicketController extends Controller
{
    // -------------------------------------------------------------------------
    // USER: view own tickets
    // -------------------------------------------------------------------------

    /**
     * Show all tickets belonging to the authenticated user.
     */
    public function myTickets()
    {
        $tickets = Ticket::with(['event', 'payment'])
            ->where('user_id', Auth::id())
            ->where(function ($q) {
                $search = \App\Support\Search::term();
                if ($search === '') return;
                $q->search($search)->orWhereHas('event', fn ($event) => $event->where('title','like','%'.$search.'%'));
            })
            ->latest()
            ->paginate(15)->withQueryString();

        return view('tickets.my-tickets', compact('tickets'));
    }

    // -------------------------------------------------------------------------
    // USER: register for an event  (GET – show payment form)
    // -------------------------------------------------------------------------

    /**
     * Show the event registration form with custom fields.
     *
     * Guards (in order):
     *  1. Event must be active.
     *  2. Registration deadline must not have passed.
     *  3. User must not already have a ticket for this event.
     *  4. Event must not be full.
     */
    public function register(Event $event)
    {
        $user = Auth::user();

        if ($event->status !== 'active') {
            return redirect()->route('events.show', $event)
                ->with('error', 'Acara ini sudah tidak menerima pendaftaran.');
        }

        if (now()->gt($event->registration_deadline)) {
            return redirect()->route('events.show', $event)
                ->with('error', 'Batas waktu pendaftaran sudah berakhir.');
        }

        $existing = Ticket::where('user_id', $user->id)
            ->where('event_id', $event->id)
            ->first();

        if ($existing) {
            return redirect()->route('tickets.show', $existing)
                ->with('info', 'Anda sudah terdaftar di acara ini.');
        }

        if ($event->isFull()) {
            return redirect()->route('events.show', $event)
                ->with('error', 'Kuota peserta telah penuh.');
        }

        return view('tickets.payment', compact('event'));
    }

    // -------------------------------------------------------------------------
    // USER: submit payment proof  (POST)
    // -------------------------------------------------------------------------

    /**
     * Create a Ticket (and Payment for paid events) when a user registers.
     *
     * - Paid event  → Ticket (status=pending) + Payment (status=pending)
     * - Free event  → Ticket (status=active, QR auto-generated via booted())
     *
     * The ticket slot is reserved immediately so another user cannot take it.
     * A unique 10-character alphanumeric ticket_code is generated.
     */
    public function submitPayment(Request $request, Event $event)
    {
        $user = Auth::user();

        $customAnswers = $this->validatedCustomAnswers($request, $event);
        $notes = $request->validate(['notes' => ['nullable', 'string', 'max:2000']])['notes'] ?? null;

        $ticket = \Illuminate\Support\Facades\DB::transaction(function () use ($event, $user, $customAnswers, $notes) {
            $lockedEvent = Event::where('id', $event->id)->lockForUpdate()->firstOrFail();

            abort_if($lockedEvent->status !== 'active',              422, 'Acara tidak aktif.');
            abort_if(now()->gt($lockedEvent->registration_deadline), 422, 'Batas pendaftaran sudah berakhir.');
            abort_if($lockedEvent->isFull(),                         422, 'Kuota peserta telah penuh.');
            abort_if(
                Ticket::where('user_id', $user->id)->where('event_id', $lockedEvent->id)->exists(),
                422,
                'Anda sudah terdaftar di acara ini.'
            );

            do {
                $code = strtoupper(Str::random(10));
            } while (Ticket::where('ticket_code', $code)->exists());

            $isPaid = $lockedEvent->ticket_price > 0;

            $ticket = Ticket::create([
                'user_id'        => $user->id,
                'event_id'       => $lockedEvent->id,
                'ticket_code'    => $code,
                'qr_code_path'   => null,
                'status'         => $isPaid ? 'pending' : ($lockedEvent->auto_approve ? 'active' : 'pending'),
                'custom_answers' => $customAnswers,
                'notes'          => $notes,
            ]);

            if ($isPaid) {
                Payment::create([
                    'ticket_id' => $ticket->id,
                    'user_id'   => $user->id,
                    'amount'    => $lockedEvent->ticket_price,
                    'status'    => 'pending',
                    'reservation_expires_at' => now()->addMinutes(\App\Services\PaymentReservationService::MINUTES),
                    'method'    => 'ewallet',
                ]);
            }

            return $ticket;
        });

        $msg = $event->ticket_price > 0
            ? 'Pendaftaran berhasil! Silakan lakukan pembayaran.'
            : ($event->auto_approve
                ? 'Pendaftaran berhasil! Tiket Anda sudah aktif.'
                : 'Pendaftaran berhasil! Menunggu persetujuan penyelenggara.');

        return redirect()->route('tickets.show', $ticket)
            ->with('success', $msg);
    }

    private function validatedCustomAnswers(Request $request, Event $event): array
    {
        return app(\App\Services\EventAnswerValidator::class)->validate($event, $request->input('custom_answers', []));
    }

    /**
     * Show ticket detail page with inline Midtrans payment or demo payment.
     */
    public function show(Ticket $ticket)
    {
        abort_if($ticket->user_id !== auth()->id(), 403);

        // A database backup may outlive a missing public QR file. Repair active tickets only.
        if ($ticket->status === 'active' && (!$ticket->qr_code_path
            || !\Illuminate\Support\Facades\Storage::disk('public')->exists($ticket->qr_code_path))) {
            $ticket->generateQrCode();
        }

        $snapToken = null;
        $demoMode = config('services.midtrans.demo') === true
            && app()->environment(['local', 'testing'])
            && !config('services.midtrans.is_production');

        $payment = $ticket->payment;
        if ($payment && $ticket->status === 'pending' && $payment->status === 'pending') {
            $reservations = app(\App\Services\PaymentReservationService::class);
            if ($payment->reservation_expires_at?->isPast()) {
                return view('tickets.show', compact('ticket', 'snapToken', 'demoMode'))
                    ->with('paymentError', 'Batas waktu pembayaran telah berakhir. Status sedang menunggu rekonsiliasi; pembayaran baru tidak dapat dibuka.');
            }
            $amount = app(\App\Services\MidtransPaymentPayload::class)->amount($payment->amount);
            abort_if($amount === null || !str_ends_with($amount, '.00') || $amount === '0.00',
                422, 'Nominal pembayaran harus lebih dari nol dan dalam rupiah utuh. Silakan hubungi penyelenggara.');

            // Snap accepts integer IDR only; never truncate or round a payment amount.
            $wholeAmount = substr($amount, 0, -3);
            $maximum = (string) PHP_INT_MAX;
            abort_if(strlen($wholeAmount) > strlen($maximum)
                || (strlen($wholeAmount) === strlen($maximum) && strcmp($wholeAmount, $maximum) > 0),
                422, 'Nominal pembayaran tidak dapat diproses. Silakan hubungi penyelenggara.');

            $payment = $reservations->beginCheckout($ticket);
            if (!$payment) {
                return redirect()->route('tickets.show', $ticket);
            }

            if (app()->environment('testing')) {
                $snapToken = 'dummy-snap-token-123';
                Payment::whereKey($payment->id)->where('status', 'pending')->update(['snap_token' => $snapToken]);
                $demoMode = false;
            } elseif ($demoMode) {
                // Demo mode - payment handled by demoPay()
            } elseif (!$payment->snap_token) {
                \Midtrans\Config::$serverKey = config('services.midtrans.server_key');
                \Midtrans\Config::$isProduction = config('services.midtrans.is_production');
                \Midtrans\Config::$isSanitized = config('services.midtrans.is_sanitized');
                \Midtrans\Config::$is3ds = config('services.midtrans.is_3ds');

                $params = [
                    'expiry' => $reservations->snapExpiry($payment),
                    'transaction_details' => [
                        'order_id' => $ticket->ticket_code,
                        'gross_amount' => (int) $wholeAmount,
                    ],
                    'customer_details' => [
                        'first_name' => auth()->user()->name,
                        'email' => auth()->user()->email,
                    ],
                ];

                try {
                    $snapToken = \Midtrans\Snap::getSnapToken($params);
                    Payment::whereKey($payment->id)->where('status', 'pending')->update(['snap_token' => $snapToken]);
                } catch (\Exception $e) {
                    report($e);
                    return view('tickets.show', compact('ticket', 'snapToken', 'demoMode'))
                        ->with('paymentError', 'Pembayaran belum dapat dimuat. Silakan muat ulang halaman atau coba kembali beberapa saat lagi.');
                }
            } else {
                $snapToken = $payment->snap_token;
            }
        }

        return view('tickets.show', compact('ticket', 'snapToken', 'demoMode'));
    }

    /**
     * Check Midtrans payment status and activate ticket if settled.
     * Called by Snap onSuccess/onPending redirect so ticket activates
     * immediately after payment without waiting for a webhook.
     */
    public function checkPaymentStatus(Ticket $ticket)
    {
        abort_if($ticket->user_id !== auth()->id(), 403);

        $payment = $ticket->payment;

        if (!$payment || $ticket->status !== 'pending' || $payment->status !== 'pending') {
            return redirect()->route('tickets.show', $ticket);
        }

        try {
            \Midtrans\Config::$serverKey    = config('services.midtrans.server_key');
            \Midtrans\Config::$isProduction = config('services.midtrans.is_production');

            $status = \Midtrans\Transaction::status($ticket->ticket_code);

            $payload = (array) $status;
            if (is_string($payload['transaction_status'] ?? null)) {
                $ticketStatus = app(\App\Services\PaymentFinalizationService::class)->finalize(
                    $ticket,
                    $payload['transaction_status'],
                    array_filter([
                        'payment_type' => $payload['payment_type'] ?? null,
                        'midtrans_transaction_id' => $payload['transaction_id'] ?? null,
                    ], static fn ($value) => $value !== null),
                    false,
                    $payload
                );

                if ($ticketStatus === 'active') {
                    return redirect()->route('tickets.show', $ticket)
                        ->with('success', 'Pembayaran berhasil! Tiket Anda sudah aktif dan QR Code telah dibuat.');
                }
            }
        } catch (\Exception $e) {
            // Transaksi belum ada di Midtrans atau error jaringan - abaikan, tetap tampilkan tiket
        }

        return redirect()->route('tickets.show', $ticket);
    }

    /**
     * Demo payment - confirm payment and activate ticket without Midtrans.
     */
    public function demoPay(Ticket $ticket)
    {
        abort_if($ticket->user_id !== auth()->id(), 403);
        abort_if(config('services.midtrans.demo') !== true || !app()->environment(['local', 'testing'])
            || config('services.midtrans.is_production'), 404, 'Demo mode tidak aktif.');

        $ticketStatus = app(\App\Services\PaymentFinalizationService::class)->finalize(
            $ticket, 'settlement', [], true
        );

        return redirect()->route('tickets.show', $ticket)
            ->with('success', $ticketStatus === 'active'
                ? 'Simulasi pembayaran berhasil! Tiket Anda sudah aktif.'
                : 'Simulasi pembayaran diproses. Silakan periksa status tiket.');
    }

    // -------------------------------------------------------------------------
    // ORGANIZER: QR scanning
    // -------------------------------------------------------------------------

    /**
     * Show the QR scan page for a specific event (organizer only).
     */
    public function scanPage(Event $event)
    {
        $event = Event::findOrFail($event->id);
        $organizer = Organizer::where('user_id', Auth::id())->first();

        abort_if(!$organizer || $organizer->status !== 'approved'
            || $event->organizer_id !== $organizer->id, 403, 'Anda bukan pengelola acara ini.');
        abort_if(!$event->acceptsCheckIn(),
            422, 'Acara ini tidak menerima pemindaian tiket.');

        return view('organizer.scan.index', compact('event'));
    }

    /**
     * Validate a QR ticket code for entry (organizer only, JSON endpoint).
     *
     * Accepts POST body: { "ticket_code": "ABCD1234XY" }
     * Returns JSON with validation result and attendee info.
     *
     * Validation flow:
     *  a. Find ticket by ticket_code + event ownership
     *  b. If not found -> reject "Tiket tidak ditemukan"
     *  c. If status 'used' -> reject "Tiket sudah digunakan"
     *  d. If status not 'active' -> reject
     *  e. If valid & unused -> mark as 'used' + timestamp (lockForUpdate protects race)
     */
    public function validateQr(Request $request, Event $event)
    {
        $validated = $request->validate([
            'ticket_code' => ['required', 'string', 'max:255'],
        ]);

        try {
            $result = \DB::transaction(function () use ($validated, $event) {
                // Match payment finalization's lock order: event -> ticket -> payments.
                $lockedEvent = Event::whereKey($event->id)->lockForUpdate()->firstOrFail();
                $organizer = Organizer::where('user_id', Auth::id())->first();
                abort_if(!$organizer || $organizer->status !== 'approved'
                    || $lockedEvent->organizer_id !== $organizer->id,
                    403, 'Anda bukan pengelola acara ini.');

                // Completed or expired events cannot accept entry, even before the scheduler runs.
                if (!$lockedEvent->acceptsCheckIn()) {
                    return [
                        'valid' => false,
                        'message' => 'Acara ini tidak menerima pemindaian tiket.',
                        'code' => 422,
                    ];
                }

                $rawCode = strtoupper(preg_replace('/[^A-Za-z0-9\-]/', '', trim($validated['ticket_code'])));

                $ticket = Ticket::with('user')
                    ->where('event_id', $lockedEvent->id)
                    ->where('ticket_code', $rawCode)
                    ->lockForUpdate()
                    ->first();

                if (!$ticket) {
                    return [
                        'valid' => false,
                        'message' => 'Tiket tidak ditemukan untuk acara ini.',
                        'code' => 404
                    ];
                }

                if ($ticket->status === 'used') {
                    return [
                        'valid' => false,
                        'message' => 'Tiket ini sudah digunakan.',
                        'used_at' => $ticket->used_at?->format('d/m/Y H:i'),
                        'code' => 422
                    ];
                }

                if ($ticket->status !== 'active') {
                    return [
                        'valid' => false,
                        'message' => "Status tiket: '{$ticket->status}'. Hanya tiket aktif yang dapat di-scan.",
                        'code' => 422
                    ];
                }

                if ($lockedEvent->ticket_price > 0) {
                    // hasOne/first() cannot establish eligibility when duplicate payments exist.
                    $payments = Payment::where('ticket_id', $ticket->id)
                        ->orderBy('id')->lockForUpdate()->get();
                    $payment = $payments->first();

                    if ($payments->count() !== 1 || !$payment
                        || $payment->status !== 'confirmed'
                        || $payment->user_id !== $ticket->user_id) {
                        return [
                            'valid' => false,
                            'message' => 'Pembayaran tiket belum terverifikasi.',
                            'code' => 422,
                        ];
                    }
                }

                // Mark as used only after all fresh eligibility checks pass.
                $ticket->update([
                    'status'  => 'used',
                    'check_in_method' => 'qr',
                    'used_at' => now(),
                ]);

                return [
                    'valid'       => true,
                    'message'     => 'Tiket valid! Peserta diperbolehkan masuk.',
                    'ticket_code' => $ticket->ticket_code,
                    'attendee'    => $ticket->participant_name,
                    'email'       => $ticket->participant_email,
                    'used_at'     => now()->format('d/m/Y H:i'),
                    'code' => 200
                ];
            });

            return response()->json($result, $result['code']);

        } catch (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $e) {
            return response()->json([
                'valid' => false,
                'message' => 'Pemindaian tiket tidak diizinkan.',
                'code' => $e->getStatusCode(),
            ], $e->getStatusCode());
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'valid' => false,
                'message' => 'Acara tidak ditemukan.',
                'code' => 404,
            ], 404);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'valid' => false,
                'message' => 'Terjadi kesalahan sistem.',
                'code' => 500,
            ], 500);
        }
    }

}
