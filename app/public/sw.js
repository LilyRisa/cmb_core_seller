/* Service worker — Web Push thông báo tin nhắn mới khi tab đóng/ẩn. */
self.addEventListener('push', (event) => {
    let data = {};
    try {
        data = event.data ? event.data.json() : {};
    } catch (e) {
        data = {};
    }
    const title = data.title || 'Tin nhắn mới';
    const options = {
        body: data.body || 'Bạn có tin nhắn mới',
        icon: '/favicon.ico',
        badge: '/favicon.ico',
        tag: 'cmb-new-message',
        renotify: true,
        data: { url: data.url || '/messaging' },
    };
    event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const url = (event.notification.data && event.notification.data.url) || '/messaging';
    event.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((wins) => {
            for (const w of wins) {
                if ('focus' in w) {
                    if ('navigate' in w) {
                        try { w.navigate(url); } catch (e) { /* ignore */ }
                    }
                    return w.focus();
                }
            }
            if (self.clients.openWindow) {
                return self.clients.openWindow(url);
            }
        }),
    );
});
