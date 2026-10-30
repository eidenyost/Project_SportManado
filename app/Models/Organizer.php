<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Organizer extends Model
{
    protected $fillable = [
        'user_id',
        'organization_name',
        'phone',
        'description',
        'logo',
        'status',
        'admin_notes',
        'ktp_path',
        'selfie_path',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function events()
    {
        return $this->hasMany(Event::class);
    }
}
