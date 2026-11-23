@extends('layouts.app')

@section('title', 'Daftar Chat')
@section('description', 'Daftar percakapan antara pembeli dan penjual untuk koordinasi transaksi COD di BARANTAU.')

@section('content')
    @if (session('status'))
        <div class="alert alert-success" role="status" id="alert-status">
            {{ session('status') }}
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-error" role="alert" id="alert-error">
            {{ session('error') }}
        </div>
    @endif

    <div class="chat-page-header">
        <div>
            <h1 class="font-display" style="font-size: clamp(1.75rem, 3vw, 2.35rem); font-weight: 600; letter-spacing: -0.04em;">
                Daftar Chat
            </h1>
            <p style="margin-top: 0.35rem; color: var(--muted); font-size: 0.94rem;">
                Koordinasikan detail kondisi barang dan kesepakatan titik temu COD bersama penjual atau pembeli.
            </p>
        </div>

        <!-- Kontrol On/Off Notifikasi Pesan Baru (Web Push HP & In-App) -->
        <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 0.35rem;">
            <div class="chat-notif-setting-box">
                <span class="chat-notif-setting-label">Notifikasi Chat (HP &amp; Web)</span>
                <button
                    type="button"
                    class="chat-notif-switch-btn"
                    data-chat-notif-toggle
                    role="switch"
                    aria-checked="true"
                    onclick="toggleBarantauChatNotif(event)"
                >
                    <span class="chat-notif-switch-track" aria-hidden="true">
                        <span class="chat-notif-switch-thumb"></span>
                    </span>
                    <span class="chat-notif-switch-state" data-chat-notif-state>ON</span>
                </button>
            </div>
            <p class="helper-text" data-webpush-status-text style="margin: 0; font-size: 0.76rem; text-align: right; max-width: 340px;">
                Mengatur status notifikasi perangkat...
            </p>
        </div>
    </div>

    @if ($daftarRoom->isNotEmpty())
        <div class="chat-room-list" id="chat-room-list">
            @foreach ($daftarRoom as $room)
                @php
                    $userId = (int) auth()->id();
                    $lawan = $room->lawanBicara($userId);
                    $peranLawan = $room->peranLawanBicara($userId);
                    $barang = $room->barang;
                    $pesanTerakhir = $room->pesanTerakhir;
                    $unreadCount = (int) ($room->unread_count ?? 0);
                    $isBarangTerjual = $barang && $barang->status === 'terjual';
                @endphp
                <article class="chat-room-card {{ $unreadCount > 0 ? 'has-unread' : '' }}" data-room-card="{{ $room->id }}">
                    <a href="{{ route('chat.show', $room->id) }}" class="chat-room-main-link" aria-label="Buka percakapan dengan {{ $lawan?->name ?? 'Pengguna' }} mengenai {{ $barang?->nama_barang ?? 'Barang' }}">
                        <div class="chat-room-avatar">
                            @if ($lawan && $lawan->urlFotoProfil())
                                <img src="{{ $lawan->urlFotoProfil() }}" alt="Foto profil {{ $lawan->username }}">
                            @else
                                <span>{{ $lawan ? $lawan->inisial() : 'U' }}</span>
                            @endif
                        </div>

                        <div class="chat-room-body">
                            <div class="chat-room-top">
                                <div class="chat-room-partner">
                                    <strong class="chat-room-partner-name">{{ $lawan?->name ?? 'Pengguna' }}</strong>
                                    @if ($lawan)
                                        <span class="chat-room-partner-username">{{ '@' . $lawan->username }}</span>
                                    @endif
                                    <span class="chat-role-badge">{{ $peranLawan }}</span>
                                </div>

                                <div class="chat-room-meta-right">
                                    @if ($pesanTerakhir)
                                        <time class="chat-room-time" datetime="{{ $pesanTerakhir->created_at?->toIso8601String() }}">
                                            {{ $pesanTerakhir->created_at?->diffForHumans() }}
                                        </time>
                                    @else
                                        <time class="chat-room-time">
                                            {{ $room->created_at?->diffForHumans() }}
                                        </time>
                                    @endif

                                    @if ($unreadCount > 0)
                                        <span class="chat-unread-pill" aria-label="{{ $unreadCount }} pesan belum dibaca">
                                            {{ $unreadCount }} baru
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <p class="chat-room-preview">
                                @if ($pesanTerakhir)
                                    @if ((int) $pesanTerakhir->pengirim_id === $userId)
                                        <span class="chat-preview-sender">Anda:</span>
                                    @endif
                                    {{ \Illuminate\Support\Str::limit($pesanTerakhir->isi_pesan, 100) }}
                                @else
                                    <span style="font-style: italic;">Belum ada pesan. Klik untuk memulai percakapan.</span>
                                @endif
                            </p>
                        </div>
                    </a>

                    @if ($barang)
                        <div class="chat-room-item-strip">
                            <a href="{{ route('barang.show', $barang) }}" class="chat-room-item-link">
                                <div class="chat-room-item-thumb">
                                    @if ($barang->urlFotoUtama())
                                        <img src="{{ $barang->urlFotoUtama() }}" alt="{{ $barang->nama_barang }}">
                                    @else
                                        <span>Foto</span>
                                    @endif
                                </div>
                                <div class="chat-room-item-info">
                                    <span class="chat-room-item-title">{{ $barang->nama_barang }}</span>
                                    <span class="chat-room-item-price">{{ $barang->hargaRupiah() }}</span>
                                </div>
                                @if ($isBarangTerjual)
                                    <span class="card-status-tag sold">Terjual</span>
                                @endif
                                <span class="chat-room-item-cta">Detail Barang &rarr;</span>
                            </a>
                        </div>
                    @endif
                </article>
            @endforeach
        </div>
    @else
        <div class="empty-listing-box" id="empty-chat-list">
            <p class="font-display" style="font-size: 1.4rem; font-weight: 600; color: var(--text);">
                Daftar chat Anda masih kosong.
            </p>
            <p style="margin-top: 0.45rem; margin-bottom: 1.3rem; max-width: 34rem; margin-left: auto; margin-right: auto;">
                Pilih barang yang Anda minati di katalog, lalu tekan tombol <strong>Chat Penjual</strong> pada halaman detail barang untuk menanyakan kondisi barang atau menyepakati titik temu COD.
            </p>
            <a href="{{ route('beranda') }}" class="button-primary">
                Jelajahi Katalog Barang
            </a>
        </div>
    @endif

    <script>
        window.addEventListener('barantau:pesan-baru', function (event) {
            const payload = event.detail;
            if (!payload || !payload.room_chat_id) return;

            const card = document.querySelector('[data-room-card="' + payload.room_chat_id + '"]');
            if (!card) return;

            card.classList.add('has-unread');

            const previewEl = card.querySelector('.chat-room-preview');
            if (previewEl) {
                previewEl.textContent = payload.cuplikan_pesan || payload.isi_pesan || '';
            }

            const timeEl = card.querySelector('.chat-room-time');
            if (timeEl) {
                timeEl.textContent = payload.waktu_relatif || 'Baru saja';
            }

            const metaRight = card.querySelector('.chat-room-meta-right');
            if (metaRight) {
                let pill = metaRight.querySelector('.chat-unread-pill');
                if (!pill) {
                    pill = document.createElement('span');
                    pill.className = 'chat-unread-pill';
                    pill.textContent = '1 baru';
                    metaRight.appendChild(pill);
                } else {
                    const currentNum = parseInt(pill.textContent || '0', 10) || 0;
                    pill.textContent = (currentNum + 1) + ' baru';
                }
            }

            const list = document.getElementById('chat-room-list');
            if (list && list.firstElementChild !== card) {
                list.insertBefore(card, list.firstElementChild);
            }
        });
    </script>
@endsection
