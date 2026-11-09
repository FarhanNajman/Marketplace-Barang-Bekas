@extends('layouts.app')

@section('title', 'Masuk')
@section('description', 'Masuk ke akun Marketplace Barang Bekas menggunakan Username, Nomor HP, atau Email.')

@section('content')
    <div class="auth-wrapper" style="max-width: 500px;">
        <div class="eyebrow">
            <span class="eyebrow-dot"></span>
            Autentikasi Akun
        </div>
        <div class="auth-header">
            <h1>Masuk ke Akun</h1>
            <p>Gunakan Username, Nomor HP, atau Email Anda untuk melanjutkan.</p>
        </div>

        @if (session('status'))
            <div class="alert alert-success" role="status" id="alert-status">
                {{ session('status') }}
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-error" role="alert" id="alert-error">
                {{ session('error') }}
            </div>
        @endif

        <section class="card">
            <form method="POST" action="{{ route('login.store') }}" id="form-login">
                @csrf

                <div class="form-group">
                    <label for="identitas">Username / Nomor HP / Email</label>
                    <input
                        type="text"
                        id="identitas"
                        name="identitas"
                        class="form-control"
                        value="{{ old('identitas') }}"
                        placeholder="Masukkan username, no. HP, atau email"
                        required
                        autofocus
                    >
                    @error('identitas')
                        <span class="text-error" role="alert">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <div style="display: flex; justify-content: space-between; align-items: baseline;">
                        <label for="password">Password</label>
                        <a href="{{ route('password.request') }}" id="link-lupa-password" style="color: var(--accent); font-size: 0.85rem; text-decoration: none;">
                            Lupa Password?
                        </a>
                    </div>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-control"
                        placeholder="Masukkan password Anda"
                        required
                    >
                    @error('password')
                        <span class="text-error" role="alert">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group" style="display: flex; align-items: center; gap: 0.5rem;">
                    <input
                        type="checkbox"
                        id="remember"
                        name="remember"
                        value="1"
                        {{ old('remember') ? 'checked' : '' }}
                    >
                    <label for="remember" style="margin-bottom: 0; font-weight: 400; font-size: 0.9rem; color: var(--muted);">
                        Ingat saya di perangkat ini
                    </label>
                </div>

                <button type="submit" class="btn btn-full" id="btn-login">
                    Masuk
                </button>
            </form>

            <p style="text-align: center; margin-top: 1.25rem; margin-bottom: 0; font-size: 0.9rem;">
                Belum punya akun?
                <a href="{{ route('register') }}" id="link-ke-register" style="color: var(--accent); font-weight: 600; text-decoration: none;">
                    Daftar Sekarang
                </a>
            </p>
        </section>
    </div>
@endsection

