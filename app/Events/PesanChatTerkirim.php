<?php

namespace App\Events;

use App\Models\Pesan;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class PesanChatTerkirim implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Pesan $pesan,
        public int $penerimaId,
        public string $namaBarang,
        public int $unreadCountPenerima,
    ) {}

    /**
     * Kirim ke private channel room chat tersebut dan private channel notifikasi penerima.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('room-chat.' . $this->pesan->room_chat_id),
            new PrivateChannel('user-notifikasi.' . $this->penerimaId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'PesanChatTerkirim';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id' => (int) $this->pesan->id,
            'room_chat_id' => (int) $this->pesan->room_chat_id,
            'room_url' => route('chat.show', $this->pesan->room_chat_id),
            'pengirim_id' => (int) $this->pesan->pengirim_id,
            'pengirim_nama' => $this->pesan->pengirim?->name ?? 'Pengguna',
            'pengirim_username' => $this->pesan->pengirim?->username ?? 'user',
            'penerima_id' => $this->penerimaId,
            'nama_barang' => $this->namaBarang,
            'isi_pesan' => (string) $this->pesan->isi_pesan,
            'cuplikan_pesan' => Str::limit((string) $this->pesan->isi_pesan, 70),
            'waktu_format' => $this->pesan->created_at?->format('d M Y, H:i') ?? now()->format('d M Y, H:i'),
            'waktu_relatif' => $this->pesan->created_at?->diffForHumans() ?? 'Baru saja',
            'created_at_iso' => $this->pesan->created_at?->toIso8601String() ?? now()->toIso8601String(),
            'unread_count_penerima' => $this->unreadCountPenerima,
        ];
    }
}
