<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Isi pesan dalam room chat beserta pengirimnya.
     * dibaca_pada kosong berarti pesan belum dibaca.
     */
    public function up(): void
    {
        Schema::create('pesan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_chat_id')->constrained('room_chat')->restrictOnDelete();
            $table->foreignId('pengirim_id')->constrained('users')->restrictOnDelete();
            $table->text('isi_pesan');
            $table->timestamp('dibaca_pada')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pesan');
    }
};
