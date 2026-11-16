<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WilayahSeeder extends Seeder
{
    /**
     * Impor data wilayah (provinsi, kabupaten_kota, kecamatan) dari file CSV hasil sortir
     * hanya jika tabel `wilayah` masih kosong. Jangan menimpa atau menduplikasi data yang sudah ada.
     */
    public function run(): void
    {
        if (DB::table('wilayah')->exists()) {
            $this->command?->info('Tabel wilayah sudah berisi data. Melewati impor agar tidak menimpa atau menduplikasi data.');

            return;
        }

        $candidatePaths = [
            'C:\Users\LENOVO\Documents\Codex\2026-09-29\referenced-chatgpt-conversation-this-is-an\wilayah_barantau_prov_kabkota_kecamatan.csv',
            database_path('seeders/data/wilayah.csv'),
        ];

        $resolvedPath = null;
        foreach ($candidatePaths as $path) {
            if (is_file($path) && is_readable($path)) {
                $resolvedPath = $path;
                break;
            }
        }

        if ($resolvedPath === null) {
            $this->command?->warn(
                'File CSV wilayah belum ditemukan atau tidak dapat diakses. Data wilayah palsu tidak dibuat.'
            );

            return;
        }

        $handle = fopen($resolvedPath, 'r');
        if ($handle === false) {
            $this->command?->error("Gagal membuka file data wilayah: {$resolvedPath}");

            return;
        }

        // Lewati baris header: id, nama_wilayah, jenis_wilayah, id_induk
        fgetcsv($handle);

        $now = now();
        $rows = [];

        while (($data = fgetcsv($handle)) !== false) {
            if (count($data) < 4 || trim((string) $data[0]) === '') {
                continue;
            }

            $idIndukRaw = trim((string) $data[3]);

            $rows[] = [
                'id' => (int) trim((string) $data[0]),
                'nama_wilayah' => trim((string) $data[1]),
                'jenis_wilayah' => trim((string) $data[2]),
                'id_induk' => $idIndukRaw === '' ? null : (int) $idIndukRaw,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        fclose($handle);

        if ($rows === []) {
            $this->command?->warn('File CSV wilayah kosong atau format tidak sesuai.');

            return;
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('wilayah')->insertOrIgnore($chunk);
        }

        $this->command?->info('Berhasil mengimpor ' . count($rows) . ' data wilayah ke tabel wilayah.');
    }
}
