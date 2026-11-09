@extends('layouts.app')

@section('title', $barang->nama_barang)
@section('description', Str::limit($barang->deskripsi, 140))

@section('content')
    @if (session('status'))
        <div class="alert alert-success" role="status" id="alert-status">
            {{ session('status') }}
        </div>
    @endif

    <div style="margin-bottom: 1.75rem;">
        <a href="{{ route('barang.index') }}" style="font-size: 0.85rem; font-weight: 700; color: var(--muted);">
            &larr; Kembali ke Daftar Barang
        </a>
    </div>

    <div class="detail-grid">
        <!-- Galeri Foto Barang -->
        <div>
            @php
                $daftarFoto = $barang->foto;
                $fotoPertama = $daftarFoto->first()?->urlFoto();
            @endphp

            <div class="detail-main-photo">
                @if ($fotoPertama)
                    <img id="main-detail-image" src="{{ $fotoPertama }}" alt="{{ $barang->nama_barang }}">
                @else
                    <div class="product-placeholder">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <rect x="3" y="5" width="18" height="14" rx="2"/>
                            <circle cx="8.5" cy="10.5" r="1.5"/>
                            <path d="m21 15-5-5L5 19"/>
                        </svg>
                        <span>Penjual belum mengunggah foto untuk barang ini</span>
                    </div>
                @endif
            </div>

            @if ($daftarFoto->count() > 1)
                <div class="detail-thumbs" aria-label="Galeri foto barang">
                    @foreach ($daftarFoto as $idx => $foto)
                        <button
                            type="button"
                            class="detail-thumb-btn {{ $idx === 0 ? 'active' : '' }}"
                            onclick="switchDetailPhoto('{{ $foto->urlFoto() }}', this)"
                            aria-label="Lihat foto {{ $idx + 1 }}"
                        >
                            <img src="{{ $foto->urlFoto() }}" alt="{{ $barang->nama_barang }} - foto {{ $idx + 1 }}">
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Informasi Lengkap Barang -->
        <div>
            <div class="eyebrow" style="margin-bottom: 0.75rem;">
                <span class="eyebrow-dot"></span>
                {{ $barang->kategori?->nama_kategori ?? 'Umum' }}
            </div>

            <h1 class="font-display" style="font-size: clamp(2rem, 4vw, 3rem); font-weight: 600; letter-spacing: -0.05em; line-height: 1.08;">
                {{ $barang->nama_barang }}
            </h1>

            <p class="font-display" style="margin-top: 0.85rem; font-size: 1.85rem; font-weight: 700; color: var(--accent);">
                {{ $barang->hargaRupiah() }}
            </p>

            <div class="detail-meta-list">
                <div class="detail-meta-row">
                    <span class="detail-meta-label">Status Barang</span>
                    <span style="font-weight: 700;">{{ ucfirst($barang->status) }}</span>
                </div>
                <div class="detail-meta-row">
                    <span class="detail-meta-label">Kondisi</span>
                    <span style="font-weight: 600;">{{ $barang->kondisi }}</span>
                </div>
                <div class="detail-meta-row">
                    <span class="detail-meta-label">Lokasi (Tingkat Kecamatan)</span>
                    <span style="font-weight: 600;">{{ $barang->kecamatan?->labelLengkap() ?? '-' }}</span>
                </div>
                <div class="detail-meta-row">
                    <span class="detail-meta-label">Penjual</span>
                    <span>
                        @if ($barang->penjual)
                            <a href="{{ route('profil.show', ['username' => $barang->penjual->username]) }}" style="font-weight: 700; text-decoration: underline; text-underline-offset: 3px;">
                                {{ $barang->penjual->name }} ({{ '@' . $barang->penjual->username }})
                            </a>
                        @else
                            -
                        @endif
                    </span>
                </div>
            </div>

            <div>
                <h2 style="font-size: 0.85rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 0.6rem;">
                    Deskripsi Barang
                </h2>
                <div style="font-size: 0.96rem; line-height: 1.75; color: var(--muted); white-space: pre-line;">{{ $barang->deskripsi }}</div>
            </div>

            <p style="margin-top: 1.25rem; padding: 0.75rem 1rem; background: var(--bg-alt); border-left: 3px solid var(--accent); font-size: 0.82rem; color: var(--muted);">
                Demi privasi dan keamanan, lokasi hanya ditampilkan pada tingkat kecamatan (tanpa alamat rumah atau titik koordinat persis).
            </p>

            <!-- Tombol Chat Penjual & Tombol Hati Wishlist -->
            <div class="detail-actions">
                @if ($namaRouteChat)
                    <a href="{{ route($namaRouteChat, $barang) }}" class="btn" id="btn-chat-penjual" style="flex: 1;">
                        Chat Penjual
                    </a>
                @else
                    <button type="button" class="btn-disabled" id="btn-chat-penjual-disabled" disabled title="Fitur chat belum dibuat">
                        Chat Penjual (Segera Hadir)
                    </button>
                @endif

                <form method="POST" action="{{ route('wishlist.toggle') }}">
                    @csrf
                    <input type="hidden" name="barang_id" value="{{ $barang->id }}">
                    <button
                        type="submit"
                        id="btn-detail-wishlist"
                        class="button-outline"
                        style="height: 100%; padding: 0.85rem 1.25rem; {{ $dalamWishlist ? 'background: var(--accent); border-color: var(--accent); color: #fff;' : '' }}"
                    >
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="{{ $dalamWishlist ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="1.9" aria-hidden="true">
                            <path d="M12 20.5s-7.5-4.6-9.5-9A5.5 5.5 0 0 1 12 6a5.5 5.5 0 0 1 9.5 5.5c-2 4.4-9.5 9-9.5 9Z"/>
                        </svg>
                        <span>{{ $dalamWishlist ? 'Tersimpan di Wishlist' : 'Simpan ke Wishlist' }}</span>
                    </button>
                </form>
            </div>

            @if (! $namaRouteChat)
                <p class="helper-text" style="margin-top: 0.65rem;">
                    Info: Rute fitur Chat Penjual sedang dalam tahap pengembangan dan belum tersedia saat ini.
                </p>
            @endif
        </div>
    </div>

    <script>
        function switchDetailPhoto(src, btnEl) {
            const mainImg = document.getElementById('main-detail-image');
            if (mainImg) mainImg.src = src;
            document.querySelectorAll('.detail-thumb-btn').forEach((btn) => btn.classList.remove('active'));
            if (btnEl) btnEl.classList.add('active');
        }
    </script>
@endsection

