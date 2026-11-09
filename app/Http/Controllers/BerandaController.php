<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\Wishlist;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BerandaController extends Controller
{
    public function index(Request $request): View
    {
        // Bagian "Barang Terbaru" di Beranda hanya menampilkan barang berstatus `tersedia`
        // yang dibuat dalam 2 hari terakhir, diurutkan paling baru dahulu.
        $barangTerbaru = Barang::with(['penjual', 'kategori', 'kecamatan.induk', 'fotoUtama', 'foto'])
            ->where('status', 'tersedia')
            ->where('created_at', '>=', now()->subDays(2))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        $wishlistBarangIds = [];
        if ($request->user()) {
            $wishlistBarangIds = Wishlist::where('user_id', $request->user()->id)
                ->pluck('barang_id')
                ->all();
        }

        return view('beranda', [
            'barangTerbaru' => $barangTerbaru,
            'wishlistBarangIds' => $wishlistBarangIds,
        ]);
    }
}
