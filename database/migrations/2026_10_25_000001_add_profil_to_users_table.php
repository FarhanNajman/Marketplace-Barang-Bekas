<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menambah kolom profil ke tabel users.
     * Satu akun bisa menjadi pembeli sekaligus penjual (tidak ada kolom peran).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 50)->unique()->after('name');
            $table->string('foto_profil')->nullable()->after('password');
            $table->text('bio')->nullable()->after('foto_profil');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn(['username', 'foto_profil', 'bio']);
        });
    }
};
