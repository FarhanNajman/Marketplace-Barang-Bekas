<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\Kategori;
use App\Models\Wilayah;
use App\Models\Wishlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

class BarangController extends Controller
{
    /**
     * Halaman Daftar Barang: menampilkan listing yang masih tersedia dan belum di-soft delete,
     * mendukung pencarian serta filter kategori dan kecamatan, diurutkan terbaru dahulu.
     */
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'kategori_id' => ['nullable', 'integer', 'exists:kategori,id'],
            'kecamatan_id' => ['nullable', 'integer', 'exists:wilayah,id'],
        ]);

        $kataKunci = isset($validated['q']) ? trim($validated['q']) : '';
        $kategoriId = $validated['kategori_id'] ?? null;
        $kecamatanId = $validated['kecamatan_id'] ?? null;

        $query = Barang::with(['penjual', 'kategori', 'kecamatan.induk', 'fotoUtama', 'foto'])
            ->where('status', 'tersedia');

        if ($kataKunci !== '') {
            $query->where(function ($subQuery) use ($kataKunci) {
                $subQuery->where('nama_barang', 'like', '%' . $kataKunci . '%')
                    ->orWhere('deskripsi', 'like', '%' . $kataKunci . '%')
                    ->orWhereHas('kecamatan', function ($kecQuery) use ($kataKunci) {
                        $kecQuery->where('nama_wilayah', 'like', '%' . $kataKunci . '%');
                    });
            });
        }

        if ($kategoriId) {
            $query->where('kategori_id', $kategoriId);
        }

        if ($kecamatanId) {
            $query->where('kecamatan_id', $kecamatanId);
        }

        $daftarBarang = $query
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        $kategoriList = Kategori::orderBy('nama_kategori')->get();
        $kecamatanList = Wilayah::with('induk')
            ->where('jenis_wilayah', 'kecamatan')
            ->orderBy('nama_wilayah')
            ->get();

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
            'kataKunci' => $kataKunci,
            'kategoriId' => $kategoriId,
            'kecamatanId' => $kecamatanId,
            'wishlistBarangIds' => $wishlistBarangIds,
        ]);
    }

    /**
     * Halaman Detail Barang: menampilkan foto-foto, nama, harga, kondisi, deskripsi,
     * status, penjual, dan lokasi tingkat kecamatan saja.
     */
    public function show(Request $request, Barang $barang): View
    {
        $barang->load(['penjual', 'kategori', 'kecamatan.induk', 'foto']);

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
}

