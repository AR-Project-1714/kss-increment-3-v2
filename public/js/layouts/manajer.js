document.addEventListener('DOMContentLoaded', function () {
            const body = document.body;

            // Toast notifikasi ditangani komponen bersama (partials/toast.blade.php).
            // window.showManagerToast tersedia sebagai alias dari window.kssToast.

            function initSlidingTabIndicators() {
                [
                    { containerSelector: '.report-tabs', itemSelector: '.report-tab', indicatorClass: 'tab-slide-indicator' },
                ].forEach(config => {
                    document.querySelectorAll(config.containerSelector).forEach(container => {
                        let indicator = container.querySelector(`.${config.indicatorClass}`);
                        if (!indicator) {
                            indicator = document.createElement('div');
                            indicator.className = config.indicatorClass;
                            container.appendChild(indicator);
                        }

                        const updateIndicator = () => {
                            const active = container.querySelector(`${config.itemSelector}.active`);
                            if (!active) {
                                indicator.style.opacity = '0';
                                return;
                            }

                            indicator.style.opacity = '1';
                            indicator.style.width = `${active.offsetWidth}px`;
                            indicator.style.transform = `translateX(${active.offsetLeft}px)`;
                        };

                        requestAnimationFrame(updateIndicator);

                        if (container.dataset.slidingIndicatorBound === 'true') return;

                        container.dataset.slidingIndicatorBound = 'true';
                        const observer = new MutationObserver(() => requestAnimationFrame(updateIndicator));
                        observer.observe(container, { subtree: true, attributes: true, attributeFilter: ['class'] });
                        container.addEventListener('scroll', () => requestAnimationFrame(updateIndicator), { passive: true });
                        window.addEventListener('resize', () => requestAnimationFrame(updateIndicator));
                        document.fonts?.ready?.then(() => requestAnimationFrame(updateIndicator));
                    });
                });
            }

            initSlidingTabIndicators();
            window.syncTabIndicators = initSlidingTabIndicators;

            // ==========================================
            // 1. SIDEBAR TOGGLE
            // ==========================================
            const btnSidebarToggle = document.getElementById('btnSidebarToggle');
            const sidebar = document.getElementById('sidebar');
            const sidebarBackdrop = document.getElementById('sidebarBackdrop');
            const sidebarToggleIcon = document.getElementById('sidebarToggleIcon');
            const mobileSidebarQuery = window.matchMedia('(max-width: 900px)');
            let isSidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';

            function applySidebarState() {
                if (mobileSidebarQuery.matches) {
                    body.classList.remove('sidebar-collapsed');
                    return;
                }

                body.classList.toggle('sidebar-collapsed', isSidebarCollapsed);
            }

            function closeMobileSidebar() {
                body.classList.remove('sidebar-mobile-open');
                btnSidebarToggle?.setAttribute('aria-expanded', 'false');
                sidebar?.setAttribute('aria-hidden', mobileSidebarQuery.matches ? 'true' : 'false');

                if (mobileSidebarQuery.matches && sidebarToggleIcon) {
                    sidebarToggleIcon.className = 'fi fi-rr-menu-burger';
                }
            }

            function openMobileSidebar() {
                body.classList.add('sidebar-mobile-open');
                btnSidebarToggle?.setAttribute('aria-expanded', 'true');
                sidebar?.setAttribute('aria-hidden', 'false');

                if (sidebarToggleIcon) {
                    sidebarToggleIcon.className = 'fi fi-br-cross';
                }
            }

            function syncSidebarMode() {
                applySidebarState();

                if (mobileSidebarQuery.matches) {
                    closeMobileSidebar();
                    btnSidebarToggle?.setAttribute('title', 'Buka Menu');
                    btnSidebarToggle?.setAttribute('aria-label', 'Buka menu navigasi');
                } else {
                    body.classList.remove('sidebar-mobile-open');
                    sidebar?.setAttribute('aria-hidden', 'false');
                    btnSidebarToggle?.setAttribute('aria-expanded', String(!isSidebarCollapsed));
                    btnSidebarToggle?.setAttribute('title', 'Toggle Sidebar');
                    btnSidebarToggle?.setAttribute('aria-label', 'Toggle sidebar');

                    if (sidebarToggleIcon) {
                        sidebarToggleIcon.className = 'fi fi-sr-angle-double-small-left';
                    }
                }
            }

            syncSidebarMode();

            if (btnSidebarToggle) {
                btnSidebarToggle.addEventListener('click', function () {
                    if (mobileSidebarQuery.matches) {
                        if (body.classList.contains('sidebar-mobile-open')) {
                            closeMobileSidebar();
                        } else {
                            openMobileSidebar();
                        }

                        return;
                    }

                    isSidebarCollapsed = !isSidebarCollapsed;
                    applySidebarState();
                    btnSidebarToggle.setAttribute('aria-expanded', String(!isSidebarCollapsed));
                    localStorage.setItem('sidebarCollapsed', isSidebarCollapsed);
                });
            }

            sidebarBackdrop?.addEventListener('click', closeMobileSidebar);

            sidebar?.querySelectorAll('a.sidebar__nav-item, .sidebar__logout').forEach(item => {
                item.addEventListener('click', () => {
                    if (mobileSidebarQuery.matches) closeMobileSidebar();
                });
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && body.classList.contains('sidebar-mobile-open')) {
                    closeMobileSidebar();
                }
            });

            if (typeof mobileSidebarQuery.addEventListener === 'function') {
                mobileSidebarQuery.addEventListener('change', syncSidebarMode);
            } else if (typeof mobileSidebarQuery.addListener === 'function') {
                mobileSidebarQuery.addListener(syncSidebarMode);
            }

            // ==========================================
            // 2. SUBMENU TOGGLE (Data Master)
            // ==========================================
            document.querySelectorAll('.js-submenu-toggle').forEach(function (toggle) {
                toggle.addEventListener('click', function (e) {
                    e.preventDefault();

                    // Find the submenu wrapper immediately after this toggle's parent block
                    const wrapper = toggle.nextElementSibling;
                    if (!wrapper || !wrapper.classList.contains('sidebar__submenu-wrapper')) return;

                    const isOpen = wrapper.classList.contains('open');
                    wrapper.classList.toggle('open', !isOpen);
                    toggle.classList.toggle('submenu-open', !isOpen);
                });
            });

            // ==========================================
            // 3. DARK MODE TOGGLE
            // ==========================================
            const btnTheme = document.getElementById('btnTheme');
            const themeIcon = document.getElementById('themeIcon');
            let isDarkMode = localStorage.getItem('theme') === 'dark';

            function enableDarkMode(animate) {
                body.classList.add('dark-mode');
                if (animate) {
                    themeIcon.className = 'fi fi-rr-moon';
                    themeIcon.classList.add('prepare-from-bottom');
                    void themeIcon.offsetWidth;
                    themeIcon.classList.remove('prepare-from-bottom');
                } else {
                    themeIcon.className = 'fi fi-rr-moon';
                }
            }

            function disableDarkMode(animate) {
                body.classList.remove('dark-mode');
                if (animate) {
                    themeIcon.className = 'fi fi-rr-sun';
                    themeIcon.classList.add('prepare-from-top');
                    void themeIcon.offsetWidth;
                    themeIcon.classList.remove('prepare-from-top');
                } else {
                    themeIcon.className = 'fi fi-rr-sun';
                }
            }

            if (isDarkMode) enableDarkMode(false);

            if (btnTheme) {
                btnTheme.addEventListener('click', function () {
                    isDarkMode = !isDarkMode;
                    if (isDarkMode) {
                        themeIcon.classList.add('animate-out-up');
                        setTimeout(function () {
                            themeIcon.classList.remove('animate-out-up');
                            enableDarkMode(true);
                            localStorage.setItem('theme', 'dark');
                        }, 200);
                    } else {
                        themeIcon.classList.add('animate-out-down');
                        setTimeout(function () {
                            themeIcon.classList.remove('animate-out-down');
                            disableDarkMode(true);
                            localStorage.setItem('theme', 'light');
                        }, 200);
                    }
                });
            }

            // ==========================================
            // 4. MODAL LOGIC
            // ==========================================

            // Open modal
            document.querySelectorAll('.js-open-modal').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const targetId = btn.getAttribute('data-modal');
                    const modal = document.getElementById(targetId);
                    if (modal) modal.classList.add('show');
                });
            });

            // Close modal — button
            document.querySelectorAll('.js-close-modal').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const targetId = btn.getAttribute('data-modal');
                    const modal = document.getElementById(targetId);
                    if (modal) modal.classList.remove('show');
                });
            });

            // Close modal — backdrop click
            document.querySelectorAll('.modal-overlay').forEach(function (overlay) {
                overlay.addEventListener('click', function (e) {
                    if (e.target === overlay) overlay.classList.remove('show');
                });
                const box = overlay.querySelector('.modal-box');
                if (box) box.addEventListener('click', function (e) { e.stopPropagation(); });
            });

            // Close modal — Escape key
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') {
                    document.querySelectorAll('.modal-overlay.show').forEach(function (m) {
                        m.classList.remove('show');
                    });
                }
            });

            // ==========================================
            // 4B. LOADING STATE (Konfirmasi TTD & Download)
            // ==========================================

            // Saat form konfirmasi (TTD/arsip, hapus) dikirim: tampilkan spinner di
            // tombol konfirmasi & cegah klik ganda. Halaman reload setelah redirect,
            // jadi tombol kembali normal dengan sendirinya.
            document.addEventListener('submit', function (e) {
                const confirmBtn = e.target.querySelector?.('.btn-modal--confirm');
                if (!confirmBtn || confirmBtn.dataset.loading === 'true') return;

                confirmBtn.dataset.loading = 'true';
                confirmBtn.innerHTML = '<span class="btn-spinner"></span> Memproses...';
                confirmBtn.disabled = true;
            });

            // Download laporan: ambil berkas lewat fetch agar spinner berhenti tepat
            // saat unduhan selesai (bukan lagi timer perkiraan).
            const filenameFromDisposition = (disposition) => {
                if (!disposition) return '';
                const match = disposition.match(/filename\*?=(?:UTF-8'')?["']?([^"';]+)/i);
                if (!match) return '';
                try { return decodeURIComponent(match[1]); } catch (_) { return match[1]; }
            };

            document.addEventListener('click', async function (e) {
                const link = e.target.closest?.('a.btn-act.download');
                if (!link || link.dataset.loading === 'true') return;
                e.preventDefault();

                const url = link.getAttribute('href');
                if (!url || url === '#') return;

                link.dataset.loading = 'true';
                link.dataset.label = link.innerHTML;
                link.classList.add('is-loading');
                link.innerHTML = '<span class="btn-spinner"></span> Menyiapkan...';

                try {
                    const response = await fetch(url, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin',
                    });
                    if (!response.ok) throw new Error('Gagal mengunduh berkas.');

                    const blob = await response.blob();
                    const filename = filenameFromDisposition(response.headers.get('Content-Disposition'));
                    const objectUrl = URL.createObjectURL(blob);
                    const anchor = document.createElement('a');
                    anchor.href = objectUrl;
                    anchor.download = filename || '';
                    document.body.appendChild(anchor);
                    anchor.click();
                    anchor.remove();
                    window.setTimeout(() => URL.revokeObjectURL(objectUrl), 10000);
                } catch (error) {
                    window.location.href = url;
                } finally {
                    link.innerHTML = link.dataset.label;
                    link.classList.remove('is-loading');
                    link.dataset.loading = 'false';
                }
            });

            // ==========================================
            // 5. TAB FILTER (Laporan Masuk)
            // ==========================================
            const reportTabs = document.querySelectorAll('.report-tab');
            const reportItems = document.querySelectorAll('.report-item');

            function filterReports(filter) {
                reportItems.forEach(function (item) {
                    const match = filter === 'all' || item.getAttribute('data-category') === filter;
                    item.style.display = match ? '' : 'none';
                });
            }

            reportTabs.forEach(function (tab) {
                const filter = tab.getAttribute('data-filter');
                const countEl = tab.querySelector('.report-tab__count');
                if (countEl) {
                    const reportCount = filter === 'all'
                        ? reportItems.length
                        : document.querySelectorAll('.report-item[data-category="' + filter + '"]').length;
                    countEl.textContent = reportCount > 0 ? reportCount : '';
                    countEl.hidden = reportCount <= 0;
                }
                tab.addEventListener('click', function () {
                    reportTabs.forEach(function (t) { t.classList.remove('active'); });
                    tab.classList.add('active');
                    filterReports(filter);
                });
            });

            // ==========================================
            // 6. FILTER TOGGLE (Arsip)
            // ==========================================
            const btnFilter = document.getElementById('btnFilter');
            const archiveFilters = document.getElementById('archiveFilters');
            if (btnFilter && archiveFilters) {
                btnFilter.addEventListener('click', function () {
                    const isOpen = !archiveFilters.classList.toggle('collapsed');
                    btnFilter.classList.toggle('btn-tool--active', isOpen);
                });
            }

            // ==========================================
            // 7. CUSTOM DROPDOWN (Regu, Shift & sort)
            // ==========================================
            document.querySelectorAll('.filter-select-wrapper').forEach(function (wrapper) {
                const select = wrapper.querySelector('select');
                if (!select) return;
                select.style.display = 'none';

                const trigger = document.createElement('div');
                trigger.className = 'filter-input filter-select-trigger';
                const label = document.createElement('span');
                label.textContent = select.options[select.selectedIndex].text;
                trigger.appendChild(label);
                wrapper.insertBefore(trigger, select.nextSibling);

                const list = document.createElement('div');
                list.className = 'filter-select-options';
                Array.from(select.options).forEach(function (opt, i) {
                    const item = document.createElement('div');
                    item.className = 'filter-select-option';
                    item.textContent = opt.text;
                    if (i === select.selectedIndex) item.classList.add('selected');
                    item.addEventListener('click', function (e) {
                        e.stopPropagation();
                        select.value = opt.value;
                        select.dispatchEvent(new Event('change'));
                        label.textContent = opt.text;
                        list.querySelectorAll('.filter-select-option').forEach(function (o) { o.classList.remove('selected'); });
                        item.classList.add('selected');
                        list.classList.remove('open');
                        trigger.classList.remove('focus-active');
                    });
                    list.appendChild(item);
                });
                wrapper.appendChild(list);

                trigger.addEventListener('click', function (e) {
                    e.stopPropagation();
                    document.querySelectorAll('.filter-select-options.open').forEach(function (c) {
                        if (c !== list) {
                            c.classList.remove('open');
                            const t = c.parentElement.querySelector('.filter-select-trigger');
                            if (t) t.classList.remove('focus-active');
                        }
                    });
                    list.classList.toggle('open');
                    trigger.classList.toggle('focus-active');
                });
            });

            document.addEventListener('click', function () {
                document.querySelectorAll('.filter-select-options.open').forEach(function (c) { c.classList.remove('open'); });
                document.querySelectorAll('.filter-select-trigger.focus-active').forEach(function (t) { t.classList.remove('focus-active'); });
            });

        });
