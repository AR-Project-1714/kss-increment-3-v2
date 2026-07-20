document.addEventListener('DOMContentLoaded', function () {
        // 1. STICKY HEADER
        const contentHeader = document.querySelector('.content-header');
        if (contentHeader) {
            const wrapper = document.createElement('div');
            wrapper.style.width = '100%'; wrapper.style.position = 'relative';
            contentHeader.parentNode.insertBefore(wrapper, contentHeader);
            wrapper.appendChild(contentHeader);
            window.addEventListener('scroll', () => {
                const rect = wrapper.getBoundingClientRect();
                if (rect.bottom < 0) {
                    if (!contentHeader.classList.contains('is-sticky')) {
                        wrapper.style.height = `${wrapper.offsetHeight}px`;
                        contentHeader.classList.add('is-sticky');
                        requestAnimationFrame(() => contentHeader.classList.add('show-sticky'));
                    }
                } else if (contentHeader.classList.contains('is-sticky')) {
                    contentHeader.classList.remove('show-sticky', 'is-sticky');
                    wrapper.style.height = 'auto';
                }
            });
        }

        // 2. DARK MODE TOGGLE
        const themeBtn = document.getElementById('themeToggle');
        const themeIcon = document.getElementById('themeIcon');
        const body = document.body;
        let isDark = localStorage.getItem('theme') === 'dark';
        if (isDark && themeIcon) themeIcon.className = 'fi fi-rr-moon';
        if (themeBtn && themeIcon) {
            themeBtn.addEventListener('click', () => {
                isDark = !isDark;
                themeIcon.classList.add(isDark ? 'animate-out-up' : 'animate-out-down');
                setTimeout(() => {
                    body.classList.toggle('dark-mode', isDark);
                    localStorage.setItem('theme', isDark ? 'dark' : 'light');
                    themeIcon.className = isDark ? 'fi fi-rr-moon' : 'fi fi-rr-sun';
                    themeIcon.classList.remove('animate-out-up', 'animate-out-down');
                    themeIcon.classList.add(isDark ? 'prepare-from-bottom' : 'prepare-from-top');
                    void themeIcon.offsetWidth;
                    themeIcon.classList.remove('prepare-from-bottom', 'prepare-from-top');
                }, 200);
            });
        }

        // 3. TOAST — ditangani komponen bersama (partials/toast.blade.php).
        //    window.showReportToast tersedia sebagai alias dari window.kssToast.

        function initSlidingTabIndicators() {
            [
                { containerSelector: '.tab-content', itemSelector: '.list-tab', indicatorClass: 'tab-slide-indicator' },
                { containerSelector: '.tab-form', itemSelector: '.list-form-tab', indicatorClass: 'tab-form-indicator' },
                { containerSelector: '.tab-group', itemSelector: '.tab-sections', indicatorClass: 'tab-group-indicator' },
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
                        if (!active || !active.offsetWidth) {
                            indicator.style.opacity = '0';
                            return;
                        }

                        // Baru terlihat pertama kali (mis. tab-group di dalam step yang baru
                        // dibuka dari d-none) — langsung posisikan tanpa transition, supaya
                        // pill tidak "membesar" dari 0. Pergantian tab berikutnya tetap animasi.
                        const firstReveal = indicator.dataset.positioned !== 'true';
                        if (firstReveal) indicator.style.transition = 'none';

                        indicator.style.opacity = '1';
                        indicator.style.width = `${active.offsetWidth}px`;
                        indicator.style.transform = `translateX(${active.offsetLeft}px)`;

                        if (firstReveal) {
                            void indicator.offsetWidth;
                            indicator.style.transition = '';
                            indicator.dataset.positioned = 'true';
                        }
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

        // 4. NUMBER INPUT SAFETY
        document.addEventListener('keydown', e => { if (e.target.matches?.('input[type="number"]') && ['-','+','e','E'].includes(e.key)) e.preventDefault(); });

        // 5. CUSTOM SELECT (native-select -> custom-input)
        function initCustomSelects() {
            document.querySelectorAll('.input-wrapper').forEach(wrapper => {
                const native = wrapper.querySelector('select.native-select');
                if (!native || wrapper.querySelector('.custom-input.cs-trigger')) return;
                native.style.display = 'none';
                const trigger = document.createElement('div');
                trigger.className = 'custom-input cs-trigger d-flex align-items-center';
                trigger.tabIndex = 0;
                const span = document.createElement('span');
                const selected = native.options[native.selectedIndex];
                span.textContent = selected ? selected.text : '';
                if (!selected || selected.disabled || selected.value === '') trigger.classList.add('text-placeholder');
                trigger.appendChild(span);
                wrapper.insertBefore(trigger, native.nextSibling);
                const list = document.createElement('div');
                list.className = 'custom-options-container';
                Array.from(native.options).forEach(opt => {
                    if (opt.disabled && opt.hidden) return;
                    const div = document.createElement('div');
                    div.className = 'custom-option'; div.textContent = opt.text; div.dataset.value = opt.value;
                    if (opt.selected) div.classList.add('selected');
                    div.addEventListener('click', e => {
                        e.stopPropagation();
                        native.value = opt.value; native.dispatchEvent(new Event('change', { bubbles: true }));
                        span.textContent = opt.text; trigger.classList.remove('text-placeholder');
                        list.querySelectorAll('.custom-option').forEach(o => o.classList.remove('selected'));
                        div.classList.add('selected'); list.classList.remove('open'); trigger.classList.remove('focus-active');
                    });
                    list.appendChild(div);
                });
                wrapper.appendChild(list);
                trigger.addEventListener('click', e => {
                    e.stopPropagation();
                    document.querySelectorAll('.custom-options-container.open').forEach(c => { if (c !== list) { c.classList.remove('open'); c.previousElementSibling?.classList.remove('focus-active'); } });
                    list.classList.toggle('open'); trigger.classList.toggle('focus-active');
                });
            });
        }
        initCustomSelects();

        // Custom select untuk SELECT di dalam tabel (.tbl-select-wrapper > select.tbl-native-select),
        // styling dropdown mengikuti modul operasional. Dipanggil ulang saat baris ditambah.
        function hydrateTableSelects(root = document) {
            root.querySelectorAll('.tbl-select-wrapper').forEach(wrapper => {
                const native = wrapper.querySelector('select.tbl-native-select');
                if (!native || wrapper.querySelector('.tbl-custom-select-trigger')) return;
                native.style.display = 'none';
                const caret = wrapper.querySelector('.sel-caret');
                const trigger = document.createElement('div');
                trigger.className = 'tbl-custom-select-trigger';
                const span = document.createElement('span');
                trigger.appendChild(span);
                wrapper.insertBefore(trigger, native.nextSibling);
                const list = document.createElement('div');
                list.className = 'tbl-custom-options';
                function updateTrigger() {
                    const o = native.options[native.selectedIndex];
                    span.textContent = o ? o.text : '';
                    trigger.classList.toggle('text-placeholder', !o || o.disabled || o.value === '');
                    list.querySelectorAll('.tbl-custom-option').forEach(op => op.classList.toggle('selected', op.dataset.value === native.value));
                }
                Array.from(native.options).forEach(option => {
                    if (option.disabled && option.hidden) return;
                    const op = document.createElement('div');
                    op.className = 'tbl-custom-option';
                    op.textContent = option.text;
                    op.dataset.value = option.value;
                    op.addEventListener('click', e => {
                        e.stopPropagation();
                        native.value = op.dataset.value;
                        native.dispatchEvent(new Event('change', { bubbles: true }));
                        if (trigger.__closeTblList) trigger.__closeTblList();
                        else {
                            list.classList.remove('open');
                            trigger.classList.remove('focus-active');
                        }
                    });
                    list.appendChild(op);
                });
                // Kotak pencarian (untuk dropdown dengan banyak opsi, mis. Jenis Unit).
                let searchInput = null;
                if (wrapper.dataset.search === 'true') {
                    const searchBox = document.createElement('div');
                    searchBox.className = 'tbl-search';
                    searchInput = document.createElement('input');
                    searchInput.type = 'text';
                    searchInput.placeholder = 'Ketik untuk mencari unit...';
                    searchInput.addEventListener('click', e => e.stopPropagation());
                    searchInput.addEventListener('input', () => {
                        const q = searchInput.value.trim().toLowerCase();
                        list.querySelectorAll('.tbl-custom-option').forEach(op => {
                            op.style.display = (!q || op.textContent.toLowerCase().includes(q)) ? '' : 'none';
                        });
                    });
                    searchBox.appendChild(searchInput);
                    list.insertBefore(searchBox, list.firstChild);
                }
                wrapper.appendChild(list);
                list.__trigger = trigger;
                native.addEventListener('change', updateTrigger);

                // Posisikan dropdown sebagai fixed relatif ke trigger agar tidak
                // terpotong oleh overflow tabel (muncul penuh di atas konten lain).
                // Dropdown selalu menempel tepat di bawah field (tidak pernah ke atas).
                function positionList() {
                    const r = trigger.getBoundingClientRect();
                    list.style.position = 'fixed';
                    list.style.left = r.left + 'px';
                    list.style.width = r.width + 'px';
                    list.style.zIndex = '9998';
                    list.style.top = (r.bottom + 4) + 'px';
                }
                function closeList() {
                    list.classList.remove('open');
                    trigger.classList.remove('focus-active');
                    list.style.position = ''; list.style.top = ''; list.style.left = ''; list.style.width = ''; list.style.zIndex = '';
                    if (window.__pmlOpenTblList === list) window.__pmlOpenTblList = null;
                }
                trigger.__closeTblList = closeList;
                trigger.__reposition = positionList;

                trigger.addEventListener('click', e => {
                    e.stopPropagation();
                    const willOpen = !list.classList.contains('open');
                    document.querySelectorAll('.tbl-custom-options.open').forEach(c => {
                        const t = c.__trigger || c.previousElementSibling;
                        if (t && t.__closeTblList) t.__closeTblList();
                    });
                    if (willOpen) {
                        list.classList.add('open');
                        trigger.classList.add('focus-active');
                        positionList();
                        window.__pmlOpenTblList = list;
                        if (searchInput) {
                            searchInput.value = '';
                            list.querySelectorAll('.tbl-custom-option').forEach(op => { op.style.display = ''; });
                            setTimeout(() => searchInput.focus({ preventScroll: true }), 20);
                        }
                    } else {
                        closeList();
                    }
                });
                updateTrigger();
            });
        }
        window.__pmlHydrateSelects = hydrateTableSelects;
        hydrateTableSelects();

        document.addEventListener('click', () => {
            document.querySelectorAll('.custom-options-container.open').forEach(c => c.classList.remove('open'));
            document.querySelectorAll('.custom-input.focus-active').forEach(t => t.classList.remove('focus-active'));
            document.querySelectorAll('.tbl-custom-options.open').forEach(c => {
                const t = c.__trigger || c.previousElementSibling;
                if (t && t.__closeTblList) t.__closeTblList(); else c.classList.remove('open');
            });
        });

        // Dropdown tabel memakai position:fixed; ikut bergerak saat halaman/area di-scroll.
        ['scroll', 'resize'].forEach(ev => window.addEventListener(ev, () => {
            const list = window.__pmlOpenTblList;
            if (list && list.classList.contains('open')) {
                const t = list.__trigger || list.previousElementSibling;
                if (t && t.__reposition) t.__reposition();
            }
        }, true));

        // 6. TIME INPUT FORMATTING (auto :)
        // Jam tidak boleh melebihi 24:00; kelebihannya dibungkus ke jam nyata
        // (mis. ketik "40:00" -> otomatis jadi "16:00", 40 - 24 = 16).
        document.addEventListener('input', e => {
            const el = e.target;
            if (!el.matches?.('.time-picker-input')) return;
            if (e.inputType === 'deleteContentBackward' || e.inputType === 'deleteContentForward') return;
            let v = el.value.replace(/\D/g, '');
            if (v.length > 4) v = v.substring(0, 4);
            if (v.length >= 2) v = String(Number(v.substring(0, 2)) % 24).padStart(2, '0') + v.substring(2);
            if (v.length >= 3) el.value = v.substring(0, 2) + ':' + v.substring(2);
            else el.value = v;
        });

        // 7. FORM SECTION SPA NAV (.list-form-tab + .form-step)
        const formTabs = document.querySelectorAll('.list-form-tab');
        const formSteps = document.querySelectorAll('.form-step');
        let currentStep = 0;
        function scrollToMaintenanceFormTabs() {
            const tabForm = document.querySelector('.tab-form');
            if (!tabForm) return;

            const topGap = window.innerWidth <= 768 ? 16 : 40;
            const targetTop = Math.max(0, tabForm.getBoundingClientRect().top + window.scrollY - topGap);
            window.scrollTo({ top: targetTop, behavior: 'smooth' });
        }

        window.__pmlShowStep = function (index) {
            if (!formSteps.length || index < 0 || index >= formSteps.length) return;
            formSteps.forEach(s => { s.classList.remove('d-flex'); s.classList.add('d-none'); });
            formTabs.forEach(t => t.classList.remove('active'));
            formSteps[index].classList.remove('d-none'); formSteps[index].classList.add('d-flex');
            formTabs[index]?.classList.add('active');
            formTabs[index]?.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
            currentStep = index;
            requestAnimationFrame(scrollToMaintenanceFormTabs);
            // Sub-tab group (Kondisi Unit) baru terukur setelah stepnya tampil.
            requestAnimationFrame(() => window.syncTabIndicators?.());
        };
        if (formTabs.length && formSteps.length) {
            formTabs.forEach((tab, i) => tab.addEventListener('click', () => window.__pmlShowStep(i)));
            document.querySelectorAll('.btn-next-step').forEach(b => b.addEventListener('click', e => { e.preventDefault(); window.__pmlShowStep(currentStep + 1); }));
            document.querySelectorAll('.btn-back-step').forEach(b => b.addEventListener('click', e => { e.preventDefault(); window.__pmlShowStep(currentStep - 1); }));
        }

        // 8. GENERIC MODAL OPEN/CLOSE (data-open-modal / data-close-modal)
        function openModal(id) { document.getElementById(id)?.classList.add('show'); }
        function closeAllModals() { document.querySelectorAll('.modal-overlay.show').forEach(m => m.classList.remove('show')); }
        window.__pmlOpenModal = openModal; window.__pmlCloseModals = closeAllModals;
        document.querySelectorAll('[data-open-modal]').forEach(btn => btn.addEventListener('click', () => openModal(btn.dataset.openModal)));
        document.querySelectorAll('[data-close-modal]').forEach(btn => btn.addEventListener('click', closeAllModals));
        document.querySelectorAll('.modal-overlay').forEach(modal => {
            modal.addEventListener('click', e => { if (e.target === modal) closeAllModals(); });
        });
        document.addEventListener('keydown', e => { if (e.key === 'Escape') closeAllModals(); });
    });
