<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'user_id',
    'kategori_id',
    'kecamatan_id',
    'nama_barang',
    'deskripsi',
    'harga',
    'kondisi',
    'status',
])]
class Barang extends Model
{
    use SoftDeletes;

    protected $table = 'barang';

    public function penjual(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(Kategori::class, 'kategori_id');
    }

    public function kecamatan(): BelongsTo
    {
        return $this->belongsTo(Wilayah::class, 'kecamatan_id');
    }

    public function foto(): HasMany
    {
        return $this->hasMany(FotoBarang::class, 'barang_id');
    }

    public function fotoUtama(): HasOne
    {
        return $this->hasOne(FotoBarang::class, 'barang_id')->oldestOfMany();
    }

    public function wishlist(): HasMany
    {
        return $this->hasMany(Wishlist::class, 'barang_id');
    }

    public function hargaRupiah(): string
    {
        return 'Rp' . number_format((int) $this->harga, 0, ',', '.');
    }

    public function urlFotoUtama(): ?string
    {
        if ($this->relationLoaded('fotoUtama') && $this->fotoUtama) {
            return $this->fotoUtama->urlFoto();
        }

        if ($this->relationLoaded('foto') && $this->foto->isNotEmpty()) {
            return $this->foto->first()->urlFoto();
        }

        return null;
    }
}

