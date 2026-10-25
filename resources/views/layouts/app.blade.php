<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="@yield('description', 'Marketplace Barang Bekas - jual beli barang bekas berkualitas.')">
    <title>@yield('title', 'Beranda') | {{ config('app.name', 'Marketplace Barang Bekas') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    <header class="navbar">
        <div class="container navbar-inner">
            <a href="{{ route('beranda') }}" class="brand" id="nav-brand">♻️ Barang<span>Bekas</span></a>
            <nav aria-label="Navigasi utama">
                <ul class="nav-links">
                    <li><a id="nav-beranda" href="{{ route('beranda') }}" class="{{ request()->routeIs('beranda') ? 'active' : '' }}">Beranda</a></li>
                    <li><a id="nav-tentang" href="{{ route('tentang') }}" class="{{ request()->routeIs('tentang') ? 'active' : '' }}">Tentang</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="container content">
        @yield('content')
    </main>

    <footer class="footer">
        <div class="container">&copy; {{ date('Y') }} Marketplace Barang Bekas. Proyek pribadi.</div>
    </footer>
</body>
</html>
