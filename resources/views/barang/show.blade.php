@extends('layouts.app')

@section('title', $barang->nama_barang)
@section('description', Str::limit($barang->deskripsi, 140))
@section('custom_back_bar', '1')

@section('content')
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

    <div class="page-back-bar" style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem;">
        <a
            href="{{ route('beranda') }}"
            data-fallback="{{ route('beranda') }}"
            onclick="return goBackBarantau(event, this)"
            class="btn-back-barantau"
            id="btn-kembali-detail"
            aria-label="Kembali ke halaman sebelumnya"
            title="Kembali"
        >
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M19 12H5M12 19l-7-7 7-7"/>
            </svg>
        </a>

        @auth
            @if (auth()->id() === $barang->user_id)
                <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 0.6rem;">
                    <a href="{{ route('barang.edit', $barang) }}" class="button-primary" id="btn-edit-barang">
                        Edit Barang &amp; Status
                    </a>

                    <form method="POST" action="{{ route('barang.update-status', $barang) }}" style="display: inline;">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="{{ $barang->status === 'tersedia' ? 'terjual' : 'tersedia' }}">
                        <button type="submit" class="button-outline" id="btn-toggle-status-cepat">
                            {{ $barang->status === 'tersedia' ? 'Tandai Terjual' : 'Tandai Tersedia Kembali' }}
                        </button>
                    </form>

                    <form
                        method="POST"
                        action="{{ route('barang.destroy', $barang) }}"
                        style="display: inline;"
                        onsubmit="return confirm('Apakah Anda yakin ingin menghapus barang ini dari katalog?');"
                    >
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="button-danger" id="btn-hapus-barang">
                            Hapus Barang
                        </button>
                    </form>
                </div>
            @endif
        @endauth
    </div>

    @php
        $isTerjual = $barang->status === 'terjual';
        $daftarFoto = $barang->foto;
        $fotoPertama = $daftarFoto->first()?->urlFoto();
        $kecamatanObj = $barang->kecamatan;
        $namaKecamatan = $kecamatanObj?->nama_wilayah;
        $namaKabKota = $kecamatanObj?->namaKabupatenKota();
        $namaProvinsi = $kecamatanObj?->namaProvinsi();
        $lokasiHierarkiLengkap = $kecamatanObj?->labelHierarkiLengkap() ?? '-';
        $kueriPetaKecamatan = $kecamatanObj?->kueriPencarianPeta() ?? '';
        $urlGoogleMapsKecamatan = $kecamatanObj?->urlGoogleMaps() ?? 'https://www.google.com/maps';
    @endphp

    <div class="detail-grid">
        <!-- Galeri Seluruh Foto Barang -->
        <div>
            <div class="detail-main-photo {{ $fotoPertama ? 'is-clickable' : '' }}">
                @if ($isTerjual)
                    <span class="product-badge sold" style="z-index: 5; font-size: 0.78rem; padding: 0.5rem 1rem;">
                        Terjual
                    </span>
                @endif

                @if ($fotoPertama)
                    <img
                        id="main-detail-image"
                        src="{{ $fotoPertama }}"
                        alt="{{ $barang->nama_barang }}"
                        class="{{ $isTerjual ? 'sold' : '' }}"
                        onclick="openPhotoLightbox(currentDetailPhotoIndex)"
                        title="Klik untuk memperbesar gambar"
                    >
                    @if ($daftarFoto->count() > 1)
                        <button
                            type="button"
                            class="detail-photo-nav prev"
                            onclick="event.stopPropagation(); stepDetailPhoto(-1);"
                            aria-label="Foto sebelumnya"
                        >
                            &lsaquo;
                        </button>
                        <button
                            type="button"
                            class="detail-photo-nav next"
                            onclick="event.stopPropagation(); stepDetailPhoto(1);"
                            aria-label="Foto berikutnya"
                        >
                            &rsaquo;
                        </button>
                    @endif
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

            @if ($daftarFoto->isNotEmpty())
                <div class="detail-thumbs" aria-label="Galeri seluruh foto barang ({{ $daftarFoto->count() }} foto)">
                    @foreach ($daftarFoto as $idx => $foto)
                        <button
                            type="button"
                            class="detail-thumb-btn {{ $idx === 0 ? 'active' : '' }}"
                            onclick="switchDetailPhoto({{ $idx }})"
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
            <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 0.75rem; margin-bottom: 0.75rem;">
                <div class="eyebrow" style="margin-bottom: 0;">
                    <span class="eyebrow-dot"></span>
                    {{ $barang->kategori?->nama_kategori ?? 'Umum' }}
                </div>

                @if ($isTerjual)
                    <span class="status-pill sold">Terjual</span>
                @endif
            </div>

            <h1 class="font-display" style="font-size: clamp(2rem, 4vw, 3rem); font-weight: 600; letter-spacing: -0.05em; line-height: 1.08;">
                {{ $barang->nama_barang }}
            </h1>

            <p class="font-display" style="margin-top: 0.85rem; font-size: 1.85rem; font-weight: 700; color: var(--accent);">
                {{ $barang->hargaRupiah() }}
            </p>

            <!-- Kartu Penjual (Username + Foto Profil) -->
            @if ($barang->penjual)
                <a
                    href="{{ route('profil.show', ['username' => $barang->penjual->username]) }}"
                    class="detail-seller-card"
                    title="Lihat profil penjual {{ '@' . $barang->penjual->username }}"
                >
                    <div class="detail-seller-avatar">
                        @if ($barang->penjual->urlFotoProfil())
                            <img src="{{ $barang->penjual->urlFotoProfil() }}" alt="Foto profil {{ '@' . $barang->penjual->username }}">
                        @else
                            <span>{{ $barang->penjual->inisial() }}</span>
                        @endif
                    </div>
                    <div style="min-width: 0; flex: 1;">
                        <p style="font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: var(--muted);">
                            Penjual
                        </p>
                        <p style="font-size: 0.98rem; font-weight: 700; color: var(--text);">
                            {{ '@' . $barang->penjual->username }}
                            <span style="font-weight: 500; color: var(--muted);">({{ $barang->penjual->name }})</span>
                        </p>
                    </div>
                    <span style="font-size: 0.8rem; font-weight: 700; color: var(--accent);">
                        Lihat Profil &rarr;
                    </span>
                </a>
            @endif

            <div class="detail-meta-list">
                @if ($isTerjual)
                    <div class="detail-meta-row">
                        <span class="detail-meta-label">Status Barang</span>
                        <span>
                            <span class="status-pill sold">Terjual</span>
                        </span>
                    </div>
                @endif
                <div class="detail-meta-row">
                    <span class="detail-meta-label">Kategori</span>
                    <span style="font-weight: 600;">{{ $barang->kategori?->nama_kategori ?? 'Umum' }}</span>
                </div>
                <div class="detail-meta-row">
                    <span class="detail-meta-label">Kondisi</span>
                    <span style="font-weight: 600;">{{ $barang->kondisi }}</span>
                </div>
                <div class="detail-meta-row">
                    <span class="detail-meta-label">Kecamatan</span>
                    <span style="font-weight: 600;">{{ $namaKecamatan ? 'Kec. ' . $namaKecamatan : '-' }}</span>
                </div>
                @if ($namaKabKota)
                    <div class="detail-meta-row">
                        <span class="detail-meta-label">Kabupaten / Kota</span>
                        <span style="font-weight: 600;">{{ $namaKabKota }}</span>
                    </div>
                @endif
                @if ($namaProvinsi)
                    <div class="detail-meta-row">
                        <span class="detail-meta-label">Provinsi</span>
                        <span style="font-weight: 600;">{{ $namaProvinsi }}</span>
                    </div>
                @endif
            </div>

            <div>
                <h2 style="font-size: 0.85rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 0.6rem;">
                    Deskripsi Barang
                </h2>
                <div style="font-size: 0.96rem; line-height: 1.75; color: var(--muted); white-space: pre-line;">{{ $barang->deskripsi }}</div>
            </div>

            <!-- Peta Perkiraan Area Kecamatan (Tanpa Pin / Titik Rumah Penjual, Dapat Ditekan ke Google Maps) -->
            @if ($kecamatanObj)
                <section class="kecamatan-map-section" id="section-peta-kecamatan" aria-labelledby="heading-peta-kecamatan">
                    <div class="kecamatan-map-section-header">
                        <h2 id="heading-peta-kecamatan" class="kecamatan-map-heading">
                            Lokasi Penjual
                        </h2>
                        <span class="kecamatan-map-region-tag">
                            {{ $lokasiHierarkiLengkap }}
                        </span>
                    </div>

                    <a
                        href="{{ $urlGoogleMapsKecamatan }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="kecamatan-map-card"
                        id="link-peta-kecamatan-gmaps"
                        title="Tekan untuk melihat area {{ $kueriPetaKecamatan }} di Google Maps"
                        aria-label="Tekan peta untuk membuka pencarian {{ $kueriPetaKecamatan }} di Google Maps"
                    >
                        <div class="kecamatan-map-card-top">
                            <span class="kecamatan-map-badge">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                                    <circle cx="12" cy="10" r="3"/>
                                </svg>
                            </span>
                            <span class="kecamatan-map-gmaps-cta">
                                <span>Buka di Google Maps</span>
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                                    <path d="M7 17L17 7M9 7h8v8"/>
                                </svg>
                            </span>
                        </div>

                        <div class="kecamatan-map-stage">
                            <!-- Kanvas Peta Poligon Batas Kecamatan (aktif hanya jika poligon batas wilayah valid tersedia) -->
                            <div
                                id="kecamatan-leaflet-map"
                                class="kecamatan-map-canvas"
                                aria-hidden="true"
                            ></div>

                            <!-- Tampilan Informasi Wilayah & Keterangan Data Batas (ditampilkan jika poligon batas belum tersedia) -->
                            <div id="kecamatan-map-fallback" class="kecamatan-map-fallback">
                                <div class="kecamatan-map-fallback-grid">
                                    <div class="kecamatan-map-hierarchy-item">
                                        <span class="kecamatan-map-hierarchy-label">Kecamatan</span>
                                        <strong class="kecamatan-map-hierarchy-value">Kec. {{ $namaKecamatan }}</strong>
                                    </div>
                                    <div class="kecamatan-map-hierarchy-item">
                                        <span class="kecamatan-map-hierarchy-label">Kabupaten / Kota</span>
                                        <strong class="kecamatan-map-hierarchy-value">{{ $namaKabKota ?? '-' }}</strong>
                                    </div>
                                    <div class="kecamatan-map-hierarchy-item">
                                        <span class="kecamatan-map-hierarchy-label">Provinsi</span>
                                        <strong class="kecamatan-map-hierarchy-value">{{ $namaProvinsi ?? '-' }}</strong>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </a>

                    <p class="kecamatan-map-notice">
                        Peta ini hanya menunjukkan <strong>perkiraan area kecamatan secara umum</strong> tanpa pin atau koordinat alamat rumah penjual demi menjaga privasi.
                    </p>
                </section>
            @else
                <p style="margin-top: 1.25rem; padding: 0.75rem 1rem; background: var(--bg-alt); border-left: 3px solid var(--accent); font-size: 0.82rem; color: var(--muted);">
                    Demi privasi dan keamanan, lokasi hanya ditampilkan pada tingkat kecamatan (tanpa alamat rumah atau titik koordinat persis).
                </p>
            @endif

            <!-- Tindakan Barang: Jika Terjual, jangan tampilkan tombol chat baru seolah masih tersedia, namun tetap izinkan buka riwayat chat yang sudah ada -->
            @if ($isTerjual)
                <div class="detail-sold-notice" role="status">
                    <span class="status-pill sold">Terjual</span>
                    <p style="margin-top: 0.5rem; font-size: 0.9rem; color: var(--muted);">
                        Barang ini sudah <strong>terjual</strong> dan tidak lagi tersedia untuk percakapan baru.
                    </p>
                    @auth
                        @if (!empty($roomChatSudahAda))
                            <div style="margin-top: 0.85rem;">
                                <a href="{{ route('chat.show', $roomChatSudahAda->id) }}" class="button-outline" id="btn-riwayat-chat-pembeli">
                                    Lihat Riwayat Chat Sebelumnya
                                </a>
                            </div>
                        @elseif (auth()->id() === $barang->user_id && ($jumlahRoomBarangIni ?? 0) > 0)
                            <div style="margin-top: 0.85rem;">
                                <a href="{{ route('chat.index') }}" class="button-outline" id="btn-riwayat-chat-penjual">
                                    Lihat Riwayat Chat Pembeli ({{ $jumlahRoomBarangIni }})
                                </a>
                            </div>
                        @endif
                    @endauth
                </div>
            @else
                <div class="detail-actions">
                    @auth
                        @if (auth()->id() === $barang->user_id)
                            <a href="{{ route('chat.index') }}" class="button-outline" id="btn-daftar-chat-penjual" style="flex: 1; padding: 0.85rem 1.25rem;">
                                Lihat Chat Masuk{{ ($jumlahRoomBarangIni ?? 0) > 0 ? ' (' . $jumlahRoomBarangIni . ')' : '' }}
                            </a>
                        @else
                            <form method="POST" action="{{ route('chat.mulai', $barang) }}" style="flex: 1; display: flex;">
                                @csrf
                                <button type="submit" class="btn" id="btn-chat-penjual" style="width: 100%;">
                                    {{ !empty($roomChatSudahAda) ? 'Lanjutkan Chat Penjual' : 'Chat Penjual' }}
                                </button>
                            </form>
                        @endif
                    @else
                        <form method="POST" action="{{ route('chat.mulai', $barang) }}" style="flex: 1; display: flex;">
                            @csrf
                            <button type="submit" class="btn" id="btn-chat-penjual" style="width: 100%;">
                                Chat Penjual
                            </button>
                        </form>
                    @endauth

                    <form method="POST" action="{{ route('wishlist.toggle') }}">
                        @csrf
                        <input type="hidden" name="barang_id" value="{{ $barang->id }}">
                        <button
                            type="submit"
                            id="btn-detail-wishlist"
                            class="button-outline"
                            style="height: 100%; padding: 0.85rem 1.25rem; {{ $dalamWishlist ? 'background: var(--accent); border-color: var(--accent); color: var(--text);' : '' }}"
                        >
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="{{ $dalamWishlist ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="1.9" aria-hidden="true">
                                <path d="M12 20.5s-7.5-4.6-9.5-9A5.5 5.5 0 0 1 12 6a5.5 5.5 0 0 1 9.5 5.5c-2 4.4-9.5 9-9.5 9Z"/>
                            </svg>
                            <span>{{ $dalamWishlist ? 'Tersimpan di Wishlist' : 'Simpan ke Wishlist' }}</span>
                        </button>
                    </form>
                </div>
            @endif
        </div>
    </div>

    <!-- Lightbox Pratinjau Foto Barang (Klik gambar untuk memperbesar & geser/scroll ke samping) -->
    @if ($daftarFoto->isNotEmpty())
        <div
            class="photo-lightbox-backdrop"
            id="photo-lightbox-modal"
            role="dialog"
            aria-modal="true"
            aria-label="Pratinjau foto {{ $barang->nama_barang }}"
            onclick="if (event.target === this) closePhotoLightbox()"
        >
            <div class="photo-lightbox-header">
                <span class="photo-lightbox-counter" id="lightbox-counter">1 / {{ $daftarFoto->count() }}</span>
                <button
                    type="button"
                    class="photo-lightbox-close"
                    onclick="closePhotoLightbox()"
                    aria-label="Tutup pratinjau foto"
                >
                    &times;
                </button>
            </div>

            <div class="photo-lightbox-stage">
                @if ($daftarFoto->count() > 1)
                    <button
                        type="button"
                        class="photo-lightbox-arrow prev"
                        onclick="scrollLightboxBy(-1)"
                        aria-label="Geser ke foto sebelumnya"
                    >
                        &lsaquo;
                    </button>
                @endif

                <div class="photo-lightbox-track" id="photo-lightbox-track">
                    @foreach ($daftarFoto as $idx => $foto)
                        <div class="photo-lightbox-slide" data-index="{{ $idx }}">
                            <img src="{{ $foto->urlFoto() }}" alt="{{ $barang->nama_barang }} - foto {{ $idx + 1 }}">
                        </div>
                    @endforeach
                </div>

                @if ($daftarFoto->count() > 1)
                    <button
                        type="button"
                        class="photo-lightbox-arrow next"
                        onclick="scrollLightboxBy(1)"
                        aria-label="Geser ke foto berikutnya"
                    >
                        &rsaquo;
                    </button>
                @endif
            </div>

            @if ($daftarFoto->count() > 1)
                <div class="photo-lightbox-thumbs" id="photo-lightbox-thumbs">
                    @foreach ($daftarFoto as $idx => $foto)
                        <button
                            type="button"
                            class="photo-lightbox-thumb {{ $idx === 0 ? 'active' : '' }}"
                            onclick="scrollLightboxTo({{ $idx }})"
                            aria-label="Pilih foto {{ $idx + 1 }}"
                        >
                            <img src="{{ $foto->urlFoto() }}" alt="Thumbnail {{ $idx + 1 }}">
                        </button>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    <script>
        const detailPhotoUrls = @json($daftarFoto->map(fn ($f) => $f->urlFoto())->values());
        let currentDetailPhotoIndex = 0;

        function switchDetailPhoto(index) {
            if (!detailPhotoUrls.length) return;
            const total = detailPhotoUrls.length;
            currentDetailPhotoIndex = ((index % total) + total) % total;

            const mainImg = document.getElementById('main-detail-image');
            if (mainImg) {
                mainImg.src = detailPhotoUrls[currentDetailPhotoIndex];
            }

            document.querySelectorAll('.detail-thumb-btn').forEach(function (btn, idx) {
                btn.classList.toggle('active', idx === currentDetailPhotoIndex);
            });
        }

        function stepDetailPhoto(delta) {
            switchDetailPhoto(currentDetailPhotoIndex + delta);
        }

        function updateLightboxCounterAndThumbs(idx) {
            const counter = document.getElementById('lightbox-counter');
            if (counter && detailPhotoUrls.length) {
                counter.textContent = (idx + 1) + ' / ' + detailPhotoUrls.length;
            }
            document.querySelectorAll('.photo-lightbox-thumb').forEach(function (btn, i) {
                btn.classList.toggle('active', i === idx);
            });
        }

        function openPhotoLightbox(startIndex) {
            const modal = document.getElementById('photo-lightbox-modal');
            const track = document.getElementById('photo-lightbox-track');
            if (!modal || !track) return;

            const idx = typeof startIndex === 'number' ? startIndex : currentDetailPhotoIndex;
            modal.classList.add('open');
            document.body.style.overflow = 'hidden';

            // Gulir langsung ke slide yang dipilih
            requestAnimationFrame(function () {
                const slideWidth = track.clientWidth;
                track.scrollTo({ left: idx * slideWidth, behavior: 'auto' });
                updateLightboxCounterAndThumbs(idx);
            });
        }

        function closePhotoLightbox() {
            const modal = document.getElementById('photo-lightbox-modal');
            if (!modal) return;
            modal.classList.remove('open');
            document.body.style.overflow = '';
        }

        function scrollLightboxTo(idx) {
            const track = document.getElementById('photo-lightbox-track');
            if (!track || !detailPhotoUrls.length) return;
            const total = detailPhotoUrls.length;
            const targetIdx = ((idx % total) + total) % total;
            track.scrollTo({ left: targetIdx * track.clientWidth, behavior: 'smooth' });
            switchDetailPhoto(targetIdx);
            updateLightboxCounterAndThumbs(targetIdx);
        }

        function scrollLightboxBy(delta) {
            const track = document.getElementById('photo-lightbox-track');
            if (!track || !detailPhotoUrls.length) return;
            const currentIdx = Math.round(track.scrollLeft / Math.max(track.clientWidth, 1));
            scrollLightboxTo(currentIdx + delta);
        }

        document.addEventListener('DOMContentLoaded', function () {
            const track = document.getElementById('photo-lightbox-track');
            if (track) {
                let scrollTick = null;
                track.addEventListener('scroll', function () {
                    if (scrollTick) clearTimeout(scrollTick);
                    scrollTick = setTimeout(function () {
                        const idx = Math.round(track.scrollLeft / Math.max(track.clientWidth, 1));
                        if (idx >= 0 && idx < detailPhotoUrls.length) {
                            switchDetailPhoto(idx);
                            updateLightboxCounterAndThumbs(idx);
                        }
                    }, 60);
                });
            }

            document.addEventListener('keydown', function (e) {
                const modal = document.getElementById('photo-lightbox-modal');
                if (!modal || !modal.classList.contains('open')) return;

                if (e.key === 'Escape') {
                    closePhotoLightbox();
                } else if (e.key === 'ArrowRight') {
                    scrollLightboxBy(1);
                } else if (e.key === 'ArrowLeft') {
                    scrollLightboxBy(-1);
                }
            });

            // Inisialisasi Peta Perkiraan Area Kecamatan (Poligon Batas Wilayah Tanpa Pin / Marker)
            initKecamatanBoundaryMap();
        });

        function initKecamatanBoundaryMap() {
            const mapContainer = document.getElementById('kecamatan-leaflet-map');
            const fallbackEl = document.getElementById('kecamatan-map-fallback');
            const statusTextEl = document.getElementById('kecamatan-map-status-text');
            if (!mapContainer || !fallbackEl) return;

            const namaKecamatan = @json($namaKecamatan ?? '');
            const namaKabKota = @json($namaKabKota ?? '');
            const namaProvinsi = @json($namaProvinsi ?? '');

            const pesanButuhDataTambahan = 'Data tabel wilayah di project saat ini menyimpan hierarki administratif (Kecamatan, Kabupaten/Kota, dan Provinsi) dan belum menyertakan poligon batas wilayah lokal (GeoJSON). Bentuk atau posisi kecamatan tidak dikarang; tekan kartu peta ini untuk melihat area kecamatan secara langsung di Google Maps.';

            if (!namaKecamatan) {
                if (statusTextEl) statusTextEl.textContent = pesanButuhDataTambahan;
                return;
            }

            // Susun kandidat kueri pencarian batas administratif kecamatan di OpenStreetMap Nominatim
            const cleanKabKota = namaKabKota.replace(/^(Kabupaten|Kota)\s+/i, '').trim();
            const candidateQueries = [
                'Kecamatan ' + namaKecamatan + ', ' + namaKabKota + ', ' + namaProvinsi + ', Indonesia',
                namaKecamatan + ', ' + namaKabKota + ', ' + namaProvinsi + ', Indonesia',
                namaKecamatan + ', ' + cleanKabKota + ', ' + namaProvinsi + ', Indonesia',
            ].filter(Boolean);

            function showBoundaryFallback() {
                mapContainer.classList.remove('is-ready');
                fallbackEl.classList.remove('is-compact');
                if (statusTextEl) {
                    statusTextEl.textContent = pesanButuhDataTambahan;
                }
            }

            function loadLeafletAssets() {
                return new Promise(function (resolve, reject) {
                    if (window.L) {
                        resolve(window.L);
                        return;
                    }

                    if (!document.getElementById('leaflet-css-cdn')) {
                        const link = document.createElement('link');
                        link.id = 'leaflet-css-cdn';
                        link.rel = 'stylesheet';
                        link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
                        link.crossOrigin = '';
                        document.head.appendChild(link);
                    }

                    const script = document.createElement('script');
                    script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
                    script.crossOrigin = '';
                    script.onload = function () {
                        if (window.L) resolve(window.L);
                        else reject(new Error('Leaflet tidak tersedia'));
                    };
                    script.onerror = function () {
                        reject(new Error('Gagal memuat pustaka peta'));
                    };
                    document.head.appendChild(script);
                });
            }

            function isValidPolygonFeature(feature) {
                if (!feature || !feature.geometry) return false;
                const type = feature.geometry.type;
                return type === 'Polygon' || type === 'MultiPolygon';
            }

            async function findKecamatanPolygon() {
                const targetNameLower = namaKecamatan.toLowerCase();
                for (let i = 0; i < candidateQueries.length; i++) {
                    const q = candidateQueries[i];
                    const url = 'https://nominatim.openstreetmap.org/search?format=geojson&polygon_geojson=1&limit=5&countrycodes=id&q=' + encodeURIComponent(q);
                    const response = await fetch(url, {
                        headers: { 'Accept': 'application/geo+json, application/json' }
                    });
                    if (!response.ok) continue;
                    const geojson = await response.json();
                    if (!geojson || !Array.isArray(geojson.features)) continue;

                    // Hanya terima fitur yang benar-benar memiliki geometri batas area (Polygon / MultiPolygon),
                    // jangan pernah menggunakan titik koordinat (Point) atau mengarang lingkaran/bentuk!
                    const polygonFeature = geojson.features.find(function (f) {
                        if (!isValidPolygonFeature(f)) return false;
                        const displayName = ((f.properties && f.properties.display_name) || '').toLowerCase();
                        return displayName.indexOf(targetNameLower) !== -1;
                    });

                    if (polygonFeature) {
                        return polygonFeature;
                    }
                }
                return null;
            }

            findKecamatanPolygon()
                .then(function (polygonFeature) {
                    if (!polygonFeature) {
                        showBoundaryFallback();
                        return;
                    }

                    return loadLeafletAssets().then(function (L) {
                        mapContainer.classList.add('is-ready');
                        fallbackEl.classList.add('is-compact');
                        if (statusTextEl) {
                            statusTextEl.textContent = 'Menampilkan batas administratif umum Kecamatan ' + namaKecamatan + ' (tanpa pin lokasi penjual).';
                        }

                        // Buat peta statis/non-interaktif di dalam kartu tautan sehingga ketika ditekan
                        // langsung membuka pencarian Google Maps dan nyaman digulir di ponsel.
                        const map = L.map(mapContainer, {
                            zoomControl: false,
                            attributionControl: false,
                            dragging: false,
                            scrollWheelZoom: false,
                            doubleClickZoom: false,
                            boxZoom: false,
                            keyboard: false,
                            tap: false,
                            touchZoom: false,
                        });

                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            maxZoom: 18,
                        }).addTo(map);

                        // Gambar HANYA poligon area kecamatan, TANPA pin atau marker titik
                        const boundaryLayer = L.geoJSON(polygonFeature, {
                            style: function () {
                                return {
                                    color: '#D9541E',
                                    weight: 2.5,
                                    opacity: 0.92,
                                    dashArray: '6, 4',
                                    fillColor: '#D9541E',
                                    fillOpacity: 0.18,
                                };
                            },
                            pointToLayer: function () {
                                return null;
                            }
                        }).addTo(map);

                        const bounds = boundaryLayer.getBounds();
                        if (bounds && bounds.isValid()) {
                            map.fitBounds(bounds, { padding: [22, 22], maxZoom: 14 });
                        } else {
                            showBoundaryFallback();
                        }
                    });
                })
                .catch(function () {
                    showBoundaryFallback();
                });
        }
    </script>
@endsection
