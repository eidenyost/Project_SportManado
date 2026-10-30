<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'admin_notes',
        'notifiable_type',
        'notifiable_id',
        'is_read',
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    public function getDestinationAttribute(): ?string
    {
        $user=auth()->user();
        if (!$user) return null;
        $model=$this->notifiable;
        if ($model instanceof Ticket) return $user->isOrganizer()
            ? route('organizer.events.participants',$model->event_id) : route('tickets.show',$model);
        if ($model instanceof WithdrawalRequest) return $user->isAdmin()
            ? route('admin.dashboard',['tab'=>'withdrawal_requests']) : route('organizer.finances.finance',$model->event_id);
        if ($model instanceof Event) return $user->isAdmin()
            ? route('admin.dashboard',['tab'=>$model->status==='pending'?'pending_validation':'all_events'])
            : ($user->isOrganizer() ? route('organizer.events.index') : route('events.index'));
        if ($model instanceof Organizer) return $user->isAdmin()
            ? route('admin.dashboard',['tab'=>'organizer_verifications']) : route('organizer.setup');
        if ($model instanceof EventRegistrationRequest) return $user->isAdmin()
            ? route('admin.dashboard',['tab'=>'pending_validation']) : route('dashboard');
        return null;
    }

    public function notifiable()
    {
        return $this->morphTo();
    }
}
