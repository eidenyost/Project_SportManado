<?php

namespace App\Http\Controllers;

use App\Models\{Event, Organizer, Payment, Ticket};
use App\Services\EventAnswerValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class OrganizerParticipantController extends Controller
{
    public function edit(Request $request, Event $event, Ticket $ticket)
    {
        $this->authorizeRegistration($request, $event, $ticket);
        $ticket->load(['user','payment']);
        $payments = Payment::where('ticket_id',$ticket->id)->get();
        $isPaidRegistration = $event->ticket_price > 0 || $payments->contains(fn ($payment) => $payment->amount > 0);
        $statusOptions = ['pending'=>'Pending', 'active'=>'Aktif', 'cancelled'=>'Dibatalkan / Ditolak'];
        if ($ticket->status === 'active') $statusOptions['used'] = 'Hadir (dicatat manual oleh Penyelenggara/EO)';
        if ($ticket->status === 'used') $statusOptions = ['used'=>'Hadir (sudah divalidasi)'];
        if ($isPaidRegistration && $ticket->status !== 'used') {
            unset($statusOptions['cancelled']);
            $validPayment = $payments->count() === 1 && $payments->first()->user_id === $ticket->user_id;
            if (!$validPayment || $payments->first()->status !== 'confirmed') unset($statusOptions['active'], $statusOptions['used']);
            if (!$validPayment || $payments->first()->status !== 'pending') unset($statusOptions['pending']);
            $statusOptions[$ticket->status] ??= match ($ticket->status) { 'active'=>'Aktif', 'pending'=>'Pending', default=>'Dibatalkan / Ditolak' };
        }
        return view('organizer.events.edit-participant', compact('event','ticket','statusOptions','isPaidRegistration'));
    }

    public function update(Request $request, Event $event, Ticket $ticket)
    {
        $this->authorizeRegistration($request, $event, $ticket);
        $data = $request->validate([
            'participant_name'=>['required','string','max:255'],
            'participant_email'=>['required','email','max:255'],
            'notes'=>['nullable','string','max:2000'],
            'status'=>['required', Rule::in(['pending','active','used','cancelled'])],
            'cancellation_reason'=>['nullable','string','max:1000'],
        ]);
        $reason = $data['cancellation_reason'] ?? null;
        unset($data['cancellation_reason']);

        DB::transaction(function () use ($request,$event,$ticket,$data,$reason) {
            // Same lock order as QR validation and payment finalization.
            $lockedEvent = Event::whereKey($event->id)->lockForUpdate()->firstOrFail();
            $lockedTicket = Ticket::whereKey($ticket->id)->lockForUpdate()->firstOrFail();
            $this->authorizeRegistration($request,$lockedEvent,$lockedTicket);
            $previous = $lockedTicket->status;
            $next = $data['status'];
            $reject = static fn (string $message) => throw ValidationException::withMessages(['status'=>$message]);

            if (($previous === 'used' || $lockedTicket->used_at !== null) && $next !== $previous) {
                $reject('Tiket yang sudah digunakan tidak dapat dibuka kembali. Riwayat waktu validasi tetap disimpan.');
            }
            if ($next === 'used' && !in_array($previous,['active','used'],true)) {
                $reject('Hanya tiket aktif yang dapat dicatat hadir.');
            }
            if ($next !== $previous && $lockedEvent->ticket_price > 0) {
                $payments = Payment::where('ticket_id',$lockedTicket->id)->orderBy('id')->lockForUpdate()->get();
                $payment = $payments->first();
                if (in_array($next,['active','used','pending'],true)) {
                    $required = $next === 'pending' ? 'pending' : 'confirmed';
                    if ($payments->count() !== 1 || !$payment || $payment->user_id !== $lockedTicket->user_id || $payment->status !== $required) {
                        $reject('Status pembayaran belum memenuhi syarat. Perubahan ini tidak dapat mengonfirmasi pembayaran.');
                    }
                }
            }
            if ($previous === 'cancelled' && $next !== 'cancelled' && $lockedEvent->isFull()) {
                $reject('Kuota kegiatan sudah penuh. Pendaftaran tidak dapat diaktifkan kembali.');
            }
            $answers = app(EventAnswerValidator::class)->validate($lockedEvent, $request->input('custom_answers', []));
            $labels = collect($lockedEvent->custom_fields ?? [])->pluck('label')->all();
            $legacyAnswers = array_diff_key($lockedTicket->custom_answers ?? [], array_flip($labels));
            $changes = $data + ['custom_answers'=>array_merge($legacyAnswers, $answers)];
            if ($next === 'cancelled' && $previous !== 'cancelled') {
                $changes = array_merge($changes, $this->cancellationChanges($lockedEvent, $lockedTicket, $request->user()->id, $reason));
            }
            if ($next === 'used' && $previous !== 'used') {
                $changes['used_at'] = now();
                $changes['check_in_method'] = 'manual';
            }
            $lockedTicket->update($changes);
        });

        return redirect()->route('organizer.events.participants',$event)->with('success','Data dan status pendaftaran peserta berhasil diperbarui.');
    }

    public function cancel(Request $request, Event $event, Ticket $ticket)
    {
        $this->authorizeRegistration($request,$event,$ticket);
        $data=$request->validate(['cancellation_reason'=>['required','string','max:1000']]);
        DB::transaction(function () use ($request,$event,$ticket,$data) {
            $lockedEvent=Event::whereKey($event->id)->lockForUpdate()->firstOrFail();
            $lockedTicket=Ticket::whereKey($ticket->id)->lockForUpdate()->firstOrFail();
            $this->authorizeRegistration($request,$lockedEvent,$lockedTicket);
            $changes=$this->cancellationChanges($lockedEvent,$lockedTicket,$request->user()->id,$data['cancellation_reason']);
            if ($changes) $lockedTicket->update($changes);
        });
        return redirect()->route('organizer.events.participants',$event)
            ->with('success','Kepesertaan dibatalkan. Tiket tidak dapat digunakan; riwayat pendaftaran dan pembayaran tetap tersimpan.');
    }

    private function cancellationChanges(Event $event, Ticket $ticket, int $actorId, ?string $reason): array
    {
        $payments=Payment::where('ticket_id',$ticket->id)->orderBy('id')->lockForUpdate()->get();
        if ($event->ticket_price > 0 || $payments->contains(fn ($payment) => $payment->amount > 0)) {
            throw ValidationException::withMessages(['cancellation_reason'=>'Kepesertaan berbayar tidak dapat dibatalkan karena pengembalian dana belum tersedia.']);
        }
        if ($ticket->status === 'used' || $ticket->used_at !== null) {
            throw ValidationException::withMessages(['cancellation_reason'=>'Peserta sudah tercatat hadir. Kepesertaan tidak dapat dibatalkan melalui menu ini.']);
        }
        if ($ticket->status === 'cancelled') return [];
        if (!is_string($reason) || trim($reason) === '') {
            throw ValidationException::withMessages(['cancellation_reason'=>'Alasan pembatalan wajib diisi.']);
        }
        return ['status'=>'cancelled','cancellation_reason'=>trim($reason),'cancelled_at'=>now(),'cancelled_by'=>$actorId];
    }

    private function authorizeRegistration(Request $request, Event $event, Ticket $ticket): void
    {
        $organizer = Organizer::where('user_id',$request->user()->id)->first();
        abort_unless($organizer && $organizer->status === 'approved' && $event->organizer_id === $organizer->id,403);
        abort_unless($ticket->event_id === $event->id,404);
        abort_unless($event->acceptsCheckIn(),403,'Kegiatan tidak aktif atau sudah selesai.');
    }
}
