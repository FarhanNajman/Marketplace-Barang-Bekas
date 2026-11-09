<?php

namespace Tests\Feature;

use App\Models\Barang;
use App\Models\Kategori;
use App\Models\User;
use App\Models\Wilayah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfilPenjualTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_profil_penjual_menampilkan_username_bio_dan_memisahkan_barang_tersedia_serta_terjual(): void
    {
        $penjual = User::factory()->create([
            'name' => 'Raka Pratama',
            'username' => 'raka_pratama',
            'bio' => 'Penjual kamera analog dan barang klasik.',
        ]);

        $kategori = Kategori::create(['nama_kategori' => 'Elektronik']);
        $kecamatan = Wilayah::create([
            'nama_wilayah' => 'Menteng',
            'jenis_wilayah' => 'kecamatan',
            'id_induk' => null,
        ]);

        Barang::create([
            'user_id' => $penjual->id,
            'kategori_id' => $kategori->id,
            'kecamatan_id' => $kecamatan->id,
            'nama_barang' => 'Kamera Olympus Tersedia',
            'deskripsi' => 'Kondisi sangat baik',
            'harga' => 1500000,
            'kondisi' => 'Bekas Mulus',
            'status' => 'tersedia',
        ]);

        Barang::create([
            'user_id' => $penjual->id,
            'kategori_id' => $kategori->id,
            'kecamatan_id' => $kecamatan->id,
            'nama_barang' => 'Lensa Klasik Sudah Laku',
            'deskripsi' => 'Sudah terjual minggu lalu',
            'harga' => 850000,
            'kondisi' => 'Layak Pakai',
            'status' => 'terjual',
        ]);

        $response = $this->get(route('profil.show', ['username' => 'raka_pratama']));

        $response->assertStatus(200);
        $response->assertSee('@raka_pratama');
        $response->assertSee('Raka Pratama');
        $response->assertSee('Penjual kamera analog dan barang klasik.');
        $response->assertSee('Barang Tersedia (1)');
        $response->assertSee('Kamera Olympus Tersedia');
        $response->assertSee('Barang Terjual (1)');
        $response->assertSee('Lensa Klasik Sudah Laku');

        // Pengguna B (pengguna lain yang login) juga dapat melihat profil & listing tersedia maupun terjual milik Pengguna A
        $penggunaB = User::factory()->create(['username' => 'pengguna_b']);
        $responseB = $this->actingAs($penggunaB)->get(route('profil.show', ['username' => 'raka_pratama']));
        $responseB->assertStatus(200);
        $responseB->assertSee('Kamera Olympus Tersedia');
        $responseB->assertSee('Lensa Klasik Sudah Laku');
        $responseB->assertDontSee('Edit Profil');

        // Beranda hanya menampilkan barang yang tersedia, tidak menampilkan barang yang sudah terjual
        $berandaResponse = $this->get(route('beranda'));
        $berandaResponse->assertStatus(200);
        $berandaResponse->assertSee('Kamera Olympus Tersedia');
        $berandaResponse->assertDontSee('Lensa Klasik Sudah Laku');
    }

    public function test_pengguna_dapat_mengubah_username_bio_dan_foto_profil(): void
    {
        Storage::fake('public');

        $user = User::factory()->create([
            'name' => 'Nama Lama',
            'username' => 'user_lama',
            'bio' => 'Bio lama',
        ]);

        $file = UploadedFile::fake()->image('avatar.jpg', 300, 300);

        $response = $this->actingAs($user)->put(route('profil.update'), [
            'name' => 'Nama Baru',
            'username' => 'user_baru',
            'bio' => 'Bio baru yang sudah diperbarui.',
            'foto_profil' => $file,
        ]);

        $response->assertRedirect(route('profil.show', ['username' => 'user_baru']));

        $user->refresh();
        $this->assertSame('Nama Baru', $user->name);
        $this->assertSame('user_baru', $user->username);
        $this->assertSame('Bio baru yang sudah diperbarui.', $user->bio);
        $this->assertNotNull($user->foto_profil);
        Storage::disk('public')->assertExists($user->foto_profil);
    }
}

