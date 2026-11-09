<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\Wishlist;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WishlistController extends Controller
{
    /**
     * Tampilkan halaman Wishlist milik pengguna yang sedang login.
     */
    public function index(Request $request): View
    {
        $items = Wishlist::with(['barang.penjual', 'barang.kategori', 'barang.kecamatan.induk', 'barang.fotoUtama', 'barang.foto'])
            ->where('user_id', $request->user()->id)
            ->whereHas('barang')
            ->latest()
            ->get();

        return view('wishlist.index', [
            'items' => $items,
        ]);
    }

    /**
     * Tambah atau hapus barang dari Wishlist (toggle tombol hati).
     */
    public function toggle(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'barang_id' => ['required', 'integer', 'exists:barang,id'],
        ], [
            'barang_id.required' => 'Barang tidak valid.',
            'barang_id.exists' => 'Barang tidak ditemukan.',
        ]);

        $barang = Barang::findOrFail($validated['barang_id']);
        $userId = $request->user()->id;

        $existing = Wishlist::where('user_id', $userId)
            ->where('barang_id', $barang->id)
            ->first();

        if ($existing) {
            $existing->delete();
            return back()->with('status', '"' . $barang->nama_barang . '" dihapus dari Wishlist.');
        }

        Wishlist::firstOrCreate([
            'user_id' => $userId,
            'barang_id' => $barang->id,
        ]);

        return back()->with('status', '"' . $barang->nama_barang . '" ditambahkan ke Wishlist.');
    }

    /**
     * Hapus barang dari Wishlist.
     */
    public function destroy(Request $request, Barang $barang): RedirectResponse
    {
        Wishlist::where('user_id', $request->user()->id)
            ->where('barang_id', $barang->id)
            ->delete();

        return back()->with('status', '"' . $barang->nama_barang . '" dihapus dari Wishlist.');
    }
}

