@extends('layouts.app')

@section('title', 'Jual Barang Baru')
@section('description', 'Jual barang bekas layak pakai di BARANTAU.')

@section('content')
    <div class="auth-wrapper">
        <div class="eyebrow">
            <span class="eyebrow-dot"></span>
            Listing Barang Baru
        </div>

        <header class="auth-header">
            <h1>Jual Barang</h1>
            <p>
                Satu listing mewakili satu barang fisik. Isi informasi barang secara jujur dan jelas, lalu unggah foto barang terbaikmu. Status awal barang otomatis menjadi <strong>Tersedia</strong>.
            </p>
        </header>

        @if ($errors->any())
            <div class="alert alert-error" role="alert">
                <strong>Mohon periksa kembali isian form Anda:</strong>
                <ul style="margin-top: 0.5rem; padding-left: 1.25rem;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($kategoriList->isEmpty())
            <div class="alert alert-demo" role="alert">
                <strong>Data kategori belum tersedia.</strong><br>
                Tabel <code>kategori</code> masih kosong. Silakan jalankan perintah <code>php artisan db:seed --class=KategoriSeeder</code> pada terminal proyek untuk mengisi kategori awal.
            </div>
        @endif

        @if ($kecamatanList->isEmpty())
            <div class="alert alert-error" role="alert" id="alert-kecamatan-kosong">
                <strong>Data wilayah kecamatan belum tersedia di database.</strong><br>
                Saat ini belum ada data wilayah bertipe <code>kecamatan</code> pada tabel <code>wilayah</code>. Silakan jalankan perintah <code>php artisan db:seed --class=WilayahSeeder</code> pada terminal proyek (atau pastikan file CSV wilayah tersedia) terlebih dahulu sebelum menyimpan listing barang baru.
            </div>
        @endif

        <div class="card">
            <form
                method="POST"
                action="{{ route('barang.store') }}"
                enctype="multipart/form-data"
                id="form-tambah-barang"
                novalidate
            >
                @csrf

                <div class="form-group">
                    <label for="nama_barang">Nama Barang *</label>
                    <input
                        type="text"
                        id="nama_barang"
                        name="nama_barang"
                        class="form-control"
                        value="{{ old('nama_barang') }}"
                        maxlength="150"
                        required
                    >
                    @error('nama_barang')
                        <span class="text-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-row-two">
                    <div class="form-group">
                        <label for="kategori_id">Kategori *</label>
                        <select id="kategori_id" name="kategori_id" class="form-control" required>
                            <option value="">-- Pilih Kategori --</option>
                            @foreach ($kategoriList as $kat)
                                <option value="{{ $kat->id }}" {{ (int) old('kategori_id') === $kat->id ? 'selected' : '' }}>
                                    {{ $kat->nama_kategori }}
                                </option>
                            @endforeach
                        </select>
                        @error('kategori_id')
                            <span class="text-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="kondisi">Kondisi Barang *</label>
                        <select id="kondisi" name="kondisi" class="form-control" required>
                            <option value="">-- Pilih Kondisi Barang --</option>
                            @foreach ($pilihanKondisi as $opsiKondisi)
                                <option value="{{ $opsiKondisi }}" {{ old('kondisi') === $opsiKondisi ? 'selected' : '' }}>
                                    {{ $opsiKondisi }}
                                </option>
                            @endforeach
                        </select>
                        @error('kondisi')
                            <span class="text-error">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="form-row-two">
                    <div class="form-group">
                        <label for="harga">Harga (Rupiah) *</label>
                        <input
                            type="number"
                            id="harga"
                            name="harga"
                            class="form-control"
                            value="{{ old('harga') }}"
                            min="1"
                            step="1"
                            required
                        >
                        <span class="helper-text">Masukkan angka bulat lebih dari 0 tanpa titik atau koma (misal: <code>150000</code> untuk Rp150.000).</span>
                        @error('harga')
                            <span class="text-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="kecamatan_id">Kecamatan Lokasi Barang *</label>
                        <select
                            id="kecamatan_id"
                            name="kecamatan_id"
                            class="form-control"
                            data-searchable-wilayah
                            {{ $kecamatanList->isEmpty() ? 'disabled' : '' }}
                            required
                        >
                            @if ($kecamatanList->isEmpty())
                                <option value="">-- Data kecamatan belum tersedia --</option>
                            @else
                                <option value="">-- Pilih Kecamatan --</option>
                                @foreach ($kecamatanList as $kec)
                                    <option
                                        value="{{ $kec->id }}"
                                        data-provinsi="{{ $kec->namaProvinsi() }}"
                                        {{ (int) old('kecamatan_id') === $kec->id ? 'selected' : '' }}
                                    >
                                        {{ $kec->labelLengkap() }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                        <span class="helper-text">Ketik untuk mencari kecamatan dengan cepat. Demi privasi, hanya tingkat kecamatan yang ditampilkan ke publik.</span>
                        @error('kecamatan_id')
                            <span class="text-error">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="form-group">
                    <label for="deskripsi">Deskripsi Barang *</label>
                    <textarea
                        id="deskripsi"
                        name="deskripsi"
                        rows="5"
                        class="form-control"
                        required
                    >{{ old('deskripsi') }}</textarea>
                    <span class="helper-text">Jelaskan kondisi fisik, kelengkapan, lama pemakaian, dan alasan dijual. Jangan cantumkan alamat rumah lengkap demi privasi Anda.</span>
                    @error('deskripsi')
                        <span class="text-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="foto">Foto Barang (1–5 Foto) *</label>
                    <input
                        type="file"
                        id="foto"
                        name="foto[]"
                        class="form-control"
                        accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                        multiple
                        required
                    >
                    <span class="helper-text">
                        Anda dapat memilih 1 hingga 5 foto sekaligus. Format yang didukung: <strong>JPG, JPEG, PNG, WEBP</strong> (maksimal 2MB per foto). Foto pertama akan menjadi foto sampul utama.
                    </span>
                    @error('foto')
                        <span class="text-error">{{ $message }}</span>
                    @enderror
                    @foreach ($errors->get('foto.*') as $messages)
                        @foreach ($messages as $msg)
                            <span class="text-error">{{ $msg }}</span>
                        @endforeach
                    @endforeach
                </div>

                <div style="margin-top: 2rem; display: flex; flex-wrap: wrap; align-items: center; gap: 0.85rem;">
                    <button
                        type="submit"
                        class="btn"
                        id="btn-simpan-barang"
                        {{ ($kecamatanList->isEmpty() || $kategoriList->isEmpty()) ? 'disabled' : '' }}
                    >
                        Simpan &amp; Tayangkan Barang
                    </button>
                    <a href="{{ route('barang.index') }}" class="button-outline" style="padding: 0.9rem 1.35rem;">
                        Batal
                    </a>
                </div>
            </form>
        </div>
    </div>
@endsection

