@php
    $user = auth()->user();
    $roleName = \App\Models\Role::normalize($user?->role?->name ?? null);
    $isManagerSigner = \App\Models\Role::hasManagementAccess($roleName);
    $isOpsReport = $report instanceof \App\Models\DailyReport;
    $isMaintenanceReport = $report instanceof \App\Models\MaintenanceReport;
    $isSafetyReport = $report instanceof \App\Models\SafetyReport;
    $isOperatorSigner = ! $isManagerSigner && $isOpsReport;

    $signatureUrl = function ($path): ?string {
        $normalized = ltrim(trim((string) $path), '/');

        if ($normalized === '') {
            return null;
        }

        if (preg_match('/^https?:\/\//i', $normalized)) {
            return $normalized;
        }

        if (file_exists(public_path($normalized))) {
            return asset($normalized);
        }

        if (file_exists(public_path('storage/'.$normalized))) {
            return asset('storage/'.$normalized);
        }

        if (file_exists(storage_path('app/public/'.$normalized))) {
            return asset('storage/'.$normalized);
        }

        return null;
    };

    $formatDate = function ($date): string {
        try {
            return $date ? \Carbon\Carbon::parse($date)->locale('id')->translatedFormat('d F Y') : '-';
        } catch (\Throwable) {
            return '-';
        }
    };

    $shiftMeta = function ($shift): array {
        $normalized = strtolower(trim((string) $shift));

        return match (true) {
            in_array($normalized, ['1', 'pagi', 'shift 1', 'shift pagi'], true) => ['label' => 'Shift Pagi'],
            in_array($normalized, ['2', 'sore', 'siang', 'shift 2', 'shift sore', 'shift siang'], true) => ['label' => 'Shift Sore'],
            in_array($normalized, ['3', 'malam', 'shift 3', 'shift malam'], true) => ['label' => 'Shift Malam'],
            default => ['label' => $shift ? 'Shift '.$shift : 'Shift -'],
        };
    };

    $dateForId = $report->report_date ?? $report->created_at ?? null;
    try {
        $year = $dateForId ? \Carbon\Carbon::parse($dateForId)->format('Y') : now()->format('Y');
    } catch (\Throwable) {
        $year = now()->format('Y');
    }

    $documentPrefix = $isMaintenanceReport ? 'MNT' : ($isSafetyReport ? 'K3' : 'OPS');
    $documentId = '#'.$documentPrefix.'-'.$year.'-'.str_pad((string) $report->id, 3, '0', STR_PAD_LEFT);
    $documentTitle = $isMaintenanceReport
        ? 'Laporan Harian Pemeliharaan'
        : ($isSafetyReport ? 'Laporan Harian K3' : 'Laporan Operasi Harian');
    $documentCardTitle = $isOperatorSigner ? 'Tanda tangani laporan ini?' : $documentTitle;
    $groupFrom = strtoupper((string) ($report->group_name ?? '')) ?: '-';
    $groupTo = strtoupper((string) ($report->received_by_group ?? '')) ?: '-';
    $userGroup = strtoupper((string) ($user->group ?? '')) ?: '-';
    $shiftLabel = $shiftMeta($report->shift ?? null)['label'];
    $creatorName = $report->creator->name ?? ($isMaintenanceReport ? 'Kasi Pemeliharaan' : 'Karu Safety');
    $signerName = $user->name ?? ($isManagerSigner ? 'Manajer' : 'Petugas Operasional');
    $signerSignatureUrl = $signatureUrl($user?->signature_path);
    $signLabel = $signLabel ?? 'Tanda Tangani';
    $signTitle = $signTitle ?? 'Konfirmasi Tanda Tangan';
    $reviewLabel = $isManagerSigner ? 'Tinjau' : 'Periksa Laporan';
    $confirmLabel = $isManagerSigner ? 'Konfirmasi TTD' : 'Ya, Tanda Tangani';
    $confirmIcon = $isManagerSigner ? 'fi fi-br-check-circle' : 'fi fi-rr-file-signature';

    $noteText = $signMessage ?? 'Tanda tangani laporan ini?';
    if ($isOperatorSigner) {
        $noteText = 'Laporan ini diterima grup Anda dari Regu '.$groupFrom.', lalu akan dikirim ke manajer setelah tanda tangan dikonfirmasi.';
    } elseif ($isOpsReport) {
        $noteText = 'Setelah dikonfirmasi, laporan ini akan berstatus diarsipkan dan bisa dicari pada menu Arsip Laporan.';
    } elseif ($isMaintenanceReport) {
        $noteText = 'Setelah ditandatangani, laporan pemeliharaan ini berstatus Diarsipkan dan tidak dapat diedit lagi oleh petugas.';
    } elseif ($isSafetyReport) {
        $noteText = 'Setelah ditandatangani, laporan K3 ini berstatus Diarsipkan dan tidak dapat diedit lagi oleh petugas.';
    }
@endphp

<script>
    (function () {
        try {
            document.documentElement.classList.toggle('kss-dark-theme', localStorage.getItem('theme') === 'dark');
        } catch (error) {
            // localStorage dapat diblokir pada mode privasi; gunakan tema terang.
        }
    })();
</script>

<style>
    .sign-fab {
        position: fixed;
        right: 18px;
        bottom: 18px;
        z-index: 500;
        display: inline-flex;
        align-items: center;
        height: 46px;
        max-width: 46px;
        padding: 0 13px;
        border: 1px solid rgba(5, 150, 105, .55);
        border-radius: 999px;
        background: rgba(255, 255, 255, .88);
        color: #047857;
        box-shadow: 0 7px 18px rgba(16, 185, 129, .14);
        cursor: pointer;
        overflow: hidden;
        white-space: nowrap;
        font-family: 'Poppins', Arial, sans-serif;
        transition: max-width .42s cubic-bezier(0.4, 0, 0.2, 1), color .2s ease, border-color .2s ease, box-shadow .25s ease, transform .18s ease;
    }

    .sign-fab:active { transform: scale(.96); }

    html.kss-dark-theme .sign-fab {
        border-color: rgba(110, 231, 183, .62);
        background: rgba(30, 41, 59, .88);
        color: #6EE7B7;
        box-shadow: 0 7px 20px rgba(2, 6, 23, .32);
    }

    .sign-fab .sign-fab__icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 20px;
        flex-shrink: 0;
        font-size: 16px;
        line-height: 1;
    }

    .sign-fab .sign-fab__icon i {
        position: relative;
        top: 0;
        display: inline-flex;
        line-height: 1;
    }

    .sign-fab .sign-fab__icon i::before { display: block; line-height: 1; transform: translateY(-.08em); }

    .sign-fab .sign-fab__text {
        max-width: 0;
        opacity: 0;
        margin-left: 0;
        overflow: hidden;
        font-size: 12px;
        font-weight: 600;
        transition: max-width .42s cubic-bezier(0.4, 0, 0.2, 1), opacity .28s ease, margin-left .42s cubic-bezier(0.4, 0, 0.2, 1);
    }

    @media (hover: hover) and (pointer: fine) {
        .sign-fab:hover,
        .sign-fab:focus-visible {
            max-width: 190px;
            border-color: #059669;
            background: rgba(255, 255, 255, .96);
            color: #065F46;
            box-shadow: 0 9px 24px rgba(16, 185, 129, .22);
        }

        html.kss-dark-theme .sign-fab:hover,
        html.kss-dark-theme .sign-fab:focus-visible {
            border-color: #6EE7B7;
            background: rgba(30, 41, 59, .96);
            color: #D1FAE5;
            box-shadow: 0 9px 26px rgba(2, 6, 23, .4), 0 0 18px rgba(52, 211, 153, .12);
        }

        .sign-fab:hover .sign-fab__text,
        .sign-fab:focus-visible .sign-fab__text {
            max-width: 132px;
            opacity: 1;
            margin-left: 8px;
        }
    }

    @media (max-width: 480px) {
        .sign-fab {
            right: 14px;
            bottom: 14px;
            height: 44px;
            max-width: 44px;
            padding: 0 12px;
        }
    }

    /* =====================================================================
       POP-UP TANDA TANGAN — disamakan 1:1 dengan pop-up di dashboard.
       Varian .is-manager  => acuan modal Manajer (.modal-box).
       Varian .is-operator => acuan modal Petugas Penerima (.pop-up.signed).
       ===================================================================== */
    .sign-modal-overlay {
        position: fixed;
        inset: 0;
        z-index: 10000;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 16px;
        opacity: 0;
        visibility: hidden;
        overflow-y: auto;
        transition: opacity .3s ease, visibility .3s ease;
        font-family: 'Poppins', Arial, sans-serif;
    }

    .sign-modal-overlay,
    .sign-modal-overlay * {
        font-family: 'Poppins', Arial, sans-serif;
    }

    .sign-modal-overlay.is-manager { background: rgba(0, 0, 0, .55); backdrop-filter: blur(5px); }
    .sign-modal-overlay.is-operator { background: rgba(0, 0, 0, .6); backdrop-filter: blur(4px); }

    .sign-modal-overlay.show {
        opacity: 1;
        visibility: visible;
    }

    /* ---- Panel ---- */
    .sign-modal-panel {
        position: relative;
        max-width: 100%;
        display: flex;
        flex-direction: column;
        background: #fff;
        transform: scale(.9);
    }

    .sign-modal-overlay.is-manager .sign-modal-panel {
        width: 360px;
        padding: 22px;
        gap: 18px;
        border-radius: 18px;
        box-shadow: 0 12px 36px rgba(0, 0, 0, .18);
        transform: scale(.90);
        transition: transform .3s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    .sign-modal-overlay.is-operator .sign-modal-panel {
        width: 380px;
        padding: 25px;
        gap: 20px;
        border-radius: 20px;
        color: #0F172A;
        box-shadow: 0 10px 30px rgba(0, 0, 0, .15);
        transform: scale(.9);
        transition: transform .3s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    .sign-modal-overlay.show .sign-modal-panel { transform: scale(1); }

    /* ---- Header ---- */
    .sign-modal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .sign-modal-title {
        color: #0F172A;
        font-weight: 600;
        line-height: 1.25;
    }

    .sign-modal-overlay.is-manager .sign-modal-title { font-size: 14px; }
    .sign-modal-overlay.is-operator .sign-modal-title { font-size: 16px; }

    .sign-modal-close {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 4px;
        border: none;
        border-radius: 4px;
        background: transparent;
        color: #94A3B8;
        font-size: 11px;
        line-height: 1;
        cursor: pointer;
        transition: color .2s ease;
    }

    .sign-modal-close i { position: relative; top: 1px; }
    .sign-modal-overlay.is-manager .sign-modal-close:hover { color: #D20000; }

    /* ---- Content wrapper ---- */
    .sign-modal-content {
        display: flex;
        flex-direction: column;
    }

    .sign-modal-overlay.is-manager .sign-modal-content { gap: 18px; }
    .sign-modal-overlay.is-operator .sign-modal-content { gap: 15px; }

    /* ---- Kartu dokumen ---- */
    .sign-modal-card {
        display: flex;
        align-items: center;
        background: rgba(14, 165, 233, .10);
    }

    .sign-modal-overlay.is-manager .sign-modal-card {
        gap: 12px;
        padding: 10px;
        border-radius: 8px;
        border: 1px solid rgba(14, 165, 233, .20);
    }

    .sign-modal-overlay.is-operator .sign-modal-card {
        gap: 12px;
        padding: 15px;
        border-radius: 12px;
        background: rgba(37, 99, 235, .05);
        border: 1px solid rgba(37, 99, 235, .10);
        box-shadow: 0 0 1px 0 #0EA5E9;
    }

    .sign-modal-doc-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        background: #fff;
        color: #2563EB;
    }

    .sign-modal-doc-icon i { position: relative; top: 3px; }

    .sign-modal-overlay.is-manager .sign-modal-doc-icon {
        width: 38px;
        height: 38px;
        border-radius: 6px;
        box-shadow: 0 0 0 1px rgba(14, 165, 233, .30);
        font-size: 20px;
    }

    .sign-modal-overlay.is-operator .sign-modal-doc-icon {
        width: 40px;
        height: 40px;
        border-radius: 8px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, .05);
        font-size: 20px;
    }

    .sign-modal-doc-text {
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 0;
    }

    .sign-modal-doc-title {
        color: #0F172A;
        font-weight: 600;
        line-height: 1.25;
    }

    .sign-modal-overlay.is-manager .sign-modal-doc-title { font-size: 13px; }
    .sign-modal-overlay.is-operator .sign-modal-doc-title { font-size: 14px; }

    .sign-modal-doc-sub {
        color: #334155;
        font-size: 10px;
        line-height: 1.35;
    }

    .sign-modal-overlay.is-manager .sign-modal-doc-sub { font-weight: 300; }
    .sign-modal-overlay.is-operator .sign-modal-doc-sub { font-weight: 400; }

    /* ---- Bagian tanda tangan ---- */
    .sign-modal-section {
        display: flex;
        flex-direction: column;
    }

    .sign-modal-overlay.is-manager .sign-modal-section { gap: 8px; }
    .sign-modal-overlay.is-operator .sign-modal-section { gap: 15px; }

    .sign-modal-section-label {
        color: #334155;
        font-size: 10px;
        font-weight: 600;
        letter-spacing: .5px;
        text-transform: uppercase;
    }

    .sign-modal-signature {
        display: flex;
        background: #fff;
    }

    .sign-modal-overlay.is-manager .sign-modal-signature {
        gap: 12px;
        padding: 12px;
        border-radius: 8px;
        box-shadow: 0 0 0 1px #E2E8F0;
    }

    .sign-modal-overlay.is-operator .sign-modal-signature {
        align-items: center;
        gap: 10px;
        padding: 10px;
        border-radius: 10px;
        box-shadow: 0 0 1px 0 #94A3B8;
    }

    .sign-modal-signature-img,
    .sign-modal-signature-placeholder {
        flex-shrink: 0;
        object-fit: contain;
    }

    .sign-modal-signature-placeholder {
        display: flex;
        align-items: center;
        justify-content: center;
        color: #94A3B8;
    }

    .sign-modal-overlay.is-manager .sign-modal-signature-img,
    .sign-modal-overlay.is-manager .sign-modal-signature-placeholder {
        width: 90px;
        height: 56px;
    }

    .sign-modal-overlay.is-manager .sign-modal-signature-placeholder {
        border-radius: 4px;
        background: #F8FAFC;
        box-shadow: 0 0 0 1px #E2E8F0;
        font-size: 9px;
    }

    .sign-modal-overlay.is-operator .sign-modal-signature-img,
    .sign-modal-overlay.is-operator .sign-modal-signature-placeholder {
        width: 100px;
        height: 60px;
        border-radius: 4px;
        background: #fff;
        box-shadow: 0 0 1px 0 #94A3B8;
    }

    .sign-modal-overlay.is-operator .sign-modal-signature-placeholder {
        font-size: 10px;
        font-weight: 600;
    }

    .sign-modal-signer {
        min-width: 0;
        display: flex;
        flex: 1;
        flex-direction: column;
        gap: 4px;
    }

    .sign-modal-signer-name {
        color: #0F172A;
        font-weight: 600;
        line-height: 1.25;
    }

    .sign-modal-overlay.is-manager .sign-modal-signer-name { font-size: 13px; }
    .sign-modal-overlay.is-operator .sign-modal-signer-name { font-size: 14px; }

    .sign-modal-signer-meta {
        font-size: 10px;
        line-height: 1.35;
    }

    .sign-modal-overlay.is-manager .sign-modal-signer-meta { color: #94A3B8; }
    .sign-modal-overlay.is-operator .sign-modal-signer-meta { color: #334155; }

    .sign-modal-verified {
        display: inline-flex;
        align-items: center;
        width: fit-content;
        font-weight: 600;
        color: #10B981;
        background: rgba(16, 185, 129, .10);
    }

    .sign-modal-verified i { position: relative; top: 1px; }

    .sign-modal-overlay.is-manager .sign-modal-verified {
        gap: 5px;
        padding: 2px 8px;
        border-radius: 50px;
        font-size: 9px;
    }

    .sign-modal-overlay.is-manager .sign-modal-verified i { font-size: 10px; }

    .sign-modal-overlay.is-operator .sign-modal-verified {
        gap: 6px;
        padding: 2px 6px;
        border-radius: 10px;
        background: rgba(16, 183, 127, .10);
        font-size: 10px;
    }

    .sign-modal-overlay.is-operator .sign-modal-verified i { font-size: 12px; }

    /* ---- Catatan ---- */
    .sign-modal-note {
        display: flex;
        align-items: flex-start;
        gap: 8px;
        margin: 0;
        color: #94A3B8;
    }

    .sign-modal-note i { position: relative; top: 3px; flex-shrink: 0; }

    .sign-modal-overlay.is-manager .sign-modal-note { font-size: 9px; line-height: 1.4; }
    .sign-modal-overlay.is-manager .sign-modal-note i { font-size: 11px; }
    .sign-modal-overlay.is-operator .sign-modal-note { font-size: 12px; line-height: 1.6; }
    .sign-modal-overlay.is-operator .sign-modal-note i { font-size: 12px; }

    /* ---- Footer & tombol ---- */
    .sign-modal-footer {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: flex-end;
        gap: 10px;
    }

    .sign-modal-footer .sign-modal-form { margin: 0; }

    .sign-modal-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        border: none;
        font-weight: 600;
        line-height: 1.1;
        text-decoration: none;
        cursor: pointer;
        transition: all .2s ease;
    }

    .sign-modal-btn i { position: relative; top: 1px; }

    .sign-modal-overlay.is-manager .sign-modal-btn { padding: 8px 16px; border-radius: 8px; font-size: 12px; }
    .sign-modal-overlay.is-operator .sign-modal-btn { padding: 10px 20px; border-radius: 10px; font-size: 13px; }

    .sign-modal-btn--cancel,
    .sign-modal-btn--review-neutral {
        color: #0F172A;
    }

    .sign-modal-overlay.is-manager .sign-modal-btn--cancel,
    .sign-modal-overlay.is-manager .sign-modal-btn--review-neutral {
        background: transparent;
    }

    .sign-modal-overlay.is-manager .sign-modal-btn--cancel:hover,
    .sign-modal-overlay.is-manager .sign-modal-btn--review-neutral:hover { background: rgba(51, 65, 85, .10); }

    .sign-modal-overlay.is-operator .sign-modal-btn--cancel {
        background: rgba(51, 65, 85, .10);
    }

    .sign-modal-btn--review-orange {
        background: #F7931E;
        color: #fff;
    }

    .sign-modal-btn--review-orange:hover { background: #E67E00; transform: translateY(-2px); }

    .sign-modal-btn--confirm {
        background: #10B981;
        color: #fff;
    }

    .sign-modal-btn--confirm:hover { background: #0F9A6B; transform: translateY(-2px); }
    .sign-modal-overlay.is-operator .sign-modal-btn--confirm i { top: 2px; }

    .sign-modal-btn:disabled,
    .sign-modal-btn.is-loading {
        opacity: .86;
        cursor: progress;
        pointer-events: none;
    }

    .sign-spinner {
        width: 16px;
        height: 16px;
        border: 2px solid rgba(255,255,255,.45);
        border-top-color: #fff;
        border-radius: 999px;
        animation: signSpin .7s linear infinite;
    }

    @keyframes signSpin { to { transform: rotate(360deg); } }

    .sign-sheet-handle {
        display: none;
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 30px;
        align-items: center;
        justify-content: center;
        touch-action: none;
        z-index: 2;
    }

    .sign-sheet-handle::after {
        content: '';
        width: 42px;
        height: 4px;
        border-radius: 999px;
        background: #CBD5E1;
    }

    .sign-flash {
        position: fixed;
        top: 18px;
        left: 50%;
        z-index: 10001;
        display: flex;
        align-items: center;
        gap: 10px;
        max-width: calc(100vw - 32px);
        padding: 12px 18px;
        border-radius: 10px;
        font-size: 13px;
        font-weight: 600;
        font-family: 'Poppins', Arial, sans-serif;
        box-shadow: 0 8px 20px rgba(0,0,0,.15);
        opacity: 0;
        pointer-events: none;
        transform: translateX(-50%) translateY(-12px);
        transition: opacity .25s ease, transform .25s ease;
    }

    .sign-flash.show {
        opacity: 1;
        transform: translateX(-50%) translateY(0);
    }

    .sign-flash.success {
        background: #ECFDF5;
        color: #0F9A6B;
        border: 1px solid #A7F3D0;
    }

    .sign-flash.error {
        background: #FEF2F2;
        color: #B80000;
        border: 1px solid #FECACA;
    }

    /* ---- Mobile: bottom sheet (≤768px), sama dengan dashboard ---- */
    @media (max-width: 768px) {
        .sign-modal-overlay {
            align-items: flex-end;
            padding: 0;
        }

        .sign-modal-overlay.is-manager .sign-modal-panel,
        .sign-modal-overlay.is-operator .sign-modal-panel {
            width: 100%;
            max-width: 100%;
            max-height: min(86vh, 680px);
            margin: 0;
            border-radius: 20px 20px 0 0;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
            transform: translateY(100%);
            transition: transform .38s cubic-bezier(0.32, 0.72, 0, 1);
        }

        .sign-modal-overlay.is-manager .sign-modal-panel {
            padding: 30px 22px max(18px, env(safe-area-inset-bottom, 0px));
        }

        .sign-modal-overlay.is-operator .sign-modal-panel {
            padding: 30px 20px max(18px, env(safe-area-inset-bottom, 0px));
        }

        .sign-modal-overlay.show .sign-modal-panel { transform: translateY(0); }
        .sign-sheet-handle { display: flex; }

        .sign-modal-footer {
            flex-direction: column-reverse;
            flex-wrap: nowrap;
            align-items: stretch;
            justify-content: flex-start;
        }

        .sign-modal-footer > * { width: 100%; margin: 0; }
        .sign-modal-btn { width: 100%; }
    }

    @media print {
        .sign-fab,
        .sign-modal-overlay,
        .sign-flash {
            display: none !important;
        }
    }
</style>

@if (session('success') || session('error'))
    <div class="sign-flash {{ session('success') ? 'success' : 'error' }}" id="signFlash">
        <i class="fi {{ session('success') ? 'fi-rr-check-circle' : 'fi-rr-cross-circle' }}"></i>
        <span>{{ session('success') ?? session('error') }}</span>
    </div>
@endif

<button type="button" class="sign-fab" id="signFabBtn" aria-label="{{ $signLabel }}">
    <span class="sign-fab__icon"><i class="fi fi-rr-file-signature"></i></span>
    <span class="sign-fab__text">{{ $signLabel }}</span>
</button>

<div class="sign-modal-overlay {{ $isOperatorSigner ? 'is-operator' : 'is-manager' }}" id="signModalOverlay">
    <div class="sign-modal-panel" role="dialog" aria-modal="true" aria-labelledby="signModalTitle">
        <div class="sign-sheet-handle" aria-hidden="true"></div>
        <div class="sign-modal-header">
            <span class="sign-modal-title" id="signModalTitle">{{ $signTitle }}</span>
            <button type="button" class="sign-modal-close" id="signModalClose" aria-label="Tutup">
                <i class="fi fi-br-cross"></i>
            </button>
        </div>

        <div class="sign-modal-content">
            <div class="sign-modal-card">
                <span class="sign-modal-doc-icon"><i class="fi fi-rr-file-signature"></i></span>
                <div class="sign-modal-doc-text">
                    <div class="sign-modal-doc-title">{{ $documentCardTitle }}</div>
                    <div class="sign-modal-doc-sub">
                        @if ($isOperatorSigner)
                            ID: {{ $documentId }} - {{ $shiftLabel }}
                        @elseif ($isOpsReport)
                            {{ $documentId }} <span aria-hidden="true">&bull;</span> Regu {{ $groupFrom }} ke Regu {{ $groupTo }}
                        @elseif ($isMaintenanceReport)
                            {{ $documentId }} <span aria-hidden="true">&bull;</span> {{ $report->day_name ? $report->day_name.', ' : '' }}{{ $formatDate($report->report_date ?? null) }}
                        @else
                            {{ $documentId }} <span aria-hidden="true">&bull;</span> {{ $formatDate($report->report_date ?? null) }}
                        @endif
                    </div>
                </div>
            </div>

            <div class="sign-modal-section">
                @unless ($isOperatorSigner)
                    <span class="sign-modal-section-label">Tanda Tangan Digital</span>
                @endunless

                <div class="sign-modal-signature">
                    @if ($signerSignatureUrl)
                        <img src="{{ $signerSignatureUrl }}" class="sign-modal-signature-img" alt="Tanda tangan {{ $signerName }}">
                    @else
                        <div class="sign-modal-signature-placeholder">[TTD]</div>
                    @endif

                    <div class="sign-modal-signer">
                        <div class="sign-modal-signer-name">{{ $signerName }}</div>
                        <div class="sign-modal-signer-meta">
                            @if ($isOperatorSigner)
                                Regu {{ $userGroup }} <span aria-hidden="true">&bull;</span> {{ $shiftLabel }}
                            @elseif ($isOpsReport)
                                {{ $shiftLabel }} <span aria-hidden="true">&bull;</span> {{ $formatDate($report->report_date ?? null) }}
                            @else
                                Pembuat: {{ $creatorName }}
                            @endif
                        </div>
                        <div class="sign-modal-verified">
                            <i class="fi fi-rr-shield-check"></i>
                            Terverifikasi Sistem
                        </div>
                    </div>
                </div>

                <p class="sign-modal-note">
                    <i class="fi fi-rr-info"></i>
                    <span>{{ $noteText }}</span>
                </p>
            </div>
        </div>

        <div class="sign-modal-footer">
            <button type="button" class="sign-modal-btn sign-modal-btn--cancel" id="signModalCancel">Batal</button>
            <button type="button" class="sign-modal-btn {{ $isOperatorSigner ? 'sign-modal-btn--review-orange' : 'sign-modal-btn--review-neutral' }}" id="signModalReview">
                @if ($isOperatorSigner)
                    <i class="fi fi-rr-eye"></i>
                @endif
                {{ $reviewLabel }}
            </button>
            <form method="POST" action="{{ $signAction }}" class="sign-modal-form" id="signModalForm">
                @csrf
                <button type="submit" class="sign-modal-btn sign-modal-btn--confirm" id="signModalConfirm">
                    <i class="{{ $confirmIcon }}"></i>
                    {{ $confirmLabel }}
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    (function () {
        var fabBtn = document.getElementById('signFabBtn');
        var overlay = document.getElementById('signModalOverlay');
        if (!fabBtn || !overlay) return;

        var panel = overlay.querySelector('.sign-modal-panel');
        var closeBtn = document.getElementById('signModalClose');
        var cancelBtn = document.getElementById('signModalCancel');
        var reviewBtn = document.getElementById('signModalReview');
        var form = document.getElementById('signModalForm');
        var confirmBtn = document.getElementById('signModalConfirm');
        var handle = overlay.querySelector('.sign-sheet-handle');

        function openModal() { overlay.classList.add('show'); }
        function closeModal() { overlay.classList.remove('show'); }

        fabBtn.addEventListener('click', openModal);
        if (closeBtn) closeBtn.addEventListener('click', closeModal);
        if (cancelBtn) cancelBtn.addEventListener('click', closeModal);
        if (reviewBtn) reviewBtn.addEventListener('click', closeModal);

        overlay.addEventListener('click', function (event) {
            if (event.target === overlay) closeModal();
        });

        if (panel) {
            panel.addEventListener('click', function (event) {
                event.stopPropagation();
            });
        }

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') closeModal();
        });

        if (form && confirmBtn) {
            form.addEventListener('submit', function () {
                if (confirmBtn.dataset.loading === 'true') return;
                confirmBtn.dataset.loading = 'true';
                confirmBtn.classList.add('is-loading');
                confirmBtn.disabled = true;
                confirmBtn.innerHTML = '<span class="sign-spinner"></span> Memproses...';
            });
        }

        var startY = 0;
        var startTime = 0;
        var dragging = false;
        var CLOSE_THRESHOLD_PX = 110;
        var CLOSE_VELOCITY = 0.55;

        function isMobile() {
            return window.matchMedia('(max-width: 768px)').matches;
        }

        if (handle && panel) {
            handle.addEventListener('touchstart', function (event) {
                if (!isMobile() || !event.touches.length) return;
                startY = event.touches[0].clientY;
                startTime = Date.now();
                dragging = true;
                panel.style.transition = 'none';
            }, { passive: true });

            handle.addEventListener('touchmove', function (event) {
                if (!dragging || !event.touches.length) return;
                var deltaY = Math.max(0, event.touches[0].clientY - startY);
                panel.style.transform = 'translateY(' + deltaY + 'px)';
                event.preventDefault();
            }, { passive: false });

            function endDrag(event) {
                if (!dragging) return;
                dragging = false;
                panel.style.transition = '';

                var touch = event.changedTouches[0];
                var deltaY = touch ? Math.max(0, touch.clientY - startY) : 0;
                var elapsed = Math.max(1, Date.now() - startTime);
                var velocity = deltaY / elapsed;

                panel.style.transform = '';

                if (deltaY > CLOSE_THRESHOLD_PX || velocity > CLOSE_VELOCITY) closeModal();
            }

            handle.addEventListener('touchend', endDrag);
            handle.addEventListener('touchcancel', endDrag);
        }

        var flash = document.getElementById('signFlash');
        if (flash) {
            requestAnimationFrame(function () {
                flash.classList.add('show');
                setTimeout(function () { flash.classList.remove('show'); }, 4200);
            });
        }
    })();
</script>
