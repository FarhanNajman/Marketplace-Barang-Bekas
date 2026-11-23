@extends('layouts.app')

@php
    $barang = $roomChat->barang;
    $userId = (int) auth()->id();
    $namaLawan = $lawanBicara?->name ?? 'Pengguna';
    $usernameLawan = $lawanBicara?->username;
    $isBarangTerjual = $barang && $barang->status === 'terjual';
@endphp

@section('title', 'Chat dengan ' . $namaLawan . ($barang ? ' — ' . $barang->nama_barang : ''))
@section('description', 'Percakapan koordinasi COD mengenai ' . ($barang?->nama_barang ?? 'barang') . ' di BARANTAU.')
@section('custom_back_bar', '1')

{{-- Navbar Khusus Halaman Room Chat (Menggantikan Navbar Utama) --}}
@section('chat_room_header')
    <header class="site-header chat-room-header" id="site-header">
        <div class="site-container chat-room-navbar-inner">
            <!-- 1. Tombol Navigasi Kembali di Pojok Kiri (Kembali ke Halaman Sebelumnya atau Daftar Chat) -->
            <div class="chat-navbar-left">
                <a
                    href="{{ route('chat.index') }}"
                    data-fallback="{{ route('chat.index') }}"
                    onclick="return goBackBarantau(event, this)"
                    class="btn-back-barantau"
                    id="btn-kembali-room-chat"
                    aria-label="Kembali ke halaman sebelumnya atau daftar chat"
                    title="Kembali"
                >
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M19 12H5M12 19l-7-7 7-7"/>
                    </svg>
                </a>

                <!-- 2. Logo BARANTAU yang Mengarah ke Halaman Katalog -->
                <a href="{{ route('beranda') }}" class="brand-logo chat-navbar-brand" id="nav-brand-chat" title="Ke Katalog BARANTAU" aria-label="BARANTAU - Ke Halaman Katalog">
                    <img
                        src="{{ asset('Assets/LOGO/LOGO BARANTAU V1 RMV.png') }}"
                        alt="Logo BARANTAU"
                        class="brand-logo-img logo-light"
                    >
                    <img
                        src="{{ asset('Assets/LOGO/LOGO BARANTAU V2 RMV.png') }}"
                        alt="Logo BARANTAU"
                        class="brand-logo-img logo-dark"
                    >
                    <span class="brand-wordmark" aria-hidden="true">BA<span class="brand-r">R</span><span class="brand-after-r">ANTAU</span></span>
                </a>
            </div>

            <!-- 3. Ringkasan Barang Terkait Room Chat (Dapat Ditekan & Mengarah ke Detail Barang) -->
            @if ($barang)
                <a
                    href="{{ route('barang.show', $barang) }}"
                    class="chat-navbar-item-summary"
                    id="link-ringkasan-barang-chat"
                    title="Lihat halaman detail {{ $barang->nama_barang }}"
                    aria-label="Lihat detail barang {{ $barang->nama_barang }}"
                >
                    <div class="chat-navbar-item-photo">
                        @if ($barang->urlFotoUtama())
                            <img src="{{ $barang->urlFotoUtama() }}" alt="{{ $barang->nama_barang }}">
                        @else
                            <span>Foto</span>
                        @endif
                    </div>
                    <div class="chat-navbar-item-text">
                        <span class="chat-navbar-item-name">{{ $barang->nama_barang }}</span>
                        <span class="chat-navbar-item-meta">
                            <strong>{{ $barang->hargaRupiah() }}</strong>
                            @if ($isBarangTerjual)
                                <span class="card-status-tag sold" style="padding: 0.1rem 0.45rem; font-size: 0.65rem;">Terjual</span>
                            @endif
                        </span>
                    </div>
                    <span class="chat-navbar-item-arrow" aria-hidden="true">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M5 12h14M13 6l6 6-6 6"/>
                        </svg>
                    </span>
                </a>
            @endif
        </div>
    </header>
@endsection

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

    <div class="chat-conversation-wrapper">
        <!-- Info Lawan Bicara & Navigasi Cepat -->
        <div class="chat-partner-bar">
            <div class="chat-partner-identity">
                <div class="chat-room-avatar">
                    @if ($lawanBicara && $lawanBicara->urlFotoProfil())
                        <img src="{{ $lawanBicara->urlFotoProfil() }}" alt="Foto profil {{ $lawanBicara->username }}">
                    @else
                        <span>{{ $lawanBicara ? $lawanBicara->inisial() : 'U' }}</span>
                    @endif
                </div>
                <div>
                    <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 0.45rem;">
                        <h1 class="chat-partner-title">{{ $namaLawan }}</h1>
                        @if ($usernameLawan)
                            <a href="{{ route('profil.show', ['username' => $usernameLawan]) }}" class="chat-partner-username">
                                {{ '@' . $usernameLawan }}
                            </a>
                        @endif
                        <span class="chat-role-badge">{{ $peranLawanBicara }}</span>
                    </div>
                    @if ($barang && $barang->kecamatan)
                        <p class="chat-partner-location">
                            Lokasi barang: {{ $barang->kecamatan->labelHierarkiLengkap() }}
                        </p>
                    @endif
                </div>
            </div>

            <div class="chat-partner-actions">
                <a href="{{ route('chat.index') }}" class="button-outline" style="padding: 0.48rem 0.85rem; font-size: 0.78rem;">
                    Semua Chat
                </a>
                @if ($barang)
                    <a href="{{ route('barang.show', $barang) }}" class="button-outline" style="padding: 0.48rem 0.85rem; font-size: 0.78rem;">
                        Detail Barang
                    </a>
                @endif
            </div>
        </div>

        <!-- Panduan Singkat Transaksi COD -->
        <div class="chat-cod-banner" role="note">
            <strong>Koordinasi COD Aman:</strong> Sepakati titik temu di tempat publik yang ramai pada tingkat kecamatan, dan periksa kondisi fisik barang secara langsung sebelum menyelesaikan pembayaran di tempat.
        </div>

        <!-- Area Riwayat Pesan -->
        <div class="chat-messages-container" id="chat-messages-box" aria-live="polite">
            @if ($daftarPesan->isEmpty())
                <div class="chat-empty-conversation">
                    <p style="font-weight: 700; color: var(--text); font-size: 1rem;">
                        Belum ada pesan di room chat ini.
                    </p>
                    <p style="margin-top: 0.35rem; font-size: 0.88rem; color: var(--muted);">
                        Kirim pesan pertama Anda di bawah untuk menanyakan detail kondisi barang atau menyepakati jadwal serta lokasi pertemuan COD.
                    </p>
                </div>
            @else
                @foreach ($daftarPesan as $pesan)
                    @php
                        $milikSendiri = (int) $pesan->pengirim_id === $userId;
                        $namaPengirim = $milikSendiri
                            ? 'Anda (' . auth()->user()->name . ')'
                            : ($pesan->pengirim?->name ?? 'Pengguna');
                    @endphp
                    <div class="chat-bubble-row {{ $milikSendiri ? 'is-mine' : 'is-theirs' }}" id="pesan-{{ $pesan->id }}">
                        <div class="chat-bubble">
                            <div class="chat-bubble-header">
                                <span class="chat-bubble-sender">{{ $namaPengirim }}</span>
                                <time class="chat-bubble-time" datetime="{{ $pesan->created_at?->toIso8601String() }}">
                                    {{ $pesan->created_at?->format('d M Y, H:i') }}
                                </time>
                            </div>
                            {{-- Tampilkan isi pesan sebagai teks aman (otomatis di-escape oleh Blade untuk mencegah XSS/HTML/script) --}}
                            <div class="chat-bubble-text">{{ $pesan->isi_pesan }}</div>
                            @if ($milikSendiri)
                                <div class="chat-bubble-status">
                                    {{ $pesan->dibaca_pada ? 'Dibaca' : 'Terkirim' }}
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            @endif
        </div>

        <!-- Form Kirim Pesan -->
        <form method="POST" action="{{ route('chat.pesan.store', $roomChat->id) }}" class="chat-compose-form" id="form-kirim-pesan">
            @csrf
            <input type="hidden" name="chat_actor_token" value="{{ $chatActorToken ?? '' }}">
            <div class="chat-compose-field-wrap">
                <label for="isi_pesan" class="chat-compose-label">
                    Tulis Pesan untuk {{ $namaLawan }} <span style="font-weight: 500; color: var(--muted);">(Maks. 1.000 karakter)</span>
                </label>
                <div class="chat-compose-row">
                    <textarea
                        id="isi_pesan"
                        name="isi_pesan"
                        rows="2"
                        maxlength="1000"
                        required
                        class="form-control chat-compose-input"
                        aria-label="Tulis pesan chat"
                    >{{ old('isi_pesan') }}</textarea>
                    <button type="submit" class="btn chat-compose-submit" id="btn-kirim-pesan">
                        <span>Kirim</span>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M22 2L11 13"/>
                            <path d="M22 2L15 22L11 13L2 9L22 2Z"/>
                        </svg>
                    </button>
                </div>
                @error('isi_pesan')
                    <span class="text-error" role="alert" style="display: block; margin-top: 0.4rem;">{{ $message }}</span>
                @enderror
                <span class="text-error" id="chat-ajax-error" role="alert" style="display: none; margin-top: 0.4rem;"></span>
            </div>
        </form>
    </div>

    <script>
        window.barantauActiveRoomChatId = {{ (int) $roomChat->id }};
        window.barantauEchoReadyQueue = window.barantauEchoReadyQueue || [];

        document.addEventListener('DOMContentLoaded', function () {
            const roomChatId = {{ (int) $roomChat->id }};
            const currentUserId = {{ (int) auth()->id() }};
            const currentUserName = @json(auth()->user()->name);
            const chatActorToken = @json($chatActorToken ?? '');
            const markReadUrl = @json(route('chat.baca', $roomChat->id));
            const incrementalMessagesUrl = @json(route('chat.pesan.baru', $roomChat->id));
            let highestMessageId = {{ (int) ($daftarPesan->last()?->id ?? 0) }};
            const box = document.getElementById('chat-messages-box');
            const form = document.getElementById('form-kirim-pesan');
            const textarea = document.getElementById('isi_pesan');
            const submitBtn = document.getElementById('btn-kirim-pesan');
            const errorEl = document.getElementById('chat-ajax-error');
            const csrfMeta = document.querySelector('meta[name="csrf-token"]');
            const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';

            function scrollChatToBottom() {
                if (box) {
                    box.scrollTop = box.scrollHeight;
                }
            }

            scrollChatToBottom();

            /**
             * Tambahkan bubble pesan baru ke area percakapan secara aman (tanpa duplikasi & tanpa menjalankan HTML/script).
             */
            function appendChatMessageBubble(msg, isMine) {
                if (!box || !msg || !msg.id) return;

                const msgId = Number(msg.id);
                if (msgId > highestMessageId) {
                    highestMessageId = msgId;
                }

                // Cegah pesan tampil dua kali pada pengirim maupun penerima
                const existingBubble = document.getElementById('pesan-' + msgId);
                if (existingBubble) {
                    return;
                }

                const emptyState = box.querySelector('.chat-empty-conversation');
                if (emptyState) {
                    emptyState.remove();
                }

                const row = document.createElement('div');
                row.className = 'chat-bubble-row ' + (isMine ? 'is-mine' : 'is-theirs');
                row.id = 'pesan-' + msgId;

                const bubble = document.createElement('div');
                bubble.className = 'chat-bubble';

                const header = document.createElement('div');
                header.className = 'chat-bubble-header';

                const sender = document.createElement('span');
                sender.className = 'chat-bubble-sender';
                sender.textContent = isMine
                    ? ('Anda (' + currentUserName + ')')
                    : (msg.pengirim_nama || 'Pengguna');

                const timeEl = document.createElement('time');
                timeEl.className = 'chat-bubble-time';
                if (msg.created_at_iso) {
                    timeEl.setAttribute('datetime', msg.created_at_iso);
                }
                timeEl.textContent = msg.waktu_format || 'Baru saja';

                header.appendChild(sender);
                header.appendChild(timeEl);

                const textEl = document.createElement('div');
                textEl.className = 'chat-bubble-text';
                // Gunakan textContent agar teks selalu aman dari XSS/HTML/script
                textEl.textContent = msg.isi_pesan || '';

                bubble.appendChild(header);
                bubble.appendChild(textEl);

                if (isMine) {
                    const statusEl = document.createElement('div');
                    statusEl.className = 'chat-bubble-status';
                    statusEl.textContent = msg.dibaca ? 'Dibaca' : 'Terkirim';
                    bubble.appendChild(statusEl);
                }

                row.appendChild(bubble);
                box.appendChild(row);
                scrollChatToBottom();
            }

            // Kirim pesan via AJAX agar pesan langsung tampil tanpa reload halaman
            if (form && textarea && submitBtn) {
                let isSending = false;

                textarea.addEventListener('keydown', function (e) {
                    if (e.key === 'Enter' && !e.shiftKey) {
                        e.preventDefault();
                        form.requestSubmit ? form.requestSubmit() : form.dispatchEvent(new Event('submit', { cancelable: true }));
                    }
                });

                form.addEventListener('submit', async function (e) {
                    e.preventDefault();
                    if (isSending) return;

                    const text = textarea.value.trim();
                    if (!text) {
                        if (errorEl) {
                            errorEl.textContent = 'Isi pesan wajib diisi sebelum dikirim.';
                            errorEl.style.display = 'block';
                        }
                        return;
                    }

                    if (errorEl) {
                        errorEl.textContent = '';
                        errorEl.style.display = 'none';
                    }

                    isSending = true;
                    submitBtn.disabled = true;

                    try {
                        const socketId = (window.Echo && typeof window.Echo.socketId === 'function')
                            ? (window.Echo.socketId() || '')
                            : '';

                        const formData = new FormData(form);
                        const response = await fetch(form.action, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': csrfToken,
                                'X-Socket-ID': socketId,
                            },
                            body: formData,
                        });

                        const data = await response.json().catch(function () { return null; });

                        if (!response.ok || !data || !data.pesan) {
                            const errMsg = (data && data.errors && data.errors.isi_pesan && data.errors.isi_pesan[0])
                                || (data && data.message)
                                || 'Gagal mengirim pesan. Silakan coba lagi.';
                            if (errorEl) {
                                errorEl.textContent = errMsg;
                                errorEl.style.display = 'block';
                            }
                            return;
                        }

                        textarea.value = '';
                        appendChatMessageBubble(data.pesan, true);
                    } catch (err) {
                        form.submit();
                    } finally {
                        isSending = false;
                        submitBtn.disabled = false;
                        textarea.focus();
                    }
                });
            }

            // Fungsi berlangganan ke private channel room-chat.{id} melalui Laravel Echo & Reverb
            function subscribeToRoomEcho(echoInstance) {
                if (!echoInstance || typeof echoInstance.private !== 'function') return;

                echoInstance.private('room-chat.' + roomChatId)
                    .listen('.PesanChatTerkirim', function (payload) {
                        if (!payload || Number(payload.room_chat_id) !== roomChatId) {
                            return;
                        }

                        const isMine = Number(payload.pengirim_id) === currentUserId;
                        appendChatMessageBubble(payload, isMine);

                        // Jika pesan berasal dari lawan bicara saat room sedang dibuka, tandai sudah dibaca & perbarui badge
                        if (!isMine && markReadUrl) {
                            fetch(markReadUrl, {
                                method: 'POST',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'X-CSRF-TOKEN': csrfToken,
                                    'X-Chat-Actor-Token': chatActorToken,
                                },
                            })
                                .then(function (res) { return res.ok ? res.json() : null; })
                                .then(function (data) {
                                    if (data && typeof data.unread_count !== 'undefined' && typeof window.updateBarantauUnreadBadges === 'function') {
                                        window.updateBarantauUnreadBadges(data.unread_count);
                                    }
                                })
                                .catch(function () {});
                        }
                    });
            }

            if (window.Echo && typeof window.Echo.private === 'function') {
                subscribeToRoomEcho(window.Echo);
            } else {
                window.barantauEchoReadyQueue.push(subscribeToRoomEcho);
            }

            // Sinkronisasi inkremental otomatis (memastikan pesan baru & status "Dibaca" tetap muncul real-time
            // meskipun koneksi WebSocket terputus atau terhambat ekstensi browser, tanpa pernah menduplikasi pesan)
            let isCheckingNew = false;
            setInterval(function () {
                if (document.hidden || isCheckingNew || !incrementalMessagesUrl) return;
                isCheckingNew = true;

                fetch(incrementalMessagesUrl + '?after_id=' + encodeURIComponent(highestMessageId), {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-Chat-Actor-Token': chatActorToken,
                    },
                })
                    .then(function (res) { return res.ok ? res.json() : null; })
                    .then(function (data) {
                        if (!data) return;

                        if (Array.isArray(data.pesan) && data.pesan.length > 0) {
                            data.pesan.forEach(function (msg) {
                                const isMine = Number(msg.pengirim_id) === currentUserId;
                                appendChatMessageBubble(msg, isMine);
                            });
                        }

                        if (Array.isArray(data.read_message_ids)) {
                            data.read_message_ids.forEach(function (readId) {
                                const rowEl = document.getElementById('pesan-' + readId);
                                if (rowEl) {
                                    const statusEl = rowEl.querySelector('.chat-bubble-status');
                                    if (statusEl) {
                                        statusEl.textContent = 'Dibaca';
                                    }
                                }
                            });
                        }

                        if (typeof data.unread_count !== 'undefined' && typeof window.updateBarantauUnreadBadges === 'function') {
                            window.updateBarantauUnreadBadges(data.unread_count);
                        }
                    })
                    .catch(function () {})
                    .finally(function () {
                        isCheckingNew = false;
                    });
            }, 2000);
        });
    </script>
@endsection
