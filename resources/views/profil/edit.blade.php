@extends('layouts.app')

@section('title', 'Edit Profil')
@section('description', 'Perbarui informasi profil, username, foto profil, dan bio Anda.')

@section('content')
    <div class="auth-wrapper" style="max-width: 600px;">
        <div class="eyebrow">
            <span class="eyebrow-dot"></span>
            Pengaturan Akun
        </div>

        <div class="auth-header">
            <h1>Edit Profil</h1>
            <p>Atur username, foto profil, dan bio yang akan ditampilkan pada halaman profil penjual Anda.</p>
        </div>

        <section class="card">
            <form method="POST" action="{{ route('profil.update') }}" enctype="multipart/form-data" id="form-edit-profil">
                @csrf
                @method('PUT')

                <div style="display: flex; align-items: center; gap: 1.5rem; margin-bottom: 1.75rem; padding-bottom: 1.5rem; border-bottom: 1px solid var(--border);">
                    <div class="profile-avatar" style="width: 84px; height: 84px; font-size: 1.75rem;">
                        @if ($user->urlFotoProfil())
                            <img src="{{ $user->urlFotoProfil() }}" alt="Foto profil {{ $user->name }}">
                        @else
                            <span>{{ $user->inisial() }}</span>
                        @endif
                    </div>

                    <div style="flex: 1;">
                        <label for="foto_profil" style="display: block; font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 0.45rem;">
                            Foto Profil
                        </label>
                        <input
                            type="file"
                            id="foto_profil"
                            name="foto_profil"
                            class="form-control"
                            accept="image/jpeg,image/png,image/webp"
                            style="padding: 0.55rem;"
                        >
                        @error('foto_profil')
                            <span class="text-error" role="alert">{{ $message }}</span>
                        @enderror
                        <div class="helper-text">Format JPG, PNG, atau WEBP (Maksimal 2MB).</div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="name">Nama Lengkap</label>
                    <input
                        type="text"
                        id="name"
                        name="name"
                        class="form-control"
                        value="{{ old('name', $user->name) }}"
                        required
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
                        value="{{ old('username', $user->username) }}"
                        required
                    >
                    @error('username')
                        <span class="text-error" role="alert">{{ $message }}</span>
                    @enderror
                    <div class="helper-text">Tautan profil publik Anda: <code>/penjual/{{ $user->username }}</code></div>
                </div>

                <div class="form-group">
                    <label for="bio">Bio Singkat</label>
                    <textarea
                        id="bio"
                        name="bio"
                        rows="4"
                        class="form-control"
                        placeholder="Ceritakan sedikit tentang diri Anda atau barang yang Anda jual..."
                    >{{ old('bio', $user->bio) }}</textarea>
                    @error('bio')
                        <span class="text-error" role="alert">{{ $message }}</span>
                    @enderror
                    <div class="helper-text">Maksimal 500 karakter.</div>
                </div>

                <div style="display: flex; gap: 0.75rem; margin-top: 1.75rem;">
                    <button type="submit" class="btn" id="btn-simpan-profil" style="flex: 1;">
                        Simpan Perubahan
                    </button>
                    <a href="{{ route('profil.show', ['username' => $user->username]) }}" class="button-outline">
                        Batal
                    </a>
                </div>
            </form>
        </section>
    </div>
@endsection

