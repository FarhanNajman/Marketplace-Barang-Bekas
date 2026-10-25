@extends('layouts.app')

@section('title', 'Beranda')

@section('content')
    <section class="hero">
        <h1>Marketplace Barang Bekas</h1>
        <p>Tempat jual beli barang bekas yang masih layak pakai. Hemat, praktis, dan ramah lingkungan.</p>
        <a href="{{ route('tentang') }}" class="btn" id="btn-tentang">Pelajari Lebih Lanjut</a>
    </section>

    <section class="cards">
        <article class="card">
            <h2>💸 Hemat</h2>
            <p>Dapatkan barang berkualitas dengan harga lebih terjangkau.</p>
        </article>
        <article class="card">
            <h2>🌱 Ramah Lingkungan</h2>
            <p>Memperpanjang umur barang berarti mengurangi sampah.</p>
        </article>
        <article class="card">
            <h2>🤝 Mudah</h2>
            <p>Fitur jual beli akan dikembangkan pada tahap berikutnya.</p>
        </article>
    </section>
@endsection
