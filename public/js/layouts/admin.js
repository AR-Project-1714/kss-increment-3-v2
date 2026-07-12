document.addEventListener('DOMContentLoaded', function () {
            const body = document.body;

            // Toast notifikasi ditangani komponen bersama (partials/toast.blade.php).
            // window.showAdminToast tersedia sebagai alias dari window.kssToast.

            // 1. SIDEBAR TOGGLE (desktop collapse + mobile off-canvas drawer)
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

            sidebar?.querySelectorAll('a.sidebar__nav-item:not(.js-submenu-toggle), .sidebar__submenu-item, .sidebar__logout').forEach(function (item) {
                item.addEventListener('click', function () {
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

            // 2. SUBMENU TOGGLE (Data Master)
            document.querySelectorAll('.js-submenu-toggle').forEach(function (toggle) {
                toggle.addEventListener('click', function (e) {
                    // Saat sidebar collapsed, klik ikon langsung membuka halaman Data Master
                    // (submenu diakses lewat flyout hover), jadi biarkan navigasi default.
                    if (body.classList.contains('sidebar-collapsed')) return;
                    e.preventDefault();
                    const wrapper = toggle.nextElementSibling;
                    if (!wrapper || !wrapper.classList.contains('sidebar__submenu-wrapper')) return;
                    const isOpen = wrapper.classList.contains('open');
                    wrapper.classList.toggle('open', !isOpen);
                    toggle.classList.toggle('submenu-open', !isOpen);
                });
            });

            // 2b. FLYOUT SUBMENU saat sidebar collapsed.
            // Submenu dipindah jadi panel melayang (position: fixed) di samping ikon
            // ketika di-hover, sehingga Data Master tetap bisa diakses dalam kondisi minimize.
            sidebar?.querySelectorAll('.sidebar__nav-group').forEach(function (group) {
                const trigger = group.querySelector('.js-submenu-toggle');
                const flyout = group.querySelector('.sidebar__submenu-wrapper');
                if (!trigger || !flyout) return;

                let hideTimer = null;

                function positionFlyout() {
                    const rect = trigger.getBoundingClientRect();
                    flyout.style.left = (rect.right + 8) + 'px';
                    let top = rect.top - 6;
                    const overflow = top + flyout.offsetHeight + 8 - window.innerHeight;
                    if (overflow > 0) top = Math.max(8, top - overflow);
                    flyout.style.top = top + 'px';
                }

                function openFlyout() {
                    if (!body.classList.contains('sidebar-collapsed')) return;
                    clearTimeout(hideTimer);
                    positionFlyout();
                    flyout.classList.add('flyout-open');
                }

                function closeFlyout() {
                    clearTimeout(hideTimer);
                    hideTimer = setTimeout(function () {
                        flyout.classList.remove('flyout-open');
                    }, 160);
                }

                group.addEventListener('mouseenter', openFlyout);
                group.addEventListener('mouseleave', closeFlyout);

                window.addEventListener('resize', function () {
                    if (flyout.classList.contains('flyout-open')) positionFlyout();
                });
            });

            // 3. DARK MODE TOGGLE
            const btnTheme = document.getElementById('btnTheme');
            const themeIcon = document.getElementById('themeIcon');
            let isDarkMode = localStorage.getItem('theme') === 'dark';

            function enableDarkMode(animate) {
                body.classList.add('dark-mode');
                themeIcon.className = 'fi fi-rr-moon';
                if (animate) {
                    themeIcon.classList.add('prepare-from-bottom');
                    void themeIcon.offsetWidth;
                    themeIcon.classList.remove('prepare-from-bottom');
                }
            }

            function disableDarkMode(animate) {
                body.classList.remove('dark-mode');
                themeIcon.className = 'fi fi-rr-sun';
                if (animate) {
                    themeIcon.classList.add('prepare-from-top');
                    void themeIcon.offsetWidth;
                    themeIcon.classList.remove('prepare-from-top');
                }
            }

            if (themeIcon) themeIcon.className = isDarkMode ? 'fi fi-rr-moon' : 'fi fi-rr-sun';

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

            // 4. ADMIN MODAL HELPERS
            const confirmModal = document.getElementById('adminConfirmModal');
            const confirmTitle = document.getElementById('adminConfirmTitle');
            const confirmSubtitle = document.getElementById('adminConfirmSubtitle');
            const confirmMessage = document.getElementById('adminConfirmMessage');
            const confirmSummary = document.getElementById('adminConfirmSummary');
            const confirmSummaryText = document.getElementById('adminConfirmSummaryText');
            const confirmAction = document.getElementById('adminConfirmAction');
            const confirmIconWrap = document.getElementById('adminConfirmIconWrap');
            const confirmIcon = document.getElementById('adminConfirmIcon');
            let activeConfirmTrigger = null;

            function modalById(modal) {
                if (!modal) return null;
                if (typeof modal === 'string') return document.getElementById(modal);
                return modal;
            }

            function focusFirstField(modal) {
                const focusable = modal.querySelector('[data-modal-focus], input:not([type="hidden"]):not([disabled]), textarea:not([disabled]), .kss-modal__select-trigger:not(.is-disabled), button');
                if (focusable) window.setTimeout(() => focusable.focus({ preventScroll: true }), 80);
            }

            function syncModalSelect(select) {
                if (!select) return;
                const wrapper = select.closest('.kss-modal__select-wrapper');
                const trigger = wrapper?.querySelector('.kss-modal__select-trigger');
                const label = trigger?.querySelector('span');
                const selectedOption = select.options[select.selectedIndex];

                if (label) label.textContent = selectedOption ? selectedOption.text : '';
                if (trigger) {
                    trigger.classList.toggle('text-placeholder', !selectedOption || selectedOption.disabled || selectedOption.value === '');
                    trigger.classList.toggle('is-disabled', select.disabled);
                    trigger.setAttribute('aria-disabled', select.disabled ? 'true' : 'false');
                    trigger.tabIndex = select.disabled ? -1 : 0;
                }

                wrapper?.querySelectorAll('.kss-modal__select-option').forEach(option => {
                    option.classList.toggle('selected', option.dataset.value === select.value);
                });
            }

            function initModalSelects(root = document) {
                root.querySelectorAll('.kss-modal__select-wrapper').forEach(function (wrapper) {
                    const select = wrapper.querySelector('select.kss-modal__native-select');
                    if (!select) return;

                    if (wrapper.dataset.selectReady === 'true') {
                        syncModalSelect(select);
                        return;
                    }

                    wrapper.dataset.selectReady = 'true';

                    const trigger = document.createElement('div');
                    trigger.className = 'kss-modal__select-trigger';
                    trigger.setAttribute('role', 'button');
                    trigger.setAttribute('tabindex', '0');
                    trigger.innerHTML = '<span></span>';
                    wrapper.insertBefore(trigger, select.nextSibling);

                    if (!wrapper.querySelector('.kss-modal__select-icon')) {
                        const icon = document.createElement('i');
                        icon.className = 'fi fi-rr-angle-small-down kss-modal__select-icon';
                        wrapper.appendChild(icon);
                    }

                    const optionsContainer = document.createElement('div');
                    optionsContainer.className = 'kss-modal__select-options';
                    wrapper.appendChild(optionsContainer);

                    Array.from(select.options).forEach(function (option) {
                        if (option.disabled && option.hidden) return;
                        const optionButton = document.createElement('div');
                        optionButton.className = 'kss-modal__select-option';
                        optionButton.textContent = option.text;
                        optionButton.dataset.value = option.value;
                        optionButton.addEventListener('click', function (e) {
                            e.stopPropagation();
                            if (select.disabled) return;
                            select.value = optionButton.dataset.value;
                            select.dispatchEvent(new Event('change', { bubbles: true }));
                            syncModalSelect(select);
                            optionsContainer.classList.remove('open');
                            trigger.classList.remove('focus-active');
                        });
                        optionsContainer.appendChild(optionButton);
                    });

                    function toggleOptions(e) {
                        e.stopPropagation();
                        if (select.disabled) return;
                        document.querySelectorAll('.kss-modal__select-options.open').forEach(function (container) {
                            if (container !== optionsContainer) {
                                container.classList.remove('open');
                                container.closest('.kss-modal__select-wrapper')?.querySelector('.kss-modal__select-trigger')?.classList.remove('focus-active');
                            }
                        });
                        optionsContainer.classList.toggle('open');
                        trigger.classList.toggle('focus-active');
                    }

                    trigger.addEventListener('click', toggleOptions);
                    trigger.addEventListener('keydown', function (e) {
                        if (e.key === 'Enter' || e.key === ' ') {
                            e.preventDefault();
                            toggleOptions(e);
                        }
                    });
                    select.addEventListener('change', () => syncModalSelect(select));
                    syncModalSelect(select);
                });
            }

            function openModal(modal) {
                const target = modalById(modal);
                if (!target) return;
                target.classList.add('show');
                target.setAttribute('aria-hidden', 'false');
                body.classList.add('modal-open');
                focusFirstField(target);
            }

            function closeModal(modal) {
                const target = modalById(modal);
                if (!target) return;
                target.classList.remove('show');
                target.setAttribute('aria-hidden', 'true');
                if (!document.querySelector('.modal-overlay.show')) {
                    body.classList.remove('modal-open');
                }
            }

            function configureConfirm(trigger) {
                if (!confirmModal || !trigger) return;

                const tone = trigger.dataset.confirmTone || 'primary';
                const iconClass = trigger.dataset.confirmIcon || (tone === 'danger' ? 'fi fi-rr-trash' : 'fi fi-rr-triangle-warning');

                confirmTitle.textContent = trigger.dataset.confirmTitle || 'Konfirmasi tindakan';
                confirmSubtitle.textContent = trigger.dataset.confirmSubtitle || 'Pastikan data yang dipilih sudah benar.';
                confirmMessage.textContent = trigger.dataset.confirmMessage || 'Tindakan ini memerlukan konfirmasi sebelum dilanjutkan.';
                confirmAction.textContent = trigger.dataset.confirmLabel || 'Lanjutkan';
                confirmIcon.className = iconClass;

                confirmIconWrap.className = 'kss-modal__icon';
                if (tone === 'danger') confirmIconWrap.classList.add('kss-modal__icon--danger');
                else if (tone === 'success') confirmIconWrap.classList.add('kss-modal__icon--success');
                else if (tone === 'warning') confirmIconWrap.classList.add('kss-modal__icon--warning');

                confirmAction.className = 'kss-modal__button';
                confirmAction.classList.add(tone === 'danger' ? 'kss-modal__button--danger' : 'kss-modal__button--primary');

                if (trigger.dataset.confirmSummary) {
                    confirmSummary.hidden = false;
                    confirmSummaryText.textContent = trigger.dataset.confirmSummary;
                } else {
                    confirmSummary.hidden = true;
                    confirmSummaryText.textContent = '';
                }
            }

            window.KssAdminModal = {
                open: openModal,
                close: closeModal,
                initSelects: initModalSelects,
                syncSelects: function (root = document) {
                    root.querySelectorAll('select.kss-modal__native-select').forEach(syncModalSelect);
                },
                confirm: function (trigger) {
                    activeConfirmTrigger = trigger;
                    configureConfirm(trigger);
                    openModal(confirmModal);
                }
            };

            document.addEventListener('click', function (e) {
                const closeTrigger = e.target.closest('[data-modal-close]');
                if (closeTrigger) {
                    e.preventDefault();
                    closeModal(closeTrigger.closest('.modal-overlay'));
                    return;
                }

                const modalOpenTrigger = e.target.closest('[data-modal-target]');
                if (modalOpenTrigger) {
                    e.preventDefault();
                    openModal(modalOpenTrigger.dataset.modalTarget);
                    return;
                }

                const confirmTrigger = e.target.closest('[data-confirm]');
                if (confirmTrigger) {
                    e.preventDefault();
                    window.KssAdminModal.confirm(confirmTrigger);
                }
            });

            initModalSelects(document);

            document.querySelectorAll('.modal-overlay').forEach(function (overlay) {
                overlay.addEventListener('click', function (e) {
                    if (e.target === overlay) closeModal(overlay);
                });
            });

            document.addEventListener('click', function () {
                document.querySelectorAll('.kss-modal__select-options.open').forEach(container => container.classList.remove('open'));
                document.querySelectorAll('.kss-modal__select-trigger.focus-active').forEach(trigger => trigger.classList.remove('focus-active'));
            });

            document.addEventListener('keydown', function (e) {
                if (e.key !== 'Escape') return;
                const opened = Array.from(document.querySelectorAll('.modal-overlay.show')).pop();
                if (opened) closeModal(opened);
            });

            document.addEventListener('submit', function (e) {
                const form = e.target.closest('[data-preview-submit]');
                if (!form) return;
                e.preventDefault();
                closeModal(form.closest('.modal-overlay'));
            });

            function filenameFromDisposition(disposition) {
                if (!disposition) return '';
                const match = disposition.match(/filename\*?=(?:UTF-8'')?["']?([^"';]+)/i);
                if (!match) return '';
                try { return decodeURIComponent(match[1]); } catch (e) { return match[1]; }
            }

            // Unduh berkas lewat fetch agar spinner pada tombol berhenti tepat saat
            // unduhan selesai (perilaku sama seperti halaman petugas & manajer).
            async function startAdminDownload(button, url) {
                if (!button || !url || url === '#') return;
                if (button.dataset.loading === 'true') return;

                button.dataset.loading = 'true';
                button.dataset.label = button.innerHTML;
                button.classList.add('is-loading');
                // Tombol berlabel teks tampilkan "Menyiapkan...", tombol ikon-saja
                // cukup spinner agar bentuknya tidak melar.
                const hasText = button.textContent.trim() !== '';
                button.innerHTML = hasText
                    ? '<span class="btn-spinner"></span> Menyiapkan...'
                    : '<span class="btn-spinner" style="margin-right:0;"></span>';

                try {
                    const response = await fetch(url, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin',
                    });
                    if (!response.ok) throw new Error('Gagal mengunduh berkas.');

                    const blob = await response.blob();
                    const filename = filenameFromDisposition(response.headers.get('Content-Disposition'));
                    const objectUrl = URL.createObjectURL(blob);
                    const link = document.createElement('a');
                    link.href = objectUrl;
                    link.download = filename || '';
                    document.body.appendChild(link);
                    link.click();
                    link.remove();
                    window.setTimeout(() => URL.revokeObjectURL(objectUrl), 10000);
                } catch (error) {
                    window.location.href = url;
                } finally {
                    button.innerHTML = button.dataset.label;
                    button.classList.remove('is-loading');
                    button.dataset.loading = 'false';
                }
            }

            if (confirmAction) {
                confirmAction.addEventListener('click', function () {
                    const redirect = activeConfirmTrigger?.dataset.confirmRedirect;
                    const submitForm = activeConfirmTrigger?.dataset.confirmSubmit === 'true';
                    const form = submitForm ? activeConfirmTrigger?.closest('form') : null;
                    closeModal(confirmModal);
                    if (form) {
                        if (typeof form.requestSubmit === 'function') form.requestSubmit();
                        else form.submit();
                    }
                    if (redirect) window.location.href = redirect;
                    activeConfirmTrigger = null;
                });
            }

            // Tombol unduh admin: langsung mengunduh tanpa pop up konfirmasi
            // (perilaku sama seperti halaman manajer), dengan spinner di tombol.
            document.addEventListener('click', function (e) {
                const button = e.target.closest?.('[data-download-url]');
                if (!button) return;
                e.preventDefault();
                startAdminDownload(button, button.dataset.downloadUrl);
            });
        });
