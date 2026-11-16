<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="@yield('description', 'BARANTAU — Marketplace barang bekas layak pakai di sekitarmu.')">
    <title>@yield('title', 'Jelajahi Barang') | BARANTAU</title>
    <!-- Skrip Anti-Flicker: terapkan tema tersimpan sebelum halaman dirender (default: light) -->
    <script>
        (function () {
            try {
                var savedTheme = localStorage.getItem('barantau_theme');
                var activeTheme = (savedTheme === 'dark' || savedTheme === 'light') ? savedTheme : 'light';
                document.documentElement.setAttribute('data-theme', activeTheme);
            } catch (e) {
                document.documentElement.setAttribute('data-theme', 'light');
            }
        })();
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    @php
        $navKategoriList = isset($kategoriList)
            ? $kategoriList
            : \App\Models\Kategori::orderBy('nama_kategori')->get();

        $navKecamatanList = isset($kecamatanList)
            ? $kecamatanList
            : \App\Models\Wilayah::with('induk.induk')
                ->where('jenis_wilayah', 'kecamatan')
                ->orderBy('nama_wilayah')
                ->get();

        $navKataKunci = $kataKunci ?? request('q', '');
        $navKategoriId = $kategoriId ?? request('kategori_id');
        $navKecamatanId = $kecamatanId ?? request('kecamatan_id');
        $navStatusFilter = $statusFilter ?? request('status', '');
        $navBaruFilter = $hanyaBaruDitambahkan ?? in_array((string) request('baru', ''), ['1', 'true'], true);

        $isHalamanKatalogUtama = request()->routeIs('beranda', 'barang.index');

        // Tentukan URL tujuan kembali yang aman jika riwayat berasal dari luar website atau kosong
        $defaultFallbackUrl = route('beranda');
        if (request()->routeIs('profil.edit') && auth()->check()) {
            $defaultFallbackUrl = route('profil.show', ['username' => auth()->user()->username]);
        } elseif (request()->routeIs('barang.edit') && isset($barang)) {
            $defaultFallbackUrl = route('barang.show', $barang);
        }

        $prevUrl = url()->previous();
        $appHost = request()->getSchemeAndHttpHost();
        $hasInternalPrevious = $prevUrl
            && str_starts_with($prevUrl, $appHost)
            && $prevUrl !== url()->current()
            && ! str_contains($prevUrl, '/login')
            && ! str_contains($prevUrl, '/register');

        $resolvedBackHref = $hasInternalPrevious ? $prevUrl : $defaultFallbackUrl;
    @endphp

    <header class="site-header">
        <!-- Tombol Ikon 3 Garis di Sudut Kiri Atas Web -->
        <div class="nav-drawer-wrap" id="nav-drawer-wrap">
            <button
                type="button"
                class="hamburger-nav-btn"
                id="hamburger-nav-btn"
                aria-label="Buka menu navigasi dan pengaturan tema"
                aria-expanded="false"
                aria-controls="hamburger-nav-panel"
                onclick="toggleHamburgerNav(event)"
                title="Menu Navigasi &amp; Mode Tampilan"
            >
                <span class="hamburger-three-lines" aria-hidden="true">
                    <span></span>
                    <span></span>
                    <span></span>
                </span>
            </button>

            <!-- Panel Navigasi & Pengalih Tema di Dalam Tombol Ikon 3 Garis -->
            <div class="hamburger-nav-panel" id="hamburger-nav-panel" role="menu" aria-label="Menu Navigasi Utama">
                <div class="hamburger-panel-section">
                    <p class="hamburger-panel-label">Navigasi</p>
                    <a href="{{ route('beranda') }}" class="hamburger-nav-item {{ request()->routeIs('beranda', 'barang.index') ? 'active' : '' }}" role="menuitem">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <rect x="3" y="3" width="7" height="7" rx="1"/>
                            <rect x="14" y="3" width="7" height="7" rx="1"/>
                            <rect x="14" y="14" width="7" height="7" rx="1"/>
                            <rect x="3" y="14" width="7" height="7" rx="1"/>
                        </svg>
                        <span>Jelajahi Katalog</span>
                    </a>
                    <a href="{{ route('wishlist.index') }}" class="hamburger-nav-item {{ request()->routeIs('wishlist.*') ? 'active' : '' }}" role="menuitem">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M12 20.5s-7.5-4.6-9.5-9A5.5 5.5 0 0 1 12 6a5.5 5.5 0 0 1 9.5 5.5c-2 4.4-9.5 9-9.5 9Z"/>
                        </svg>
                        <span>Wishlist</span>
                    </a>
                    @auth
                        <a href="{{ route('barang.create') }}" class="hamburger-nav-item {{ request()->routeIs('barang.create') ? 'active' : '' }}" role="menuitem">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="M12 5v14M5 12h14"/>
                            </svg>
                            <span>Jual Barang</span>
                        </a>
                        <a href="{{ route('profil.show', ['username' => auth()->user()->username]) }}" class="hamburger-nav-item {{ request()->routeIs('profil.*') ? 'active' : '' }}" role="menuitem">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <circle cx="12" cy="8" r="4"/>
                                <path d="M4 20c1.8-3.8 5-6 8-6s6.2 2.2 8 6"/>
                            </svg>
                            <span>Profil Saya ({{ '@' . auth()->user()->username }})</span>
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="hamburger-nav-item {{ request()->routeIs('login') ? 'active' : '' }}" role="menuitem">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4M10 17l5-5-5-5M15 12H3"/>
                            </svg>
                            <span>Masuk</span>
                        </a>
                        <a href="{{ route('register') }}" class="hamburger-nav-item {{ request()->routeIs('register') ? 'active' : '' }}" role="menuitem">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <circle cx="9" cy="7" r="4"/>
                                <path d="M3 21v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2M19 8v6M16 11h6"/>
                            </svg>
                            <span>Daftar Akun</span>
                        </a>
                    @endauth
                    <a href="{{ route('tentang') }}" class="hamburger-nav-item {{ request()->routeIs('tentang') ? 'active' : '' }}" role="menuitem">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <circle cx="12" cy="12" r="10"/>
                            <path d="M12 16v-4M12 8h.01"/>
                        </svg>
                        <span>Tentang Barantau</span>
                    </a>
                </div>

                <div class="hamburger-panel-divider"></div>

                <!-- Tombol Mode Tampilan Web (Mode Terang / Mode Gelap) di dalam Menu Ikon 3 Garis -->
                <div class="hamburger-panel-section">
                    <p class="hamburger-panel-label">Mode Tampilan</p>
                    <button
                        type="button"
                        id="btn-theme-toggle"
                        class="theme-toggle-btn hamburger-theme-btn"
                        onclick="toggleBarantauTheme()"
                        aria-label="Alihkan ke mode gelap"
                        aria-pressed="false"
                    >
                        <span class="hamburger-theme-left">
                            <svg class="theme-icon-moon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
                            </svg>
                            <svg class="theme-icon-sun" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <circle cx="12" cy="12" r="5"/>
                                <path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/>
                            </svg>
                            <span class="theme-toggle-label" data-theme-label>Beralih ke Mode Gelap</span>
                        </span>
                        <span class="hamburger-theme-badge" data-theme-status>Aktif: Terang</span>
                    </button>
                </div>

                @auth
                    <div class="hamburger-panel-divider"></div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="hamburger-nav-item hamburger-logout-btn">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>
                            </svg>
                            <span>Keluar</span>
                        </button>
                    </form>
                @endauth
            </div>
        </div>

        <div class="site-container header-with-corner-menu">
            <!-- Baris Utama Header: Logo BARANTAU, Pencarian + Kategori + Kecamatan, Wishlist (Logo Love), Masuk/Daftar, Jual Barang -->
            <div class="header-marketplace-top">
                <!-- Logo / Nama BARANTAU: Gambar Logo V1/V2 di atas + Tulisan Wordmark BARANTAU di bawahnya -->
                <a href="{{ route('beranda') }}" class="brand-logo" id="nav-brand" title="Kembali ke Katalog BARANTAU" aria-label="BARANTAU - Kembali ke Katalog">
                    <img
                        src="{{ asset('Assets/LOGO/LOGO BARANTAU V1 RMV.png') }}"
                        alt="Logo BARANTAU"
                        class="brand-logo-img logo-light"
                    >
                    <img
                        src="{{ asset('Assets/LOGO/LOGO BARANTAU V2 RMV.png') }}"
                        alt="Logo BARANTAU"
                        class="brand-logo-img logo-dark"
                    >
                    <span class="brand-wordmark" aria-hidden="true">BA<span class="brand-r">R</span><span class="brand-after-r">ANTAU</span></span>
                </a>

                <!-- Form Pencarian Barang, Pilihan Kategori, & Pilihan Lokasi Kecamatan di Header -->
                <form method="GET" action="{{ route('beranda') }}" class="header-search-form" id="form-pencarian-utama">
                    @if ($navStatusFilter !== '')
                        <input type="hidden" name="status" value="{{ $navStatusFilter }}">
                    @endif
                    @if ($navBaruFilter)
                        <input type="hidden" name="baru" value="1">
                    @endif

                    <div class="header-field-group">
                        <label for="header-q" class="header-field-label">Cari Barang</label>
                        <div class="header-search-input-wrap">
                            <svg class="header-search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <circle cx="11" cy="11" r="7"/>
                                <path d="m20 20-3.5-3.5"/>
                            </svg>
                            <input
                                type="search"
                                id="header-q"
                                name="q"
                                class="form-control header-search-field"
                                value="{{ $navKataKunci }}"
                                aria-label="Cari nama barang"
                            >
                        </div>
                    </div>

                    <div class="header-field-group header-filter-select-wrap">
                        <label for="header-kategori-id" class="header-field-label">Kategori</label>
                        <select id="header-kategori-id" name="kategori_id" class="form-control header-select-field" aria-label="Pilih Kategori">
                            <option value="">Semua Kategori</option>
                            @foreach ($navKategoriList as $kat)
                                <option value="{{ $kat->id }}" {{ (int) $navKategoriId === $kat->id ? 'selected' : '' }}>
                                    {{ $kat->nama_kategori }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="header-field-group header-filter-wilayah-wrap">
                        <label for="header-kecamatan-id" class="header-field-label">Lokasi Kecamatan</label>
                        <select
                            id="header-kecamatan-id"
                            name="kecamatan_id"
                            class="form-control header-select-field"
                            data-searchable-wilayah
                            aria-label="Pilih Kecamatan"
                        >
                            <option value="">Semua Kecamatan</option>
                            @foreach ($navKecamatanList as $kec)
                                <option
                                    value="{{ $kec->id }}"
                                    data-provinsi="{{ $kec->namaProvinsi() }}"
                                    {{ (int) $navKecamatanId === $kec->id ? 'selected' : '' }}
                                >
                                    {{ $kec->labelLengkap() }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <button type="submit" class="btn header-search-submit" id="btn-cari-header">
                        Cari
                    </button>
                </form>

                <!-- Tautan Wishlist, Masuk/Daftar (atau Profil), & Tombol Jual Barang -->
                <div class="header-actions">
                    <a
                        id="nav-wishlist"
                        href="{{ route('wishlist.index') }}"
                        class="header-wishlist-link {{ request()->routeIs('wishlist.*') ? 'active' : '' }}"
                        title="Lihat Wishlist"
                        aria-label="Lihat Wishlist"
                    >
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path d="M12 20.5s-7.5-4.6-9.5-9A5.5 5.5 0 0 1 12 6a5.5 5.5 0 0 1 9.5 5.5c-2 4.4-9.5 9-9.5 9Z"/>
                        </svg>
                    </a>

                    @guest
                        <a id="nav-login" href="{{ route('login') }}" class="nav-link {{ request()->routeIs('login') ? 'active' : '' }}">
                            Masuk
                        </a>
                        <a id="nav-register" href="{{ route('register') }}" class="button-outline header-btn-compact">
                            Daftar
                        </a>
                        <a id="nav-pasarkan" href="{{ route('login') }}" class="button-primary header-btn-sell">
                            Jual Barang
                        </a>
                    @else
                        <a
                            id="nav-profil"
                            href="{{ route('profil.show', ['username' => auth()->user()->username]) }}"
                            class="nav-user-actor {{ request()->routeIs('profil.*') ? 'active' : '' }}"
                            title="Lihat Profil {{ '@' . auth()->user()->username }}"
                        >
                            <span class="nav-user-actor-icon" aria-hidden="true">
                                @if (auth()->user()->urlFotoProfil())
                                    <img src="{{ auth()->user()->urlFotoProfil() }}" alt="Foto profil {{ auth()->user()->username }}">
                                @else
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="8" r="4"/>
                                        <path d="M4 20c1.8-3.8 5-6 8-6s6.2 2.2 8 6"/>
                                    </svg>
                                @endif
                            </span>
                            <span class="nav-user-actor-name">{{ auth()->user()->username }}</span>
                        </a>
                        <form method="POST" action="{{ route('logout') }}" style="display: inline;">
                            @csrf
                            <button type="submit" id="btn-logout" class="button-outline header-btn-compact">
                                Keluar
                            </button>
                        </form>
                        <a id="nav-pasarkan" href="{{ route('barang.create') }}" class="button-primary header-btn-sell">
                            Jual Barang
                        </a>
                    @endguest
                </div>

                <!-- Aksi Cepat Mobile (Tombol Jual Barang di Kanan Atas Ponsel) -->
                <div class="mobile-header-controls">
                    @auth
                        <a href="{{ route('barang.create') }}" class="button-primary header-btn-sell">
                            Jual Barang
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="button-primary header-btn-sell">
                            Jual Barang
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </header>

    <main style="flex: 1;">
        @hasSection('fullbleed')
            @yield('fullbleed')
        @else
            <div class="site-container page-section">
                @if (! $isHalamanKatalogUtama && ! View::hasSection('custom_back_bar'))
                    <div class="page-back-bar">
                        <a
                            href="{{ $resolvedBackHref }}"
                            data-fallback="{{ $defaultFallbackUrl }}"
                            onclick="return goBackBarantau(event, this)"
                            class="btn-back-barantau"
                            id="btn-kembali-halaman"
                            aria-label="Kembali ke halaman sebelumnya"
                        >
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M19 12H5M12 19l-7-7 7-7"/>
                            </svg>
                            <span>Kembali</span>
                        </a>
                    </div>
                @endif

                @yield('content')
            </div>
        @endif
    </main>

    <footer id="tentang-footer" class="site-footer">
        <div class="site-container footer-inner">
            <div class="footer-grid">
                <div>
                    <p class="footer-brand">BARANTAU</p>
                    <p style="margin-top: 0.75rem; max-width: 24rem; font-size: 0.875rem; line-height: 1.5rem;">
                        Marketplace barang bekas layak pakai di sekitarmu. Cari cepat, pilih dekat, transaksi langsung.
                    </p>
                </div>
                <div style="font-size: 0.875rem;">
                    <p style="margin-bottom: 0.75rem; font-weight: 700;">Katalog</p>
                    <a href="{{ route('beranda') }}" style="display: block; padding: 0.3rem 0;">Jelajahi Barang</a>
                    <a href="{{ route('beranda', ['baru' => 1]) }}" style="display: block; padding: 0.3rem 0;">Baru Ditambahkan</a>
                    <a href="{{ route('wishlist.index') }}" style="display: block; padding: 0.3rem 0;">Wishlist</a>
                    <a href="{{ route('tentang') }}" style="display: block; padding: 0.3rem 0;">Tentang Barantau</a>
                </div>
                <div style="font-size: 0.875rem;">
                    <p style="margin-bottom: 0.75rem; font-weight: 700;">Akun &amp; Penjualan</p>
                    @guest
                        <a href="{{ route('login') }}" style="display: block; padding: 0.3rem 0;">Masuk</a>
                        <a href="{{ route('register') }}" style="display: block; padding: 0.3rem 0;">Daftar</a>
                        <a href="{{ route('login') }}" style="display: block; padding: 0.3rem 0;">Jual Barang</a>
                    @else
                        <a href="{{ route('barang.create') }}" style="display: block; padding: 0.3rem 0;">Jual Barang</a>
                        <a href="{{ route('profil.show', ['username' => auth()->user()->username]) }}" style="display: block; padding: 0.3rem 0;">Profil Saya</a>
                    @endguest
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; {{ date('Y') }} BARANTAU &mdash; Marketplace Barang Bekas</p>
                <p>Lokasi aman hanya sampai tingkat kecamatan.</p>
            </div>
        </div>
    </footer>

    <script>
        // Buka/tutup menu tombol ikon 3 garis di sudut kiri atas
        function toggleHamburgerNav(event) {
            if (event) event.stopPropagation();
            const panel = document.getElementById('hamburger-nav-panel');
            const btn = document.getElementById('hamburger-nav-btn');
            if (!panel || !btn) return;
            const isOpen = panel.classList.toggle('open');
            btn.classList.toggle('open', isOpen);
            btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        }

        function closeHamburgerNav() {
            const panel = document.getElementById('hamburger-nav-panel');
            const btn = document.getElementById('hamburger-nav-btn');
            if (!panel || !btn) return;
            panel.classList.remove('open');
            btn.classList.remove('open');
            btn.setAttribute('aria-expanded', 'false');
        }

        document.addEventListener('click', function (e) {
            const wrap = document.getElementById('nav-drawer-wrap');
            if (wrap && !wrap.contains(e.target)) {
                closeHamburgerNav();
            }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeHamburgerNav();
            }
        });

        // Pengelolaan Mode Terang / Mode Gelap
        function syncThemeToggleControls(theme) {
            const isDark = theme === 'dark';
            const buttons = document.querySelectorAll('.theme-toggle-btn');
            buttons.forEach(function (btn) {
                btn.setAttribute('aria-pressed', isDark ? 'true' : 'false');
                btn.setAttribute('aria-label', isDark ? 'Alihkan ke mode terang' : 'Alihkan ke mode gelap');
                const labelEl = btn.querySelector('[data-theme-label]');
                if (labelEl) {
                    labelEl.textContent = isDark ? 'Beralih ke Mode Terang' : 'Beralih ke Mode Gelap';
                }
                const statusEl = btn.querySelector('[data-theme-status]');
                if (statusEl) {
                    statusEl.textContent = isDark ? 'Aktif: Gelap' : 'Aktif: Terang';
                }
            });
        }

        function toggleBarantauTheme() {
            const current = document.documentElement.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
            const next = current === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', next);
            try {
                localStorage.setItem('barantau_theme', next);
            } catch (e) {}
            syncThemeToggleControls(next);
        }

        // Fungsi Tombol Kembali Pintar:
        // Jika pengguna datang dari halaman sebelumnya di dalam website ini, arahkan kembali ke halaman tersebut.
        // Jika riwayat kosong atau berasal dari luar website, arahkan ke fallback yang sesuai (misal Katalog Barang).
        function goBackBarantau(event, anchorEl) {
            if (!anchorEl) return true;
            const fallbackUrl = anchorEl.getAttribute('data-fallback') || '{{ route('beranda') }}';
            const ref = document.referrer || '';
            const sameOrigin = ref.indexOf(window.location.origin) === 0;

            if (sameOrigin && window.history.length > 1 && ref !== window.location.href) {
                event.preventDefault();
                window.history.back();
                return false;
            }

            anchorEl.href = fallbackUrl;
            return true;
        }

        // Inisialisasi status tombol tema & pencarian wilayah (kecamatan)
        document.addEventListener('DOMContentLoaded', function () {
            const initialTheme = document.documentElement.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
            syncThemeToggleControls(initialTheme);

            const selects = document.querySelectorAll('select[data-searchable-wilayah]');
            selects.forEach(function (selectEl) {
                if (selectEl.disabled) return;

                const allOptions = Array.from(selectEl.options).map(function (opt) {
                    const label = opt.textContent.trim();
                    const provinsi = (opt.getAttribute('data-provinsi') || '').trim();
                    return {
                        value: opt.value,
                        label: label,
                        provinsi: provinsi,
                        searchLower: (label + ' ' + provinsi).toLowerCase(),
                    };
                });

                const emptyOption = allOptions.find(function (o) { return o.value === ''; }) || { value: '', label: 'Semua Kecamatan', provinsi: '', searchLower: '' };
                const dataOptions = allOptions.filter(function (o) { return o.value !== ''; });
                if (dataOptions.length === 0) return;

                const wrapper = document.createElement('div');
                wrapper.className = 'wilayah-combobox';

                const inputWrap = document.createElement('div');
                inputWrap.className = 'wilayah-input-wrap';

                const searchIcon = document.createElement('span');
                searchIcon.className = 'wilayah-search-icon';
                searchIcon.setAttribute('aria-hidden', 'true');
                searchIcon.innerHTML = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21s-7-4.5-7-11a7 7 0 1 1 14 0c0 6.5-7 11-7 11Z"/><circle cx="12" cy="10" r="2.5"/></svg>';

                const input = document.createElement('input');
                input.type = 'text';
                input.className = 'form-control wilayah-search-input';
                input.autocomplete = 'off';
                input.setAttribute('aria-label', 'Cari dan pilih kecamatan');

                const actions = document.createElement('div');
                actions.className = 'wilayah-actions';

                const clearBtn = document.createElement('button');
                clearBtn.type = 'button';
                clearBtn.className = 'wilayah-clear-btn';
                clearBtn.title = 'Hapus pilihan kecamatan';
                clearBtn.setAttribute('aria-label', 'Hapus pilihan kecamatan');
                clearBtn.innerHTML = '&times;';

                const chevronBtn = document.createElement('button');
                chevronBtn.type = 'button';
                chevronBtn.className = 'wilayah-chevron-btn';
                chevronBtn.tabIndex = -1;
                chevronBtn.setAttribute('aria-hidden', 'true');
                chevronBtn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>';

                actions.appendChild(clearBtn);
                actions.appendChild(chevronBtn);

                inputWrap.appendChild(searchIcon);
                inputWrap.appendChild(input);
                inputWrap.appendChild(actions);

                const dropdown = document.createElement('div');
                dropdown.className = 'wilayah-dropdown';
                dropdown.setAttribute('role', 'listbox');

                wrapper.appendChild(inputWrap);
                wrapper.appendChild(dropdown);

                selectEl.parentNode.insertBefore(wrapper, selectEl);
                selectEl.style.display = 'none';

                let activeIndex = -1;
                let renderedItems = [];

                function escapeHtml(str) {
                    return str.replace(/[&<>"']/g, function (c) {
                        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
                    });
                }

                function highlightText(text, terms) {
                    let safe = escapeHtml(text);
                    if (!terms.length) return safe;
                    terms.forEach(function (term) {
                        if (!term) return;
                        const escapedTerm = term.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
                        const regex = new RegExp('(' + escapedTerm + ')', 'ig');
                        safe = safe.replace(regex, '<mark>$1</mark>');
                    });
                    return safe;
                }

                function syncFromSelect() {
                    const currentVal = selectEl.value;
                    const found = dataOptions.find(function (o) { return o.value === currentVal; });
                    if (found) {
                        input.value = found.label;
                        clearBtn.classList.add('visible');
                    } else {
                        input.value = '';
                        clearBtn.classList.remove('visible');
                    }
                }

                function selectOption(opt) {
                    selectEl.value = opt.value;
                    selectEl.dispatchEvent(new Event('change', { bubbles: true }));
                    syncFromSelect();
                    closeDropdown();
                }

                function renderDropdown(queryText) {
                    const terms = queryText.trim().toLowerCase().split(/\s+/).filter(Boolean);
                    let matches = dataOptions;
                    if (terms.length > 0) {
                        matches = dataOptions.filter(function (item) {
                            return terms.every(function (t) { return item.searchLower.indexOf(t) !== -1; });
                        });
                    }

                    const maxRender = 60;
                    const slice = matches.slice(0, maxRender);
                    renderedItems = [];
                    dropdown.innerHTML = '';

                    if (terms.length === 0 && emptyOption) {
                        const resetBtn = document.createElement('button');
                        resetBtn.type = 'button';
                        resetBtn.className = 'wilayah-option' + (selectEl.value === '' ? ' selected' : '');
                        resetBtn.innerHTML = '<span class="wilayah-option-main">' + escapeHtml(emptyOption.label) + '</span>';
                        resetBtn.addEventListener('mousedown', function (e) {
                            e.preventDefault();
                            selectOption({ value: '', label: '', provinsi: '' });
                        });
                        dropdown.appendChild(resetBtn);
                        renderedItems.push({ el: resetBtn, opt: { value: '', label: '', provinsi: '' } });
                    }

                    if (slice.length === 0) {
                        const emptyMsg = document.createElement('div');
                        emptyMsg.className = 'wilayah-dropdown-meta';
                        emptyMsg.textContent = 'Kecamatan tidak ditemukan. Coba kata kunci kecamatan atau kota/kabupaten lain.';
                        dropdown.appendChild(emptyMsg);
                        activeIndex = -1;
                        return;
                    }

                    slice.forEach(function (opt) {
                        const btn = document.createElement('button');
                        btn.type = 'button';
                        btn.className = 'wilayah-option' + (selectEl.value === opt.value ? ' selected' : '');
                        const mainHtml = '<span class="wilayah-option-main">' + highlightText(opt.label, terms) + '</span>';
                        const provHtml = opt.provinsi ? '<span class="wilayah-option-prov">' + escapeHtml(opt.provinsi) + '</span>' : '';
                        btn.innerHTML = mainHtml + provHtml;

                        btn.addEventListener('mousedown', function (e) {
                            e.preventDefault();
                            selectOption(opt);
                        });

                        dropdown.appendChild(btn);
                        renderedItems.push({ el: btn, opt: opt });
                    });

                    const meta = document.createElement('div');
                    meta.className = 'wilayah-dropdown-meta';
                    if (matches.length > maxRender) {
                        meta.textContent = 'Menampilkan ' + maxRender + ' dari ' + matches.length + ' kecamatan. Ketik lebih spesifik untuk mempersempit.';
                    } else {
                        meta.textContent = 'Menampilkan ' + matches.length + ' kecamatan.';
                    }
                    dropdown.appendChild(meta);
                    activeIndex = -1;
                }

                function openDropdown(queryOverride) {
                    const q = typeof queryOverride === 'string' ? queryOverride : '';
                    renderDropdown(q);
                    dropdown.classList.add('open');
                }

                function closeDropdown() {
                    dropdown.classList.remove('open');
                    activeIndex = -1;
                }

                function updateActiveItem() {
                    renderedItems.forEach(function (item, idx) {
                        if (idx === activeIndex) {
                            item.el.classList.add('active');
                            item.el.scrollIntoView({ block: 'nearest' });
                        } else {
                            item.el.classList.remove('active');
                        }
                    });
                }

                input.addEventListener('focus', function () {
                    input.select();
                    openDropdown('');
                });

                input.addEventListener('click', function () {
                    if (!dropdown.classList.contains('open')) {
                        openDropdown('');
                    }
                });

                input.addEventListener('input', function () {
                    if (input.value.trim() !== '') {
                        clearBtn.classList.add('visible');
                    } else if (!selectEl.value) {
                        clearBtn.classList.remove('visible');
                    }
                    openDropdown(input.value);
                });

                input.addEventListener('keydown', function (e) {
                    if (!dropdown.classList.contains('open')) {
                        if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                            openDropdown(input.value);
                            e.preventDefault();
                        }
                        return;
                    }

                    if (e.key === 'ArrowDown') {
                        e.preventDefault();
                        if (renderedItems.length > 0) {
                            activeIndex = (activeIndex + 1) % renderedItems.length;
                            updateActiveItem();
                        }
                    } else if (e.key === 'ArrowUp') {
                        e.preventDefault();
                        if (renderedItems.length > 0) {
                            activeIndex = (activeIndex - 1 + renderedItems.length) % renderedItems.length;
                            updateActiveItem();
                        }
                    } else if (e.key === 'Enter') {
                        if (activeIndex >= 0 && renderedItems[activeIndex]) {
                            e.preventDefault();
                            selectOption(renderedItems[activeIndex].opt);
                        } else if (renderedItems.length === 1) {
                            e.preventDefault();
                            selectOption(renderedItems[0].opt);
                        }
                    } else if (e.key === 'Escape') {
                        closeDropdown();
                        syncFromSelect();
                    }
                });

                input.addEventListener('blur', function () {
                    setTimeout(function () {
                        closeDropdown();
                        if (input.value.trim() === '') {
                            selectEl.value = '';
                        }
                        syncFromSelect();
                    }, 150);
                });

                clearBtn.addEventListener('mousedown', function (e) {
                    e.preventDefault();
                    selectEl.value = '';
                    selectEl.dispatchEvent(new Event('change', { bubbles: true }));
                    input.value = '';
                    clearBtn.classList.remove('visible');
                    input.focus();
                    openDropdown('');
                });

                chevronBtn.addEventListener('mousedown', function (e) {
                    e.preventDefault();
                    if (dropdown.classList.contains('open')) {
                        closeDropdown();
                    } else {
                        input.focus();
                        openDropdown('');
                    }
                });

                syncFromSelect();
            });
        });
    </script>
</body>
</html>
