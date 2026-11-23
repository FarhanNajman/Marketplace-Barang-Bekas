<?php

namespace App\Http\Controllers;

use App\Events\PesanChatTerkirim;
use App\Models\Barang;
use App\Models\Pesan;
use App\Models\RoomChat;
use App\Models\User;
use App\Services\WebPushService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class ChatController extends Controller
{
    /**
     * Daftar seluruh room chat di mana pengguna yang sedang login terdaftar sebagai
     * pembeli maupun penjual.
     */
    public function index(Request $request): View
    {
        $userId = (int) $request->user()->id;

        $daftarRoom = RoomChat::with([
            'barang.fotoUtama',
            'barang.foto',
            'barang.kecamatan.induk',
            'pembeli',
            'penjual',
            'pesanTerakhir.pengirim',
        ])
            ->withCount([
                'pesan as unread_count' => function ($query) use ($userId) {
                    $query->where('pengirim_id', '!=', $userId)
                        ->whereNull('dibaca_pada');
                },
            ])
            ->where(function ($query) use ($userId) {
                $query->where('pembeli_id', $userId)
                    ->orWhere('penjual_id', $userId);
            })
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->get();

        return view('chat.index', [
            'daftarRoom' => $daftarRoom,
        ]);
    }

    /**
     * Mulai chat dengan penjual dari halaman detail listing barang, atau buka kembali
     * room chat yang sudah pernah dibuat untuk pembeli & listing yang sama.
     */
    public function mulai(Request $request, Barang $barang): RedirectResponse
    {
        $user = $request->user();
        $userId = (int) $user->id;
        $penjualId = (int) $barang->user_id;

        // Cegah pengguna memulai chat dengan dirinya sendiri
        if ($userId === $penjualId) {
            return redirect()
                ->route('barang.show', $barang)
                ->with('error', 'Anda adalah penjual barang ini dan tidak dapat memulai chat dengan diri sendiri.');
        }

        // Periksa apakah room chat untuk pembeli dan barang ini sudah ada
        $roomSudahAda = RoomChat::where('barang_id', $barang->id)
            ->where('pembeli_id', $userId)
            ->first();

        if ($roomSudahAda) {
            return redirect()->route('chat.show', $roomSudahAda);
        }

        // Jika barang sudah terjual dan belum ada room sebelumnya, cegah pembuatan room chat baru
        if ($barang->status === 'terjual') {
            return redirect()
                ->route('barang.show', $barang)
                ->with('error', 'Barang ini sudah terjual sehingga percakapan baru tidak dapat dimulai.');
        }

        // Validasi kepemilikan listing: penjual_id selalu diambil dari pemilik sah listing barang
        $room = RoomChat::firstOrCreate(
            [
                'barang_id' => $barang->id,
                'pembeli_id' => $userId,
            ],
            [
                'penjual_id' => $penjualId,
            ]
        );

        return redirect()->route('chat.show', $room);
    }

    /**
     * Tampilkan halaman percakapan untuk satu room chat beserta riwayat pesannya.
     * Hanya pembeli dan penjual peserta room tersebut yang diizinkan mengakses.
     */
    public function show(Request $request, int $roomChatId): View|RedirectResponse
    {
        $userId = (int) $request->user()->id;

        $roomChat = RoomChat::with([
            'barang.fotoUtama',
            'barang.foto',
            'barang.kecamatan.induk.induk',
            'pembeli',
            'penjual',
            'pesan' => function ($query) {
                $query->with('pengirim')
                    ->orderBy('created_at', 'asc')
                    ->orderBy('id', 'asc');
            },
        ])->find($roomChatId);

        if (! $roomChat) {
            return redirect()
                ->route('chat.index')
                ->with('error', 'Percakapan yang Anda cari tidak ditemukan atau gagal dimuat.');
        }

        // Kontrol akses ketat: tolak jika pengguna bukan pembeli maupun penjual di room ini
        abort_unless(
            $roomChat->adalahPeserta($userId),
            403,
            'Akses ditolak. Anda bukan peserta dalam room chat ini.'
        );

        // Tandai semua pesan masuk dari lawan bicara yang belum dibaca menjadi sudah dibaca
        Pesan::where('room_chat_id', $roomChat->id)
            ->where('pengirim_id', '!=', $userId)
            ->whereNull('dibaca_pada')
            ->update(['dibaca_pada' => now()]);

        $lawanBicara = $roomChat->lawanBicara($userId);
        $peranLawanBicara = $roomChat->peranLawanBicara($userId);

        // Token terenkripsi per-tab agar jika pengguna menguji 2 akun berbeda di 2 tab pada browser yang sama,
        // identitas pengirim pada tab room chat tetap akurat dan tidak tertukar oleh pergantian cookie sesi tab lain.
        $chatActorToken = Crypt::encryptString((string) json_encode([
            'user_id' => $userId,
            'room_chat_id' => (int) $roomChat->id,
        ]));

        return view('chat.show', [
            'roomChat' => $roomChat,
            'daftarPesan' => $roomChat->pesan,
            'lawanBicara' => $lawanBicara,
            'peranLawanBicara' => $peranLawanBicara,
            'chatActorToken' => $chatActorToken,
        ]);
    }

    /**
     * Resolusi ID pengguna peserta room chat berdasarkan token tab terenkripsi (jika valid untuk room ini)
     * atau fallback ke sesi pengguna saat ini.
     */
    protected function resolveRoomActorUserId(Request $request, RoomChat $roomChat): int
    {
        $rawToken = $request->input('chat_actor_token') ?: $request->header('X-Chat-Actor-Token');

        if (is_string($rawToken) && $rawToken !== '') {
            try {
                $decrypted = Crypt::decryptString($rawToken);
                $data = json_decode($decrypted, true);

                if (
                    is_array($data)
                    && isset($data['user_id'], $data['room_chat_id'])
                    && (int) $data['room_chat_id'] === (int) $roomChat->id
                    && $roomChat->adalahPeserta((int) $data['user_id'])
                ) {
                    return (int) $data['user_id'];
                }
            } catch (Throwable) {
                // Gunakan fallback user dari session
            }
        }

        return (int) $request->user()->id;
    }

    /**
     * Simpan pesan baru di dalam room chat, siarkan secara real-time melalui Laravel Reverb,
     * dan kirim Web Push notification ke perangkat lawan bicara.
     */
    public function kirimPesan(Request $request, int $roomChatId, WebPushService $webPushService): RedirectResponse|JsonResponse
    {
        $roomChat = RoomChat::with('barang')->find($roomChatId);

        if (! $roomChat) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'message' => 'Percakapan tidak ditemukan atau gagal dimuat.',
                ], 404);
            }

            return redirect()
                ->route('chat.index')
                ->with('error', 'Percakapan tidak ditemukan atau gagal dimuat.');
        }

        $userId = $this->resolveRoomActorUserId($request, $roomChat);

        abort_unless(
            $roomChat->adalahPeserta($userId),
            403,
            'Akses ditolak. Anda bukan peserta dalam room chat ini.'
        );

        $validated = $request->validate([
            'isi_pesan' => ['required', 'string', 'max:1000', 'regex:/\S/'],
        ], [
            'isi_pesan.required' => 'Isi pesan wajib diisi sebelum dikirim.',
            'isi_pesan.string' => 'Format isi pesan tidak valid.',
            'isi_pesan.max' => 'Isi pesan terlalu panjang (maksimal 1.000 karakter).',
            'isi_pesan.regex' => 'Pesan tidak boleh hanya berisi spasi kosong.',
        ]);

        $pesan = Pesan::create([
            'room_chat_id' => $roomChat->id,
            'pengirim_id' => $userId,
            'isi_pesan' => trim($validated['isi_pesan']),
            'dibaca_pada' => null,
        ]);

        $pesan->load('pengirim');
        $namaPengirim = $pesan->pengirim?->name ?? 'Pengguna';

        // Perbarui timestamp updated_at pada room chat agar naik ke urutan teratas daftar chat
        $roomChat->touch();

        // Tentukan penerima (jika pengirim adalah pembeli -> kirim ke penjual; jika pengirim adalah penjual -> kirim ke pembeli)
        $penerimaId = ((int) $roomChat->pembeli_id === $userId)
            ? (int) $roomChat->penjual_id
            : (int) $roomChat->pembeli_id;

        $unreadCountPenerima = $this->hitungUnreadUntukPengguna($penerimaId);
        $namaBarang = $roomChat->barang?->nama_barang ?? 'Barang';

        // Siarkan event real-time ke peserta lain di room chat & channel notifikasi penerima melalui Laravel Reverb
        if ($penerimaId !== $userId) {
            try {
                broadcast(new PesanChatTerkirim(
                    $pesan,
                    $penerimaId,
                    $namaBarang,
                    $unreadCountPenerima
                ))->toOthers();
            } catch (Throwable) {
                // Jangan gagalkan penyimpanan pesan apabila daemon Reverb sedang tidak aktif
            }

            // Kirim Web Push notification ke perangkat penerima (tanpa menampilkan isi pesan pribadi di layar kunci)
            $webPushService->sendToUser(
                $penerimaId,
                [
                    'title' => 'Pesan Baru di BARANTAU',
                    'body' => 'Pesan baru dari ' . $namaPengirim . ' mengenai "' . Str::limit($namaBarang, 45) . '". Ketuk untuk membuka room chat.',
                    'url' => route('chat.show', $roomChat->id),
                    'room_chat_id' => (int) $roomChat->id,
                    'tag' => 'barantau-chat-room-' . $roomChat->id,
                    'timestamp' => now()->timestamp * 1000,
                ],
                $userId
            );
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'ok',
                'pesan' => [
                    'id' => (int) $pesan->id,
                    'room_chat_id' => (int) $pesan->room_chat_id,
                    'pengirim_id' => (int) $pesan->pengirim_id,
                    'pengirim_nama' => $namaPengirim,
                    'isi_pesan' => (string) $pesan->isi_pesan,
                    'waktu_format' => $pesan->created_at?->format('d M Y, H:i') ?? now()->format('d M Y, H:i'),
                    'created_at_iso' => $pesan->created_at?->toIso8601String() ?? now()->toIso8601String(),
                    'dibaca' => false,
                ],
            ], 201);
        }

        return redirect()->route('chat.show', $roomChat->id);
    }

    /**
     * Tandai seluruh pesan masuk pada suatu room chat sebagai sudah dibaca ketika penerima
     * menerima pesan real-time saat halaman room chat sedang terbuka.
     */
    public function tandaiDibaca(Request $request, int $roomChatId): JsonResponse
    {
        $roomChat = RoomChat::find($roomChatId);
        if (! $roomChat) {
            return response()->json(['message' => 'Room chat tidak ditemukan.'], 404);
        }

        $userId = $this->resolveRoomActorUserId($request, $roomChat);

        abort_unless(
            $roomChat->adalahPeserta($userId),
            403,
            'Akses ditolak. Anda bukan peserta dalam room chat ini.'
        );

        Pesan::where('room_chat_id', $roomChat->id)
            ->where('pengirim_id', '!=', $userId)
            ->whereNull('dibaca_pada')
            ->update(['dibaca_pada' => now()]);

        return response()->json([
            'status' => 'ok',
            'unread_count' => $this->hitungUnreadUntukPengguna($userId),
        ]);
    }

    /**
     * Ambil pesan terbaru setelah ID pesan tertentu pada room chat yang sedang dibuka,
     * sekaligus menandai pesan masuk tersebut sebagai sudah dibaca.
     */
    public function pesanBaru(Request $request, int $roomChatId): JsonResponse
    {
        $roomChat = RoomChat::find($roomChatId);
        if (! $roomChat) {
            return response()->json(['message' => 'Room chat tidak ditemukan.'], 404);
        }

        $userId = $this->resolveRoomActorUserId($request, $roomChat);

        abort_unless(
            $roomChat->adalahPeserta($userId),
            403,
            'Akses ditolak. Anda bukan peserta dalam room chat ini.'
        );

        $afterId = max(0, (int) $request->query('after_id', 0));

        $pesanBaruList = Pesan::with('pengirim')
            ->where('room_chat_id', $roomChat->id)
            ->where('id', '>', $afterId)
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        if ($pesanBaruList->isNotEmpty()) {
            Pesan::where('room_chat_id', $roomChat->id)
                ->where('pengirim_id', '!=', $userId)
                ->whereNull('dibaca_pada')
                ->update(['dibaca_pada' => now()]);
        }

        // Ambil juga daftar ID pesan milik pengguna sendiri yang sudah dibaca lawan bicara
        $idsMilikSendiriYangSudahDibaca = Pesan::where('room_chat_id', $roomChat->id)
            ->where('pengirim_id', $userId)
            ->whereNotNull('dibaca_pada')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values();

        return response()->json([
            'status' => 'ok',
            'pesan' => $pesanBaruList->map(function (Pesan $p) {
                return [
                    'id' => (int) $p->id,
                    'room_chat_id' => (int) $p->room_chat_id,
                    'pengirim_id' => (int) $p->pengirim_id,
                    'pengirim_nama' => $p->pengirim?->name ?? 'Pengguna',
                    'isi_pesan' => (string) $p->isi_pesan,
                    'waktu_format' => $p->created_at?->format('d M Y, H:i') ?? now()->format('d M Y, H:i'),
                    'created_at_iso' => $p->created_at?->toIso8601String() ?? now()->toIso8601String(),
                    'dibaca' => $p->dibaca_pada !== null,
                ];
            })->values(),
            'read_message_ids' => $idsMilikSendiriYangSudahDibaca,
            'unread_count' => $this->hitungUnreadUntukPengguna($userId),
        ]);
    }

    /**
     * Hitung total pesan masuk yang belum dibaca untuk seorang pengguna di seluruh room chat-nya.
     */
    protected function hitungUnreadUntukPengguna(int $userId): int
    {
        return Pesan::where('pengirim_id', '!=', $userId)
            ->whereNull('dibaca_pada')
            ->whereHas('roomChat', function ($query) use ($userId) {
                $query->where('pembeli_id', $userId)
                    ->orWhere('penjual_id', $userId);
            })
            ->count();
    }

    /**
     * Endpoint JSON ringan untuk memeriksa jumlah pesan belum dibaca dan daftar
     * notifikasi terbaru khusus room chat di mana pengguna menjadi pesertanya.
     */
    public function notifikasiStatus(Request $request): JsonResponse
    {
        $userId = (int) $request->user()->id;

        $queryPesanMasuk = Pesan::with(['pengirim', 'roomChat.barang'])
            ->where('pengirim_id', '!=', $userId)
            ->whereNull('dibaca_pada')
            ->whereHas('roomChat', function ($query) use ($userId) {
                $query->where('pembeli_id', $userId)
                    ->orWhere('penjual_id', $userId);
            });

        $unreadCount = (clone $queryPesanMasuk)->count();

        $pesanTerbaruList = (clone $queryPesanMasuk)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(6)
            ->get();

        $latestMessageId = $pesanTerbaruList->first()?->id ?? 0;

        $items = $pesanTerbaruList->map(function (Pesan $pesan) {
            return [
                'id' => (int) $pesan->id,
                'room_chat_id' => (int) $pesan->room_chat_id,
                'room_url' => route('chat.show', $pesan->room_chat_id),
                'pengirim_nama' => $pesan->pengirim?->name ?? 'Pengguna',
                'pengirim_username' => $pesan->pengirim?->username ?? 'user',
                'nama_barang' => $pesan->roomChat?->barang?->nama_barang ?? 'Barang',
                'cuplikan_pesan' => Str::limit($pesan->isi_pesan, 70),
                'waktu' => $pesan->created_at?->diffForHumans() ?? 'Baru saja',
            ];
        })->values();

        return response()->json([
            'unread_count' => $unreadCount,
            'latest_message_id' => $latestMessageId,
            'items' => $items,
        ]);
    }
}
