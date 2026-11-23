<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['barang_id', 'pembeli_id', 'penjual_id'])]
class RoomChat extends Model
{
    protected $table = 'room_chat';

    public function barang(): BelongsTo
    {
        return $this->belongsTo(Barang::class, 'barang_id')->withTrashed();
    }

    public function pembeli(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pembeli_id');
    }

    public function penjual(): BelongsTo
    {
        return $this->belongsTo(User::class, 'penjual_id');
    }

    public function pesan(): HasMany
    {
        return $this->hasMany(Pesan::class, 'room_chat_id');
    }

    public function pesanTerakhir(): HasOne
    {
        return $this->hasOne(Pesan::class, 'room_chat_id')->latestOfMany();
    }

    /**
     * Periksa apakah pengguna dengan ID tertentu adalah peserta (pembeli atau penjual) di room ini.
     */
    public function adalahPeserta(int $userId): bool
    {
        return (int) $this->pembeli_id === $userId || (int) $this->penjual_id === $userId;
    }

    /**
     * Ambil objek User lawan bicara berdasarkan ID pengguna yang sedang login.
     */
    public function lawanBicara(int $userId): ?User
    {
        if ((int) $this->pembeli_id === $userId) {
            return $this->penjual;
        }

        if ((int) $this->penjual_id === $userId) {
            return $this->pembeli;
        }

        return null;
    }

    /**
     * Label peran lawan bicara ('Penjual' atau 'Pembeli').
     */
    public function peranLawanBicara(int $userId): string
    {
        return (int) $this->pembeli_id === $userId ? 'Penjual' : 'Pembeli';
    }
}
