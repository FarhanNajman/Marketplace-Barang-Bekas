<?php

namespace Database\Seeders;

use App\Models\Kategori;
use Illuminate\Database\Seeder;

class KategoriSeeder extends Seeder
{
    /**
     * Tambah daftar kategori awal secara aman tanpa menghapus kategori yang sudah ada.
     */
    public function run(): void
    {
        $daftarKategori = [
            'Elektronik',
            'Pakaian',
            'Perabot',
            'Buku',
            'Kendaraan',
            'Lainnya',
        ];

        foreach ($daftarKategori as $namaKategori) {
            Kategori::firstOrCreate([
                'nama_kategori' => $namaKategori,
            ]);
        }
    }
}

