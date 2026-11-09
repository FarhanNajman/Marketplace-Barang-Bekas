<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="@yield('description', 'BarangRantau / BalikPakai - Marketplace barang bekas lokal berkualitas.')">
    <title>@yield('title', 'Beranda') | {{ config('app.name', 'BarangRantau') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    <header class="site-header">
        <div class="site-container header-inner">
            <a href="{{ route('beranda') }}" class="brand-logo" id="nav-brand">
                Barang<span>Rantau</span>
            </a>

            <nav class="desktop-nav" aria-label="Navigasi utama">
                <a id="nav-beranda" href="{{ route('beranda') }}" class="nav-link {{ request()->routeIs('beranda') ? 'active' : '' }}">
                    Beranda
                </a>
                <a id="nav-jelajahi" href="{{ route('barang.index') }}" class="nav-link {{ request()->routeIs('barang.*') ? 'active' : '' }}">
                    Jelajahi Barang
                </a>
                <a id="nav-wishlist" href="{{ route('wishlist.index') }}" class="nav-link {{ request()->routeIs('wishlist.*') ? 'active' : '' }}">
                    Wishlist
                </a>
            </nav>

            <div class="header-actions">
                @guest
                    <a id="nav-login" href="{{ route('login') }}" class="nav-link {{ request()->routeIs('login') ? 'active' : '' }}">
                        Masuk
                    </a>
                    <a id="nav-register" href="{{ route('register') }}" class="button-outline">
                        Daftar
                    </a>
                @else
                    <a id="nav-profil" href="{{ route('profil.show', ['username' => auth()->user()->username]) }}" class="nav-link {{ request()->routeIs('profil.*') ? 'active' : '' }}">
                        Profil ({{ '@' . auth()->user()->username }})
                    </a>
                    <form method="POST" action="{{ route('logout') }}" style="display: inline;">
                        @csrf
                        <button type="submit" id="btn-logout" class="button-outline" style="padding: 0.55rem 0.9rem;">
                            Keluar
                        </button>
                    </form>
                @endguest

                <button type="button" class="button-primary" onclick="toggleSellGuide(true)">
                    Pasang Barang <span aria-hidden="true">＋</span>
                </button>
            </div>

            <button
                type="button"
                class="mobile-menu-toggle"
                id="mobile-menu-btn"
                aria-label="Buka menu"
                aria-expanded="false"
                onclick="toggleMobileMenu()"
            >
                <span style="display: block; width: 20px;">
                    <span style="display: block; height: 1.5px; width: 20px; background: #111; margin-bottom: 5px;"></span>
                    <span style="display: block; height: 1.5px; width: 20px; background: #111;"></span>
                </span>
            </button>
        </div>

        <div class="mobile-menu" id="mobile-menu">
            <nav class="mobile-menu-nav">
                <a href="{{ route('beranda') }}">Beranda</a>
                <a href="{{ route('barang.index') }}">Jelajahi Barang</a>
                <a href="{{ route('wishlist.index') }}">Wishlist</a>
                @guest
                    <a href="{{ route('login') }}">Masuk</a>
                    <a href="{{ route('register') }}">Daftar</a>
                @else
                    <a href="{{ route('profil.show', ['username' => auth()->user()->username]) }}">
                        Profil ({{ '@' . auth()->user()->username }})
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="button-outline" style="width: 100%;">Keluar</button>
                    </form>
                @endguest
                <button type="button" class="button-primary" style="justify-content: space-between; width: 100%;" onclick="toggleMobileMenu(); toggleSellGuide(true);">
                    Pasang Barang <span>＋</span>
                </button>
            </nav>
        </div>
    </header>

    <main style="flex: 1;">
        @hasSection('fullbleed')
            @yield('fullbleed')
        @else
            <div class="site-container page-section">
                @yield('content')
            </div>
        @endif
    </main>

    <footer id="tentang-footer" class="site-footer">
        <div class="site-container footer-inner">
            <div class="footer-grid">
                <div>
                    <p class="footer-brand">BALIKPAKAI</p>
                    <p style="margin-top: 1rem; max-width: 24rem; font-size: 0.875rem; line-height: 1.5rem;">
                        Ruang temu untuk barang yang masih punya banyak cerita.
                    </p>
                </div>
                <div style="font-size: 0.875rem;">
                    <p style="margin-bottom: 1rem; font-weight: 700;">Jelajah</p>
                    <a href="{{ route('beranda') }}#barang-terbaru" style="display: block; padding: 0.35rem 0;">Barang terbaru</a>
                    <a href="{{ route('beranda') }}#cara-kerja" style="display: block; padding: 0.35rem 0;">Cara kerja</a>
                    <a href="{{ route('tentang') }}" style="display: block; padding: 0.35rem 0;">Tentang kami</a>
                </div>
                <div style="font-size: 0.875rem;">
                    <p style="margin-bottom: 1rem; font-weight: 700;">Akun & Bantuan</p>
                    @guest
                        <a href="{{ route('login') }}" style="display: block; padding: 0.35rem 0;">Masuk</a>
                        <a href="{{ route('register') }}" style="display: block; padding: 0.35rem 0;">Daftar</a>
                    @endguest
                    <a href="{{ route('password.request') }}" style="display: block; padding: 0.35rem 0;">Lupa password</a>
                    <p style="padding: 0.35rem 0;">Privasi lokasi kecamatan</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; {{ date('Y') }} BalikPakai &mdash; BarangRantau</p>
                <p>Lebih sedikit sampah, lebih banyak guna.</p>
            </div>
        </div>
    </footer>

    <!-- Modal Panduan Pasang Barang -->
    <div class="modal-backdrop" id="sell-guide-modal" role="presentation" onclick="if(event.target === this) toggleSellGuide(false)">
        <section class="modal-dialog" role="dialog" aria-modal="true" aria-labelledby="sell-guide-title">
            <div style="margin-bottom: 2.25rem; display: flex; align-items: flex-start; justify-content: space-between; gap: 1.5rem;">
                <div>
                    <p style="margin-bottom: 0.5rem; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.18em; color: var(--accent);">
                        Mulai menjual
                    </p>
                    <h2 id="sell-guide-title" class="font-display" style="font-size: 2.25rem; font-weight: 600; letter-spacing: -0.055em;">
                        Siapkan barangmu.
                    </h2>
                </div>
                <button
                    type="button"
                    onclick="toggleSellGuide(false)"
                    aria-label="Tutup"
                    style="display: grid; place-items: center; width: 40px; height: 40px; border-radius: 999px; border: 1px solid rgba(17,17,17,0.4); background: transparent; font-size: 1.25rem;"
                >
                    &times;
                </button>
            </div>

            <ol style="list-style: none; border-top: 1px solid rgba(17,17,17,0.3); border-bottom: 1px solid rgba(17,17,17,0.3);">
                <li style="display: flex; align-items: center; gap: 1.25rem; padding: 1rem 0; border-bottom: 1px solid rgba(17,17,17,0.2);">
                    <span class="font-serif" style="font-size: 1.5rem; font-style: italic; color: var(--accent);">01</span>
                    <span style="font-size: 0.875rem; font-weight: 600;">Foto dengan cahaya yang jelas</span>
                </li>
                <li style="display: flex; align-items: center; gap: 1.25rem; padding: 1rem 0; border-bottom: 1px solid rgba(17,17,17,0.2);">
                    <span class="font-serif" style="font-size: 1.5rem; font-style: italic; color: var(--accent);">02</span>
                    <span style="font-size: 0.875rem; font-weight: 600;">Tulis kondisi barang dengan jujur</span>
                </li>
                <li style="display: flex; align-items: center; gap: 1.25rem; padding: 1rem 0;">
                    <span class="font-serif" style="font-size: 1.5rem; font-style: italic; color: var(--accent);">03</span>
                    <span style="font-size: 0.875rem; font-weight: 600;">Cantumkan lokasi sampai kecamatan saja</span>
                </li>
            </ol>

            <p style="margin-top: 1.5rem; font-size: 0.875rem; line-height: 1.5rem; color: var(--muted);">
                @guest
                    Silakan masuk terlebih dahulu untuk mulai memasang barang. Jangan cantumkan alamat rumah atau titik lokasi persis di deskripsi.
                @else
                    Halo {{ auth()->user()->name }}! Jangan cantumkan alamat rumah atau titik lokasi persis di deskripsi barangmu.
                @endguest
            </p>

            @guest
                <div style="margin-top: 1.75rem; display: flex; gap: 0.75rem;">
                    <a href="{{ route('login') }}" class="button-primary" style="flex: 1;">
                        Masuk ke Akun
                    </a>
                    <button type="button" class="button-outline" onclick="toggleSellGuide(false)">
                        Nanti Saja
                    </button>
                </div>
            @else
                <button type="button" class="button-primary" style="margin-top: 1.75rem; width: 100%;" onclick="toggleSellGuide(false)">
                    Mengerti, lanjut nanti &rarr;
                </button>
            @endguest
        </section>
    </div>

    <script>
        function toggleMobileMenu() {
            const menu = document.getElementById('mobile-menu');
            const btn = document.getElementById('mobile-menu-btn');
            if (!menu) return;
            const isOpen = menu.classList.toggle('open');
            if (btn) btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        }

        function toggleSellGuide(show) {
            const modal = document.getElementById('sell-guide-modal');
            if (!modal) return;
            if (show) {
                modal.classList.add('open');
            } else {
                modal.classList.remove('open');
            }
        }
    </script>
</body>
</html>
