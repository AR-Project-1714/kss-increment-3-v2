{{--
    Autosave draft laporan. Menyimpan laporan yang sedang diisi (ops/pemeliharaan/
    safety) menjadi draft secara otomatis: berkala (~60 dtk), saat menekan logout,
    dan saat menutup/meninggalkan tab. Tujuannya agar pekerjaan tidak hilang ketika
    session login habis atau tombol logout tak sengaja tertekan.

    Fallback offline: bila jaringan putus saat autosave, isian form disimpan ke
    localStorage ("Tersimpan offline") dan otomatis disinkronkan ke server ketika
    koneksi kembali atau saat halaman form berikutnya dibuka.

    Notifikasi: pil mungil di atas-tengah layar. Saat menyimpan tampil spinner,
    lalu berubah jadi centang + "Laporan tersimpan". Bila tombol "Simpan Sebagai
    Draft" sedang melayang (header sticky), pil muncul tepat di bawah tombol itu.

    Anti-duplikat: draft direservasi server begitu form dibuka, jadi form laporan
    baru sekalipun sudah menembak endpoint update (PUT) sejak keystroke pertama.
    Ini wajib karena penyimpanan lewat sendBeacon tidak bisa membaca response —
    ia takkan pernah tahu ID draft yang baru dibuat. Kalau form masih menembak
    endpoint store, tiap kali tab disembunyikan akan lahir draft baru.

    Bergantung pada konvensi form yang sama di tiga modul:
      - <form id="mainReportForm" action="..."> (POST; PUT via _method)
      - data-discard-blank-url pada form baru: dipakai membuang baris reservasi
        bila form ditinggal tanpa diisi sama sekali
      - opsional window.__reportSyncPayload() untuk menyegarkan hidden form_payload (ops)
      - submitAs() di tiap form menyetel window.__reportAutosaveSuppress = true saat
        pengiriman manual, agar autosave tidak menimpa/menurunkan status laporan.
--}}
@push('scripts')
<style>
    #reportAutosaveToast {
        position: fixed; left: 0; right: 0; top: 18px;
        z-index: 10050;
        display: flex; justify-content: center;
        pointer-events: none;
        font-family: 'Poppins', sans-serif;
    }
    #reportAutosaveToast .rat-pill {
        display: inline-flex; align-items: center; gap: 9px;
        max-width: calc(100vw - 28px);
        padding: 9px; /* kotak-bulat saat fase spinner */
        border-radius: 999px;
        background: var(--white, #ffffff);
        color: var(--dark-main, #1f2937);
        font-size: 12px; font-weight: 600; line-height: 1; letter-spacing: .1px;
        border: 1px solid var(--smooth-border, rgba(15, 23, 42, .08));
        box-shadow: 0 10px 30px rgba(15, 23, 42, .14), 0 2px 6px rgba(15, 23, 42, .06);
        opacity: 0; transform: translateY(-12px) scale(.96);
        transition: opacity .3s cubic-bezier(.22, 1, .36, 1),
                    transform .3s cubic-bezier(.22, 1, .36, 1),
                    padding .3s cubic-bezier(.22, 1, .36, 1);
        will-change: opacity, transform;
    }
    #reportAutosaveToast.show .rat-pill { opacity: 1; transform: translateY(0) scale(1); }
    #reportAutosaveToast.is-done .rat-pill { padding: 8px 15px 8px 11px; }

    #reportAutosaveToast .rat-ico {
        width: 16px; height: 16px; flex: none;
        display: inline-flex; align-items: center; justify-content: center;
    }
    #reportAutosaveToast .rat-spin {
        width: 14px; height: 14px; border-radius: 50%;
        border: 2px solid var(--blue-main-25, rgba(37, 99, 235, .22));
        border-top-color: var(--blue-main, #2563eb);
        animation: ratSpin .6s linear infinite;
    }
    @keyframes ratSpin { to { transform: rotate(360deg); } }

    #reportAutosaveToast .rat-check { width: 16px; height: 16px; display: none; }
    #reportAutosaveToast .rat-check svg { width: 100%; height: 100%; display: block; }
    #reportAutosaveToast .rat-check circle,
    #reportAutosaveToast .rat-check path {
        stroke: var(--success, #16a34a); stroke-width: 2.4; fill: none;
        stroke-linecap: round; stroke-linejoin: round;
    }
    #reportAutosaveToast .rat-check circle { stroke-dasharray: 64; stroke-dashoffset: 64; }
    #reportAutosaveToast .rat-check path { stroke-dasharray: 24; stroke-dashoffset: 24; }

    #reportAutosaveToast .rat-label { display: none; white-space: nowrap; }

    /* Fase selesai: sembunyikan spinner, gambar centang, munculkan teks */
    #reportAutosaveToast.is-done .rat-spin { display: none; }
    #reportAutosaveToast.is-done .rat-check { display: block; }
    #reportAutosaveToast.is-done .rat-check circle { animation: ratDraw .4s ease forwards; }
    #reportAutosaveToast.is-done .rat-check path { animation: ratDraw .3s .22s ease forwards; }
    #reportAutosaveToast.is-done .rat-label { display: inline-block; animation: ratLabelIn .26s ease both; }
    @keyframes ratDraw { to { stroke-dashoffset: 0; } }
    @keyframes ratLabelIn { from { opacity: 0; transform: translateX(-4px); } to { opacity: 1; transform: none; } }

    body.dark-mode #reportAutosaveToast .rat-pill {
        background: #1E293B; color: #F1F5F9;
        border-color: rgba(255, 255, 255, .08);
        box-shadow: 0 12px 30px rgba(0, 0, 0, .45);
    }

    @media (prefers-reduced-motion: reduce) {
        #reportAutosaveToast .rat-pill { transition: opacity .2s ease; transform: none; }
        #reportAutosaveToast .rat-spin { animation-duration: 1s; }
        #reportAutosaveToast.is-done .rat-check circle,
        #reportAutosaveToast.is-done .rat-check path { animation: none; stroke-dashoffset: 0; }
        #reportAutosaveToast.is-done .rat-label { animation: none; }
    }
</style>
<script>
(function () {
    const form = document.getElementById('mainReportForm');
    if (!form) return;

    const AUTOSAVE_INTERVAL_MS = 30000;
    const SPINNER_MS = 1500;      // spinner berputar tetap ~1,5 dtk (target 1-2 dtk)
    const DONE_VISIBLE_MS = 2500; // teks "Laporan tersimpan" tampil ~2,5 dtk (target 2-3 dtk)
    const SAVE_TIMEOUT_MS = 15000;
    const OFFLINE_KEY_PREFIX = 'kssOfflineDraft:';
    const OFFLINE_TTL_MS = 3 * 24 * 60 * 60 * 1000; // selaras masa simpan draft (3 hari)
    let dirty = false;
    let dirtyRevision = 0;  // penanda "isian berubah lagi" saat request berjalan
    let everSaved = false;  // form pernah benar-benar dikirim ke server?
    let everTouched = false; // pengguna pernah mengetik/memilih sesuatu?
    let saving = false;
    let trackingReady = false;
    let saveError = false;
    let savedOffline = false;

    const toast = document.createElement('div');
    toast.id = 'reportAutosaveToast';
    toast.setAttribute('aria-live', 'polite');
    toast.innerHTML =
        '<div class="rat-pill">' +
            '<span class="rat-ico">' +
                '<span class="rat-spin"></span>' +
                '<span class="rat-check"><svg viewBox="0 0 24 24" aria-hidden="true">' +
                    '<circle cx="12" cy="12" r="9"></circle><path d="M7.5 12.5l3 3 6-6.5"></path>' +
                '</svg></span>' +
            '</span>' +
            '<span class="rat-label">Laporan tersimpan</span>' +
        '</div>';
    const label = toast.querySelector('.rat-label');

    let hideTimer = null;
    let spinnerTimer = null;
    let spinnerElapsed = false;

    // Muncul di atas-tengah; jika tombol "Simpan Sebagai Draft" sedang melayang
    // (header sticky), letakkan pil tepat di bawah tombol tersebut.
    function positionToast() {
        let top = 18;
        const floatBtn = document.querySelector('.content-header.is-sticky #btnSaveDraft, .content-header.is-sticky .btn-draft-save');
        if (floatBtn) {
            const rect = floatBtn.getBoundingClientRect();
            if (rect.width > 0 && rect.bottom > 0) top = Math.round(rect.bottom + 10);
        }
        toast.style.top = top + 'px';
    }

    // Spinner tampil sekurangnya SPINNER_MS, tetapi centang baru muncul setelah
    // server atau fallback offline benar-benar menerima snapshot.
    function showSaving() {
        if (!toast.isConnected) document.body.appendChild(toast);
        window.clearTimeout(hideTimer);
        window.clearTimeout(spinnerTimer);
        positionToast();
        toast.classList.remove('is-done');
        void toast.offsetWidth; // reset agar animasi centang bisa terputar ulang
        toast.classList.add('show');
        saveError = false;
        savedOffline = false;
        spinnerElapsed = false;
        spinnerTimer = window.setTimeout(() => {
            spinnerElapsed = true;
            finishSpinner();
        }, SPINNER_MS);
    }

    function finishSpinner() {
        if (!toast.classList.contains('show')) return;
        // Jangan pernah menyatakan "tersimpan" sebelum server/offline fallback
        // benar-benar selesai memproses snapshot terbaru.
        if (saving || !spinnerElapsed) return;
        if (saveError) { hideToast(); return; } // simpan gagal: tutup tanpa "tersimpan"
        if (label) label.textContent = savedOffline ? 'Tersimpan offline, sinkron saat online' : 'Laporan tersimpan';
        toast.classList.add('is-done');
        window.clearTimeout(hideTimer);
        hideTimer = window.setTimeout(hideToast, DONE_VISIBLE_MS);
    }

    function hideToast() { toast.classList.remove('show'); }

    function ensurePutMethod() {
        if (form.querySelector('input[name="_method"]')) return;
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = '_method';
        input.value = 'PUT';
        form.appendChild(input);
    }

    function buildDraftFormData() {
        // Segarkan snapshot payload (khusus form ops); modul lain membaca named input.
        if (typeof window.__reportSyncPayload === 'function') {
            try { window.__reportSyncPayload(); } catch (_) {}
        }
        const data = new FormData(form);
        data.set('status', 'draft');
        data.set('autosave', '1');
        return data;
    }

    // Jangan autosave saat form sedang dikirim manual (Simpan Draft / Kirim) agar
    // tidak ada balapan request yang menurunkan status laporan.
    function suppressed() { return window.__reportAutosaveSuppress === true; }

    // ===== Fallback offline (localStorage) =====

    function offlineEntries(data) {
        const entries = [];
        data.forEach((value, name) => {
            if (typeof value === 'string') entries.push([name, value]);
        });
        return entries;
    }

    function storeOfflineDraft() {
        try {
            window.localStorage.setItem(OFFLINE_KEY_PREFIX + form.action, JSON.stringify({
                action: form.action,
                savedAt: Date.now(),
                entries: offlineEntries(buildDraftFormData()),
            }));
            return true;
        } catch (_) {
            return false;
        }
    }

    // Kirim ulang seluruh draft offline yang tertunda. Snapshot hanya dihapus
    // setelah server benar-benar menerimanya; kegagalan jaringan, autentikasi,
    // atau validasi dipertahankan sampai TTL agar isian tetap dapat dipulihkan.
    async function syncOfflineDrafts() {
        if (!navigator.onLine) return;

        let keys = [];
        try {
            keys = Object.keys(window.localStorage).filter((key) => key.indexOf(OFFLINE_KEY_PREFIX) === 0);
        } catch (_) { return; }

        let synced = 0;

        for (const key of keys) {
            let record = null;
            try { record = JSON.parse(window.localStorage.getItem(key)); } catch (_) {}

            const expired = !record || !record.action || !Array.isArray(record.entries)
                || (Date.now() - (record.savedAt || 0)) > OFFLINE_TTL_MS;

            if (expired) {
                try { window.localStorage.removeItem(key); } catch (_) {}
                continue;
            }

            const data = new FormData();
            record.entries.forEach(([name, value]) => data.append(name, value));
            data.set('status', 'draft');
            data.set('autosave', '1');

            // Token CSRF di record bisa kedaluwarsa; pakai token halaman ini.
            const tokenInput = form.querySelector('input[name="_token"]');
            if (tokenInput) data.set('_token', tokenInput.value);

            try {
                let response = await fetch(record.action, {
                    method: 'POST',
                    body: data,
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    credentials: 'same-origin',
                });

                // Draft tujuan bisa sudah kedaluwarsa. Simpan sebagai draft baru
                // alih-alih membuang snapshot offline saat endpoint update 404.
                if (response.status === 404 && form.dataset.storeUrl) {
                    data.delete('_method');
                    response = await fetch(form.dataset.storeUrl, {
                        method: 'POST',
                        body: data,
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                        credentials: 'same-origin',
                    });
                }

                // Hanya hapus snapshot setelah server benar-benar menerimanya.
                // Respons validasi/auth dipertahankan sampai TTL agar data tidak
                // hilang hanya karena sesi atau isian sementara bermasalah.
                if (response.ok) {
                    try { window.localStorage.removeItem(key); } catch (_) {}
                    synced++;
                }
            } catch (_) {
                // Masih offline / server tak terjangkau — coba lagi nanti.
            }
        }

        if (synced > 0 && typeof window.kssToast === 'function') {
            window.kssToast('success', 'Draft offline tersinkron', 'Isian yang tersimpan offline sudah dikirim ke server. Periksa tab Draft.');
        }
    }

    function postDraft(signal = undefined) {
        return fetch(form.action, {
            method: 'POST',
            body: buildDraftFormData(),
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            credentials: 'same-origin',
            signal,
        });
    }

    // Baris draft yang dituju sudah tidak ada (mis. kedaluwarsa atau terlanjur
    // dibuang sebagai draft kosong). Isian di layar tidak boleh ikut hilang:
    // kembali ke endpoint store agar tersimpan sebagai laporan baru.
    function recoverToStoreEndpoint() {
        const storeUrl = form.dataset.storeUrl;
        if (!storeUrl || form.action === storeUrl) return false;
        form.action = storeUrl;
        form.querySelector('input[name="_method"]')?.remove();
        return true;
    }

    async function saveDraft() {
        if (saving || suppressed() || !dirty) return;
        saving = true;
        showSaving(); // spinner tampil ~SPINNER_MS, lalu finishSpinner() menampilkan hasil
        const actionUsed = form.action;
        const controller = typeof AbortController === 'function' ? new AbortController() : null;
        const requestTimeout = controller
            ? window.setTimeout(() => controller.abort(), SAVE_TIMEOUT_MS)
            : null;
        // Perubahan yang diketik SELAMA request berlangsung tidak boleh ikut
        // dianggap tersimpan; hanya bersihkan dirty bila tak ada ketikan baru.
        const revision = ++dirtyRevision;
        try {
            let response = await postDraft(controller?.signal);
            if (response.status === 404 && recoverToStoreEndpoint()) {
                response = await postDraft(controller?.signal);
            }
            if (!response.ok) { saveError = true; return; }
            everSaved = true;
            const data = await response.json().catch(() => null);
            if (data && data.update_url) {
                // Jaring pengaman bila form masih menembak endpoint store (mis.
                // halaman lama yang ter-cache): pindahkan ke draft yang baru
                // dibuat agar simpanan berikutnya menimpa, bukan menduplikat.
                form.action = data.update_url;
                ensurePutMethod();
            }
            if (revision === dirtyRevision) dirty = false;
            // Server sudah menerima data terbaru — draft offline lama tak diperlukan.
            try {
                window.localStorage.removeItem(OFFLINE_KEY_PREFIX + actionUsed);
                window.localStorage.removeItem(OFFLINE_KEY_PREFIX + form.action);
            } catch (_) {}
        } catch (_) {
            // Jaringan gagal: amankan isian ke localStorage agar tidak hilang.
            if (storeOfflineDraft()) {
                savedOffline = true;
                if (revision === dirtyRevision) dirty = false;
            } else {
                saveError = true;
            }
        } finally {
            if (requestTimeout !== null) window.clearTimeout(requestTimeout);
            saving = false;
            finishSpinner();
        }
    }

    // Penyimpanan saat tab disembunyikan/ditutup. sendBeacon tidak bisa membaca
    // response, jadi ia tak pernah tahu ID draft baru — makanya form laporan baru
    // pun sudah menembak endpoint update sejak awal (draft direservasi server
    // saat form dibuka). Dengan begitu beacon berkali-kali tetap menimpa satu
    // baris yang sama, bukan menumpuk draft.
    function saveDraftBeacon() {
        if (suppressed() || !dirty) return;
        if (!navigator.onLine) { storeOfflineDraft(); return; }
        if (!navigator.sendBeacon) return;
        try {
            navigator.sendBeacon(form.action, buildDraftFormData());
            everSaved = true;
        } catch (_) {}
    }

    // Form baru yang dibuka lalu ditinggal tanpa diisi: buang baris reservasinya
    // supaya tab Draft tidak penuh laporan kosong. Server tetap memeriksa ulang
    // dan menolak menghapus draft yang ternyata sudah ada isinya.
    //
    // HANYA dipanggil saat halaman benar-benar ditinggalkan. Sempat dipasang di
    // visibilitychange juga, dan itu keliru: pindah tab sebentar (buka WhatsApp,
    // cek data lain) menghapus draft yang formnya masih terbuka, sehingga
    // simpanan berikutnya menembak ID yang sudah tiada.
    function discardBlankBeacon() {
        const url = form.dataset.discardBlankUrl;
        if (!url || everSaved || everTouched || !navigator.sendBeacon) return;
        const data = new FormData();
        const tokenInput = form.querySelector('input[name="_token"]');
        if (tokenInput) data.set('_token', tokenInput.value);
        try { navigator.sendBeacon(url, data); } catch (_) {}
    }

    // Lacak perubahan pengguna; aktif setelah render awal (prefill baris) selesai
    // supaya draft kosong tidak terbuat tanpa interaksi nyata.
    //
    // everTouched dipisah dari dirty dan hanya dinaikkan oleh event asli dari
    // pengguna (isTrusted) — bukan event sintetis saat prefill. Ini yang menjaga
    // agar draft yang sudah diketik tidak ikut dibuang lewat discardBlankBeacon,
    // termasuk ketikan pada 1,2 detik pertama sebelum trackingReady menyala.
    const markDirty = (event) => {
        if (event.isTrusted) {
            // Ketikan nyata harus langsung aman, termasuk bila terjadi sebelum
            // jeda 1,2 detik untuk mengabaikan event prefill sintetis berakhir.
            everTouched = true;
            dirty = true;
            return;
        }

        if (trackingReady) dirty = true;
    };
    form.addEventListener('input', markDirty);
    form.addEventListener('change', markDirty);
    window.addEventListener('load', () => window.setTimeout(() => { trackingReady = true; }, 1200));

    window.setInterval(saveDraft, AUTOSAVE_INTERVAL_MS);

    // Sinkronkan draft offline yang tertunda: saat halaman dibuka & saat online kembali.
    window.addEventListener('load', () => window.setTimeout(syncOfflineDrafts, 2500));
    window.addEventListener('online', () => window.setTimeout(syncOfflineDrafts, 1500));

    // "Batalkan" = pernyataan tegas bahwa laporan ini tidak jadi dibuat, jadi
    // reservasinya langsung dibuang tanpa menunggu pagehide (yang bisa terlewat
    // bila halaman masuk bfcache).
    document.addEventListener('click', (event) => {
        if (event.target.closest?.('.btn-form.cancel')) discardBlankBeacon();
    });

    window.addEventListener('pagehide', (event) => {
        saveDraftBeacon();
        // event.persisted = halaman masuk bfcache dan masih mungkin dibuka lagi;
        // hanya buang reservasi saat halaman betul-betul ditinggalkan.
        if (! event.persisted) discardBlankBeacon();
    });
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'hidden') saveDraftBeacon();
    });

    // Logout (sengaja / tak sengaja): simpan draft dulu, baru lanjut logout.
    document.querySelectorAll('form[action$="/logout"]').forEach((logoutForm) => {
        let handled = false;
        logoutForm.addEventListener('submit', async (event) => {
            if (handled || suppressed() || !dirty) return;
            event.preventDefault();
            handled = true;
            await Promise.race([
                saveDraft(),
                new Promise((resolve) => window.setTimeout(resolve, 2000)),
            ]);
            window.__reportAutosaveSuppress = true; // cegah beacon ganda saat unload
            logoutForm.submit();
        });
    });
})();
</script>
@endpush
