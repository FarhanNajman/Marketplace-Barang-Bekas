@extends('layouts.app')

@section('title', 'Tentang')
@section('description', 'Tentang BarangRantau / BalikPakai — Marketplace barang bekas lokal.')

@section('content')
    <div class="auth-wrapper">
        <div class="eyebrow">
            <span class="eyebrow-dot"></span>
            Tentang Platform
        </div>

        <div class="auth-header">
            <h1>
                Ruang temu barang baik,<br>
                <span class="hero-title-italic">lebih panjang guna.</span>
            </h1>
            <p>
                BarangRantau (BalikPakai) mempertemukan penjual dan pembeli barang bekas layak pakai di tingkat kecamatan agar transaksi lebih dekat, aman, dan ramah lingkungan.
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
@endsection
