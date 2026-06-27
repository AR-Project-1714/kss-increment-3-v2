<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $guarded = ['id'];

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

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Jabatan yang ditampilkan di header petugas — bukan sekadar nama peran.
     * Operasional: "Kepala Regu {Regu}" atau "Wakil Kepala Regu {Regu}"
     * (dibedakan dari prefix username karu./wakaru.). Pemeliharaan: "Kasi
     * Pemeliharaan". Safety: "Karu Safety".
     */
    public function jobTitle(): string
    {
        $roleName = Role::normalize($this->role->name ?? null);

        return match ($roleName) {
            Role::MAINTENANCE => 'Kasi Pemeliharaan',
            Role::SAFETY => 'Karu Safety',
            Role::OPERATIONAL => $this->operationalJobTitle(),
            default => Role::displayName($this->role->name ?? null),
        };
    }

    protected function operationalJobTitle(): string
    {
        $group = strtoupper(trim((string) $this->group));
        $isWakil = str_starts_with(strtolower((string) $this->username), 'wakaru');
        $label = $isWakil ? 'Wakil Kepala Regu' : 'Kepala Regu';

        return $group !== '' ? $label.' '.$group : $label;
    }
}
