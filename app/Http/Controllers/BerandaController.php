<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class BerandaController extends Controller
{
    /**
     * Alamat utama `/` langsung menampilkan halaman Katalog Barang.
     */
    public function index(Request $request): View
    {
        return app(BarangController::class)->index($request);
    }
}
