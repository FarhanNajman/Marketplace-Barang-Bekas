<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Barang yang dijual. Memakai soft delete (deleted_at) agar barang yang
     * dihapus tidak tampil lagi, tetapi riwayat chat dan pesan tetap ada.
     */
    public function up(): void
    {
        Schema::create('barang', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('kategori_id')->constrained('kategori')->restrictOnDelete();
            $table->foreignId('kecamatan_id')->constrained('wilayah')->restrictOnDelete();
            $table->string('nama_barang', 150);
            $table->text('deskripsi');
            $table->unsignedBigInteger('harga');
            $table->string('kondisi', 50);
            $table->enum('status', ['tersedia', 'terjual'])->default('tersedia');
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('barang');
    }
};
