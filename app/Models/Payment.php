<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'ticket_id',
        'user_id',
        'amount',
        'method',
        'status',
        'notes',
        'proof_image',
        'snap_token',
        'reservation_expires_at',
        'snap_requested_at',
        'midtrans_transaction_id',
        'payment_type',
    ];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'reservation_expires_at'=>'datetime', 'snap_requested_at'=>'datetime'];
    }

    public function scopeSearch($query, string $search)
    {
        if ($search === '') return $query;
        return $query->where(fn ($q) => $q->whereHas('ticket', fn ($ticket) => $ticket->search($search))
            ->orWhereHas('ticket.event', fn ($event) => $event->where('title','like','%'.$search.'%')));
    }

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
