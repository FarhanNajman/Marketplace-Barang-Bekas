<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nama_kategori'])]
class Kategori extends Model
{
    protected $table = 'kategori';

    public function barang(): HasMany
    {
        return $this->hasMany(Barang::class, 'kategori_id');
    }
}

