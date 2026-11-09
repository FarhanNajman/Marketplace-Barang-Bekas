<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Hash;

#[Fillable(['user_id', 'no_hp', 'kode_otp_hash', 'expires_at', 'used_at'])]
class OtpLupaPassword extends Model
{
    protected $table = 'otp_lupa_password';

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isUsed(): bool
    {
        return $this->used_at !== null;
    }

    public function isValidOtp(string $otp): bool
    {
        return ! $this->isUsed()
            && ! $this->isExpired()
            && Hash::check($otp, $this->kode_otp_hash);
    }
}

