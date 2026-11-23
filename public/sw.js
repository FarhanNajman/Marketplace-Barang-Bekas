/* Service Worker BARANTAU — Penerima Web Push Notification */

self.addEventListener('install', function (event) {
    self.skipWaiting();
});

self.addEventListener('activate', function (event) {
    event.waitUntil(self.clients.claim());
});

self.addEventListener('push', function (event) {
    let payload = {
        title: 'Pesan Baru di BARANTAU',
        body: 'Ada pesan chat baru untuk Anda. Ketuk untuk membuka room chat.',
        url: '/chat',
        tag: 'barantau-chat-notification',
    };

    if (event.data) {
        try {
            const parsed = event.data.json();
            if (parsed && typeof parsed === 'object') {
                payload.title = parsed.title || payload.title;
                payload.body = parsed.body || payload.body;
                payload.url = parsed.url || payload.url;
                payload.tag = parsed.tag || payload.tag;
                payload.room_chat_id = parsed.room_chat_id || null;
            }
        } catch (err) {
            try {
                const text = event.data.text();
                if (text) {
                    payload.body = text;
                }
            } catch (e) {}
        }
    }

    const options = {
        body: payload.body,
        icon: '/Assets/LOGO/LOGO BARANTAU V1 RMV.png',
        badge: '/Assets/LOGO/LOGO BARANTAU V1 RMV.png',
        tag: payload.tag,
        renotify: true,
        vibrate: [180, 80, 180],
        data: {
            url: payload.url,
            room_chat_id: payload.room_chat_id || null,
        },
    };

    event.waitUntil(
        self.registration.showNotification(payload.title, options)
    );
});

self.addEventListener('notificationclick', function (event) {
    event.notification.close();

    const rawUrl = (event.notification.data && event.notification.data.url)
        ? event.notification.data.url
        : '/chat';

    const targetUrl = new URL(rawUrl, self.location.origin).href;

    event.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (clientList) {
            for (let i = 0; i < clientList.length; i++) {
                const client = clientList[i];
                if (client.url && client.url.indexOf(self.location.origin) === 0 && 'focus' in client) {
                    if ('navigate' in client) {
                        return client.navigate(targetUrl).then(function (navigatedClient) {
                            return navigatedClient ? navigatedClient.focus() : client.focus();
                        });
                    }
                    return client.focus();
                }
            }

            if (self.clients.openWindow) {
                return self.clients.openWindow(targetUrl);
            }
        })
    );
});
