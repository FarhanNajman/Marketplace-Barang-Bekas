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
}

