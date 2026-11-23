<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nama_wilayah', 'jenis_wilayah', 'id_induk'])]
class Wilayah extends Model
{
    protected $table = 'wilayah';

    public function induk(): BelongsTo
    {
        return $this->belongsTo(self::class, 'id_induk');
    }

    public function subWilayah(): HasMany
    {
        return $this->hasMany(self::class, 'id_induk');
    }

    public function labelLengkap(): string
    {
        if ($this->jenis_wilayah === 'kecamatan' && $this->induk) {
            return 'Kec. ' . $this->nama_wilayah . ', ' . $this->induk->nama_wilayah;
        }

        return $this->nama_wilayah;
    }

    public function namaKabupatenKota(): ?string
    {
        if ($this->jenis_wilayah === 'kecamatan') {
            return $this->induk?->nama_wilayah;
        }

        if ($this->jenis_wilayah === 'kabupaten_kota') {
            return $this->nama_wilayah;
        }

        return null;
    }

    public function namaProvinsi(): ?string
    {
        if ($this->jenis_wilayah === 'kecamatan') {
            return $this->induk?->induk?->nama_wilayah;
        }

        if ($this->jenis_wilayah === 'kabupaten_kota') {
            return $this->induk?->nama_wilayah;
        }

        return $this->nama_wilayah;
    }

    public function labelHierarkiLengkap(): string
    {
        if ($this->jenis_wilayah === 'kecamatan') {
            $bagian = ['Kec. ' . $this->nama_wilayah];

            if ($this->namaKabupatenKota()) {
                $bagian[] = $this->namaKabupatenKota();
            }

            if ($this->namaProvinsi()) {
                $bagian[] = $this->namaProvinsi();
            }

            return implode(', ', $bagian);
        }

        if ($this->jenis_wilayah === 'kabupaten_kota') {
            $bagian = [$this->nama_wilayah];

            if ($this->namaProvinsi()) {
                $bagian[] = $this->namaProvinsi();
            }

            return implode(', ', $bagian);
        }

        return $this->nama_wilayah;
    }

    /**
     * Kata kunci pencarian wilayah tingkat kecamatan untuk Google Maps & pencarian batas peta
     * (tanpa alamat rumah atau koordinat penjual).
     */
    public function kueriPencarianPeta(): string
    {
        if ($this->jenis_wilayah === 'kecamatan') {
            $bagian = ['Kecamatan ' . $this->nama_wilayah];

            if ($this->namaKabupatenKota()) {
                $bagian[] = $this->namaKabupatenKota();
            }

            if ($this->namaProvinsi()) {
                $bagian[] = $this->namaProvinsi();
            }

            return implode(', ', $bagian);
        }

        return $this->labelHierarkiLengkap();
    }

    /**
     * URL Google Maps resmi dengan URL encoding yang benar berdasarkan nama kecamatan,
     * kabupaten/kota, dan provinsi.
     */
    public function urlGoogleMaps(): string
    {
        return 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($this->kueriPencarianPeta());
    }
}

