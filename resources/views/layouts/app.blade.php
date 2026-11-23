<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
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
    @php
        $gaMeasurementId = trim((string) config('services.google_analytics.measurement_id', ''));
    @endphp
    @if ($gaMeasurementId !== '')
        <!-- Google Analytics Opsional (Tanpa mengirim isi pesan, nomor HP, email, atau data pribadi) -->
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ urlencode($gaMeasurementId) }}"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', @json($gaMeasurementId), {
                anonymize_ip: true,
                allow_google_signals: false,
                allow_ad_personalization_signals: false,
                page_location: window.location.origin + window.location.pathname,
                page_title: document.title
            });
        </script>
    @endif
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
        $navUrutkan = $urutkan ?? request('urutkan', 'terbaru');

        $isHalamanKatalogUtama = request()->routeIs('beranda', 'barang.index');

        // Hitung pesan chat belum dibaca khusus untuk room di mana pengguna menjadi peserta
        $navUnreadChatCount = 0;
        $navUnreadChatItems = collect();
        $navLatestUnreadMsgId = 0;
        $navInitialNotifPayload = [];

        if (auth()->check()) {
            $authUserId = (int) auth()->id();
            $unreadQuery = \App\Models\Pesan::with(['pengirim', 'roomChat.barang'])
                ->where('pengirim_id', '!=', $authUserId)
                ->whereNull('dibaca_pada')
                ->whereHas('roomChat', function ($q) use ($authUserId) {
                    $q->where('pembeli_id', $authUserId)
                        ->orWhere('penjual_id', $authUserId);
                });

            $navUnreadChatCount = (clone $unreadQuery)->count();
            $navUnreadChatItems = (clone $unreadQuery)
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->limit(6)
                ->get();
            $navLatestUnreadMsgId = (int) ($navUnreadChatItems->first()?->id ?? 0);
            $navInitialNotifPayload = $navUnreadChatItems->map(function ($p) {
                return [
                    'id' => (int) $p->id,
                    'room_chat_id' => (int) $p->room_chat_id,
                    'room_url' => route('chat.show', $p->room_chat_id),
                    'pengirim_nama' => $p->pengirim?->name ?? 'Pengguna',
                    'nama_barang' => $p->roomChat?->barang?->nama_barang ?? 'Barang',
                    'cuplikan_pesan' => \Illuminate\Support\Str::limit($p->isi_pesan, 70),
                    'waktu' => $p->created_at?->diffForHumans() ?? 'Baru saja',
                ];
            })->values()->all();
        }

        // Tentukan URL tujuan kembali yang aman jika riwayat berasal dari luar website atau kosong
        $defaultFallbackUrl = route('beranda');
        if (request()->routeIs('profil.edit') && auth()->check()) {
            $defaultFallbackUrl = route('profil.show', ['username' => auth()->user()->username]);
        } elseif (request()->routeIs('barang.edit') && isset($barang)) {
            $defaultFallbackUrl = route('barang.show', $barang);
        } elseif (request()->routeIs('chat.show')) {
            $defaultFallbackUrl = route('chat.index');
        }

        $prevUrl = url()->previous();
        $appHost = request()->getSchemeAndHttpHost();
        $isHalamanDaftarChat = request()->routeIs('chat.index');
        $hasInternalPrevious = ! $isHalamanDaftarChat
            && $prevUrl
            && str_starts_with($prevUrl, $appHost)
            && $prevUrl !== url()->current()
            && ! str_contains($prevUrl, '/login')
            && ! str_contains($prevUrl, '/register')
            && ! str_contains($prevUrl, '/chat');

        $resolvedBackHref = $hasInternalPrevious ? $prevUrl : $defaultFallbackUrl;
    @endphp

    @if (View::hasSection('chat_room_header'))
        @yield('chat_room_header')
    @else
    <header class="site-header" id="site-header">
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
                        <span>Beranda</span>
                    </a>
                    <a href="{{ route('wishlist.index') }}" class="hamburger-nav-item {{ request()->routeIs('wishlist.*') ? 'active' : '' }}" role="menuitem">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M12 20.5s-7.5-4.6-9.5-9A5.5 5.5 0 0 1 12 6a5.5 5.5 0 0 1 9.5 5.5c-2 4.4-9.5 9-9.5 9Z"/>
                        </svg>
                        <span>Wishlist</span>
                    </a>
                    @auth
                        <a href="{{ route('chat.index') }}" class="hamburger-nav-item {{ request()->routeIs('chat.*') ? 'active' : '' }}" role="menuitem">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                            </svg>
                            <span>Room Chat</span>
                            @if ($navUnreadChatCount > 0)
                                <span class="chat-unread-pill" style="margin-left: auto;">{{ $navUnreadChatCount }}</span>
                            @endif
                        </a>
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
                        <span>Tentang BARANTAU</span>
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
                        role="switch"
                        onclick="toggleBarantauTheme()"
                        aria-label="Mode Terang, klik untuk beralih ke mode gelap"
                        aria-checked="false"
                    >
                        <span class="theme-switch-icon" aria-hidden="true">
                            <svg class="theme-icon-sun" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="5"/>
                                <path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/>
                            </svg>
                            <svg class="theme-icon-moon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
                            </svg>
                        </span>
                        <span class="theme-switch-track" aria-hidden="true">
                            <span class="theme-switch-thumb"></span>
                        </span>
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
                    @if ($navUrutkan && $navUrutkan !== 'terbaru')
                        <input type="hidden" name="urutkan" value="{{ $navUrutkan }}">
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

                    <div class="header-search-actions">
                        <button type="submit" class="btn header-search-submit" id="btn-cari-header">
                            Cari
                        </button>
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

                        <!-- Tombol On/Off Push Notification Tepat di Samping Tombol Favorit (Hanya Mengatur Push Notification, Tidak Membuka Daftar Chat) -->
                        <div class="header-notif-wrap" id="header-notif-wrap">
                            @auth
                                <button
                                    type="button"
                                    id="btn-push-notif-toggle"
                                    class="header-push-toggle-btn"
                                    data-chat-notif-toggle
                                    role="switch"
                                    aria-checked="false"
                                    onclick="toggleBarantauChatNotif(event)"
                                    title="Aktifkan atau nonaktifkan Push Notification pesan chat di perangkat ini"
                                    aria-label="Tombol On/Off Push Notification pesan chat"
                                >
                                    <span class="push-toggle-icon-wrap" aria-hidden="true">
                                        <!-- Ikon Lonceng Aktif (ON) -->
                                        <svg class="push-bell-icon-on" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                                            <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                                        </svg>
                                        <!-- Ikon Lonceng Nonaktif (OFF) -->
                                        <svg class="push-bell-icon-off" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                                            <path d="M18.63 13A17.89 17.89 0 0 1 18 8"/>
                                            <path d="M6.26 6.26A5.86 5.86 0 0 0 6 8c0 7-3 9-3 9h14"/>
                                            <path d="M18 8a6 6 0 0 0-9.33-5"/>
                                            <line x1="1" y1="1" x2="23" y2="23"/>
                                        </svg>
                                    </span>
                                    <span class="push-toggle-label">
                                        Notif: <strong data-chat-notif-state>OFF</strong>
                                    </span>
                                </button>
                                <div class="header-push-feedback" id="header-push-feedback" role="status" aria-live="polite" data-webpush-status-text></div>
                            @else
                                <a
                                    id="btn-push-notif-toggle"
                                    href="{{ route('login') }}"
                                    class="header-push-toggle-btn"
                                    title="Masuk untuk mengaktifkan Push Notification pesan chat"
                                    aria-label="Masuk untuk mengatur Push Notification pesan chat"
                                >
                                    <span class="push-toggle-icon-wrap" aria-hidden="true">
                                        <svg class="push-bell-icon-off" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" style="display: block;">
                                            <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                                            <path d="M18.63 13A17.89 17.89 0 0 1 18 8"/>
                                            <path d="M6.26 6.26A5.86 5.86 0 0 0 6 8c0 7-3 9-3 9h14"/>
                                            <path d="M18 8a6 6 0 0 0-9.33-5"/>
                                            <line x1="1" y1="1" x2="23" y2="23"/>
                                        </svg>
                                    </span>
                                    <span class="push-toggle-label">
                                        Notif: <strong>OFF</strong>
                                    </span>
                                </a>
                            @endauth
                        </div>
                    </div>
                </form>

                <!-- Tautan Masuk (atau Profil) & Tombol Jual Barang -->
                <div class="header-actions">

                    @guest
                        <a id="nav-pasarkan" href="{{ route('login') }}" class="button-primary header-btn-sell">
                            Jual Barang
                        </a>
                        <a id="nav-login" href="{{ route('login') }}" class="nav-link nav-link-accent {{ request()->routeIs('login') ? 'active' : '' }}">
                            Masuk
                        </a>
                    @else
                        <a id="nav-pasarkan" href="{{ route('barang.create') }}" class="button-primary header-btn-sell">
                            Jual Barang
                        </a>
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
                                    <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="8" r="4"/>
                                        <path d="M4 20c1.8-3.8 5-6 8-6s6.2 2.2 8 6"/>
                                    </svg>
                                @endif
                            </span>
                            <span class="nav-user-actor-name">{{ auth()->user()->username }}</span>
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
    @endif

    <!-- Tombol Chat Melayang (Selalu di Kanan Bawah Layar, Mengarah ke Daftar Room Chat + Badge Pesan Belum Dibaca) -->
    <a
        href="{{ auth()->check() ? route('chat.index') : route('login') }}"
        id="floating-chat-btn"
        class="floating-chat-btn {{ request()->routeIs('chat.*') ? 'active' : '' }}"
        title="{{ auth()->check() ? 'Buka Daftar Room Chat' : 'Masuk untuk membuka Room Chat' }}"
        aria-label="{{ auth()->check() ? 'Buka Daftar Room Chat' . ($navUnreadChatCount > 0 ? ' (' . $navUnreadChatCount . ' pesan belum dibaca)' : '') : 'Masuk untuk membuka Room Chat' }}"
    >
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
        </svg>
        @auth
            <span
                id="floating-chat-badge"
                class="floating-chat-badge {{ $navUnreadChatCount > 0 ? 'visible' : '' }}"
                data-chat-unread-badge
                data-unread-count="{{ $navUnreadChatCount }}"
            >
                {{ $navUnreadChatCount > 99 ? '99+' : $navUnreadChatCount }}
            </span>
        @endauth
    </a>

    <!-- Pemberitahuan Singkat (Toast In-App) Saat Pesan Chat Baru Masuk -->
    @auth
        <div class="chat-toast-popup" id="barantau-chat-toast" role="status" aria-live="polite" aria-hidden="true">
            <div class="chat-toast-icon" aria-hidden="true">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                </svg>
            </div>
            <a href="{{ route('chat.index') }}" class="chat-toast-content" id="barantau-chat-toast-link">
                <span class="chat-toast-label">Pesan Baru Masuk</span>
                <strong class="chat-toast-sender" id="barantau-chat-toast-sender"></strong>
                <span class="chat-toast-preview" id="barantau-chat-toast-preview"></span>
            </a>
            <button type="button" class="chat-toast-close" onclick="hideBarantauChatToast()" aria-label="Tutup pemberitahuan">
                &times;
            </button>
        </div>
    @endauth

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
                            @if ($isHalamanDaftarChat) data-force-fallback="1" @endif
                            onclick="return goBackBarantau(event, this)"
                            class="btn-back-barantau"
                            id="btn-kembali-halaman"
                            aria-label="Kembali ke halaman sebelumnya"
                            title="Kembali"
                        >
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M19 12H5M12 19l-7-7 7-7"/>
                            </svg>
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
                    <a href="{{ route('tentang') }}" style="display: block; padding: 0.3rem 0;">Tentang BARANTAU</a>
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
            </div>
        </div>
    </footer>

    @auth
        <!-- Pustaka Pusher-JS & Laravel Echo untuk Koneksi WebSocket Laravel Reverb -->
        <script src="https://cdn.jsdelivr.net/npm/pusher-js@8.4.0/dist/web/pusher.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.16.1/dist/echo.iife.js"></script>
    @endauth

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

            const notifWrap = document.getElementById('header-notif-wrap');
            if (notifWrap && !notifWrap.contains(e.target)) {
                closeChatNotifDropdown();
            }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeHamburgerNav();
                closeChatNotifDropdown();
                hideBarantauChatToast();
            }
        });

        // Buka/tutup dropdown lonceng notifikasi chat
        function toggleChatNotifDropdown(event) {
            if (event) event.stopPropagation();
            const dropdown = document.getElementById('chat-notif-dropdown');
            const btn = document.getElementById('btn-chat-notif-bell');
            if (!dropdown || !btn) return;
            const isOpen = dropdown.classList.toggle('open');
            btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        }

        function closeChatNotifDropdown() {
            const dropdown = document.getElementById('chat-notif-dropdown');
            const btn = document.getElementById('btn-chat-notif-bell');
            if (!dropdown || !btn) return;
            dropdown.classList.remove('open');
            btn.setAttribute('aria-expanded', 'false');
        }

        // Kontrol On/Off Push Notification Perangkat (Status ON hanya tampil jika Service Worker & PushSubscription benar-benar terdaftar di browser & server)
        const barantauWebPushConfig = {
            vapidPublicKey: @json((string) config('services.webpush.public_key', '')),
            subscribeUrl: @json(auth()->check() ? route('webpush.subscribe') : ''),
            unsubscribeUrl: @json(auth()->check() ? route('webpush.unsubscribe') : ''),
            isAuthenticated: @json(auth()->check()),
            swUrl: '/sw.js',
        };

        let barantauPushIsSubscribed = false;
        let barantauPushBusy = false;
        let pushFeedbackTimer = null;

        function isBarantauChatNotifEnabled() {
            return barantauPushIsSubscribed;
        }

        function setWebPushStatusMessages(message, isWarning, showFloatingFeedback) {
            document.querySelectorAll('[data-webpush-status-text]').forEach(function (el) {
                el.textContent = message;
                if (el.id !== 'header-push-feedback') {
                    el.style.color = isWarning ? '#D9541E' : '';
                }
            });

            const headerFeedback = document.getElementById('header-push-feedback');
            if (headerFeedback && showFloatingFeedback) {
                headerFeedback.classList.toggle('is-warning', Boolean(isWarning));
                headerFeedback.classList.add('visible');
                if (pushFeedbackTimer) clearTimeout(pushFeedbackTimer);
                pushFeedbackTimer = setTimeout(function () {
                    headerFeedback.classList.remove('visible');
                }, 6000);
            }
        }

        function urlBase64ToUint8Array(base64String) {
            const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
            const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
            const rawData = window.atob(base64);
            const outputArray = new Uint8Array(rawData.length);
            for (let i = 0; i < rawData.length; ++i) {
                outputArray[i] = rawData.charCodeAt(i);
            }
            return outputArray;
        }

        function isWebPushSupportedInBrowser() {
            return ('serviceWorker' in navigator)
                && ('PushManager' in window)
                && ('Notification' in window)
                && Boolean(window.isSecureContext);
        }

        function syncChatNotifToggleControls(customHintText, isWarning, showFloatingFeedback) {
            const enabled = barantauPushIsSubscribed;
            document.querySelectorAll('[data-chat-notif-toggle]').forEach(function (btn) {
                btn.setAttribute('aria-checked', enabled ? 'true' : 'false');
                btn.classList.toggle('is-on', enabled);
                const stateEl = btn.querySelector('[data-chat-notif-state]');
                if (stateEl) {
                    stateEl.textContent = enabled ? 'ON' : 'OFF';
                }
            });

            if (typeof customHintText === 'string' && customHintText !== '') {
                setWebPushStatusMessages(customHintText, Boolean(isWarning), Boolean(showFloatingFeedback));
            } else if (enabled) {
                setWebPushStatusMessages(
                    'Push Notification ON: Perangkat ini terdaftar untuk menerima notifikasi pesan chat baru.',
                    false,
                    Boolean(showFloatingFeedback)
                );
            } else if (!window.isSecureContext) {
                setWebPushStatusMessages(
                    'Push Notification OFF: Membutuhkan koneksi HTTPS (atau localhost) agar Web Push dapat diaktifkan.',
                    true,
                    Boolean(showFloatingFeedback)
                );
            } else if (!('serviceWorker' in navigator) || !('PushManager' in window) || !('Notification' in window)) {
                setWebPushStatusMessages(
                    'Push Notification OFF: Browser ini tidak mendukung Service Worker / Web Push.',
                    true,
                    Boolean(showFloatingFeedback)
                );
            } else if (Notification.permission === 'denied') {
                setWebPushStatusMessages(
                    'Push Notification OFF (Izin diblokir): Buka Pengaturan Situs di sebelah kiri URL Chrome → Notifikasi → Izinkan.',
                    true,
                    Boolean(showFloatingFeedback)
                );
            } else {
                setWebPushStatusMessages(
                    'Push Notification OFF: Tekan tombol Notif untuk mengaktifkan notifikasi pesan chat di perangkat ini.',
                    false,
                    Boolean(showFloatingFeedback)
                );
            }

            if (!enabled) {
                hideBarantauChatToast();
            }
        }

        async function sendSubscriptionToServer(subscription) {
            if (!barantauWebPushConfig.isAuthenticated || !barantauWebPushConfig.subscribeUrl || !subscription) {
                return false;
            }

            const subJson = subscription.toJSON ? subscription.toJSON() : {};
            if (!subJson.endpoint || !subJson.keys || !subJson.keys.p256dh || !subJson.keys.auth) {
                return false;
            }

            const csrfMeta = document.querySelector('meta[name="csrf-token"]');
            const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';
            const encoding = (PushManager.supportedContentEncodings && PushManager.supportedContentEncodings.length > 0)
                ? PushManager.supportedContentEncodings[0]
                : 'aes128gcm';

            const response = await fetch(barantauWebPushConfig.subscribeUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({
                    endpoint: subJson.endpoint,
                    keys: {
                        p256dh: subJson.keys.p256dh,
                        auth: subJson.keys.auth,
                    },
                    content_encoding: encoding,
                }),
            });

            return response.ok;
        }

        async function removeSubscriptionFromServer(endpoint) {
            if (!barantauWebPushConfig.isAuthenticated || !barantauWebPushConfig.unsubscribeUrl) {
                return false;
            }

            const csrfMeta = document.querySelector('meta[name="csrf-token"]');
            const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';

            try {
                const response = await fetch(barantauWebPushConfig.unsubscribeUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({ endpoint: endpoint || '' }),
                });
                return response.ok;
            } catch (e) {
                return false;
            }
        }

        /**
         * Periksa status langganan perangkat secara pasif saat halaman dibuka
         * TANPA meminta izin otomatis (tidak memanggil Notification.requestPermission).
         */
        async function initPassiveWebPushStatus() {
            barantauPushIsSubscribed = false;

            if (!barantauWebPushConfig.isAuthenticated || !isWebPushSupportedInBrowser()) {
                syncChatNotifToggleControls('', false, false);
                return;
            }

            // Jika pengguna sebelumnya sengaja mematikan (OFF) atau izin belum 'granted', biarkan OFF
            let savedPref = null;
            try {
                savedPref = localStorage.getItem('barantau_push_pref');
            } catch (e) {}

            if (savedPref === 'off' || Notification.permission !== 'granted') {
                syncChatNotifToggleControls('', false, false);
                return;
            }

            try {
                const registration = await navigator.serviceWorker.register(barantauWebPushConfig.swUrl, { scope: '/' });
                await navigator.serviceWorker.ready;
                const existingSub = await registration.pushManager.getSubscription();

                if (existingSub) {
                    const synced = await sendSubscriptionToServer(existingSub);
                    if (synced) {
                        barantauPushIsSubscribed = true;
                        try {
                            localStorage.setItem('barantau_push_pref', 'on');
                            localStorage.setItem('barantau_chat_notif', 'on');
                        } catch (e) {}
                    }
                }
            } catch (e) {
                barantauPushIsSubscribed = false;
            }

            syncChatNotifToggleControls('', false, false);
        }

        /**
         * Aktifkan Web Push ketika pengguna menekan tombol ke ON:
         * 1. Jika izin sebelumnya sudah 'denied', jangan memanggil requestPermission lagi; tampilkan petunjuk pengaturan situs Chrome.
         * 2. Jika izin masih 'default', minta izin Chrome saat itu juga.
         * 3. Jika pengguna memilih 'Izinkan' ('granted'), daftarkan perangkat ke PushManager dan simpan ke server.
         * 4. Tampilkan status ON HANYA setelah pendaftaran ke browser dan server berhasil.
         */
        async function activateWebPushFromUserClick() {
            if (!barantauWebPushConfig.isAuthenticated) {
                window.location.href = '{{ route('login') }}';
                return;
            }

            if (!window.isSecureContext) {
                barantauPushIsSubscribed = false;
                syncChatNotifToggleControls(
                    'Push Notification memerlukan koneksi HTTPS saat dibuka di HP. Status tetap OFF.',
                    true,
                    true
                );
                return;
            }

            if (!('serviceWorker' in navigator) || !('PushManager' in window) || !('Notification' in window)) {
                barantauPushIsSubscribed = false;
                syncChatNotifToggleControls(
                    'Browser ini tidak mendukung Web Push Notification. Di iPhone/iPad, gunakan "Tambahkan ke Layar Utama" (iOS 16.4+). Status tetap OFF.',
                    true,
                    true
                );
                return;
            }

            if (!barantauWebPushConfig.vapidPublicKey) {
                barantauPushIsSubscribed = false;
                syncChatNotifToggleControls(
                    'Konfigurasi VAPID_PUBLIC_KEY belum tersedia di server. Status tetap OFF.',
                    true,
                    true
                );
                return;
            }

            // Jika izin sebelumnya sudah ditolak ('denied'), jangan mengulang requestPermission() yang tidak akan muncul di Chrome
            if (Notification.permission === 'denied') {
                barantauPushIsSubscribed = false;
                try {
                    localStorage.setItem('barantau_push_pref', 'off');
                    localStorage.setItem('barantau_chat_notif', 'off');
                } catch (e) {}
                syncChatNotifToggleControls(
                    'Izin notifikasi telah ditolak sebelumnya. Untuk mengaktifkan di Chrome: ketuk ikon Pengaturan Situs di sebelah kiri alamat URL → Izin → Notifikasi → pilih Izinkan, lalu tekan tombol Notif kembali.',
                    true,
                    true
                );
                return;
            }

            // Jika izin masih belum ditentukan ('default'), minta izin notifikasi Chrome pada saat itu juga!
            let permission = Notification.permission;
            if (permission === 'default') {
                permission = await Notification.requestPermission();
            }

            // Jika pengguna memilih "Jangan izinkan" atau menutup dialog izin, biarkan status OFF
            if (permission !== 'granted') {
                barantauPushIsSubscribed = false;
                try {
                    localStorage.setItem('barantau_push_pref', 'off');
                    localStorage.setItem('barantau_chat_notif', 'off');
                } catch (e) {}

                const pesanDitolak = permission === 'denied'
                    ? 'Anda memilih Jangan Izinkan. Status tetap OFF. Untuk mengaktifkan nanti, ubah izin Notifikasi melalui Pengaturan Situs di sebelah kiri URL Chrome.'
                    : 'Izin notifikasi belum diberikan, sehingga status tetap OFF.';

                syncChatNotifToggleControls(pesanDitolak, true, true);
                return;
            }

            // Izin telah diberikan ('granted') -> daftarkan Service Worker & PushSubscription lalu simpan ke server
            try {
                const registration = await navigator.serviceWorker.register(barantauWebPushConfig.swUrl, { scope: '/' });
                await navigator.serviceWorker.ready;

                let subscription = await registration.pushManager.getSubscription();
                let createdNewSub = false;

                if (!subscription) {
                    const appServerKey = urlBase64ToUint8Array(barantauWebPushConfig.vapidPublicKey);
                    subscription = await registration.pushManager.subscribe({
                        userVisibleOnly: true,
                        applicationServerKey: appServerKey,
                    });
                    createdNewSub = true;
                }

                const savedOnServer = await sendSubscriptionToServer(subscription);
                if (!savedOnServer) {
                    if (createdNewSub && subscription) {
                        try { await subscription.unsubscribe(); } catch (e) {}
                    }
                    barantauPushIsSubscribed = false;
                    try {
                        localStorage.setItem('barantau_push_pref', 'off');
                        localStorage.setItem('barantau_chat_notif', 'off');
                    } catch (e) {}
                    syncChatNotifToggleControls(
                        'Gagal menyimpan langganan push perangkat ke server. Status tetap OFF.',
                        true,
                        true
                    );
                    return;
                }

                // Tampilkan status ON HANYA setelah pendaftaran perangkat ke Web Push & penyimpanan di server berhasil
                barantauPushIsSubscribed = true;
                try {
                    localStorage.setItem('barantau_push_pref', 'on');
                    localStorage.setItem('barantau_chat_notif', 'on');
                } catch (e) {}

                syncChatNotifToggleControls(
                    'Push Notification ON: Perangkat ini berhasil didaftarkan untuk menerima notifikasi pesan chat baru.',
                    false,
                    true
                );
            } catch (err) {
                barantauPushIsSubscribed = false;
                try {
                    localStorage.setItem('barantau_push_pref', 'off');
                    localStorage.setItem('barantau_chat_notif', 'off');
                } catch (e) {}
                syncChatNotifToggleControls(
                    'Pendaftaran Web Push gagal pada browser ini. Status tetap OFF, namun fitur chat tetap berfungsi normal.',
                    true,
                    true
                );
            }
        }

        /**
         * Matikan Web Push ketika pengguna menekan tombol ke OFF:
         * Hentikan subscription perangkat di browser dan hapus dari server.
         */
        async function deactivateWebPushFromUserClick() {
            try {
                if ('serviceWorker' in navigator) {
                    const registration = await navigator.serviceWorker.getRegistration('/');
                    if (registration && registration.pushManager) {
                        const subscription = await registration.pushManager.getSubscription();
                        if (subscription) {
                            const endpoint = subscription.endpoint;
                            await removeSubscriptionFromServer(endpoint);
                            await subscription.unsubscribe();
                        }
                    }
                }
            } catch (e) {}

            barantauPushIsSubscribed = false;
            try {
                localStorage.setItem('barantau_push_pref', 'off');
                localStorage.setItem('barantau_chat_notif', 'off');
            } catch (e) {}

            syncChatNotifToggleControls(
                'Push Notification OFF: Langganan perangkat telah dihentikan dan dihapus dari server.',
                false,
                true
            );
        }

        async function toggleBarantauChatNotif(event) {
            if (event) event.stopPropagation();
            if (barantauPushBusy) return;

            barantauPushBusy = true;
            try {
                if (!barantauPushIsSubscribed) {
                    await activateWebPushFromUserClick();
                } else {
                    await deactivateWebPushFromUserClick();
                }
            } finally {
                barantauPushBusy = false;
            }
        }

        let chatToastTimer = null;

        function showBarantauChatToast(item) {
            if (!isBarantauChatNotifEnabled() || !item) return;
            const toast = document.getElementById('barantau-chat-toast');
            const link = document.getElementById('barantau-chat-toast-link');
            const sender = document.getElementById('barantau-chat-toast-sender');
            const preview = document.getElementById('barantau-chat-toast-preview');
            if (!toast || !link || !sender || !preview) return;

            link.href = item.room_url || '{{ route('chat.index') }}';
            sender.textContent = (item.pengirim_nama || 'Pengguna') + ' • ' + (item.nama_barang || 'Barang');
            preview.textContent = item.cuplikan_pesan || 'Pesan baru masuk';

            toast.classList.add('visible');
            toast.setAttribute('aria-hidden', 'false');

            if (chatToastTimer) clearTimeout(chatToastTimer);
            chatToastTimer = setTimeout(function () {
                hideBarantauChatToast();
            }, 6500);
        }

        function hideBarantauChatToast() {
            const toast = document.getElementById('barantau-chat-toast');
            if (!toast) return;
            toast.classList.remove('visible');
            toast.setAttribute('aria-hidden', 'true');
        }

        // Pengelolaan Mode Terang / Mode Gelap
        function syncThemeToggleControls(theme) {
            const isDark = theme === 'dark';
            const buttons = document.querySelectorAll('.theme-toggle-btn');
            buttons.forEach(function (btn) {
                btn.setAttribute('aria-checked', isDark ? 'true' : 'false');
                btn.setAttribute('aria-label', isDark
                    ? 'Mode Gelap, klik untuk beralih ke mode terang'
                    : 'Mode Terang, klik untuk beralih ke mode gelap');
                const labelEl = btn.querySelector('[data-theme-label]');
                if (labelEl) {
                    labelEl.textContent = isDark ? 'Mode Gelap' : 'Mode Terang';
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

            if (anchorEl.getAttribute('data-force-fallback') === '1') {
                anchorEl.href = fallbackUrl;
                return true;
            }

            const ref = document.referrer || '';
            const sameOrigin = ref.indexOf(window.location.origin) === 0;
            const isFromChatRoom = /\/chat(\/|$|\?)/.test(ref);

            if (sameOrigin && window.history.length > 1 && ref !== window.location.href && !isFromChatRoom) {
                event.preventDefault();
                window.history.back();
                return false;
            }

            anchorEl.href = fallbackUrl;
            return true;
        }

        // Inisialisasi status tombol tema, notifikasi chat, scroll navbar, kotak upload gambar, & pencarian wilayah
        document.addEventListener('DOMContentLoaded', function () {
            const initialTheme = document.documentElement.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
            syncThemeToggleControls(initialTheme);
            initPassiveWebPushStatus();

            // Perilaku Navbar saat Halaman Di-scroll (Muncul di atas / saat scroll ke atas, sembunyi saat scroll ke bawah)
            (function initSmartScrollNavbar() {
                const header = document.getElementById('site-header');
                if (!header) return;

                let lastScrollY = window.scrollY || 0;
                let ticking = false;

                function hasOpenHeaderPopup() {
                    const hamburgerPanel = document.getElementById('hamburger-nav-panel');
                    const notifDropdown = document.getElementById('chat-notif-dropdown');
                    const openWilayah = header.querySelector('.wilayah-dropdown.open');
                    return (hamburgerPanel && hamburgerPanel.classList.contains('open'))
                        || (notifDropdown && notifDropdown.classList.contains('open'))
                        || Boolean(openWilayah);
                }

                function updateNavbarOnScroll() {
                    const currentY = Math.max(0, window.scrollY || 0);

                    if (currentY <= 24) {
                        header.classList.remove('navbar-hidden');
                        header.classList.remove('navbar-scrolled');
                        lastScrollY = currentY;
                        ticking = false;
                        return;
                    }

                    header.classList.add('navbar-scrolled');

                    if (hasOpenHeaderPopup()) {
                        header.classList.remove('navbar-hidden');
                        lastScrollY = currentY;
                        ticking = false;
                        return;
                    }

                    const delta = currentY - lastScrollY;
                    if (delta > 10 && currentY > 80) {
                        // Menggulir ke bawah: sembunyikan navbar agar ruang baca lebih luas
                        header.classList.add('navbar-hidden');
                    } else if (delta < -6) {
                        // Menggulir kembali ke atas: tampilkan navbar segera
                        header.classList.remove('navbar-hidden');
                    }

                    lastScrollY = currentY;
                    ticking = false;
                }

                window.addEventListener('scroll', function () {
                    if (!ticking) {
                        window.requestAnimationFrame(updateNavbarOnScroll);
                        ticking = true;
                    }
                }, { passive: true });
            })();

            // Koneksi Real-Time WebSocket (Laravel Reverb + Laravel Echo) untuk Badge Chat & Notifikasi
            window.updateBarantauUnreadBadges = function (unreadCount) {
                const count = Math.max(0, parseInt(unreadCount || 0, 10));
                document.querySelectorAll('[data-chat-unread-badge]').forEach(function (badgeEl) {
                    badgeEl.textContent = count > 99 ? '99+' : String(count);
                    badgeEl.setAttribute('data-unread-count', String(count));
                    badgeEl.classList.toggle('visible', count > 0);
                });

                const floatingBtn = document.getElementById('floating-chat-btn');
                if (floatingBtn) {
                    floatingBtn.setAttribute(
                        'aria-label',
                        'Buka Daftar Room Chat' + (count > 0 ? ' (' + count + ' pesan belum dibaca)' : '')
                    );
                }
            };

            @auth
            (function initBarantauEchoRealtime() {
                const reverbKey = @json((string) config('broadcasting.connections.reverb.key', ''));
                const reverbHost = @json((string) config('broadcasting.connections.reverb.options.host', '127.0.0.1'));
                const reverbPort = {{ (int) config('broadcasting.connections.reverb.options.port', 8080) }};
                const reverbScheme = @json((string) config('broadcasting.connections.reverb.options.scheme', 'http'));
                const authEndpointUrl = @json(url('/broadcasting/auth'));
                const statusEndpoint = @json(route('chat.notifikasi.status'));
                const currentUserId = {{ (int) auth()->id() }};
                const csrfMeta = document.querySelector('meta[name="csrf-token"]');
                const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';

                const EchoConstructor = window.Echo && typeof window.Echo === 'function'
                    ? window.Echo
                    : (window.Echo && window.Echo.default ? window.Echo.default : null);

                if (EchoConstructor && window.Pusher && reverbKey) {
                    try {
                        window.Echo = new EchoConstructor({
                            broadcaster: 'reverb',
                            key: reverbKey,
                            wsHost: reverbHost || window.location.hostname,
                            wsPort: reverbPort || 8080,
                            wssPort: reverbPort || 443,
                            forceTLS: reverbScheme === 'https',
                            enabledTransports: ['ws', 'wss'],
                            authEndpoint: authEndpointUrl,
                            auth: {
                                headers: {
                                    'X-CSRF-TOKEN': csrfToken,
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                            },
                        });

                        // Jalankan seluruh callback halaman (seperti room chat) yang menunggu inisialisasi Echo selesai
                        if (Array.isArray(window.barantauEchoReadyQueue)) {
                            window.barantauEchoReadyQueue.forEach(function (fn) {
                                try { fn(window.Echo); } catch (e) {}
                            });
                            window.barantauEchoReadyQueue = [];
                        }

                        // Dengarkan private channel notifikasi pengguna untuk memperbarui badge pesan belum dibaca secara langsung
                        window.Echo.private('user-notifikasi.' + currentUserId)
                            .listen('.PesanChatTerkirim', function (payload) {
                                if (!payload) return;

                                const activeRoomId = Number(window.barantauActiveRoomChatId || 0);
                                const incomingRoomId = Number(payload.room_chat_id || 0);

                                // Jika pengguna sedang berada di luar room chat tersebut, perbarui badge & tampilkan toast
                                if (activeRoomId !== incomingRoomId) {
                                    if (typeof payload.unread_count_penerima !== 'undefined') {
                                        window.updateBarantauUnreadBadges(payload.unread_count_penerima);
                                    }
                                    showBarantauChatToast(payload);
                                }

                                window.dispatchEvent(new CustomEvent('barantau:pesan-baru', { detail: payload }));
                            });
                    } catch (err) {}
                }

                // Sinkronisasi cadangan untuk badge pesan belum dibaca apabila pengguna berada di luar halaman room chat
                let lastNotifMsgId = {{ (int) $navLatestUnreadMsgId }};
                setInterval(function () {
                    if (document.hidden || Number(window.barantauActiveRoomChatId || 0) > 0) return;
                    fetch(statusEndpoint, { headers: { 'Accept': 'application/json' } })
                        .then(function (res) { return res.ok ? res.json() : null; })
                        .then(function (data) {
                            if (!data) return;
                            if (typeof data.unread_count !== 'undefined') {
                                window.updateBarantauUnreadBadges(data.unread_count);
                            }
                            const latestId = parseInt(data.latest_message_id || 0, 10);
                            if (latestId > lastNotifMsgId && Array.isArray(data.items) && data.items.length > 0) {
                                lastNotifMsgId = latestId;
                                showBarantauChatToast(data.items[0]);
                                window.dispatchEvent(new CustomEvent('barantau:pesan-baru', {
                                    detail: {
                                        room_chat_id: data.items[0].room_chat_id,
                                        cuplikan_pesan: data.items[0].cuplikan_pesan,
                                        waktu_relatif: data.items[0].waktu,
                                    },
                                }));
                            }
                        })
                        .catch(function () {});
                }, 4000);
            })();
            @endauth

            document.querySelectorAll('[data-image-upload-box]').forEach(function (box) {
                const input = box.querySelector('input[type="file"]');
                const nameEl = box.querySelector('[data-upload-filename]');
                if (!input || !nameEl) return;

                input.addEventListener('change', function () {
                    const files = input.files;
                    if (!files || files.length === 0) {
                        box.classList.remove('has-file');
                        nameEl.textContent = '';
                        return;
                    }
                    box.classList.add('has-file');
                    nameEl.textContent = files.length === 1
                        ? files[0].name
                        : files.length + ' gambar dipilih';
                });
            });

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
