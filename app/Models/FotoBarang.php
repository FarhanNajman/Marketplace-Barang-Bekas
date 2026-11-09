<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['barang_id', 'path_foto'])]
class FotoBarang extends Model
{
    protected $table = 'foto_barang';

    public function barang(): BelongsTo
    {
        return $this->belongsTo(Barang::class, 'barang_id');
    }

    public function urlFoto(): string
    {
        if (str_starts_with($this->path_foto, 'http://') || str_starts_with($this->path_foto, 'https://')) {
            return $this->path_foto;
        }

        if (str_starts_with($this->path_foto, 'images/')) {
            return asset($this->path_foto);
        }

        return asset('storage/' . ltrim($this->path_foto, '/'));
    }
}

