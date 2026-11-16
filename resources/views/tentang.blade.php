@extends('layouts.app')

@section('title', 'Tentang')
@section('description', 'Tentang BARANTAU — Marketplace barang bekas lokal.')

@section('fullbleed')
    <div class="site-container page-section" style="padding-bottom: 3.5rem;">
        <div class="page-back-bar">
            <a
                href="{{ route('beranda') }}"
                data-fallback="{{ route('beranda') }}"
                onclick="return goBackBarantau(event, this)"
                class="btn-back-barantau"
                id="btn-kembali-tentang"
                aria-label="Kembali ke halaman sebelumnya"
                title="Kembali"
            >
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M19 12H5M12 19l-7-7 7-7"/>
                </svg>
            </a>
        </div>

            <div class="auth-header">
                <h1>
                    Ruang temu barang baik,<br>
                    <span class="hero-title-italic">lebih panjang guna.</span>
                </h1>
                <p>
                    BarangRantau mempertemukan penjual dan pembeli barang bekas layak pakai di tingkat kecamatan agar transaksi lebih dekat, aman, dan ramah lingkungan.
                </p>
            </div>

            <div class="auth-grid">
                <section class="card">
                    <span class="how-step-num" style="display: block; margin-bottom: 0.75rem;">01</span>
                    <h2>Hemat &amp; Berkelanjutan</h2>
                    <p style="margin-bottom: 0;">
                        Memperpanjang usia pakai perabot, elektronik, sepeda, hingga buku berarti mengurangi limbah sekaligus membantu sesama mendapatkan barang berkualitas dengan harga terjangkau.
                    </p>
                </section>

                <section class="card">
                    <span class="how-step-num" style="display: block; margin-bottom: 0.75rem;">02</span>
                    <h2>Privasi Lokasi Kecamatan</h2>
                    <p style="margin-bottom: 0;">
                        Demi keamanan dan kenyamanan pengguna, lokasi barang hanya ditampilkan hingga tingkat kecamatan. Pembeli dan penjual dapat menyepakati titik temu COD di ruang publik melalui fitur chat.
                    </p>
                </section>
            </div>
        </div>
    </div>

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
                    Tiga langkah sederhana bertransaksi aman secara langsung.
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
                                Gunakan fitur pencarian serta filter kategori dan kecamatan pada halaman Katalog Barang untuk menemukan barang bekas layak pakai yang dekat dari lokasimu. Simpan barang incaran ke Wishlist agar mudah ditemukan kembali.
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
                                src="{{ asset('Assets/Beranda/CHAT.jpg') }}"
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
                                src="{{ asset('Assets/Beranda/DEAL.jpg') }}"
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
        })();
    </script>
@endsection
