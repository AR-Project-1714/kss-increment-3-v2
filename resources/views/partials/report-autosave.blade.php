{{--
    Autosave draft laporan. Menyimpan laporan yang sedang diisi (ops/pemeliharaan/
    safety) menjadi draft secara otomatis: berkala (~60 dtk), saat menekan logout,
    dan saat menutup/meninggalkan tab. Tujuannya agar pekerjaan tidak hilang ketika
    session login habis atau tombol logout tak sengaja tertekan.

    Notifikasi: pil mungil di atas-tengah layar. Saat menyimpan tampil spinner,
    lalu berubah jadi centang + "Laporan tersimpan". Bila tombol "Simpan Sebagai
    Draft" sedang melayang (header sticky), pil muncul tepat di bawah tombol itu.

    Bergantung pada konvensi form yang sama di tiga modul:
      - <form id="mainReportForm" action="..."> (POST; PUT via _method saat edit)
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
    let dirty = false;
    let saving = false;
    let trackingReady = false;
    let saveError = false;

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

    // Tampilkan spinner dengan durasi tetap (SPINNER_MS), lalu beralih otomatis ke
    // centang + "Laporan tersimpan" — tidak tergantung lamanya request jaringan,
    // jadi spinner tak akan berputar terlalu lama.
    function showSaving() {
        if (!toast.isConnected) document.body.appendChild(toast);
        window.clearTimeout(hideTimer);
        window.clearTimeout(spinnerTimer);
        positionToast();
        toast.classList.remove('is-done');
        void toast.offsetWidth; // reset agar animasi centang bisa terputar ulang
        toast.classList.add('show');
        saveError = false;
        spinnerTimer = window.setTimeout(finishSpinner, SPINNER_MS);
    }

    function finishSpinner() {
        if (!toast.classList.contains('show')) return;
        if (saveError) { hideToast(); return; } // simpan gagal: tutup tanpa "tersimpan"
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

    async function saveDraft() {
        if (saving || suppressed() || !dirty) return;
        saving = true;
        showSaving(); // spinner tampil ~SPINNER_MS, lalu finishSpinner() menampilkan hasil
        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: buildDraftFormData(),
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                credentials: 'same-origin',
            });
            if (!response.ok) { saveError = true; return; }
            const data = await response.json().catch(() => null);
            if (data && data.update_url) {
                // Draft baru tercipta: arahkan form ke draft tsb agar simpan berikutnya
                // (autosave & kirim manual) memperbarui draft yang sama, bukan duplikat.
                form.action = data.update_url;
                ensurePutMethod();
            }
            dirty = false;
        } catch (_) {
            // Best-effort. Tandai gagal agar "Laporan tersimpan" tidak ditampilkan.
            saveError = true;
        } finally {
            saving = false;
        }
    }

    function saveDraftBeacon() {
        if (suppressed() || !dirty || !navigator.sendBeacon) return;
        try { navigator.sendBeacon(form.action, buildDraftFormData()); } catch (_) {}
    }

    // Lacak perubahan pengguna; aktif setelah render awal (prefill baris) selesai
    // supaya draft kosong tidak terbuat tanpa interaksi nyata.
    const markDirty = () => { if (trackingReady) dirty = true; };
    form.addEventListener('input', markDirty);
    form.addEventListener('change', markDirty);
    window.addEventListener('load', () => window.setTimeout(() => { trackingReady = true; }, 1200));

    window.setInterval(saveDraft, AUTOSAVE_INTERVAL_MS);

    window.addEventListener('pagehide', saveDraftBeacon);
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
