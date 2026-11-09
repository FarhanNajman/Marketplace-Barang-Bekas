@extends('layouts.app')

@section('title', 'Reset Password via Email')
@section('description', 'Atur ulang password akun Anda melalui tautan reset email.')

@section('content')
    <div class="auth-wrapper" style="max-width: 520px;">
        <div class="auth-header">
            <h1>Atur Ulang Password</h1>
            <p>Masukkan password baru untuk akun dengan alamat email terverifikasi Anda.</p>
        </div>

        <section class="card">
            <form method="POST" action="{{ route('password.update') }}" id="form-reset-password-email">
                @csrf

                <input type="hidden" name="token" value="{{ $token }}">

                <div class="form-group">
                    <label for="email">Alamat Email</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="form-control"
                        value="{{ old('email', $email) }}"
                        required
                    >
                    @error('email')
                        <span class="text-error" role="alert">{{ $message }}</span>
                    @enderror
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

                <button type="submit" class="btn btn-full" id="btn-reset-password-email">
                    Atur Ulang Password
                </button>
            </form>
        </section>
    </div>
@endsection

