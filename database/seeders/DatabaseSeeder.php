<?php

namespace Database\Seeders;

use App\Models\Barang;
use App\Models\FotoBarang;
use App\Models\Kategori;
use App\Models\User;
use App\Models\Wilayah;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $kategoriData = [
            'Elektronik',
            'Rumah & Furnitur',
            'Fashion',
            'Hobi & Koleksi',
            'Olahraga',
        ];

        $kategoriMap = [];
        foreach ($kategoriData as $nama) {
            $kategoriMap[$nama] = Kategori::firstOrCreate(['nama_kategori' => $nama]);
        }

        // Wilayah: Provinsi -> Kabupaten/Kota -> Kecamatan
        $lokasiSamples = [
            ['prov' => 'DKI Jakarta', 'kab' => 'Jakarta Pusat', 'kec' => 'Menteng'],
            ['prov' => 'Jawa Barat', 'kab' => 'Kota Bandung', 'kec' => 'Coblong'],
            ['prov' => 'DI Yogyakarta', 'kab' => 'Kota Yogyakarta', 'kec' => 'Mergangsan'],
            ['prov' => 'Jawa Timur', 'kab' => 'Kota Surabaya', 'kec' => 'Wonokromo'],
            ['prov' => 'Bali', 'kab' => 'Kab. Badung', 'kec' => 'Kuta Utara'],
            ['prov' => 'Jawa Tengah', 'kab' => 'Kota Semarang', 'kec' => 'Banyumanik'],
        ];

        $kecamatanMap = [];
        foreach ($lokasiSamples as $lok) {
            $prov = Wilayah::firstOrCreate([
                'nama_wilayah' => $lok['prov'],
                'jenis_wilayah' => 'provinsi',
                'id_induk' => null,
            ]);
            $kab = Wilayah::firstOrCreate([
                'nama_wilayah' => $lok['kab'],
                'jenis_wilayah' => 'kabupaten_kota',
                'id_induk' => $prov->id,
            ]);
            $kec = Wilayah::firstOrCreate([
                'nama_wilayah' => $lok['kec'],
                'jenis_wilayah' => 'kecamatan',
                'id_induk' => $kab->id,
            ]);
            $kecamatanMap[$lok['kec']] = $kec;
        }

        $penjual1 = User::updateOrCreate(
            ['username' => 'raka_pratama'],
            [
                'name' => 'Raka Pratama',
                'email' => 'raka@example.com',
                'no_hp' => '081234567801',
                'email_verified_at' => now(),
                'no_hp_verified_at' => now(),
                'password' => Hash::make('password123'),
                'foto_profil' => 'images/avatar-default.svg',
                'bio' => 'Kolektor kamera analog, sepeda komuter, dan perabot minimalis. Siap COD sekitar Menteng & Bandung.',
            ]
        );

        $penjual2 = User::updateOrCreate(
            ['username' => 'nadia_lestari'],
            [
                'name' => 'Nadia Lestari',
                'email' => 'nadia@example.com',
                'no_hp' => '081234567802',
                'email_verified_at' => now(),
                'no_hp_verified_at' => now(),
                'password' => Hash::make('password123'),
                'foto_profil' => 'images/avatar-default.svg',
                'bio' => 'Suka merapikan isi rumah dan melepas barang layak pakai agar hidup lagi di pemilik baru.',
            ]
        );

        // Belum ada barang di list karena fitur CRUD barang akan dibuat pada tahap berikutnya.
        FotoBarang::query()->delete();
        Barang::withTrashed()->forceDelete();
    }
}
