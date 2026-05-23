@extends('admin.layouts.app')

@section('title', 'KSS Admin - Manajemen Backup')
@section('active', 'backup')

@push('styles')
<style>
    .backup-layout {
        display: grid;
        grid-template-columns: minmax(0, 1.35fr) minmax(300px, 0.75fr);
        gap: 20px;
        align-items: start;
    }

    .section-card {
        background-color: var(--white);
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(37,99,235,0.07);
        transition: background-color 0.3s ease;
    }

    .section-card__title { font-size: 16px; font-weight: 600; color: var(--black); }

    .archive-body {
        padding: 20px;
        display: flex;
        flex-direction: column;
        gap: 16px;
    }

    .backup-toolbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
        margin-bottom: 16px;
    }

    .backup-toolbar__actions { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }

    .btn-tool {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 8px 14px;
        border: 1px solid var(--smooth-border);
        border-radius: 8px;
        background-color: var(--white);
        color: var(--black-secondary);
        font-family: inherit;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: 0.2s ease;
    }

    .btn-tool i { position: relative; top: 1px; }
    .btn-tool:hover { background-color: var(--blue-main-5); border-color: var(--blue-main-25); color: var(--blue-main); }
    .btn-tool--primary { background-color: var(--blue-main); border-color: var(--blue-main); color: #fff; }
    .btn-tool--primary:hover { background-color: var(--blue-hover); border-color: var(--blue-hover); color: #fff; }
    .btn-tool--danger { border-color: var(--red-main); color: var(--red-main); }
    .btn-tool--danger:hover { background-color: var(--red-main-10); border-color: var(--red-main); color: var(--red-main); }

    .backup-health {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 12px;
        margin-bottom: 16px;
    }

    .health-item {
        border: 1px solid var(--smooth-border);
        border-radius: 8px;
        padding: 12px;
        background-color: var(--main-bg);
    }

    .health-item__label { font-size: 10px; color: var(--muted); font-weight: 500; }
    .health-item__value { margin-top: 6px; font-size: 14px; color: var(--black); font-weight: 700; }
    .health-item__value.success { color: var(--success); }
    .health-item__value.warning { color: var(--orange-main); }

    .backup-table-wrapper {
        width: 100%;
        overflow-x: auto;
        scrollbar-width: thin;
        scrollbar-color: var(--blue-main-25) transparent;
    }

    .backup-table { min-width: 780px; width: 100%; }

    .backup-row {
        display: grid;
        grid-template-columns: 1.4fr 0.85fr 0.8fr 0.85fr 1fr;
        align-items: center;
        gap: 10px;
        border-bottom: 1px solid var(--smooth-border);
    }

    .backup-row.header {
        background-color: var(--blue-main-5);
        border-radius: 6px;
        border-bottom: none;
    }

    .backup-row > div {
        padding: 11px 10px;
        font-size: 12px;
        color: var(--black-secondary);
    }

    .backup-row.header > div {
        font-weight: 600;
        color: var(--black-secondary);
    }

    .backup-name {
        display: flex;
        flex-direction: column;
        gap: 2px;
    }

    .backup-name strong { color: var(--black); font-size: 12px; }
    .backup-name span { color: var(--muted); font-size: 10px; font-weight: 400; }

    .backup-status {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        width: fit-content;
        padding: 4px 9px;
        border-radius: 999px;
        font-size: 10px;
        font-weight: 600;
    }

    .backup-status::before {
        content: '';
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background-color: currentColor;
    }

    .backup-status.success { color: var(--success); background-color: var(--success-10); }
    .backup-status.warning { color: var(--orange-main); background-color: var(--orange-main-10); }

    .backup-actions { display: flex; gap: 7px; flex-wrap: wrap; }

    .btn-act {
        display: inline-flex;
        justify-content: center;
        align-items: center;
        width: 30px;
        height: 30px;
        border: none;
        border-radius: 6px;
        color: #fff;
        cursor: pointer;
        transition: 0.2s ease-out;
    }

    .btn-act i { position: relative; top: 1px; }
    .btn-act.download { background-color: var(--blue-main); }
    .btn-act.restore { background-color: var(--orange-main); }
    .btn-act.delete { background-color: var(--red-main); }
    .btn-act:hover { transform: translateY(-1px); filter: brightness(0.95); }

    .side-panel {
        display: flex;
        flex-direction: column;
        gap: 16px;
    }

    .backup-card {
        background-color: var(--white);
        border-radius: 10px;
        padding: 18px;
        box-shadow: 0 2px 4px rgba(37,99,235,0.07);
        border: 1px solid transparent;
    }

    .backup-card__title {
        font-size: 13px;
        font-weight: 700;
        color: var(--black);
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 12px;
    }

    .backup-card__title i { color: var(--blue-main); position: relative; top: 1px; }

    .backup-card--annual { border-color: var(--orange-main-10); }
    .backup-card--annual .backup-card__title i { color: var(--orange-main); }

    .annual-target {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 12px;
        border-radius: 8px;
        background-color: var(--main-bg);
        border: 1px solid var(--smooth-border);
    }

    .annual-target__label { display: block; font-size: 10px; color: var(--muted); font-weight: 500; }
    .annual-target__year { font-size: 20px; font-weight: 800; color: var(--black); }
    .annual-target__count { font-size: 12px; font-weight: 600; color: var(--blue-main); }

    .annual-btn { width: 100%; justify-content: center; }
    .annual-note { margin-top: 10px; font-size: 11px; color: var(--muted); line-height: 1.5; }

    .storage-meter {
        height: 8px;
        border-radius: 999px;
        background-color: var(--blue-main-10);
        overflow: hidden;
        margin: 12px 0 8px;
    }

    .storage-meter span {
        display: block;
        width: 62%;
        height: 100%;
        background: linear-gradient(90deg, var(--blue-main), var(--success));
        border-radius: inherit;
    }

    .backup-meta {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        font-size: 11px;
        color: var(--muted);
    }

    .schedule-list {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .schedule-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        padding: 10px 0;
        border-bottom: 1px solid var(--smooth-border);
    }

    .schedule-item:last-child { border-bottom: none; padding-bottom: 0; }
    .schedule-item span { color: var(--muted); font-size: 11px; }
    .schedule-item strong { color: var(--black); font-size: 12px; }

    @media (max-width: 1024px) {
        .backup-layout { grid-template-columns: 1fr; }
        /* Izinkan kolom menyusut agar tabel di dalamnya bisa di-scroll, bukan meluber */
        .backup-layout > * { min-width: 0; }
        .backup-health { grid-template-columns: 1fr; }
    }

    @media (max-width: 560px) {
        .backup-health { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .backup-toolbar { flex-direction: column; align-items: stretch; }
        .backup-toolbar__actions { width: 100%; }
        .backup-toolbar__actions .btn-tool { flex: 1 1 auto; justify-content: center; }
    }
</style>
@endpush

@section('content')
@php
    $stats = $stats ?? [
        ['label' => 'Backup Terakhir', 'value' => '21 Mei 2026', 'icon' => 'fi fi-sr-cloud-check', 'color' => 'green'],
        ['label' => 'Total Cadangan', 'value' => '18', 'icon' => 'fi fi-sr-folder', 'color' => 'blue'],
        ['label' => 'Storage Terpakai', 'value' => '62%', 'icon' => 'fi fi-sr-database', 'color' => 'cyan'],
        ['label' => 'Retensi Aktif', 'value' => '30 Hari', 'icon' => 'fi fi-sr-calendar', 'color' => 'orange'],
    ];

    $backups = $backups ?? collect([
        ['name' => 'backup-kss-20260521-0200.zip', 'meta' => 'Database + lampiran tanda tangan', 'date' => '21 Mei 2026, 02:00', 'size' => '428 MB', 'type' => 'Otomatis', 'status' => 'success', 'status_label' => 'Berhasil'],
        ['name' => 'backup-kss-20260520-0200.zip', 'meta' => 'Database + file laporan', 'date' => '20 Mei 2026, 02:00', 'size' => '421 MB', 'type' => 'Otomatis', 'status' => 'success', 'status_label' => 'Berhasil'],
        ['name' => 'backup-kss-manual-20260519.zip', 'meta' => 'Backup manual sebelum maintenance', 'date' => '19 Mei 2026, 16:42', 'size' => '418 MB', 'type' => 'Manual', 'status' => 'success', 'status_label' => 'Berhasil'],
        ['name' => 'backup-kss-20260518-0200.zip', 'meta' => 'Database + file laporan', 'date' => '18 Mei 2026, 02:00', 'size' => '410 MB', 'type' => 'Otomatis', 'status' => 'warning', 'status_label' => 'Perlu Cek'],
    ]);

    $backupSchedule = $backupSchedule ?? [
        'frequency' => 'Harian',
        'time' => '02:00',
        'retention' => '30 Hari',
        'target' => 'Local Storage',
    ];

    $backupStorage = $backupStorage ?? [
        'used_label' => '18.6 GB dipakai',
        'capacity_label' => '30 GB tersedia',
        'percent' => 62,
    ];

    $annualBackup = $annualBackup ?? [
        'eligible' => false,
        'year' => null,
        'count' => 0,
        'file_name' => null,
    ];
@endphp

<div class="page-header">
    <span class="page-title">Manajemen Backup</span>
    <span class="page-subtitle">Pantau cadangan sistem, retensi file, dan status pencadangan aplikasi.</span>
</div>

<div class="stats-row">
    @foreach ($stats as $s)
        <div class="stat-card">
            <span class="stat-card__label">{{ $s['label'] }}</span>
            <div class="stat-card__row">
                <span class="stat-card__value">{{ $s['value'] }}</span>
                <span class="stat-card__icon stat-card__icon--{{ $s['color'] }}"><i class="{{ $s['icon'] }}"></i></span>
            </div>
        </div>
    @endforeach
</div>

<div class="backup-layout">
    @component('admin.layouts.card', ['title' => 'Daftar Backup'])
        <div class="backup-toolbar">
            <div class="backup-health">
                <div class="health-item">
                    <div class="health-item__label">Status Scheduler</div>
                    <div class="health-item__value success">Aktif</div>
                </div>
                <div class="health-item">
                    <div class="health-item__label">Backup Berikutnya</div>
                    <div class="health-item__value">22 Mei, 02:00</div>
                </div>
                <div class="health-item">
                    <div class="health-item__label">Pemeriksaan Integritas</div>
                    <div class="health-item__value warning">Mingguan</div>
                </div>
            </div>
            <div class="backup-toolbar__actions">
                <button type="button" class="btn-tool" data-modal-target="backupScheduleModal">
                    <i class="fi fi-rr-calendar-clock"></i> Atur Jadwal
                </button>
                <form method="POST" action="{{ route('admin.backup.generate') }}">
                    @csrf
                    <button type="submit"
                            class="btn-tool btn-tool--primary"
                            data-confirm
                            data-confirm-submit="true"
                            data-confirm-tone="warning"
                            data-confirm-title="Generate backup manual?"
                            data-confirm-subtitle="Cadangan sistem akan dibuat dari kondisi data saat ini."
                            data-confirm-message="Proses backup manual disiapkan untuk database, laporan, dan master data."
                            data-confirm-summary="Output: file backup .json"
                            data-confirm-label="Generate Backup"
                            data-confirm-icon="fi fi-rr-rotate-right">
                        <i class="fi fi-rr-rotate-right"></i> Generate Backup
                    </button>
                </form>
            </div>
        </div>

        <div class="backup-table-wrapper">
            <div class="backup-table">
                <div class="backup-row header">
                    <div>File Backup</div>
                    <div>Tanggal</div>
                    <div>Ukuran</div>
                    <div>Status</div>
                    <div>Aksi</div>
                </div>
                @foreach ($backups as $backup)
                    <div class="backup-row">
                        <div class="backup-name">
                            <strong>{{ $backup['name'] }}</strong>
                            <span>{{ $backup['meta'] }} - {{ $backup['type'] }}</span>
                        </div>
                        <div>{{ $backup['date'] }}</div>
                        <div>{{ $backup['size'] }}</div>
                        <div><span class="backup-status {{ $backup['status'] }}">{{ $backup['status_label'] }}</span></div>
                        <div class="backup-actions">
                            <button type="button"
                                    class="btn-act download"
                                    title="Download"
                                    data-confirm
                                    data-confirm-redirect="{{ $backup['download_url'] ?? '#' }}"
                                    data-confirm-tone="success"
                                    data-confirm-title="Download backup?"
                                    data-confirm-subtitle="File backup akan disiapkan untuk diunduh."
                                    data-confirm-message="Pastikan file ini disimpan di lokasi yang aman setelah diunduh."
                                    data-confirm-summary="{{ $backup['name'] }}"
                                    data-confirm-label="Download"
                                    data-confirm-icon="fi fi-rr-download">
                                <i class="fi fi-rr-download"></i>
                            </button>
                            <form method="POST" action="{{ $backup['restore_url'] ?? '#' }}">
                                @csrf
                                <button type="submit"
                                        class="btn-act restore"
                                        title="Restore"
                                        data-confirm
                                        data-confirm-submit="true"
                                        data-confirm-tone="warning"
                                        data-confirm-title="Restore dari backup?"
                                        data-confirm-subtitle="Permintaan restore akan dicatat."
                                        data-confirm-message="Restore adalah tindakan sensitif. File akan diverifikasi oleh admin server sebelum data dikembalikan."
                                        data-confirm-summary="{{ $backup['name'] }}"
                                        data-confirm-label="Catat Restore"
                                        data-confirm-icon="fi fi-rr-time-past">
                                    <i class="fi fi-rr-time-past"></i>
                                </button>
                            </form>
                            <form method="POST" action="{{ $backup['destroy_url'] ?? '#' }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="btn-act delete"
                                        title="Hapus"
                                        data-confirm
                                        data-confirm-submit="true"
                                        data-confirm-tone="danger"
                                        data-confirm-title="Hapus file backup?"
                                        data-confirm-subtitle="File backup akan dihapus dari daftar cadangan."
                                        data-confirm-message="Pastikan file ini sudah melewati masa retensi atau sudah dipindahkan ke penyimpanan lain."
                                        data-confirm-summary="{{ $backup['name'] }}"
                                        data-confirm-label="Hapus Backup"
                                        data-confirm-icon="fi fi-rr-trash">
                                    <i class="fi fi-rr-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endcomponent

    <div class="side-panel">
        <div class="backup-card backup-card--annual">
            <div class="backup-card__title"><i class="fi fi-sr-time-past"></i> Backup Tahunan</div>
            <div class="kss-modal__message" style="margin-bottom: 14px;">
                Arsipkan seluruh laporan tahun sebelumnya ke satu file ZIP untuk dipindahkan ke penyimpanan lokal, lalu hapus dari sistem agar penyimpanan server lebih ringan.
            </div>

            @if ($annualBackup['eligible'])
                <div class="annual-target">
                    <div>
                        <span class="annual-target__label">Tahun</span>
                        <strong class="annual-target__year">{{ $annualBackup['year'] }}</strong>
                    </div>
                    <div class="annual-target__count">{{ number_format($annualBackup['count'], 0, ',', '.') }} laporan</div>
                </div>

                <form method="POST" action="{{ route('admin.backup.annual') }}" style="margin-top: 14px;">
                    @csrf
                    <button type="submit"
                            class="btn-tool btn-tool--danger annual-btn"
                            data-confirm
                            data-confirm-submit="true"
                            data-confirm-tone="danger"
                            data-confirm-title="Backup & hapus laporan tahun {{ $annualBackup['year'] }}?"
                            data-confirm-subtitle="Seluruh laporan tahun {{ $annualBackup['year'] }} akan diarsipkan ke ZIP lalu DIHAPUS permanen dari sistem."
                            data-confirm-message="Pastikan Anda mengunduh file ZIP dan menyimpannya ke penyimpanan lokal setelah proses selesai. Tindakan ini tidak dapat dibatalkan."
                            data-confirm-summary="{{ $annualBackup['file_name'] }} • {{ $annualBackup['count'] }} laporan"
                            data-confirm-label="Backup & Arsipkan"
                            data-confirm-icon="fi fi-rr-box-open">
                        <i class="fi fi-rr-archive"></i> Backup Laporan {{ $annualBackup['year'] }}
                    </button>
                </form>
            @else
                <button type="button" class="btn-tool annual-btn" disabled style="opacity: 0.6; cursor: not-allowed;">
                    <i class="fi fi-rr-lock"></i> Belum tersedia
                </button>
                <div class="annual-note">
                    Tersedia saat sudah memasuki tahun baru dan masih ada laporan tahun sebelumnya yang tersimpan di sistem.
                </div>
            @endif
        </div>

        <div class="backup-card">
            <div class="backup-card__title"><i class="fi fi-sr-database"></i> Kapasitas Storage</div>
            <div class="storage-meter"><span style="width: {{ $backupStorage['percent'] }}%;"></span></div>
            <div class="backup-meta">
                <span>{{ $backupStorage['used_label'] }}</span>
                <span>{{ $backupStorage['capacity_label'] }}</span>
            </div>
        </div>

        <div class="backup-card">
            <div class="backup-card__title"><i class="fi fi-sr-calendar"></i> Jadwal Backup</div>
            <div class="schedule-list">
                <div class="schedule-item">
                    <span>Frekuensi</span>
                    <strong>{{ $backupSchedule['frequency'] }}</strong>
                </div>
                <div class="schedule-item">
                    <span>Jam Eksekusi</span>
                    <strong>{{ $backupSchedule['time'] }} WITA</strong>
                </div>
                <div class="schedule-item">
                    <span>Retensi</span>
                    <strong>{{ $backupSchedule['retention'] }}</strong>
                </div>
                <div class="schedule-item">
                    <span>Tujuan</span>
                    <strong>{{ $backupSchedule['target'] }}</strong>
                </div>
            </div>
        </div>

        <div class="backup-card">
            <div class="backup-card__title"><i class="fi fi-sr-shield-check"></i> Validasi Terakhir</div>
            <div class="kss-modal__message">
                Pemeriksaan backup terakhir mengikuti file cadangan terbaru yang tersimpan di storage lokal aplikasi.
            </div>
        </div>
    </div>
</div>

<div class="modal-overlay" id="backupScheduleModal" aria-hidden="true">
    <div class="modal-box modal-box--wide" role="dialog" aria-modal="true" aria-labelledby="backupScheduleTitle">
        <form method="POST" action="{{ route('admin.backup.schedule') }}">
            @csrf
            @method('PUT')
            <div class="kss-modal__header">
                <div class="kss-modal__icon">
                    <i class="fi fi-rr-calendar-clock"></i>
                </div>
                <div class="kss-modal__heading">
                    <div class="kss-modal__title" id="backupScheduleTitle">Atur Jadwal Backup</div>
                    <div class="kss-modal__subtitle">Sesuaikan frekuensi, waktu eksekusi, dan masa retensi cadangan.</div>
                </div>
                <button type="button" class="kss-modal__close" data-modal-close aria-label="Tutup modal">
                    <i class="fi fi-rr-cross-small"></i>
                </button>
            </div>
            <div class="kss-modal__body">
                <div class="kss-modal__grid">
                    <div class="kss-modal__field">
                        <label for="backupFrequency">Frekuensi</label>
                        <div class="kss-modal__select-wrapper">
                            <select class="kss-modal__native-select" id="backupFrequency" name="frequency">
                                <option @selected($backupSchedule['frequency'] === 'Harian')>Harian</option>
                                <option @selected($backupSchedule['frequency'] === 'Mingguan')>Mingguan</option>
                                <option @selected($backupSchedule['frequency'] === 'Bulanan')>Bulanan</option>
                            </select>
                            <i class="fi fi-rr-angle-small-down kss-modal__select-icon"></i>
                        </div>
                    </div>
                    <div class="kss-modal__field">
                        <label for="backupTime">Jam Backup</label>
                        <input id="backupTime" name="time" type="hidden" value="{{ $backupSchedule['time'] }}" data-kss-picker="time" data-trigger-class="kss-modal__input" data-placeholder="Pilih jam backup">
                    </div>
                    <div class="kss-modal__field">
                        <label for="backupRetention">Retensi</label>
                        <div class="kss-modal__select-wrapper">
                            <select class="kss-modal__native-select" id="backupRetention" name="retention">
                                <option @selected($backupSchedule['retention'] === '14 Hari')>14 Hari</option>
                                <option @selected($backupSchedule['retention'] === '30 Hari')>30 Hari</option>
                                <option @selected($backupSchedule['retention'] === '60 Hari')>60 Hari</option>
                                <option @selected($backupSchedule['retention'] === '90 Hari')>90 Hari</option>
                            </select>
                            <i class="fi fi-rr-angle-small-down kss-modal__select-icon"></i>
                        </div>
                    </div>
                    <div class="kss-modal__field">
                        <label for="backupTarget">Tujuan Backup</label>
                        <div class="kss-modal__select-wrapper">
                            <select class="kss-modal__native-select" id="backupTarget" name="target">
                                <option @selected($backupSchedule['target'] === 'Local Storage')>Local Storage</option>
                                <option @selected($backupSchedule['target'] === 'External Drive')>External Drive</option>
                                <option @selected($backupSchedule['target'] === 'Cloud Storage')>Cloud Storage</option>
                            </select>
                            <i class="fi fi-rr-angle-small-down kss-modal__select-icon"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="kss-modal__footer">
                <button type="button" class="kss-modal__button" data-modal-close>Batal</button>
                <button type="submit" class="kss-modal__button kss-modal__button--primary">
                    <i class="fi fi-rr-disk"></i> Simpan Jadwal
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
