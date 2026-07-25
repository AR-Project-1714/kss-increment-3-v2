/**
 * Interaksi grafik, dipakai bersama dashboard manajer dan admin.
 *
 * Seluruh grafik sudah digambar sebagai SVG oleh Blade, jadi berkas ini hanya
 * mengurus dua hal: tooltip saat kursor berada di atas data, dan pergantian
 * bentuk grafik tren antara garis dan batang. Tidak ada penggambaran ulang —
 * kedua bentuk sudah ada di DOM sejak awal dan hanya ditukar lewat kelas.
 *
 * Kontrak markup:
 *   [data-chart-tip]        elemen yang memicu tooltip
 *   [data-tip-title]        judul tooltip (mis. nama bulan)
 *   [data-tip-rows]         JSON: [{label, value, color}]
 *   [data-chart-stack]      pembungkus grafik yang punya dua bentuk
 *   [data-chart-view]       "line" atau "bar" pada pembungkus tersebut
 *   [data-chart-switch]     tombol pengubah bentuk, nilainya "line"/"bar"
 */
(function () {
    'use strict';

    var STORAGE_KEY = 'kss.chart.trendView';

    // ============================================================
    // Tooltip
    // ============================================================

    var tooltip = null;

    function ensureTooltip() {
        if (tooltip) return tooltip;

        tooltip = document.createElement('div');
        tooltip.className = 'chart-tooltip';
        tooltip.setAttribute('role', 'status');
        tooltip.setAttribute('aria-live', 'polite');
        document.body.appendChild(tooltip);

        return tooltip;
    }

    function parseRows(target) {
        var raw = target.getAttribute('data-tip-rows');
        if (!raw) return [];

        try {
            var rows = JSON.parse(raw);
            return Array.isArray(rows) ? rows : [];
        } catch (error) {
            // Markup rusak tidak boleh mematikan halaman — cukup lewati.
            return [];
        }
    }

    function renderTooltip(target) {
        var tip = ensureTooltip();
        var title = target.getAttribute('data-tip-title') || '';
        var rows = parseRows(target);
        var html = '';

        if (title) {
            html += '<div class="chart-tooltip__title">' + escapeHtml(title) + '</div>';
        }

        rows.forEach(function (row) {
            html += '<div class="chart-tooltip__row">';
            if (row.color) {
                html += '<span class="chart-tooltip__swatch" style="background-color:' + escapeAttr(row.color) + '"></span>';
            }
            html += '<span class="chart-tooltip__label">' + escapeHtml(row.label || '') + '</span>';
            html += '<span class="chart-tooltip__value">' + escapeHtml(row.value || '') + '</span>';
            html += '</div>';
        });

        tip.innerHTML = html;
        tip.classList.add('is-visible');

        return tip;
    }

    function positionTooltip(event) {
        if (!tooltip) return;

        var pad = 12;
        var rect = tooltip.getBoundingClientRect();
        var left = event.clientX;
        var top = event.clientY;

        // Jaga agar tooltip tidak keluar layar di sisi kiri/kanan.
        left = Math.min(Math.max(left, rect.width / 2 + pad), window.innerWidth - rect.width / 2 - pad);

        // Bila ruang di atas kursor tidak cukup, pindahkan ke bawah kursor.
        if (top - rect.height - pad < 0) {
            tooltip.style.transform = 'translate(-50%, 0) translateY(16px)';
        } else {
            tooltip.style.transform = 'translate(-50%, -100%) translateY(-10px)';
        }

        tooltip.style.left = left + 'px';
        tooltip.style.top = top + 'px';
    }

    function hideTooltip() {
        if (tooltip) tooltip.classList.remove('is-visible');
        document.querySelectorAll('[data-chart-tip].is-active').forEach(function (el) {
            el.classList.remove('is-active');
        });
        document.querySelectorAll('.chart__cursor.is-visible').forEach(function (el) {
            el.classList.remove('is-visible');
        });
    }

    function showFor(target, event) {
        renderTooltip(target);
        positionTooltip(event);
        target.classList.add('is-active');

        // Garis bantu vertikal pada grafik tren mengikuti kolom yang disorot.
        var cursorId = target.getAttribute('data-tip-cursor');
        if (cursorId) {
            var cursor = document.getElementById(cursorId);
            if (cursor) {
                cursor.setAttribute('x1', target.getAttribute('data-tip-x'));
                cursor.setAttribute('x2', target.getAttribute('data-tip-x'));
                cursor.classList.add('is-visible');
            }
        }
    }

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    function escapeAttr(value) {
        return String(value).replace(/"/g, '&quot;').replace(/</g, '&lt;');
    }

    // ============================================================
    // Pergantian bentuk grafik
    // ============================================================

    /**
     * Tombol pengubah bentuk berada di header kartu, sedangkan grafiknya di
     * badan kartu — keduanya bukan pasangan induk-anak. Karena itu pencarian
     * tombol naik dulu ke kartu terdekat, bukan ke dalam pembungkus grafik.
     */
    function switchesFor(stack) {
        var scope = stack.closest('.section-card') || document;

        return scope.querySelectorAll('[data-chart-switch]');
    }

    function applyView(stack, view) {
        stack.setAttribute('data-chart-view', view);

        switchesFor(stack).forEach(function (button) {
            var active = button.getAttribute('data-chart-switch') === view;
            button.classList.toggle('is-active', active);
            button.setAttribute('aria-pressed', active ? 'true' : 'false');
        });

        try {
            window.localStorage.setItem(STORAGE_KEY, view);
        } catch (error) {
            // Mode privat memblokir localStorage; pilihan cukup berlaku sesi ini.
        }
    }

    function initStacks() {
        document.querySelectorAll('[data-chart-stack]').forEach(function (stack) {
            var stored = null;

            try {
                stored = window.localStorage.getItem(STORAGE_KEY);
            } catch (error) {
                stored = null;
            }

            if (stored === 'line' || stored === 'bar') {
                applyView(stack, stored);
            } else {
                applyView(stack, stack.getAttribute('data-chart-view') || 'line');
            }

            switchesFor(stack).forEach(function (button) {
                button.addEventListener('click', function () {
                    applyView(stack, button.getAttribute('data-chart-switch'));
                    hideTooltip();
                });
            });
        });
    }

    // ============================================================
    // Pemasangan
    // ============================================================

    function bindTooltips() {
        // Delegasi di tingkat dokumen supaya grafik yang dimuat belakangan
        // (mis. setelah filter dikirim ulang) ikut bekerja tanpa pemasangan ulang.
        document.addEventListener('mouseover', function (event) {
            var target = event.target.closest('[data-chart-tip]');
            if (!target) return;
            showFor(target, event);
        });

        document.addEventListener('mousemove', function (event) {
            if (!tooltip || !tooltip.classList.contains('is-visible')) return;
            if (!event.target.closest('[data-chart-tip]')) return;
            positionTooltip(event);
        });

        document.addEventListener('mouseout', function (event) {
            var target = event.target.closest('[data-chart-tip]');
            if (!target) return;
            if (target.contains(event.relatedTarget)) return;
            hideTooltip();
        });

        // Perangkat sentuh: ketuk untuk menampilkan, ketuk di luar untuk menutup.
        document.addEventListener('click', function (event) {
            var target = event.target.closest('[data-chart-tip]');

            if (!target) {
                hideTooltip();
                return;
            }

            showFor(target, {
                clientX: target.getBoundingClientRect().left + target.getBoundingClientRect().width / 2,
                clientY: target.getBoundingClientRect().top,
            });
        });

        window.addEventListener('scroll', hideTooltip, true);
        window.addEventListener('resize', hideTooltip);
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') hideTooltip();
        });
    }

    function init() {
        initStacks();
        bindTooltips();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
