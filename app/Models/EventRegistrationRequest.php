<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventRegistrationRequest extends Model
{
    protected $fillable = [
        'user_id',
        'organization_name',
        'phone',
        'event_title',
        'event_description',
        'event_location',
        'event_date',
        'registration_deadline',
        'max_participants',
        'ticket_price',
        'category',
        'proposal_file',
        'permit_file',
        'banner',
        'status',
        'admin_notes',
    ];

    protected $casts = [
        'event_date'            => 'datetime',
        'registration_deadline' => 'datetime',
    ];

    /**
     * The user (peserta) who submitted this request.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
