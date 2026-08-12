(function () {
    'use strict';

    var CATEGORY_ICONS = {
        report: 'fi fi-rr-document-signed',
        approval: 'fi fi-rr-badge-check',
        backup: 'fi fi-rr-cloud-upload',
        safety: 'fi fi-rr-shield-check',
        system: 'fi fi-rr-info',
    };

    function createElement(tag, className, text) {
        var element = document.createElement(tag);
        if (className) element.className = className;
        if (typeof text === 'string') element.textContent = text;
        return element;
    }

    function init(root) {
        var trigger = root.querySelector('[data-notification-trigger]');
        var panel = root.querySelector('[data-notification-panel]');
        var badge = root.querySelector('[data-notification-badge]');
        var content = root.querySelector('[data-notification-content]');
        var readAllButton = root.querySelector('[data-notification-read-all]');
        var refreshLabel = root.querySelector('[data-notification-refresh-label]');
        var live = root.querySelector('[data-notification-live]');
        var csrfToken = root.dataset.csrfToken;
        var unreadCount = 0;
        var lastLoadedAt = 0;
        var loading = false;

        function request(url, options) {
            var settings = options || {};
            settings.headers = Object.assign({
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken,
            }, settings.headers || {});

            return fetch(url, settings).then(function (response) {
                if (!response.ok) throw new Error('Permintaan notifikasi gagal.');
                return response.json();
            });
        }

        function updateBadge(count) {
            unreadCount = Math.max(0, Number(count) || 0);
            badge.hidden = unreadCount === 0;
            badge.textContent = unreadCount > 99 ? '99+' : String(unreadCount);
            badge.setAttribute('aria-label', unreadCount + ' notifikasi belum dibaca');
            trigger.setAttribute('aria-label', unreadCount > 0
                ? 'Buka notifikasi, ' + unreadCount + ' belum dibaca'
                : 'Buka notifikasi');
            readAllButton.disabled = unreadCount === 0;
        }

        function state(icon, title, message, type) {
            var wrapper = createElement('div', 'kss-notification-center__state' + (type ? ' is-' + type : ''));
            wrapper.appendChild(createElement('i', icon));
            wrapper.appendChild(createElement('strong', '', title));
            wrapper.appendChild(createElement('span', '', message));
            return wrapper;
        }

        function showLoading() {
            content.replaceChildren(state(
                'fi fi-rr-spinner',
                'Memuat notifikasi',
                'Menyiapkan pembaruan terbaru untuk Anda.',
                'loading'
            ));
        }

        function showError() {
            var wrapper = state(
                'fi fi-rr-cloud-disabled',
                'Notifikasi belum dapat dimuat',
                'Periksa koneksi lalu coba lagi.',
                'error'
            );
            var retry = createElement('button', '', 'Coba lagi');
            retry.type = 'button';
            retry.addEventListener('click', load);
            wrapper.appendChild(retry);
            content.replaceChildren(wrapper);
        }

        function notificationItem(item) {
            var button = createElement('button', 'kss-notification-center__item' + (item.is_read ? '' : ' is-unread'));
            button.type = 'button';
            button.dataset.notificationId = item.id;
            button.dataset.actionUrl = item.action_url || '';

            var icon = createElement('span', 'kss-notification-center__icon is-' + item.severity);
            icon.setAttribute('aria-hidden', 'true');
            icon.appendChild(createElement('i', CATEGORY_ICONS[item.category] || CATEGORY_ICONS.system));

            var copy = createElement('span', 'kss-notification-center__copy');
            var titleRow = createElement('span', 'kss-notification-center__title-row');
            titleRow.appendChild(createElement('strong', '', item.title));
            if (!item.is_read) {
                var dot = createElement('span', 'kss-notification-center__unread-dot');
                dot.setAttribute('aria-label', 'Belum dibaca');
                titleRow.appendChild(dot);
            }

            copy.appendChild(titleRow);
            copy.appendChild(createElement('span', 'kss-notification-center__message', item.message));

            var meta = createElement('span', 'kss-notification-center__meta');
            var occurred = createElement('span', '', item.occurred_ago || item.occurred_at || 'Baru saja');
            if (item.occurred_at) occurred.title = item.occurred_at;
            meta.appendChild(occurred);
            if (item.expires_at) meta.appendChild(createElement('span', '', 'Aktif hingga ' + item.expires_at));
            copy.appendChild(meta);

            if (item.action_label) {
                var action = createElement('span', 'kss-notification-center__action', item.action_label);
                action.appendChild(createElement('i', 'fi fi-rr-arrow-small-right'));
                copy.appendChild(action);
            }

            button.appendChild(icon);
            button.appendChild(copy);
            button.addEventListener('click', function () {
                openNotification(button);
            });
            return button;
        }

        function render(payload) {
            updateBadge(payload.unread_count);
            refreshLabel.textContent = payload.refreshed_at
                ? 'Diperbarui pukul ' + payload.refreshed_at
                : 'Pembaruan sistem dan laporan';

            if (!payload.items || payload.items.length === 0) {
                content.replaceChildren(state(
                    'fi fi-rr-bell-slash',
                    'Tidak ada notifikasi aktif',
                    'Pembaruan baru akan muncul di sini sesuai peran Anda.'
                ));
                return;
            }

            var list = createElement('div', 'kss-notification-center__list');
            payload.items.forEach(function (item) {
                list.appendChild(notificationItem(item));
            });
            content.replaceChildren(list);
        }

        function load() {
            if (loading) return Promise.resolve();
            loading = true;
            if (!lastLoadedAt) showLoading();

            return request(root.dataset.indexUrl)
                .then(function (payload) {
                    lastLoadedAt = Date.now();
                    render(payload);
                })
                .catch(showError)
                .finally(function () {
                    loading = false;
                });
        }

        function readUrl(id) {
            return root.dataset.readUrlTemplate.replace('__ID__', String(id));
        }

        function openNotification(button) {
            var id = button.dataset.notificationId;
            var actionUrl = button.dataset.actionUrl;
            var wasUnread = button.classList.contains('is-unread');

            button.disabled = true;
            request(readUrl(id), { method: 'PATCH' })
                .then(function () {
                    if (wasUnread) updateBadge(unreadCount - 1);
                })
                .catch(function () {
                    live.textContent = 'Status baca belum dapat disimpan.';
                })
                .finally(function () {
                    if (actionUrl) {
                        window.location.assign(actionUrl);
                        return;
                    }

                    button.disabled = false;
                    load();
                });
        }

        function markAllRead() {
            if (readAllButton.disabled) return;
            readAllButton.disabled = true;

            request(root.dataset.readAllUrl, { method: 'PATCH' })
                .then(function () {
                    updateBadge(0);
                    live.textContent = 'Semua notifikasi ditandai sudah dibaca.';
                    return load();
                })
                .catch(function () {
                    readAllButton.disabled = false;
                    live.textContent = 'Notifikasi belum dapat ditandai sudah dibaca.';
                });
        }

        function setOpen(open) {
            trigger.setAttribute('aria-expanded', open ? 'true' : 'false');
            panel.hidden = !open;
            panel.setAttribute('aria-hidden', open ? 'false' : 'true');
            if (open && Date.now() - lastLoadedAt > 30000) load();
        }

        trigger.addEventListener('click', function () {
            setOpen(panel.hidden);
        });
        readAllButton.addEventListener('click', markAllRead);

        document.addEventListener('click', function (event) {
            if (!panel.hidden && !root.contains(event.target)) setOpen(false);
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && !panel.hidden) {
                setOpen(false);
                trigger.focus();
            }
        });

        document.addEventListener('visibilitychange', function () {
            if (document.visibilityState === 'visible' && Date.now() - lastLoadedAt > 60000) load();
        });

        load();
        window.setInterval(function () {
            if (document.visibilityState === 'visible') load();
        }, 60000);
    }

    function boot() {
        document.querySelectorAll('[data-notification-root]').forEach(init);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
