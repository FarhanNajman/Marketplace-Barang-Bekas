<?php

use App\Models\RoomChat;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Private channel untuk Room Chat dan Notifikasi Pengguna di BARANTAU.
| Hanya pembeli dan penjual yang terdaftar pada room chat terkait yang
| diizinkan mendengarkan event pesan pada channel room tersebut.
|
*/

Broadcast::channel('room-chat.{roomChatId}', function (User $user, int $roomChatId): bool {
    $roomChat = RoomChat::find($roomChatId);

    if (! $roomChat) {
        return false;
    }

    return $roomChat->adalahPeserta((int) $user->id);
});

Broadcast::channel('user-notifikasi.{userId}', function (User $user, int $userId): bool {
    return (int) $user->id === (int) $userId;
});
