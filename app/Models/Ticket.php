<?php

namespace App\Models;

use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\Encoder;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    private static bool $generatingQrCode = false;

    protected $fillable = [
        'user_id',
        'event_id',
        'ticket_code',
        'qr_code_path',
        'status',
        'used_at',
        'custom_answers',
        'notes',
        'participant_name',
        'participant_email',
        'check_in_method',
        'cancellation_reason',
        'cancelled_at',
        'cancelled_by',
    ];

    protected $casts = [
        'used_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'cancelled_by' => 'integer',
        'custom_answers' => 'array',
    ];

    protected static function booted()
    {
        static::saved(function (self $ticket) {
            if ($ticket->status === 'active' && !$ticket->qr_code_path && !static::$generatingQrCode) {
                static::$generatingQrCode = true;
                try {
                    $ticket->generateQrCode();
                } finally {
                    static::$generatingQrCode = false;
                }
            }
        });
    }

    public function scopeSearch($query, string $search)
    {
        if ($search === '') return $query;
        $like = '%'.$search.'%';
        return $query->where(function ($q) use ($like) {
            $q->where('ticket_code','like',$like)->orWhere('participant_name','like',$like)->orWhere('participant_email','like',$like)
                ->orWhere(fn ($fallback) => $fallback->whereNull('participant_name')->whereHas('user', fn ($user) => $user->where('name','like',$like)))
                ->orWhere(fn ($fallback) => $fallback->whereNull('participant_email')->whereHas('user', fn ($user) => $user->where('email','like',$like)));
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getParticipantNameAttribute($value): string
    {
        return (string) ($value ?? $this->user?->name ?? '');
    }

    public function getParticipantEmailAttribute($value): string
    {
        return (string) ($value ?? $this->user?->email ?? '');
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function payment()
    {
        return $this->hasOne(Payment::class);
    }

    /**
     * Generate SVG QR code for the ticket.
     * Payload contains ticket_code and event_id only.
     */
    public function generateQrCode()
    {
        $payload = [
            'ticket_code' => $this->ticket_code,
            'event_id'    => $this->event_id,
        ];

        $qrContent = json_encode($payload);

        $renderer  = new \BaconQrCode\Renderer\ImageRenderer(
            new \BaconQrCode\Renderer\RendererStyle\RendererStyle(300, 8),
            new \BaconQrCode\Renderer\Image\SvgImageBackEnd()
        );
        $writer    = new \BaconQrCode\Writer($renderer);
        $svgString = $writer->writeString($qrContent, Encoder::DEFAULT_BYTE_MODE_ENCODING, ErrorCorrectionLevel::M());

        $qrFilename = "qr-codes/{$this->ticket_code}.svg";
        if (!\Illuminate\Support\Facades\Storage::disk('public')->put($qrFilename, $svgString)) {
            throw new \RuntimeException('QR Code gagal disimpan. Silakan coba lagi.');
        }

        // The outer save already emits the ticket status notification. Persisting
        // its QR path must not emit that same status transition a second time.
        $this->updateQuietly([
            'qr_code_path' => $qrFilename,
        ]);
    }
}
