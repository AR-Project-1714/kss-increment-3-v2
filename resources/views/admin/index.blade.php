@extends('admin.layouts.app')

@section('title', 'KSS Admin — Dashboard Sistem')
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

    .quick-action-item {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 16px 0;
        border-bottom: 1px solid var(--smooth-border);
        cursor: pointer;
        transition: background-color 0.2s ease;
    }

    .quick-action-item:last-child { border-bottom: none; padding-bottom: 0; }
    .quick-action-item:first-child { padding-top: 0; }

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
</style>
@endpush

@section('content')
@php
    // Sample data — ganti dengan binding controller saat sudah ada backend.
    $stats = [
        ['label' => 'Total Pengguna Aktif',      'value' => '12',  'icon' => 'fi fi-sr-user',         'color' => 'blue',  'success' => false],
        ['label' => 'Kapasitas Server Terpakai', 'value' => '65%', 'icon' => 'fi fi-sr-database',     'color' => 'cyan',  'success' => false],
        ['label' => 'Status Backup Terakhir',    'value' => 'Berhasil', 'icon' => 'fi fi-sr-cloud-upload', 'color' => 'green', 'success' => true],
    ];

    $auditLogs = [
        ['time' => '10:24', 'type' => 'blue',  'text' => 'Admin menonaktifkan akun <strong>"karu_a"</strong>'],
        ['time' => '10:24', 'type' => 'red',   'text' => 'Gagal Login: Percobaan password salah 3x oleh <strong>"Pak Mustari"</strong>'],
        ['time' => '10:24', 'type' => 'green', 'text' => '<strong>"Pak Nurul Huda"</strong> berhasil membuat laporan operasional'],
        ['time' => '10:24', 'type' => 'dark',  'text' => '<strong>"Pak Sabarudin"</strong> login ke sistem'],
    ];
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
            <div class="quick-action-item"
                 data-confirm
                 data-confirm-tone="warning"
                 data-confirm-title="Generate manual backup?"
                 data-confirm-subtitle="Sistem akan membuat cadangan database."
                 data-confirm-message="Gunakan aksi ini saat ingin mengambil cadangan terbaru di luar jadwal otomatis."
                 data-confirm-summary="Output preview: file backup .zip"
                 data-confirm-label="Generate Backup"
                 data-confirm-icon="fi fi-rr-rotate-right">
                <div class="quick-action-icon quick-action-icon--blue"><i class="fi fi-rr-rotate-right"></i></div>
                <div class="quick-action-text">
                    <div class="quick-action-title">Generate Manual Backup</div>
                    <div class="quick-action-sub">Buat cadangan database (.zip)</div>
                </div>
                <span class="quick-action-chevron"><i class="fi fi-rr-angle-small-right"></i></span>
            </div>
            <div class="quick-action-item" data-modal-target="dashboardUserModal">
                <div class="quick-action-icon quick-action-icon--green"><i class="fi fi-rr-user-add"></i></div>
                <div class="quick-action-text">
                    <div class="quick-action-title">Tambah Pengguna Baru</div>
                    <div class="quick-action-sub">Registrasi akun petugas</div>
                </div>
                <span class="quick-action-plus text-success"><i class="fi fi-rr-plus-small"></i></span>
            </div>
        </div>
    </div>

</div>

<div class="modal-overlay" id="dashboardUserModal" aria-hidden="true">
    <div class="modal-box modal-box--wide" role="dialog" aria-modal="true" aria-labelledby="dashboardUserTitle">
        <form data-preview-submit>
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
                        <input class="kss-modal__input" id="dashboardUserName" type="text" placeholder="Nama pengguna" data-modal-focus>
                    </div>
                    <div class="kss-modal__field">
                        <label for="dashboardUsername">Username</label>
                        <input class="kss-modal__input" id="dashboardUsername" type="text" placeholder="username">
                    </div>
                    <div class="kss-modal__field">
                        <label for="dashboardUserRole">Role</label>
                        <div class="kss-modal__select-wrapper">
                            <select class="kss-modal__native-select" id="dashboardUserRole">
                                <option>Operasional</option>
                                <option>Admin</option>
                                <option>Manajer</option>
                                <option>Pemeliharaan</option>
                                <option>Safety</option>
                            </select>
                            <i class="fi fi-rr-angle-small-down kss-modal__select-icon"></i>
                        </div>
                    </div>
                    <div class="kss-modal__field">
                        <label for="dashboardUserGroup">Regu</label>
                        <div class="kss-modal__select-wrapper">
                            <select class="kss-modal__native-select" id="dashboardUserGroup">
                                <option>Regu A</option>
                                <option>Regu B</option>
                                <option>Regu C</option>
                                <option>Kantor</option>
                            </select>
                            <i class="fi fi-rr-angle-small-down kss-modal__select-icon"></i>
                        </div>
                    </div>
                    <div class="kss-modal__field kss-modal__field--full">
                        <label for="dashboardUserNote">Catatan</label>
                        <textarea class="kss-modal__textarea" id="dashboardUserNote" placeholder="Catatan singkat untuk akun baru"></textarea>
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
