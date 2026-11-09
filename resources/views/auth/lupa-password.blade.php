@extends('layouts.app')

@section('title', 'Lupa Password')
@section('description', 'Pulihkan akun Anda melalui Nomor HP terverifikasi (OTP) atau Email terverifikasi (tautan reset password).')

@section('content')
    <div class="auth-wrapper">
        <div class="eyebrow">
            <span class="eyebrow-dot"></span>
            Pemulihan Akun
        </div>
        <div class="auth-header">
            <h1>Lupa Password</h1>
            <p>Pilih metode pemulihan akun Anda melalui Nomor HP terverifikasi atau Email terverifikasi.</p>
        </div>

        @if (session('status'))
            <div class="alert alert-success" role="status" id="alert-status">
                {{ session('status') }}
            </div>
        @endif

        <div class="auth-grid">
            <!-- Metode 1: Nomor HP Terverifikasi (OTP) -->
            <section class="card" aria-labelledby="heading-phone">
                <h2 id="heading-phone">📱 Melalui Nomor HP</h2>
                <p>Kirim kode OTP sekali pakai ke nomor HP Anda yang sudah terverifikasi. Gunakan metode ini jika email belum ditambahkan atau belum diverifikasi.</p>

                <form method="POST" action="{{ route('password.phone.send') }}" id="form-lupa-password-hp">
                    @csrf
                    <div class="form-group">
                        <label for="no_hp">Nomor HP Terverifikasi</label>
                        <input
                            type="text"
                            id="no_hp"
                            name="no_hp"
                            class="form-control"
                            value="{{ old('no_hp') }}"
                            placeholder="Contoh: 081234567890"
                            required
                        >
                        @error('no_hp')
                            <span class="text-error" role="alert">{{ $message }}</span>
                        @enderror
                        <div class="helper-text">Kode OTP berlaku satu kali dan kedaluwarsa dalam 10 menit.</div>
                    </div>

                    <button type="submit" class="btn btn-full" id="btn-kirim-otp">
                        Kirim Kode OTP
                    </button>
                </form>
            </section>

            <!-- Metode 2: Email Terverifikasi (Tautan Reset) -->
            <section class="card" aria-labelledby="heading-email">
                <h2 id="heading-email">
                    ✉️ Melalui Email
                    <span class="badge-optional">Opsional</span>
                </h2>
                <p>Jika Anda telah menambahkan dan memverifikasi alamat email pada akun Anda, kami akan mengirimkan tautan reset password ke email tersebut.</p>

                <form method="POST" action="{{ route('password.email') }}" id="form-lupa-password-email">
                    @csrf
                    <div class="form-group">
                        <label for="email">Alamat Email Terverifikasi</label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            class="form-control"
                            value="{{ old('email') }}"
                            placeholder="nama@email.com"
                            required
                        >
                        @error('email')
                            <span class="text-error" role="alert">{{ $message }}</span>
                        @enderror
                        <div class="helper-text">Tautan hanya dikirim ke email yang sudah terverifikasi.</div>
                    </div>

                    <button type="submit" class="btn btn-full" id="btn-kirim-link-email">
                        Kirim Tautan Reset Password
                    </button>
                </form>
            </section>
        </div>
    </div>
@endsection

