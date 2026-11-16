@extends('layouts.app')

@section('title', 'Edit Barang: ' . $barang->nama_barang)
@section('description', 'Edit informasi, foto, atau status barang di Barantau.')

@section('content')
        <header class="auth-header">
            <h1>Edit Barang &amp; Status</h1>
            <p>
                Perbarui informasi barang, ubah status antara <strong>Tersedia</strong> dan <strong>Terjual</strong>, atau kelola foto barang. Foto lama tidak akan terhapus kecuali Anda mencentang opsi hapus pada foto terkait.
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

        @if ($kecamatanList->isEmpty())
            <div class="alert alert-error" role="alert">
                <strong>Data wilayah kecamatan belum tersedia di database.</strong><br>
                Pastikan tabel <code>wilayah</code> memiliki data dengan <code>jenis_wilayah = 'kecamatan'</code> terlebih dahulu.
            </div>
        @endif

        <div class="card">
            <form
                method="POST"
                action="{{ route('barang.update', $barang) }}"
                enctype="multipart/form-data"
                id="form-edit-barang"
                novalidate
            >
                @csrf
                @method('PUT')

                <div class="form-group">
                    <label for="nama_barang">Nama Barang *</label>
                    <input
                        type="text"
                        id="nama_barang"
                        name="nama_barang"
                        class="form-control"
                        value="{{ old('nama_barang', $barang->nama_barang) }}"
                        maxlength="150"
                        required
                    >
                    @error('nama_barang')
                        <span class="text-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-row-two">
                    <div class="form-group">
                        <label for="status">Status Barang *</label>
                        @php
                            $statusTerpilih = old('status', $barang->status);
                        @endphp
                        <select id="status" name="status" class="form-control" required>
                            <option value="tersedia" {{ $statusTerpilih === 'tersedia' ? 'selected' : '' }}>
                                Tersedia (Masih Dijual)
                            </option>
                            <option value="terjual" {{ $statusTerpilih === 'terjual' ? 'selected' : '' }}>
                                Terjual (Sudah Laku)
                            </option>
                        </select>
                        <span class="helper-text">Ubah ke <strong>Terjual</strong> jika barang ini sudah laku.</span>
                        @error('status')
                            <span class="text-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="kategori_id">Kategori *</label>
                        @php
                            $kategoriTerpilih = (int) old('kategori_id', $barang->kategori_id);
                        @endphp
                        <select id="kategori_id" name="kategori_id" class="form-control" required>
                            <option value="">-- Pilih Kategori --</option>
                            @foreach ($kategoriList as $kat)
                                <option value="{{ $kat->id }}" {{ $kategoriTerpilih === $kat->id ? 'selected' : '' }}>
                                    {{ $kat->nama_kategori }}
                                </option>
                            @endforeach
                        </select>
                        @error('kategori_id')
                            <span class="text-error">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="form-row-two">
                    <div class="form-group">
                        <label for="kondisi">Kondisi Barang *</label>
                        @php
                            $kondisiTerpilih = old('kondisi', $barang->kondisi);
                            $daftarKondisi = $pilihanKondisi;
                            if ($kondisiTerpilih && ! in_array($kondisiTerpilih, $daftarKondisi, true)) {
                                array_unshift($daftarKondisi, $kondisiTerpilih);
                            }
                        @endphp
                        <select id="kondisi" name="kondisi" class="form-control" required>
                            <option value="">-- Pilih Kondisi Barang --</option>
                            @foreach ($daftarKondisi as $opsiKondisi)
                                <option value="{{ $opsiKondisi }}" {{ $kondisiTerpilih === $opsiKondisi ? 'selected' : '' }}>
                                    {{ $opsiKondisi }}
                                </option>
                            @endforeach
                        </select>
                        @error('kondisi')
                            <span class="text-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="harga">Harga (Rupiah) *</label>
                        <input
                            type="number"
                            id="harga"
                            name="harga"
                            class="form-control"
                            value="{{ old('harga', $barang->harga) }}"
                            min="1"
                            step="1"
                            required
                        >
                        <span class="helper-text">Angka bulat dalam Rupiah lebih dari 0.</span>
                        @error('harga')
                            <span class="text-error">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="form-group">
                    <label for="kecamatan_id">Kecamatan Lokasi Barang *</label>
                    @php
                        $kecamatanTerpilih = (int) old('kecamatan_id', $barang->kecamatan_id);
                    @endphp
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
                                    {{ $kecamatanTerpilih === $kec->id ? 'selected' : '' }}
                                >
                                    {{ $kec->labelLengkap() }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                    <span class="helper-text">Ketik nama kecamatan atau kabupaten/kota untuk mencari lokasi dengan cepat.</span>
                    @error('kecamatan_id')
                        <span class="text-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="deskripsi">Deskripsi Barang *</label>
                    <textarea
                        id="deskripsi"
                        name="deskripsi"
                        rows="5"
                        class="form-control"
                        required
                    >{{ old('deskripsi', $barang->deskripsi) }}</textarea>
                    @error('deskripsi')
                        <span class="text-error">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Foto Saat Ini -->
                <div class="form-group" style="margin-top: 1.75rem;">
                    <label>Foto Barang Saat Ini ({{ $barang->foto->count() }} Foto)</label>
                    @if ($barang->foto->isNotEmpty())
                        <p class="helper-text" style="margin-top: 0; margin-bottom: 0.75rem;">
                            Centang <strong>"Hapus foto ini"</strong> pada foto yang ingin Anda hapus saat menyimpan perubahan. Jika tidak dicentang, foto lama akan tetap tersimpan.
                        </p>
                        @php
                            $hapusFotoLama = collect(old('hapus_foto', []))->map(fn ($id) => (int) $id)->all();
                        @endphp
                        <div class="edit-photos-grid">
                            @foreach ($barang->foto as $idx => $foto)
                                <div class="edit-photo-item">
                                    <div class="edit-photo-preview">
                                        <img src="{{ $foto->urlFoto() }}" alt="Foto {{ $idx + 1 }} {{ $barang->nama_barang }}">
                                        @if ($idx === 0)
                                            <span class="edit-photo-badge">Utama</span>
                                        @endif
                                    </div>
                                    <label class="edit-photo-delete-label" for="hapus_foto_{{ $foto->id }}">
                                        <input
                                            type="checkbox"
                                            id="hapus_foto_{{ $foto->id }}"
                                            name="hapus_foto[]"
                                            value="{{ $foto->id }}"
                                            {{ in_array($foto->id, $hapusFotoLama, true) ? 'checked' : '' }}
                                        >
                                        <span>Hapus foto ini</span>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="helper-text">Belum ada foto tersimpan untuk barang ini.</p>
                    @endif
                </div>

                <!-- Tambah Foto Baru -->
                <div class="form-group" style="margin-top: 1.5rem;">
                    <label for="foto_baru">Tambah Foto Baru <span class="badge-optional">Opsional</span></label>
                    <div class="image-upload-box" data-image-upload-box>
                        <input
                            type="file"
                            id="foto_baru"
                            name="foto_baru[]"
                            class="image-upload-input"
                            accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                            multiple
                        >
                        <span class="image-upload-arrow" aria-hidden="true">
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 4v13"/>
                                <path d="m6 12 6 6 6-6"/>
                            </svg>
                        </span>
                        <span class="image-upload-title">Pilih Gambar</span>
                        <span class="image-upload-filename" data-upload-filename></span>
                    </div>
                    <span class="helper-text">
                        Kosongkan jika tidak ingin menambah foto baru. Total foto per barang maksimal 5 foto (JPG, JPEG, PNG, WEBP, maks. 2MB per foto).
                    </span>
                    @error('foto_baru')
                        <span class="text-error">{{ $message }}</span>
                    @enderror
                    @foreach ($errors->get('foto_baru.*') as $messages)
                        @foreach ($messages as $msg)
                            <span class="text-error">{{ $msg }}</span>
                        @endforeach
                    @endforeach
                </div>

                <div style="margin-top: 2rem; display: flex; flex-wrap: wrap; align-items: center; gap: 0.85rem;">
                    <button
                        type="submit"
                        class="btn"
                        id="btn-update-barang"
                        {{ $kecamatanList->isEmpty() ? 'disabled' : '' }}
                    >
                        Simpan Perubahan
                    </button>
                    <a href="{{ route('barang.show', $barang) }}" class="button-outline" style="padding: 0.9rem 1.35rem;">
                        Batal
                    </a>
                </div>
            </form>
        </div>

        <!-- Zona Hapus Barang (Soft Delete) -->
        <div class="card card-danger-zone" style="margin-top: 2rem;">
            <h2 class="danger-zone-title" style="font-size: 1.25rem;">Hapus Listing Barang</h2>
            <p style="margin-bottom: 1rem;">
                Menghapus barang akan menyembunyikan listing ini dari katalog, beranda, dan profil Anda (menggunakan mekanisme <em>soft delete</em>). Riwayat chat atau pesan terkait barang ini tidak ikut terhapus.
            </p>
            <form
                method="POST"
                action="{{ route('barang.destroy', $barang) }}"
                onsubmit="return confirm('Apakah Anda yakin ingin menghapus barang ini dari katalog?');"
            >
                @csrf
                @method('DELETE')
                <button type="submit" class="button-danger" id="btn-hapus-barang-edit">
                    Hapus Barang Ini
                </button>
            </form>
        </div>
    </div>
@endsection

