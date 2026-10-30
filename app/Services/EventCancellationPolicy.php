<?php

namespace App\Services;

use App\Models\{Event, Payment};
use Illuminate\Validation\ValidationException;

class EventCancellationPolicy
{
    /** Caller holds the event lock, also used by registration and payment finalization. */
    public function ensureAllowed(Event $event): void
    {
        $paidParticipants = $event->ticket_price > 0 && $event->allocatedTickets()->exists();
        $paidHistory = Payment::whereHas('ticket', fn ($query) => $query->where('event_id', $event->id))
            ->where('amount', '>', 0)->whereIn('status', ['pending', 'confirmed'])->exists();
        if ($paidParticipants || $paidHistory) {
            throw ValidationException::withMessages([
                'status' => 'Event dengan kepesertaan atau pembayaran berbayar belum dapat dibatalkan karena pengembalian dana belum tersedia.',
            ]);
        }
    }
}
