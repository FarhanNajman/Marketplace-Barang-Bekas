@extends('layouts.app')

@section('title', $penjual->name . ' (@' . $penjual->username . ')')
@section('description', $penjual->bio ?: 'Profil dan daftar barang dari ' . $penjual->name . ' di BarangRantau.')

@section('content')
    @if (session('status'))
        <div class="alert alert-success" role="status" id="alert-status">
            {{ session('status') }}
        </div>
    @endif

    <div class="eyebrow">
        <span class="eyebrow-dot"></span>
        Profil &amp; Listing Penjual
    </div>

    <section class="profile-banner" aria-labelledby="profile-heading">
        <div class="profile-avatar">
            @if ($penjual->urlFotoProfil())
                <img src="{{ $penjual->urlFotoProfil() }}" alt="Foto profil {{ $penjual->name }}">
            @else
                <span>{{ $penjual->inisial() }}</span>
            @endif
        </div>

        <div>
            <span class="profile-username">{{ '@' . $penjual->username }}</span>
            <h1 id="profile-heading" class="profile-name">{{ $penjual->name }}</h1>
            <p class="profile-bio">
                {{ $penjual->bio ?: 'Belum ada bio yang ditambahkan.' }}
            </p>

            <div class="profile-stats">
                <div>
                    <span class="profile-stat-count">{{ $barangTersedia->count() }}</span>
                    Barang Tersedia
                </div>
                <div>
                    <span class="profile-stat-count">{{ $barangTerjual->count() }}</span>
                    Barang Terjual
                </div>
            </div>
        </div>

        <div>
            @auth
                @if (auth()->id() === $penjual->id)
                    <a href="{{ route('profil.edit') }}" class="button-outline" id="btn-edit-profil">
                        Edit Profil
                    </a>
                @endif
            @endauth
        </div>
    </section>

    <!-- Bagian 1: Barang Tersedia -->
    <section id="section-barang-tersedia" style="margin-bottom: 5rem;" aria-labelledby="heading-tersedia">
        <div class="listings-header">
            <div>
                <p class="section-tag">Masih Bisa Dibeli</p>
                <h2 id="heading-tersedia" class="section-heading">
                    Barang Tersedia ({{ $barangTersedia->count() }})
                </h2>
            </div>
            <p class="listings-note">
                Daftar barang milik {{ '@' . $penjual->username }} yang saat ini masih tersedia.
            </p>
        </div>

        @if ($barangTersedia->isNotEmpty())
            <div class="products-grid">
                @foreach ($barangTersedia as $barang)
                    <article class="product-card">
                        <div class="product-media">
                            <a href="{{ route('barang.show', $barang) }}" style="display: block; width: 100%; height: 100%;">
                                @if ($barang->urlFotoUtama())
                                    <img src="{{ $barang->urlFotoUtama() }}" alt="{{ $barang->nama_barang }}">
                                @else
                                    <div class="product-placeholder">
                                        <span>Belum ada foto</span>
                                    </div>
                                @endif
                            </a>
                            <a href="{{ route('barang.show', $barang) }}" class="product-hover-arrow" aria-label="Lihat detail {{ $barang->nama_barang }}">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M5 12h14M14 7l5 5-5 5" stroke="currentColor" stroke-width="1.8" />
                                </svg>
                            </a>
                        </div>

                        <div class="product-info">
                            <div class="product-top">
                                <h3 class="product-title">
                                    <a href="{{ route('barang.show', $barang) }}">{{ $barang->nama_barang }}</a>
                                </h3>
                                <p class="product-price">{{ $barang->hargaRupiah() }}</p>
                            </div>
                            <p class="product-category">
                                {{ $barang->kategori?->nama_kategori ?? 'Umum' }} &bull; {{ $barang->kondisi }}
                            </p>
                            <p class="product-location">
                                <svg width="16" height="16" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                                    <path d="M15.5 8.2c0 4.2-5.5 8.1-5.5 8.1S4.5 12.4 4.5 8.2a5.5 5.5 0 1 1 11 0Z" stroke="currentColor" stroke-width="1.4" />
                                    <circle cx="10" cy="8" r="1.7" stroke="currentColor" stroke-width="1.4" />
                                </svg>
                                {{ $barang->kecamatan?->labelLengkap() ?? '-' }}
                            </p>
                        </div>
                    </article>
                @endforeach
            </div>
        @else
            <div class="empty-listing-box">
                <p class="font-display" style="font-size: 1.35rem; font-weight: 600; color: var(--text);">
                    Belum ada barang tersedia.
                </p>
                <p style="margin-top: 0.35rem; font-size: 0.9rem;">
                    Penjual ini belum memiliki barang yang sedang dijual saat ini.
                </p>
            </div>
        @endif
    </section>

    <!-- Bagian 2: Barang Terjual -->
    <section id="section-barang-terjual" aria-labelledby="heading-terjual">
        <div class="listings-header">
            <div>
                <p class="section-tag" style="color: var(--muted);">Riwayat Penjualan</p>
                <h2 id="heading-terjual" class="section-heading">
                    Barang Terjual ({{ $barangTerjual->count() }})
                </h2>
            </div>
            <p class="listings-note">
                Barang milik {{ '@' . $penjual->username }} yang sudah laku terjual.
            </p>
        </div>

        @if ($barangTerjual->isNotEmpty())
            <div class="products-grid">
                @foreach ($barangTerjual as $barang)
                    <article class="product-card">
                        <div class="product-media">
                            @if ($barang->urlFotoUtama())
                                <img src="{{ $barang->urlFotoUtama() }}" alt="{{ $barang->nama_barang }}" class="sold">
                            @else
                                <div style="width: 100%; height: 100%; display: grid; place-items: center; color: var(--muted); font-size: 0.875rem;">
                                    Tanpa Foto
                                </div>
                            @endif
                            <span class="product-badge sold">Terjual</span>
                        </div>

                        <div class="product-info">
                            <div class="product-top">
                                <h3 class="product-title">
                                    <a href="{{ route('barang.show', $barang) }}">{{ $barang->nama_barang }}</a>
                                </h3>
                                <p class="product-price">{{ $barang->hargaRupiah() }}</p>
                            </div>
                            <p class="product-category">
                                {{ $barang->kategori?->nama_kategori ?? 'Umum' }} &bull; {{ $barang->kondisi }}
                            </p>
                            <p class="product-location">
                                <svg width="16" height="16" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                                    <path d="M15.5 8.2c0 4.2-5.5 8.1-5.5 8.1S4.5 12.4 4.5 8.2a5.5 5.5 0 1 1 11 0Z" stroke="currentColor" stroke-width="1.4" />
                                    <circle cx="10" cy="8" r="1.7" stroke="currentColor" stroke-width="1.4" />
                                </svg>
                                {{ $barang->kecamatan?->labelLengkap() ?? '-' }}
                            </p>
                        </div>
                    </article>
                @endforeach
            </div>
        @else
            <div class="empty-listing-box">
                <p class="font-display" style="font-size: 1.35rem; font-weight: 600; color: var(--text);">
                    Belum ada barang terjual.
                </p>
                <p style="margin-top: 0.35rem; font-size: 0.9rem;">
                    Riwayat barang yang sudah terjual akan tampil di bagian ini.
                </p>
            </div>
        @endif
    </section>
@endsection

