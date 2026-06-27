@extends('admin.layouts.app')

@section('title', 'KSS Admin - Pusat Bantuan')
@section('active', 'help')

@push('styles')
<style>
    /* =========================================================
       PUSAT BANTUAN ADMIN
       Desain diseragamkan dengan halaman lain (section-card +
       report-tab) agar konsisten di seluruh project.
       ========================================================= */

    /* ---- Box putih kartu (samakan dgn layout manajer/index) ---- */
    .section-card {
        background-color: var(--white);
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(37,99,235,0.07);
        transition: background-color 0.3s ease;
    }

    .section-card__header {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 5px;
        padding: 15px 20px;
        background-color: var(--blue-main-3);
        border-top: 3px solid var(--blue-main);
        border-top-left-radius: 10px;
        border-top-right-radius: 10px;
    }

    .section-card__title { font-size: 16px; font-weight: 600; color: var(--black); }
    .section-card__subtitle { font-size: 11px; font-weight: 400; color: var(--black-secondary); line-height: 1.5; }

    .section-card__body {
        padding: 20px;
        display: flex;
        flex-direction: column;
        gap: 16px;
    }

    /* ---- Toolbar / pencarian ---- */
    .help-toolbar {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }

    .help-search { position: relative; flex: 1 1 320px; max-width: 540px; }

    .help-searchbox {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 9px 16px;
        border: 1px solid var(--smooth-border);
        border-radius: 50px;
        background-color: var(--main-bg);
    }

    .help-searchbox i { color: var(--muted); font-size: 13px; position: relative; top: 1px; }

    .help-searchbox input {
        border: none;
        background: transparent;
        outline: none;
        font-family: inherit;
        font-size: 12px;
        color: var(--black);
        width: 100%;
        padding-right: 22px;
    }

    .help-searchbox input::placeholder { color: var(--muted); }

    .help-search__clear {
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        width: 20px;
        height: 20px;
        border: none;
        border-radius: 50%;
        background-color: var(--blue-main-10);
        color: var(--blue-main);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        font-size: 11px;
    }

    .help-search__clear i { position: relative; top: 1px; }
    .help-toolbar__hint { font-size: 11px; color: var(--muted); }

    /* ---- Tab navigasi (gaya report-tab project, sticky) ---- */
    .help-tabs {
        position: sticky;
        top: 0;
        z-index: 6;
        flex-shrink: 0;
        display: flex;
        align-items: center;
        align-content: center;
        gap: 5px 10px;
        padding: 5px;
        overflow-x: auto;
        overflow-y: hidden;
        background-color: rgba(255, 255, 255, 0.72);
        backdrop-filter: blur(18px) saturate(180%);
        -webkit-backdrop-filter: blur(18px) saturate(180%);
        border: 1px solid rgba(255, 255, 255, 0.5);
        border-radius: 10px;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08), inset 0 1px 0 rgba(255, 255, 255, 0.7);
        scrollbar-width: none;
    }

    body.dark-mode .help-tabs {
        background-color: rgba(15, 23, 42, 0.72);
        border-color: rgba(255, 255, 255, 0.12);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.45), inset 0 1px 0 rgba(255, 255, 255, 0.08);
    }

    .help-tabs::-webkit-scrollbar { display: none; }

    .help-tab {
        position: relative;
        z-index: 1;
        display: flex;
        min-width: 130px;
        flex: 1 0 auto;
        justify-content: center;
        align-items: center;
        gap: 8px;
        padding: 6px 12px;
        border: none;
        background: transparent;
        border-radius: 8px;
        font-family: inherit;
        font-size: 12px;
        font-weight: 500;
        color: var(--black-secondary);
        white-space: nowrap;
        cursor: pointer;
        transition: color 0.2s ease-out, background-color 0.2s ease-out;
    }

    .help-tab .icon-tab { position: relative; top: 1px; display: inline-flex; }
    .help-tab .icon-tab i { position: relative; top: 1px; font-size: 12px; }
    .help-tab:hover { background-color: var(--blue-main-10); color: var(--blue-main); }
    .help-tab.active,
    .help-tab.active:hover { color: #fff; background: transparent; }

    .help-tab-indicator {
        position: absolute;
        left: 0;
        top: 5px;
        bottom: 5px;
        height: auto;
        width: 0;
        border-radius: 8px;
        background: var(--blue-main);
        box-shadow: 0 0 4px 0 var(--blue-main-40);
        transform: translateX(0);
        transition: transform 0.34s cubic-bezier(.22,1,.36,1), width 0.34s cubic-bezier(.22,1,.36,1);
        pointer-events: none;
        z-index: 0;
    }

    .help-section { scroll-margin-top: 64px; }

    /* ---- Konten ---- */
    .help-lead { font-size: 12px; line-height: 1.7; color: var(--black-secondary); }

    .help-steps { display: flex; flex-direction: column; gap: 10px; }

    .help-step {
        display: flex;
        gap: 12px;
        padding: 12px 14px;
        border: 1px solid var(--smooth-border);
        border-radius: 8px;
        background-color: var(--blue-main-3);
    }

    .help-step__num {
        width: 22px;
        height: 22px;
        flex-shrink: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        background-color: var(--blue-main);
        color: #fff;
        font-size: 11px;
        font-weight: 700;
    }

    .help-step__title { font-size: 12px; font-weight: 600; color: var(--black); }
    .help-step__text { margin-top: 2px; font-size: 11px; line-height: 1.6; color: var(--black-secondary); }

    .help-defs { display: flex; flex-direction: column; gap: 1px; }

    .help-def {
        display: grid;
        grid-template-columns: 168px minmax(0, 1fr);
        gap: 14px;
        padding: 11px 0;
        border-bottom: 1px solid var(--smooth-border);
    }

    .help-def:last-child { border-bottom: none; }
    .help-def__term { font-size: 11px; font-weight: 700; color: var(--black); }
    .help-def__desc { font-size: 11px; line-height: 1.6; color: var(--black-secondary); }

    .help-callout {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        padding: 12px 14px;
        border: 1px solid var(--blue-main-10);
        border-radius: 8px;
        background-color: var(--blue-main-3);
        font-size: 11px;
        line-height: 1.6;
        color: var(--black-secondary);
    }

    .help-callout i { position: relative; top: 2px; flex-shrink: 0; font-size: 13px; color: var(--blue-main); }
    .help-callout strong { color: var(--black); }
    .help-callout--warn   { border-color: var(--orange-main-10); background-color: var(--orange-main-10); }
    .help-callout--warn i { color: var(--orange-main); }
    .help-callout--danger   { border-color: var(--red-main-10); background-color: var(--red-main-5); }
    .help-callout--danger i { color: var(--red-main); }

    .help-table { width: 100%; min-width: 520px; border-collapse: collapse; font-size: 11px; }

    .help-table th,
    .help-table td {
        padding: 11px 12px;
        border-bottom: 1px solid var(--smooth-border);
        text-align: left;
        vertical-align: top;
        line-height: 1.55;
    }

    .help-table th { color: var(--black); background-color: var(--blue-main-5); font-weight: 600; }
    .help-table td { color: var(--black-secondary); }
    .help-table tr:last-child td { border-bottom: none; }
    .help-table code {
        font-size: 10px;
        padding: 1px 6px;
        border-radius: 5px;
        background-color: var(--blue-main-10);
        color: var(--blue-main);
        font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
    }

    .help-pill {
        display: inline-flex;
        align-items: center;
        padding: 2px 8px;
        border-radius: 999px;
        font-size: 10px;
        font-weight: 700;
        white-space: nowrap;
    }

    .help-pill.gray   { color: var(--muted);       background-color: var(--main-bg); border: 1px solid var(--smooth-border); }
    .help-pill.orange { color: var(--orange-main); background-color: var(--orange-main-10); }
    .help-pill.cyan   { color: var(--cyan-main);   background-color: var(--cyan-main-10); }
    .help-pill.blue   { color: var(--blue-main);   background-color: var(--blue-main-10); }
    .help-pill.green  { color: var(--success);     background-color: var(--success-10); }

    .faq-list { display: flex; flex-direction: column; gap: 10px; }

    .faq-item {
        border: 1px solid var(--smooth-border);
        border-radius: 8px;
        background-color: var(--white);
        overflow: hidden;
    }

    .faq-item summary {
        list-style: none;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 13px 14px;
        cursor: pointer;
        font-size: 12px;
        font-weight: 600;
        color: var(--black);
    }

    .faq-item summary::-webkit-details-marker { display: none; }
    .faq-item summary i { color: var(--blue-main); transition: transform 0.34s cubic-bezier(0.4, 0, 0.2, 1); flex-shrink: 0; }
    .faq-item.is-open summary i { transform: rotate(180deg); }

    .faq-item__body {
        padding: 0 14px 14px;
        font-size: 11px;
        line-height: 1.7;
        color: var(--black-secondary);
    }

    /* ---- Empty state pencarian ---- */
    .help-empty {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 10px;
        text-align: center;
        padding: 36px 16px;
    }

    .help-empty__icon {
        width: 54px;
        height: 54px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: var(--blue-main-10);
        color: var(--blue-main);
        font-size: 20px;
    }

    .help-empty__icon i { position: relative; top: 2px; }
    .help-empty__title { font-size: 13px; font-weight: 600; color: var(--black); }
    .help-empty__text { font-size: 11px; color: var(--muted); }

    .is-hidden { display: none !important; }

    @media (max-width: 560px) {
        .section-card__body { padding: 16px 14px; }
        .help-def { grid-template-columns: 1fr; gap: 2px; }
        .help-tab { min-width: 44px; padding: 9px 10px; gap: 0; }
        .help-tab span:not(.icon-tab) { display: none; }
    }
</style>
@endpush

@section('content')
@php
    $nav = [
        ['id' => 'ringkasan', 'label' => 'Ringkasan',     'icon' => 'fi fi-rr-shield-check'],
        ['id' => 'dashboard', 'label' => 'Dashboard',      'icon' => 'fi fi-rr-apps'],
        ['id' => 'arsip',     'label' => 'Arsip Laporan',  'icon' => 'fi fi-rr-folder'],
        ['id' => 'log',       'label' => 'Log Aktivitas',  'icon' => 'fi fi-rr-document'],
        ['id' => 'pengguna',  'label' => 'Kelola Pengguna','icon' => 'fi fi-rr-user'],
        ['id' => 'master',    'label' => 'Data Master',    'icon' => 'fi fi-rr-database'],
        ['id' => 'backup',    'label' => 'Backup',         'icon' => 'fi fi-rr-cloud-upload'],
        ['id' => 'status',    'label' => 'Status Laporan', 'icon' => 'fi fi-rr-time-check'],
        ['id' => 'akses',     'label' => 'Hak Akses',      'icon' => 'fi fi-rr-users-alt'],
        ['id' => 'faq',       'label' => 'Tanya Jawab',    'icon' => 'fi fi-rr-interrogation'],
    ];
@endphp

<div class="page-header">
    <span class="page-title">Pusat Bantuan</span>
    <span class="page-subtitle">Panduan lengkap penggunaan panel admin: dashboard, arsip, log, pengguna, data master, dan backup.</span>
</div>

{{-- Pencarian --}}
<div class="section-card">
    <div class="section-card__body">
        <div class="help-toolbar">
            <div class="help-search">
                <div class="help-searchbox">
                    <i class="fi fi-rr-search"></i>
                    <input type="text" id="helpSearch" placeholder="Cari bantuan, mis. backup, pengguna, status laporan" autocomplete="off">
                </div>
                <button type="button" class="help-search__clear is-hidden" id="helpSearchClear" aria-label="Bersihkan pencarian">
                    <i class="fi fi-rr-cross-small"></i>
                </button>
            </div>
            <span class="help-toolbar__hint">Mengetik akan menyaring topik di bawah secara langsung.</span>
        </div>
    </div>
</div>

{{-- Tab navigasi (sticky + glassmorphism, gaya tab form petugas) --}}
<div class="help-tabs" id="helpTabs">
    @foreach ($nav as $item)
        <button type="button" class="help-tab" data-tab="{{ $item['id'] }}">
            <span class="icon-tab"><i class="{{ $item['icon'] }}"></i></span>
            <span>{{ $item['label'] }}</span>
        </button>
    @endforeach
    <span class="help-tab-indicator" id="helpTabIndicator"></span>
</div>

<div class="help-empty is-hidden" id="helpEmpty">
    <div class="help-empty__icon"><i class="fi fi-rr-search"></i></div>
    <div class="help-empty__title">Topik tidak ditemukan</div>
    <div class="help-empty__text">Coba kata kunci lain, mis. "tanda tangan", "retensi", atau "status".</div>
</div>

{{-- RINGKASAN & PERAN ADMIN --}}
<section class="section-card help-section" id="ringkasan">
    <div class="section-card__header d-flex flex-column align-items-start">
        <span class="section-card__title">Ringkasan &amp; Peran Admin</span>
        <span class="section-card__subtitle">Apa yang dikelola admin dan bagaimana posisinya dalam sistem KSS.</span>
    </div>
    <div class="section-card__body">
        <p class="help-lead" data-help-item>
            KSS adalah sistem pelaporan harian untuk tiga divisi: <strong>Operasional</strong>, <strong>Pemeliharaan</strong>, dan <strong>Safety/K3</strong>.
            Sebagai admin, Anda <strong>tidak mengisi laporan</strong>. Tugas admin adalah menjaga data dan sistem: memantau dashboard,
            mengelola arsip laporan dari ketiga divisi, memeriksa log aktivitas, mengatur akun pengguna, merawat data master, dan menjalankan backup.
        </p>
        <div class="help-callout" data-help-item>
            <i class="fi fi-rr-info"></i>
            <span>Gunakan menu di sidebar kiri. Bagian <strong>Menu Utama</strong> (Dashboard, Arsip, Log) untuk memantau, bagian <strong>Administrasi</strong> (Kelola Pengguna, Data Master, Backup) untuk mengelola sistem.</span>
        </div>
    </div>
</section>

{{-- DASHBOARD --}}
<section class="section-card help-section" id="dashboard">
    <div class="section-card__header d-flex flex-column align-items-start">
        <span class="section-card__title">Dashboard Sistem</span>
        <span class="section-card__subtitle">Halaman pertama saat masuk — ringkasan kesehatan sistem secara cepat.</span>
    </div>
    <div class="section-card__body">
        <p class="help-lead" data-help-item>Empat kartu di atas dashboard menampilkan kondisi sistem terkini:</p>
        <div class="help-defs" data-help-item>
            <div class="help-def">
                <div class="help-def__term">Total Pengguna Aktif</div>
                <div class="help-def__desc">Jumlah akun berstatus aktif yang bisa login. Akun nonaktif tidak dihitung.</div>
            </div>
            <div class="help-def">
                <div class="help-def__term">Kapasitas Storage Terpakai</div>
                <div class="help-def__desc">Persentase pemakaian penyimpanan backup terhadap kapasitas 30&nbsp;GB. Pantau agar tidak penuh.</div>
            </div>
            <div class="help-def">
                <div class="help-def__term">Status Backup Terakhir</div>
                <div class="help-def__desc">Hasil backup paling baru. "Belum Ada" berarti backup belum pernah dibuat.</div>
            </div>
            <div class="help-def">
                <div class="help-def__term">Kejadian Keamanan Hari Ini</div>
                <div class="help-def__desc">Jumlah peristiwa keamanan yang tercatat hari ini. Idealnya 0; jika ada, periksa Log Aktivitas.</div>
            </div>
        </div>
        <div class="help-callout" data-help-item>
            <i class="fi fi-rr-time-past"></i>
            <span>Daftar <strong>aktivitas terbaru</strong> di dashboard adalah ringkasan dari Log Aktivitas. Untuk pencarian &amp; filter lengkap, buka menu Log Aktivitas.</span>
        </div>
    </div>
</section>

{{-- ARSIP --}}
<section class="section-card help-section" id="arsip">
    <div class="section-card__header d-flex flex-column align-items-start">
        <span class="section-card__title">Arsip Laporan</span>
        <span class="section-card__subtitle">Kumpulan laporan dari ketiga divisi dalam satu tempat.</span>
    </div>
    <div class="section-card__body">
        <p class="help-lead" data-help-item>Dari sini Anda bisa menemukan laporan lama dan mengelolanya. Langkah umum:</p>
        <div class="help-steps" data-help-item>
            <div class="help-step">
                <span class="help-step__num">1</span>
                <div>
                    <div class="help-step__title">Cari laporan</div>
                    <div class="help-step__text">Ketik di kolom pencarian (ID dokumen, tanggal, regu, nama kapal, karyawan, atau isi laporan). Saran muncul saat mengetik; tekan Enter untuk mencari ke seluruh arsip.</div>
                </div>
            </div>
            <div class="help-step">
                <span class="help-step__num">2</span>
                <div>
                    <div class="help-step__title">Persempit dengan filter</div>
                    <div class="help-step__text">Gunakan filter Tanggal, Divisi, Regu, Shift, dan Status. Atur urutan Terbaru/Terlama. Tombol Reset mengembalikan ke kondisi awal.</div>
                </div>
            </div>
            <div class="help-step">
                <span class="help-step__num">3</span>
                <div>
                    <div class="help-step__title">Lihat, unduh, atau hapus</div>
                    <div class="help-step__text">Tombol Lihat membuka pratinjau laporan, Unduh menyimpan PDF, dan Hapus menghapus laporan dari arsip.</div>
                </div>
            </div>
        </div>
        <div class="help-callout help-callout--danger" data-help-item>
            <i class="fi fi-rr-trash"></i>
            <span><strong>Hapus bersifat permanen.</strong> Laporan yang dihapus tidak bisa dikembalikan dan tindakan ini dicatat ke Log Aktivitas. Unduh dulu PDF-nya bila masih diperlukan.</span>
        </div>
    </div>
</section>

{{-- LOG --}}
<section class="section-card help-section" id="log">
    <div class="section-card__header d-flex flex-column align-items-start">
        <span class="section-card__title">Log Aktivitas</span>
        <span class="section-card__subtitle">Rekam jejak siapa melakukan apa dan kapan.</span>
    </div>
    <div class="section-card__body">
        <p class="help-lead" data-help-item>Setiap tindakan penting tercatat otomatis. Jenis aktivitas yang direkam:</p>
        <div class="help-defs" data-help-item>
            <div class="help-def">
                <div class="help-def__term">Update</div>
                <div class="help-def__desc">Penambahan atau perubahan data (pengguna, data master, jadwal backup).</div>
            </div>
            <div class="help-def">
                <div class="help-def__term">Delete</div>
                <div class="help-def__desc">Penghapusan data, mis. arsip laporan, pengguna, atau item master.</div>
            </div>
            <div class="help-def">
                <div class="help-def__term">Backup</div>
                <div class="help-def__desc">Pembuatan, pengunduhan, penghapusan backup, dan permintaan restore.</div>
            </div>
            <div class="help-def">
                <div class="help-def__term">Security</div>
                <div class="help-def__desc">Kejadian terkait keamanan; ikut dihitung pada kartu "Kejadian Keamanan Hari Ini".</div>
            </div>
        </div>
        <div class="help-callout" data-help-item>
            <i class="fi fi-rr-filter"></i>
            <span>Saring log dengan kolom pencarian (deskripsi, IP, nama/username) serta filter Tanggal, Role, dan Jenis. Daftar menampilkan hingga 60 entri terbaru.</span>
        </div>
    </div>
</section>

{{-- PENGGUNA --}}
<section class="section-card help-section" id="pengguna">
    <div class="section-card__header d-flex flex-column align-items-start">
        <span class="section-card__title">Kelola Pengguna</span>
        <span class="section-card__subtitle">Membuat dan merawat akun untuk manajer dan petugas tiap divisi.</span>
    </div>
    <div class="section-card__body">
        <p class="help-lead" data-help-item>Menambah atau mengubah pengguna:</p>
        <div class="help-steps" data-help-item>
            <div class="help-step">
                <span class="help-step__num">1</span>
                <div>
                    <div class="help-step__title">Buka form Tambah/Edit</div>
                    <div class="help-step__text">Klik "Tambah Pengguna" atau ikon edit pada baris pengguna.</div>
                </div>
            </div>
            <div class="help-step">
                <span class="help-step__num">2</span>
                <div>
                    <div class="help-step__title">Isi data akun</div>
                    <div class="help-step__text">Nama, Username (unik), Email (opsional, dibuat otomatis bila kosong), Role, Regu/Group, dan Status. Password minimal 6 karakter — saat edit, kosongkan bila tidak ingin mengganti.</div>
                </div>
            </div>
            <div class="help-step">
                <span class="help-step__num">3</span>
                <div>
                    <div class="help-step__title">Unggah tanda tangan (opsional)</div>
                    <div class="help-step__text">File harus <strong>PNG</strong>, maksimal <strong>2&nbsp;MB</strong>. Tanda tangan ini dipakai pada laporan yang membutuhkan paraf pengguna tersebut.</div>
                </div>
            </div>
        </div>
        <div class="help-defs" data-help-item>
            <div class="help-def">
                <div class="help-def__term">Aktif / Nonaktif</div>
                <div class="help-def__desc">Gunakan toggle status pada tabel untuk menonaktifkan akun tanpa menghapusnya. Akun nonaktif tidak bisa login.</div>
            </div>
            <div class="help-def">
                <div class="help-def__term">Hapus pengguna</div>
                <div class="help-def__desc">Menghapus akun secara permanen. Pertimbangkan menonaktifkan dulu bila hanya ingin menghentikan akses sementara.</div>
            </div>
        </div>
        <div class="help-callout help-callout--warn" data-help-item>
            <i class="fi fi-rr-shield-exclamation"></i>
            <span>Demi keamanan, <strong>akun admin yang sedang Anda pakai tidak bisa dinonaktifkan atau dihapus sendiri</strong>. Minta admin lain bila perlu mengubahnya.</span>
        </div>
    </div>
</section>

{{-- DATA MASTER --}}
<section class="section-card help-section" id="master">
    <div class="section-card__header d-flex flex-column align-items-start">
        <span class="section-card__title">Data Master</span>
        <span class="section-card__subtitle">Data acuan yang dipakai berulang saat petugas mengisi laporan.</span>
    </div>
    <div class="section-card__body">
        <p class="help-lead" data-help-item>Data Master terbagi menjadi enam tab. Pilih tab lewat submenu sidebar atau tab di halaman:</p>
        <div class="help-defs" data-help-item>
            <div class="help-def">
                <div class="help-def__term">Data Karyawan</div>
                <div class="help-def__desc">NPK, nama, regu, jabatan, divisi, dan waktu kerja. Menjadi pilihan nama saat mengisi laporan.</div>
            </div>
            <div class="help-def">
                <div class="help-def__term">Data Unit</div>
                <div class="help-def__desc">Kendaraan/alat berat. Nama unit dibentuk dari Tipe + Nomor unit; kategori "Masuk Cek Unit Operasional" menentukan unit yang muncul pada cek harian operasional.</div>
            </div>
            <div class="help-def">
                <div class="help-def__term">Data Truck</div>
                <div class="help-def__desc">Daftar truck beserta nomor plat dan keterangan.</div>
            </div>
            <div class="help-def">
                <div class="help-def__term">Data Inventaris</div>
                <div class="help-def__desc">Barang/perlengkapan beserta kategori dan stok.</div>
            </div>
            <div class="help-def">
                <div class="help-def__term">Data Lokasi K3 &amp; Item K3</div>
                <div class="help-def__desc">Lokasi dan item pemeriksaan yang dipakai pada laporan Safety/K3. Bisa diaktif/nonaktifkan.</div>
            </div>
        </div>
        <div class="help-callout" data-help-item>
            <i class="fi fi-rr-search"></i>
            <span>Tiap tab punya kolom pencarian dan filter cepat (mis. Regu, Divisi, Jabatan untuk Karyawan; Tipe &amp; Kategori untuk Unit). Gunakan untuk menemukan data sebelum mengedit.</span>
        </div>
        <div class="help-callout help-callout--warn" data-help-item>
            <i class="fi fi-rr-info"></i>
            <span>Perubahan data master <strong>tidak mengubah laporan lama</strong> yang sudah dibuat. Tetap berhati-hati saat menghapus data yang masih sering dipakai agar pilihan pada laporan baru tidak hilang.</span>
        </div>
    </div>
</section>

{{-- BACKUP --}}
<section class="section-card help-section" id="backup">
    <div class="section-card__header d-flex flex-column align-items-start">
        <span class="section-card__title">Manajemen Backup</span>
        <span class="section-card__subtitle">Mengamankan data dan meringankan penyimpanan server.</span>
    </div>
    <div class="section-card__body">
        <p class="help-lead" data-help-item>Tersedia tiga hal yang perlu dibedakan:</p>
        <div class="help-defs" data-help-item>
            <div class="help-def">
                <div class="help-def__term">Backup Manual</div>
                <div class="help-def__desc">Membuat snapshot cadangan saat itu juga. Cocok dilakukan sebelum perubahan besar.</div>
            </div>
            <div class="help-def">
                <div class="help-def__term">Jadwal Backup</div>
                <div class="help-def__desc">Mengatur Frekuensi (Harian/Mingguan/Bulanan), Jam, Retensi (14–90 hari), dan Target penyimpanan.</div>
            </div>
            <div class="help-def">
                <div class="help-def__term">Backup Tahunan</div>
                <div class="help-def__desc">Mengarsipkan seluruh laporan tahun sebelumnya ke satu file ZIP, lalu menghapusnya dari sistem untuk meringankan server.</div>
            </div>
        </div>
        <div class="help-callout help-callout--danger" data-help-item>
            <i class="fi fi-rr-exclamation"></i>
            <span><strong>Backup tahunan menghapus laporan dari database</strong> setelah arsip ZIP dibuat dan diverifikasi. Selalu <strong>unduh file ZIP-nya</strong> lalu simpan ke penyimpanan lokal Anda. Fitur ini hanya aktif bila sudah masuk tahun baru dan masih ada laporan tahun sebelumnya.</span>
        </div>
        <div class="help-callout help-callout--warn" data-help-item>
            <i class="fi fi-rr-rotate-right"></i>
            <span><strong>Restore tidak dijalankan otomatis.</strong> Menekan Restore hanya mencatat permintaan ke log; pemulihan data harus dilakukan manual oleh admin server (unduh file lalu impor ke database).</span>
        </div>
    </div>
</section>

{{-- STATUS & ALUR --}}
<section class="section-card help-section" id="status">
    <div class="section-card__header d-flex flex-column align-items-start">
        <span class="section-card__title">Alur &amp; Status Laporan</span>
        <span class="section-card__subtitle">Memahami status yang tampil pada Arsip Laporan.</span>
    </div>
    <div class="section-card__body">
        <div class="table-responsive-wrapper" data-help-item>
            <table class="help-table">
                <thead>
                    <tr>
                        <th>Status sistem</th>
                        <th>Tampilan</th>
                        <th>Arti</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>draft</code></td>
                        <td><span class="help-pill gray">Draft</span></td>
                        <td>Tersimpan oleh petugas, belum dikirim. Tidak tampil di arsip.</td>
                    </tr>
                    <tr>
                        <td><code>submitted</code></td>
                        <td><span class="help-pill orange">Diserahkan</span></td>
                        <td>Sudah dikirim petugas dan menunggu proses penerimaan/tinjauan.</td>
                    </tr>
                    <tr>
                        <td><code>acknowledged</code></td>
                        <td><span class="help-pill cyan">Diterima</span></td>
                        <td>Khusus Operasional: sudah diterima &amp; ditandatangani regu tujuan; siap ditanda tangani manajer.</td>
                    </tr>
                    <tr>
                        <td><code>approved</code></td>
                        <td><span class="help-pill blue">Diarsipkan</span></td>
                        <td>Sudah ditandatangani manajer dan resmi masuk Arsip Laporan.</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="help-callout" data-help-item>
            <i class="fi fi-rr-route"></i>
            <span><strong>Operasional:</strong> Draft → Diserahkan → Diterima (regu tujuan) → Diarsipkan (manajer). &nbsp;<strong>Pemeliharaan &amp; Safety:</strong> Draft → Diserahkan → Diarsipkan (manajer) — tanpa tahap "Diterima".</span>
        </div>
    </div>
</section>

{{-- AKSES --}}
<section class="section-card help-section" id="akses">
    <div class="section-card__header d-flex flex-column align-items-start">
        <span class="section-card__title">Peran &amp; Hak Akses</span>
        <span class="section-card__subtitle">Siapa bisa membuka apa. Tiap akun hanya diarahkan ke halamannya sendiri.</span>
    </div>
    <div class="section-card__body">
        <div class="table-responsive-wrapper" data-help-item>
            <table class="help-table">
                <thead>
                    <tr>
                        <th>Peran</th>
                        <th>Akses utama</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><span class="help-pill blue">Admin</span></td>
                        <td>Seluruh data sistem: arsip 3 divisi, log, kelola pengguna, data master, dan backup. Tidak mengisi laporan.</td>
                    </tr>
                    <tr>
                        <td><span class="help-pill green">Manajer</span></td>
                        <td>Meninjau &amp; menandatangani laporan masuk dari 3 divisi, serta melihat arsip.</td>
                    </tr>
                    <tr>
                        <td><span class="help-pill orange">Petugas Operasional</span></td>
                        <td>Membuat &amp; mengelola laporan operasional shift dan serah terima antar regu.</td>
                    </tr>
                    <tr>
                        <td><span class="help-pill orange">Pemeliharaan</span></td>
                        <td>Membuat &amp; mengelola laporan pemeliharaan.</td>
                    </tr>
                    <tr>
                        <td><span class="help-pill orange">Safety / K3</span></td>
                        <td>Membuat &amp; mengelola laporan K3/safety.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</section>

{{-- FAQ --}}
<section class="section-card help-section" id="faq">
    <div class="section-card__header d-flex flex-column align-items-start">
        <span class="section-card__title">Tanya Jawab (FAQ)</span>
        <span class="section-card__subtitle">Pertanyaan yang sering muncul saat mengelola sistem.</span>
    </div>
    <div class="section-card__body">
        <div class="faq-list">
            <details class="faq-item" data-help-item open>
                <summary><span>Apakah menghapus data master memengaruhi laporan yang sudah dibuat?</span><i class="fi fi-rr-angle-small-down"></i></summary>
                <div class="faq-item__body">Tidak. Laporan yang sudah dibuat menyimpan datanya sendiri sehingga tetap utuh. Namun data master yang dihapus tidak lagi muncul sebagai pilihan pada laporan baru, jadi hapus hanya data yang benar-benar tidak dipakai.</div>
            </details>
            <details class="faq-item" data-help-item>
                <summary><span>Apa beda backup manual, jadwal backup, dan backup tahunan?</span><i class="fi fi-rr-angle-small-down"></i></summary>
                <div class="faq-item__body">Backup manual membuat cadangan saat itu juga. Jadwal backup mengatur kapan backup berjalan otomatis beserta retensinya. Backup tahunan mengarsipkan seluruh laporan tahun lalu ke ZIP lalu menghapusnya dari sistem untuk menghemat penyimpanan — pastikan ZIP-nya diunduh dan disimpan.</div>
            </details>
            <details class="faq-item" data-help-item>
                <summary><span>Kenapa tombol Restore tidak langsung memulihkan data?</span><i class="fi fi-rr-angle-small-down"></i></summary>
                <div class="faq-item__body">Restore otomatis berisiko menimpa data yang sedang berjalan. Karena itu sistem hanya mencatat permintaan restore ke log; pemulihan dilakukan manual oleh admin server dengan mengunduh file backup lalu mengimpornya ke database.</div>
            </details>
            <details class="faq-item" data-help-item>
                <summary><span>Kenapa akun saya sendiri tidak bisa dinonaktifkan atau dihapus?</span><i class="fi fi-rr-angle-small-down"></i></summary>
                <div class="faq-item__body">Ini perlindungan agar sistem tidak kehilangan admin yang sedang aktif. Bila akun admin Anda harus diubah atau dihapus, lakukan dari akun admin lain.</div>
            </details>
            <details class="faq-item" data-help-item>
                <summary><span>Format tanda tangan apa yang didukung?</span><i class="fi fi-rr-angle-small-down"></i></summary>
                <div class="faq-item__body">Hanya file gambar <strong>PNG</strong> dengan ukuran maksimal <strong>2&nbsp;MB</strong>. Disarankan PNG berlatar transparan agar rapi saat tampil di laporan.</div>
            </details>
            <details class="faq-item" data-help-item>
                <summary><span>Laporan apa saja yang muncul di Arsip admin?</span><i class="fi fi-rr-angle-small-down"></i></summary>
                <div class="faq-item__body">Arsip admin menampilkan laporan dari ketiga divisi (Operasional, Pemeliharaan, Safety) yang sudah berstatus diserahkan/diarsipkan. Gunakan filter Divisi untuk fokus pada satu divisi.</div>
            </details>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var scroller = document.querySelector('.page-content');
        var tabsWrap = document.getElementById('helpTabs');
        var indicator = document.getElementById('helpTabIndicator');
        var tabs = Array.prototype.slice.call(document.querySelectorAll('.help-tab'));
        var sections = Array.prototype.slice.call(document.querySelectorAll('.help-section[id]'));
        var currentId = null;

        function tabFor(id) { return tabs.filter(function (t) { return t.getAttribute('data-tab') === id; })[0]; }

        function moveIndicator(tab) {
            if (!indicator) return;
            if (!tab || tab.classList.contains('is-hidden')) { indicator.style.width = '0px'; return; }
            indicator.style.width = tab.offsetWidth + 'px';
            indicator.style.transform = 'translateX(' + tab.offsetLeft + 'px)';
        }

        function setActive(id) {
            currentId = id;
            tabs.forEach(function (t) { t.classList.toggle('active', t.getAttribute('data-tab') === id); });
            var tab = tabFor(id);
            moveIndicator(tab);
            // pastikan tab aktif terlihat di dalam bar
            if (tab && tabsWrap) {
                var left = tab.offsetLeft, right = left + tab.offsetWidth;
                if (left < tabsWrap.scrollLeft) tabsWrap.scrollTo({ left: left - 16, behavior: 'smooth' });
                else if (right > tabsWrap.scrollLeft + tabsWrap.clientWidth) tabsWrap.scrollTo({ left: right - tabsWrap.clientWidth + 16, behavior: 'smooth' });
            }
        }

        function jumpTo(id) {
            var target = document.getElementById(id);
            if (target) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        // Saat scroll dipicu klik tab, kunci scroll-spy sebentar agar tab aktif tidak "loncat".
        var spyLocked = false, spyTimer = null;
        function lockSpy() { spyLocked = true; clearTimeout(spyTimer); spyTimer = setTimeout(function () { spyLocked = false; }, 650); }

        tabs.forEach(function (tab) {
            tab.addEventListener('click', function () {
                var id = tab.getAttribute('data-tab');
                setActive(id);   // langsung pindahkan tab aktif saat diklik
                lockSpy();
                jumpTo(id);
            });
        });

        // Scroll-spy: pilih section paling atas yang terlihat di bawah tab bar.
        var visible = {};
        if ('IntersectionObserver' in window && scroller) {
            var observer = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) { visible[entry.target.id] = entry.isIntersecting; });
                if (spyLocked) return;
                // Di dasar halaman, section terakhir mungkin tak bisa naik penuh — paksa tab terakhir aktif.
                if (scroller.scrollTop + scroller.clientHeight >= scroller.scrollHeight - 4) {
                    setActive(sections[sections.length - 1].id);
                    return;
                }
                for (var i = 0; i < sections.length; i++) {
                    if (visible[sections[i].id]) { if (sections[i].id !== currentId) setActive(sections[i].id); break; }
                }
            }, { root: scroller, rootMargin: '-96px 0px -55% 0px', threshold: 0 });
            sections.forEach(function (s) { observer.observe(s); });
        }

        // State awal
        if (sections.length) setActive(sections[0].id);
        window.addEventListener('resize', function () { moveIndicator(tabFor(currentId)); });

        // Animasi buka/tutup FAQ yang halus (Web Animations API pada elemen <details>).
        // Menganimasikan tinggi seluruh <details> sehingga buka & tutup sama mulus,
        // dan klik di tengah animasi langsung membalik arah tanpa patahan.
        var FAQ_DURATION = 340;
        var FAQ_EASING = 'cubic-bezier(0.4, 0, 0.2, 1)';
        document.querySelectorAll('.faq-item').forEach(function (item) {
            var summary = item.querySelector('summary');
            var body = item.querySelector('.faq-item__body');
            if (!summary || !body) return;

            if (item.open) item.classList.add('is-open');

            var animation = null;
            var isClosing = false;
            var isExpanding = false;

            summary.addEventListener('click', function (e) {
                e.preventDefault();
                item.style.overflow = 'hidden';
                if (isClosing || !item.open) {
                    openFaq();
                } else if (isExpanding || item.open) {
                    shrinkFaq();
                }
            });

            function animateHeight(startHeight, endHeight, onDone) {
                if (animation) animation.cancel();
                animation = item.animate(
                    { height: [startHeight, endHeight] },
                    { duration: FAQ_DURATION, easing: FAQ_EASING }
                );
                animation.onfinish = onDone;
            }

            function shrinkFaq() {
                isClosing = true;
                item.classList.remove('is-open');
                animateHeight(item.offsetHeight + 'px', summary.offsetHeight + 'px', function () {
                    finishFaq(false);
                });
            }

            function openFaq() {
                item.style.height = item.offsetHeight + 'px';
                item.open = true;
                item.classList.add('is-open');
                window.requestAnimationFrame(expandFaq);
            }

            function expandFaq() {
                isExpanding = true;
                animateHeight(item.offsetHeight + 'px', (summary.offsetHeight + body.offsetHeight) + 'px', function () {
                    finishFaq(true);
                });
            }

            function finishFaq(open) {
                item.open = open;
                animation = null;
                isClosing = false;
                isExpanding = false;
                item.style.height = '';
                item.style.overflow = '';
                item.classList.toggle('is-open', open);
            }
        });

        // Pencarian (filter langsung)
        var input = document.getElementById('helpSearch');
        var clearBtn = document.getElementById('helpSearchClear');
        var emptyState = document.getElementById('helpEmpty');
        var items = Array.prototype.slice.call(document.querySelectorAll('[data-help-item]'));

        function applySearch(raw) {
            var q = (raw || '').trim().toLowerCase();
            var anyVisible = false;

            items.forEach(function (item) {
                var match = q === '' || (item.textContent || '').toLowerCase().indexOf(q) !== -1;
                item.classList.toggle('is-hidden', !match);
                if (match) anyVisible = true;
            });

            sections.forEach(function (sec) {
                var secItems = sec.querySelectorAll('[data-help-item]');
                if (!secItems.length) return;
                var hasVisible = Array.prototype.some.call(secItems, function (i) { return !i.classList.contains('is-hidden'); });
                var hide = q !== '' && !hasVisible;
                sec.classList.toggle('is-hidden', hide);
                var tab = tabFor(sec.id);
                if (tab) tab.classList.toggle('is-hidden', hide);
            });

            if (emptyState) emptyState.classList.toggle('is-hidden', !(q !== '' && !anyVisible));
            if (clearBtn) clearBtn.classList.toggle('is-hidden', q === '');
            moveIndicator(tabFor(currentId));
        }

        if (input) {
            input.addEventListener('input', function () { applySearch(input.value); });
            input.addEventListener('keydown', function (e) { if (e.key === 'Escape') { input.value = ''; applySearch(''); } });
        }
        if (clearBtn) {
            clearBtn.addEventListener('click', function () { input.value = ''; applySearch(''); input.focus(); });
        }
    });
</script>
@endpush
