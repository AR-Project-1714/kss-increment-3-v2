@extends('admin.layouts.app')

@section('title', 'KSS Admin - Master Data')
@section('active', 'master')

@push('styles')
<style>
    /* =============================================
       SECTION CARD
       ============================================= */
    .section-card {
        background-color: var(--white);
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(37,99,235,0.07);
        transition: background-color 0.3s ease;
    }

    .section-card__title { font-size: 16px; font-weight: 600; color: var(--black); }

    /* =============================================
       BREADCRUMB
       ============================================= */
    .page-breadcrumb {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 12px;
        font-weight: 400;
    }
    .page-breadcrumb__root { color: var(--muted); }
    .page-breadcrumb__sep { color: var(--muted); font-size: 11px; display: flex; position: relative; top: 1px; }
    .page-breadcrumb__current { color: var(--black-secondary); font-weight: 500; }

    /* =============================================
       CARD HEADER — judul kartu sebaris dengan pencarian & filter.
       Anatominya disamakan dengan Kelola Pengguna: aksi utama
       (Tambah) di page header, pencarian + filter di header kartu.
       ============================================= */
    .archive-body { padding: 20px; display: flex; flex-direction: column; gap: 16px; }

    .master-card-header {
        position: relative;
        z-index: 15;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
    }

    /* Judul kartu + lencana jumlah data dibaca sebagai satu kesatuan, jadi
       keduanya dibungkus supaya toolbar tetap terdorong ke ujung kanan. */
    .master-card-heading {
        display: flex;
        align-items: center;
        gap: 10px;
        min-width: 0;
        flex-wrap: wrap;
    }

    /* Lencana jumlah — resep yang sama dengan .archive-count pada Arsip
       Laporan supaya penanda "berapa banyak data" seragam antar halaman. */
    .master-count {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 12px;
        border-radius: 999px;
        color: var(--blue-main);
        background-color: var(--blue-main-10);
        font-size: 10px;
        font-weight: 600;
        white-space: nowrap;
    }

    .master-count i { position: relative; top: 1px; font-size: 11px; }

    .master-toolbar {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-left: auto;
        min-width: 0;
    }

    .master-toolbar__search { min-width: 0; }

    .search-box {
        position: relative;
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 0 34px 0 12px;
        height: 38px;
        border: 1px solid var(--smooth-border);
        border-radius: 8px;
        background-color: var(--main-bg);
        width: 300px;
        max-width: 100%;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .search-box:focus-within { border-color: var(--blue-main); box-shadow: 0 0 0 3px var(--blue-main-10); }

    .search-box i { color: var(--muted); font-size: 13px; position: relative; top: 1px; }

    .search-box input {
        border: none;
        background: transparent;
        outline: none;
        font-family: inherit;
        font-size: 12px;
        color: var(--black);
        width: 100%;
        min-width: 0;
    }

    .search-box input::placeholder { color: var(--muted); }

    .search-box input[type="search"]::-webkit-search-cancel-button,
    .search-box input[type="search"]::-webkit-search-decoration {
        display: none;
        -webkit-appearance: none;
    }

    /* Tombol bersihkan menempati ruang kanan yang sudah dipesan lewat
       padding-inline-end, jadi teks tidak pernah berjalan di bawahnya. */
    .search-clear {
        position: absolute;
        right: 6px;
        top: 50%;
        transform: translateY(-50%);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 22px;
        height: 22px;
        padding: 0;
        border: none;
        border-radius: 50%;
        color: var(--blue-main);
        background-color: var(--blue-main-10);
        cursor: pointer;
        transition: .2s ease-out;
    }

    .search-clear:hover { background-color: var(--blue-main-25); }
    .search-clear[hidden] { display: none; }
    .search-clear i { top: 0; font-size: 11px; }

    .btn-tool {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 14px;
        border: 1px solid var(--smooth-border);
        border-radius: 8px;
        background-color: var(--white);
        color: var(--black-secondary);
        font-family: inherit;
        font-size: 12px;
        font-weight: 500;
        cursor: pointer;
        transition: 0.2s ease;
    }

    /* Seluruh glyph uicons memusatkan tintanya 0,5em di atas baseline, jadi
       kotak <i> harus turun 2px supaya tinta ikon benar-benar setinggi
       tengah tombol. Nilainya sama untuk semua ikon dan semua tinggi
       tombol karena isinya sudah di-center oleh flex. */
    .btn-tool i { position: relative; top: 2px; }
    .btn-tool--primary { background-color: var(--blue-main); border-color: var(--blue-main); color: #fff; }
    .btn-tool--primary:hover { background-color: var(--blue-hover); border-color: var(--blue-hover); color: #fff; }

    /* =============================================
       FILTER (pemicu ikon di header kartu + popover, pola Kelola Pengguna)
       ============================================= */

    .btn-tool:hover { background-color: var(--blue-main-5); border-color: var(--blue-main-25); color: var(--blue-main); }
    .btn-tool--primary:hover { background-color: var(--blue-hover); border-color: var(--blue-hover); color: #fff; }
    .btn-tool--active { background-color: var(--blue-main-10); border-color: var(--blue-main); color: var(--blue-main); }
    .btn-tool.is-hidden { display: none; }

    .btn-reset {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 16px;
        border: 1px solid var(--red-main);
        border-radius: 8px;
        background-color: transparent;
        color: var(--red-main);
        font-family: inherit;
        font-size: 12px;
        font-weight: 500;
        text-decoration: none;
        cursor: pointer;
        transition: 0.2s ease;
    }
    .btn-reset i { position: relative; top: 1px; }
    .btn-reset:hover { background-color: var(--red-main-10, rgba(239,68,68,0.08)); }
    .btn-reset.is-hidden { display: none; }

    /* Aksi utama (Tambah) duduk di ujung kanan page header — anatomi yang
       sama dengan Kelola Pengguna. */
    .performance-page-header {
        position: relative;
        z-index: 20;
        flex-direction: row;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
    }

    .performance-page-header__heading {
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    /* Aksi page header berdampingan: Ekspor Excel lalu Tambah. */
    .performance-page-header__actions {
        display: flex;
        align-items: center;
        gap: 8px;
        flex: 0 0 auto;
        flex-wrap: wrap;
    }

    .performance-page-header__action {
        flex: 0 0 auto;
        min-height: 38px;
        padding-inline: 14px;
        box-shadow: 0 5px 14px rgba(37, 99, 235, .18);
    }

    /* Ekspor bersifat sekunder terhadap Tambah, jadi tampil sebagai tombol
       bergaris hijau — resep yang sama dengan tombol Ekspor di Arsip Laporan. */
    .performance-export-button {
        flex: 0 0 auto;
        min-height: 38px;
        padding-inline: 14px;
        color: var(--success);
        border-color: var(--success);
        background-color: var(--success-10);
        text-decoration: none;
        box-shadow: none;
    }

    .performance-export-button:hover,
    .performance-export-button:focus-visible {
        color: var(--success);
        border-color: var(--success);
        background-color: var(--success-10);
    }

    .performance-filter { position: relative; flex: 0 0 auto; }

    /* Tombol ikon saja: dibuat bujur sangkar 38px agar tingginya persis
       sama dengan kotak pencarian di sebelahnya. */
    .performance-filter__trigger {
        width: 38px;
        height: 38px;
        padding: 0;
        justify-content: center;
        position: relative;
    }

    /* Glyph "settings-sliders" duduk lebih tinggi dari kotak em-nya sendiri
       (ruang kosong font berada di bawah, bukan di atas), jadi butuh offset
       positif ekstra supaya optically center di tombol persegi 38px. */
    .performance-filter__trigger i { top: 3px; font-size: 14px; }

    .performance-filter__trigger[aria-expanded="true"] {
        background-color: var(--blue-main-10);
        border-color: var(--blue-main);
        color: var(--blue-main);
    }

    .performance-filter__trigger--active {
        background-color: var(--blue-main);
        border-color: var(--blue-main);
        color: #fff;
    }

    .performance-filter__trigger--active:hover {
        background-color: var(--blue-hover);
        border-color: var(--blue-hover);
        color: #fff;
    }

    /* Titik penanda filter aktif — dipakai bersama ikon supaya tidak perlu
       label teks di tombol. */
    .performance-filter__status {
        position: absolute;
        top: -3px;
        right: -3px;
        width: 9px;
        height: 9px;
        border: 2px solid var(--white);
        border-radius: 50%;
        background-color: var(--orange-main);
    }

    .performance-filter__status.is-hidden { display: none; }

    .performance-filter__popover {
        position: absolute;
        top: calc(100% + 10px);
        right: 0;
        z-index: 40;
        width: min(620px, calc(100vw - 112px));
        padding: 16px;
        /* Frosted bersama — lihat resources/css/components/frosted-surface.css. */
        border: 1px solid var(--kss-frost-border);
        border-radius: 14px;
        background-color: var(--kss-frost-surface);
        -webkit-backdrop-filter: var(--kss-frost-filter);
        backdrop-filter: var(--kss-frost-filter);
        box-shadow: inset 0 1px 0 var(--kss-frost-edge), var(--kss-frost-shadow);
    }

    .performance-filter__popover[hidden] { display: none; }

    .performance-filter__head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
        margin-bottom: 14px;
        padding-bottom: 12px;
        border-bottom: 1px solid var(--smooth-border);
    }

    .performance-filter__title {
        display: block;
        font-size: 13px;
        font-weight: 600;
        color: var(--black);
    }

    .performance-filter__subtitle {
        display: block;
        margin-top: 2px;
        font-size: 10px;
        color: var(--muted);
    }

    .performance-filter__close {
        width: 30px;
        height: 30px;
        flex: 0 0 auto;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0;
        border: 1px solid var(--smooth-border);
        border-radius: 8px;
        color: var(--black-secondary);
        background-color: transparent;
        cursor: pointer;
    }

    .performance-filter__actions {
        display: flex;
        justify-content: flex-end;
        gap: 8px;
        margin-top: 14px;
    }

    /* Satu grup bidang per pane; hanya grup pane aktif yang ditampilkan
       (display diatur JS, karena select pane lain juga dinonaktifkan). */
    .master-filter-group {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        align-items: end;
        gap: 12px;
    }

    .master-filter-group[data-filter-pane="unit"] {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .master-filter-group .filter-field { max-width: none; }

    /* Selaras dengan popover Arsip Laporan dan Log Aktivitas: bidang di dalam
       popover setinggi 36px, satu tingkat lebih rapat dari kontrol toolbar. */
    .master-filter-group .filter-field .filter-input,
    .master-filter-group .filter-field .filter-select-trigger { height: 36px; }

    .filter-field { display: flex; flex: 1 1 160px; min-width: 0; flex-direction: column; gap: 4px; }
    .filter-field label { font-size: 10px; font-weight: 500; color: var(--black-secondary); }

    /* Base input look (dipakai trigger custom dropdown) */
    .filter-input {
        font-family: inherit;
        font-size: 12px;
        color: var(--black);
        background-color: var(--white);
        border: 1px solid var(--smooth-border);
        border-radius: 8px;
        padding: 8px 12px;
        cursor: pointer;
        outline: none;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .filter-field .filter-input { width: 100%; min-width: 0; height: 38px; display: flex; align-items: center; }

    /* Custom dropdown (pola Arsip Laporan) */
    .native-select { display: none; }

    .filter-select-wrapper { position: relative; width: 100%; min-width: 0; }

    .filter-select-trigger { display: flex; align-items: center; padding-right: 34px; cursor: pointer; }
    .filter-select-trigger span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .filter-select-trigger.focus-active { border-color: var(--blue-main); box-shadow: 0 0 0 3px var(--blue-main-10); }

    .select-arrow {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--blue-main);
        font-size: 14px;
        pointer-events: none;
        display: flex;
        transition: transform 0.2s ease;
    }
    .filter-select-trigger.focus-active ~ .select-arrow { transform: translateY(-50%) rotate(180deg); }

    .filter-select-options {
        position: absolute;
        top: calc(100% + 5px);
        left: 0;
        right: 0;
        background-color: var(--white);
        border: 1px solid var(--smooth-border);
        border-radius: 8px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        z-index: 999;
        display: none;
        max-height: 220px;
        overflow-y: auto;
        padding: 6px 0;
    }
    .filter-select-options.open { display: block; animation: fadeIn 0.2s ease-out; }

    .filter-select-option {
        padding: 9px 14px;
        font-size: 12px;
        color: var(--black-secondary);
        cursor: pointer;
        transition: background-color 0.2s ease, color 0.2s ease;
    }
    .filter-select-option:hover { background-color: var(--blue-main-10); color: var(--blue-main); }
    .filter-select-option.selected {
        background-color: var(--blue-main-5);
        color: var(--blue-main);
        border-left: 3px solid var(--blue-main);
        font-weight: 500;
    }

    @media (max-width: 720px) {
        .master-card-header { align-items: stretch; }

        .master-toolbar {
            width: 100%;
            margin-left: 0;
        }

        .master-toolbar__search { flex: 1 1 auto; }
        .search-box { width: 100%; }

        .master-filter-group { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }

    @media (max-width: 640px) {
        .filter-field { max-width: none; }

        .performance-page-header { flex-direction: column; }
        .performance-page-header__actions { width: 100%; }

        .performance-page-header__actions > * {
            flex: 1 1 0;
            justify-content: center;
        }

        /* Tetap ditambatkan ke tepi kanan tombol. Membaliknya ke `left: 0`
           justru membuat panel keluar layar, karena titik acuannya adalah
           .performance-filter yang sudah berada di ujung kanan kartu. */
        .performance-filter__popover { width: min(620px, calc(100vw - 48px)); }

        .performance-filter__actions > * {
            flex: 1 1 0;
            justify-content: center;
            text-align: center;
        }

        .master-filter-group,
        .master-filter-group[data-filter-pane="unit"] { grid-template-columns: 1fr; }
    }

    /* =============================================
       MASTER DATA PANES (Tab switching)
       ============================================= */
    .master-pane { display: none; }
    .master-pane.active { display: block; }

    /* =============================================
       TABLE
       ============================================= */
    .table-responsive-wrapper {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: thin;
        scrollbar-color: var(--blue-main-25) transparent;
    }

    .table-responsive-wrapper::-webkit-scrollbar { height: 6px; }
    .table-responsive-wrapper::-webkit-scrollbar-track { background: transparent; border-radius: 10px; }
    .table-responsive-wrapper::-webkit-scrollbar-thumb { background-color: var(--blue-main-25); border-radius: 10px; }
    .table-responsive-wrapper::-webkit-scrollbar-thumb:hover { background-color: var(--blue-main-40); }

    .table-responsive-wrapper table { min-width: 960px; width: 100%; }

    /* Jumlah min-width seluruh kolom unit: 50+64+150+85+150+110+120+130+110+80+180.
       Tanpa ini baris meluber keluar kotak <tr> begitu kolom Merk dilebarkan. */
    .table-responsive-wrapper table.unit-table { min-width: 1229px; }
    .table-responsive-wrapper .employee-table { min-width: 1260px; }

    .thead { background-color: var(--blue-main-5); border-radius: 6px; }

    .thead th {
        display: flex;
        padding: 10px;
        align-items: center;
        flex: 1 0 0;
        font-size: 12px;
        font-weight: 500;
        color: var(--black-secondary);
    }

    .tbody { border-bottom: 1px solid var(--smooth-border); transition: background-color 0.15s ease-in-out; }
    .tbody:hover { background-color: var(--blue-main-3); }

    .tbody td {
        display: flex;
        align-items: center;
        padding: 12px 10px;
        flex: 1 0 0;
        font-size: 12px;
        font-weight: 500;
        color: var(--black);
    }

    /* Columns */
    .thead th.col-no,       .tbody td.col-no       { width: 50px; flex: none; justify-content: center; padding: 12px 0; color: var(--black-secondary); }
    .thead th.col-logo,     .tbody td.col-logo     { width: 64px; flex: none; justify-content: center; padding: 8px 0; }
    .thead th.col-name,     .tbody td.col-name     { min-width: 150px; }
    .thead th.col-code,     .tbody td.col-code     { min-width: 85px; }
    /* 150px = teks merek terpanjang ("ISUZU NKR71 HARIMAU", terukur 130px)
       + padding sel 2x10px. Di bawah itu kolomnya terjepit sampai teks
       merek pecah jadi dua baris. */
    .thead th.col-brand,    .tbody td.col-brand    { min-width: 150px; white-space: nowrap; }
    .thead th.col-number,   .tbody td.col-number   { min-width: 110px; }
    .thead th.col-npk,      .tbody td.col-npk      { min-width: 110px; }
    .thead th.col-group,    .tbody td.col-group    { min-width: 100px; }
    .thead th.col-shiftgroup, .tbody td.col-shiftgroup { min-width: 165px; }
    .thead th.col-position, .tbody td.col-position { min-width: 100px; }
    .thead th.col-division, .tbody td.col-division { min-width: 120px; }
    .thead th.col-worktime, .tbody td.col-worktime { min-width: 110px; }
    .thead th.col-type,     .tbody td.col-type     { min-width: 120px; }
    .thead th.col-year,     .tbody td.col-year     { min-width: 80px; }
    .thead th.col-plate,    .tbody td.col-plate    { min-width: 120px; }
    .thead th.col-desc,     .tbody td.col-desc     { min-width: 200px; flex: 2 0 0; }
    .thead th.col-category, .tbody td.col-category { min-width: 130px; }
    .thead th.col-opscheck, .tbody td.col-opscheck { min-width: 110px; }
    .thead th.col-stock,    .tbody td.col-stock    { min-width: 90px; }
    .thead th.col-order,    .tbody td.col-order    { min-width: 90px; }
    .thead th.col-count,    .tbody td.col-count    { min-width: 110px; }
    .thead th.col-status,   .tbody td.col-status   { min-width: 110px; }
    .thead th.col-qtyflag,  .tbody td.col-qtyflag  { min-width: 120px; }
    .thead th.col-aksi,     .tbody td.col-aksi     { min-width: 180px; gap: 8px; flex-wrap: nowrap; }

    /* Lencana logo merek unit. Latar putih tetap dipakai di mode gelap karena
       berkas logo dibuat untuk latar terang — tanpa itu logo berwarna gelap
       (Komatsu, Yale) hilang di atas permukaan gelap. Rasio asli dijaga lewat
       object-fit: contain, jadi logo lebar maupun kotak sama-sama utuh. */
    .brand-logo {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 46px;
        height: 34px;
        padding: 3px;
        overflow: hidden;
        border: 1px solid var(--smooth-border);
        border-radius: 8px;
        background-color: #fff;
    }

    .brand-logo img { width: 100%; height: 100%; object-fit: contain; }
    .brand-logo [hidden] { display: none; }

    /* Cadangan saat merek tidak punya berkas logo atau gambarnya gagal dimuat. */
    .brand-logo--fallback {
        background-color: var(--blue-main-10);
        border-color: var(--blue-main-25);
        color: var(--blue-main);
        font-size: 10px;
        font-weight: 700;
        letter-spacing: .02em;
    }

    /* Employee table: hierarchy and scan-friendly metadata */
    .employee-table .tbody td {
        min-height: 56px;
    }

    .employee-table .col-name {
        font-weight: 600;
    }

    .employee-name {
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .employee-npk {
        color: var(--black-secondary);
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        font-size: 11px;
        font-weight: 600;
        letter-spacing: .015em;
        white-space: nowrap;
    }

    .employee-npk--empty {
        color: var(--muted);
        font-family: inherit;
        font-weight: 400;
        letter-spacing: 0;
        opacity: .72;
    }

    .division-badge,
    .position-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        min-height: 28px;
        padding: 5px 10px;
        border-radius: 999px;
        font-size: 10px;
        font-weight: 600;
        line-height: 1.2;
        white-space: nowrap;
    }

    .division-badge i,
    .position-badge i {
        position: relative;
        top: 1px;
        flex: 0 0 auto;
        font-size: 11px;
    }

    .division-badge.operasional {
        color: var(--blue-main);
        background-color: var(--blue-main-10);
    }

    .division-badge.pemeliharaan {
        color: var(--orange-main);
        background-color: var(--orange-main-10);
    }

    .division-badge.safety {
        color: var(--success);
        background-color: var(--success-10);
    }

    .division-badge.office {
        color: var(--cyan-main);
        background-color: var(--cyan-main-10);
    }

    .division-badge.keduanya {
        color: var(--blue-main);
        background: linear-gradient(135deg, var(--blue-main-10), var(--success-10));
    }

    .position-badge {
        max-width: 100%;
        color: var(--blue-main);
        background-color: var(--blue-main-10);
    }

    .position-badge.position-badge--lead {
        color: var(--orange-main);
        background-color: var(--orange-main-10);
    }

    .position-badge span {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .employee-muted-value {
        color: var(--muted);
        font-weight: 400;
        opacity: .72;
    }

    .employee-table td.col-aksi form { margin: 0; }

    /* Action buttons */
    td.col-aksi .btn-act {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        padding: 7px 10px;
        border: none;
        border-radius: 6px;
        color: #fff;
        font-family: inherit;
        font-size: 10px;
        font-weight: 500;
        white-space: nowrap;
        cursor: pointer;
        transition: 0.2s ease-out;
    }

    td.col-aksi .btn-act i { position: relative; top: 1px; }
    td.col-aksi .btn-act.edit { background-color: var(--blue-main); }
    td.col-aksi .btn-act.edit:hover { background-color: var(--blue-hover); transform: translateY(-1px); }
    td.col-aksi .btn-act.delete { background-color: var(--red-main); }
    td.col-aksi .btn-act.delete:hover { background-color: var(--red-hover); transform: translateY(-1px); }
</style>
@endpush

@section('content')
@php
    $employees = $employees ?? collect([
        ['no' => 1, 'npk' => '2000.1.010', 'name' => 'Mustari S,T',         'group' => 'Kantor', 'position' => 'Admin'],
        ['no' => 2, 'npk' => '2000.1.011', 'name' => 'Budi Santoso',        'group' => 'Regu A', 'position' => 'Operator'],
        ['no' => 3, 'npk' => '2000.1.012', 'name' => 'Andi Wijaya',         'group' => 'Regu B', 'position' => 'Mekanik'],
        ['no' => 4, 'npk' => '2000.1.013', 'name' => 'Siti Aminah',         'group' => 'Kantor', 'position' => 'Staff'],
    ]);
    $units = $units ?? collect([
        ['no' => 1, 'name' => 'Excavator PC200',    'type' => 'Alat Berat'],
        ['no' => 2, 'name' => 'Dump Truck HD465',   'type' => 'Kendaraan'],
        ['no' => 3, 'name' => 'Bulldozer D85ESS',   'type' => 'Alat Berat'],
        ['no' => 4, 'name' => 'Wheel Loader WA380', 'type' => 'Alat Berat'],
    ]);
    $trucks = $trucks ?? collect([
        ['no' => 1, 'name' => 'Hino 500',        'plate' => 'B 9012 KSS', 'desc' => 'Truk Angkut Material'],
        ['no' => 2, 'name' => 'Mitsubishi Fuso', 'plate' => 'B 9013 KSS', 'desc' => 'Truk Tangki Air'],
        ['no' => 3, 'name' => 'Isuzu Giga',      'plate' => 'B 9014 KSS', 'desc' => 'Truk Angkut Batu'],
        ['no' => 4, 'name' => 'Scania P360',     'plate' => 'B 9015 KSS', 'desc' => 'Truk Trailer'],
    ]);
    $inventories = $inventories ?? collect([
        ['no' => 1, 'name' => 'Helm Safety',        'category' => 'APD'],
        ['no' => 2, 'name' => 'Sepatu Boots',       'category' => 'APD'],
        ['no' => 3, 'name' => 'Oli Mesin SAE 40',   'category' => 'Sparepart'],
        ['no' => 4, 'name' => 'Ban Truck 1000-20',  'category' => 'Sparepart'],
    ]);
    $safetyLocations = $safetyLocations ?? collect([]);
    $safetyItems = $safetyItems ?? collect([]);
    $masterActions = $masterActions ?? [
        'karyawan' => ['store' => '#'],
        'unit' => ['store' => '#'],
        'truck' => ['store' => '#'],
        'inventaris' => ['store' => '#'],
        'lingkungan' => ['store' => '#'],
        'safety_lokasi' => ['store' => '#'],
        'safety_item' => ['store' => '#'],
    ];
    $environments = $environments ?? collect([]);
    $masterUi = [
        'karyawan'      => ['title' => 'Data Karyawan',   'search' => 'Cari Karyawan',   'add' => 'Tambah Karyawan',   'icon' => 'fi fi-rr-user-add'],
        'unit'          => ['title' => 'Data Unit',       'search' => 'Cari Unit',       'add' => 'Tambah Unit',       'icon' => 'fi fi-rr-add'],
        'truck'         => ['title' => 'Data Truck',      'search' => 'Cari Truck',      'add' => 'Tambah Truck',      'icon' => 'fi fi-rr-add'],
        'inventaris'    => ['title' => 'Data Inventaris', 'search' => 'Cari Inventaris', 'add' => 'Tambah Inventaris', 'icon' => 'fi fi-rr-add'],
        'lingkungan'    => ['title' => 'Data Lingkungan Operasi', 'search' => 'Cari Item Lingkungan', 'add' => 'Tambah Item', 'icon' => 'fi fi-rr-house-chimney'],
        'safety_lokasi' => ['title' => 'Data Lokasi K3',  'search' => 'Cari Lokasi K3',  'add' => 'Tambah Lokasi',     'icon' => 'fi fi-rr-marker'],
        'safety_item'   => ['title' => 'Data Item K3',    'search' => 'Cari Item K3',    'add' => 'Tambah Item',       'icon' => 'fi fi-rr-checkbox'],
    ];

    $activePane = $activePane ?? 'karyawan';
    $activeMasterUi = $masterUi[$activePane] ?? $masterUi['karyawan'];
    $masterSearch = $masterSearch ?? '';
    $masterFilters = $masterFilters ?? ['group' => '', 'division' => '', 'position' => '', 'type' => '', 'category' => ''];

    // Opsi filter dropdown (diselaraskan dengan modal tambah/edit).
    $filterGroupOptions = [
        '' => 'Semua Group', 'kantor' => 'Kantor', 'bengkel' => 'Bengkel',
        'Relief 1' => 'Relief 1', 'Relief 2' => 'Relief 2',
        'A' => 'Regu A', 'B' => 'Regu B', 'C' => 'Regu C', 'D' => 'Regu D',
        'OP7 A' => 'OP7 A', 'OP7 B' => 'OP7 B', 'OP7 C' => 'OP7 C', 'OP7 D' => 'OP7 D',
    ];
    $filterDivisionOptions = [
        '' => 'Semua Divisi', 'Operasional' => 'Operasional', 'Pemeliharaan' => 'Pemeliharaan', 'Safety' => 'Safety', 'Office' => 'Office',
    ];
    // Opsi Jabatan dibaca dari jabatan yang benar-benar dipakai karyawan
    // (AdminV2Controller::employeePositionFilterOptions), bukan daftar tetap
    // yang mudah ketinggalan begitu jabatan baru ditambahkan lewat modal.
    $filterPositionOptions = array_merge(['' => 'Semua Jabatan'], $masterPositionOptions ?? []);
    $unitTypeOptionList = ['Trailer', 'Tronton', 'Dump Truck', 'Minibus', 'Bus', 'Pickup', 'Forklift', 'Wheel Loader', 'Excavator'];
    $filterTypeOptions = array_merge(['' => 'Semua Tipe'], array_combine($unitTypeOptionList, $unitTypeOptionList));
    $filterCategoryOptions = [
        '' => 'Semua Kategori', 'truck' => 'Truck', 'bus' => 'Bus', 'heavy' => 'Heavy', '-' => 'Tanpa Kategori',
    ];

    $masterFilterActive = [
        'karyawan' => $masterFilters['group'] !== '' || $masterFilters['division'] !== '' || $masterFilters['position'] !== '',
        'unit' => $masterFilters['type'] !== '' || $masterFilters['category'] !== '',
    ];

    // Pencarian/filter hanya berlaku pada pane yang sedang aktif, jadi pesan
    // "tidak ada hasil" hanya muncul pada pane tersebut.
    $masterEmptyState = function (string $pane, string $singular, string $icon) use ($activePane, $masterSearch, $masterFilterActive): array {
        $isFiltering = $activePane === $pane && ($masterSearch !== '' || ($masterFilterActive[$pane] ?? false));

        return [
            'icon' => $isFiltering ? 'fi fi-rr-search' : $icon,
            'title' => $isFiltering ? 'Tidak ada '.$singular.' yang cocok' : 'Belum ada data '.$singular,
            'message' => $isFiltering
                ? 'Tidak ada '.$singular.' yang sesuai dengan pencarian atau filter aktif. Coba ubah kata kunci atau atur ulang filter.'
                : 'Tambahkan '.$singular.' baru lewat tombol di atas untuk melengkapi master data.',
        ];
    };
@endphp

@php
    $filterApplied = $masterFilterActive[$activePane] ?? false;
    $hasFilterPane = in_array($activePane, ['karyawan', 'unit'], true);

    // Jumlah data per pane. Pencarian & filter hanya diterapkan server pada
    // pane aktif, jadi pane lain menampilkan jumlah seluruh datanya.
    $masterTotalOf = fn ($rows): int => method_exists($rows, 'total') ? $rows->total() : count($rows);
    $masterCounts = [
        'karyawan' => $masterTotalOf($employees),
        'unit' => $masterTotalOf($units),
        'truck' => $masterTotalOf($trucks),
        'inventaris' => $masterTotalOf($inventories),
        'lingkungan' => $masterTotalOf($environments),
        'safety_lokasi' => $masterTotalOf($safetyLocations),
        'safety_item' => $masterTotalOf($safetyItems),
    ];

    // Satuan yang mengikuti angka. Pane aktif memakai "hasil" saat sedang
    // disaring, supaya angkanya tidak terbaca sebagai jumlah seluruh data.
    $masterCountNouns = [
        'karyawan' => 'karyawan',
        'unit' => 'unit',
        'truck' => 'truck',
        'inventaris' => 'inventaris',
        'lingkungan' => 'item',
        'safety_lokasi' => 'lokasi',
        'safety_item' => 'item',
    ];
    $masterIsNarrowed = $masterSearch !== '' || $filterApplied;
    $masterCountNoun = $masterIsNarrowed ? 'hasil' : ($masterCountNouns[$activePane] ?? 'data');

    // Parameter ekspor = pane + pencarian + filter yang sedang aktif, jadi
    // berkas Excel berisi tepat baris yang sedang dilihat pengguna.
    $masterExportQuery = array_filter([
        'pane' => $activePane,
        'q' => $masterSearch,
        'f_group' => $masterFilters['group'] ?? '',
        'f_division' => $masterFilters['division'] ?? '',
        'f_position' => $masterFilters['position'] ?? '',
        'f_type' => $masterFilters['type'] ?? '',
        'f_category' => $masterFilters['category'] ?? '',
    ], fn ($value): bool => filled($value));
    $masterExportUrl = route('admin.datamaster.export', $masterExportQuery);

    // Ikon lencana mengikuti jenis datanya, bukan ikon folder generik.
    $masterCountIcons = [
        'karyawan' => 'fi fi-rr-users',
        'unit' => 'fi fi-rr-truck-side',
        'truck' => 'fi fi-rr-truck-moving',
        'inventaris' => 'fi fi-rr-box-open',
        'lingkungan' => 'fi fi-rr-house-chimney',
        'safety_lokasi' => 'fi fi-rr-marker',
        'safety_item' => 'fi fi-rr-checkbox',
    ];
@endphp

<div class="page-header performance-page-header">
    <div class="performance-page-header__heading">
        <span class="page-title">Master Data</span>
        <div class="page-breadcrumb">
            <span class="page-breadcrumb__root">Data Master</span>
            <span class="page-breadcrumb__sep"><i class="fi fi-rr-angle-small-right"></i></span>
            <span class="page-breadcrumb__current" id="masterCrumb">{{ $activeMasterUi['title'] }}</span>
        </div>
    </div>

    <div class="performance-page-header__actions">
        {{-- Ekspor mengikuti pane, pencarian, dan filter yang sedang aktif.
             Tautannya dibangun ulang oleh JS saat pane berpindah. --}}
        <a href="{{ $masterExportUrl }}"
           class="btn-tool performance-export-button"
           id="masterExportBtn"
           data-export-base="{{ route('admin.datamaster.export') }}"
           data-confirm
           data-confirm-redirect="{{ $masterExportUrl }}"
           data-confirm-tone="success"
           data-confirm-title="Ekspor master data?"
           data-confirm-subtitle="Berkas Excel akan diunduh sesuai filter yang sedang aktif."
           data-confirm-message="Ekspor mengambil seluruh baris yang lolos pencarian dan filter saat ini, bukan hanya halaman yang tampil."
           data-confirm-summary="Format: Excel (.xlsx)"
           data-confirm-label="Ekspor Data"
           data-confirm-download="true"
           data-confirm-icon="fi fi-rr-cloud-upload-alt">
            <i class="fi fi-rr-cloud-upload-alt" aria-hidden="true"></i>
            <span>Ekspor Excel</span>
        </a>

        <button type="button" class="btn-tool btn-tool--primary performance-page-header__action" id="masterAddBtn">
            <i class="{{ $activeMasterUi['icon'] }}" id="masterAddIcon" aria-hidden="true"></i>
            <span id="masterAddText">{{ $activeMasterUi['add'] }}</span>
        </button>
    </div>
</div>

@component('admin.layouts.card')
    <div class="master-card-header">
        <div class="master-card-heading">
            <span class="section-card__title" id="masterTitle">{{ $activeMasterUi['title'] }}</span>
            <span class="master-count" id="masterCount">
                <i class="{{ $masterCountIcons[$activePane] ?? 'fi fi-rr-folder-open' }}" id="masterCountIcon" aria-hidden="true"></i>
                <span id="masterCountText">{{ $masterCounts[$activePane] ?? 0 }} {{ $masterCountNoun }}</span>
            </span>
        </div>

        <div class="master-toolbar">
            {{-- Pencarian tanpa tombol: form dikirim otomatis setelah ketikan
                 berhenti sejenak (debounce di skrip bawah). --}}
            <form class="master-toolbar__search"
                  method="GET"
                  action="{{ route('admin.datamaster') }}"
                  id="masterSearchForm"
                  autocomplete="off">
                <input type="hidden" name="pane" id="masterPaneInput" value="{{ $activePane ?? 'karyawan' }}">
                <div class="search-box">
                    <span><i class="fi fi-rr-search" aria-hidden="true"></i></span>
                    <input type="search"
                           name="q"
                           id="masterSearch"
                           value="{{ $masterSearch ?? '' }}"
                           data-initial-value="{{ $masterSearch ?? '' }}"
                           data-search-debounce="650"
                           placeholder="{{ $activeMasterUi['search'] }}"
                           aria-label="Cari data master">
                    <button type="button"
                            class="search-clear"
                            id="masterSearchClear"
                            aria-label="Bersihkan pencarian"
                            @if (($masterSearch ?? '') === '') hidden @endif>
                        <i class="fi fi-br-cross-small" aria-hidden="true"></i>
                    </button>
                </div>
            </form>

            {{-- Pemicu filter + popover. Bidangnya tetap milik #masterSearchForm
                 lewat atribut form=, jadi pencarian, pane, dan filter berangkat
                 sebagai satu permintaan meski markup-nya di luar elemen form. --}}
            <div class="performance-filter" data-master-filter>
                <button type="button"
                        @class([
                            'btn-tool',
                            'performance-filter__trigger',
                            'performance-filter__trigger--active' => $filterApplied,
                            'is-hidden' => ! $hasFilterPane,
                        ])
                        id="masterFilterBtn"
                        data-master-filter-trigger
                        aria-expanded="false"
                        aria-controls="master-filter-popover"
                        aria-label="Filter master data"
                        title="Filter master data">
                    <i class="fi fi-rr-settings-sliders" aria-hidden="true"></i>
                    <span @class(['performance-filter__status', 'is-hidden' => ! $filterApplied])
                          id="masterFilterStatus"
                          aria-hidden="true"></span>
                </button>

                <div class="performance-filter__popover"
                     id="master-filter-popover"
                     data-master-filter-popover
                     hidden>
                    <div class="performance-filter__head">
                        <div>
                            <span class="performance-filter__title">Filter Master Data</span>
                            <span class="performance-filter__subtitle" id="masterFilterSubtitle">Atur divisi, group, dan jabatan karyawan.</span>
                        </div>
                        <button type="button"
                                class="performance-filter__close"
                                data-master-filter-close
                                aria-label="Tutup filter">
                            <i class="fi fi-rr-cross-small" aria-hidden="true"></i>
                        </button>
                    </div>

                    {{-- Filter Karyawan --}}
                    <div class="master-filter-group" data-filter-pane="karyawan" @style(['display:none' => $activePane !== 'karyawan'])>
                        <div class="filter-field">
                            <label>Divisi</label>
                            <div class="filter-select-wrapper">
                                <select name="f_division" form="masterSearchForm" class="native-select js-master-filter" aria-label="Filter Divisi">
                                    @foreach ($filterDivisionOptions as $val => $label)
                                        <option value="{{ $val }}" @selected($masterFilters['division'] === $val)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <i class="fi fi-rr-angle-small-down select-arrow"></i>
                            </div>
                        </div>
                        <div class="filter-field">
                            <label>Group</label>
                            <div class="filter-select-wrapper">
                                <select name="f_group" form="masterSearchForm" class="native-select js-master-filter" aria-label="Filter Group">
                                    @foreach ($filterGroupOptions as $val => $label)
                                        <option value="{{ $val }}" @selected($masterFilters['group'] === $val)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <i class="fi fi-rr-angle-small-down select-arrow"></i>
                            </div>
                        </div>
                        <div class="filter-field">
                            <label>Jabatan</label>
                            <div class="filter-select-wrapper">
                                <select name="f_position" form="masterSearchForm" class="native-select js-master-filter" aria-label="Filter Jabatan">
                                    @foreach ($filterPositionOptions as $val => $label)
                                        <option value="{{ $val }}" @selected($masterFilters['position'] === $val)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <i class="fi fi-rr-angle-small-down select-arrow"></i>
                            </div>
                        </div>
                    </div>

                    {{-- Filter Unit --}}
                    <div class="master-filter-group" data-filter-pane="unit" @style(['display:none' => $activePane !== 'unit'])>
                        <div class="filter-field">
                            <label>Tipe Unit</label>
                            <div class="filter-select-wrapper">
                                <select name="f_type" form="masterSearchForm" class="native-select js-master-filter" aria-label="Filter Tipe Unit">
                                    @foreach ($filterTypeOptions as $val => $label)
                                        <option value="{{ $val }}" @selected($masterFilters['type'] === $val)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <i class="fi fi-rr-angle-small-down select-arrow"></i>
                            </div>
                        </div>
                        <div class="filter-field">
                            <label>Kategori</label>
                            <div class="filter-select-wrapper">
                                <select name="f_category" form="masterSearchForm" class="native-select js-master-filter" aria-label="Filter Kategori">
                                    @foreach ($filterCategoryOptions as $val => $label)
                                        <option value="{{ $val }}" @selected($masterFilters['category'] === $val)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <i class="fi fi-rr-angle-small-down select-arrow"></i>
                            </div>
                        </div>
                    </div>

                    <div class="performance-filter__actions">
                        <a href="{{ route('admin.datamaster', ['pane' => $activePane]) }}"
                           @class(['btn-reset', 'is-hidden' => ! $filterApplied && $masterSearch === ''])
                           id="masterFilterReset">
                            <i class="fi fi-rr-refresh"></i> Reset
                        </a>
                        <button type="submit" form="masterSearchForm" class="btn-tool btn-tool--primary">
                            <i class="fi fi-rr-check" aria-hidden="true"></i> Terapkan
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- PANE: Master Employees -->
    <div class="master-pane {{ $activePane === 'karyawan' ? 'active' : '' }}" data-pane="karyawan">
        <div class="table-responsive-wrapper">
            <table class="employee-table">
                <tr class="thead d-flex justify-content-between align-items-center">
                    <th class="col-no">No</th>
                    <th class="col-npk">NPK</th>
                    <th class="col-name">Nama</th>
                    <th class="col-group">Group</th>
                    <th class="col-position">Posisi</th>
                    <th class="col-division">Divisi</th>
                    <th class="col-worktime">Jam Kerja</th>
                    <th class="col-shiftgroup">Penugasan Sementara</th>
                    <th class="col-aksi">Aksi</th>
                </tr>
                @forelse ($employees as $e)
                    @php
                        $employeeName = $e['name'] ?? '-';
                        $divisionLabel = $e['division'] ?? 'Operasional';
                        $divisionKey = match (\Illuminate\Support\Str::lower($divisionLabel)) {
                            'pemeliharaan' => 'pemeliharaan',
                            'safety' => 'safety',
                            'office' => 'office',
                            'keduanya' => 'keduanya',
                            default => 'operasional',
                        };
                        $divisionIcon = match ($divisionKey) {
                            'pemeliharaan' => 'fi fi-rr-tools',
                            'safety' => 'fi fi-rr-shield-check',
                            'office' => 'fi fi-rr-briefcase',
                            'keduanya' => 'fi fi-rr-users-alt',
                            default => 'fi fi-rr-ship',
                        };
                        $positionLabel = $e['position'] ?? '-';
                        $normalizedPosition = \Illuminate\Support\Str::lower($positionLabel);
                        $positionIsLead = \Illuminate\Support\Str::contains(
                            $normalizedPosition,
                            ['kepala', 'karu', 'wakil', 'manager', 'kabag', 'kasi', 'koordinator', 'foreman']
                        );
                        $positionIcon = match (true) {
                            \Illuminate\Support\Str::contains($normalizedPosition, 'driver') => 'fi fi-sr-truck-side',
                            \Illuminate\Support\Str::contains($normalizedPosition, ['operator fl', 'forklift']) => 'fi fi-sr-forklift',
                            \Illuminate\Support\Str::contains($normalizedPosition, ['operator wl', 'exca', 'excavator']) => 'fi fi-sr-excavator',
                            \Illuminate\Support\Str::contains($normalizedPosition, 'checker') => 'fi fi-sr-search',
                            $positionIsLead => 'fi fi-sr-user-helmet-safety',
                            \Illuminate\Support\Str::contains($normalizedPosition, ['mekanik', 'pemeliharaan', 'peralatan']) => 'fi fi-sr-tools',
                            \Illuminate\Support\Str::contains($normalizedPosition, ['rigger', 'helper', 'operator']) => 'fi fi-sr-user-hard-work',
                            \Illuminate\Support\Str::contains($normalizedPosition, ['admin', 'staf', 'staff']) => 'fi fi-sr-briefcase',
                            default => 'fi fi-sr-id-badge',
                        };
                        $groupLabel = $e['group'] ?? '-';
                        $workTimeLabel = $e['work_time'] ?? '-';
                        $shiftGroupLabel = $e['shift_group'] ?? '-';
                        $hasShiftAssignment = filled($shiftGroupLabel) && $shiftGroupLabel !== '-';
                    @endphp
                    <tr class="tbody d-flex justify-content-between align-items-center" data-update-url="{{ $e['update_url'] ?? '' }}">
                        <td class="col-no">{{ $e['no'] }}</td>
                        <td class="col-npk" data-value="{{ $e['npk'] }}">
                            <span class="employee-npk {{ $e['npk'] === '-' ? 'employee-npk--empty' : '' }}">
                                {{ $e['npk'] === '-' ? 'Belum ada' : $e['npk'] }}
                            </span>
                        </td>
                        <td class="col-name" data-value="{{ $employeeName }}">
                            <span class="employee-name" title="{{ $employeeName }}">{{ $employeeName }}</span>
                        </td>
                        <td class="col-group" data-value="{{ $groupLabel }}">{{ $groupLabel }}</td>
                        <td class="col-position" data-value="{{ $positionLabel }}">
                            @if ($positionLabel !== '-')
                                <span class="position-badge {{ $positionIsLead ? 'position-badge--lead' : '' }}" title="{{ $positionLabel }}">
                                    <i class="{{ $positionIcon }}" aria-hidden="true"></i>
                                    <span>{{ $positionLabel }}</span>
                                </span>
                            @else
                                <span class="employee-muted-value">Belum diisi</span>
                            @endif
                        </td>
                        <td class="col-division" data-value="{{ $divisionLabel }}">
                            <span class="division-badge {{ $divisionKey }}" title="Divisi {{ $divisionLabel }}">
                                <i class="{{ $divisionIcon }}" aria-hidden="true"></i>
                                <span>{{ $divisionLabel }}</span>
                            </span>
                        </td>
                        <td class="col-worktime" data-value="{{ $workTimeLabel }}">{{ $workTimeLabel }}</td>
                        <td class="col-shiftgroup" data-value="{{ $shiftGroupLabel }}">
                            @if ($hasShiftAssignment)
                                <span>{{ $shiftGroupLabel }}</span>
                            @else
                                <span class="employee-muted-value">Tidak ada</span>
                            @endif
                        </td>
                        <td class="col-aksi">
                            <button type="button" class="btn-act edit js-master-edit" aria-label="Edit {{ $employeeName }}" title="Edit {{ $employeeName }}"><i class="fi fi-rr-pencil"></i> Edit</button>
                            <form method="POST" action="{{ $e['destroy_url'] ?? '#' }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-act delete js-master-delete" aria-label="Hapus {{ $employeeName }}" title="Hapus {{ $employeeName }}"><i class="fi fi-rr-trash"></i> Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    @include('admin.layouts.empty-state', $masterEmptyState('karyawan', 'karyawan', 'fi fi-rr-users-alt'))
                @endforelse
            </table>
        </div>
    </div>

    <!-- PANE: Master Units -->
    <div class="master-pane {{ $activePane === 'unit' ? 'active' : '' }}" data-pane="unit">
        <div class="table-responsive-wrapper">
            <table class="unit-table">
                <tr class="thead d-flex justify-content-between align-items-center">
                    <th class="col-no">No</th>
                    <th class="col-logo">Logo</th>
                    <th class="col-name">Nama</th>
                    <th class="col-code">Kode</th>
                    <th class="col-brand">Merk</th>
                    <th class="col-number">Plat</th>
                    <th class="col-type">Tipe</th>
                    <th class="col-category">Kategori</th>
                    <th class="col-opscheck">Cek Unit</th>
                    <th class="col-year">Tahun</th>
                    <th class="col-aksi">Aksi</th>
                </tr>
                @forelse ($units as $u)
                    <tr class="tbody d-flex justify-content-between align-items-center" data-update-url="{{ $u['update_url'] ?? '' }}">
                        <td class="col-no">{{ $u['no'] }}</td>
                        <td class="col-logo">
                            @php
                                $unitBrand = trim((string) ($u['brand'] ?? ''));
                                $unitBrandLogo = $u['brand_logo'] ?? '';
                                $unitBrandLabel = $unitBrand === '' || $unitBrand === '-' ? 'Tanpa merek' : $unitBrand;
                                // Inisial dua huruf dari kata pertama merek, mis. "KOBELCO" -> "KO".
                                $unitBrandInitials = $unitBrand === '' || $unitBrand === '-'
                                    ? '-'
                                    : mb_strtoupper(mb_substr(strtok($unitBrand, ' ') ?: $unitBrand, 0, 2));
                            @endphp
                            @if ($unitBrandLogo !== '')
                                <span class="brand-logo" title="{{ $unitBrandLabel }}">
                                    <img src="{{ $unitBrandLogo }}"
                                         alt="Logo {{ $unitBrandLabel }}"
                                         loading="lazy"
                                         onerror="this.closest('.brand-logo').classList.add('brand-logo--fallback'); this.hidden=true; this.nextElementSibling.hidden=false;">
                                    <span aria-hidden="true" hidden>{{ $unitBrandInitials }}</span>
                                </span>
                            @else
                                <span class="brand-logo brand-logo--fallback" title="{{ $unitBrandLabel }}" aria-label="Logo {{ $unitBrandLabel }} belum tersedia">
                                    {{ $unitBrandInitials }}
                                </span>
                            @endif
                        </td>
                        <td class="col-name">{{ $u['name'] }}</td>
                        <td class="col-code">{{ $u['unit_number'] }}</td>
                        <td class="col-brand">{{ $u['brand'] }}</td>
                        <td class="col-number">{{ $u['plate'] }}</td>
                        <td class="col-type">{{ $u['type'] }}</td>
                        <td class="col-category">{{ $u['macro_category'] }}</td>
                        <td class="col-opscheck">{{ $u['in_operational_check'] }}</td>
                        <td class="col-year">{{ $u['year'] }}</td>
                        <td class="col-aksi">
                            <button type="button" class="btn-act edit js-master-edit"><i class="fi fi-rr-pencil"></i> Edit</button>
                            <form method="POST" action="{{ $u['destroy_url'] ?? '#' }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-act delete js-master-delete"><i class="fi fi-rr-trash"></i> Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    @include('admin.layouts.empty-state', $masterEmptyState('unit', 'unit', 'fi fi-rr-truck-side'))
                @endforelse
            </table>
        </div>
    </div>

    <!-- PANE: Master Trucks -->
    <div class="master-pane {{ $activePane === 'truck' ? 'active' : '' }}" data-pane="truck">
        <div class="table-responsive-wrapper">
            <table>
                <tr class="thead d-flex justify-content-between align-items-center">
                    <th class="col-no">No</th>
                    <th class="col-name">Nama</th>
                    <th class="col-plate">Nomor Plat</th>
                    <th class="col-desc">Keterangan</th>
                    <th class="col-aksi">Aksi</th>
                </tr>
                @forelse ($trucks as $t)
                    <tr class="tbody d-flex justify-content-between align-items-center" data-update-url="{{ $t['update_url'] ?? '' }}">
                        <td class="col-no">{{ $t['no'] }}</td>
                        <td class="col-name">{{ $t['name'] }}</td>
                        <td class="col-plate">{{ $t['plate'] }}</td>
                        <td class="col-desc">{{ $t['desc'] }}</td>
                        <td class="col-aksi">
                            <button type="button" class="btn-act edit js-master-edit"><i class="fi fi-rr-pencil"></i> Edit</button>
                            <form method="POST" action="{{ $t['destroy_url'] ?? '#' }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-act delete js-master-delete"><i class="fi fi-rr-trash"></i> Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    @include('admin.layouts.empty-state', $masterEmptyState('truck', 'truck', 'fi fi-rr-truck-moving'))
                @endforelse
            </table>
        </div>
    </div>

    <!-- PANE: Master Inventory Items -->
    <div class="master-pane {{ $activePane === 'inventaris' ? 'active' : '' }}" data-pane="inventaris">
        <div class="table-responsive-wrapper">
            <table>
                <tr class="thead d-flex justify-content-between align-items-center">
                    <th class="col-no">No</th>
                    <th class="col-name">Nama</th>
                    <th class="col-category">Kategori</th>
                    <th class="col-stock">Jumlah</th>
                    <th class="col-aksi">Aksi</th>
                </tr>
                @forelse ($inventories as $i)
                    <tr class="tbody d-flex justify-content-between align-items-center" data-update-url="{{ $i['update_url'] ?? '' }}">
                        <td class="col-no">{{ $i['no'] }}</td>
                        <td class="col-name">{{ $i['name'] }}</td>
                        <td class="col-category">{{ $i['category'] }}</td>
                        <td class="col-stock">{{ $i['stock'] ?? 0 }}</td>
                        <td class="col-aksi">
                            <button type="button" class="btn-act edit js-master-edit"><i class="fi fi-rr-pencil"></i> Edit</button>
                            <form method="POST" action="{{ $i['destroy_url'] ?? '#' }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-act delete js-master-delete"><i class="fi fi-rr-trash"></i> Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    @include('admin.layouts.empty-state', $masterEmptyState('inventaris', 'inventaris', 'fi fi-rr-box-open'))
                @endforelse
            </table>
        </div>
    </div>

    <!-- PANE: Master Lingkungan Operasi (Shelter) -->
    <div class="master-pane {{ $activePane === 'lingkungan' ? 'active' : '' }}" data-pane="lingkungan">
        <div class="table-responsive-wrapper">
            <table>
                <tr class="thead d-flex justify-content-between align-items-center">
                    <th class="col-no">No</th>
                    <th class="col-name">Nama Item</th>
                    <th class="col-category">Kategori</th>
                    <th class="col-order">Urutan</th>
                    <th class="col-status">Status</th>
                    <th class="col-aksi">Aksi</th>
                </tr>
                @forelse ($environments as $env)
                    <tr class="tbody d-flex justify-content-between align-items-center" data-update-url="{{ $env['update_url'] ?? '' }}">
                        <td class="col-no">{{ $env['no'] }}</td>
                        <td class="col-name">{{ $env['name'] }}</td>
                        <td class="col-category">{{ $env['category'] }}</td>
                        <td class="col-order">{{ $env['sort_order'] }}</td>
                        <td class="col-status">{{ $env['is_active'] }}</td>
                        <td class="col-aksi">
                            <button type="button" class="btn-act edit js-master-edit"><i class="fi fi-rr-pencil"></i> Edit</button>
                            <form method="POST" action="{{ $env['destroy_url'] ?? '#' }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-act delete js-master-delete"><i class="fi fi-rr-trash"></i> Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    @include('admin.layouts.empty-state', $masterEmptyState('lingkungan', 'item lingkungan', 'fi fi-rr-house-chimney'))
                @endforelse
            </table>
        </div>
    </div>

    <!-- PANE: Master Safety Locations -->
    <div class="master-pane {{ $activePane === 'safety_lokasi' ? 'active' : '' }}" data-pane="safety_lokasi">
        <div class="table-responsive-wrapper">
            <table>
                <tr class="thead d-flex justify-content-between align-items-center">
                    <th class="col-no">No</th>
                    <th class="col-name">Nama Lokasi</th>
                    <th class="col-order">Urutan</th>
                    <th class="col-count">Jumlah Item</th>
                    <th class="col-status">Status</th>
                    <th class="col-aksi">Aksi</th>
                </tr>
                @forelse ($safetyLocations as $loc)
                    <tr class="tbody d-flex justify-content-between align-items-center" data-update-url="{{ $loc['update_url'] ?? '' }}">
                        <td class="col-no">{{ $loc['no'] }}</td>
                        <td class="col-name">{{ $loc['name'] }}</td>
                        <td class="col-order">{{ $loc['sort_order'] }}</td>
                        <td class="col-count">{{ $loc['item_count'] }} item</td>
                        <td class="col-status">{{ $loc['is_active'] }}</td>
                        <td class="col-aksi">
                            <button type="button" class="btn-act edit js-master-edit"><i class="fi fi-rr-pencil"></i> Edit</button>
                            <form method="POST" action="{{ $loc['destroy_url'] ?? '#' }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-act delete js-master-delete"><i class="fi fi-rr-trash"></i> Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    @include('admin.layouts.empty-state', $masterEmptyState('safety_lokasi', 'lokasi K3', 'fi fi-rr-marker'))
                @endforelse
            </table>
        </div>
    </div>

    <!-- PANE: Master Safety Items -->
    <div class="master-pane {{ $activePane === 'safety_item' ? 'active' : '' }}" data-pane="safety_item">
        <div class="table-responsive-wrapper">
            <table>
                <tr class="thead d-flex justify-content-between align-items-center">
                    <th class="col-no">No</th>
                    <th class="col-name">Nama Item</th>
                    <th class="col-qtyflag">Pakai QTY</th>
                    <th class="col-status">Status</th>
                    <th class="col-aksi">Aksi</th>
                </tr>
                @forelse ($safetyItems as $it)
                    <tr class="tbody d-flex justify-content-between align-items-center" data-update-url="{{ $it['update_url'] ?? '' }}">
                        <td class="col-no">{{ $it['no'] }}</td>
                        <td class="col-name">{{ $it['name'] }}</td>
                        <td class="col-qtyflag">{{ $it['is_countable'] }}</td>
                        <td class="col-status">{{ $it['is_active'] }}</td>
                        <td class="col-aksi">
                            <button type="button" class="btn-act edit js-master-edit"><i class="fi fi-rr-pencil"></i> Edit</button>
                            <form method="POST" action="{{ $it['destroy_url'] ?? '#' }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-act delete js-master-delete"><i class="fi fi-rr-trash"></i> Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    @include('admin.layouts.empty-state', $masterEmptyState('safety_item', 'item K3', 'fi fi-rr-checkbox'))
                @endforelse
            </table>
        </div>
    </div>

    @if (($activePane ?? 'karyawan') === 'karyawan' && method_exists($employees, 'links'))
        @include('admin.layouts.pagination', ['paginator' => $employees, 'label' => 'karyawan'])
    @elseif (($activePane ?? 'karyawan') === 'unit' && method_exists($units, 'links'))
        @include('admin.layouts.pagination', ['paginator' => $units, 'label' => 'unit'])
    @elseif (($activePane ?? 'karyawan') === 'truck' && method_exists($trucks, 'links'))
        @include('admin.layouts.pagination', ['paginator' => $trucks, 'label' => 'truck'])
    @elseif (($activePane ?? 'karyawan') === 'inventaris' && method_exists($inventories, 'links'))
        @include('admin.layouts.pagination', ['paginator' => $inventories, 'label' => 'inventaris'])
    @elseif (($activePane ?? 'karyawan') === 'lingkungan' && method_exists($environments, 'links'))
        @include('admin.layouts.pagination', ['paginator' => $environments, 'label' => 'item lingkungan'])
    @elseif (($activePane ?? 'karyawan') === 'safety_lokasi' && method_exists($safetyLocations, 'links'))
        @include('admin.layouts.pagination', ['paginator' => $safetyLocations, 'label' => 'lokasi K3'])
    @elseif (($activePane ?? 'karyawan') === 'safety_item' && method_exists($safetyItems, 'links'))
        @include('admin.layouts.pagination', ['paginator' => $safetyItems, 'label' => 'item K3'])
    @endif
@endcomponent

<div class="modal-overlay" id="masterFormModal" aria-hidden="true">
    <div class="modal-box modal-box--wide" role="dialog" aria-modal="true" aria-labelledby="masterFormTitle">
        <form method="POST" action="#" id="masterForm">
            @csrf
            <input type="hidden" name="_method" id="masterFormMethod" value="POST">
            <div class="kss-modal__header">
                <div class="kss-modal__icon">
                    <i class="fi fi-rr-database" id="masterFormIcon"></i>
                </div>
                <div class="kss-modal__heading">
                    <div class="kss-modal__title" id="masterFormTitle">Tambah Data Master</div>
                    <div class="kss-modal__subtitle" id="masterFormSubtitle">Lengkapi data sesuai kategori master yang aktif.</div>
                </div>
                <button type="button" class="kss-modal__close" data-modal-close aria-label="Tutup modal">
                    <i class="fi fi-rr-cross-small"></i>
                </button>
            </div>
            <div class="kss-modal__body">
                <div class="kss-modal__grid" id="masterFormFields"></div>
            </div>
            <div class="kss-modal__footer">
                <button type="button" class="kss-modal__button" data-modal-close>Batal</button>
                <button type="submit" class="kss-modal__button kss-modal__button--primary" id="masterFormSubmit">
                    <i class="fi fi-rr-disk"></i> Simpan Data
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // MASTER DATA TAB SWITCHING (via submenu sidebar)
        const masterTabs = {
            karyawan:      { title: 'Data Karyawan',   search: 'Cari Karyawan',   add: 'Tambah Karyawan',   icon: 'fi fi-rr-user-add' },
            unit:          { title: 'Data Unit',       search: 'Cari Unit',       add: 'Tambah Unit',       icon: 'fi fi-rr-add' },
            truck:         { title: 'Data Truck',      search: 'Cari Truck',      add: 'Tambah Truck',      icon: 'fi fi-rr-add' },
            inventaris:    { title: 'Data Inventaris', search: 'Cari Inventaris', add: 'Tambah Inventaris', icon: 'fi fi-rr-add' },
            lingkungan:    { title: 'Data Lingkungan Operasi', search: 'Cari Item Lingkungan', add: 'Tambah Item', icon: 'fi fi-rr-house-chimney' },
            safety_lokasi: { title: 'Data Lokasi K3',  search: 'Cari Lokasi K3',  add: 'Tambah Lokasi',     icon: 'fi fi-rr-marker' },
            safety_item:   { title: 'Data Item K3',    search: 'Cari Item K3',    add: 'Tambah Item',       icon: 'fi fi-rr-checkbox' }
        };

        const employeeGroupOptions = ['-', 'Bengkel', 'Relief 1', 'Relief 2', 'A', 'B', 'C', 'D', 'OP7 A', 'OP7 B', 'OP7 C', 'OP7 D'];
        // Penugasan sementara: TIDAK memindahkan karyawan, hanya membuatnya
        // ikut tampil di daftar unit tujuan. Regu A-D masuk daftar Karyawan
        // Shift, Relief masuk tab Relief & Lembur, Bengkel masuk absensi
        // laporan pemeliharaan. Nilainya harus sama dengan yang ditampilkan
        // kolom Regu Shift (setelah awalan 'Regu' dibuang) supaya dropdown
        // terisi balik saat form edit dibuka.
        const employeeShiftGroupOptions = ['-', 'A', 'B', 'C', 'D', 'Relief 1', 'Relief 2', 'Bengkel'];
        const employeePositionOptions = [
            '-',
            'Kepala Regu ( KARU )',
            'Wakil Karu',
            'Wakil Kepala Regu',
            'Checker',
            'Operator FL',
            'Driver',
            'Operator Exca/ WL',
            'Operator WL/ Exca',
            'Kasi Pemeliharaan & Peralatan',
            'Karu Peralatan',
            'Karu Pemeliharaan',
            'Mekanik',
            'Helper',
            'Rigger',
            'Operator OP.7',
            'Manager',
            'Kabag',
            'Kasi',
            'Karu',
            'Staf Ahli',
            'Staf',
            'Kepala Seksi',
            'Kepala Regu',
        ];
        const employeeDivisionOptions = ['Operasional', 'Pemeliharaan', 'Safety', 'Office'];
        const employeeWorkTimeOptions = ['Non Shift', 'Shift', 'Relief'];

        // Regu hanya relevan untuk divisi Operasional (A-D, Relief 1/2, OP7 A-D)
        // dan Pemeliharaan (satu-satunya opsi: Bengkel). Kantor & Safety tidak
        // punya Regu sama sekali.
        const employeeGroupOptionsByDivision = {
            'Operasional': ['-', 'A', 'B', 'C', 'D', 'Relief 1', 'Relief 2', 'OP7 A', 'OP7 B', 'OP7 C', 'OP7 D'],
            'Pemeliharaan': ['Bengkel'],
        };

        function employeeGroupOptionsForDivision(division) {
            return employeeGroupOptionsByDivision[division] || ['-'];
        }

        function employeeGroupIsEditable(division) {
            return division === 'Operasional' || division === 'Pemeliharaan';
        }

        // Jam Kerja mengikuti Divisi, kecuali karyawan Operasional yang
        // Regu-nya Relief 1/2 (jam kerjanya tetap Relief).
        function employeeWorkTimeForDivisionAndGroup(division, group) {
            if (division === 'Operasional') {
                return /^Relief\s*[12]$/i.test(group || '') ? 'Relief' : 'Shift';
            }
            return 'Non Shift';
        }

        const masterSchemas = {
            karyawan: {
                label: 'Karyawan',
                icon: 'fi fi-rr-user',
                fields: [
                    { key: 'npk', label: 'NPK', placeholder: 'cth, 2000.1.010' },
                    { key: 'name', label: 'Nama Karyawan', placeholder: 'cth, Budi Santoso' },
                    { key: 'division', label: 'Divisi', type: 'select', options: employeeDivisionOptions },
                    { key: 'group', label: 'Regu', type: 'select', options: employeeGroupOptions },
                    { key: 'work_time', label: 'Jam Kerja', type: 'select', options: employeeWorkTimeOptions },
                    { key: 'position', label: 'Jabatan', type: 'select', options: employeePositionOptions },
                    { key: 'shift_group', label: 'Penugasan Sementara (opsional)', type: 'select', options: employeeShiftGroupOptions },
                ],
            },
            unit: {
                label: 'Unit',
                icon: 'fi fi-rr-truck-side',
                fields: [
                    // Nama unit otomatis = Tipe + Kode unit, jadi tidak ada input nama manual.
                    { key: 'unit_number', label: 'Kode Unit', placeholder: 'cth, TRL-01 / FL-01' },
                    { key: 'brand', label: 'Merk', placeholder: 'cth, NISSAN CWM 330' },
                    { key: 'plate_number', label: 'Nomor Plat', placeholder: 'cth, KTDE 8512' },
                    { key: 'type', label: 'Tipe Unit', type: 'select', options: ['Trailer', 'Tronton', 'Dump Truck', 'Minibus', 'Bus', 'Pickup', 'Forklift', 'Wheel Loader', 'Excavator'] },
                    { key: 'macro_category', label: 'Kategori', type: 'select', options: ['-', 'truck', 'bus', 'heavy'] },
                    { key: 'in_operational_check', label: 'Masuk Cek Unit Operasional', type: 'select', options: ['Ya', 'Tidak'] },
                    { key: 'year', label: 'Tahun Pembuatan', type: 'number', placeholder: 'cth, 2024' },
                ],
            },
            truck: {
                label: 'Truck',
                icon: 'fi fi-rr-truck-moving',
                fields: [
                    { key: 'name', label: 'Nama Truck', placeholder: 'cth, Hino 500' },
                    { key: 'plate', label: 'Nomor Polisi', placeholder: 'cth, B 9012 KSS' },
                    { key: 'desc', label: 'Deskripsi', type: 'textarea', placeholder: 'cth, Truk angkut material' },
                ],
            },
            inventaris: {
                label: 'Inventaris',
                icon: 'fi fi-rr-box-open',
                fields: [
                    { key: 'name', label: 'Nama Inventaris', placeholder: 'cth, Helm Safety' },
                    { key: 'category', label: 'Kategori', type: 'select', options: ['APD', 'Sparepart', 'Tools', 'Consumable'] },
                    { key: 'stock', label: 'Jumlah', type: 'number', placeholder: 'cth, 50' },
                ],
            },
            lingkungan: {
                label: 'Item Lingkungan Operasi',
                icon: 'fi fi-rr-house-chimney',
                fields: [
                    { key: 'name', label: 'Nama Item', placeholder: 'cth, Ruangan Shelter' },
                    { key: 'category', label: 'Kategori', type: 'select', options: ['Kebersihan', 'Kerapian'] },
                    { key: 'sort_order', label: 'Urutan', type: 'number', placeholder: 'cth, 1' },
                    { key: 'is_active', label: 'Status', type: 'select', options: ['Aktif', 'Nonaktif'] },
                ],
            },
            safety_lokasi: {
                label: 'Lokasi K3',
                icon: 'fi fi-rr-marker',
                fields: [
                    { key: 'name', label: 'Nama Lokasi', placeholder: 'cth, Shelter Shift Operasi' },
                    { key: 'sort_order', label: 'Urutan', type: 'number', placeholder: 'cth, 1' },
                    { key: 'is_active', label: 'Status', type: 'select', options: ['Aktif', 'Nonaktif'] },
                ],
            },
            safety_item: {
                label: 'Item K3',
                icon: 'fi fi-rr-checkbox',
                fields: [
                    { key: 'name', label: 'Nama Item', placeholder: 'cth, APAR' },
                    { key: 'is_countable', label: 'Pakai QTY?', type: 'select', options: ['Tidak', 'Ya'] },
                    { key: 'is_active', label: 'Status', type: 'select', options: ['Aktif', 'Nonaktif'] },
                ],
            },
        };

        const masterTitle    = document.getElementById('masterTitle');
        const masterCrumb     = document.getElementById('masterCrumb');
        const masterCountText = document.getElementById('masterCountText');
        const masterCountIcon = document.getElementById('masterCountIcon');
        const masterSearch    = document.getElementById('masterSearch');
        const masterSearchClear = document.getElementById('masterSearchClear');
        const masterSearchForm = document.getElementById('masterSearchForm');
        const masterPaneInput = document.getElementById('masterPaneInput');
        const masterAddText   = document.getElementById('masterAddText');
        const masterAddIcon   = document.getElementById('masterAddIcon');
        const masterAddBtn    = document.getElementById('masterAddBtn');
        const masterExportBtn = document.getElementById('masterExportBtn');
        const masterPanes     = document.querySelectorAll('.master-pane');
        const masterMenuItems = document.querySelectorAll('.sidebar__submenu-item[data-pane]');
        const masterFormModal = document.getElementById('masterFormModal');
        const masterFormTitle = document.getElementById('masterFormTitle');
        const masterFormSubtitle = document.getElementById('masterFormSubtitle');
        const masterFormIcon = document.getElementById('masterFormIcon');
        const masterFormFields = document.getElementById('masterFormFields');
        const masterFormSubmit = document.getElementById('masterFormSubmit');
        const masterForm = document.getElementById('masterForm');
        const masterFormMethod = document.getElementById('masterFormMethod');
        const masterFilter = document.querySelector('[data-master-filter]');
        const masterFilterBtn = document.getElementById('masterFilterBtn');
        const masterFilterPopover = document.getElementById('master-filter-popover');
        const masterFilterClose = masterFilter?.querySelector('[data-master-filter-close]');
        const masterFilterStatus = document.getElementById('masterFilterStatus');
        const masterFilterSubtitle = document.getElementById('masterFilterSubtitle');
        const masterFilterGroups = document.querySelectorAll('.master-filter-group[data-filter-pane]');
        const masterFilterPanes = ['karyawan', 'unit'];
        // Status filter dibaca per pane supaya badge "Aktif" ikut benar saat
        // pane berpindah tanpa memuat ulang halaman.
        const masterFilterApplied = @json($masterFilterActive);
        const masterFilterSubtitles = {
            karyawan: 'Atur divisi, group, dan jabatan karyawan.',
            unit: 'Atur tipe dan kategori unit.',
        };
        const masterCounts = @json($masterCounts);
        const masterCountNouns = @json($masterCountNouns);
        const masterCountIcons = @json($masterCountIcons);
        // Pane yang benar-benar disaring server pada permintaan ini; hanya
        // pane inilah yang angkanya berarti "hasil", bukan jumlah seluruh data.
        const masterNarrowedPane = @json($masterIsNarrowed ? $activePane : null);
        const masterActions = @json($masterActions);
        let activeMasterPane = @json($activePane ?? 'karyawan');
        let masterSearchTimer = null;
        let lastSubmittedSearch = masterSearch ? masterSearch.value : '';
        let lastSubmittedPane = masterPaneInput ? masterPaneInput.value : activeMasterPane;

        function switchMasterPane(pane) {
            const cfg = masterTabs[pane];
            if (!cfg) return;
            activeMasterPane = pane;
            masterPanes.forEach(p => p.classList.toggle('active', p.getAttribute('data-pane') === pane));
            masterMenuItems.forEach(m => m.classList.toggle('active', m.getAttribute('data-pane') === pane));
            if (masterTitle)   masterTitle.textContent = cfg.title;
            if (masterCrumb)   masterCrumb.textContent = cfg.title;
            if (masterSearch)  masterSearch.placeholder = cfg.search;
            if (masterPaneInput) masterPaneInput.value = pane;
            if (masterAddText) masterAddText.textContent = cfg.add;
            if (masterAddIcon) masterAddIcon.className = cfg.icon;
            if (masterCountText) {
                const noun = pane === masterNarrowedPane ? 'hasil' : (masterCountNouns[pane] || 'data');
                masterCountText.textContent = `${masterCounts[pane] ?? 0} ${noun}`;
            }
            if (masterCountIcon) {
                masterCountIcon.className = masterCountIcons[pane] || 'fi fi-rr-folder-open';
            }
            syncMasterExportLink(pane);
            syncMasterFilters(pane);
        }

        // Tautan Ekspor selalu membawa pane, kata kunci, dan filter yang sedang
        // aktif. Nilai filter dibaca dari select-nya sendiri supaya tetap benar
        // meski pengguna baru mengubahnya tanpa menekan "Terapkan".
        function syncMasterExportLink(pane) {
            if (!masterExportBtn) return;

            const base = masterExportBtn.dataset.exportBase;
            if (!base) return;

            const url = new URL(base, window.location.origin);
            url.searchParams.set('pane', pane);

            const keyword = (masterSearch?.value || '').trim();
            if (keyword !== '') url.searchParams.set('q', keyword);

            document.querySelectorAll('.js-master-filter').forEach(function (select) {
                if (select.disabled || !select.value) return;
                url.searchParams.set(select.name, select.value);
            });

            masterExportBtn.href = url.toString();
            masterExportBtn.dataset.confirmRedirect = url.toString();
        }

        function setMasterFilterOpen(open) {
            if (!masterFilterBtn || !masterFilterPopover) return;
            masterFilterPopover.hidden = !open;
            masterFilterBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
            masterFilter?.classList.toggle('is-open', open);
        }

        // Tampilkan pemicu Filter hanya untuk pane yang punya filter; tampilkan
        // grup filter milik pane aktif & nonaktifkan select pane lain agar tidak
        // ikut terkirim ke URL.
        function syncMasterFilters(pane) {
            const hasFilters = masterFilterPanes.includes(pane);
            if (masterFilterBtn) masterFilterBtn.classList.toggle('is-hidden', !hasFilters);
            if (!hasFilters) setMasterFilterOpen(false);

            const applied = masterFilterApplied[pane] ?? false;
            if (masterFilterStatus) {
                masterFilterStatus.classList.toggle('is-hidden', !applied);
            }
            // Tombol ikon tidak punya teks, jadi status filter aktif dibaca dari
            // warnanya sendiri — bukan hanya dari titik penanda di sudutnya.
            if (masterFilterBtn) {
                masterFilterBtn.classList.toggle('performance-filter__trigger--active', applied);
            }
            if (masterFilterSubtitle && masterFilterSubtitles[pane]) {
                masterFilterSubtitle.textContent = masterFilterSubtitles[pane];
            }

            masterFilterGroups.forEach(function (group) {
                const isActive = group.getAttribute('data-filter-pane') === pane;
                group.style.display = isActive ? 'grid' : 'none';
                group.querySelectorAll('select').forEach(function (select) {
                    select.disabled = !isActive;
                });
            });
        }

        function scheduleMasterSearchSubmit(delay = null) {
            if (!masterSearchForm || !masterSearch) return;

            window.clearTimeout(masterSearchTimer);
            const debounceMs = delay ?? Number(masterSearch.dataset.searchDebounce || 650);

            masterSearchTimer = window.setTimeout(function () {
                const currentSearch = masterSearch.value;
                const currentPane = masterPaneInput ? masterPaneInput.value : activeMasterPane;
                if (currentSearch === lastSubmittedSearch && currentPane === lastSubmittedPane) return;

                lastSubmittedSearch = currentSearch;
                lastSubmittedPane = currentPane;
                if (typeof masterSearchForm.requestSubmit === 'function') {
                    masterSearchForm.requestSubmit();
                } else {
                    masterSearchForm.submit();
                }
            }, debounceMs);
        }

        function readMasterRow(row, pane) {
            const text = selector => row.querySelector(selector)?.textContent.trim() || '';
            const value = selector => row.querySelector(selector)?.dataset.value ?? text(selector);
            if (pane === 'karyawan') {
                const group = value('.col-group');

                const shiftGroup = value('.col-shiftgroup');

                return {
                    npk: value('.col-npk') === '-' ? '' : value('.col-npk'),
                    name: value('.col-name'),
                    group: group === 'Kantor' || group === '-' ? '-' : group.replace(/^Regu\s+/i, ''),
                    shift_group: shiftGroup === '' || shiftGroup === '-' ? '-' : shiftGroup.replace(/^Regu\s+/i, ''),
                    position: value('.col-position') === '-' ? '' : value('.col-position'),
                    division: value('.col-division') || 'Operasional',
                    work_time: value('.col-worktime') === '-' ? 'Non Shift' : value('.col-worktime'),
                };
            }
            if (pane === 'unit') {
                const category = text('.col-category');
                return {
                    name: text('.col-name'),
                    unit_number: text('.col-code') === '-' ? '' : text('.col-code'),
                    brand: text('.col-brand') === '-' ? '' : text('.col-brand'),
                    plate_number: text('.col-number') === '-' ? '' : text('.col-number'),
                    type: text('.col-type'),
                    macro_category: category === 'Truck' ? 'truck' : (category === 'Bus' ? 'bus' : (category === 'Heavy' ? 'heavy' : '-')),
                    in_operational_check: text('.col-opscheck') === 'Ya' ? 'Ya' : 'Tidak',
                    year: text('.col-year') === '-' ? '' : text('.col-year'),
                };
            }
            if (pane === 'truck') {
                return { name: text('.col-name'), plate: text('.col-plate'), desc: text('.col-desc') };
            }
            if (pane === 'safety_lokasi') {
                return {
                    name: text('.col-name'),
                    sort_order: text('.col-order'),
                    is_active: text('.col-status') || 'Aktif',
                };
            }
            if (pane === 'safety_item') {
                return {
                    name: text('.col-name'),
                    is_countable: text('.col-qtyflag') || 'Tidak',
                    is_active: text('.col-status') || 'Aktif',
                };
            }
            if (pane === 'lingkungan') {
                return {
                    name: text('.col-name'),
                    category: text('.col-category') || 'Kebersihan',
                    sort_order: text('.col-order'),
                    is_active: text('.col-status') || 'Aktif',
                };
            }
            return { name: text('.col-name'), category: text('.col-category'), stock: text('.col-stock') };
        }

        function addField(field, value, index) {
            const wrapper = document.createElement('div');
            wrapper.className = 'kss-modal__field';
            if (field.type === 'textarea') wrapper.classList.add('kss-modal__field--full');

            const label = document.createElement('label');
            label.setAttribute('for', `masterField_${field.key}`);
            label.textContent = field.label;
            wrapper.appendChild(label);

            let control;
            if (field.type === 'select') {
                const selectWrapper = document.createElement('div');
                selectWrapper.className = 'kss-modal__select-wrapper';

                control = document.createElement('select');
                control.className = 'kss-modal__native-select';
                field.options.forEach(optionText => {
                    const option = document.createElement('option');
                    option.textContent = optionText;
                    option.value = optionText;
                    control.appendChild(option);
                });
                selectWrapper.appendChild(control);

                const icon = document.createElement('i');
                icon.className = 'fi fi-rr-angle-small-down kss-modal__select-icon';
                selectWrapper.appendChild(icon);

                control.id = `masterField_${field.key}`;
                control.name = field.key;
                if (value && Array.from(control.options).some(option => option.value === value)) {
                    control.value = value;
                } else {
                    control.selectedIndex = 0;
                }
                if (index === 0) control.dataset.modalFocus = 'true';
                wrapper.appendChild(selectWrapper);
                masterFormFields.appendChild(wrapper);
                return;
            } else if (field.type === 'textarea') {
                control = document.createElement('textarea');
                control.className = 'kss-modal__textarea';
            } else {
                control = document.createElement('input');
                control.type = field.type === 'number' ? 'number' : 'text';
                control.className = 'kss-modal__input';
                if (field.type === 'number') {
                    control.min = '0';
                    control.step = '1';
                    control.inputMode = 'numeric';
                }
            }

            control.id = `masterField_${field.key}`;
            control.name = field.key;
            control.value = value || '';
            control.placeholder = field.placeholder || '';
            if (index === 0) control.dataset.modalFocus = 'true';
            wrapper.appendChild(control);
            masterFormFields.appendChild(wrapper);
        }

        function rebuildSelectOptions(select, options, selectedValue) {
            select.replaceChildren();
            options.forEach(optionText => {
                const option = document.createElement('option');
                option.textContent = optionText;
                option.value = optionText;
                select.appendChild(option);
            });
            if (selectedValue && options.includes(selectedValue)) {
                select.value = selectedValue;
            } else {
                select.selectedIndex = 0;
            }
        }

        // Dropdown kustom (kss-modal__select-*) dibangun sekali dari opsi
        // <select> lalu ditandai `selectReady`. Karena Regu diisi ulang dgn
        // opsi berbeda-beda, tandanya perlu dilepas supaya initSelects()
        // membangun ulang panel opsinya, bukan cuma menyinkronkan label.
        function resetCustomSelectWidget(select) {
            const wrapper = select.closest('.kss-modal__select-wrapper');
            if (!wrapper) return;
            wrapper.querySelectorAll('.kss-modal__select-trigger, .kss-modal__select-options').forEach(el => el.remove());
            delete wrapper.dataset.selectReady;
        }

        function applyEmployeeDivisionRules(divisionSelect, groupSelect, preferredGroupValue) {
            const division = divisionSelect.value;
            const options = employeeGroupOptionsForDivision(division);
            const editable = employeeGroupIsEditable(division);

            // Karyawan lama dengan Regu di luar daftar (mis. data legacy) tetap
            // ditampilkan supaya tidak hilang diam-diam saat form dibuka.
            const finalOptions = (preferredGroupValue && preferredGroupValue !== '-' && !options.includes(preferredGroupValue))
                ? [...options, preferredGroupValue]
                : options;

            rebuildSelectOptions(groupSelect, finalOptions, preferredGroupValue);
            groupSelect.disabled = !editable;
            if (!editable) groupSelect.value = finalOptions.includes('-') ? '-' : finalOptions[0];
            resetCustomSelectWidget(groupSelect);
        }

        function applyEmployeeWorkTimeRule(divisionSelect, groupSelect, workTimeSelect) {
            workTimeSelect.value = employeeWorkTimeForDivisionAndGroup(divisionSelect.value, groupSelect.value);
        }

        function setupKaryawanDivisionLogic(rowValues) {
            const divisionSelect = document.getElementById('masterField_division');
            const groupSelect = document.getElementById('masterField_group');
            const workTimeSelect = document.getElementById('masterField_work_time');
            if (!divisionSelect || !groupSelect || !workTimeSelect) return;

            applyEmployeeDivisionRules(divisionSelect, groupSelect, rowValues && rowValues.group);
            applyEmployeeWorkTimeRule(divisionSelect, groupSelect, workTimeSelect);

            divisionSelect.addEventListener('change', function () {
                applyEmployeeDivisionRules(divisionSelect, groupSelect, null);
                applyEmployeeWorkTimeRule(divisionSelect, groupSelect, workTimeSelect);
                window.KssAdminModal.initSelects(masterFormModal);
            });

            groupSelect.addEventListener('change', function () {
                applyEmployeeWorkTimeRule(divisionSelect, groupSelect, workTimeSelect);
                window.KssAdminModal.syncSelects(masterFormModal);
            });
        }

        function openMasterForm(mode, pane, values = {}) {
            const schema = masterSchemas[pane];
            if (!schema) return;

            masterFormTitle.textContent = `${mode === 'edit' ? 'Edit' : 'Tambah'} ${schema.label}`;
            masterFormSubtitle.textContent = mode === 'edit'
                ? `Perbarui informasi ${schema.label.toLowerCase()} yang dipilih.`
                : `Masukkan detail ${schema.label.toLowerCase()} baru ke master data.`;
            masterFormIcon.className = schema.icon;
            masterFormSubmit.innerHTML = `<i class="fi fi-rr-disk"></i> ${mode === 'edit' ? 'Simpan Perubahan' : 'Simpan Data'}`;
            if (masterForm) masterForm.action = mode === 'edit' && values.updateUrl ? values.updateUrl : (masterActions[pane]?.store || '#');
            if (masterFormMethod) masterFormMethod.value = mode === 'edit' ? 'PUT' : 'POST';

            masterFormFields.replaceChildren();
            schema.fields.forEach((field, index) => addField(field, values[field.key], index));
            if (pane === 'karyawan') setupKaryawanDivisionLogic(values);
            window.KssAdminModal.initSelects(masterFormModal);
            window.KssAdminModal.syncSelects(masterFormModal);
            window.KssAdminModal.open(masterFormModal);
        }

        masterMenuItems.forEach(function (item) {
            item.addEventListener('click', function (e) {
                const pane = item.getAttribute('data-pane');
                if (!pane || !masterTabs[pane]) return;
                window.clearTimeout(masterSearchTimer);
            });
        });

        const initialPane = new URLSearchParams(window.location.search).get('pane') || @json($activePane ?? 'karyawan');
        switchMasterPane(initialPane);

        function syncMasterSearchClear() {
            if (masterSearchClear) masterSearchClear.hidden = (masterSearch?.value ?? '') === '';
        }

        masterSearch?.addEventListener('input', function () {
            syncMasterSearchClear();
            syncMasterExportLink(activeMasterPane);
            scheduleMasterSearchSubmit();
        });

        document.querySelectorAll('.js-master-filter').forEach(function (select) {
            select.addEventListener('change', function () {
                syncMasterExportLink(activeMasterPane);
            });
        });

        masterSearch?.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && masterSearch.value !== '') {
                event.preventDefault();
                masterSearch.value = '';
                syncMasterSearchClear();
                scheduleMasterSearchSubmit(0);
                return;
            }

            if (event.key !== 'Enter') return;
            event.preventDefault();
            scheduleMasterSearchSubmit(0);
        });

        masterSearchClear?.addEventListener('click', function () {
            if (!masterSearch) return;
            masterSearch.value = '';
            masterSearch.focus();
            syncMasterSearchClear();
            scheduleMasterSearchSubmit(0);
        });

        // Kursor dikembalikan ke akhir teks setelah halaman dimuat ulang oleh
        // debounce, supaya mengetik terasa tidak terputus.
        if (masterSearch && masterSearch.value !== '') {
            masterSearch.focus();
            masterSearch.setSelectionRange(masterSearch.value.length, masterSearch.value.length);
        }

        masterSearchForm?.addEventListener('submit', function () {
            window.clearTimeout(masterSearchTimer);
            if (masterSearch) {
                masterSearch.value = masterSearch.value.trim();
            }
        });

        masterAddBtn?.addEventListener('click', function () {
            openMasterForm('add', activeMasterPane);
        });

        masterFilterBtn?.addEventListener('click', function () {
            setMasterFilterOpen(masterFilterPopover?.hidden ?? true);
        });

        masterFilterClose?.addEventListener('click', function () {
            setMasterFilterOpen(false);
            masterFilterBtn?.focus();
        });

        document.addEventListener('click', function (event) {
            if (!masterFilterPopover || masterFilterPopover.hidden) return;
            if (masterFilter?.contains(event.target)) return;
            setMasterFilterOpen(false);
        });

        document.addEventListener('keydown', function (event) {
            if (event.key !== 'Escape' || !masterFilterPopover || masterFilterPopover.hidden) return;
            setMasterFilterOpen(false);
            masterFilterBtn?.focus();
        });

        // Custom dropdown bergaya Arsip Laporan untuk select filter.
        document.querySelectorAll('#master-filter-popover .filter-select-wrapper').forEach(function (wrapper) {
            const select = wrapper.querySelector('select');
            if (!select) return;
            select.style.display = 'none';

            const trigger = document.createElement('div');
            trigger.className = 'filter-input filter-select-trigger';
            const label = document.createElement('span');
            label.textContent = select.options[select.selectedIndex].text;
            trigger.appendChild(label);
            wrapper.insertBefore(trigger, select.nextSibling);

            const list = document.createElement('div');
            list.className = 'filter-select-options';
            Array.from(select.options).forEach(function (opt, i) {
                const item = document.createElement('div');
                item.className = 'filter-select-option';
                item.textContent = opt.text;
                if (i === select.selectedIndex) item.classList.add('selected');
                item.addEventListener('click', function (e) {
                    e.stopPropagation();
                    select.value = opt.value;
                    label.textContent = opt.text;
                    list.querySelectorAll('.filter-select-option').forEach(o => o.classList.remove('selected'));
                    item.classList.add('selected');
                    list.classList.remove('open');
                    trigger.classList.remove('focus-active');
                    select.dispatchEvent(new Event('change'));
                });
                list.appendChild(item);
            });
            wrapper.appendChild(list);

            trigger.addEventListener('click', function (e) {
                e.stopPropagation();
                document.querySelectorAll('.filter-select-options.open').forEach(function (c) {
                    if (c !== list) {
                        c.classList.remove('open');
                        const t = c.parentElement.querySelector('.filter-select-trigger');
                        if (t) t.classList.remove('focus-active');
                    }
                });
                list.classList.toggle('open');
                trigger.classList.toggle('focus-active');
            });
        });

        document.addEventListener('click', function () {
            document.querySelectorAll('.filter-select-options.open').forEach(c => c.classList.remove('open'));
            document.querySelectorAll('.filter-select-trigger.focus-active').forEach(t => t.classList.remove('focus-active'));
        });

        document.querySelectorAll('.js-master-edit').forEach(function (button) {
            button.addEventListener('click', function () {
                const pane = button.closest('.master-pane')?.getAttribute('data-pane') || activeMasterPane;
                const row = button.closest('tr');
                openMasterForm('edit', pane, { ...readMasterRow(row, pane), updateUrl: row?.dataset.updateUrl || '' });
            });
        });

        document.querySelectorAll('.js-master-delete').forEach(function (button) {
            button.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                const pane = button.closest('.master-pane')?.getAttribute('data-pane') || activeMasterPane;
                const cfg = masterTabs[pane];
                const rowData = readMasterRow(button.closest('tr'), pane);
                button.dataset.confirmTone = 'danger';
                button.dataset.confirmTitle = `Hapus ${cfg.title}?`;
                button.dataset.confirmSubtitle = 'Data master akan dihapus dari daftar.';
                button.dataset.confirmMessage = 'Pastikan data ini sudah tidak dipakai pada laporan atau referensi operasional.';
                button.dataset.confirmSummary = rowData.name || rowData.npk || cfg.title;
                button.dataset.confirmLabel = 'Hapus Data';
                button.dataset.confirmIcon = 'fi fi-rr-trash';
                button.dataset.confirmSubmit = 'true';
                window.KssAdminModal.confirm(button);
            });
        });
    });
</script>
@endpush
