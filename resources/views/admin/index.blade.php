@extends('admin.layouts.app')

@section('title', 'KSS Admin - Dashboard Sistem')
@section('active', 'dashboard')

@push('styles')
<style>
    /* =============================================
       DASHBOARD PANELS — TWO-COLUMN GRID
       ============================================= */
    .dashboard-panels {
        display: grid;
        grid-template-columns: 3fr 2fr;
        gap: 20px;
        align-items: start;
    }

    /* =============================================
       PANEL CARD
       ============================================= */
    .panel-card {
        background-color: var(--white);
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(37,99,235,0.07);
        padding: 20px;
        transition: background-color 0.3s ease;
    }

    .panel-card__header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        margin-bottom: 16px;
        gap: 10px;
    }

    .panel-card__header-left {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .panel-card__icon {
        width: 40px;
        height: 40px;
        background-color: var(--blue-main);
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 18px;
        flex-shrink: 0;
    }

    .panel-card__icon i { position: relative; top: 2px; }

    .panel-card__title {
        font-size: 14px;
        font-weight: 700;
        color: var(--black);
        line-height: 1.2;
    }

    .panel-card__subtitle {
        font-size: 11px;
        font-weight: 400;
        color: var(--muted);
        margin-top: 2px;
    }

    .panel-card__link {
        font-size: 12px;
        font-weight: 500;
        color: var(--blue-main);
        display: flex;
        align-items: center;
        gap: 3px;
        white-space: nowrap;
        cursor: pointer;
        flex-shrink: 0;
        transition: color 0.2s ease;
    }

    .panel-card__link:hover { color: var(--blue-hover); }
    .panel-card__link i { position: relative; top: 1px; font-size: 11px; }

    /* =============================================
       AUDIT LOG ENTRIES
       ============================================= */
    .audit-log-body { display: flex; flex-direction: column; }

    .audit-log-entry {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 13px 0;
        border-bottom: 1px solid var(--smooth-border);
    }

    .audit-log-entry:last-child { border-bottom: none; padding-bottom: 0; }
    .audit-log-entry:first-child { padding-top: 0; }

    .audit-timestamp {
        font-size: 10px;
        font-weight: 600;
        color: var(--black-secondary);
        background-color: var(--main-bg);
        padding: 4px 8px;
        border-radius: 4px;
        flex-shrink: 0;
        min-width: 46px;
        text-align: center;
    }

    .audit-timestamp--blue  { background-color: var(--blue-main-10); color: var(--blue-main); }
    .audit-timestamp--red   { background-color: var(--red-main-10);  color: var(--red-main); }
    .audit-timestamp--green { background-color: var(--success-10);   color: var(--success); }

    .audit-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        flex-shrink: 0;
    }

    .audit-dot--blue  { background-color: var(--blue-main); }
    .audit-dot--red   { background-color: var(--red-main); }
    .audit-dot--green { background-color: var(--success); }
    .audit-dot--dark  { background-color: var(--black); }

    .audit-text {
        font-size: 12px;
        font-weight: 400;
        color: var(--black-secondary);
        flex: 1;
        line-height: 1.4;
    }

    /* =============================================
       QUICK ACTION ITEMS
       ============================================= */
    .quick-action-body { display: flex; flex-direction: column; }
    .quick-action-body > form { margin: 0; }

    .quick-action-item {
        display: flex;
        align-items: center;
        gap: 14px;
        width: 100%;
        padding: 16px 10px;
        border-bottom: 1px solid var(--smooth-border);
        border-radius: 8px;
        cursor: pointer;
        color: inherit;
        transition: background-color 0.2s ease, transform 0.2s ease;
    }

    .quick-action-item:hover,
    .quick-action-item:focus-visible {
        background-color: var(--blue-main-5);
        transform: translateY(-1px);
        outline: none;
    }

    .quick-action-item:last-child { border-bottom: none; }
    .quick-action-body > form .quick-action-item { border-bottom: 1px solid var(--smooth-border); }

    .quick-action-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        flex-shrink: 0;
    }

    .quick-action-icon i { position: relative; top: 2px; }
    .quick-action-icon--blue  { background-color: var(--blue-main-10); color: var(--blue-main); }
    .quick-action-icon--green { background-color: var(--success-10);   color: var(--success); }

    .quick-action-text { flex: 1; }

    .quick-action-title {
        font-size: 13px;
        font-weight: 600;
        color: var(--black);
        line-height: 1.3;
    }

    .quick-action-sub {
        font-size: 11px;
        font-weight: 400;
        color: var(--muted);
        margin-top: 2px;
    }

    .quick-action-chevron {
        font-size: 16px;
        color: var(--muted);
        display: flex;
        align-items: center;
        flex-shrink: 0;
    }

    .quick-action-chevron i { position: relative; top: 1px; }

    .quick-action-plus {
        font-size: 22px;
        display: flex;
        align-items: center;
        flex-shrink: 0;
    }

    .quick-action-plus i { position: relative; top: 1px; }

    .dashboard-user-status {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        user-select: none;
    }

    .dashboard-user-status input {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }

    .dashboard-user-status__track {
        width: 38px;
        height: 22px;
        padding: 2px;
        border-radius: 999px;
        background-color: var(--red-main-10);
        border: 1px solid rgba(210,0,0,0.22);
        transition: 0.2s ease;
        flex-shrink: 0;
    }

    .dashboard-user-status__thumb {
        width: 16px;
        height: 16px;
        border-radius: 50%;
        display: block;
        background-color: var(--red-main);
        box-shadow: 0 2px 5px rgba(15,23,42,0.16);
        transition: transform 0.2s ease, background-color 0.2s ease;
    }

    .dashboard-user-status input:checked + .dashboard-user-status__track {
        background-color: var(--success-10);
        border-color: rgba(16,185,129,0.26);
    }

    .dashboard-user-status input:checked + .dashboard-user-status__track .dashboard-user-status__thumb {
        transform: translateX(16px);
        background-color: var(--success);
    }

    .dashboard-user-status__label {
        min-width: 58px;
        font-size: 10px;
        font-weight: 600;
        color: var(--red-main);
    }

    .dashboard-user-status.is-active .dashboard-user-status__label { color: var(--success); }

    .signature-upload {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px;
        border: 1px dashed var(--blue-main-25);
        border-radius: 8px;
        background-color: var(--main-bg);
    }

    .signature-upload__preview {
        width: 120px;
        height: 58px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1px solid var(--smooth-border);
        border-radius: 8px;
        background-color: var(--white);
        overflow: hidden;
        flex-shrink: 0;
        color: var(--muted);
        font-size: 10px;
        font-weight: 600;
        text-align: center;
    }

    .signature-upload__preview img {
        width: 100%;
        height: 100%;
        object-fit: contain;
    }

    .signature-upload__control {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 5px;
    }

    .signature-upload__hint {
        font-size: 10px;
        color: var(--muted);
        font-weight: 400;
    }

    @media (max-width: 640px) {
        .signature-upload {
            align-items: stretch;
            flex-direction: column;
        }

        .signature-upload__preview {
            width: 100%;
        }
    }

    /* =============================================
       MOBILE — stack the two-column panel grid
       ============================================= */
    @media (max-width: 900px) {
        .dashboard-panels {
            grid-template-columns: 1fr;
            gap: 14px;
        }
    }
</style>
@endpush

@section('content')
@php
    // Sample data — ganti dengan binding controller saat sudah ada backend.
    $stats = $stats ?? [
        ['label' => 'Total Pengguna Aktif',      'value' => '12',  'icon' => 'fi fi-sr-user',         'color' => 'blue',  'success' => false],
        ['label' => 'Kapasitas Server Terpakai', 'value' => '65%', 'icon' => 'fi fi-sr-database',     'color' => 'cyan',  'success' => false],
        ['label' => 'Status Backup Terakhir',    'value' => 'Berhasil', 'icon' => 'fi fi-sr-cloud-upload', 'color' => 'green', 'success' => true],
    ];

    $auditLogs = $auditLogs ?? [
        ['time' => '10:24', 'type' => 'blue',  'text' => 'Admin menonaktifkan akun <strong>"karu_a"</strong>'],
        ['time' => '10:24', 'type' => 'red',   'text' => 'Gagal Login: Percobaan password salah 3x oleh <strong>"Pak Mustari"</strong>'],
        ['time' => '10:24', 'type' => 'green', 'text' => '<strong>"Pak Nurul Huda"</strong> berhasil membuat laporan operasional'],
        ['time' => '10:24', 'type' => 'dark',  'text' => '<strong>"Pak Sabarudin"</strong> login ke sistem'],
    ];

    $roles = $roles ?? collect();
@endphp

<div class="page-header">
    <span class="page-title">Dashboard Sistem</span>
    <span class="page-subtitle">Pantau status infrastruktur, pengguna, dan pencadangan sistem (Backup).</span>
</div>

<!-- Stats Row -->
<div class="stats-row">
    @foreach ($stats as $s)
        <div class="stat-card">
            <span class="stat-card__label">{{ $s['label'] }}</span>
            <div class="stat-card__row">
                <span class="stat-card__value {{ $s['success'] ? 'stat-card__value--success' : '' }}">{{ $s['value'] }}</span>
                <span class="stat-card__icon stat-card__icon--{{ $s['color'] }}"><i class="{{ $s['icon'] }}"></i></span>
            </div>
        </div>
    @endforeach
</div>

<!-- Dashboard Panels -->
<div class="dashboard-panels">

    <!-- Audit Log -->
    <div class="panel-card">
        <div class="panel-card__header">
            <div class="panel-card__header-left">
                <div class="panel-card__icon"><i class="fi fi-rr-document"></i></div>
                <div>
                    <div class="panel-card__title">Audit Log</div>
                    <div class="panel-card__subtitle">Riwayat aktivitas sistem terbaru.</div>
                </div>
            </div>
            <a href="{{ route('admin.log') }}" class="panel-card__link">Lihat Semua <i class="fi fi-rr-angle-small-right"></i></a>
        </div>
        <div class="audit-log-body">
            @foreach ($auditLogs as $log)
                <div class="audit-log-entry">
                    <span class="audit-timestamp {{ $log['type'] !== 'dark' ? 'audit-timestamp--'.$log['type'] : '' }}">{{ $log['time'] }}</span>
                    <span class="audit-dot audit-dot--{{ $log['type'] }}"></span>
                    <span class="audit-text">{!! $log['text'] !!}</span>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Aksi Cepat IT -->
    <div class="panel-card">
        <div class="panel-card__header">
            <div class="panel-card__header-left">
                <div class="panel-card__icon"><i class="fi fi-rr-settings"></i></div>
                <div>
                    <div class="panel-card__title">Aksi Cepat IT</div>
                    <div class="panel-card__subtitle">Pilih tindakan untuk mengelola sistem dengan cepat</div>
                </div>
            </div>
        </div>
        <div class="quick-action-body">
            <form method="POST" action="{{ route('admin.backup.generate') }}">
                @csrf
                <button type="submit"
                        class="quick-action-item border-0 bg-transparent text-start"
                        data-confirm
                        data-confirm-submit="true"
                        data-confirm-tone="warning"
                        data-confirm-title="Generate manual backup?"
                        data-confirm-subtitle="Sistem akan membuat cadangan database."
                        data-confirm-message="Gunakan aksi ini saat ingin mengambil cadangan terbaru di luar jadwal otomatis."
                        data-confirm-summary="Output: file backup .json"
                        data-confirm-label="Generate Backup"
                        data-confirm-icon="fi fi-rr-rotate-right">
                    <div class="quick-action-icon quick-action-icon--blue"><i class="fi fi-rr-rotate-right"></i></div>
                    <div class="quick-action-text">
                        <div class="quick-action-title">Generate Manual Backup</div>
                        <div class="quick-action-sub">Buat cadangan data aplikasi (.json)</div>
                    </div>
                    <span class="quick-action-chevron"><i class="fi fi-rr-angle-small-right"></i></span>
                </button>
            </form>
            <button type="button" class="quick-action-item border-0 bg-transparent text-start" data-modal-target="dashboardUserModal">
                <div class="quick-action-icon quick-action-icon--green"><i class="fi fi-rr-user-add"></i></div>
                <div class="quick-action-text">
                    <div class="quick-action-title">Tambah Pengguna Baru</div>
                    <div class="quick-action-sub">Registrasi akun petugas</div>
                </div>
                <span class="quick-action-plus text-success"><i class="fi fi-rr-plus-small"></i></span>
            </button>
        </div>
    </div>

</div>

<div class="modal-overlay" id="dashboardUserModal" aria-hidden="true">
    <div class="modal-box modal-box--wide" role="dialog" aria-modal="true" aria-labelledby="dashboardUserTitle">
        <form method="POST" action="{{ route('admin.users.store') }}" id="dashboardUserForm" enctype="multipart/form-data">
            @csrf
            <div class="kss-modal__header">
                <div class="kss-modal__icon kss-modal__icon--success">
                    <i class="fi fi-rr-user-add"></i>
                </div>
                <div class="kss-modal__heading">
                    <div class="kss-modal__title" id="dashboardUserTitle">Tambah Pengguna Baru</div>
                    <div class="kss-modal__subtitle">Registrasi akun petugas dari shortcut dashboard.</div>
                </div>
                <button type="button" class="kss-modal__close" data-modal-close aria-label="Tutup modal">
                    <i class="fi fi-rr-cross-small"></i>
                </button>
            </div>
            <div class="kss-modal__body">
                <div class="kss-modal__grid">
                    <div class="kss-modal__field">
                        <label for="dashboardUserName">Nama Lengkap</label>
                        <input class="kss-modal__input" id="dashboardUserName" name="name" type="text" placeholder="Nama pengguna" data-modal-focus required>
                    </div>
                    <div class="kss-modal__field">
                        <label for="dashboardUsername">Username</label>
                        <input class="kss-modal__input" id="dashboardUsername" name="username" type="text" placeholder="username" required>
                    </div>
                    <div class="kss-modal__field">
                        <label for="dashboardUserRole">Role</label>
                        <div class="kss-modal__select-wrapper">
                            <select class="kss-modal__native-select" id="dashboardUserRole" name="role_id" required>
                                @foreach ($roles as $roleOption)
                                    <option value="{{ $roleOption->id }}" @selected($roleOption->name === \App\Models\Role::OPERATIONAL)>
                                        {{ \App\Models\Role::displayName($roleOption->name) }}
                                    </option>
                                @endforeach
                            </select>
                            <i class="fi fi-rr-angle-small-down kss-modal__select-icon"></i>
                        </div>
                    </div>
                    <div class="kss-modal__field">
                        <label for="dashboardUserGroup">Regu</label>
                        <div class="kss-modal__select-wrapper">
                            <select class="kss-modal__native-select" id="dashboardUserGroup" name="group">
                                <option value="Kantor">Kantor</option>
                                <option value="A">Regu A</option>
                                <option value="B">Regu B</option>
                                <option value="C">Regu C</option>
                                <option value="D">Regu D</option>
                            </select>
                            <i class="fi fi-rr-angle-small-down kss-modal__select-icon"></i>
                        </div>
                    </div>
                    <div class="kss-modal__field">
                        <label>Status</label>
                        <label class="dashboard-user-status is-active" id="dashboardUserStatusWrap">
                            <input type="hidden" name="status" value="nonaktif">
                            <input type="checkbox" id="dashboardUserStatus" name="status" value="aktif" checked>
                            <span class="dashboard-user-status__track"><span class="dashboard-user-status__thumb"></span></span>
                            <span class="dashboard-user-status__label" id="dashboardUserStatusLabel">Aktif</span>
                        </label>
                    </div>
                    <div class="kss-modal__field">
                        <label for="dashboardUserPassword">Password Awal</label>
                        <input class="kss-modal__input" id="dashboardUserPassword" name="password" type="password" placeholder="Masukkan password awal" required>
                    </div>
                    <div class="kss-modal__field kss-modal__field--full">
                        <label for="dashboardUserSignature">Tanda Tangan PNG</label>
                        <div class="signature-upload">
                            <div class="signature-upload__preview" id="dashboardUserSignaturePreview">Belum ada tanda tangan</div>
                            <div class="signature-upload__control">
                                <input class="kss-modal__input" id="dashboardUserSignature" name="signature" type="file" accept="image/png">
                                <span class="signature-upload__hint">Gunakan file PNG, maksimal 2 MB.</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="kss-modal__footer">
                <button type="button" class="kss-modal__button" data-modal-close>Batal</button>
                <button type="submit" class="kss-modal__button kss-modal__button--primary">
                    <i class="fi fi-rr-disk"></i> Simpan Pengguna
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const dashboardUserModal = document.getElementById('dashboardUserModal');
        const dashboardUserForm = document.getElementById('dashboardUserForm');
        const statusInput = document.getElementById('dashboardUserStatus');
        const statusWrap = document.getElementById('dashboardUserStatusWrap');
        const statusLabel = document.getElementById('dashboardUserStatusLabel');
        const signatureInput = document.getElementById('dashboardUserSignature');
        const signaturePreview = document.getElementById('dashboardUserSignaturePreview');
        let previewUrl = null;

        function setUserStatus(isActive) {
            if (!statusInput || !statusWrap || !statusLabel) return;
            statusInput.checked = isActive;
            statusWrap.classList.toggle('is-active', isActive);
            statusLabel.textContent = isActive ? 'Aktif' : 'Non-Aktif';
        }

        function clearPreviewUrl() {
            if (!previewUrl) return;
            URL.revokeObjectURL(previewUrl);
            previewUrl = null;
        }

        function setSignaturePreview(url) {
            if (!signaturePreview) return;

            if (url) {
                signaturePreview.innerHTML = `<img src="${url}" alt="Preview tanda tangan">`;
            } else {
                signaturePreview.textContent = 'Belum ada tanda tangan';
            }
        }

        statusInput?.addEventListener('change', () => setUserStatus(statusInput.checked));

        document.querySelector('[data-modal-target="dashboardUserModal"]')?.addEventListener('click', function () {
            dashboardUserForm?.reset();
            clearPreviewUrl();
            setSignaturePreview('');
            setUserStatus(true);
            window.KssAdminModal?.syncSelects(dashboardUserModal);
        });

        signatureInput?.addEventListener('change', function () {
            const file = signatureInput.files?.[0];
            clearPreviewUrl();

            if (!file) {
                setSignaturePreview('');
                return;
            }

            if (file.type !== 'image/png') {
                signatureInput.value = '';
                setSignaturePreview('');
                window.showAdminToast?.('error', 'File tidak valid', 'File tanda tangan harus berformat PNG.');
                return;
            }

            previewUrl = URL.createObjectURL(file);
            setSignaturePreview(previewUrl);
        });

        dashboardUserModal?.addEventListener('click', function (event) {
            if (!event.target.closest('[data-modal-close]') && event.target !== dashboardUserModal) return;
            clearPreviewUrl();
        });

        dashboardUserForm?.addEventListener('reset', function () {
            clearPreviewUrl();
            setSignaturePreview('');
            setUserStatus(true);
        });

        setUserStatus(true);
    });
</script>
@endpush
