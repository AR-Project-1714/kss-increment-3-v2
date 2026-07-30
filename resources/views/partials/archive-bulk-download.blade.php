{{--
    CSS & JS untuk pilih baris arsip + unduh massal.
    Dipakai halaman arsip Admin & Manajer lewat: @include('partials.archive-bulk-download')
    Markup-nya disediakan partials/archive-bulk-bar.blade.php.

    Dua jalur unduhan:
      - <= data-instant-limit laporan: satu request, ZIP langsung terunduh.
      - di atasnya: bundel dirakit queue worker di latar; halaman memantau
        progres lewat polling dan boleh ditutup (token disimpan di
        localStorage, jadi progres muncul lagi saat halaman dibuka kembali).

    Mandiri: membawa CSS & JS sendiri agar perilakunya identik di kedua halaman.
--}}
<style>
    .archive-select {
        position: relative;
        width: 15px;
        height: 15px;
        flex: 0 0 auto;
        margin: 0;
        appearance: none;
        -webkit-appearance: none;
        border: 1.5px solid var(--blue-main-40);
        border-radius: 4px;
        background-color: var(--white);
        cursor: pointer;
        transition: border-color .15s ease-out, background-color .15s ease-out;
    }

    .archive-select:hover { border-color: var(--blue-main); }

    .archive-select:checked,
    .archive-select:indeterminate {
        border-color: var(--blue-main);
        background-color: var(--blue-main);
    }

    .archive-select:checked::after {
        content: "";
        position: absolute;
        left: 4px;
        top: 1px;
        width: 4px;
        height: 8px;
        border: solid #fff;
        border-width: 0 1.5px 1.5px 0;
        transform: rotate(45deg);
    }

    .archive-select:indeterminate::after {
        content: "";
        position: absolute;
        left: 2px;
        top: 5.5px;
        width: 7px;
        border-top: 1.5px solid #fff;
    }

    .archive-select:focus-visible { outline: 2px solid var(--blue-main-40); outline-offset: 2px; }
    .archive-select:disabled { opacity: .4; cursor: not-allowed; }

    .archive-bulk-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
        padding: 10px 14px;
        border: 1px solid var(--blue-main-25);
        border-radius: 10px;
        background-color: var(--blue-main-5);
    }

    .archive-bulk-bar[hidden] { display: none; }

    /* Tanpa pilihan, form unduh massal tidak boleh menyisakan jarak di antara
       toolbar dan tabel (keduanya flex item pada kartu yang sama). */
    [data-bulk-form].is-empty { display: none; }

    .archive-bulk-bar__info {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        min-width: 0;
        flex-wrap: wrap;
        font-size: 12px;
        font-weight: 600;
        color: var(--blue-main);
    }

    .archive-bulk-bar__info i { position: relative; top: 1px; }
    .archive-bulk-bar__hint { font-size: 10px; font-weight: 400; color: var(--black-secondary); }
    .archive-bulk-bar__actions { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }

    .archive-bulk-bar .btn-tool.is-loading,
    .archive-bundle-panel .btn-tool.is-loading {
        opacity: .85;
        cursor: progress;
        pointer-events: none;
    }

    /* =============================================
       PANEL PROGRES BUNDEL LATAR
       ============================================= */
    .archive-bundle-panel {
        display: flex;
        flex-direction: column;
        gap: 10px;
        padding: 12px 14px;
        border: 1px solid var(--smooth-border);
        border-radius: 10px;
        background-color: var(--white);
        box-shadow: 0 2px 10px rgba(37, 99, 235, .06);
    }

    .archive-bundle-panel[hidden] { display: none; }

    .archive-bundle-panel--ready { border-color: var(--success); background-color: var(--success-10); }
    .archive-bundle-panel--failed { border-color: var(--red-main); background-color: var(--red-main-10); }

    .archive-bundle-panel__head {
        display: flex;
        align-items: baseline;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
    }

    .archive-bundle-panel__title {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: 12px;
        font-weight: 600;
        color: var(--black);
    }

    .archive-bundle-panel__title i { position: relative; top: 1px; color: var(--blue-main); }
    .archive-bundle-panel--ready .archive-bundle-panel__title i { color: var(--success); }
    .archive-bundle-panel--failed .archive-bundle-panel__title i { color: var(--red-main); }

    .archive-bundle-panel__meta {
        font-size: 11px;
        font-weight: 600;
        color: var(--blue-main);
        white-space: nowrap;
    }

    .archive-bundle-progress {
        position: relative;
        height: 7px;
        overflow: hidden;
        border-radius: 999px;
        background-color: var(--blue-main-10);
    }

    .archive-bundle-progress__fill {
        display: block;
        width: 0;
        height: 100%;
        border-radius: 999px;
        background-color: var(--blue-main);
        transition: width .35s ease-out;
    }

    .archive-bundle-panel--ready .archive-bundle-progress__fill { background-color: var(--success); }
    .archive-bundle-panel--failed .archive-bundle-progress__fill { background-color: var(--red-main); }

    /* Selagi menunggu antrean, progres belum bergerak — beri denyut halus
       supaya jelas bahwa proses tidak macet. */
    .archive-bundle-panel--queued .archive-bundle-progress__fill {
        width: 100% !important;
        opacity: .35;
        animation: archiveBundlePulse 1.4s ease-in-out infinite;
    }

    @keyframes archiveBundlePulse {
        0%, 100% { opacity: .18; }
        50% { opacity: .45; }
    }

    .archive-bundle-panel__foot {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
    }

    .archive-bundle-panel__hint { min-width: 0; font-size: 10px; color: var(--black-secondary); }
    .archive-bundle-panel__actions { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
    .archive-bundle-panel__actions [hidden] { display: none; }

    @media (max-width: 560px) {
        .archive-bulk-bar,
        .archive-bulk-bar__actions { width: 100%; }

        /* Tombol "pilih semua" berlabel panjang: beri satu baris penuh supaya
           tidak terjepit jadi tiga baris teks. */
        .archive-bulk-bar__actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            align-items: stretch;
        }

        .archive-bulk-bar__actions [data-bulk-select-all] { grid-column: 1 / -1; }
        .archive-bulk-bar__actions > * { justify-content: center; }

        .archive-bundle-panel__foot,
        .archive-bundle-panel__actions { width: 100%; }
        .archive-bundle-panel__actions > * { flex: 1 1 0; justify-content: center; }
    }
</style>

<script>
    (function () {
        function init() {
            const form = document.querySelector('[data-bulk-form]');
            if (!form) return;

            const bar = form.querySelector('[data-bulk-bar]');
            const countLabel = form.querySelector('[data-bulk-count]');
            const barHint = form.querySelector('[data-bulk-hint]');
            const allFlag = form.querySelector('[data-bulk-all]');
            const keysBox = form.querySelector('[data-bulk-keys]');
            const selectAllButton = form.querySelector('[data-bulk-select-all]');
            const clearButton = form.querySelector('[data-bulk-clear]');
            const submitButton = form.querySelector('[data-bulk-submit]');
            const submitLabel = form.querySelector('[data-bulk-submit-label]');
            const master = document.querySelector('[data-bulk-master]');
            const checkboxes = Array.from(document.querySelectorAll('[data-bulk-checkbox]'));

            const panel = document.querySelector('[data-bundle-panel]');
            const panelTitle = panel?.querySelector('[data-bundle-title]');
            const panelMeta = panel?.querySelector('[data-bundle-meta]');
            const panelHint = panel?.querySelector('[data-bundle-hint]');
            const panelFill = panel?.querySelector('[data-bundle-fill]');
            const panelProgress = panel?.querySelector('[data-bundle-progress]');
            const panelDownload = panel?.querySelector('[data-bundle-download]');
            const panelDismiss = panel?.querySelector('[data-bundle-dismiss]');
            const panelDismissLabel = panel?.querySelector('[data-bundle-dismiss-label]');

            const filterTotal = Number(form.dataset.filterTotal || 0);
            const instantLimit = Number(form.dataset.instantLimit || 50) || 50;
            const bundleLimit = Number(form.dataset.bundleLimit || 1000) || 1000;
            const bundleUrl = form.dataset.bundleUrl || '';
            const storageKey = 'kss-archive-bundle:' + (form.dataset.context || 'admin');

            let allMode = false;
            let pollTimer = null;
            let activeBundle = null;
            let autoDownloadToken = null;
            let lastStatus = null;

            function toast(message, type) {
                if (typeof window.kssToast === 'function') {
                    window.kssToast(type || 'error', type === 'success' ? 'Berhasil' : 'Gagal', message);
                } else if (type !== 'success') {
                    window.alert(message);
                }
            }

            function csrfToken() {
                return form.querySelector('input[name="_token"]')?.value || '';
            }

            function formatBytes(bytes) {
                if (!bytes) return '';
                const units = ['B', 'KB', 'MB', 'GB'];
                let value = Number(bytes);
                let unit = 0;
                while (value >= 1024 && unit < units.length - 1) { value /= 1024; unit += 1; }

                return (unit === 0 ? value : value.toFixed(1)) + ' ' + units[unit];
            }

            // ==========================================================
            // Pilih baris
            // ==========================================================

            // Baris yang disembunyikan pencarian langsung tidak boleh ikut terbundel,
            // tapi centangnya tetap disimpan supaya kembali saat pencarian dibersihkan.
            function isVisible(box) {
                const row = box.closest('tr');
                return !!row && !row.classList.contains('d-none');
            }

            function selected() {
                return checkboxes.filter(box => box.checked && isVisible(box));
            }

            function selectionTotal() {
                return allMode ? filterTotal : selected().length;
            }

            function sync() {
                const visible = checkboxes.filter(isVisible);
                const picked = selected();

                checkboxes.forEach(box => { box.disabled = !isVisible(box); });

                if (master) {
                    master.disabled = visible.length === 0;
                    master.checked = visible.length > 0 && picked.length === visible.length;
                    master.indeterminate = picked.length > 0 && picked.length < visible.length;
                }

                if (allMode && picked.length === 0) allMode = false;
                if (allFlag) allFlag.value = allMode ? '1' : '0';

                const total = selectionTotal();
                const background = total > instantLimit;

                if (countLabel) {
                    countLabel.textContent = allMode
                        ? `Semua ${filterTotal} laporan hasil filter dipilih`
                        : `${total} laporan dipilih`;
                }

                if (barHint) {
                    barHint.textContent = background
                        ? `Lebih dari ${instantLimit} laporan — disiapkan di latar`
                        : `Sampai ${instantLimit} laporan diunduh langsung`;
                }

                if (submitLabel) {
                    submitLabel.textContent = background ? 'Siapkan di latar' : 'Unduh ZIP';
                }

                if (bar) bar.hidden = total === 0;
                form.classList.toggle('is-empty', total === 0);
            }

            if (!checkboxes.length) {
                if (master) master.disabled = true;
            } else {
                master?.addEventListener('change', () => {
                    allMode = false;
                    checkboxes.filter(isVisible).forEach(box => { box.checked = master.checked; });
                    sync();
                });

                checkboxes.forEach(box => box.addEventListener('change', () => {
                    allMode = false;
                    sync();
                }));

                selectAllButton?.addEventListener('click', () => {
                    if (filterTotal > bundleLimit) {
                        toast(`Satu bundel maksimal ${bundleLimit} laporan (${filterTotal} hasil pada filter ini). Persempit filter terlebih dahulu.`);
                        return;
                    }

                    allMode = true;
                    checkboxes.forEach(box => { if (isVisible(box)) box.checked = true; });
                    sync();
                });

                clearButton?.addEventListener('click', () => {
                    allMode = false;
                    checkboxes.forEach(box => { box.checked = false; });
                    sync();
                });

                // Pencarian langsung menyembunyikan baris dengan .d-none — ikuti perubahannya
                // agar hitungan pilihan dan checkbox header tetap akurat.
                const observer = new MutationObserver(() => sync());
                checkboxes.forEach(box => {
                    const row = box.closest('tr');
                    if (row) observer.observe(row, { attributes: true, attributeFilter: ['class'] });
                });
            }

            // ==========================================================
            // Unduhan instan (satu request)
            // ==========================================================

            function filenameFromDisposition(disposition) {
                if (!disposition) return '';
                const match = disposition.match(/filename\*?=(?:UTF-8'')?["']?([^"';]+)/i);
                if (!match) return '';
                try { return decodeURIComponent(match[1]); } catch (error) { return match[1]; }
            }

            function saveAs(blob, fallbackName) {
                const objectUrl = URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = objectUrl;
                link.download = fallbackName || 'Arsip-Laporan.zip';
                document.body.appendChild(link);
                link.click();
                link.remove();
                window.setTimeout(() => URL.revokeObjectURL(objectUrl), 10000);
            }

            function setLoading(button, loading, loadingText) {
                if (!button) return;

                if (loading) {
                    button.dataset.label = button.innerHTML;
                    button.innerHTML = '<span class="btn-spinner"></span> ' + loadingText;
                    button.classList.add('is-loading');
                } else {
                    if (button.dataset.label) button.innerHTML = button.dataset.label;
                    button.classList.remove('is-loading');
                }
            }

            /**
             * Kunci baris hanya dikirim untuk pilihan manual — mode "pilih semua"
             * mengandalkan filter di server.
             */
            function fillKeys() {
                if (!keysBox) return;
                keysBox.innerHTML = '';
                if (allMode) return;

                selected().forEach(box => {
                    const field = document.createElement('input');
                    field.type = 'hidden';
                    field.name = 'keys[]';
                    field.value = box.value;
                    keysBox.appendChild(field);
                });
            }

            async function errorMessageFrom(response, fallback) {
                try {
                    const payload = await response.json();
                    if (payload?.message) return payload.message;
                } catch (error) { /* respons bukan JSON: pakai pesan bawaan */ }

                return fallback;
            }

            async function downloadInstant(total) {
                setLoading(submitButton, true, 'Menyiapkan ZIP...');

                try {
                    const response = await fetch(form.action, {
                        method: 'POST',
                        body: new FormData(form),
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin',
                    });

                    if (!response.ok) {
                        throw new Error(await errorMessageFrom(response, 'Unduhan massal gagal disiapkan. Coba lagi.'));
                    }

                    const blob = await response.blob();
                    saveAs(blob, filenameFromDisposition(response.headers.get('Content-Disposition')));
                    toast(`${total} laporan diunduh sebagai satu berkas ZIP.`, 'success');
                } catch (error) {
                    toast(error.message || 'Unduhan massal gagal disiapkan. Coba lagi.');
                } finally {
                    setLoading(submitButton, false);
                }
            }

            // ==========================================================
            // Bundel latar
            // ==========================================================

            function rememberToken(token) {
                try { window.localStorage.setItem(storageKey, token); } catch (error) { /* mode privat */ }
            }

            function forgetToken() {
                try { window.localStorage.removeItem(storageKey); } catch (error) { /* mode privat */ }
            }

            function storedToken() {
                try { return window.localStorage.getItem(storageKey); } catch (error) { return null; }
            }

            function stopPolling() {
                if (pollTimer) window.clearTimeout(pollTimer);
                pollTimer = null;
            }

            function hidePanel() {
                stopPolling();
                activeBundle = null;
                if (panel) panel.hidden = true;
            }

            function renderBundle(bundle) {
                if (!panel) return;

                activeBundle = bundle;
                panel.hidden = false;
                panel.classList.remove('archive-bundle-panel--queued', 'archive-bundle-panel--ready', 'archive-bundle-panel--failed');

                const percent = Number(bundle.percent || 0);
                if (panelFill) panelFill.style.width = percent + '%';
                if (panelProgress) panelProgress.setAttribute('aria-valuenow', String(percent));

                if (bundle.status === 'ready') {
                    panel.classList.add('archive-bundle-panel--ready');
                    if (panelTitle) panelTitle.textContent = 'Bundel siap diunduh';
                    if (panelMeta) panelMeta.textContent = [bundle.file_name, formatBytes(bundle.file_size)].filter(Boolean).join(' · ');
                    if (panelHint) {
                        panelHint.textContent = bundle.skipped > 0
                            ? `${bundle.total - bundle.skipped} dari ${bundle.total} laporan berhasil dibundel (${bundle.skipped} gagal disiapkan). Tautan berlaku 24 jam.`
                            : `${bundle.total} laporan siap dalam satu ZIP. Tautan berlaku 24 jam.`;
                    }
                    if (panelDownload) panelDownload.hidden = false;
                    if (panelDismissLabel) panelDismissLabel.textContent = 'Hapus bundel';

                    return;
                }

                if (bundle.status === 'failed') {
                    panel.classList.add('archive-bundle-panel--failed');
                    if (panelTitle) panelTitle.textContent = 'Bundel gagal disiapkan';
                    if (panelMeta) panelMeta.textContent = '';
                    if (panelHint) panelHint.textContent = bundle.error || 'Silakan coba lagi dengan pilihan lebih kecil.';
                    if (panelFill) panelFill.style.width = '100%';
                    if (panelDownload) panelDownload.hidden = true;
                    if (panelDismissLabel) panelDismissLabel.textContent = 'Tutup';

                    return;
                }

                if (panelDownload) panelDownload.hidden = true;
                if (panelDismissLabel) panelDismissLabel.textContent = 'Batalkan';

                if (bundle.status === 'queued') {
                    panel.classList.add('archive-bundle-panel--queued');
                    if (panelTitle) panelTitle.textContent = 'Bundel menunggu antrean';
                    if (panelMeta) panelMeta.textContent = bundle.total + ' laporan';
                    if (panelHint) {
                        // Tanpa queue worker yang jalan, status ini tidak akan pernah
                        // berubah — beri tahu setelah menunggu cukup lama.
                        panelHint.textContent = Number(bundle.queued_seconds || 0) > 90
                            ? 'Masih menunggu pekerja latar (queue worker). Hubungi admin server bila terus begini.'
                            : 'Antrean akan diambil pekerja latar sebentar lagi. Halaman ini boleh ditutup.';
                    }

                    return;
                }

                if (panelTitle) panelTitle.textContent = 'Menyiapkan bundel di latar';
                if (panelMeta) panelMeta.textContent = `${bundle.processed} / ${bundle.total} laporan · ${percent}%`;
                if (panelHint) panelHint.textContent = 'Halaman ini boleh ditutup — progres tersimpan di server.';
            }

            async function downloadBundle() {
                if (!activeBundle?.download_url) return;

                setLoading(panelDownload, true, 'Mengunduh...');

                try {
                    const response = await fetch(activeBundle.download_url, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin',
                    });

                    if (!response.ok) {
                        throw new Error(await errorMessageFrom(response, 'Berkas bundel tidak bisa diunduh.'));
                    }

                    const blob = await response.blob();
                    saveAs(blob, filenameFromDisposition(response.headers.get('Content-Disposition')) || activeBundle.file_name);
                } catch (error) {
                    toast(error.message || 'Berkas bundel tidak bisa diunduh.');
                } finally {
                    setLoading(panelDownload, false);
                }
            }

            function schedulePoll(bundle) {
                stopPolling();

                if (!bundle || bundle.status === 'ready' || bundle.status === 'failed') return;

                pollTimer = window.setTimeout(() => pollBundle(bundle.status_url), 2000);
            }

            async function pollBundle(statusUrl) {
                if (!statusUrl) return;

                try {
                    const response = await fetch(statusUrl, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                        credentials: 'same-origin',
                    });

                    // Bundel sudah dibersihkan/kedaluwarsa: lupakan tanpa ribut.
                    if (response.status === 404 || response.status === 403) {
                        forgetToken();
                        hidePanel();

                        return;
                    }

                    if (!response.ok) throw new Error('poll gagal');

                    const bundle = (await response.json()).bundle;
                    const previousStatus = lastStatus;
                    lastStatus = bundle.status;
                    renderBundle(bundle);

                    if (bundle.status === 'ready') {
                        // Auto-unduh hanya untuk bundel yang dimulai dari tab ini,
                        // supaya halaman yang dibuka ulang tidak mengunduh sendiri.
                        if (autoDownloadToken === bundle.token) {
                            autoDownloadToken = null;
                            downloadBundle();
                        }

                        // Hanya beri kabar saat baru selesai — bukan setiap kali
                        // halaman dibuka dengan bundel yang sudah siap.
                        if (previousStatus !== null && previousStatus !== 'ready') {
                            toast(`Bundel ${bundle.total} laporan siap diunduh.`, 'success');
                        }

                        return;
                    }

                    if (bundle.status === 'failed') {
                        forgetToken();

                        return;
                    }

                    schedulePoll(bundle);
                } catch (error) {
                    // Gangguan jaringan sesaat: coba lagi pada siklus berikutnya.
                    pollTimer = window.setTimeout(() => pollBundle(statusUrl), 5000);
                }
            }

            function adoptBundle(bundle, autoDownload) {
                rememberToken(bundle.token);
                if (autoDownload) autoDownloadToken = bundle.token;
                renderBundle(bundle);
                schedulePoll(bundle);
            }

            async function startBundle(total) {
                if (!bundleUrl) {
                    toast('Penyiapan bundel di latar belum tersedia.');
                    return;
                }

                setLoading(submitButton, true, 'Menjadwalkan...');

                try {
                    const response = await fetch(bundleUrl, {
                        method: 'POST',
                        body: new FormData(form),
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                        credentials: 'same-origin',
                    });

                    const payload = await response.json().catch(() => ({}));

                    // 409: sudah ada bundel jalan — tampilkan yang itu, jangan bikin baru.
                    if (response.status === 409 && payload.bundle) {
                        adoptBundle(payload.bundle, false);
                        toast(payload.message || 'Masih ada bundel yang sedang disiapkan.');

                        return;
                    }

                    if (!response.ok || !payload.bundle) {
                        throw new Error(payload.message || 'Bundel gagal dijadwalkan. Coba lagi.');
                    }

                    adoptBundle(payload.bundle, true);
                    toast(`${total} laporan sedang disiapkan di latar. Halaman ini boleh ditutup.`, 'success');
                } catch (error) {
                    toast(error.message || 'Bundel gagal dijadwalkan. Coba lagi.');
                } finally {
                    setLoading(submitButton, false);
                }
            }

            async function dismissBundle() {
                if (!activeBundle) {
                    hidePanel();
                    return;
                }

                const url = activeBundle.cancel_url;
                forgetToken();
                stopPolling();
                hidePanel();

                if (!url) return;

                try {
                    await fetch(url, {
                        method: 'POST',
                        body: new URLSearchParams({ _method: 'DELETE', _token: csrfToken() }),
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                        credentials: 'same-origin',
                    });
                } catch (error) { /* baris tersisa akan dibersihkan archive:prune-bundles */ }
            }

            panelDownload?.addEventListener('click', downloadBundle);
            panelDismiss?.addEventListener('click', dismissBundle);

            form.addEventListener('submit', event => {
                event.preventDefault();

                const total = selectionTotal();

                if (total === 0) {
                    toast('Pilih minimal satu laporan untuk diunduh.');
                    return;
                }

                if (total > bundleLimit) {
                    toast(`Satu bundel maksimal ${bundleLimit} laporan (${total} terpilih).`);
                    return;
                }

                fillKeys();

                const run = total > instantLimit ? startBundle(total) : downloadInstant(total);
                Promise.resolve(run).finally(() => { if (keysBox) keysBox.innerHTML = ''; });
            });

            // Lanjutkan memantau bundel yang dijadwalkan sebelum halaman ditutup.
            const resumeToken = storedToken();
            if (resumeToken && panel) {
                const template = form.dataset.bundleUrl.replace(/\/bundles$/, '/bundles/') + resumeToken;
                pollBundle(template);
            }

            sync();
        }

        if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
        else init();
    })();
</script>
