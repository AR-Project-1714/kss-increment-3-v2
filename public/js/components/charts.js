/**
 * Interaksi grafik, dipakai bersama dashboard manajer dan admin.
 *
 * Seluruh grafik sudah digambar sebagai SVG oleh Blade, jadi berkas ini hanya
 * mengurus tiga hal: tooltip saat kursor berada di atas data, pergantian bentuk
 * grafik tren antara garis dan batang, dan pembuka daftar papan peringkat.
 * Tidak ada penggambaran ulang maupun permintaan ke server — seluruh isinya
 * sudah ada di DOM sejak awal dan hanya ditukar lewat kelas.
 *
 * Kontrak markup:
 *   [data-chart-tip]        elemen yang memicu tooltip
 *   [data-tip-title]        judul tooltip (mis. nama bulan)
 *   [data-tip-rows]         JSON: [{label, value, color}]
 *   [data-tip-marker]       id kelompok marker HTML yang disorot
 *   [data-chart-stack]      pembungkus grafik yang punya dua bentuk
 *   [data-chart-view]       "line" atau "bar" pada pembungkus tersebut
 *   [data-chart-switch]     tombol pengubah bentuk, nilainya "line"/"bar"
 *   [data-leader-toggle]    tombol pembuka daftar penuh papan peringkat;
 *                           aria-controls menunjuk daftarnya, data-label-more
 *                           dan data-label-less berisi teks kedua keadaan
 *   [data-overtime-sort]     tombol pengurut posisi pada tabel lembur
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
        document.querySelectorAll('.chart__marker-group.is-active').forEach(function (el) {
            el.classList.remove('is-active');
        });
    }

    function showFor(target, event) {
        renderTooltip(target);
        positionTooltip(event);
        target.classList.add('is-active');

        document.querySelectorAll('.chart__marker-group.is-active').forEach(function (el) {
            el.classList.remove('is-active');
        });

        var markerId = target.getAttribute('data-tip-marker');
        if (markerId) {
            var marker = document.getElementById(markerId);
            if (marker) marker.classList.add('is-active');
        }

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
    // Panel kegiatan (halaman Rincian Kegiatan)
    //
    // Panel tidak ikut dirender bersama halaman: isinya diambil lewat permintaan
    // terpisah saat tabnya dibuka, dan panel pertama baru diambil setelah
    // halaman selesai digambar. Hasil yang sudah diambil disimpan di memori
    // supaya berpindah tab tidak memanggil server lagi.
    //
    // Daftar tabnya datang dari halaman, yang menurunkannya dari katalog
    // kegiatan — jadi kegiatan yang ditandai tidak tampil di menu ini (Pemuatan
    // Pupuk Kantong) tidak pernah diminta dari sini.
    // ============================================================

    function initActivityPanel() {
        var panel = document.getElementById('activity-panel');
        var tabs = Array.prototype.slice.call(document.querySelectorAll('[data-activity-tab]'));
        var tabsWrap = document.getElementById('activityTabs');
        var indicator = document.getElementById('activityTabIndicator');

        if (!panel || tabs.length === 0) return;

        var cache = {};
        // Menunjukkan tab yang saat ini dipilih, bukan sekadar request terakhir.
        // Ini mencegah respons lama menimpa panel tab cached yang baru dipilih.
        var selectedKey = null;
        var indicatorFrame = null;

        function moveIndicator(tab) {
            if (!indicator || !tab) return;

            var isInitialPlacement = tabsWrap && !tabsWrap.classList.contains('is-indicator-ready');

            // Posisi pertama tidak dianimasikan dari lebar nol. Tanpa ini,
            // background fallback dilepas ketika indikator masih setengah jalan
            // sehingga tab aktif tampak terbelah sesaat.
            if (isInitialPlacement) indicator.style.transition = 'none';
            indicator.style.width = tab.offsetWidth + 'px';
            indicator.style.transform = 'translateX(' + tab.offsetLeft + 'px)';

            if (tabsWrap) {
                tabsWrap.classList.add('is-indicator-ready');

                if (isInitialPlacement) {
                    window.requestAnimationFrame(function () {
                        indicator.style.transition = '';
                    });
                }
            }
        }

        function keepTabVisible(tab) {
            if (!tabsWrap || !tab) return;

            var left = tab.offsetLeft;
            var right = left + tab.offsetWidth;

            if (left < tabsWrap.scrollLeft) {
                tabsWrap.scrollTo({ left: left - 16, behavior: 'smooth' });
            } else if (right > tabsWrap.scrollLeft + tabsWrap.clientWidth) {
                tabsWrap.scrollTo({
                    left: right - tabsWrap.clientWidth + 16,
                    behavior: 'smooth',
                });
            }
        }

        function activeTab() {
            return tabs.filter(function (tab) {
                return tab.classList.contains('is-active');
            })[0] || tabs[0];
        }

        function scheduleIndicatorSync() {
            if (indicatorFrame !== null) window.cancelAnimationFrame(indicatorFrame);

            indicatorFrame = window.requestAnimationFrame(function () {
                indicatorFrame = null;
                moveIndicator(activeTab());
            });
        }

        function markActive(active) {
            tabs.forEach(function (tab) {
                var isActive = tab === active;
                tab.classList.toggle('is-active', isActive);
                tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
            });

            moveIndicator(active);
            keepTabVisible(active);
            panel.setAttribute('aria-labelledby', active.id || '');
        }

        function showSkeleton() {
            panel.innerHTML =
                '<div class="act-panel__loading">' +
                '<span class="act-skeleton act-skeleton--metrics"></span>' +
                '<span class="act-skeleton act-skeleton--block"></span>' +
                '</div>';
        }

        function load(tab) {
            var key = tab.getAttribute('data-activity-tab');

            selectedKey = key;
            markActive(tab);
            hideTooltip();

            if (window.history && typeof window.history.replaceState === 'function') {
                var nextUrl = new URL(window.location.href);
                nextUrl.searchParams.set('kegiatan', key);
                window.history.replaceState({}, '', nextUrl.toString());
            }

            if (cache[key]) {
                panel.innerHTML = cache[key];
                return;
            }

            showSkeleton();

            fetch(tab.getAttribute('data-activity-url'), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            })
                .then(function (response) {
                    if (!response.ok) throw new Error('Gagal memuat panel');
                    return response.text();
                })
                .then(function (html) {
                    cache[key] = html;

                    // Pengguna bisa sudah pindah tab sebelum jawabannya tiba —
                    // jangan timpa panel dengan isi kegiatan yang sudah lewat.
                    if (selectedKey === key) panel.innerHTML = html;
                })
                .catch(function () {
                    if (selectedKey !== key) return;

                    panel.innerHTML =
                        '<div class="perf-empty perf-empty--action">' +
                        '<span>Rincian kegiatan gagal dimuat.</span>' +
                        '<button type="button" class="btn-tool" data-activity-retry>Coba lagi</button>' +
                        '</div>';
                });
        }

        panel.addEventListener('click', function (event) {
            if (!event.target.closest('[data-activity-retry]')) return;
            load(activeTab());
        });

        tabs.forEach(function (tab) {
            tab.addEventListener('click', function () {
                load(tab);
            });
        });

        var initialTab = activeTab();
        markActive(initialTab);
        window.addEventListener('resize', scheduleIndicatorSync);

        // Lebar tab dapat berubah tanpa resize window: font selesai dimuat,
        // scrollbar panel muncul, atau sidebar bertransisi. Sinkronkan ulang
        // indikator terhadap ukuran tombol yang benar-benar terlihat.
        if (typeof window.ResizeObserver === 'function' && tabsWrap) {
            var tabsResizeObserver = new window.ResizeObserver(scheduleIndicatorSync);
            tabsResizeObserver.observe(tabsWrap);
            tabs.forEach(function (tab) {
                tabsResizeObserver.observe(tab);
            });
        }

        if (document.fonts && document.fonts.ready) {
            document.fonts.ready.then(scheduleIndicatorSync);
        }

        // Panel pertama diambil setelah halaman selesai digambar, empat sisanya
        // menunggu tabnya diklik. Penundaannya sengaja memakai timer, bukan
        // IntersectionObserver atau perhitungan posisi: isi halaman berada di
        // wadah bergulir sendiri, dan pada sebagian peramban peristiwa gulir
        // maupun observer tidak pernah sampai ke jendela — panel yang diam-diam
        // tidak pernah termuat jauh lebih buruk daripada satu permintaan kecil.
        if (typeof window.requestIdleCallback === 'function') {
            window.requestIdleCallback(function () {
                // Klik pengguna selalu menang atas pekerjaan idle. Tanpa guard
                // ini pilihan yang dibuat cepat dapat dipaksa kembali ke tab 1.
                if (selectedKey === null) load(initialTab);
            }, { timeout: 1200 });
        } else {
            window.setTimeout(function () {
                if (selectedKey === null) load(initialTab);
            }, 200);
        }
    }

    // ============================================================
    // Papan peringkat: buka/tutup daftar penuh
    //
    // Seluruh baris sudah ada di DOM; yang di luar sepuluh teratas hanya
    // disembunyikan CSS. Jadi membuka daftar tidak memanggil server dan tidak
    // pernah gagal di tengah jalan.
    // ============================================================

    function toggleLeaderList(button) {
        var list = document.getElementById(button.getAttribute('aria-controls'));
        if (!list) return;

        var expanded = list.classList.contains('is-collapsed');

        list.classList.toggle('is-collapsed', !expanded);
        button.setAttribute('aria-expanded', expanded ? 'true' : 'false');

        var label = button.querySelector('[data-leader-toggle-label]');
        if (label) {
            label.textContent = button.getAttribute(expanded ? 'data-label-less' : 'data-label-more') || label.textContent;
        }

        // Saat ditutup, daftar bisa jadi jauh lebih pendek daripada gulir yang
        // sedang berlaku — tombolnya ditarik kembali ke layar agar pembaca
        // tidak mendadak berada di bagian halaman yang lain.
        if (!expanded) {
            var rect = button.getBoundingClientRect();
            if (rect.top < 0 || rect.bottom > window.innerHeight) {
                button.scrollIntoView({ block: 'nearest' });
            }
        }
    }

    function bindLeaderToggles() {
        // Delegasi: panel kegiatan memuat papan peringkatnya belakangan.
        document.addEventListener('click', function (event) {
            var button = event.target.closest('[data-leader-toggle]');
            if (!button) return;

            toggleLeaderList(button);
        });
    }

    // ============================================================
    // Tabel peringkat lembur: urut posisi naik/turun melalui panah masing-masing
    // ============================================================

    function sortOvertimeRows(button) {
        var ranking = button.closest('[data-overtime-ranking]');
        if (!ranking) return;

        var body = ranking.querySelector('.overtime-ranking__body');
        if (!body) return;

        var rows = Array.prototype.slice.call(body.querySelectorAll('[data-overtime-position]'));
        var nextDirection = button.getAttribute('data-overtime-sort') === 'desc' ? 'desc' : 'asc';
        var visibleCount = parseInt(body.getAttribute('data-visible-count'), 10);

        if (!Number.isFinite(visibleCount) || visibleCount < 1) {
            visibleCount = rows.length;
        }

        rows.sort(function (left, right) {
            var leftPosition = parseInt(left.getAttribute('data-overtime-position'), 10) || 0;
            var rightPosition = parseInt(right.getAttribute('data-overtime-position'), 10) || 0;

            return nextDirection === 'asc'
                ? leftPosition - rightPosition
                : rightPosition - leftPosition;
        });

        rows.forEach(function (row, index) {
            row.classList.toggle('overtime-ranking__row--extra', index >= visibleCount);
            body.appendChild(row);
        });

        var sortButtons = ranking.querySelectorAll('[data-overtime-sort]');
        sortButtons.forEach(function (sortButton) {
            var isActive = sortButton.getAttribute('data-overtime-sort') === nextDirection;
            sortButton.classList.toggle('is-active', isActive);
            sortButton.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });

        var heading = button.closest('th');
        if (heading) {
            heading.setAttribute('aria-sort', nextDirection === 'asc' ? 'ascending' : 'descending');
        }

    }

    function bindOvertimeSort() {
        // Delegasi juga mencakup tabel pada panel kegiatan yang dimuat via AJAX.
        document.addEventListener('click', function (event) {
            var button = event.target.closest('[data-overtime-sort]');
            if (!button) return;

            sortOvertimeRows(button);
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

        // Area data SVG dapat difokuskan dengan keyboard. Fokus menampilkan
        // detail yang sama seperti hover tanpa membutuhkan aktivasi tambahan.
        document.addEventListener('focus', function (event) {
            var target = event.target.closest('[data-chart-tip]');
            if (!target) return;

            var rect = target.getBoundingClientRect();
            showFor(target, {
                clientX: rect.left + rect.width / 2,
                clientY: rect.top,
            });
        }, true);

        document.addEventListener('blur', function (event) {
            var target = event.target.closest('[data-chart-tip]');
            if (!target) return;
            if (target.contains(event.relatedTarget)) return;
            hideTooltip();
        }, true);

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
        bindLeaderToggles();
        bindOvertimeSort();
        initActivityPanel();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
