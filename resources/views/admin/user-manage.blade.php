@extends('admin.layouts.app')

@section('title', 'KSS Admin - Kelola Pengguna')
@section('active', 'user')

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
       PAGE HEADER — judul di kiri, aksi utama di ujung kanan.
       Susunannya sengaja disamakan dengan tombol Filter pada
       Arsip Laporan (.performance-page-header) supaya kedua
       halaman admin punya titik aksi yang sama.
       ============================================= */
    .user-page-header {
        position: relative;
        z-index: 20;
        flex-direction: row;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
    }

    .user-page-header__heading {
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .user-page-header__action {
        flex: 0 0 auto;
        min-height: 38px;
        padding-inline: 14px;
        box-shadow: 0 5px 14px rgba(37, 99, 235, .18);
    }

    /* =============================================
       CARD HEADER — judul kartu sebaris dengan pencarian & filter
       ============================================= */
    .archive-body { padding: 20px; display: flex; flex-direction: column; gap: 16px; }

    .user-card-header {
        position: relative;
        z-index: 15;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
    }

    .user-toolbar {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-left: auto;
        min-width: 0;
    }

    .user-toolbar__search { min-width: 0; }

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

    .btn-tool i { position: relative; top: 1px; }
    .btn-tool:hover { background-color: var(--blue-main-5); border-color: var(--blue-main-25); color: var(--blue-main); }
    .btn-tool--primary { background-color: var(--blue-main); border-color: var(--blue-main); color: #fff; }
    .btn-tool--primary:hover { background-color: var(--blue-hover); border-color: var(--blue-hover); color: #fff; }

    /* =============================================
       FILTER POPOVER (role / regu / status)
       ============================================= */
    .user-filter { position: relative; flex: 0 0 auto; }

    /* Tombol ikon saja: dibuat bujur sangkar 38px agar tingginya persis
       sama dengan kotak pencarian di sebelahnya. */
    .user-filter__trigger {
        width: 38px;
        height: 38px;
        padding: 0;
        justify-content: center;
        position: relative;
    }

    /* Glyph "settings-sliders" duduk lebih tinggi dari kotak em-nya sendiri
       (ruang kosong font berada di bawah, bukan di atas), jadi butuh offset
       positif ekstra supaya optically center di tombol persegi 38px. */
    .user-filter__trigger i { top: 3px; font-size: 14px; }

    .user-filter__trigger[aria-expanded="true"] {
        background-color: var(--blue-main-10);
        border-color: var(--blue-main);
        color: var(--blue-main);
    }

    .user-filter__trigger--active {
        background-color: var(--blue-main);
        border-color: var(--blue-main);
        color: #fff;
    }

    .user-filter__trigger--active:hover {
        background-color: var(--blue-hover);
        border-color: var(--blue-hover);
        color: #fff;
    }

    /* Titik penanda filter aktif — dipakai bersama ikon supaya tidak perlu
       label teks di tombol. */
    .user-filter__dot {
        position: absolute;
        top: -3px;
        right: -3px;
        width: 9px;
        height: 9px;
        border: 2px solid var(--white);
        border-radius: 50%;
        background-color: var(--orange-main);
    }

    .user-filter__popover {
        position: absolute;
        top: calc(100% + 10px);
        right: 0;
        z-index: 40;
        width: min(520px, calc(100vw - 112px));
        padding: 16px;
        /* Frosted bersama — lihat resources/css/components/frosted-surface.css. */
        border: 1px solid var(--kss-frost-border);
        border-radius: 14px;
        background-color: var(--kss-frost-surface);
        -webkit-backdrop-filter: var(--kss-frost-filter);
        backdrop-filter: var(--kss-frost-filter);
        box-shadow: inset 0 1px 0 var(--kss-frost-edge), var(--kss-frost-shadow);
    }

    .user-filter__popover[hidden] { display: none; }

    .user-filter__head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
        margin-bottom: 14px;
        padding-bottom: 12px;
        border-bottom: 1px solid var(--smooth-border);
    }

    .user-filter__title { display: block; font-size: 13px; font-weight: 600; color: var(--black); }
    .user-filter__subtitle { display: block; margin-top: 2px; font-size: 10px; color: var(--muted); }

    .user-filter__close {
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

    .user-filter__fields {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        align-items: end;
        gap: 12px;
    }

    .user-filter__actions {
        display: flex;
        justify-content: flex-end;
        gap: 8px;
        margin-top: 14px;
    }

    .filter-field { display: flex; min-width: 0; flex-direction: column; gap: 4px; }
    .filter-field label { font-size: 10px; font-weight: 500; color: var(--black-secondary); }

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
        transition: border-color 0.2s ease;
    }

    .filter-select-wrapper { position: relative; width: 100%; min-width: 0; }

    .filter-select-trigger {
        display: flex;
        align-items: center;
        width: 100%;
        min-width: 0;
        height: 36px;
        padding-right: 34px;
        cursor: pointer;
    }

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
        max-height: 200px;
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

    @keyframes fadeIn { from { opacity: 0; transform: translateY(-5px); } to { opacity: 1; transform: translateY(0); } }

    .btn-reset {
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
    .btn-reset:hover { background-color: var(--red-main-10); }

    @media (max-width: 720px) {
        .user-card-header { align-items: stretch; }

        .user-toolbar {
            width: 100%;
            margin-left: 0;
        }

        .user-toolbar__search { flex: 1 1 auto; }
        .search-box { width: 100%; }

        .user-filter__fields { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }

    @media (max-width: 560px) {
        .user-page-header { flex-direction: column; }
        .user-page-header__action { width: 100%; justify-content: center; }

        /* Tetap ditambatkan ke tepi kanan tombol. Membaliknya ke `left: 0`
           justru membuat panel keluar layar, karena titik acuannya adalah
           .user-filter yang sudah berada di ujung kanan kartu. */
        .user-filter__popover { width: min(520px, calc(100vw - 48px)); }

        .user-filter__fields { grid-template-columns: 1fr; }
        .user-filter__actions > * { flex: 1 1 0; justify-content: center; text-align: center; }
    }

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

    /* User table columns */
    .thead th.col-no,       .tbody td.col-no       { width: 50px; flex: none; justify-content: center; padding: 12px 0; color: var(--black-secondary); }
    .thead th.col-photo,    .tbody td.col-photo    { width: 60px; flex: none; justify-content: center; padding: 8px 0; }
    .thead th.col-name,     .tbody td.col-name     { min-width: 150px; }
    .thead th.col-username, .tbody td.col-username { min-width: 120px; }
    .thead th.col-role,     .tbody td.col-role     { min-width: 100px; }
    .thead th.col-regu,     .tbody td.col-regu     { min-width: 100px; }
    .thead th.col-status,   .tbody td.col-status   { min-width: 110px; }
    .thead th.col-aksi,     .tbody td.col-aksi     { min-width: 180px; gap: 8px; flex-wrap: nowrap; }

    .tbody td.col-username { color: var(--black-secondary); font-weight: 400; }

    /* Avatar kolom foto — bentuk & warna mengikuti avatar pada
       partials/account-trigger agar identitas pengguna terbaca sama di
       seluruh shell. Inisial dipakai saat foto kosong atau gagal dimuat. */
    .user-avatar {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 34px;
        height: 34px;
        overflow: hidden;
        border: 1px solid var(--smooth-border);
        border-radius: 8px;
        background-color: var(--blue-main-10);
        color: var(--blue-main);
        font-size: 12px;
        font-weight: 700;
        line-height: 1;
    }

    .user-avatar img { width: 100%; height: 100%; object-fit: cover; }
    .user-avatar [hidden] { display: none; }

    /* Status badges */
    .status {
        display: inline-flex;
        padding: 3px 8px;
        align-items: center;
        gap: 5px;
        border-radius: 10px;
        font-size: 10px;
        font-weight: 500;
    }

    .status-dot { width: 6px; height: 6px; border-radius: 50%; background-color: currentColor; flex-shrink: 0; }
    .status.aktif    { border: 1px solid var(--success);  color: var(--success);  background-color: var(--success-10); }
    .status.nonaktif { border: 1px solid var(--red-main); color: var(--red-main); background-color: var(--red-main-10); }

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
    td.col-aksi .btn-act.view { background-color: var(--orange-main); width: 30px; padding: 7px; }
    td.col-aksi .btn-act.view:hover { background-color: var(--orange-hover); transform: translateY(-1px); }
    td.col-aksi .btn-act.delete { background-color: var(--red-main); width: 30px; padding: 7px; }
    td.col-aksi .btn-act.delete:hover { background-color: var(--red-hover); transform: translateY(-1px); }

    .user-status-switch {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        user-select: none;
    }

    .user-status-switch input {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }

    .user-status-switch__track {
        width: 38px;
        height: 22px;
        padding: 2px;
        border-radius: 999px;
        background-color: var(--red-main-10);
        border: 1px solid rgba(210,0,0,0.22);
        transition: 0.2s ease;
        flex-shrink: 0;
    }

    .user-status-switch__thumb {
        width: 16px;
        height: 16px;
        border-radius: 50%;
        display: block;
        background-color: var(--red-main);
        box-shadow: 0 2px 5px rgba(15,23,42,0.16);
        transition: transform 0.2s ease, background-color 0.2s ease;
    }

    .user-status-switch input:checked + .user-status-switch__track {
        background-color: var(--success-10);
        border-color: rgba(16,185,129,0.26);
    }

    .user-status-switch input:checked + .user-status-switch__track .user-status-switch__thumb {
        transform: translateX(16px);
        background-color: var(--success);
    }

    .user-status-switch input:focus-visible + .user-status-switch__track {
        box-shadow: 0 0 0 3px var(--blue-main-10);
    }

    .user-status-switch:has(input:disabled) {
        cursor: not-allowed;
        opacity: 0.75;
    }

    .user-status-switch__label {
        min-width: 58px;
        font-size: 10px;
        font-weight: 600;
        color: var(--red-main);
    }

    .user-status-switch.is-active .user-status-switch__label { color: var(--success); }

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
        gap: 7px;
    }

    .signature-upload__picker {
        min-height: 42px;
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 5px 7px;
        border: 1px solid var(--smooth-border);
        border-radius: 8px;
        background-color: var(--white);
        transition: border-color 0.2s ease, box-shadow 0.2s ease, opacity 0.2s ease;
    }

    .signature-upload__picker:focus-within {
        border-color: var(--blue-main);
        box-shadow: 0 0 0 3px var(--blue-main-10);
    }

    .signature-upload__native {
        position: absolute;
        width: 1px;
        height: 1px;
        opacity: 0;
        overflow: hidden;
        pointer-events: none;
    }

    .signature-upload__button {
        min-height: 30px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        padding: 6px 11px;
        border: 1px solid var(--blue-main-25);
        border-radius: 7px;
        background-color: var(--blue-main-5);
        color: var(--blue-main);
        font-size: 11px;
        font-weight: 600;
        white-space: nowrap;
        cursor: pointer;
        transition: 0.2s ease;
    }

    .signature-upload__button:hover,
    .signature-upload__button:focus-visible {
        background-color: var(--blue-main);
        border-color: var(--blue-main);
        color: #fff;
        outline: none;
    }

    .signature-upload__filename {
        min-width: 0;
        overflow: hidden;
        color: var(--black-secondary);
        font-size: 11px;
        font-weight: 500;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .signature-upload__picker.is-disabled {
        opacity: 0.65;
        cursor: not-allowed;
    }

    .signature-upload__picker.is-disabled .signature-upload__button {
        pointer-events: none;
        cursor: not-allowed;
    }

    .signature-upload__hint {
        font-size: 10px;
        color: var(--muted);
        font-weight: 400;
    }

    .user-field-hint {
        font-size: 10px;
        line-height: 1.45;
        color: var(--muted);
    }

    .credential-notice {
        display: flex;
        align-items: flex-start;
        gap: 9px;
        padding: 10px 12px;
        border: 1px solid var(--orange-main-25, rgba(255, 138, 0, 0.25));
        border-radius: 8px;
        background-color: var(--orange-main-5, rgba(255, 138, 0, 0.05));
        color: var(--black-secondary);
        font-size: 11px;
        line-height: 1.5;
    }

    .credential-notice i {
        position: relative;
        top: 2px;
        flex-shrink: 0;
        color: var(--orange-main);
    }

    .credential-card {
        display: grid;
        gap: 8px;
        padding: 12px;
        border: 1px solid var(--smooth-border);
        border-radius: 10px;
        background-color: var(--main-bg);
    }

    .credential-card__row {
        display: grid;
        grid-template-columns: 88px minmax(0, 1fr);
        align-items: center;
        gap: 10px;
    }

    .credential-card__label {
        color: var(--muted);
        font-size: 10px;
        font-weight: 600;
    }

    .credential-card__value {
        min-width: 0;
        padding: 7px 9px;
        overflow-wrap: anywhere;
        border: 1px solid var(--smooth-border);
        border-radius: 7px;
        background-color: var(--white);
        color: var(--black);
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        font-size: 12px;
        font-weight: 600;
    }

    .kss-password-wrap {
        position: relative;
        width: 100%;
    }

    .kss-password-wrap .kss-modal__input {
        padding-right: 40px;
    }

    .kss-password-toggle {
        position: absolute;
        top: 50%;
        right: 8px;
        transform: translateY(-50%);
        display: flex;
        align-items: center;
        justify-content: center;
        width: 28px;
        height: 28px;
        padding: 0;
        border: none;
        border-radius: 6px;
        background: transparent;
        color: var(--muted);
        cursor: pointer;
        transition: color 0.2s ease, background-color 0.2s ease;
    }

    .kss-password-toggle:hover {
        color: var(--blue-main);
        background-color: var(--blue-main-10);
    }

    .kss-password-toggle i {
        font-size: 15px;
        line-height: 1;
    }

    @media (max-width: 640px) {
        .signature-upload {
            align-items: stretch;
            flex-direction: column;
        }

        .signature-upload__preview {
            width: 100%;
            height: 76px;
        }

        .signature-upload__picker {
            align-items: stretch;
            flex-direction: column;
        }

        .signature-upload__filename {
            padding: 2px 4px;
        }

        .credential-card__row {
            grid-template-columns: 1fr;
            gap: 4px;
        }
    }
</style>
@endpush

@section('content')
@php
    $users = $users ?? collect([
        ['no' => 1, 'name' => 'Mustari S,T', 'username' => 'admin', 'role' => 'Admin', 'regu' => 'Regu B', 'status' => 'aktif',    'status_label' => 'Aktif'],
        ['no' => 2, 'name' => 'Mustari S,T', 'username' => 'admin', 'role' => 'Admin', 'regu' => 'Regu B', 'status' => 'nonaktif', 'status_label' => 'Non-Aktif'],
        ['no' => 3, 'name' => 'Mustari S,T', 'username' => 'admin', 'role' => 'Admin', 'regu' => 'Regu B', 'status' => 'aktif',    'status_label' => 'Aktif'],
        ['no' => 4, 'name' => 'Mustari S,T', 'username' => 'admin', 'role' => 'Admin', 'regu' => 'Regu B', 'status' => 'aktif',    'status_label' => 'Aktif'],
        ['no' => 5, 'name' => 'Mustari S,T', 'username' => 'admin', 'role' => 'Admin', 'regu' => 'Regu B', 'status' => 'aktif',    'status_label' => 'Aktif'],
    ]);

    $roles = $roles ?? collect();
    $issuedCredentials = $issuedCredentials ?? null;

    $userSearch = $userSearch ?? '';
    $selectedUserRole = $selectedUserRole ?? 'all';
    $selectedUserGroup = $selectedUserGroup ?? 'all';
    $selectedUserStatus = $selectedUserStatus ?? 'all';
    $hasUserFilter = $selectedUserRole !== 'all'
        || $selectedUserGroup !== 'all'
        || $selectedUserStatus !== 'all';
@endphp

<div class="page-header user-page-header">
    <div class="user-page-header__heading">
        <span class="page-title">Kelola Pengguna</span>
        <span class="page-subtitle">Manajemen akun staff, hak akses (Role-Based), dan reset sandi (Separation of Duties).</span>
    </div>

    <button type="button" class="btn-tool btn-tool--primary user-page-header__action" id="btnAddUser">
        <i class="fi fi-rr-user-add" aria-hidden="true"></i>
        <span>Tambah Pengguna</span>
    </button>
</div>

@component('admin.layouts.card')
    <div class="user-card-header">
        <span class="section-card__title">Daftar Pengguna</span>

        <div class="user-toolbar">
            {{-- Pencarian tanpa tombol: form dikirim otomatis setelah ketikan
                 berhenti sejenak (debounce di skrip bawah). Filter dibawa
                 sebagai input tersembunyi supaya tidak hilang saat mencari. --}}
            <form class="user-toolbar__search"
                  method="GET"
                  action="{{ route('admin.user-manage') }}"
                  id="userSearchForm"
                  autocomplete="off">
                <div class="search-box">
                    <span><i class="fi fi-rr-search" aria-hidden="true"></i></span>
                    <input type="search"
                           id="userSearchInput"
                           name="q"
                           value="{{ $userSearch }}"
                           data-initial-value="{{ $userSearch }}"
                           placeholder="Cari nama, username, atau role"
                           aria-label="Cari pengguna">
                    <button type="button"
                            class="search-clear"
                            id="userSearchClear"
                            aria-label="Bersihkan pencarian"
                            @if ($userSearch === '') hidden @endif>
                        <i class="fi fi-br-cross-small" aria-hidden="true"></i>
                    </button>
                </div>
                <input type="hidden" name="role" value="{{ $selectedUserRole }}">
                <input type="hidden" name="regu" value="{{ $selectedUserGroup }}">
                <input type="hidden" name="status" value="{{ $selectedUserStatus }}">
            </form>

            <div class="user-filter" data-user-filter>
                <button type="button"
                        class="btn-tool user-filter__trigger {{ $hasUserFilter ? 'user-filter__trigger--active' : '' }}"
                        data-user-filter-trigger
                        aria-expanded="false"
                        aria-controls="user-filter-popover"
                        aria-label="Filter pengguna"
                        title="Filter pengguna">
                    <i class="fi fi-rr-settings-sliders" aria-hidden="true"></i>
                    @if ($hasUserFilter)
                        <span class="user-filter__dot" aria-hidden="true"></span>
                    @endif
                </button>

                <div class="user-filter__popover" id="user-filter-popover" data-user-filter-popover hidden>
                    <div class="user-filter__head">
                        <div>
                            <span class="user-filter__title">Filter Pengguna</span>
                            <span class="user-filter__subtitle">Saring daftar berdasarkan role, regu, dan status akun.</span>
                        </div>
                        <button type="button" class="user-filter__close" data-user-filter-close aria-label="Tutup filter">
                            <i class="fi fi-rr-cross-small" aria-hidden="true"></i>
                        </button>
                    </div>

                    <form method="GET" action="{{ route('admin.user-manage') }}" id="userFilterForm" autocomplete="off">
                        <input type="hidden" name="q" value="{{ $userSearch }}">

                        <div class="user-filter__fields">
                            <div class="filter-field">
                                <label>Role</label>
                                <div class="filter-select-wrapper">
                                    <select class="native-select" name="role" aria-label="Filter role">
                                        <option value="all" @selected($selectedUserRole === 'all')>Semua Role</option>
                                        @foreach (\App\Models\Role::NAMES as $roleName)
                                            <option value="{{ $roleName }}" @selected($selectedUserRole === $roleName)>
                                                {{ \App\Models\Role::displayName($roleName) }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <i class="fi fi-rr-angle-small-down select-arrow"></i>
                                </div>
                            </div>

                            <div class="filter-field">
                                <label>Regu</label>
                                <div class="filter-select-wrapper">
                                    <select class="native-select" name="regu" aria-label="Filter regu">
                                        <option value="all" @selected($selectedUserGroup === 'all')>Semua Regu</option>
                                        <option value="kantor" @selected($selectedUserGroup === 'kantor')>Kantor</option>
                                        @foreach (['A', 'B', 'C', 'D'] as $groupCode)
                                            <option value="{{ $groupCode }}" @selected($selectedUserGroup === $groupCode)>Regu {{ $groupCode }}</option>
                                        @endforeach
                                    </select>
                                    <i class="fi fi-rr-angle-small-down select-arrow"></i>
                                </div>
                            </div>

                            <div class="filter-field">
                                <label>Status</label>
                                <div class="filter-select-wrapper">
                                    <select class="native-select" name="status" aria-label="Filter status">
                                        <option value="all" @selected($selectedUserStatus === 'all')>Semua Status</option>
                                        <option value="aktif" @selected($selectedUserStatus === 'aktif')>Aktif</option>
                                        <option value="nonaktif" @selected($selectedUserStatus === 'nonaktif')>Non-Aktif</option>
                                    </select>
                                    <i class="fi fi-rr-angle-small-down select-arrow"></i>
                                </div>
                            </div>
                        </div>

                        <div class="user-filter__actions">
                            @if ($hasUserFilter)
                                <a href="{{ route('admin.user-manage', array_filter(['q' => $userSearch])) }}" class="btn-reset">Reset</a>
                            @endif
                            <button type="submit" class="btn-tool btn-tool--primary">
                                <i class="fi fi-rr-check" aria-hidden="true"></i> Terapkan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="table-responsive-wrapper">
        <table>
            <tr class="thead d-flex justify-content-between align-items-center">
                <th class="col-no">No</th>
                <th class="col-photo">Foto</th>
                <th class="col-name">Nama Lengkap</th>
                <th class="col-username">Username</th>
                <th class="col-role">Role</th>
                <th class="col-regu">Regu</th>
                <th class="col-status">Status</th>
                <th class="col-aksi">Aksi</th>
            </tr>

            @forelse ($users as $u)
                <tr class="tbody d-flex justify-content-between align-items-center"
                    data-user-name="{{ $u['name'] }}"
                    data-user-username="{{ $u['username'] }}"
                    data-user-role="{{ $u['role'] }}"
                    data-user-role-id="{{ $u['role_id'] ?? '' }}"
                    data-user-regu="{{ $u['regu'] }}"
                    data-user-group="{{ $u['group_value'] ?? $u['regu'] }}"
                    data-user-status="{{ $u['status'] }}"
                    data-user-self="{{ !empty($u['is_self']) ? '1' : '0' }}"
                    data-user-is-admin="{{ !empty($u['is_admin']) ? '1' : '0' }}"
                    data-user-update-url="{{ $u['update_url'] ?? '' }}"
                    data-user-signature-url="{{ $u['signature_url'] ?? '' }}">
                    <td class="col-no">{{ $u['no'] }}</td>
                    <td class="col-photo">
                        @php
                            $userPhotoUrl = $u['photo_url'] ?? '';
                            $userInitials = $u['initials'] ?? (collect(preg_split('/\s+/', trim((string) $u['name'])))
                                ->filter()
                                ->take(2)
                                ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
                                ->implode('') ?: 'U');
                        @endphp
                        <span class="user-avatar" title="Foto profil {{ $u['name'] }}">
                            <img src="{{ $userPhotoUrl }}"
                                 alt="Foto profil {{ $u['name'] }}"
                                 loading="lazy"
                                 @if (! $userPhotoUrl) hidden @endif
                                 onerror="this.hidden=true; this.nextElementSibling.hidden=false;">
                            <span aria-hidden="true" @if ($userPhotoUrl) hidden @endif>{{ $userInitials }}</span>
                        </span>
                    </td>
                    <td class="col-name">{{ $u['name'] }}</td>
                    <td class="col-username">{{ $u['username'] }}</td>
                    <td class="col-role">{{ $u['role'] }}</td>
                    <td class="col-regu">{{ $u['regu'] }}</td>
                    <td class="col-status">
                        <form method="POST" action="{{ $u['status_url'] ?? '#' }}">
                            @csrf
                            @method('PATCH')
                            <label class="user-status-switch {{ $u['status'] === 'aktif' ? 'is-active' : '' }}" title="Aktifkan atau nonaktifkan pengguna">
                                <input type="checkbox"
                                       class="js-user-status-toggle"
                                       @checked($u['status'] === 'aktif')
                                       aria-label="Status pengguna {{ $u['name'] }}"
                                       data-confirm
                                       data-confirm-submit="true"
                                       data-confirm-tone="warning"
                                       data-confirm-title="{{ $u['status'] === 'aktif' ? 'Nonaktifkan pengguna?' : 'Aktifkan pengguna?' }}"
                                       data-confirm-subtitle="Status akun akan diperbarui."
                                       data-confirm-message="Perubahan status langsung mempengaruhi akses login pengguna."
                                       data-confirm-summary="{{ $u['name'] }} - {{ $u['role'] }}"
                                       data-confirm-label="Ubah Status"
                                       data-confirm-icon="fi fi-rr-refresh">
                                <span class="user-status-switch__track"><span class="user-status-switch__thumb"></span></span>
                                <span class="user-status-switch__label">{{ $u['status_label'] }}</span>
                            </label>
                        </form>
                    </td>
                    <td class="col-aksi">
                        <button type="button" class="btn-act edit js-user-edit"><i class="fi fi-rr-pencil"></i> Edit</button>
                        <button type="button" class="btn-act view js-user-view" title="Lihat"><i class="fi fi-rr-eye"></i></button>
                        <form method="POST" action="{{ $u['destroy_url'] ?? '#' }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="btn-act delete"
                                    title="Hapus"
                                    data-confirm
                                    data-confirm-submit="true"
                                    data-confirm-tone="danger"
                                    data-confirm-title="Hapus pengguna?"
                                    data-confirm-subtitle="Akun akan dihapus dari daftar pengguna."
                                    data-confirm-message="Pastikan pengguna ini memang tidak lagi membutuhkan akses ke sistem."
                                    data-confirm-summary="{{ $u['name'] }} - {{ $u['role'] }} - {{ $u['regu'] }}"
                                    data-confirm-label="Hapus Pengguna">
                                <i class="fi fi-rr-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
            @empty
                @php
                    $userIsSearching = $userSearch !== '';
                    $userIsNarrowed = $userIsSearching || $hasUserFilter;
                    $userEmptyMessage = match (true) {
                        $userIsSearching && $hasUserFilter => 'Tidak ada pengguna yang cocok dengan pencarian "'.$userSearch.'" pada filter yang sedang aktif. Longgarkan filter atau ubah kata kuncinya.',
                        $userIsSearching => 'Tidak ada pengguna yang sesuai dengan pencarian "'.$userSearch.'". Coba kata kunci lain atau periksa ejaannya.',
                        $hasUserFilter => 'Tidak ada pengguna pada kombinasi role, regu, dan status yang sedang dipilih. Ubah atau reset filternya.',
                        default => 'Tambahkan pengguna baru lewat tombol "Tambah Pengguna" untuk mulai mengelola akun dan hak akses.',
                    };
                @endphp
                @include('admin.layouts.empty-state', [
                    'icon' => $userIsNarrowed ? 'fi fi-rr-search' : 'fi fi-rr-users',
                    'title' => $userIsNarrowed ? 'Tidak ada pengguna yang cocok' : 'Belum ada pengguna',
                    'message' => $userEmptyMessage,
                ])
            @endforelse
        </table>
    </div>
    @include('admin.layouts.pagination', ['paginator' => $users, 'label' => 'pengguna'])
@endcomponent

<div class="modal-overlay" id="userFormModal" aria-hidden="true">
    <div class="modal-box modal-box--wide modal-box--form-sheet" role="dialog" aria-modal="true" aria-labelledby="userFormTitle">
        <form method="POST" action="{{ route('admin.users.store') }}" id="userForm" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="_method" id="userFormMethod" value="POST">
            <div class="kss-modal__header">
                <div class="kss-modal__icon">
                    <i class="fi fi-rr-user-add" id="userFormIcon"></i>
                </div>
                <div class="kss-modal__heading">
                    <div class="kss-modal__title" id="userFormTitle">Tambah Pengguna</div>
                    <div class="kss-modal__subtitle" id="userFormSubtitle">Lengkapi detail akun dan hak akses pengguna.</div>
                </div>
                <button type="button" class="kss-modal__close" data-modal-close aria-label="Tutup modal">
                    <i class="fi fi-rr-cross-small"></i>
                </button>
            </div>

            <div class="kss-modal__body">
                <div class="kss-modal__grid">
                    <div class="kss-modal__field">
                        <label for="userNameInput">Nama Lengkap</label>
                        <input class="kss-modal__input" id="userNameInput" name="name" type="text" placeholder="cth, Budi Santoso" data-modal-focus required>
                    </div>
                    <div class="kss-modal__field">
                        <label for="userUsernameInput">Username</label>
                        <input class="kss-modal__input" id="userUsernameInput" name="username" type="text" placeholder="cth, budi.santoso" required>
                    </div>
                    <div class="kss-modal__field">
                        <label for="userRoleInput">Role</label>
                        <div class="kss-modal__select-wrapper">
                            <select class="kss-modal__native-select" id="userRoleInput" name="role_id" required>
                                @foreach ($roles as $roleOption)
                                    <option value="{{ $roleOption->id }}"
                                            data-role-name="{{ \App\Models\Role::normalize($roleOption->name) }}"
                                            @selected(\App\Models\Role::normalize($roleOption->name) === \App\Models\Role::OPERATIONAL)>
                                        {{ \App\Models\Role::displayName($roleOption->name) }}
                                    </option>
                                @endforeach
                            </select>
                            <i class="fi fi-rr-angle-small-down kss-modal__select-icon"></i>
                        </div>
                        <span class="user-field-hint" id="userReguHint">Role Operasional dapat memilih regu kerja.</span>
                    </div>
                    <div class="kss-modal__field">
                        <label for="userReguInput">Regu</label>
                        <div class="kss-modal__select-wrapper">
                            <select class="kss-modal__native-select" id="userReguInput" name="group">
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
                        <label class="user-status-switch is-active" id="userStatusInputWrap">
                            <input type="hidden" name="status" value="nonaktif">
                            <input type="checkbox" id="userStatusInput" name="status" value="aktif" checked>
                            <span class="user-status-switch__track"><span class="user-status-switch__thumb"></span></span>
                            <span class="user-status-switch__label" id="userStatusInputLabel">Aktif</span>
                        </label>
                    </div>
                    <div class="kss-modal__field">
                        <label for="userPasswordInput" id="userPasswordLabel">Password Awal</label>
                        <div class="kss-password-wrap">
                            <input class="kss-modal__input" id="userPasswordInput" name="password" type="password" minlength="5" placeholder="Min. 5 karakter">
                            <button type="button" class="kss-password-toggle" id="userPasswordToggle" aria-label="Tampilkan password" title="Tampilkan password">
                                <i class="fi fi-rr-eye"></i>
                            </button>
                        </div>
                        <span class="user-field-hint" id="userPasswordHint">Kredensial dapat disalin setelah pengguna berhasil dibuat.</span>
                    </div>
                    <div class="kss-modal__field kss-modal__field--full">
                        <label for="userSignatureInput">Tanda Tangan PNG</label>
                        <div class="signature-upload">
                            <div class="signature-upload__preview" id="userSignaturePreview">Belum ada tanda tangan</div>
                            <div class="signature-upload__control">
                                <div class="signature-upload__picker" id="userSignaturePicker">
                                    <input class="signature-upload__native" id="userSignatureInput" name="signature" type="file" accept="image/png">
                                    <label class="signature-upload__button" for="userSignatureInput" id="userSignatureButton" tabindex="0">
                                        <i class="fi fi-rr-cloud-upload-alt"></i>
                                        <span>Pilih File PNG</span>
                                    </label>
                                    <span class="signature-upload__filename" id="userSignatureFileName">Belum ada file dipilih</span>
                                </div>
                                <span class="signature-upload__hint">Gunakan file PNG, maksimal 2 MB. Saat edit, kosongkan jika tidak ingin mengganti tanda tangan.</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="kss-modal__footer">
                <button type="button" class="kss-modal__button" data-modal-close>Batal</button>
                <button type="submit" class="kss-modal__button kss-modal__button--primary" id="userFormSubmit">
                    <i class="fi fi-rr-disk"></i> Simpan Pengguna
                </button>
            </div>
        </form>
    </div>
</div>

@if (is_array($issuedCredentials) && isset($issuedCredentials['username'], $issuedCredentials['password']))
    <div class="modal-overlay" id="userCredentialsModal" aria-hidden="true">
        <div class="modal-box modal-box--sm" role="dialog" aria-modal="true" aria-labelledby="userCredentialsTitle">
            <div class="kss-modal__header">
                <div class="kss-modal__icon kss-modal__icon--success">
                    <i class="fi fi-rr-key"></i>
                </div>
                <div class="kss-modal__heading">
                    <div class="kss-modal__title" id="userCredentialsTitle">Kredensial Siap Disalin</div>
                    <div class="kss-modal__subtitle">Password hanya ditampilkan setelah dibuat atau di-reset.</div>
                </div>
                <button type="button" class="kss-modal__close" data-modal-close aria-label="Tutup modal">
                    <i class="fi fi-rr-cross-small"></i>
                </button>
            </div>
            <div class="kss-modal__body">
                <div class="credential-notice">
                    <i class="fi fi-rr-shield-check"></i>
                    <span>Simpan dan kirimkan kredensial ini melalui saluran yang aman. Setelah modal ditutup, password lama tidak dapat ditampilkan kembali.</span>
                </div>
                <div class="credential-card">
                    <div class="credential-card__row">
                        <span class="credential-card__label">Username</span>
                        <span class="credential-card__value">{{ $issuedCredentials['username'] }}</span>
                    </div>
                    <div class="credential-card__row">
                        <span class="credential-card__label">Password</span>
                        <span class="credential-card__value">{{ $issuedCredentials['password'] }}</span>
                    </div>
                </div>
            </div>
            <div class="kss-modal__footer">
                <button type="button" class="kss-modal__button" data-modal-close>Tutup</button>
                <button type="button" class="kss-modal__button kss-modal__button--primary" id="copyUserCredentials">
                    <i class="fi fi-rr-copy-alt"></i>
                    <span>Salin Username &amp; Password</span>
                </button>
            </div>
        </div>
    </div>
@endif
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const userModal = document.getElementById('userFormModal');
        const userTitle = document.getElementById('userFormTitle');
        const userSubtitle = document.getElementById('userFormSubtitle');
        const userIcon = document.getElementById('userFormIcon');
        const userSubmit = document.getElementById('userFormSubmit');
        const userForm = document.getElementById('userForm');
        const userFormMethod = document.getElementById('userFormMethod');
        const userStoreUrl = @json(route('admin.users.store'));
        const userStatusWrap = document.getElementById('userStatusInputWrap');
        const userStatusLabel = document.getElementById('userStatusInputLabel');
        const userReguHint = document.getElementById('userReguHint');
        const userPasswordLabel = document.getElementById('userPasswordLabel');
        const userPasswordHint = document.getElementById('userPasswordHint');
        const fields = {
            name: document.getElementById('userNameInput'),
            username: document.getElementById('userUsernameInput'),
            role: document.getElementById('userRoleInput'),
            regu: document.getElementById('userReguInput'),
            status: document.getElementById('userStatusInput'),
            password: document.getElementById('userPasswordInput'),
            signature: document.getElementById('userSignatureInput'),
        };
        const signaturePreview = document.getElementById('userSignaturePreview');
        const signaturePicker = document.getElementById('userSignaturePicker');
        const signatureButton = document.getElementById('userSignatureButton');
        const signatureFileName = document.getElementById('userSignatureFileName');
        const passwordToggle = document.getElementById('userPasswordToggle');
        const credentialsModal = document.getElementById('userCredentialsModal');
        const copyCredentialsButton = document.getElementById('copyUserCredentials');
        const issuedCredentials = @json($issuedCredentials);
        const defaultRoleId = fields.role?.value || '';
        let editingIsSelf = false;
        let currentUserMode = 'add';
        let currentSignatureUrl = '';

        function setPasswordVisible(visible) {
            if (!passwordToggle) return;
            fields.password.type = visible ? 'text' : 'password';
            const icon = passwordToggle.querySelector('i');
            if (icon) icon.className = visible ? 'fi fi-rr-eye-crossed' : 'fi fi-rr-eye';
            const label = visible ? 'Sembunyikan password' : 'Tampilkan password';
            passwordToggle.setAttribute('aria-label', label);
            passwordToggle.title = label;
        }

        if (passwordToggle) {
            passwordToggle.addEventListener('click', function () {
                setPasswordVisible(fields.password.type === 'password');
            });
        }

        function setFieldsDisabled(disabled) {
            Object.values(fields).forEach(field => field.disabled = disabled);
            signaturePicker?.classList.toggle('is-disabled', disabled);
            signatureButton?.setAttribute('tabindex', disabled ? '-1' : '0');
            if (userSubmit) userSubmit.hidden = disabled;
            syncReguForRole();
            window.KssAdminModal.syncSelects(userModal);
        }

        function selectedRoleName() {
            return fields.role?.options[fields.role.selectedIndex]?.dataset.roleName || '';
        }

        function syncReguForRole() {
            const isOperational = selectedRoleName() === 'operasional';

            if (!isOperational) {
                fields.regu.value = 'Kantor';
            }

            fields.regu.disabled = currentUserMode === 'view' || !isOperational;

            if (userReguHint) {
                userReguHint.textContent = isOperational
                    ? 'Role Operasional dapat memilih regu kerja.'
                    : 'Regu otomatis ditetapkan sebagai Kantor untuk role ini.';
            }

            window.KssAdminModal.syncSelects(userModal);
        }

        function setUserStatus(isActive) {
            fields.status.checked = isActive;
            userStatusWrap.classList.toggle('is-active', isActive);
            userStatusLabel.textContent = isActive ? 'Aktif' : 'Non-Aktif';
        }

        function fillUserForm(data = {}) {
            fields.name.value = data.name || '';
            fields.username.value = data.username || '';
            fields.role.value = data.roleId || defaultRoleId;
            fields.regu.value = data.group || 'A';
            setUserStatus((data.status || 'aktif').toLowerCase() === 'aktif');
            fields.password.value = '';
            setPasswordVisible(false);
            fields.password.placeholder = data.mode === 'edit' ? 'Kosongkan jika tidak diubah' : 'Min. 5 karakter';
            fields.password.required = data.mode !== 'edit' && data.mode !== 'view';
            if (userPasswordLabel) {
                userPasswordLabel.textContent = data.mode === 'edit' ? 'Password Baru' : 'Password Awal';
            }
            if (userPasswordHint) {
                userPasswordHint.textContent = data.mode === 'edit'
                    ? 'Isi hanya jika ingin reset. Kredensial baru dapat disalin setelah disimpan.'
                    : 'Kredensial dapat disalin setelah pengguna berhasil dibuat.';
            }
            fields.signature.value = '';
            currentSignatureUrl = data.signatureUrl || '';
            setSignaturePreview(currentSignatureUrl);
            setSignatureFileName('', Boolean(currentSignatureUrl));
            syncReguForRole();
            window.KssAdminModal.syncSelects(userModal);
        }

        function setSignaturePreview(url) {
            if (!signaturePreview) return;

            if (url) {
                signaturePreview.innerHTML = `<img src="${url}" alt="Preview tanda tangan">`;
            } else {
                signaturePreview.textContent = 'Belum ada tanda tangan';
            }
        }

        function setSignatureFileName(name = '', hasExistingSignature = false) {
            if (!signatureFileName) return;

            signatureFileName.textContent = name || (hasExistingSignature
                ? 'Tanda tangan tersimpan'
                : 'Belum ada file dipilih');
            signatureFileName.title = name || '';
        }

        function openUserModal(mode, row) {
            currentUserMode = mode;
            editingIsSelf = row?.dataset.userSelf === '1';
            const data = row ? {
                name: row.dataset.userName,
                username: row.dataset.userUsername,
                roleId: row.dataset.userRoleId,
                group: row.dataset.userGroup,
                status: row.dataset.userStatus,
                signatureUrl: row.dataset.userSignatureUrl,
                mode,
            } : { mode };

            fillUserForm(data);
            setFieldsDisabled(mode === 'view');
            if (userForm) userForm.action = mode === 'edit' && row?.dataset.userUpdateUrl ? row.dataset.userUpdateUrl : userStoreUrl;
            if (userFormMethod) userFormMethod.value = mode === 'edit' ? 'PUT' : 'POST';

            if (mode === 'add') {
                userTitle.textContent = 'Tambah Pengguna';
                userSubtitle.textContent = 'Buat akun baru beserta role dan regu pengguna.';
                userIcon.className = 'fi fi-rr-user-add';
                userSubmit.innerHTML = '<i class="fi fi-rr-disk"></i> Simpan Pengguna';
            } else if (mode === 'edit') {
                userTitle.textContent = 'Edit Pengguna';
                userSubtitle.textContent = 'Perbarui detail akun dan hak akses pengguna.';
                userIcon.className = 'fi fi-rr-pencil';
                userSubmit.innerHTML = '<i class="fi fi-rr-disk"></i> Simpan Perubahan';
            } else {
                userTitle.textContent = 'Detail Pengguna';
                userSubtitle.textContent = 'Ringkasan informasi akun pengguna.';
                userIcon.className = 'fi fi-rr-eye';
            }

            window.KssAdminModal.open(userModal);
        }

        document.getElementById('btnAddUser')?.addEventListener('click', () => openUserModal('add'));
        fields.role?.addEventListener('change', syncReguForRole);

        // =====================================================
        // PENCARIAN OTOMATIS (debounce)
        // Form dikirim sendiri setelah ketikan berhenti, jadi hasilnya
        // mencakup SELURUH pengguna — bukan hanya baris di halaman ini.
        // Setelah muat ulang, fokus dan posisi kursor dikembalikan ke akhir
        // teks supaya mengetik terasa tidak terputus.
        // =====================================================
        (function () {
            const searchForm = document.getElementById('userSearchForm');
            const searchInput = document.getElementById('userSearchInput');
            const searchClear = document.getElementById('userSearchClear');
            if (!searchForm || !searchInput) return;

            const initialValue = searchInput.dataset.initialValue || '';
            const debounceMs = 400;
            let timer = null;

            function submitSearch() {
                if (timer) window.clearTimeout(timer);
                // Halaman selalu kembali ke awal saat kata kunci berubah:
                // nomor halaman lama hampir pasti tidak ada lagi di hasil baru.
                searchForm.requestSubmit ? searchForm.requestSubmit() : searchForm.submit();
            }

            function scheduleSearch() {
                if (searchClear) searchClear.hidden = searchInput.value === '';
                if (timer) window.clearTimeout(timer);

                // Tidak perlu memuat ulang kalau nilainya kembali sama persis
                // dengan yang sedang ditampilkan server.
                if (searchInput.value.trim() === initialValue.trim()) return;

                timer = window.setTimeout(submitSearch, debounceMs);
            }

            searchInput.addEventListener('input', scheduleSearch);

            searchInput.addEventListener('keydown', function (event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    submitSearch();
                    return;
                }

                if (event.key === 'Escape' && searchInput.value !== '') {
                    event.preventDefault();
                    searchInput.value = '';
                    scheduleSearch();
                }
            });

            searchClear?.addEventListener('click', function () {
                searchInput.value = '';
                searchInput.focus();
                scheduleSearch();
            });

            if (initialValue !== '') {
                searchInput.focus();
                searchInput.setSelectionRange(searchInput.value.length, searchInput.value.length);
            }
        })();

        // =====================================================
        // POPOVER FILTER (role / regu / status)
        // Pola buka-tutupnya mengikuti filter Arsip Laporan.
        // =====================================================
        (function () {
            const filterRoot = document.querySelector('[data-user-filter]');
            const filterTrigger = filterRoot?.querySelector('[data-user-filter-trigger]');
            const filterPopover = filterRoot?.querySelector('[data-user-filter-popover]');
            const filterClose = filterRoot?.querySelector('[data-user-filter-close]');
            if (!filterRoot || !filterTrigger || !filterPopover) return;

            function setFilterOpen(open) {
                filterPopover.hidden = !open;
                filterTrigger.setAttribute('aria-expanded', open ? 'true' : 'false');
            }

            filterTrigger.addEventListener('click', () => setFilterOpen(filterPopover.hidden));
            filterClose?.addEventListener('click', () => {
                setFilterOpen(false);
                filterTrigger.focus();
            });

            document.addEventListener('click', function (event) {
                if (filterPopover.hidden || filterRoot.contains(event.target)) return;
                setFilterOpen(false);
            });

            document.addEventListener('keydown', function (event) {
                if (event.key !== 'Escape' || filterPopover.hidden) return;
                setFilterOpen(false);
                filterTrigger.focus();
            });

            // DROPDOWN KUSTOM — sama seperti pada Arsip Laporan supaya
            // tampilannya seragam antar halaman admin.
            filterPopover.querySelectorAll('.filter-select-wrapper').forEach(function (wrapper) {
                const select = wrapper.querySelector('select');
                if (!select) return;
                select.style.display = 'none';

                const trigger = document.createElement('div');
                trigger.className = 'filter-input filter-select-trigger';
                const label = document.createElement('span');
                label.textContent = select.options[select.selectedIndex].text.trim();
                trigger.appendChild(label);
                wrapper.insertBefore(trigger, select.nextSibling);

                const list = document.createElement('div');
                list.className = 'filter-select-options';
                Array.from(select.options).forEach(function (opt, i) {
                    const item = document.createElement('div');
                    item.className = 'filter-select-option';
                    item.textContent = opt.text.trim();
                    if (i === select.selectedIndex) item.classList.add('selected');
                    item.addEventListener('click', function (e) {
                        e.stopPropagation();
                        select.value = opt.value;
                        select.dispatchEvent(new Event('change'));
                        label.textContent = opt.text.trim();
                        list.querySelectorAll('.filter-select-option').forEach(o => o.classList.remove('selected'));
                        item.classList.add('selected');
                        list.classList.remove('open');
                        trigger.classList.remove('focus-active');
                    });
                    list.appendChild(item);
                });
                wrapper.appendChild(list);

                trigger.addEventListener('click', function (e) {
                    e.stopPropagation();
                    filterPopover.querySelectorAll('.filter-select-options.open').forEach(function (c) {
                        if (c === list) return;
                        c.classList.remove('open');
                        c.parentElement.querySelector('.filter-select-trigger')?.classList.remove('focus-active');
                    });
                    list.classList.toggle('open');
                    trigger.classList.toggle('focus-active');
                });
            });

            document.addEventListener('click', function () {
                filterPopover.querySelectorAll('.filter-select-options.open').forEach(c => c.classList.remove('open'));
                filterPopover.querySelectorAll('.filter-select-trigger.focus-active').forEach(t => t.classList.remove('focus-active'));
            });
        })();

        signatureButton?.addEventListener('keydown', function (event) {
            if ((event.key === 'Enter' || event.key === ' ') && !fields.signature.disabled) {
                event.preventDefault();
                fields.signature.click();
            }
        });

        fields.status?.addEventListener('change', () => {
            if (editingIsSelf && !fields.status.checked) {
                window.showAdminToast?.('error', 'Tidak diizinkan', 'Anda tidak bisa menonaktifkan akun Anda sendiri. Minta admin lain untuk menonaktifkannya.');
                setUserStatus(true);
                return;
            }
            setUserStatus(fields.status.checked);
        });

        fields.signature?.addEventListener('change', function () {
            const file = fields.signature.files?.[0];

            if (!file) {
                setSignaturePreview(currentSignatureUrl);
                setSignatureFileName('', Boolean(currentSignatureUrl));
                return;
            }

            if (file.type !== 'image/png') {
                fields.signature.value = '';
                setSignaturePreview(currentSignatureUrl);
                setSignatureFileName('', Boolean(currentSignatureUrl));
                window.showAdminToast?.('error', 'File tidak valid', 'File tanda tangan harus berformat PNG.');
                return;
            }

            setSignatureFileName(file.name);
            setSignaturePreview(URL.createObjectURL(file));
        });

        async function copyText(text) {
            if (navigator.clipboard?.writeText) {
                await navigator.clipboard.writeText(text);
                return;
            }

            const fallback = document.createElement('textarea');
            fallback.value = text;
            fallback.setAttribute('readonly', '');
            fallback.style.position = 'fixed';
            fallback.style.opacity = '0';
            document.body.appendChild(fallback);
            fallback.select();

            const copied = document.execCommand('copy');
            fallback.remove();

            if (!copied) {
                throw new Error('Clipboard tidak tersedia.');
            }
        }

        copyCredentialsButton?.addEventListener('click', async function () {
            if (!issuedCredentials?.username || !issuedCredentials?.password) return;

            const credentialText = `Username: ${issuedCredentials.username}\nPassword: ${issuedCredentials.password}`;

            try {
                await copyText(credentialText);
                copyCredentialsButton.innerHTML = '<i class="fi fi-rr-check"></i><span>Berhasil Disalin</span>';
                window.showAdminToast?.('success', 'Kredensial disalin', 'Username dan password sudah tersalin ke clipboard.');
            } catch (error) {
                window.showAdminToast?.('error', 'Gagal menyalin', 'Browser tidak mengizinkan akses clipboard. Salin kredensial secara manual.');
            }
        });

        document.querySelectorAll('.js-user-status-toggle').forEach(function (toggle) {
            const ownerRow = toggle.closest('tr');

            // Cegah admin menonaktifkan akunnya sendiri lewat tombol status di tabel.
            // Capture phase + stopImmediatePropagation agar berjalan sebelum dialog konfirmasi global.
            if (ownerRow?.dataset.userSelf === '1') {
                toggle.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    window.showAdminToast?.('error', 'Tidak diizinkan', 'Anda tidak bisa menonaktifkan akun Anda sendiri. Minta admin lain untuk menonaktifkannya.');
                }, true);
            }

            toggle.addEventListener('change', function () {
                const row = toggle.closest('tr');
                const switchWrap = toggle.closest('.user-status-switch');
                const label = switchWrap?.querySelector('.user-status-switch__label');
                const isActive = toggle.checked;

                switchWrap?.classList.toggle('is-active', isActive);
                if (label) label.textContent = isActive ? 'Aktif' : 'Non-Aktif';
                if (row) row.dataset.userStatus = isActive ? 'aktif' : 'nonaktif';
            });
        });

        document.querySelectorAll('.js-user-edit').forEach(button => {
            button.addEventListener('click', () => openUserModal('edit', button.closest('tr')));
        });

        document.querySelectorAll('.js-user-view').forEach(button => {
            button.addEventListener('click', () => openUserModal('view', button.closest('tr')));
        });

        if (credentialsModal && issuedCredentials?.username && issuedCredentials?.password) {
            window.KssAdminModal.open(credentialsModal);
        }
    });
</script>
@endpush
