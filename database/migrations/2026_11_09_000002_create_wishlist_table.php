<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel wishlist untuk menyimpan barang favorit pengguna.
     * Memiliki unique constraint agar satu pengguna tidak menyimpan barang yang sama dua kali.
     */
    public function up(): void
    {
        Schema::create('wishlist', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('barang_id')->constrained('barang')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'barang_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wishlist');
    }
};

