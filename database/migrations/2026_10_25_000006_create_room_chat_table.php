<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Percakapan antara pembeli dan penjual untuk barang tertentu.
     */
    public function up(): void
    {
        Schema::create('room_chat', function (Blueprint $table) {
            $table->id();
            $table->foreignId('barang_id')->constrained('barang')->restrictOnDelete();
            $table->foreignId('pembeli_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('penjual_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            // Satu pembeli hanya punya satu room untuk satu barang.
            $table->unique(['barang_id', 'pembeli_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_chat');
    }
};
