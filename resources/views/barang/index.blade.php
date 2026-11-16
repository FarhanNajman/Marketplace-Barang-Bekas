@extends('layouts.app')

@section('title', 'Jelajahi Barang')
@section('description', 'Jelajahi katalog barang bekas layak pakai di BARANTAU. Cari barang, filter kategori dan kecamatan, atau jual barangmu.')

@section('fullbleed')
    @php
        $statusFilter = $statusFilter ?? '';
        $hanyaBaruDitambahkan = $hanyaBaruDitambahkan ?? false;
        $adaFilterAktif = ($kataKunci !== '') || !empty($kategoriId) || !empty($kecamatanId) || ($statusFilter !== '') || $hanyaBaruDitambahkan;

        $baseQuery = array_filter([
            'q' => $kataKunci !== '' ? $kataKunci : null,
            'kategori_id' => $kategoriId ?: null,
            'kecamatan_id' => $kecamatanId ?: null,
            'status' => $statusFilter !== '' ? $statusFilter : null,
            'baru' => $hanyaBaruDitambahkan ? '1' : null,
        ], fn ($val) => $val !== null && $val !== '');

        $kecamatanTerpilihObj = $kecamatanId
            ? $kecamatanList->firstWhere('id', (int) $kecamatanId)
            : null;
    @endphp

    <!-- Bar Kategori Cepat & Filter Cepat Katalog -->
    <section class="catalog-quickbar" aria-label="Kategori dan Filter Cepat">
        <div class="site-container">
            <div class="category-pills-row">
                <a
                    href="{{ route('beranda', array_diff_key($baseQuery, ['kategori_id' => ''])) }}"
                    class="category-pill {{ empty($kategoriId) ? 'active' : '' }}"
                >
                    Semua Kategori
                </a>
                @foreach ($kategoriList as $kat)
                    <a
                        href="{{ route('beranda', array_merge($baseQuery, ['kategori_id' => $kat->id])) }}"
                        class="category-pill {{ (int) $kategoriId === $kat->id ? 'active' : '' }}"
                    >
                        {{ $kat->nama_kategori }}
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    @if (! $adaFilterAktif)
        <!-- Banner Ringkas: Barang baik, hidup lagi. + 3 Gambar Tumpuk Alur (sembunyi saat filter/pencarian aktif) -->
        <section class="compact-hero-section" aria-label="Pengenalan Barantau">
            <div class="site-container compact-hero-grid">
                <div>
                    <h1 class="compact-hero-title">
                        Barang baik,<br>
                        <span class="hero-title-italic">hidup lagi.</span>
                    </h1>

                    <div class="compact-hero-sub">
                        <p class="compact-hero-desc">
                            Temukan barang layak pakai di sekitarmu. Lebih hemat, lebih dekat, dan lebih panjang umur pakainya.
                        </p>
                    </div>
                </div>

                <div class="compact-hero-visual">
                    <a href="{{ route('tentang') }}#cara-kerja" id="link-hero-alur-transaksi" style="display: block; position: relative;" title="Klik untuk melihat detail Alur Transaksi">
                        <div class="hero-stack compact-hero-stack" id="hero-fade-carousel">
                            <div class="hero-stack-card pos-0" data-step="01">
                                <span class="hero-step-corner">01</span>
                                <img
                                    src="{{ asset('Assets/Beranda/pencarian.jpg') }}"
                                    alt="Alur 01: Mencari Barang di Sekitarmu"
                                >
                            </div>
                            <div class="hero-stack-card pos-1" data-step="02">
                                <span class="hero-step-corner">02</span>
                                <img
                                    src="{{ asset('Assets/Beranda/CHAT.jpg') }}"
                                    alt="Alur 02: Chat Penjual Secara Langsung"
                                >
                            </div>
                            <div class="hero-stack-card pos-2" data-step="03">
                                <span class="hero-step-corner">03</span>
                                <img
                                    src="{{ asset('Assets/Beranda/DEAL.jpg') }}"
                                    alt="Alur 03: Bertemu untuk COD di Tempat Aman"
                                >
                            </div>
                        </div>
                    </a>
                </div>
            </div>
        </section>
    @endif

    <!-- Konten Utama Katalog Barang -->
    <section class="site-container catalog-main-section">
        @if (session('status'))
            <div class="alert alert-success" role="status" id="alert-status">
                {{ session('status') }}
            </div>
        @endif

        @if ($adaFilterAktif)
            <div class="catalog-toolbar" id="daftar-katalog-barang" style="justify-content: flex-end;">
                <div class="catalog-quick-filters">
                    <a href="{{ route('beranda') }}" class="quick-chip-reset" id="btn-reset-filter">
                        &times; Reset Filter
                    </a>
                </div>
            </div>
        @endif

        @if ($daftarBarang->isNotEmpty())
            <div class="products-grid">
                @foreach ($daftarBarang as $barang)
                    @php
                        $namaKategori = $barang->kategori?->nama_kategori ?? 'Umum';
                        $lokasiLengkap = $barang->kecamatan?->labelLengkap() ?? '-';
                        $dalamWishlist = in_array($barang->id, $wishlistBarangIds ?? [], true);
                        $isTerjual = $barang->status === 'terjual';
                    @endphp
                    <article class="product-card {{ $isTerjual ? 'is-sold' : '' }}">
                        <div class="product-media">
                            <!-- Tampilkan badge hanya jika status Terjual -->
                            @if ($isTerjual)
                                <span class="product-badge sold" style="z-index: 5;">Terjual</span>
                            @endif

                            <!-- Tombol Hati di Sudut Kanan Atas Foto untuk Wishlist (tidak membuka detail) -->
                            <form method="POST" action="{{ route('wishlist.toggle') }}" class="wishlist-heart-form">
                                @csrf
                                <input type="hidden" name="barang_id" value="{{ $barang->id }}">
                                <button
                                    type="submit"
                                    class="wishlist-heart-btn {{ $dalamWishlist ? 'active' : '' }}"
                                    aria-label="{{ $dalamWishlist ? 'Hapus dari Wishlist' : 'Tambah ke Wishlist' }}"
                                    title="{{ $dalamWishlist ? 'Hapus dari Wishlist' : 'Simpan ke Wishlist' }}"
                                >
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="{{ $dalamWishlist ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="1.9" aria-hidden="true">
                                        <path d="M12 20.5s-7.5-4.6-9.5-9A5.5 5.5 0 0 1 12 6a5.5 5.5 0 0 1 9.5 5.5c-2 4.4-9.5 9-9.5 9Z"/>
                                    </svg>
                                </button>
                            </form>

                            <!-- Klik foto membuka halaman Detail Barang -->
                            <a href="{{ route('barang.show', $barang) }}" class="product-media-link">
                                @if ($barang->urlFotoUtama())
                                    <img
                                        src="{{ $barang->urlFotoUtama() }}"
                                        alt="{{ $barang->nama_barang }}"
                                        class="{{ $isTerjual ? 'sold' : '' }}"
                                    >
                                @else
                                    <div class="product-placeholder">
                                        <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                            <rect x="3" y="5" width="18" height="14" rx="2"/>
                                            <circle cx="8.5" cy="10.5" r="1.5"/>
                                            <path d="m21 15-5-5L5 19"/>
                                        </svg>
                                        <span>Belum ada foto</span>
                                    </div>
                                @endif
                            </a>
                        </div>

                        <div class="product-info">
                            <p class="product-price">{{ $barang->hargaRupiah() }}</p>

                            <h2 class="product-title">
                                <a href="{{ route('barang.show', $barang) }}">
                                    {{ $barang->nama_barang }}
                                </a>
                            </h2>

                            <div class="product-meta-badges">
                                <span class="card-meta-tag">{{ $barang->kondisi }}</span>
                                <span class="card-meta-tag muted">{{ $namaKategori }}</span>
                                @if ($isTerjual)
                                    <span class="card-status-tag sold">Terjual</span>
                                @endif
                            </div>

                            <p class="product-location">
                                <svg width="14" height="14" viewBox="0 0 20 20" fill="none" aria-hidden="true" style="flex-shrink: 0;">
                                    <path d="M15.5 8.2c0 4.2-5.5 8.1-5.5 8.1S4.5 12.4 4.5 8.2a5.5 5.5 0 1 1 11 0Z" stroke="currentColor" stroke-width="1.5" />
                                    <circle cx="10" cy="8" r="1.7" stroke="currentColor" stroke-width="1.5" />
                                </svg>
                                <span>{{ $lokasiLengkap }}</span>
                            </p>
                        </div>
                    </article>
                @endforeach
            </div>
        @else
            <div class="empty-listing-box">
                @if ($adaFilterAktif)
                    <p class="font-display" style="font-size: 1.4rem; font-weight: 600; color: var(--text);">
                        Tidak ada barang yang cocok dengan pencarian atau filter Anda.
                    </p>
                    <p style="margin-top: 0.4rem; margin-bottom: 1.25rem;">
                        @if ($hanyaBaruDitambahkan)
                            Belum ada barang yang diposting dalam 2 hari terakhir untuk filter ini. Coba tampilkan seluruh katalog.
                        @else
                            Coba gunakan kata kunci lain atau tampilkan semua kategori, kecamatan, dan status.
                        @endif
                    </p>
                    <div style="display: flex; flex-wrap: wrap; justify-content: center; gap: 0.75rem;">
                        @if ($hanyaBaruDitambahkan)
                            <a href="{{ route('beranda', array_diff_key($baseQuery, ['baru' => ''])) }}" class="button-primary">
                                Lihat Semua Katalog
                            </a>
                        @endif
                        <a href="{{ route('beranda') }}" class="button-outline">
                            Reset Semua Filter
                        </a>
                    </div>
                @else
                    <p class="font-display" style="font-size: 1.4rem; font-weight: 600; color: var(--text);">
                        Belum ada barang di katalog saat ini.
                    </p>
                    <p style="margin-top: 0.4rem; margin-bottom: 1.25rem;">
                        Jadilah yang pertama menjual barang bekas layak pakai di BARANTAU!
                    </p>
                    @auth
                        <a href="{{ route('barang.create') }}" class="button-primary">
                            Jual Barang Sekarang
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="button-primary">
                            Masuk untuk Jual Barang
                        </a>
                    @endauth
                @endif
            </div>
        @endif
    </section>

    <script>
        (function () {
            const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            const heroCards = document.querySelectorAll('#hero-fade-carousel .hero-stack-card');
            let currentHeroIdx = 0;

            function updateHeroStack() {
                const total = heroCards.length;
                heroCards.forEach(function (card, idx) {
                    card.classList.remove('pos-0', 'pos-1', 'pos-2');
                    const relativePos = (idx - currentHeroIdx + total) % total;
                    card.classList.add('pos-' + relativePos);
                });
            }

            if (heroCards.length > 1 && !prefersReducedMotion) {
                setInterval(function () {
                    currentHeroIdx = (currentHeroIdx + 1) % heroCards.length;
                    updateHeroStack();
                }, 3000);
            }
        })();
    </script>
@endsection
