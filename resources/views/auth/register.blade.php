@extends('layouts.app')

@section('title', 'Daftar Akun')
@section('description', 'Buat akun baru di Marketplace Barang Bekas untuk mulai jual beli.')

@section('content')
    <div class="auth-wrapper" style="max-width: 560px;">
        <div class="eyebrow">
            <span class="eyebrow-dot"></span>
            Mulai Jual &amp; Beli
        </div>
        <div class="auth-header">
            <h1>Daftar Akun Baru</h1>
            <p>Satu akun dapat digunakan untuk membeli sekaligus menjual barang bekas berkualitas.</p>
        </div>

        <section class="card">
            <form method="POST" action="{{ route('register.store') }}" id="form-register">
                @csrf

                <div class="form-group">
                    <label for="name">Nama Lengkap</label>
                    <input
                        type="text"
                        id="name"
                        name="name"
                        class="form-control"
                        value="{{ old('name') }}"
                        placeholder="Masukkan nama lengkap Anda"
                        required
                        autofocus
                    >
                    @error('name')
                        <span class="text-error" role="alert">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="username">Username</label>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        class="form-control"
                        value="{{ old('username') }}"
                        placeholder="Contoh: budi_santoso"
                        required
                    >
                    @error('username')
                        <span class="text-error" role="alert">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="no_hp">Nomor HP</label>
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
                    <div class="helper-text">Digunakan untuk masuk dan pemulihan password via kode OTP.</div>
                </div>

                <div class="form-group">
                    <label for="email">
                        Alamat Email
                        <span class="badge-optional">Opsional</span>
                    </label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="form-control"
                        value="{{ old('email') }}"
                        placeholder="nama@email.com (boleh dikosongkan)"
                    >
                    @error('email')
                        <span class="text-error" role="alert">{{ $message }}</span>
                    @enderror
                    <div class="helper-text">Opsional. Jika diisi, Anda juga dapat memulihkan password melalui tautan email.</div>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
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
                    <label for="password_confirmation">Konfirmasi Password</label>
                    <input
                        type="password"
                        id="password_confirmation"
                        name="password_confirmation"
                        class="form-control"
                        placeholder="Ulangi password Anda"
                        required
                    >
                </div>

                <button type="submit" class="btn btn-full" id="btn-register">
                    Daftar Sekarang
                </button>
            </form>

            <p style="text-align: center; margin-top: 1.25rem; margin-bottom: 0; font-size: 0.9rem;">
                Sudah punya akun?
                <a href="{{ route('login') }}" id="link-ke-login" style="color: var(--accent); font-weight: 600; text-decoration: none;">
                    Masuk di sini
                </a>
            </p>
        </section>
    </div>
@endsection

