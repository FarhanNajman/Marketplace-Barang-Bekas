<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\LupaPasswordController;
use App\Http\Controllers\BarangController;
use App\Http\Controllers\BerandaController;
use App\Http\Controllers\ProfilController;
use App\Http\Controllers\WishlistController;
use Illuminate\Support\Facades\Route;

Route::get('/', [BerandaController::class, 'index'])->name('beranda');
Route::view('/tentang', 'tentang')->name('tentang');

// Halaman Daftar Barang & Detail Barang
Route::get('/barang', [BarangController::class, 'index'])->name('barang.index');
Route::get('/barang/{barang}', [BarangController::class, 'show'])->name('barang.show');

// Fitur Wishlist (Hanya untuk pengguna yang sudah login)
Route::middleware('auth')->group(function () {
    Route::get('/wishlist', [WishlistController::class, 'index'])->name('wishlist.index');
    Route::post('/wishlist/toggle', [WishlistController::class, 'toggle'])->name('wishlist.toggle');
    Route::delete('/wishlist/{barang}', [WishlistController::class, 'destroy'])->name('wishlist.destroy');
});

// Fitur Profil & Listing Penjual (Dipisah Barang Tersedia dan Terjual)
Route::middleware('auth')->group(function () {
    Route::get('/profil/edit', [ProfilController::class, 'edit'])->name('profil.edit');
    Route::put('/profil', [ProfilController::class, 'update'])->name('profil.update');
});

Route::get('/penjual/{username}', [ProfilController::class, 'show'])->name('profil.show');

// Fitur Autentikasi (Registrasi, Verifikasi OTP Nomor HP, Login, & Logout)
Route::middleware('guest')->group(function () {
    Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.store');

    Route::get('/register/verifikasi-otp', [AuthController::class, 'showVerifyPhoneForm'])->name('register.verify.form');
    Route::post('/register/verifikasi-otp', [AuthController::class, 'verifyPhoneOtp'])->name('register.verify.submit');
    Route::post('/register/kirim-ulang-otp', [AuthController::class, 'resendPhoneOtp'])->name('register.verify.resend');

    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

// Fitur Lupa Password (melalui Nomor HP Terverifikasi [OTP] atau Email Terverifikasi [Link])
Route::prefix('lupa-password')->group(function () {
    Route::get('/', [LupaPasswordController::class, 'showRequestForm'])
        ->name('password.request');

    Route::post('/no-hp', [LupaPasswordController::class, 'sendPhoneOtp'])
        ->name('password.phone.send');

    Route::get('/verifikasi-otp', [LupaPasswordController::class, 'showVerifyOtpForm'])
        ->name('password.phone.verify.form');

    Route::post('/verifikasi-otp', [LupaPasswordController::class, 'resetWithPhoneOtp'])
        ->name('password.phone.reset');

    Route::post('/email', [LupaPasswordController::class, 'sendEmailResetLink'])
        ->name('password.email');
});

Route::get('/reset-password/{token}', [LupaPasswordController::class, 'showResetEmailForm'])
    ->name('password.reset');

Route::post('/reset-password', [LupaPasswordController::class, 'resetWithEmailToken'])
    ->name('password.update');

