<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'username', 'email', 'no_hp', 'email_verified_at', 'no_hp_verified_at', 'password', 'foto_profil', 'bio'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'no_hp_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function barang(): HasMany
    {
        return $this->hasMany(Barang::class, 'user_id');
    }

    public function wishlist(): HasMany
    {
        return $this->hasMany(Wishlist::class, 'user_id');
    }

    public function urlFotoProfil(): ?string
    {
        if (empty($this->foto_profil)) {
            return null;
        }

        if (str_starts_with($this->foto_profil, 'http://') || str_starts_with($this->foto_profil, 'https://')) {
            return $this->foto_profil;
        }

        if (str_starts_with($this->foto_profil, 'images/')) {
            return asset($this->foto_profil);
        }

        return asset('storage/' . ltrim($this->foto_profil, '/'));
    }

    public function inisial(): string
    {
        $parts = preg_split('/\s+/', trim((string) $this->name)) ?: [];
        $initials = '';
        foreach (array_slice($parts, 0, 2) as $part) {
            $initials .= mb_strtoupper(mb_substr($part, 0, 1));
        }

        return $initials !== '' ? $initials : 'U';
    }

    public function hasVerifiedEmail(): bool
    {
        return ! empty($this->email) && $this->email_verified_at !== null;
    }

    public function hasVerifiedPhone(): bool
    {
        return ! empty($this->no_hp) && $this->no_hp_verified_at !== null;
    }
}
