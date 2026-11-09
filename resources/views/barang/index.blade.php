@extends('layouts.app')

@section('title', 'Daftar Barang')
@section('description', 'Jelajahi daftar barang bekas layak pakai yang masih tersedia berdasarkan kategori dan kecamatan.')

@section('fullbleed')
    <section class="site-container" style="padding-top: 3rem; padding-bottom: 1.5rem;">
        @if (session('status'))
            <div class="alert alert-success" role="status" id="alert-status">
                {{ session('status') }}
            </div>
        @endif

        <div class="eyebrow">
            <span class="eyebrow-dot"></span>
            Katalog Barang Bekas
        </div>

        <div style="display: flex; flex-wrap: wrap; align-items: flex-end; justify-content: space-between; gap: 1rem;">
            <div>
                <h1 class="section-heading">Daftar Barang</h1>
                <p style="margin-top: 0.5rem; color: var(--muted); font-size: 0.95rem;">
                    Menampilkan {{ $daftarBarang->count() }} barang yang masih tersedia, diurutkan dari yang terbaru.
                </p>
            </div>
        </div>
    </section>

    <!-- Panel Pencarian & Filter Kategori serta Kecamatan -->
    <section class="filter-panel" aria-label="Pencarian dan Filter Barang">
        <div class="site-container">
            <form method="GET" action="{{ route('barang.index') }}" class="filter-grid" id="form-filter-barang">
                <div>
                    <label for="q" style="display: block; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 0.4rem;">
                        Cari Barang
                    </label>
                    <input
                        type="search"
                        id="q"
                        name="q"
                        class="form-control"
                        value="{{ $kataKunci }}"
                        placeholder="Ketik nama barang atau kata kunci..."
                    >
                </div>

                <div>
                    <label for="kategori_id" style="display: block; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 0.4rem;">
                        Kategori
                    </label>
                    <select id="kategori_id" name="kategori_id" class="form-control">
                        <option value="">Semua Kategori</option>
                        @foreach ($kategoriList as $kat)
                            <option value="{{ $kat->id }}" {{ (int) $kategoriId === $kat->id ? 'selected' : '' }}>
                                {{ $kat->nama_kategori }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="kecamatan_id" style="display: block; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 0.4rem;">
                        Kecamatan
                    </label>
                    <select id="kecamatan_id" name="kecamatan_id" class="form-control">
                        <option value="">Semua Kecamatan</option>
                        @foreach ($kecamatanList as $kec)
                            <option value="{{ $kec->id }}" {{ (int) $kecamatanId === $kec->id ? 'selected' : '' }}>
                                {{ $kec->labelLengkap() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div style="display: flex; gap: 0.5rem;">
                    <button type="submit" class="btn" id="btn-terapkan-filter">
                        Cari &amp; Filter
                    </button>
                    @if ($kataKunci !== '' || $kategoriId || $kecamatanId)
                        <a href="{{ route('barang.index') }}" class="button-outline" id="btn-reset-filter" style="padding: 0.9rem 1rem;">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </section>

    <section class="site-container" style="padding-bottom: 5rem;">
        @if ($daftarBarang->isNotEmpty())
            <div class="products-grid">
                @foreach ($daftarBarang as $barang)
                    @php
                        $namaKategori = $barang->kategori?->nama_kategori ?? 'Umum';
                        $lokasiLengkap = $barang->kecamatan?->labelLengkap() ?? '-';
                        $dalamWishlist = in_array($barang->id, $wishlistBarangIds ?? [], true);
                    @endphp
                    <article class="product-card">
                        <div class="product-media">
                            <!-- Ikon Hati di Sudut Kanan Atas Foto (Berfungsi terpisah dari link detail) -->
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
                            <a href="{{ route('barang.show', $barang) }}" style="display: block; width: 100%; height: 100%;">
                                @if ($barang->urlFotoUtama())
                                    <img src="{{ $barang->urlFotoUtama() }}" alt="{{ $barang->nama_barang }}">
                                @else
                                    <div class="product-placeholder">
                                        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
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
                            <div class="product-top">
                                <!-- Klik judul membuka halaman Detail Barang -->
                                <h2 class="product-title">
                                    <a href="{{ route('barang.show', $barang) }}">
                                        {{ $barang->nama_barang }}
                                    </a>
                                </h2>
                                <p class="product-price">{{ $barang->hargaRupiah() }}</p>
                            </div>

                            <p class="product-category">
                                {{ $namaKategori }} &bull; Kondisi: {{ $barang->kondisi }} &bull; Status: {{ ucfirst($barang->status) }}
                            </p>

                            <p class="product-location">
                                <svg width="16" height="16" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                                    <path d="M15.5 8.2c0 4.2-5.5 8.1-5.5 8.1S4.5 12.4 4.5 8.2a5.5 5.5 0 1 1 11 0Z" stroke="currentColor" stroke-width="1.4" />
                                    <circle cx="10" cy="8" r="1.7" stroke="currentColor" stroke-width="1.4" />
                                </svg>
                                {{ $lokasiLengkap }}
                            </p>
                        </div>
                    </article>
                @endforeach
            </div>
        @else
            <div class="empty-listing-box">
                <p class="font-display" style="font-size: 1.5rem; font-weight: 600; color: var(--text);">
                    Tidak ada barang yang cocok dengan pencarian atau filter Anda.
                </p>
                <p style="margin-top: 0.4rem; margin-bottom: 1.25rem;">
                    Coba gunakan kata kunci lain atau tampilkan semua kategori dan kecamatan.
                </p>
                <a href="{{ route('barang.index') }}" class="button-outline">
                    Reset Pencarian &amp; Filter
                </a>
            </div>
        @endif
    </section>
@endsection

