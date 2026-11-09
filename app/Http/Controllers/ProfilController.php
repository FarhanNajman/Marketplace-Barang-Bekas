<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfilController extends Controller
{
    /**
     * Tampilkan halaman profil publik penjual/pengguna beserta listing barang
     * yang dipisahkan menjadi barang tersedia dan barang terjual.
     */
    public function show(string $username): View
    {
        $penjual = User::where('username', $username)->firstOrFail();

        $barangTersedia = $penjual->barang()
            ->with(['kategori', 'kecamatan.induk', 'fotoUtama', 'foto'])
            ->where('status', 'tersedia')
            ->latest()
            ->get();

        $barangTerjual = $penjual->barang()
            ->with(['kategori', 'kecamatan.induk', 'fotoUtama', 'foto'])
            ->where('status', 'terjual')
            ->latest()
            ->get();

        return view('profil.show', [
            'penjual' => $penjual,
            'barangTersedia' => $barangTersedia,
            'barangTerjual' => $barangTerjual,
        ]);
    }

    /**
     * Tampilkan form edit profil pengguna yang sedang masuk.
     */
    public function edit(Request $request): View
    {
        return view('profil.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Perbarui profil pengguna (nama, username, foto profil, dan bio).
     */
    public function update(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => [
                'required',
                'string',
                'max:50',
                'alpha_dash',
                Rule::unique('users', 'username')->ignore($user->id),
            ],
            'bio' => ['nullable', 'string', 'max:500'],
            'foto_profil' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'username.required' => 'Username wajib diisi.',
            'username.alpha_dash' => 'Username hanya boleh berisi huruf, angka, tanda hubung, dan garis bawah.',
            'username.unique' => 'Username sudah digunakan oleh pengguna lain.',
            'bio.max' => 'Bio maksimal 500 karakter.',
            'foto_profil.image' => 'File foto profil harus berupa gambar.',
            'foto_profil.mimes' => 'Format foto profil harus JPG, JPEG, PNG, atau WEBP.',
            'foto_profil.max' => 'Ukuran foto profil maksimal 2MB.',
        ]);

        $data = [
            'name' => trim($validated['name']),
            'username' => trim($validated['username']),
            'bio' => isset($validated['bio']) && trim($validated['bio']) !== '' ? trim($validated['bio']) : null,
        ];

        if ($request->hasFile('foto_profil')) {
            if ($user->foto_profil && ! str_starts_with($user->foto_profil, 'http')) {
                Storage::disk('public')->delete($user->foto_profil);
            }

            $data['foto_profil'] = $request->file('foto_profil')->store('foto-profil', 'public');
        }

        $user->update($data);

        return redirect()
            ->route('profil.show', ['username' => $user->username])
            ->with('status', 'Profil berhasil diperbarui.');
    }
}

