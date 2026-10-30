<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Organizer;
use App\Models\Payment;
use App\Models\WithdrawalRequest;
use App\Services\FinanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrganizerFinanceController extends Controller
{
    public function financePage(Event $event, Request $request)
    {
        $organizer = Organizer::where('user_id', Auth::id())->firstOrFail();
        abort_if($event->organizer_id !== $organizer->id, 403, 'Akses ditolak.');
        abort_if(!in_array($event->status, ['active', 'completed'], true), 403, 'Event belum disetujui.');

        $finance = app(FinanceService::class);
        $financeData = $finance->financeData($event);

        $filter = $request->query('filter', '');
        $paymentsQuery = Payment::whereHas('ticket', fn($q) => $q->where('event_id', $event->id))
            ->with('ticket', 'user');

        if (in_array($filter, ['confirmed', 'pending', 'rejected'])) {
            $paymentsQuery->where('status', $filter);
        }

        $payments = $paymentsQuery->search(\App\Support\Search::term($request))->latest()->get();

        $pendingPaymentTotal = Payment::whereHas('ticket', fn($q) => $q->where('event_id', $event->id))
            ->where('status', 'pending')
            ->sum('amount');

        return view('organizer.finances.index', array_merge(
            compact('event', 'payments', 'filter', 'pendingPaymentTotal'),
            $financeData
        ));
    }

    public function exportFinance(Event $event)
    {
        $organizer = Organizer::where('user_id', Auth::id())->firstOrFail();
        abort_if($event->organizer_id !== $organizer->id, 403, 'Akses ditolak.');
        abort_if(!in_array($event->status, ['active', 'completed'], true), 403);

        $payments = Payment::whereHas('ticket', fn($q) => $q->where('event_id', $event->id))
            ->with('ticket', 'user')
            ->search(\App\Support\Search::term())
            ->when(in_array(request('filter'),['pending','confirmed','rejected'],true),fn ($q)=>$q->where('status',request('filter')))
            ->orderBy('created_at')
            ->get();

        $filename = 'keuangan-' . \Illuminate\Support\Str::slug($event->title) . '-' . now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($payments) {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF");

            fputcsv($handle, ['No', 'Tanggal', 'Kode Tiket', 'Peserta', 'Jumlah', 'Metode', 'Status']);

            foreach ($payments as $i => $pm) {
                fputcsv($handle, \App\Support\Csv::safeRow([
                    $i + 1,
                    $pm->created_at->format('d/m/Y H:i'),
                    $pm->ticket->ticket_code ?? '-',
                    $pm->ticket?->participant_name ?? $pm->user?->name ?? '-',
                    $pm->amount,
                    $pm->method,
                    $pm->status,
                ]));
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function withdrawPage(Event $event)
    {
        $organizer = Organizer::where('user_id', Auth::id())->firstOrFail();
        abort_if($event->organizer_id !== $organizer->id, 403, 'Akses ditolak.');
        abort_if(!in_array($event->status, ['active', 'completed'], true), 403, 'Event belum disetujui.');

        $finance = app(FinanceService::class);
        $financeData = $finance->financeData($event);
        $withdrawals = $finance->withdrawals($event);

        return view('organizer.finances.withdraw', array_merge(
            compact('event', 'withdrawals'),
            $financeData
        ));
    }

    public function withdraw(Event $event, Request $request)
    {
        $organizer = Organizer::where('user_id', Auth::id())->firstOrFail();
        abort_if($event->organizer_id !== $organizer->id, 403, 'Akses ditolak.');
        abort_if(!in_array($event->status, ['active', 'completed'], true), 403, 'Event belum disetujui.');

        $finance = app(FinanceService::class);
        $availableBalance = $finance->availableBalance($event);

        $request->validate([
            'amount' => 'required|integer|min:10000|max:' . $availableBalance,
            'bank_name' => 'required|string|max:100',
            'account_number' => 'required|string|max:50',
            'account_holder' => 'required|string|max:150',
        ], [
            'amount.max' => 'Jumlah penarikan melebihi saldo yang tersedia.',
            'amount.min' => 'Jumlah penarikan minimal Rp10.000.',
        ]);

        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($event, $request, $organizer) {
                $organizerLocked = Organizer::lockForUpdate()->findOrFail($organizer->id);
                Event::whereKey($event->id)->lockForUpdate()->firstOrFail();

                $grossRevenue = \App\Models\Payment::whereHas('ticket', fn($q) => $q->where('event_id', $event->id))
                    ->where('status', 'confirmed')
                    ->sum('amount');
                $withdrawn = WithdrawalRequest::where('event_id', $event->id)
                    ->where('status', 'approved')
                    ->sum('amount');
                $pendingWithdrawal = WithdrawalRequest::where('event_id', $event->id)
                    ->where('status', 'pending')
                    ->sum('amount');
                $availableBalance = $grossRevenue - ($withdrawn + $pendingWithdrawal);

                if ($request->amount > $availableBalance) {
                    throw new \Exception('Jumlah penarikan melebihi saldo yang tersedia.');
                }

                WithdrawalRequest::create([
                    'event_id' => $event->id,
                    'organizer_id' => $organizerLocked->id,
                    'amount' => $request->amount,
                    'bank_name' => $request->bank_name,
                    'account_number' => $request->account_number,
                    'account_holder' => $request->account_holder,
                    'status' => 'pending',
                ]);
            });
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['amount' => $e->getMessage()]);
        }

        return redirect()->route('organizer.finances.finance', $event)
            ->with('success', 'Pengajuan penarikan dana berhasil dikirim dan menunggu persetujuan Admin.');
    }
}
