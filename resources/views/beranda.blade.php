@extends('layouts.app')

@section('title', 'Beranda')
@section('description', 'Temukan barang layak pakai di sekitarmu. Lebih hemat, lebih dekat, dan lebih panjang umur pakainya.')

@section('fullbleed')
    <section class="site-container hero-section">
        @if (session('status'))
            <div class="alert alert-success" role="status" id="alert-status">
                {{ session('status') }}
            </div>
        @endif

        <div class="eyebrow">
            <span class="eyebrow-dot"></span>
            Marketplace barang bekas lokal
        </div>

        <div class="hero-grid">
            <div>
                <h1 class="hero-title">
                    Barang baik,<br>
                    <span class="hero-title-italic">hidup lagi.</span>
                </h1>

                <div class="hero-sub">
                    <p class="hero-desc">
                        Temukan barang layak pakai di sekitarmu. Lebih hemat, lebih dekat, dan lebih panjang umur pakainya.
                    </p>
                    <a href="{{ route('barang.index') }}" class="hero-cta-link" id="btn-jelajahi-hero">
                        Mulai cari barang
                        <span class="circle-arrow">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M5 12h14M14 7l5 5-5 5" stroke="currentColor" stroke-width="1.8" />
                            </svg>
                        </span>
                    </a>
                </div>
            </div>

            <div class="hero-visual">
                <a href="#cara-kerja" id="link-hero-alur-transaksi" style="display: block; position: relative;" title="Klik untuk melihat detail Alur Transaksi">
                    <div class="hero-stack" id="hero-fade-carousel">
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
                                src="{{ asset('Assets/Beranda/CHAT.png') }}"
                                alt="Alur 02: Chat Penjual Secara Langsung"
                            >
                        </div>
                        <div class="hero-stack-card pos-2" data-step="03">
                            <span class="hero-step-corner">03</span>
                            <img
                                src="{{ asset('Assets/Beranda/DEAL.webp') }}"
                                alt="Alur 03: Bertemu untuk COD di Tempat Aman"
                            >
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </section>

    <!-- Carousel Alur Transaksi (3 Tahap: Mencari Barang, Chat Penjual, Bertemu COD) -->
    <section id="cara-kerja" class="carousel-section" aria-label="Carousel Alur Transaksi">
        <div class="site-container">
            <div style="margin-bottom: 2rem; display: flex; flex-wrap: wrap; align-items: flex-end; justify-content: space-between; gap: 1rem;">
                <div>
                    <p class="section-tag" style="margin-bottom: 0.6rem;">Alur Transaksi</p>
                    <h2 class="how-heading" style="font-size: clamp(2.1rem, 4.5vw, 3.5rem);">
                        Dari tangan ke tangan.
                    </h2>
                </div>
                <p style="max-width: 26rem; font-size: 0.875rem; line-height: 1.55; color: rgba(243,238,227,0.65);">
                    Tiga langkah sederhana bertransaksi aman secara langsung di kecamatan terdekatmu.
                </p>
            </div>

            <div class="carousel-shell" id="alur-carousel" onmouseenter="pauseCarousel()" onmouseleave="resumeCarousel()">
                <div class="carousel-track" id="carousel-track">
                    <!-- Slide 1: Mencari Barang -->
                    <article class="carousel-slide" aria-roledescription="slide" aria-label="1 dari 3: Mencari Barang">
                        <div>
                            <div class="carousel-step-badge">
                                <span class="font-serif" style="font-size: 1.5rem; font-style: italic;">01</span>
                                <span>Tahap Pertama</span>
                            </div>
                            <h3 class="carousel-slide-title">Mencari barang di sekitarmu</h3>
                            <p class="carousel-slide-desc">
                                Gunakan fitur pencarian serta filter kategori dan kecamatan pada halaman Daftar Barang untuk menemukan barang bekas layak pakai yang dekat dari lokasimu. Simpan barang incaran ke Wishlist agar mudah ditemukan kembali.
                            </p>
                        </div>
                        <div class="carousel-illustration" style="padding: 0; overflow: hidden;">
                            <img
                                src="{{ asset('Assets/Beranda/pencarian.jpg') }}"
                                alt="Ilustrasi Mencari Barang"
                                style="width: 100%; height: 100%; object-fit: cover;"
                            >
                        </div>
                    </article>

                    <!-- Slide 2: Chat Penjual -->
                    <article class="carousel-slide" aria-roledescription="slide" aria-label="2 dari 3: Chat Penjual">
                        <div>
                            <div class="carousel-step-badge">
                                <span class="font-serif" style="font-size: 1.5rem; font-style: italic;">02</span>
                                <span>Tahap Kedua</span>
                            </div>
                            <h3 class="carousel-slide-title">Chat penjual secara langsung</h3>
                            <p class="carousel-slide-desc">
                                Tanyakan detail kondisi barang, kelengkapan, riwayat pemakaian, serta negosiasikan harga secara sopan dan transparan langsung dengan penjual tanpa perantara.
                            </p>
                        </div>
                        <div class="carousel-illustration" style="padding: 0; overflow: hidden;">
                            <img
                                src="{{ asset('Assets/Beranda/CHAT.png') }}"
                                alt="Ilustrasi Chat Penjual"
                                style="width: 100%; height: 100%; object-fit: cover;"
                            >
                        </div>
                    </article>

                    <!-- Slide 3: Bertemu untuk COD -->
                    <article class="carousel-slide" aria-roledescription="slide" aria-label="3 dari 3: Bertemu untuk COD">
                        <div>
                            <div class="carousel-step-badge">
                                <span class="font-serif" style="font-size: 1.5rem; font-style: italic;">03</span>
                                <span>Tahap Ketiga</span>
                            </div>
                            <h3 class="carousel-slide-title">Bertemu untuk COD di tempat aman</h3>
                            <p class="carousel-slide-desc">
                                Demi privasi, lokasi barang hanya ditampilkan hingga tingkat kecamatan. Sepakati titik temu di ruang publik yang ramai, periksa barang secara langsung, lalu selesaikan transaksi di tempat.
                            </p>
                        </div>
                        <div class="carousel-illustration" style="padding: 0; overflow: hidden;">
                            <img
                                src="{{ asset('Assets/Beranda/DEAL.webp') }}"
                                alt="Ilustrasi Bertemu untuk COD"
                                style="width: 100%; height: 100%; object-fit: cover;"
                            >
                        </div>
                    </article>
                </div>
            </div>

            <div class="carousel-controls">
                <div class="carousel-dots" role="tablist" aria-label="Pilih slide alur transaksi">
                    <button type="button" class="carousel-dot active" onclick="goToSlide(0)" aria-label="Slide 1: Mencari barang" aria-selected="true"></button>
                    <button type="button" class="carousel-dot" onclick="goToSlide(1)" aria-label="Slide 2: Chat penjual" aria-selected="false"></button>
                    <button type="button" class="carousel-dot" onclick="goToSlide(2)" aria-label="Slide 3: Bertemu COD" aria-selected="false"></button>
                </div>

                <div class="carousel-nav-btns">
                    <button type="button" class="carousel-btn" id="carousel-prev" onclick="prevSlide()" aria-label="Slide sebelumnya">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M19 12H5M10 17l-5-5 5-5" stroke="currentColor" stroke-width="1.8" />
                        </svg>
                    </button>
                    <button type="button" class="carousel-btn" id="carousel-next" onclick="nextSlide()" aria-label="Slide berikutnya">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M5 12h14M14 7l5 5-5 5" stroke="currentColor" stroke-width="1.8" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </section>

    <script>
        (function () {
            const track = document.getElementById('carousel-track');
            const dots = document.querySelectorAll('#alur-carousel ~ .carousel-controls .carousel-dot');
            const totalSlides = 3;
            let currentSlide = 0;
            let autoTimer = null;
            const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            function renderCarousel() {
                if (!track) return;
                track.style.transform = 'translateX(-' + (currentSlide * 100) + '%)';
                dots.forEach((dot, idx) => {
                    const isActive = idx === currentSlide;
                    dot.classList.toggle('active', isActive);
                    dot.setAttribute('aria-selected', isActive ? 'true' : 'false');
                });
            }

            window.goToSlide = function (index) {
                currentSlide = (index + totalSlides) % totalSlides;
                renderCarousel();
                restartAutoTimer();
            };

            window.nextSlide = function () {
                window.goToSlide(currentSlide + 1);
            };

            window.prevSlide = function () {
                window.goToSlide(currentSlide - 1);
            };

            function startAutoTimer() {
                if (prefersReducedMotion) return;
                stopAutoTimer();
                autoTimer = setInterval(function () {
                    currentSlide = (currentSlide + 1) % totalSlides;
                    renderCarousel();
                }, 5000);
            }

            function stopAutoTimer() {
                if (autoTimer) {
                    clearInterval(autoTimer);
                    autoTimer = null;
                }
            }

            function restartAutoTimer() {
                stopAutoTimer();
                startAutoTimer();
            }

            window.pauseCarousel = stopAutoTimer;
            window.resumeCarousel = startAutoTimer;

            startAutoTimer();

            // Hero 3-Image Stacked Fade Carousel (Berganti otomatis setiap 3 detik dengan tumpukan & transisi pudar)
            const heroCards = document.querySelectorAll('#hero-fade-carousel .hero-stack-card');
            let currentHeroIdx = 0;

            function updateHeroStack() {
                const total = heroCards.length;
                heroCards.forEach((card, idx) => {
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
