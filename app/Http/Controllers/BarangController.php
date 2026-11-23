<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\FotoBarang;
use App\Models\Kategori;
use App\Models\Wilayah;
use App\Models\Wishlist;
use Database\Seeders\KategoriSeeder;
use Database\Seeders\WilayahSeeder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BarangController extends Controller
{
    /**
     * Daftar pilihan kondisi barang standar agar memudahkan penjual pemula.
     *
     * @return list<string>
     */
    private function daftarPilihanKondisi(): array
    {
        return [
            'Seperti Baru',
            'Sangat Baik',
            'Baik / Layak Pakai',
            'Butuh Perbaikan Ringan',
        ];
    }

    /**
     * Halaman Katalog Barang (ditampilkan langsung di `/` maupun `/barang`):
     * menampilkan semua barang yang belum di-soft delete, diurutkan dari yang terbaru,
     * mendukung filter cepat "Baru ditambahkan" (2 hari terakhir), pencarian nama barang,
     * serta filter kategori, kecamatan, dan status.
     */
    public function index(Request $request): View
    {
        if (Kategori::count() === 0) {
            (new KategoriSeeder())->run();
        }

        if (Wilayah::count() === 0) {
            (new WilayahSeeder())->run();
        }

        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'kategori_id' => ['nullable', 'integer', 'exists:kategori,id'],
            'provinsi_id' => [
                'nullable',
                'integer',
                Rule::exists('wilayah', 'id')->where('jenis_wilayah', 'provinsi'),
            ],
            'kabupaten_kota_id' => [
                'nullable',
                'integer',
                Rule::exists('wilayah', 'id')->where('jenis_wilayah', 'kabupaten_kota'),
            ],
            'kecamatan_id' => [
                'nullable',
                'integer',
                Rule::exists('wilayah', 'id')->where('jenis_wilayah', 'kecamatan'),
            ],
            'status' => ['nullable', 'string', Rule::in(['tersedia', 'terjual'])],
            'baru' => ['nullable', 'in:0,1,true'],
            'urutkan' => ['nullable', 'string', Rule::in(['terbaru', 'terlama', 'harga_asc', 'harga_desc'])],
        ]);

        $kataKunci = isset($validated['q']) ? trim($validated['q']) : '';
        $kategoriId = isset($validated['kategori_id']) ? (int) $validated['kategori_id'] : null;
        $provinsiId = isset($validated['provinsi_id']) ? (int) $validated['provinsi_id'] : null;
        $kabupatenKotaId = isset($validated['kabupaten_kota_id']) ? (int) $validated['kabupaten_kota_id'] : null;
        $kecamatanId = isset($validated['kecamatan_id']) ? (int) $validated['kecamatan_id'] : null;
        $statusFilter = $validated['status'] ?? '';
        $hanyaBaruDitambahkan = in_array((string) ($validated['baru'] ?? ''), ['1', 'true'], true);
        $urutkan = $validated['urutkan'] ?? 'terbaru';

        // Sinkronisasi hierarki wilayah bertingkat (Provinsi -> Kabupaten/Kota -> Kecamatan)
        $kecamatanTerpilih = null;
        $kabupatenTerpilih = null;
        $provinsiTerpilih = null;

        if ($kecamatanId) {
            $kecamatanTerpilih = Wilayah::with('induk.induk')
                ->where('jenis_wilayah', 'kecamatan')
                ->find($kecamatanId);

            if ($kecamatanTerpilih) {
                $kabupatenTerpilih = $kecamatanTerpilih->induk;
                $provinsiTerpilih = $kecamatanTerpilih->induk?->induk;
                $kabupatenKotaId = $kabupatenTerpilih?->id;
                $provinsiId = $provinsiTerpilih?->id;
            }
        } elseif ($kabupatenKotaId) {
            $kabupatenTerpilih = Wilayah::with('induk')
                ->where('jenis_wilayah', 'kabupaten_kota')
                ->find($kabupatenKotaId);

            if ($kabupatenTerpilih) {
                if ($provinsiId && (int) $kabupatenTerpilih->id_induk !== $provinsiId) {
                    // Jika pengguna mengganti provinsi dan kabupaten/kota lama tidak cocok, reset kabupaten/kota
                    $kabupatenTerpilih = null;
                    $kabupatenKotaId = null;
                } else {
                    $provinsiTerpilih = $kabupatenTerpilih->induk;
                    $provinsiId = $provinsiTerpilih?->id;
                }
            }
        }

        if ($provinsiId && ! $provinsiTerpilih) {
            $provinsiTerpilih = Wilayah::where('jenis_wilayah', 'provinsi')->find($provinsiId);
        }

        // Model Barang menggunakan SoftDeletes, sehingga barang dengan deleted_at terisi
        // otomatis tidak diikutsertakan dalam query ini.
        $query = Barang::with(['penjual', 'kategori', 'kecamatan.induk.induk', 'fotoUtama', 'foto']);

        if ($kataKunci !== '') {
            $query->where('nama_barang', 'like', '%' . $kataKunci . '%');
        }

        if ($kategoriId) {
            $query->where('kategori_id', $kategoriId);
        }

        if ($kecamatanId) {
            $query->where('kecamatan_id', $kecamatanId);
        } elseif ($kabupatenKotaId) {
            $query->whereHas('kecamatan', function ($q) use ($kabupatenKotaId) {
                $q->where('id_induk', $kabupatenKotaId);
            });
        } elseif ($provinsiId) {
            $query->whereHas('kecamatan.induk', function ($q) use ($provinsiId) {
                $q->where('id_induk', $provinsiId);
            });
        }

        if ($statusFilter !== '') {
            $query->where('status', $statusFilter);
        }

        // Filter cepat "Baru ditambahkan": hanya menampilkan barang yang diposting dalam 2 hari terakhir.
        if ($hanyaBaruDitambahkan) {
            $query->where('created_at', '>=', now()->subDays(2));
        }

        // Pengurutan hasil pencarian
        switch ($urutkan) {
            case 'harga_asc':
                $query->orderBy('harga', 'asc')->orderByDesc('id');
                break;
            case 'harga_desc':
                $query->orderBy('harga', 'desc')->orderByDesc('id');
                break;
            case 'terlama':
                $query->orderBy('created_at', 'asc')->orderBy('id', 'asc');
                break;
            case 'terbaru':
            default:
                $query->orderByDesc('created_at')->orderByDesc('id');
                break;
        }

        // Pertahankan seluruh query filter & urutan saat berpindah halaman hasil pencarian
        $daftarBarang = $query->paginate(12)->withQueryString();

        $kategoriList = Kategori::orderBy('nama_kategori')->get();
        $kecamatanList = Wilayah::with('induk.induk')
            ->where('jenis_wilayah', 'kecamatan')
            ->orderBy('nama_wilayah')
            ->get();

        // Daftar wilayah bertingkat dari tabel wilayah
        $provinsiList = Wilayah::where('jenis_wilayah', 'provinsi')
            ->orderBy('nama_wilayah')
            ->get();

        $kabupatenKotaList = $provinsiId
            ? Wilayah::where('jenis_wilayah', 'kabupaten_kota')
                ->where('id_induk', $provinsiId)
                ->orderBy('nama_wilayah')
                ->get()
            : collect();

        $kecamatanFilterList = $kabupatenKotaId
            ? Wilayah::where('jenis_wilayah', 'kecamatan')
                ->where('id_induk', $kabupatenKotaId)
                ->orderBy('nama_wilayah')
                ->get()
            : collect();

        $wishlistBarangIds = [];
        if ($request->user()) {
            $wishlistBarangIds = Wishlist::where('user_id', $request->user()->id)
                ->pluck('barang_id')
                ->all();
        }

        return view('barang.index', [
            'daftarBarang' => $daftarBarang,
            'kategoriList' => $kategoriList,
            'kecamatanList' => $kecamatanList,
            'provinsiList' => $provinsiList,
            'kabupatenKotaList' => $kabupatenKotaList,
            'kecamatanFilterList' => $kecamatanFilterList,
            'kataKunci' => $kataKunci,
            'kategoriId' => $kategoriId,
            'provinsiId' => $provinsiId,
            'kabupatenKotaId' => $kabupatenKotaId,
            'kecamatanId' => $kecamatanId,
            'provinsiTerpilih' => $provinsiTerpilih,
            'kabupatenTerpilih' => $kabupatenTerpilih,
            'kecamatanTerpilih' => $kecamatanTerpilih,
            'statusFilter' => $statusFilter,
            'hanyaBaruDitambahkan' => $hanyaBaruDitambahkan,
            'urutkan' => $urutkan,
            'wishlistBarangIds' => $wishlistBarangIds,
        ]);
    }

    /**
     * Endpoint JSON untuk memuat daftar sub-wilayah bertingkat (Kabupaten/Kota berdasarkan Provinsi,
     * atau Kecamatan berdasarkan Kabupaten/Kota) langsung dari tabel wilayah.
     */
    public function subWilayah(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id_induk' => ['required', 'integer', 'exists:wilayah,id'],
            'jenis_wilayah' => ['nullable', 'string', Rule::in(['kabupaten_kota', 'kecamatan'])],
        ]);

        $query = Wilayah::where('id_induk', (int) $validated['id_induk']);

        if (! empty($validated['jenis_wilayah'])) {
            $query->where('jenis_wilayah', $validated['jenis_wilayah']);
        }

        $items = $query
            ->orderBy('nama_wilayah')
            ->get(['id', 'nama_wilayah', 'jenis_wilayah', 'id_induk']);

        return response()->json([
            'data' => $items,
        ]);
    }

    /**
     * Halaman Form Tambah Barang.
     */
    public function create(): View
    {
        if (Kategori::count() === 0) {
            (new KategoriSeeder())->run();
        }

        if (Wilayah::count() === 0) {
            (new WilayahSeeder())->run();
        }

        $kategoriList = Kategori::orderBy('nama_kategori')->get();
        $kecamatanList = Wilayah::with('induk.induk')
            ->where('jenis_wilayah', 'kecamatan')
            ->orderBy('nama_wilayah')
            ->get();

        return view('barang.create', [
            'kategoriList' => $kategoriList,
            'kecamatanList' => $kecamatanList,
            'pilihanKondisi' => $this->daftarPilihanKondisi(),
        ]);
    }

    /**
     * Simpan listing barang baru beserta foto-fotonya ke database dan disk `public`.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nama_barang' => ['required', 'string', 'max:150'],
            'kategori_id' => ['required', 'integer', 'exists:kategori,id'],
            'kecamatan_id' => [
                'required',
                'integer',
                Rule::exists('wilayah', 'id')->where('jenis_wilayah', 'kecamatan'),
            ],
            'harga' => ['required', 'integer', 'min:1'],
            'kondisi' => ['required', 'string', 'max:50'],
            'deskripsi' => ['required', 'string'],
            'foto' => ['required', 'array', 'min:1', 'max:5'],
            'foto.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'nama_barang.required' => 'Nama barang wajib diisi.',
            'nama_barang.max' => 'Nama barang maksimal 150 karakter.',
            'kategori_id.required' => 'Kategori barang wajib dipilih.',
            'kategori_id.exists' => 'Kategori yang dipilih tidak valid.',
            'kecamatan_id.required' => 'Kecamatan lokasi barang wajib dipilih.',
            'kecamatan_id.exists' => 'Kecamatan yang dipilih tidak valid atau bukan tingkat kecamatan.',
            'harga.required' => 'Harga barang wajib diisi.',
            'harga.integer' => 'Harga harus berupa angka bulat dalam Rupiah (tanpa titik atau koma).',
            'harga.min' => 'Harga barang harus lebih dari Rp0.',
            'kondisi.required' => 'Kondisi barang wajib dipilih atau diisi.',
            'kondisi.max' => 'Kondisi barang maksimal 50 karakter.',
            'deskripsi.required' => 'Deskripsi barang wajib diisi.',
            'foto.required' => 'Unggah minimal 1 foto barang.',
            'foto.array' => 'Format unggahan foto tidak valid.',
            'foto.min' => 'Unggah minimal 1 foto barang.',
            'foto.max' => 'Jumlah foto barang maksimal 5 foto.',
            'foto.*.required' => 'File foto barang wajib diunggah.',
            'foto.*.image' => 'Setiap file yang diunggah harus berupa gambar.',
            'foto.*.mimes' => 'Format foto harus JPG, JPEG, PNG, atau WEBP.',
            'foto.*.max' => 'Ukuran setiap foto maksimal 2MB (2048 KB).',
        ]);

        $barang = Barang::create([
            'user_id' => $request->user()->id,
            'kategori_id' => (int) $validated['kategori_id'],
            'kecamatan_id' => (int) $validated['kecamatan_id'],
            'nama_barang' => trim($validated['nama_barang']),
            'deskripsi' => trim($validated['deskripsi']),
            'harga' => (int) $validated['harga'],
            'kondisi' => trim($validated['kondisi']),
            'status' => 'tersedia',
        ]);

        if ($request->hasFile('foto')) {
            foreach ($request->file('foto') as $fileFoto) {
                $path = $fileFoto->store('foto-barang', 'public');

                FotoBarang::create([
                    'barang_id' => $barang->id,
                    'path_foto' => $path,
                ]);
            }
        }

        return redirect()
            ->route('barang.show', $barang)
            ->with('status', 'Barang berhasil dipasang dan kini tampil di katalog.');
    }

    /**
     * Halaman Detail Barang: menampilkan galeri seluruh foto, nama, harga, kondisi,
     * kategori, deskripsi, status, kecamatan, serta username dan foto profil penjual.
     */
    public function show(Request $request, Barang $barang): View
    {
        $barang->load(['penjual', 'kategori', 'kecamatan.induk.induk', 'foto']);

        $dalamWishlist = false;
        if ($request->user()) {
            $dalamWishlist = Wishlist::where('user_id', $request->user()->id)
                ->where('barang_id', $barang->id)
                ->exists();
        }

        // Periksa apakah route chat sudah tersedia di proyek
        $namaRouteChat = null;
        foreach (['chat.mulai', 'chat.show', 'chat.index', 'room-chat.store'] as $calonRoute) {
            if (Route::has($calonRoute)) {
                $namaRouteChat = $calonRoute;
                break;
            }
        }

        return view('barang.show', [
            'barang' => $barang,
            'dalamWishlist' => $dalamWishlist,
            'namaRouteChat' => $namaRouteChat,
        ]);
    }

    /**
     * Halaman Form Edit Barang: hanya pemilik barang yang boleh mengakses.
     */
    public function edit(Request $request, Barang $barang): View
    {
        abort_if(
            $barang->user_id !== $request->user()->id,
            403,
            'Anda tidak memiliki izin untuk mengedit barang milik pengguna lain.'
        );

        if (Kategori::count() === 0) {
            (new KategoriSeeder())->run();
        }

        if (Wilayah::count() === 0) {
            (new WilayahSeeder())->run();
        }

        $barang->load(['foto', 'kategori', 'kecamatan.induk']);

        $kategoriList = Kategori::orderBy('nama_kategori')->get();
        $kecamatanList = Wilayah::with('induk.induk')
            ->where('jenis_wilayah', 'kecamatan')
            ->orderBy('nama_wilayah')
            ->get();

        return view('barang.edit', [
            'barang' => $barang,
            'kategoriList' => $kategoriList,
            'kecamatanList' => $kecamatanList,
            'pilihanKondisi' => $this->daftarPilihanKondisi(),
        ]);
    }

    /**
     * Simpan perubahan data barang, status (`tersedia` / `terjual`), penghapusan foto
     * tertentu, serta penambahan foto baru.
     */
    public function update(Request $request, Barang $barang): RedirectResponse
    {
        abort_if(
            $barang->user_id !== $request->user()->id,
            403,
            'Anda tidak memiliki izin untuk mengubah barang milik pengguna lain.'
        );

        $validated = $request->validate([
            'nama_barang' => ['required', 'string', 'max:150'],
            'kategori_id' => ['required', 'integer', 'exists:kategori,id'],
            'kecamatan_id' => [
                'required',
                'integer',
                Rule::exists('wilayah', 'id')->where('jenis_wilayah', 'kecamatan'),
            ],
            'harga' => ['required', 'integer', 'min:1'],
            'kondisi' => ['required', 'string', 'max:50'],
            'status' => ['required', Rule::in(['tersedia', 'terjual'])],
            'deskripsi' => ['required', 'string'],
            'hapus_foto' => ['nullable', 'array'],
            'hapus_foto.*' => ['integer'],
            'foto_baru' => ['nullable', 'array', 'max:5'],
            'foto_baru.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'nama_barang.required' => 'Nama barang wajib diisi.',
            'nama_barang.max' => 'Nama barang maksimal 150 karakter.',
            'kategori_id.required' => 'Kategori barang wajib dipilih.',
            'kategori_id.exists' => 'Kategori yang dipilih tidak valid.',
            'kecamatan_id.required' => 'Kecamatan lokasi barang wajib dipilih.',
            'kecamatan_id.exists' => 'Kecamatan yang dipilih tidak valid atau bukan tingkat kecamatan.',
            'harga.required' => 'Harga barang wajib diisi.',
            'harga.integer' => 'Harga harus berupa angka bulat dalam Rupiah (tanpa titik atau koma).',
            'harga.min' => 'Harga barang harus lebih dari Rp0.',
            'kondisi.required' => 'Kondisi barang wajib dipilih atau diisi.',
            'kondisi.max' => 'Kondisi barang maksimal 50 karakter.',
            'status.required' => 'Status barang wajib dipilih.',
            'status.in' => 'Status barang hanya boleh Tersedia atau Terjual.',
            'deskripsi.required' => 'Deskripsi barang wajib diisi.',
            'foto_baru.array' => 'Format unggahan foto baru tidak valid.',
            'foto_baru.max' => 'Maksimal 5 foto baru dalam sekali unggah.',
            'foto_baru.*.image' => 'Setiap file foto baru harus berupa gambar.',
            'foto_baru.*.mimes' => 'Format foto baru harus JPG, JPEG, PNG, atau WEBP.',
            'foto_baru.*.max' => 'Ukuran setiap foto maksimal 2MB (2048 KB).',
        ]);

        $barang->load('foto');

        $idsHapus = collect($validated['hapus_foto'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->all();

        $fotoYangAkanDihapus = $barang->foto->whereIn('id', $idsHapus);
        $sisaFotoLama = $barang->foto->count() - $fotoYangAkanDihapus->count();
        $fileFotoBaru = $request->file('foto_baru', []);
        $jumlahFotoBaru = is_array($fileFotoBaru) ? count($fileFotoBaru) : 0;
        $totalFotoAkhir = $sisaFotoLama + $jumlahFotoBaru;

        if ($barang->foto->count() > 0 && $totalFotoAkhir < 1) {
            return back()
                ->withErrors([
                    'foto_baru' => 'Barang harus memiliki minimal 1 foto. Jangan menghapus seluruh foto lama tanpa mengunggah foto baru.',
                ])
                ->withInput();
        }

        if ($totalFotoAkhir > 5) {
            return back()
                ->withErrors([
                    'foto_baru' => 'Total foto untuk satu barang maksimal 5 foto (saat ini tersisa ' . $sisaFotoLama . ' foto lama dan Anda memilih ' . $jumlahFotoBaru . ' foto baru).',
                ])
                ->withInput();
        }

        // Hapus hanya foto-foto lama yang dicentang oleh pemilik barang
        foreach ($fotoYangAkanDihapus as $fotoLama) {
            if (
                $fotoLama->path_foto
                && ! str_starts_with($fotoLama->path_foto, 'http://')
                && ! str_starts_with($fotoLama->path_foto, 'https://')
                && ! str_starts_with($fotoLama->path_foto, 'images/')
            ) {
                Storage::disk('public')->delete($fotoLama->path_foto);
            }

            $fotoLama->delete();
        }

        // Simpan foto baru apabila penjual mengunggah foto tambahan
        if ($request->hasFile('foto_baru')) {
            foreach ($request->file('foto_baru') as $fileFoto) {
                $path = $fileFoto->store('foto-barang', 'public');

                FotoBarang::create([
                    'barang_id' => $barang->id,
                    'path_foto' => $path,
                ]);
            }
        }

        $barang->update([
            'kategori_id' => (int) $validated['kategori_id'],
            'kecamatan_id' => (int) $validated['kecamatan_id'],
            'nama_barang' => trim($validated['nama_barang']),
            'deskripsi' => trim($validated['deskripsi']),
            'harga' => (int) $validated['harga'],
            'kondisi' => trim($validated['kondisi']),
            'status' => $validated['status'],
        ]);

        return redirect()
            ->route('barang.show', $barang)
            ->with('status', 'Perubahan data barang berhasil disimpan.');
    }

    /**
     * Ubah status barang secara cepat antara `tersedia` dan `terjual` (khusus pemilik).
     */
    public function updateStatus(Request $request, Barang $barang): RedirectResponse
    {
        abort_if(
            $barang->user_id !== $request->user()->id,
            403,
            'Anda tidak memiliki izin untuk mengubah status barang ini.'
        );

        $validated = $request->validate([
            'status' => ['required', Rule::in(['tersedia', 'terjual'])],
        ]);

        $barang->update([
            'status' => $validated['status'],
        ]);

        $labelStatus = $validated['status'] === 'terjual' ? 'Terjual' : 'Tersedia';

        return redirect()
            ->route('barang.show', $barang)
            ->with('status', 'Status barang berhasil diubah menjadi "' . $labelStatus . '".');
    }

    /**
     * Soft delete barang milik pengguna (mengisi kolom `deleted_at` tanpa menghapus
     * room chat, pesan, atau data berelasi lainnya).
     */
    public function destroy(Request $request, Barang $barang): RedirectResponse
    {
        abort_if(
            $barang->user_id !== $request->user()->id,
            403,
            'Anda tidak memiliki izin untuk menghapus barang milik pengguna lain.'
        );

        $namaBarang = $barang->nama_barang;

        // Menggunakan SoftDeletes (mengisi deleted_at)
        $barang->delete();

        return redirect()
            ->route('barang.index')
            ->with('status', 'Barang "' . $namaBarang . '" berhasil dihapus dari katalog.');
    }
}
