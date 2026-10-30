<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Payment;
use App\Models\WithdrawalRequest;

class FinanceService
{
    public function organizerFinanceData(\App\Models\Organizer $organizer): array
    {
        $events=Event::where('organizer_id',$organizer->id)->select('id');
        $grossRevenue=(int) Payment::whereHas('ticket',fn ($q)=>$q->whereIn('event_id',clone $events))->where('status','confirmed')->sum('amount');
        $withdrawn=(int) WithdrawalRequest::whereIn('event_id',clone $events)->where('status','approved')->sum('amount');
        $pendingWithdrawal=(int) WithdrawalRequest::whereIn('event_id',clone $events)->where('status','pending')->sum('amount');
        return compact('grossRevenue','withdrawn','pendingWithdrawal')+['availableBalance'=>$grossRevenue-$withdrawn-$pendingWithdrawal];
    }

    public function grossRevenue(Event $event): int
    {
        return Payment::whereHas('ticket', fn($q) => $q->where('event_id', $event->id))
            ->where('status', 'confirmed')
            ->sum('amount');
    }

    public function withdrawn(Event $event): int
    {
        return WithdrawalRequest::where('event_id', $event->id)
            ->where('status', 'approved')
            ->sum('amount');
    }

    public function pendingWithdrawal(Event $event): int
    {
        return WithdrawalRequest::where('event_id', $event->id)
            ->where('status', 'pending')
            ->sum('amount');
    }

    public function availableBalance(Event $event): int
    {
        return $this->grossRevenue($event) - ($this->withdrawn($event) + $this->pendingWithdrawal($event));
    }

    public function withdrawals(Event $event)
    {
        return WithdrawalRequest::where('event_id', $event->id)->latest()->get();
    }

    public function payments(Event $event)
    {
        return Payment::with('user')
            ->whereHas('ticket', fn($q) => $q->where('event_id', $event->id))
            ->latest()
            ->paginate(15);
    }

    public function financeData(Event $event): array
    {
        $grossRevenue = $this->grossRevenue($event);
        $withdrawn = $this->withdrawn($event);
        $pendingWithdrawal = $this->pendingWithdrawal($event);

        return [
            'grossRevenue'      => $grossRevenue,
            'withdrawn'         => $withdrawn,
            'pendingWithdrawal' => $pendingWithdrawal,
            'availableBalance'  => $grossRevenue - ($withdrawn + $pendingWithdrawal),
        ];
    }
}
