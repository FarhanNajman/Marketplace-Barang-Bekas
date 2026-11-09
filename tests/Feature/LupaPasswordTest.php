<?php

namespace Tests\Feature;

use App\Models\OtpLupaPassword;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class LupaPasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_lupa_password_dapat_diakses(): void
    {
        $response = $this->get(route('password.request'));

        $response->assertStatus(200);
        $response->assertSee('Lupa Password');
        $response->assertSee('Melalui Nomor HP');
        $response->assertSee('Melalui Email');
    }

    public function test_pengguna_tanpa_email_dapat_meminta_otp_dan_reset_password_via_no_hp_terverifikasi(): void
    {
        $user = User::factory()->withoutEmail()->create([
            'no_hp' => '081234567890',
            'no_hp_verified_at' => now(),
        ]);

        // Set environment ke local untuk memverifikasi demo_otp aktif di APP_ENV=local
        $this->app['env'] = 'local';

        $response = $this->post(route('password.phone.send'), [
            'no_hp' => '081234567890',
        ]);

        $response->assertRedirect(route('password.phone.verify.form', ['no_hp' => '081234567890']));
        $response->assertSessionHas('demo_otp');

        $otpCode = session('demo_otp');
        $this->assertNotNull($otpCode);
        $this->assertSame(6, strlen($otpCode));

        // Reset password dengan kode OTP yang baru dikirim
        $resetResponse = $this->post(route('password.phone.reset'), [
            'no_hp' => '081234567890',
            'otp' => $otpCode,
            'password' => 'PasswordBaru123!',
            'password_confirmation' => 'PasswordBaru123!',
        ]);

        $resetResponse->assertRedirect(route('login'));
        $resetResponse->assertSessionHas('status');

        $this->assertTrue(Hash::check('PasswordBaru123!', $user->fresh()->password));
    }

    public function test_pengguna_dengan_email_belum_terverifikasi_tetap_dapat_pulihkan_akun_via_no_hp(): void
    {
        $user = User::factory()->unverified()->create([
            'email' => 'belumverifikasi@example.com',
            'no_hp' => '081299998888',
            'no_hp_verified_at' => now(),
        ]);

        $this->app['env'] = 'local';

        $response = $this->post(route('password.phone.send'), [
            'no_hp' => '081299998888',
        ]);

        $otpCode = session('demo_otp');

        $this->post(route('password.phone.reset'), [
            'no_hp' => '081299998888',
            'otp' => $otpCode,
            'password' => 'PasswordAman123',
            'password_confirmation' => 'PasswordAman123',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('PasswordAman123', $user->fresh()->password));
    }

    public function test_otp_hanya_berlaku_sekali_pakai(): void
    {
        $user = User::factory()->withoutEmail()->create([
            'no_hp' => '081122334455',
            'no_hp_verified_at' => now(),
        ]);

        $this->app['env'] = 'local';

        $this->post(route('password.phone.send'), ['no_hp' => '081122334455']);
        $otpCode = session('demo_otp');

        // Penggunaan pertama berhasil
        $this->post(route('password.phone.reset'), [
            'no_hp' => '081122334455',
            'otp' => $otpCode,
            'password' => 'PasswordPertama123',
            'password_confirmation' => 'PasswordPertama123',
        ])->assertRedirect(route('login'));

        // Penggunaan kedua dengan OTP yang sama harus ditolak
        $secondAttempt = $this->post(route('password.phone.reset'), [
            'no_hp' => '081122334455',
            'otp' => $otpCode,
            'password' => 'PasswordKedua123',
            'password_confirmation' => 'PasswordKedua123',
        ]);

        $secondAttempt->assertSessionHasErrors('otp');
        $this->assertTrue(Hash::check('PasswordPertama123', $user->fresh()->password));
    }

    public function test_otp_yang_sudah_kedaluwarsa_ditolak(): void
    {
        $user = User::factory()->withoutEmail()->create([
            'no_hp' => '081566778899',
            'no_hp_verified_at' => now(),
        ]);

        OtpLupaPassword::create([
            'user_id' => $user->id,
            'no_hp' => $user->no_hp,
            'kode_otp_hash' => Hash::make('654321'),
            'expires_at' => now()->subMinute(),
        ]);

        $response = $this->post(route('password.phone.reset'), [
            'no_hp' => '081566778899',
            'otp' => '654321',
            'password' => 'PasswordBaru123',
            'password_confirmation' => 'PasswordBaru123',
        ]);

        $response->assertSessionHasErrors('otp');
        $this->assertFalse(Hash::check('PasswordBaru123', $user->fresh()->password));
    }

    public function test_no_hp_belum_terverifikasi_tidak_dapat_meminta_otp(): void
    {
        User::factory()->unverifiedPhone()->create([
            'no_hp' => '081900001111',
        ]);

        $response = $this->post(route('password.phone.send'), [
            'no_hp' => '081900001111',
        ]);

        $response->assertSessionHasErrors('no_hp');
        $this->assertDatabaseCount('otp_lupa_password', 0);
    }

    public function test_mode_otp_demo_tidak_ditampilkan_di_production(): void
    {
        User::factory()->withoutEmail()->create([
            'no_hp' => '081355557777',
            'no_hp_verified_at' => now(),
        ]);

        $this->app['env'] = 'production';

        $response = $this->post(route('password.phone.send'), [
            'no_hp' => '081355557777',
        ]);

        $response->assertRedirect(route('password.phone.verify.form', ['no_hp' => '081355557777']));
        $response->assertSessionMissing('demo_otp');

        $verifyPage = $this->get(route('password.phone.verify.form', ['no_hp' => '081355557777']));
        $verifyPage->assertDontSee('Mode Demo Lokal');
    }

    public function test_email_terverifikasi_menerima_tautan_reset_password_dan_dapat_reset(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'terverifikasi@example.com',
            'email_verified_at' => now(),
        ]);

        $response = $this->post(route('password.email'), [
            'email' => 'terverifikasi@example.com',
        ]);

        $response->assertSessionHas('status');
        Notification::assertSentTo($user, ResetPassword::class);

        $token = Password::broker()->createToken($user);

        $resetResponse = $this->post(route('password.update'), [
            'token' => $token,
            'email' => 'terverifikasi@example.com',
            'password' => 'PasswordViaEmail123',
            'password_confirmation' => 'PasswordViaEmail123',
        ]);

        $resetResponse->assertRedirect(route('login'));
        $this->assertTrue(Hash::check('PasswordViaEmail123', $user->fresh()->password));
    }

    public function test_email_belum_terverifikasi_ditolak_saat_meminta_tautan_reset_password(): void
    {
        Notification::fake();

        $user = User::factory()->unverified()->create([
            'email' => 'belum@example.com',
        ]);

        $response = $this->post(route('password.email'), [
            'email' => 'belum@example.com',
        ]);

        $response->assertSessionHasErrors('email');
        Notification::assertNothingSent();
    }
}

