<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menyesuaikan tabel users (email opsional, nomor HP & verifikasinya)
     * serta membuat tabel otp_lupa_password untuk pemulihan via nomor HP.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
            $table->string('no_hp', 20)->unique()->nullable()->after('email');
            $table->timestamp('no_hp_verified_at')->nullable()->after('email_verified_at');
        });

        Schema::create('otp_lupa_password', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('no_hp', 20)->index();
            $table->string('kode_otp_hash');
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('otp_lupa_password');

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['no_hp']);
            $table->dropColumn(['no_hp', 'no_hp_verified_at']);
            $table->string('email')->nullable(false)->change();
        });
    }
};

