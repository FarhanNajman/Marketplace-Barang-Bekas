@extends('layouts.app')

@section('title', 'Verifikasi OTP & Reset Password')
@section('description', 'Masukkan kode OTP yang dikirim ke nomor HP terverifikasi Anda untuk mengatur ulang password.')

@section('content')
    <div class="auth-wrapper" style="max-width: 520px;">
        <div class="auth-header">
            <h1>Verifikasi Kode OTP</h1>
            <p>Masukkan 6 digit kode OTP yang dikirim ke nomor HP terverifikasi Anda beserta password baru.</p>
        </div>

        @if (session('status'))
            <div class="alert alert-success" role="status" id="alert-status">
                {{ session('status') }}
            </div>
        @endif

        @if (app()->environment('local') && session('demo_otp'))
            <div class="alert alert-demo" role="note" id="alert-demo-otp">
                <strong>Mode Demo Lokal (APP_ENV=local):</strong> Kode OTP Anda adalah
                <code id="demo-otp-code" style="font-size: 1.15rem; font-weight: 700; letter-spacing: 2px;">{{ session('demo_otp') }}</code>
            </div>
        @endif

        <section class="card">
            <form method="POST" action="{{ route('password.phone.reset') }}" id="form-verifikasi-otp">
                @csrf

                <div class="form-group">
                    <label for="no_hp">Nomor HP Terverifikasi</label>
                    <input
                        type="text"
                        id="no_hp"
                        name="no_hp"
                        class="form-control"
                        value="{{ old('no_hp', $no_hp) }}"
                        required
                    >
                    @error('no_hp')
                        <span class="text-error" role="alert">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="otp">Kode OTP (6 Digit)</label>
                    <input
                        type="text"
                        id="otp"
                        name="otp"
                        class="form-control"
                        maxlength="6"
                        inputmode="numeric"
                        placeholder="123456"
                        required
                    >
                    @error('otp')
                        <span class="text-error" role="alert">{{ $message }}</span>
                    @enderror
                    <div class="helper-text">Kode OTP hanya berlaku satu kali dan memiliki masa kedaluwarsa 10 menit.</div>
                </div>

                <div class="form-group">
                    <label for="password">Password Baru</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-control"
                        placeholder="Minimal 8 karakter"
                        required
                    >
                    @error('password')
                        <span class="text-error" role="alert">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="password_confirmation">Konfirmasi Password Baru</label>
                    <input
                        type="password"
                        id="password_confirmation"
                        name="password_confirmation"
                        class="form-control"
                        placeholder="Ulangi password baru"
                        required
                    >
                </div>

                <button type="submit" class="btn btn-full" id="btn-reset-password-otp">
                    Simpan Password Baru
                </button>
            </form>
        </section>
    </div>
@endsection

