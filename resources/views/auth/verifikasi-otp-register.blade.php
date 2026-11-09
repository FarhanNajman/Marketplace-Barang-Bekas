@extends('layouts.app')

@section('title', 'Verifikasi Nomor HP')
@section('description', 'Masukkan kode OTP yang dikirim ke nomor HP Anda untuk mengaktifkan akun.')

@section('content')
    <div class="auth-wrapper" style="max-width: 520px;">
        <div class="eyebrow">
            <span class="eyebrow-dot"></span>
            Aktivasi Akun Baru
        </div>

        <div class="auth-header">
            <h1>Verifikasi Nomor HP</h1>
            <p>Masukkan 6 digit kode OTP untuk memverifikasi nomor HP dan mengaktifkan akun Anda.</p>
        </div>

        @if (session('status'))
            <div class="alert alert-success" role="status" id="alert-status">
                {{ session('status') }}
            </div>
        @endif

        @if (app()->environment('local') && session('demo_otp'))
            <div class="alert alert-demo" role="note" id="alert-demo-otp">
                <strong>Mode Demo Lokal (APP_ENV=local):</strong> Kode OTP Registrasi Anda adalah
                <code id="demo-otp-code" style="font-size: 1.15rem; font-weight: 700; letter-spacing: 2px;">{{ session('demo_otp') }}</code>
            </div>
        @endif

        <section class="card">
            <form method="POST" action="{{ route('register.verify.submit') }}" id="form-verifikasi-otp-register">
                @csrf

                <div class="form-group">
                    <label for="no_hp">Nomor HP</label>
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
                        autofocus
                    >
                    @error('otp')
                        <span class="text-error" role="alert">{{ $message }}</span>
                    @enderror
                    <div class="helper-text">Kode OTP berlaku satu kali dan kedaluwarsa dalam 10 menit.</div>
                </div>

                <button type="submit" class="btn btn-full" id="btn-verifikasi-register">
                    Verifikasi &amp; Masuk
                </button>
            </form>

            <form method="POST" action="{{ route('register.verify.resend') }}" style="margin-top: 1.15rem; text-align: center;">
                @csrf
                <input type="hidden" name="no_hp" value="{{ old('no_hp', $no_hp) }}">
                <span style="font-size: 0.88rem; color: var(--muted);">Belum menerima atau kode kedaluwarsa?</span>
                <button
                    type="submit"
                    id="btn-kirim-ulang-otp"
                    style="background: transparent; border: none; color: var(--accent); font-size: 0.88rem; font-weight: 700; text-decoration: underline; margin-left: 0.25rem;"
                >
                    Kirim Ulang Kode OTP
                </button>
            </form>
        </section>
    </div>
@endsection

