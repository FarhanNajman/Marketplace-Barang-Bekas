<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['room_chat_id', 'pengirim_id', 'isi_pesan', 'dibaca_pada'])]
class Pesan extends Model
{
    protected $table = 'pesan';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'dibaca_pada' => 'datetime',
        ];
    }

    public function roomChat(): BelongsTo
    {
        return $this->belongsTo(RoomChat::class, 'room_chat_id');
    }

    public function pengirim(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pengirim_id');
    }
}
