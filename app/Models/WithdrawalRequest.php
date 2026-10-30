<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WithdrawalRequest extends Model
{
    protected $fillable = [
        'event_id',
        'organizer_id',
        'amount',
        'bank_name',
        'account_number',
        'account_holder',
        'status',
        'iris_reference_no',
        'payout_requested_at',
        'admin_notes',
        'transfer_proof_path','transfer_reference','transferred_at','approved_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transferred_at'=>'datetime', 'approved_by'=>'integer',
        'payout_requested_at'=>'datetime',
    ];

    public function getDisplayNotesAttribute(): ?string
    {
        $notes=$this->admin_notes;
        if ($this->status==='approved' && !$this->transfer_proof_path
            && (str_starts_with((string)$notes,'Simulasi persetujuan Sandbox.')
                || str_starts_with((string)$notes,'Disetujui Otomatis oleh Simulator Midtrans Iris.'))) return null;
        return $notes;
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function organizer()
    {
        return $this->belongsTo(Organizer::class);
    }
}
