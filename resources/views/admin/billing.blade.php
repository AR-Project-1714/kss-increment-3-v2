@extends('admin.layouts.app')

@section('title', 'KSS Admin - Billing Cloud')
@section('active', 'billing')

@push('styles')
<style>
    .billing-shell { display: flex; flex-direction: column; gap: 18px; }

    /* =============================================
       HERO: pita identitas + baris kartu metrik

       Kartu metrik memakai ulang anatomi .kpi-card (charts.css) dalam
       ukuran lebih kecil: kepala ikon + label, angka, lalu keterangan.
       Nada status dibawa ikon dan chip, bukan garis tebal di tepi kartu.
       ============================================= */
    .billing-hero { display: flex; flex-direction: column; gap: 12px; }

    .billing-profile {
        display: grid;
        grid-template-columns: minmax(0, 250px) minmax(0, 1fr);
        align-items: center;
        gap: 14px 20px;
        padding: 14px 16px;
        border: 1px solid var(--smooth-border);
        border-radius: 14px;
        background-color: var(--white);
        box-shadow: 0 2px 4px rgba(37, 99, 235, .07);
        transition: background-color .3s ease, border-color .3s ease;
    }

    .billing-profile__head { display: flex; align-items: center; gap: 11px; min-width: 0; }

    .billing-profile__icon {
        display: grid;
        width: 40px;
        height: 40px;
        flex: 0 0 auto;
        place-items: center;
        border-radius: 11px;
        background-color: var(--blue-main-10);
        color: var(--blue-main);
        font-size: 17px;
    }
    .billing-profile__icon i { position: relative; top: 1px; line-height: 1; }
    .billing-hero--warning .billing-profile__icon { background-color: var(--orange-main-10); color: var(--orange-main); }
    .billing-hero--critical .billing-profile__icon { background-color: var(--red-main-10); color: var(--red-main); }
    .billing-hero--unavailable .billing-profile__icon { background-color: var(--main-bg); color: var(--muted); }

    .billing-profile__title { margin: 0; color: var(--black); font-size: 14px; font-weight: 600; line-height: 1.3; }
    .billing-profile__chips { display: flex; flex-wrap: wrap; gap: 5px; margin-top: 6px; }

    .billing-chip {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 3px 8px;
        border-radius: 999px;
        background-color: var(--blue-main-10);
        color: var(--blue-main);
        font-size: 9px;
        font-weight: 500;
        white-space: nowrap;
    }
    .billing-chip--neutral {
        padding: 2px 7px;
        border: 1px solid var(--smooth-border);
        background-color: var(--main-bg);
        color: var(--black-secondary);
    }
    .billing-chip--status::before { width: 5px; height: 5px; border-radius: 50%; background-color: currentColor; content: ""; }
    .billing-chip--healthy { background-color: var(--success-10); color: var(--success); }
    .billing-chip--warning { background-color: var(--orange-main-10); color: var(--orange-main); }
    .billing-chip--critical { background-color: var(--red-main-10); color: var(--red-main); }
    .billing-chip--unavailable { background-color: var(--main-bg); color: var(--muted); }

    .billing-profile__meta {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        grid-template-rows: auto auto;
        gap: 12px 18px;
        margin: 0;
        padding-left: 20px;
        border-left: 1px solid var(--smooth-border);
    }
    .billing-profile__meta > div { min-width: 0; }

    /* Label "Terakhir Diperbarui" melipat dua baris di lebar menengah
       sementara tetangganya satu baris. Subgrid menyamakan baris label dan
       baris nilai antar kolom supaya nilainya tidak ikut turun sendiri.
       Jarak dt→dd tetap dipegang margin dd agar sama dengan fallback. */
    @supports (grid-template-rows: subgrid) {
        .billing-profile__meta > div {
            display: grid;
            grid-row: span 2;
            grid-template-rows: subgrid;
            row-gap: 0;
        }
    }
    .billing-profile__meta dt {
        color: var(--muted);
        font-size: 10px;
        font-weight: 400;
        line-height: 1.4;
    }
    .billing-profile__meta dd {
        margin: 3px 0 0;
        color: var(--black);
        font-size: 11px;
        font-weight: 500;
        line-height: 1.4;
        overflow-wrap: anywhere;
    }

    .billing-metrics {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 12px;
    }

    .billing-metric {
        display: flex;
        min-width: 0;
        flex-direction: column;
        gap: 8px;
        padding: 13px 14px;
        border: 1px solid var(--smooth-border);
        border-radius: 14px;
        background-color: var(--white);
        box-shadow: 0 2px 4px rgba(37, 99, 235, .07);
        transition: background-color .3s ease, border-color .3s ease;
    }

    .billing-metric__head { display: flex; align-items: center; gap: 9px; min-width: 0; }

    .billing-metric__icon {
        display: flex;
        width: 30px;
        height: 30px;
        flex-shrink: 0;
        align-items: center;
        justify-content: center;
        border-radius: 9px;
        font-size: 13px;
    }
    .billing-metric__icon i { position: relative; top: 2px; line-height: 1; }
    .billing-metric__icon--blue   { background-color: var(--blue-main-10);   color: var(--blue-main); }
    .billing-metric__icon--cyan   { background-color: var(--cyan-main-10);   color: var(--cyan-main); }
    .billing-metric__icon--orange { background-color: var(--orange-main-10); color: var(--orange-main); }

    /* Kartu masa aktif ikut nada kesehatan saldo — ikonnya jadi penanda
       status setelah garis tebal di tepi kartu identitas dilepas. */
    .billing-metric__icon--runway { background-color: var(--success-10); color: var(--success); }
    .billing-hero--warning .billing-metric__icon--runway { background-color: var(--orange-main-10); color: var(--orange-main); }
    .billing-hero--critical .billing-metric__icon--runway { background-color: var(--red-main-10); color: var(--red-main); }
    .billing-hero--unavailable .billing-metric__icon--runway { background-color: var(--main-bg); color: var(--muted); }

    .billing-metric__label {
        color: var(--black-secondary);
        font-size: 11px;
        font-weight: 500;
        line-height: 1.35;
    }

    .billing-metric__value {
        display: block;
        color: var(--black);
        font-size: 20px;
        font-weight: 600;
        line-height: 1.2;
        font-variant-numeric: tabular-nums;
        overflow-wrap: anywhere;
    }

    .billing-metric__note {
        display: block;
        margin-top: auto;
        color: var(--muted);
        font-size: 10px;
        line-height: 1.45;
    }
    .billing-metric__foot { margin-top: auto; }

    .billing-runway {
        height: 5px;
        overflow: hidden;
        border-radius: 999px;
        background-color: var(--smooth-border);
    }
    .billing-runway__fill {
        display: block;
        width: var(--runway);
        height: 100%;
        border-radius: inherit;
        background-color: var(--success);
    }
    .billing-hero--warning .billing-runway__fill { background-color: var(--orange-main); }
    .billing-hero--critical .billing-runway__fill { background-color: var(--red-main); }
    .billing-hero--unavailable .billing-runway__fill { background-color: var(--muted); }
    .billing-runway__meta { display: flex; justify-content: space-between; gap: 10px; margin-top: 6px; color: var(--muted); font-size: 9px; }

    .billing-alert {
        display: flex;
        align-items: flex-start;
        gap: 9px;
        padding: 11px 16px;
        border: 1px solid transparent;
        border-radius: 12px;
        background-color: var(--orange-main-10);
        color: var(--orange-main);
        font-size: 11px;
        font-weight: 500;
        line-height: 1.45;
    }
    .billing-alert i { position: relative; top: 1px; flex-shrink: 0; }
    .billing-alert--critical { background-color: var(--red-main-10); color: var(--red-main); }
    .billing-alert--healthy,
    .billing-alert--unavailable { background-color: var(--main-bg); border-color: var(--smooth-border); color: var(--black-secondary); }

    .billing-errors { display: grid; gap: 8px; }

    /* =============================================
       TAB (pola glass sticky seperti Bantuan)
       ============================================= */
    .billing-tabs {
        position: sticky;
        top: 0;
        z-index: 6;
        flex-shrink: 0;
        display: flex;
        width: 100%;
        min-width: 0;
        max-width: 100%;
        align-items: center;
        align-content: center;
        gap: 5px 10px;
        overflow-x: auto;
        overflow-y: hidden;
        padding: 5px;
        -webkit-overflow-scrolling: touch;
        overscroll-behavior-inline: contain;
        scroll-behavior: smooth;
        background-color: rgba(255, 255, 255, .72);
        -webkit-backdrop-filter: blur(18px) saturate(180%);
        backdrop-filter: blur(18px) saturate(180%);
        border: 1px solid rgba(255, 255, 255, .5);
        border-radius: 10px;
        box-shadow: 0 8px 24px rgba(15, 23, 42, .08), inset 0 1px 0 rgba(255, 255, 255, .7);
        scrollbar-width: none;
    }
    body.dark-mode .billing-tabs {
        background-color: rgba(15, 23, 42, .72);
        border-color: rgba(255, 255, 255, .12);
        box-shadow: 0 8px 24px rgba(0, 0, 0, .45), inset 0 1px 0 rgba(255, 255, 255, .08);
    }
    .billing-tabs::-webkit-scrollbar { display: none; }

    .billing-tab {
        position: relative;
        z-index: 1;
        display: flex;
        min-width: 150px;
        flex: 1 0 auto;
        justify-content: center;
        align-items: center;
        gap: 8px;
        padding: 6px 12px;
        border: none;
        border-radius: 8px;
        background: transparent;
        color: var(--black-secondary);
        font-family: inherit;
        font-size: 12px;
        font-weight: 500;
        white-space: nowrap;
        cursor: pointer;
        transition: color .2s ease-out, background-color .2s ease-out;
    }
    .billing-tab i { position: relative; top: 1px; font-size: 12px; }
    .billing-tab:hover { background-color: var(--blue-main-10); color: var(--blue-main); }
    .billing-tab.is-active { color: var(--white-pure); background-color: var(--blue-main); }
    .billing-tabs.is-indicator-ready .billing-tab.is-active,
    .billing-tabs.is-indicator-ready .billing-tab.is-active:hover { background-color: transparent; }
    .billing-tab.is-active:hover { color: var(--white-pure); }
    .billing-tab:focus-visible { outline: none; box-shadow: 0 0 0 3px var(--blue-main-25); }

    .billing-tab__count {
        display: inline-flex;
        min-width: 18px;
        justify-content: center;
        padding: 1px 5px;
        border-radius: 999px;
        background-color: var(--blue-main-10);
        color: var(--blue-main);
        font-size: 9px;
        font-weight: 500;
        line-height: 1.5;
        transition: background-color .2s ease-out, color .2s ease-out;
    }
    .billing-tab.is-active .billing-tab__count { background-color: rgba(255, 255, 255, .22); color: var(--white-pure); }

    .billing-tab-indicator {
        position: absolute;
        z-index: 0;
        top: 5px;
        bottom: 5px;
        left: 0;
        width: 0;
        border-radius: 8px;
        background-color: var(--blue-main);
        box-shadow: 0 0 4px var(--blue-main-40);
        transform: translateX(0);
        transition: transform .34s cubic-bezier(.22,1,.36,1), width .34s cubic-bezier(.22,1,.36,1);
        pointer-events: none;
    }
    /* Tab aktif bisa dipulihkan dari sesi sebelumnya; penempatan pertama
       jangan ikut dianimasikan dari posisi nol. */
    .billing-tabs:not(.is-indicator-ready) .billing-tab-indicator { transition: none; }

    /* =============================================
       PANEL TAB
       ============================================= */
    .billing-panel[hidden] { display: none; }
    .billing-panel { animation: billingPanelIn .26s ease-out both; }
    .billing-panel:focus-visible { outline: none; }

    @keyframes billingPanelIn {
        from { opacity: 0; transform: translateY(6px); }
        to   { opacity: 1; transform: none; }
    }
    @media (prefers-reduced-motion: reduce) {
        .billing-panel { animation: none; }
        .billing-tab-indicator { transition: none; }
    }

    .billing-card {
        overflow: hidden;
        border: 1px solid var(--smooth-border);
        border-radius: 14px;
        background-color: var(--white);
        box-shadow: 0 2px 4px rgba(37, 99, 235, .07);
    }
    .billing-card__header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 14px;
        padding: 14px 16px 12px;
        border-bottom: 1px solid var(--smooth-border);
    }
    .billing-card__title { color: var(--black); font-size: 13px; font-weight: 600; }
    .billing-card__subtitle { margin: 3px 0 0; color: var(--muted); font-size: 10px; line-height: 1.45; }
    .billing-card__count { color: var(--muted); font-size: 10px; white-space: nowrap; }

    .billing-table-wrap { overflow-x: auto; }
    .billing-table { width: 100%; min-width: 590px; border-collapse: collapse; }
    .billing-table--compact { min-width: 470px; }
    .billing-table th {
        padding: 10px 16px;
        background-color: var(--main-bg);
        color: var(--muted);
        font-size: 9px;
        font-weight: 500;
        letter-spacing: .05em;
        text-align: left;
        text-transform: uppercase;
    }
    .billing-table td {
        padding: 12px 16px;
        border-top: 1px solid var(--smooth-border);
        color: var(--black-secondary);
        font-size: 10px;
        vertical-align: middle;
    }
    .billing-table tbody tr:first-child td { border-top: 0; }
    .billing-table tbody tr:hover { background-color: var(--blue-main-5); }
    .billing-table__body.is-collapsed .billing-table__row--extra { display: none; }
    .billing-table__id { color: var(--blue-main); font-weight: 500; }
    .billing-table__amount { color: var(--black); font-weight: 500; white-space: nowrap; font-variant-numeric: tabular-nums; }
    .billing-table__amount--credit { color: var(--success); }
    .billing-table__amount--debit { color: var(--red-main); }

    .billing-card__footer {
        display: flex;
        justify-content: flex-start;
        padding: 11px 16px;
        border-top: 1px solid var(--smooth-border);
    }
    .billing-show-all {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        border: 0;
        padding: 4px 0;
        background: transparent;
        color: var(--muted);
        font-family: inherit;
        font-size: 11px;
        font-weight: 500;
        cursor: pointer;
        transition: color .15s ease;
    }
    .billing-show-all:hover { color: var(--blue-main); }
    .billing-show-all:focus-visible { outline: 2px solid var(--blue-main); outline-offset: 3px; border-radius: 4px; }
    .billing-show-all i { font-size: 12px; line-height: 0; transition: transform .18s ease; }
    .billing-show-all[aria-expanded="true"] i { transform: rotate(180deg); }

    .billing-pill {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 4px 7px;
        border-radius: 999px;
        background-color: var(--success-10);
        color: var(--success);
        font-size: 9px;
        font-weight: 500;
        white-space: nowrap;
    }
    .billing-pill::before { width: 5px; height: 5px; border-radius: 50%; background-color: currentColor; content: ""; }
    .billing-pill--ongoing { background-color: var(--blue-main-10); color: var(--blue-main); }
    .billing-pill--unpaid { background-color: var(--orange-main-10); color: var(--orange-main); }

    .billing-empty { padding: 34px 18px; color: var(--muted); font-size: 11px; text-align: center; }
    .billing-empty i { display: block; margin-bottom: 8px; font-size: 22px; opacity: .55; }

    /* Pita identitas melipat lebih dulu: pemisah pindah dari sisi kiri
       ke garis atas begitu kolomnya jadi satu. */
    @media (max-width: 1100px) {
        .billing-profile { grid-template-columns: minmax(0, 1fr); align-items: start; }
        .billing-profile__meta {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            padding-top: 13px;
            padding-left: 0;
            border-top: 1px solid var(--smooth-border);
            border-left: 0;
        }
        .billing-metrics { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }

    @media (max-width: 620px) {
        .billing-card__header { padding: 13px 14px 11px; }
    }

    /* =============================================
       MOBILE: tab pindah jadi island mengambang di
       bawah layar — bar sticky di atas hanya masuk
       akal saat ada ruang lebar; di ponsel ia berebut
       tempat dengan konten dan gampang tersembunyi
       saat scroll. Struktur & JS indikator tidak
       berubah (offsetWidth/offsetLeft tetap dihitung
       dari DOM yang sama), hanya reposisi lewat CSS.
       ============================================= */
    @media (max-width: 767px) {
        .billing-shell { padding-bottom: calc(76px + env(safe-area-inset-bottom)); }

        .billing-tabs {
            position: fixed;
            top: auto;
            bottom: calc(14px + env(safe-area-inset-bottom));
            left: 50%;
            right: auto;
            z-index: 850;
            width: max-content;
            max-width: calc(100vw - 28px);
            transform: translateX(-50%);
            gap: 4px;
            padding: 6px;
            border-radius: 999px;
            box-shadow: 0 18px 40px rgba(15, 23, 42, .16), 0 2px 10px rgba(15, 23, 42, .08), inset 0 1px 0 rgba(255, 255, 255, .7);
            scroll-snap-type: x proximity;
        }
        body.dark-mode .billing-tabs {
            box-shadow: 0 18px 40px rgba(0, 0, 0, .55), 0 2px 10px rgba(0, 0, 0, .3), inset 0 1px 0 rgba(255, 255, 255, .08);
        }

        .billing-tab {
            scroll-snap-align: start;
            flex: 0 0 auto;
            min-width: 46px;
            min-height: 46px;
            padding: 8px 12px;
            gap: 0;
            border-radius: 999px;
        }
        .billing-tab span { display: none; }
        .billing-tab i { font-size: 15px; }

        .billing-tab-indicator {
            top: 6px;
            bottom: 6px;
            border-radius: 999px;
            box-shadow: 0 0 14px 1px var(--blue-main-40);
        }
    }

    /* Ponsel: kartu identitas dikecilkan (padding, ikon, judul), dan empat
       kartu metrik tetap 2x2 alih-alih ditumpuk 1 kolom ke bawah — pola yang
       sama dengan .kpi-row supaya konten lain di bawahnya tidak terlalu jauh. */
    @media (max-width: 430px) {
        .billing-profile {
            padding: 12px 14px;
            gap: 10px 14px;
        }
        .billing-profile__head { gap: 9px; }
        .billing-profile__icon { width: 34px; height: 34px; border-radius: 9px; font-size: 15px; }
        .billing-profile__title { font-size: 13px; }
        .billing-profile__chips { gap: 4px; margin-top: 5px; }
        .billing-profile__meta {
            grid-template-columns: minmax(0, 1fr);
            gap: 10px;
            padding-top: 11px;
        }

        .billing-metrics { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; }
        .billing-metric { padding: 11px 10px; gap: 7px; }
        .billing-metric__head { gap: 7px; }
        .billing-metric__icon { width: 26px; height: 26px; font-size: 11px; }
        .billing-metric__label { font-size: 10px; }
        .billing-metric__value { font-size: 16px; }
        .billing-metric__note { font-size: 9px; }
        .billing-runway__meta { font-size: 8px; }
    }

    @media (max-width: 340px) {
        .billing-metrics { grid-template-columns: minmax(0, 1fr); }
    }
</style>
@endpush

@section('content')
@php
    $billing = array_merge([
        'available' => false,
        'level' => 'unavailable',
        'level_label' => 'Tidak tersedia',
        'credit_formatted' => '-',
        'remaining_label' => '-',
        'runway_percent' => 0,
        'message' => 'Informasi billing belum tersedia.',
        'reports' => [],
        'topup_invoices' => [],
        'balance_history' => [],
        'partial_errors' => [],
    ], $billing ?? []);

    $tablePreviewLimit = 10;

    $estimateSourceLabel = match ($billing['estimate_source'] ?? null) {
        'configured' => 'Konfigurasi manual',
        'invoice_history' => 'Histori invoice',
        'balance_trend' => 'Tren saldo',
        default => '-',
    };

    $billingTabs = [
        [
            'key' => 'reports',
            'label' => 'Laporan Pemakaian',
            'icon' => 'fi fi-rr-chart-histogram',
            'count' => count($billing['reports']),
        ],
        [
            'key' => 'topup-invoices',
            'label' => 'Invoice Top Up',
            'icon' => 'fi fi-rr-receipt',
            'count' => count($billing['topup_invoices']),
        ],
        [
            'key' => 'balance-history',
            'label' => 'Riwayat Saldo',
            'icon' => 'fi fi-rr-time-past',
            'count' => count($billing['balance_history']),
        ],
    ];
@endphp

<div class="page-header">
    <span class="page-title">Billing Cloud</span>
    <span class="page-subtitle">Pantau kredit, pemakaian, invoice top-up, dan riwayat saldo IDCloudHost.</span>
</div>

<div class="billing-shell">
    <section class="billing-hero billing-hero--{{ $billing['level'] }}" aria-labelledby="billingProfileTitle">
        <article class="billing-profile">
            <div class="billing-profile__head">
                <span class="billing-profile__icon" aria-hidden="true"><i class="fi fi-rr-cloud"></i></span>
                <div>
                    <h1 class="billing-profile__title" id="billingProfileTitle">
                        {{ $billing['account_title'] ?? 'IDCloudHost Billing' }}
                    </h1>
                    <div class="billing-profile__chips">
                        <span class="billing-chip billing-chip--status billing-chip--{{ $billing['level'] }}">{{ $billing['level_label'] }}</span>
                        <span class="billing-chip billing-chip--neutral">Infrastruktur Cloud</span>
                        @if (! empty($billing['is_stale']))
                            <span class="billing-chip billing-chip--neutral">Snapshot terakhir</span>
                        @endif
                    </div>
                </div>
            </div>

            <dl class="billing-profile__meta">
                <div>
                    <dt>Billing Account</dt>
                    <dd>{{ $billing['billing_account_id'] ?? '-' }}</dd>
                </div>
                <div>
                    <dt>Status Layanan</dt>
                    <dd>
                        @if (! $billing['available'])
                            Tidak tersedia
                        @else
                            {{ ($billing['is_active'] ?? true) ? 'Aktif' : 'Nonaktif' }}
                        @endif
                    </dd>
                </div>
                <div>
                    <dt>Sumber Estimasi</dt>
                    <dd>{{ $billing['available'] ? $estimateSourceLabel : '-' }}</dd>
                </div>
                <div>
                    <dt>Terakhir Diperbarui</dt>
                    <dd>
                        @if ($billing['available'] && ! empty($billing['captured_iso']))
                            <time datetime="{{ $billing['captured_iso'] }}">{{ $billing['captured_label'] }}</time>
                        @else
                            -
                        @endif
                    </dd>
                </div>
            </dl>
        </article>

        <div class="billing-metrics">
            <article class="billing-metric">
                <div class="billing-metric__head">
                    <span class="billing-metric__icon billing-metric__icon--blue" aria-hidden="true"><i class="fi fi-rr-wallet"></i></span>
                    <span class="billing-metric__label">Remaining Credit</span>
                </div>
                <strong class="billing-metric__value">{{ $billing['credit_formatted'] }}</strong>
                <span class="billing-metric__note">Saldo efektif setelah pemakaian berjalan.</span>
            </article>

            <article class="billing-metric">
                <div class="billing-metric__head">
                    <span class="billing-metric__icon billing-metric__icon--runway" aria-hidden="true"><i class="fi fi-rr-calendar-clock"></i></span>
                    <span class="billing-metric__label">Estimasi Masa Aktif</span>
                </div>
                <strong class="billing-metric__value">{{ $billing['remaining_label'] }}</strong>
                <div class="billing-metric__foot">
                    <div class="billing-runway" role="progressbar" aria-label="Estimasi masa aktif terhadap target enam bulan"
                         aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $billing['runway_percent'] }}">
                        <span class="billing-runway__fill" style="--runway: {{ $billing['runway_percent'] }}%"></span>
                    </div>
                    <div class="billing-runway__meta"><span>0 hari</span><span>Target aman 6 bulan</span></div>
                </div>
            </article>

            <article class="billing-metric">
                <div class="billing-metric__head">
                    <span class="billing-metric__icon billing-metric__icon--cyan" aria-hidden="true"><i class="fi fi-rr-coins"></i></span>
                    <span class="billing-metric__label">Estimasi Biaya Harian</span>
                </div>
                <strong class="billing-metric__value">{{ $billing['daily_cost_formatted'] ?? '-' }}</strong>
                <span class="billing-metric__note">
                    @if ($billing['available'] && ! empty($billing['daily_cost_formatted']))
                        Dasar perhitungan estimasi masa aktif.
                    @else
                        Tersedia setelah histori biaya terkumpul.
                    @endif
                </span>
            </article>

            <article class="billing-metric">
                <div class="billing-metric__head">
                    <span class="billing-metric__icon billing-metric__icon--orange" aria-hidden="true"><i class="fi fi-rr-chart-histogram"></i></span>
                    <span class="billing-metric__label">Pemakaian Berjalan</span>
                </div>
                <strong class="billing-metric__value">{{ $billing['usage_total_formatted'] ?? '-' }}</strong>
                <span class="billing-metric__note">Akumulasi biaya periode berjalan.</span>
            </article>
        </div>
    </section>

    @if ($billing['message'])
        <div class="billing-alert billing-alert--{{ $billing['level'] }}"
             @if (in_array($billing['level'], ['warning', 'critical'], true)) role="alert" @endif>
            <i class="fi fi-rr-triangle-warning" aria-hidden="true"></i>
            <span>{{ $billing['message'] }}</span>
        </div>
    @endif

    @if ($billing['partial_errors'])
        <div class="billing-errors" role="status">
            @foreach ($billing['partial_errors'] as $error)
                <div class="billing-alert billing-alert--unavailable">
                    <i class="fi fi-rr-info" aria-hidden="true"></i><span>{{ $error }}</span>
                </div>
            @endforeach
        </div>
    @endif

    <div class="billing-tabs" id="billingTabs" role="tablist" aria-label="Bagian billing">
        @foreach ($billingTabs as $tab)
            <button type="button"
                    class="billing-tab {{ $loop->first ? 'is-active' : '' }}"
                    id="billing-tab-{{ $tab['key'] }}"
                    role="tab"
                    data-billing-tab="{{ $tab['key'] }}"
                    aria-controls="billing-panel-{{ $tab['key'] }}"
                    aria-selected="{{ $loop->first ? 'true' : 'false' }}"
                    aria-label="{{ $tab['label'] }} ({{ $tab['count'] }})"
                    tabindex="{{ $loop->first ? '0' : '-1' }}">
                <i class="{{ $tab['icon'] }}" aria-hidden="true"></i>
                <span>{{ $tab['label'] }}</span>
                <span class="billing-tab__count">{{ $tab['count'] }}</span>
            </button>
        @endforeach
        <span class="billing-tab-indicator" id="billingTabIndicator" aria-hidden="true"></span>
    </div>

    <section class="billing-panel"
             id="billing-panel-reports"
             role="tabpanel"
             aria-labelledby="billing-tab-reports"
             tabindex="0">
        <div class="billing-card">
            <header class="billing-card__header">
                <div>
                    <h2 class="billing-card__title">Laporan Pemakaian</h2>
                    <p class="billing-card__subtitle">Biaya berjalan dan laporan pemakaian bulanan.</p>
                </div>
                <span class="billing-card__count">{{ count($billing['reports']) }} laporan</span>
            </header>
            @if ($billing['reports'])
                <div class="billing-table-wrap">
                    <table class="billing-table">
                        <thead><tr><th>Laporan</th><th>Status</th><th>Periode</th><th>Pemakaian</th></tr></thead>
                        <tbody id="billing-reports-table" class="billing-table__body {{ count($billing['reports']) > $tablePreviewLimit ? 'is-collapsed' : '' }}">
                        @foreach ($billing['reports'] as $report)
                            <tr class="{{ $loop->index >= $tablePreviewLimit ? 'billing-table__row--extra' : '' }}">
                                <td class="billing-table__id">{{ $report['id'] }}</td>
                                <td><span class="billing-pill billing-pill--{{ $report['status_tone'] }}">{{ $report['status'] }}</span></td>
                                <td>{{ $report['period'] }}</td>
                                <td class="billing-table__amount">{{ $report['amount_formatted'] }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                @if (count($billing['reports']) > $tablePreviewLimit)
                    <div class="billing-card__footer">
                        <button class="billing-show-all" type="button" data-billing-show-all="billing-reports-table" aria-controls="billing-reports-table" aria-expanded="false"
                                data-label-more="Lihat semua {{ count($billing['reports']) }} laporan" data-label-less="Tampilkan {{ $tablePreviewLimit }} teratas">
                            <span data-billing-show-all-label>Lihat semua {{ count($billing['reports']) }} laporan</span>
                            <i class="fi fi-rr-angle-small-down" aria-hidden="true"></i>
                        </button>
                    </div>
                @endif
            @else
                <div class="billing-empty">
                    <i class="fi fi-rr-chart-histogram" aria-hidden="true"></i>
                    Belum ada laporan pemakaian yang dapat ditampilkan.
                </div>
            @endif
        </div>
    </section>

    <section class="billing-panel"
             id="billing-panel-topup-invoices"
             role="tabpanel"
             aria-labelledby="billing-tab-topup-invoices"
             tabindex="0"
             hidden>
        <div class="billing-card">
            <header class="billing-card__header">
                <div>
                    <h2 class="billing-card__title">Invoice Top Up</h2>
                    <p class="billing-card__subtitle">Invoice penambahan saldo IDCloudHost.</p>
                </div>
                <span class="billing-card__count">{{ count($billing['topup_invoices']) }} invoice</span>
            </header>
            @if ($billing['topup_invoices'])
                <div class="billing-table-wrap">
                    <table class="billing-table billing-table--compact">
                        <thead><tr><th>Invoice</th><th>Status</th><th>Terbit</th><th>Total</th></tr></thead>
                        <tbody id="billing-topups-table" class="billing-table__body {{ count($billing['topup_invoices']) > $tablePreviewLimit ? 'is-collapsed' : '' }}">
                        @foreach ($billing['topup_invoices'] as $invoice)
                            <tr class="{{ $loop->index >= $tablePreviewLimit ? 'billing-table__row--extra' : '' }}">
                                <td class="billing-table__id">{{ $invoice['id'] }}</td>
                                <td><span class="billing-pill billing-pill--{{ $invoice['status_tone'] }}">{{ $invoice['status'] }}</span></td>
                                <td>{{ $invoice['issued'] }}</td>
                                <td class="billing-table__amount">{{ $invoice['total_formatted'] }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                @if (count($billing['topup_invoices']) > $tablePreviewLimit)
                    <div class="billing-card__footer">
                        <button class="billing-show-all" type="button" data-billing-show-all="billing-topups-table" aria-controls="billing-topups-table" aria-expanded="false"
                                data-label-more="Lihat semua {{ count($billing['topup_invoices']) }} invoice" data-label-less="Tampilkan {{ $tablePreviewLimit }} teratas">
                            <span data-billing-show-all-label>Lihat semua {{ count($billing['topup_invoices']) }} invoice</span>
                            <i class="fi fi-rr-angle-small-down" aria-hidden="true"></i>
                        </button>
                    </div>
                @endif
            @else
                <div class="billing-empty">
                    <i class="fi fi-rr-receipt" aria-hidden="true"></i>
                    Belum ada invoice top up.
                </div>
            @endif
        </div>
    </section>

    <section class="billing-panel"
             id="billing-panel-balance-history"
             role="tabpanel"
             aria-labelledby="billing-tab-balance-history"
             tabindex="0"
             hidden>
        <div class="billing-card">
            <header class="billing-card__header">
                <div>
                    <h2 class="billing-card__title">Riwayat Saldo</h2>
                    <p class="billing-card__subtitle">Mutasi kredit dan pembayaran invoice terbaru.</p>
                </div>
                <span class="billing-card__count">{{ count($billing['balance_history']) }} transaksi</span>
            </header>
            @if ($billing['balance_history'])
                <div class="billing-table-wrap">
                    <table class="billing-table billing-table--compact">
                        <thead><tr><th>Tanggal</th><th>Jumlah</th><th>Deskripsi</th></tr></thead>
                        <tbody id="billing-history-table" class="billing-table__body {{ count($billing['balance_history']) > $tablePreviewLimit ? 'is-collapsed' : '' }}">
                        @foreach ($billing['balance_history'] as $record)
                            <tr class="{{ $loop->index >= $tablePreviewLimit ? 'billing-table__row--extra' : '' }}">
                                <td>{{ $record['date'] }}</td>
                                <td class="billing-table__amount billing-table__amount--{{ $record['tone'] }}">{{ $record['amount_formatted'] }}</td>
                                <td>{{ $record['description'] }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                @if (count($billing['balance_history']) > $tablePreviewLimit)
                    <div class="billing-card__footer">
                        <button class="billing-show-all" type="button" data-billing-show-all="billing-history-table" aria-controls="billing-history-table" aria-expanded="false"
                                data-label-more="Lihat semua {{ count($billing['balance_history']) }} transaksi" data-label-less="Tampilkan {{ $tablePreviewLimit }} teratas">
                            <span data-billing-show-all-label>Lihat semua {{ count($billing['balance_history']) }} transaksi</span>
                            <i class="fi fi-rr-angle-small-down" aria-hidden="true"></i>
                        </button>
                    </div>
                @endif
            @else
                <div class="billing-empty">
                    <i class="fi fi-rr-time-past" aria-hidden="true"></i>
                    Belum ada riwayat saldo.
                </div>
            @endif
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var tabStrip = document.getElementById('billingTabs');
        var indicator = document.getElementById('billingTabIndicator');
        var tabs = Array.prototype.slice.call(document.querySelectorAll('[data-billing-tab]'));

        if (! tabs.length) {
            return;
        }

        var STORAGE_KEY = 'billing:active-tab';

        function panelFor(tab) {
            return document.getElementById(tab.getAttribute('aria-controls'));
        }

        function moveIndicator(activeTab) {
            if (! indicator || ! tabStrip || ! activeTab.offsetWidth) {
                return;
            }

            indicator.style.width = activeTab.offsetWidth + 'px';
            indicator.style.transform = 'translateX(' + activeTab.offsetLeft + 'px)';
            tabStrip.classList.add('is-indicator-ready');
        }

        // Panel disembunyikan penuh (atribut hidden), bukan sekadar anchor scroll:
        // tiap koleksi billing hanya tampil lewat tab-nya masing-masing.
        function activateTab(key, options) {
            var settings = options || {};
            var activeTab = null;

            tabs.forEach(function (tab) {
                var isActive = tab.getAttribute('data-billing-tab') === key;
                var panel = panelFor(tab);

                tab.classList.toggle('is-active', isActive);
                tab.setAttribute('aria-selected', String(isActive));
                tab.setAttribute('tabindex', isActive ? '0' : '-1');

                if (panel) {
                    panel.hidden = ! isActive;
                }

                if (isActive) {
                    activeTab = tab;
                }
            });

            if (! activeTab) {
                return;
            }

            moveIndicator(activeTab);

            if (settings.focus) {
                activeTab.focus();
            }

            if (settings.scrollIntoView !== false) {
                activeTab.scrollIntoView({ block: 'nearest', inline: 'nearest' });
            }

            try {
                window.sessionStorage.setItem(STORAGE_KEY, key);
            } catch (error) {
                /* penyimpanan sesi tidak tersedia — abaikan */
            }
        }

        tabs.forEach(function (tab, index) {
            tab.addEventListener('click', function () {
                activateTab(tab.getAttribute('data-billing-tab'));
            });

            tab.addEventListener('keydown', function (event) {
                var step = 0;

                if (event.key === 'ArrowRight' || event.key === 'ArrowDown') {
                    step = 1;
                } else if (event.key === 'ArrowLeft' || event.key === 'ArrowUp') {
                    step = -1;
                } else if (event.key === 'Home') {
                    step = -index;
                } else if (event.key === 'End') {
                    step = tabs.length - 1 - index;
                } else {
                    return;
                }

                event.preventDefault();

                var target = tabs[(index + step + tabs.length) % tabs.length];
                activateTab(target.getAttribute('data-billing-tab'), { focus: true });
            });
        });

        function refreshIndicator() {
            var active = document.querySelector('[data-billing-tab].is-active');
            if (active) {
                moveIndicator(active);
            }
        }

        window.addEventListener('resize', refreshIndicator);

        // Lebar tab ikut berubah tanpa resize window — sidebar dilipat, ikon
        // webfont selesai dimuat. ResizeObserver menjaga indikator tetap pas.
        if ('ResizeObserver' in window) {
            new ResizeObserver(refreshIndicator).observe(tabStrip);
        }

        function initialKey() {
            var candidates = [window.location.hash.replace('#', '')];

            try {
                candidates.push(window.sessionStorage.getItem(STORAGE_KEY));
            } catch (error) {
                /* penyimpanan sesi tidak tersedia — abaikan */
            }

            for (var i = 0; i < candidates.length; i++) {
                var match = tabs.some(function (tab) {
                    return tab.getAttribute('data-billing-tab') === candidates[i];
                });

                if (match) {
                    return candidates[i];
                }
            }

            return tabs[0].getAttribute('data-billing-tab');
        }

        activateTab(initialKey(), { scrollIntoView: false });

        document.querySelectorAll('[data-billing-show-all]').forEach(function (button) {
            button.addEventListener('click', function () {
                var table = document.getElementById(button.getAttribute('data-billing-show-all'));
                var expanded = button.getAttribute('aria-expanded') === 'true';
                var label = button.querySelector('[data-billing-show-all-label]');

                if (! table) {
                    return;
                }

                table.classList.toggle('is-collapsed', expanded);
                button.setAttribute('aria-expanded', String(! expanded));

                if (label) {
                    label.textContent = expanded
                        ? button.getAttribute('data-label-more')
                        : button.getAttribute('data-label-less');
                }
            });
        });
    });
</script>
@endpush
