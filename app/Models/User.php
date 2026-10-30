<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function hasProtectedHistory(): bool
    {
        return WithdrawalRequest::where('approved_by',$this->id)->exists()
            || $this->tickets()->exists()
            || Payment::where('user_id', $this->id)->exists()
            || $this->organizer()->exists()
            || EventRegistrationRequest::where('user_id', $this->id)->exists();
    }

    protected static function booted(): void
    {
        static::deleting(function (User $user) {
            if ($user->hasProtectedHistory()) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'password'=>'Akun memiliki riwayat tiket, pembayaran, atau Penyelenggara/EO sehingga tidak dapat dihapus. Riwayat kegiatan harus tetap tersimpan.',
                ]);
            }
        });
    }

    public function organizer()
    {
        return $this->hasOne(Organizer::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    public function isOrganizer(): bool
    {
        return $this->role === 'organizer';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
}
