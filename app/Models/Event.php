<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Event extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'organizer_id',
        'title',
        'description',
        'location',
        'event_date',
        'event_end_at',
        'registration_deadline',
        'max_participants',
        'ticket_price',
        'category',
        'banner',
        'proposal',
        'permit',
        'ktp_image',
        'status',
        'custom_fields',
        'auto_approve',
    ];

    protected $casts = [
        'event_date' => 'datetime',
        'event_end_at' => 'datetime',
        'registration_deadline' => 'datetime',
        'custom_fields' => 'array',
        'auto_approve' => 'boolean',
        'ticket_price' => 'decimal:2',
    ];

    /** Legacy events remain open through the end of their event day, in WITA. */
    public function endsAt(): \Carbon\Carbon
    {
        return $this->event_end_at ?? $this->event_date->copy()->endOfDay();
    }

    public function acceptsCheckIn(): bool
    {
        return $this->status === 'active' && now()->lt($this->endsAt());
    }

    public function organizer()
    {
        return $this->belongsTo(Organizer::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    /** Tickets that currently consume one participant place. */
    public function allocatedTickets()
    {
        return $this->tickets()->whereIn('status', ['pending', 'active', 'used']);
    }

    public function allocatedTicketCount(): int
    {
        return $this->allocatedTickets()->count();
    }

    public function remainingCapacity(): int
    {
        return max(0, (int) $this->max_participants - $this->allocatedTicketCount());
    }

    public function isFull(): bool
    {
        return $this->remainingCapacity() === 0;
    }
}
