<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Satu tabel untuk provinsi, kabupaten/kota, dan kecamatan.
     * Tingkat wilayah dihubungkan melalui id_induk (provinsi: id_induk kosong).
     */
    public function up(): void
    {
        Schema::create('wilayah', function (Blueprint $table) {
            $table->id();
            $table->string('nama_wilayah', 100);
            $table->enum('jenis_wilayah', ['provinsi', 'kabupaten_kota', 'kecamatan']);
            $table->foreignId('id_induk')->nullable()
                ->constrained('wilayah')->restrictOnDelete();
            $table->timestamps();

            $table->index('jenis_wilayah');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wilayah');
    }
};
