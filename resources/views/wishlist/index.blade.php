@extends('layouts.app')

@section('title', 'Wishlist Saya')
@section('description', 'Daftar barang bekas incaran yang Anda simpan di Wishlist.')

@section('content')
    @if (session('status'))
        <div class="alert alert-success" role="status" id="alert-status">
            {{ session('status') }}
        </div>
    @endif

    <div class="eyebrow">
        <span class="eyebrow-dot"></span>
        Barang Tersimpan
    </div>

    <div class="listings-header" style="margin-bottom: 2.5rem;">
        <div>
            <h1 class="section-heading">Wishlist Saya ({{ $items->count() }})</h1>
            <p style="margin-top: 0.45rem; color: var(--muted); font-size: 0.95rem;">
                Barang-barang yang Anda simpan untuk dilihat kembali atau ditanyakan ke penjual.
            </p>
        </div>
        <a href="{{ route('barang.index') }}" class="button-outline">
            Jelajahi Barang Lainnya
        </a>
    </div>

    @if ($items->isNotEmpty())
        <div class="products-grid">
            @foreach ($items as $wishlistItem)
                @php
                    $barang = $wishlistItem->barang;
                    $namaKategori = $barang->kategori?->nama_kategori ?? 'Umum';
                    $lokasiLengkap = $barang->kecamatan?->labelLengkap() ?? '-';
                @endphp
                <article class="product-card">
                    <div class="product-media">
                        <!-- Tombol hapus dari Wishlist di sudut kanan atas foto -->
                        <form method="POST" action="{{ route('wishlist.destroy', $barang) }}" class="wishlist-heart-form">
                            @csrf
                            @method('DELETE')
                            <button
                                type="submit"
                                class="wishlist-heart-btn active"
                                aria-label="Hapus dari Wishlist"
                                title="Hapus dari Wishlist"
                            >
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="1.9" aria-hidden="true">
                                    <path d="M12 20.5s-7.5-4.6-9.5-9A5.5 5.5 0 0 1 12 6a5.5 5.5 0 0 1 9.5 5.5c-2 4.4-9.5 9-9.5 9Z"/>
                                </svg>
                            </button>
                        </form>

                        @if ($barang->status === 'terjual')
                            <span class="product-badge sold">Terjual</span>
                        @endif

                        <!-- Klik foto membuka Detail Barang -->
                        <a href="{{ route('barang.show', $barang) }}" style="display: block; width: 100%; height: 100%;">
                            @if ($barang->urlFotoUtama())
                                <img
                                    src="{{ $barang->urlFotoUtama() }}"
                                    alt="{{ $barang->nama_barang }}"
                                    class="{{ $barang->status === 'terjual' ? 'sold' : '' }}"
                                >
                            @else
                                <div class="product-placeholder">
                                    <span>Belum ada foto</span>
                                </div>
                            @endif
                        </a>
                    </div>

                    <div class="product-info">
                        <div class="product-top">
                            <h2 class="product-title">
                                <a href="{{ route('barang.show', $barang) }}">
                                    {{ $barang->nama_barang }}
                                </a>
                            </h2>
                            <p class="product-price">{{ $barang->hargaRupiah() }}</p>
                        </div>

                        <p class="product-category">
                            {{ $namaKategori }} &bull; {{ $barang->kondisi }} &bull; Status: {{ ucfirst($barang->status) }}
                        </p>

                        <p class="product-location">
                            <svg width="16" height="16" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                                <path d="M15.5 8.2c0 4.2-5.5 8.1-5.5 8.1S4.5 12.4 4.5 8.2a5.5 5.5 0 1 1 11 0Z" stroke="currentColor" stroke-width="1.4" />
                                <circle cx="10" cy="8" r="1.7" stroke="currentColor" stroke-width="1.4" />
                            </svg>
                            {{ $lokasiLengkap }}
                        </p>

                        <div style="margin-top: 0.9rem; display: flex; justify-content: space-between; align-items: center; gap: 0.75rem;">
                            <a href="{{ route('barang.show', $barang) }}" class="seller-chip" style="margin-top: 0;">
                                Lihat Detail Barang &rarr;
                            </a>

                            <form method="POST" action="{{ route('wishlist.destroy', $barang) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" style="background: transparent; border: none; font-size: 0.78rem; font-weight: 700; color: #B02A1E; text-decoration: underline;">
                                    Hapus dari Wishlist
                                </button>
                            </form>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    @else
        <div class="empty-listing-box">
            <p class="font-display" style="font-size: 1.5rem; font-weight: 600; color: var(--text);">
                Wishlist Anda masih kosong.
            </p>
            <p style="margin-top: 0.45rem; margin-bottom: 1.25rem;">
                Tekan ikon hati pada barang yang Anda sukai di halaman Daftar Barang untuk menyimpannya ke sini.
            </p>
            <a href="{{ route('barang.index') }}" class="button-primary">
                Jelajahi Barang Sekarang
            </a>
        </div>
    @endif
@endsection

