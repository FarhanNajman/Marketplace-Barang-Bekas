<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_register_dan_login_dapat_diakses(): void
    {
        $this->get(route('register'))->assertStatus(200)->assertSee('Daftar Akun Baru');
        $this->get(route('login'))->assertStatus(200)->assertSee('Masuk ke Akun');
    }

    public function test_pengguna_dapat_registrasi_tanpa_email(): void
    {
        $this->app['env'] = 'local';

        $response = $this->post(route('register.store'), [
            'name' => 'Budi Santoso',
            'username' => 'budi_santoso',
            'no_hp' => '081234567890',
            'email' => '',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertRedirect(route('register.verify.form', ['no_hp' => '081234567890']));
        $response->assertSessionHas('demo_otp');
        $this->assertGuest();

        $otpCode = session('demo_otp');

        $verifyResponse = $this->post(route('register.verify.submit'), [
            'no_hp' => '081234567890',
            'otp' => $otpCode,
        ]);

        $verifyResponse->assertRedirect(route('beranda'));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'username' => 'budi_santoso',
            'no_hp' => '081234567890',
            'email' => null,
        ]);
        $this->assertNotNull(User::where('username', 'budi_santoso')->first()->no_hp_verified_at);
    }

    public function test_pengguna_dapat_registrasi_dengan_email_opsional(): void
    {
        $this->app['env'] = 'local';

        $response = $this->post(route('register.store'), [
            'name' => 'Siti Aminah',
            'username' => 'siti_aminah',
            'no_hp' => '081298765432',
            'email' => 'siti@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertRedirect(route('register.verify.form', ['no_hp' => '081298765432']));
        $otpCode = session('demo_otp');

        $this->post(route('register.verify.submit'), [
            'no_hp' => '081298765432',
            'otp' => $otpCode,
        ])->assertRedirect(route('beranda'));

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'username' => 'siti_aminah',
            'no_hp' => '081298765432',
            'email' => 'siti@example.com',
        ]);
    }

    public function test_pengguna_dapat_login_dengan_username_no_hp_atau_email(): void
    {
        $user = User::factory()->create([
            'username' => 'andi_wijaya',
            'no_hp' => '081311223344',
            'email' => 'andi@example.com',
            'password' => 'Rahasia123',
        ]);

        // Login menggunakan Username
        $this->post(route('login.store'), [
            'identitas' => 'andi_wijaya',
            'password' => 'Rahasia123',
        ])->assertRedirect(route('beranda'));
        $this->assertAuthenticatedAs($user);

        // Logout
        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->assertGuest();

        // Login menggunakan Nomor HP
        $this->post(route('login.store'), [
            'identitas' => '081311223344',
            'password' => 'Rahasia123',
        ])->assertRedirect(route('beranda'));
        $this->assertAuthenticatedAs($user);

        $this->post(route('logout'));

        // Login menggunakan Email
        $this->post(route('login.store'), [
            'identitas' => 'andi@example.com',
            'password' => 'Rahasia123',
        ])->assertRedirect(route('beranda'));
        $this->assertAuthenticatedAs($user);
    }
}

