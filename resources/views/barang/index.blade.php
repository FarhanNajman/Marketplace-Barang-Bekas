@extends('layouts.app')

@section('title', 'Jelajahi Barang')
@section('description', 'Jelajahi katalog barang bekas layak pakai di BARANTAU. Cari barang, filter kategori dan kecamatan, atau jual barangmu.')

@section('fullbleed')
    @php
        $statusFilter = $statusFilter ?? '';
        $hanyaBaruDitambahkan = $hanyaBaruDitambahkan ?? false;
        $provinsiId = $provinsiId ?? null;
        $kabupatenKotaId = $kabupatenKotaId ?? null;
        $kecamatanId = $kecamatanId ?? null;
        $urutkan = $urutkan ?? 'terbaru';
        $provinsiList = $provinsiList ?? collect();
        $kabupatenKotaList = $kabupatenKotaList ?? collect();
        $kecamatanFilterList = $kecamatanFilterList ?? collect();

        $adaFilterWilayah = !empty($provinsiId) || !empty($kabupatenKotaId) || !empty($kecamatanId);
        $adaFilterAktif = ($kataKunci !== '') || !empty($kategoriId) || $adaFilterWilayah || ($statusFilter !== '') || $hanyaBaruDitambahkan;

        $baseQuery = array_filter([
            'q' => $kataKunci !== '' ? $kataKunci : null,
            'kategori_id' => $kategoriId ?: null,
            'provinsi_id' => $provinsiId ?: null,
            'kabupaten_kota_id' => $kabupatenKotaId ?: null,
            'kecamatan_id' => $kecamatanId ?: null,
            'status' => $statusFilter !== '' ? $statusFilter : null,
            'baru' => $hanyaBaruDitambahkan ? '1' : null,
            'urutkan' => ($urutkan !== '' && $urutkan !== 'terbaru') ? $urutkan : null,
        ], fn ($val) => $val !== null && $val !== '');

        $kecamatanTerpilihObj = $kecamatanTerpilih ?? ($kecamatanId
            ? $kecamatanList->firstWhere('id', (int) $kecamatanId)
            : null);

        $ringkasanWilayahAktif = null;
        if (!empty($kecamatanTerpilihObj)) {
            $ringkasanWilayahAktif = $kecamatanTerpilihObj->labelHierarkiLengkap();
        } elseif (!empty($kabupatenTerpilih)) {
            $ringkasanWilayahAktif = $kabupatenTerpilih->labelHierarkiLengkap();
        } elseif (!empty($provinsiTerpilih)) {
            $ringkasanWilayahAktif = 'Provinsi ' . $provinsiTerpilih->nama_wilayah;
        }
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

        <!-- Filter Pencarian Berdasarkan Kecamatan (Bertingkat: Provinsi -> Kabupaten/Kota -> Kecamatan) & Urutan -->
        <div class="catalog-wilayah-filter-card" id="daftar-katalog-barang">
            <form method="GET" action="{{ route('beranda') }}" id="form-filter-wilayah" class="catalog-wilayah-filter-form">
                @if ($kataKunci !== '')
                    <input type="hidden" name="q" value="{{ $kataKunci }}">
                @endif
                @if (!empty($kategoriId))
                    <input type="hidden" name="kategori_id" value="{{ $kategoriId }}">
                @endif
                @if ($statusFilter !== '')
                    <input type="hidden" name="status" value="{{ $statusFilter }}">
                @endif
                @if ($hanyaBaruDitambahkan)
                    <input type="hidden" name="baru" value="1">
                @endif

                <div class="catalog-wilayah-filter-grid">
                    <div class="catalog-filter-field">
                        <label for="filter-provinsi-id" class="catalog-filter-label">1. Provinsi</label>
                        <select
                            id="filter-provinsi-id"
                            name="provinsi_id"
                            class="form-control catalog-filter-select"
                            aria-label="Filter berdasarkan Provinsi"
                        >
                            <option value="">Semua Provinsi</option>
                            @foreach ($provinsiList as $prov)
                                <option value="{{ $prov->id }}" {{ (int) $provinsiId === $prov->id ? 'selected' : '' }}>
                                    {{ $prov->nama_wilayah }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="catalog-filter-field">
                        <label for="filter-kabupaten-id" class="catalog-filter-label">2. Kabupaten / Kota</label>
                        <select
                            id="filter-kabupaten-id"
                            name="kabupaten_kota_id"
                            class="form-control catalog-filter-select"
                            aria-label="Filter berdasarkan Kabupaten atau Kota"
                            {{ empty($provinsiId) ? 'disabled' : '' }}
                        >
                            <option value="">{{ empty($provinsiId) ? 'Pilih Provinsi terlebih dahulu' : 'Semua Kabupaten / Kota' }}</option>
                            @foreach ($kabupatenKotaList as $kab)
                                <option value="{{ $kab->id }}" {{ (int) $kabupatenKotaId === $kab->id ? 'selected' : '' }}>
                                    {{ $kab->nama_wilayah }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="catalog-filter-field">
                        <label for="filter-kecamatan-id" class="catalog-filter-label">3. Kecamatan</label>
                        <select
                            id="filter-kecamatan-id"
                            name="kecamatan_id"
                            class="form-control catalog-filter-select"
                            aria-label="Filter berdasarkan Kecamatan"
                            {{ empty($kabupatenKotaId) ? 'disabled' : '' }}
                        >
                            <option value="">{{ empty($kabupatenKotaId) ? 'Pilih Kabupaten / Kota terlebih dahulu' : 'Semua Kecamatan' }}</option>
                            @foreach ($kecamatanFilterList as $kec)
                                <option value="{{ $kec->id }}" {{ (int) $kecamatanId === $kec->id ? 'selected' : '' }}>
                                    Kec. {{ $kec->nama_wilayah }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="catalog-filter-field">
                        <label for="filter-urutkan" class="catalog-filter-label">Urutkan Hasil</label>
                        <select
                            id="filter-urutkan"
                            name="urutkan"
                            class="form-control catalog-filter-select"
                            aria-label="Urutkan daftar barang"
                            onchange="this.form.submit()"
                        >
                            <option value="terbaru" {{ $urutkan === 'terbaru' ? 'selected' : '' }}>Terbaru</option>
                            <option value="terlama" {{ $urutkan === 'terlama' ? 'selected' : '' }}>Terlama</option>
                            <option value="harga_asc" {{ $urutkan === 'harga_asc' ? 'selected' : '' }}>Harga: Terendah ke Tertinggi</option>
                            <option value="harga_desc" {{ $urutkan === 'harga_desc' ? 'selected' : '' }}>Harga: Tertinggi ke Terendah</option>
                        </select>
                    </div>

                    <div class="catalog-filter-actions">
                        <button type="submit" class="btn catalog-filter-submit" id="btn-terapkan-filter-wilayah">
                            Terapkan Filter
                        </button>
                        @if ($adaFilterAktif || ($urutkan !== 'terbaru'))
                            <a href="{{ route('beranda') }}" class="quick-chip-reset" id="btn-reset-filter">
                                &times; Reset
                            </a>
                        @endif
                    </div>
                </div>

                @if ($ringkasanWilayahAktif || $kataKunci !== '' || !empty($kategoriId))
                    <div class="catalog-active-filter-bar" role="status">
                        <span class="catalog-active-filter-text">
                            Filter aktif:
                            @if ($kataKunci !== '')
                                <strong>"{{ $kataKunci }}"</strong>
                            @endif
                            @if (!empty($kategoriId))
                                @php
                                    $katAktif = $kategoriList->firstWhere('id', (int) $kategoriId);
                                @endphp
                                @if ($katAktif)
                                    <span>&bull; Kategori: <strong>{{ $katAktif->nama_kategori }}</strong></span>
                                @endif
                            @endif
                            @if ($ringkasanWilayahAktif)
                                <span>&bull; Lokasi: <strong>{{ $ringkasanWilayahAktif }}</strong></span>
                            @endif
                        </span>
                    </div>
                @endif
            </form>
        </div>

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

            @if ($daftarBarang->hasPages())
                <nav class="catalog-pagination" aria-label="Navigasi halaman katalog barang">
                    @if ($daftarBarang->onFirstPage())
                        <span class="pagination-btn disabled" aria-disabled="true">&lsaquo; Sebelumnya</span>
                    @else
                        <a href="{{ $daftarBarang->previousPageUrl() }}" class="pagination-btn" rel="prev">&lsaquo; Sebelumnya</a>
                    @endif

                    <div class="pagination-pages">
                        @foreach ($daftarBarang->getUrlRange(max(1, $daftarBarang->currentPage() - 2), min($daftarBarang->lastPage(), $daftarBarang->currentPage() + 2)) as $page => $url)
                            @if ($page == $daftarBarang->currentPage())
                                <span class="pagination-page active" aria-current="page">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="pagination-page">{{ $page }}</a>
                            @endif
                        @endforeach
                    </div>

                    @if ($daftarBarang->hasMorePages())
                        <a href="{{ $daftarBarang->nextPageUrl() }}" class="pagination-btn" rel="next">Berikutnya &rsaquo;</a>
                    @else
                        <span class="pagination-btn disabled" aria-disabled="true">Berikutnya &rsaquo;</span>
                    @endif
                </nav>
            @endif
        @else
            <div class="empty-listing-box">
                @if ($adaFilterAktif)
                    <p class="font-display" style="font-size: 1.4rem; font-weight: 600; color: var(--text);">
                        @if ($ringkasanWilayahAktif)
                            Belum ada barang yang ditemukan di {{ $ringkasanWilayahAktif }}.
                        @else
                            Tidak ada barang yang cocok dengan pencarian atau filter Anda.
                        @endif
                    </p>
                    <p style="margin-top: 0.4rem; margin-bottom: 1.25rem;">
                        @if ($hanyaBaruDitambahkan)
                            Belum ada barang yang diposting dalam 2 hari terakhir untuk filter ini. Coba tampilkan seluruh katalog.
                        @elseif ($ringkasanWilayahAktif)
                            Saat ini belum ada penjual yang memasang barang di wilayah <strong>{{ $ringkasanWilayahAktif }}</strong>{{ $kataKunci !== '' ? ' untuk kata kunci "' . $kataKunci . '"' : '' }}. Coba pilih kecamatan/kabupaten lain atau reset filter wilayah.
                        @else
                            Coba gunakan kata kunci lain atau tampilkan semua kategori, kecamatan, dan status.
                        @endif
                    </p>
                    <div style="display: flex; flex-wrap: wrap; justify-content: center; gap: 0.75rem;">
                        @if ($adaFilterWilayah)
                            <a href="{{ route('beranda', array_diff_key($baseQuery, ['provinsi_id' => '', 'kabupaten_kota_id' => '', 'kecamatan_id' => ''])) }}" class="button-primary">
                                Tampilkan Semua Wilayah
                            </a>
                        @endif
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

            // Pemilihan Wilayah Bertingkat: Provinsi -> Kabupaten/Kota -> Kecamatan
            const endpointSubWilayah = @json(route('wilayah.sub'));
            const provSelect = document.getElementById('filter-provinsi-id');
            const kabSelect = document.getElementById('filter-kabupaten-id');
            const kecSelect = document.getElementById('filter-kecamatan-id');

            function setSelectLoading(selectEl, placeholderText) {
                if (!selectEl) return;
                selectEl.innerHTML = '';
                const opt = document.createElement('option');
                opt.value = '';
                opt.textContent = placeholderText;
                selectEl.appendChild(opt);
                selectEl.disabled = true;
            }

            function populateSubWilayahSelect(selectEl, items, allLabel, prefixLabel) {
                if (!selectEl) return;
                selectEl.innerHTML = '';
                const defaultOpt = document.createElement('option');
                defaultOpt.value = '';
                defaultOpt.textContent = allLabel;
                selectEl.appendChild(defaultOpt);

                items.forEach(function (item) {
                    const opt = document.createElement('option');
                    opt.value = String(item.id);
                    opt.textContent = (prefixLabel ? prefixLabel + item.nama_wilayah : item.nama_wilayah);
                    selectEl.appendChild(opt);
                });

                selectEl.disabled = items.length === 0;
            }

            if (provSelect && kabSelect && kecSelect) {
                provSelect.addEventListener('change', function () {
                    const provId = provSelect.value;

                    // Reset pilihan Kabupaten/Kota & Kecamatan saat Provinsi berubah
                    setSelectLoading(kecSelect, 'Pilih Kabupaten / Kota terlebih dahulu');

                    // Sinkronkan juga pilihan kecamatan di header jika ada
                    const headerKecSelect = document.getElementById('header-kecamatan-id');
                    if (headerKecSelect) {
                        headerKecSelect.value = '';
                    }

                    if (!provId) {
                        setSelectLoading(kabSelect, 'Pilih Provinsi terlebih dahulu');
                        return;
                    }

                    setSelectLoading(kabSelect, 'Memuat Kabupaten / Kota...');
                    const url = endpointSubWilayah + '?id_induk=' + encodeURIComponent(provId) + '&jenis_wilayah=kabupaten_kota';

                    fetch(url, { headers: { 'Accept': 'application/json' } })
                        .then(function (res) { return res.ok ? res.json() : Promise.reject(); })
                        .then(function (payload) {
                            const list = Array.isArray(payload.data) ? payload.data : [];
                            populateSubWilayahSelect(kabSelect, list, 'Semua Kabupaten / Kota', '');
                        })
                        .catch(function () {
                            setSelectLoading(kabSelect, 'Gagal memuat Kabupaten / Kota');
                        });
                });

                kabSelect.addEventListener('change', function () {
                    const kabId = kabSelect.value;

                    const headerKecSelect = document.getElementById('header-kecamatan-id');
                    if (headerKecSelect) {
                        headerKecSelect.value = '';
                    }

                    if (!kabId) {
                        setSelectLoading(kecSelect, 'Pilih Kabupaten / Kota terlebih dahulu');
                        return;
                    }

                    setSelectLoading(kecSelect, 'Memuat Kecamatan...');
                    const url = endpointSubWilayah + '?id_induk=' + encodeURIComponent(kabId) + '&jenis_wilayah=kecamatan';

                    fetch(url, { headers: { 'Accept': 'application/json' } })
                        .then(function (res) { return res.ok ? res.json() : Promise.reject(); })
                        .then(function (payload) {
                            const list = Array.isArray(payload.data) ? payload.data : [];
                            populateSubWilayahSelect(kecSelect, list, 'Semua Kecamatan', 'Kec. ');
                        })
                        .catch(function () {
                            setSelectLoading(kecSelect, 'Gagal memuat Kecamatan');
                        });
                });
            }
        })();
    </script>
@endsection
