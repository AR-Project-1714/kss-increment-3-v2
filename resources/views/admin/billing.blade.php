@extends('admin.layouts.app')

@section('title', 'KSS Admin - Billing Cloud')
@section('active', 'billing')

@push('styles')
<style>
    .billing-shell { display: flex; flex-direction: column; gap: 18px; }

    .billing-summary {
        overflow: hidden;
        border: 1px solid var(--smooth-border);
        border-radius: 14px;
        background: var(--white);
        box-shadow: 0 8px 28px rgba(37, 99, 235, .08);
    }

    .billing-summary__header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        padding: 20px 22px;
        border-bottom: 1px solid var(--smooth-border);
    }

    .billing-identity { display: flex; align-items: center; gap: 13px; min-width: 0; }
    .billing-identity__icon {
        display: grid;
        width: 46px;
        height: 46px;
        place-items: center;
        flex: 0 0 auto;
        border-radius: 12px;
        background: var(--blue-main-10);
        color: var(--blue-main);
        font-size: 20px;
    }
    .billing-identity__eyebrow,
    .billing-metric__label {
        color: var(--muted);
        font-size: 10px;
        font-weight: 700;
        letter-spacing: .07em;
        text-transform: uppercase;
    }
    .billing-identity__title {
        margin: 2px 0 0;
        color: var(--black);
        font-size: 16px;
        font-weight: 700;
    }
    .billing-identity__updated { margin-top: 3px; color: var(--muted); font-size: 10px; }

    .billing-status {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 6px 10px;
        border-radius: 999px;
        background: rgba(5, 150, 105, .11);
        color: #047857;
        font-size: 11px;
        font-weight: 700;
        white-space: nowrap;
    }
    .billing-status::before { width: 7px; height: 7px; border-radius: 50%; background: currentColor; content: ""; }
    .billing-status--warning { background: rgba(180, 83, 9, .12); color: #b45309; }
    .billing-status--critical { background: rgba(185, 28, 28, .11); color: #b91c1c; }
    .billing-status--unavailable { background: var(--main-bg); color: var(--black-secondary); }

    .billing-summary__metrics {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(0, 1.15fr);
        gap: 0;
    }
    .billing-metric { min-width: 0; padding: 24px 22px; }
    .billing-metric + .billing-metric { border-left: 1px solid var(--smooth-border); }
    .billing-metric__value {
        display: block;
        margin-top: 7px;
        color: var(--black);
        font-size: clamp(25px, 3vw, 34px);
        font-weight: 750;
        line-height: 1.1;
        letter-spacing: -.025em;
        overflow-wrap: anywhere;
    }
    .billing-metric__helper { display: block; margin-top: 7px; color: var(--muted); font-size: 11px; line-height: 1.45; }
    .billing-runway {
        height: 8px;
        margin-top: 16px;
        overflow: hidden;
        border-radius: 999px;
        background: var(--main-bg);
    }
    .billing-runway__fill {
        display: block;
        width: var(--runway);
        height: 100%;
        border-radius: inherit;
        background: #059669;
    }
    .billing-summary--warning .billing-runway__fill { background: #b45309; }
    .billing-summary--critical .billing-runway__fill { background: #b91c1c; }
    .billing-runway__meta { display: flex; justify-content: space-between; gap: 12px; margin-top: 7px; color: var(--muted); font-size: 9px; }

    .billing-alert {
        display: flex;
        align-items: flex-start;
        gap: 9px;
        padding: 11px 22px;
        border-top: 1px solid var(--smooth-border);
        background: rgba(180, 83, 9, .08);
        color: #92400e;
        font-size: 11px;
        font-weight: 600;
    }
    .billing-alert--critical { background: rgba(185, 28, 28, .08); color: #991b1b; }
    .billing-alert--unavailable { background: var(--main-bg); color: var(--black-secondary); }

    /* Mengikuti pola tab glass + sticky pada Bantuan/Kegiatan Manajer. */
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
        scrollbar-width: thin;
        scrollbar-color: var(--blue-main-25) transparent;
    }
    body.dark-mode .billing-tabs {
        background-color: rgba(15, 23, 42, .72);
        border-color: rgba(255, 255, 255, .12);
        box-shadow: 0 8px 24px rgba(0, 0, 0, .45), inset 0 1px 0 rgba(255, 255, 255, .08);
    }
    .billing-tabs::-webkit-scrollbar { display: block; height: 6px; }
    .billing-tabs::-webkit-scrollbar-track { background: transparent; margin-inline: 6px; }
    .billing-tabs::-webkit-scrollbar-thumb { border-radius: 999px; background-color: var(--blue-main-25); }
    .billing-tabs::-webkit-scrollbar-thumb:hover { background-color: var(--blue-main-40); }
    .billing-tab {
        position: relative;
        z-index: 1;
        display: flex;
        min-width: 140px;
        flex: 1 0 auto;
        justify-content: center;
        align-items: center;
        gap: 8px;
        padding: 6px 12px;
        border-radius: 8px;
        color: var(--black-secondary);
        font-size: 12px;
        font-weight: 500;
        white-space: nowrap;
        transition: color .2s ease-out, background-color .2s ease-out;
    }
    .billing-tab i { position: relative; top: 1px; font-size: 12px; }
    .billing-tab:hover { background-color: var(--blue-main-10); color: var(--blue-main); }
    .billing-tab.is-active, .billing-tab.is-active:hover { color: var(--white-pure); background-color: var(--blue-main); }
    .billing-tabs.is-indicator-ready .billing-tab.is-active,
    .billing-tabs.is-indicator-ready .billing-tab.is-active:hover { background-color: transparent; }
    .billing-tab:focus-visible { outline: none; box-shadow: 0 0 0 3px var(--blue-main-10); }
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

    .billing-grid { display: flex; flex-direction: column; gap: 18px; }
    .billing-card {
        overflow: hidden;
        border: 1px solid var(--smooth-border);
        border-radius: 12px;
        background: var(--white);
        box-shadow: 0 2px 7px rgba(15, 23, 42, .04);
        scroll-margin-top: 90px;
    }
    .billing-card__header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 14px;
        padding: 14px 16px 12px;
        border-bottom: 1px solid var(--smooth-border);
    }
    .billing-card__title { color: var(--black); font-size: 14px; font-weight: 700; }
    .billing-card__subtitle { margin: 3px 0 0; color: var(--muted); font-size: 10px; line-height: 1.45; }
    .billing-card__count { color: var(--muted); font-size: 10px; white-space: nowrap; }
    .billing-table-wrap { overflow-x: auto; }
    .billing-table { width: 100%; min-width: 590px; border-collapse: collapse; }
    .billing-table--compact { min-width: 470px; }
    .billing-table th {
        padding: 10px 16px;
        background: var(--main-bg);
        color: var(--muted);
        font-size: 9px;
        font-weight: 700;
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
    .billing-table tbody tr:hover { background: var(--blue-main-5); }
    .billing-table__body.is-collapsed .billing-table__row--extra { display: none; }
    .billing-table__id { color: var(--blue-main); font-weight: 650; }
    .billing-table__amount { color: var(--black); font-weight: 700; white-space: nowrap; }
    .billing-table__amount--credit { color: #047857; }
    .billing-table__amount--debit { color: #b91c1c; }
    .billing-card__footer {
        display: flex;
        justify-content: flex-start;
        padding: 11px 16px;
        border-top: 1px solid var(--smooth-border);
    }
    .billing-show-all {
        border: 0;
        padding: 4px 0;
        background: transparent;
        color: var(--muted);
        font-family: inherit;
        font-size: 11px;
        font-weight: 600;
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
        background: rgba(5, 150, 105, .1);
        color: #047857;
        font-size: 9px;
        font-weight: 700;
        white-space: nowrap;
    }
    .billing-pill::before { width: 5px; height: 5px; border-radius: 50%; background: currentColor; content: ""; }
    .billing-pill--ongoing { background: rgba(37, 99, 235, .1); color: #1d4ed8; }
    .billing-pill--unpaid { background: rgba(180, 83, 9, .11); color: #b45309; }
    .billing-empty { padding: 30px 18px; color: var(--muted); font-size: 11px; text-align: center; }
    .billing-errors { display: grid; gap: 8px; }

    body.dark-mode .billing-status { color: #6ee7b7; }
    body.dark-mode .billing-status--warning { color: #fbbf24; }
    body.dark-mode .billing-status--critical { color: #fca5a5; }
    body.dark-mode .billing-table__amount--credit { color: #6ee7b7; }
    body.dark-mode .billing-table__amount--debit { color: #fca5a5; }
    body.dark-mode .billing-pill { color: #6ee7b7; }
    body.dark-mode .billing-pill--ongoing { color: #93c5fd; }
    body.dark-mode .billing-pill--unpaid { color: #fbbf24; }

    @media (max-width: 620px) {
        .billing-summary__header { align-items: flex-start; padding: 17px; }
        .billing-summary__metrics { grid-template-columns: 1fr; }
        .billing-metric { padding: 20px 17px; }
        .billing-metric + .billing-metric { border-top: 1px solid var(--smooth-border); border-left: 0; }
        .billing-alert { padding-inline: 17px; }
        .billing-card__header { padding: 13px 14px 11px; }
        .billing-tab { min-width: 44px; padding: 8px 10px; gap: 0; }
        .billing-tab span { display: none; }
    }
</style>
@endpush

@section('content')
@php
    $billing = $billing ?? [
        'available' => false,
        'level' => 'unavailable',
        'level_label' => 'Tidak tersedia',
        'credit_formatted' => '—',
        'remaining_label' => '—',
        'runway_percent' => 0,
        'message' => 'Informasi billing belum tersedia.',
        'reports' => [],
        'topup_invoices' => [],
        'balance_history' => [],
        'partial_errors' => [],
    ];
    $tablePreviewLimit = 10;
@endphp

<div class="page-header">
    <span class="page-title">Billing Cloud</span>
    <span class="page-subtitle">Pantau kredit, pemakaian, invoice top-up, dan riwayat saldo IDCloudHost.</span>
</div>

<div class="billing-shell">
    <section class="billing-summary billing-summary--{{ $billing['level'] }}" aria-labelledby="billingSummaryTitle">
        <div class="billing-summary__header">
            <div class="billing-identity">
                <span class="billing-identity__icon" aria-hidden="true"><i class="fi fi-rr-cloud"></i></span>
                <div>
                    <span class="billing-identity__eyebrow">Infrastruktur Cloud</span>
                    <h1 class="billing-identity__title" id="billingSummaryTitle">IDCloudHost Billing</h1>
                    <div class="billing-identity__updated">
                        @if ($billing['available'])
                            Diperbarui <time datetime="{{ $billing['captured_iso'] }}">{{ $billing['captured_label'] }}</time>
                            @if ($billing['is_stale']) · snapshot terakhir @endif
                        @else
                            Monitoring saldo server
                        @endif
                    </div>
                </div>
            </div>
            <span class="billing-status billing-status--{{ $billing['level'] }}">{{ $billing['level_label'] }}</span>
        </div>

        <div class="billing-summary__metrics">
            <div class="billing-metric">
                <span class="billing-metric__label">Remaining Credit</span>
                <strong class="billing-metric__value">{{ $billing['credit_formatted'] }}</strong>
                <span class="billing-metric__helper">Saldo efektif setelah pemakaian berjalan.</span>
            </div>
            <div class="billing-metric">
                <span class="billing-metric__label">Estimasi Masa Aktif</span>
                <strong class="billing-metric__value">{{ $billing['remaining_label'] }}</strong>
                <span class="billing-metric__helper">
                    @if ($billing['available'] && $billing['daily_cost_formatted'])
                        Berdasarkan estimasi pemakaian {{ $billing['daily_cost_formatted'] }} per hari.
                    @else
                        Estimasi tersedia setelah histori biaya terkumpul.
                    @endif
                </span>
                <div class="billing-runway" role="progressbar" aria-label="Estimasi masa aktif terhadap target enam bulan"
                     aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $billing['runway_percent'] }}">
                    <span class="billing-runway__fill" style="--runway: {{ $billing['runway_percent'] }}%"></span>
                </div>
                <div class="billing-runway__meta"><span>0 hari</span><span>Target aman 6 bulan</span></div>
            </div>
        </div>

        @if ($billing['message'])
            <div class="billing-alert billing-alert--{{ $billing['level'] }}"
                 @if (in_array($billing['level'], ['warning', 'critical'], true)) role="alert" @endif>
                <i class="fi fi-rr-triangle-warning" aria-hidden="true"></i>
                <span>{{ $billing['message'] }}</span>
            </div>
        @endif
    </section>

    @if ($billing['partial_errors'])
        <div class="billing-errors" role="status">
            @foreach ($billing['partial_errors'] as $error)
                <div class="billing-alert billing-alert--unavailable">
                    <i class="fi fi-rr-info" aria-hidden="true"></i><span>{{ $error }}</span>
                </div>
            @endforeach
        </div>
    @endif

    <nav class="billing-tabs" id="billingTabs" aria-label="Bagian billing">
        <a class="billing-tab is-active" href="#reports" data-billing-tab="reports" aria-current="true">
            <i class="fi fi-rr-chart-histogram" aria-hidden="true"></i><span>Laporan Pemakaian</span>
        </a>
        <a class="billing-tab" href="#topup-invoices" data-billing-tab="topup-invoices">
            <i class="fi fi-rr-receipt" aria-hidden="true"></i><span>Invoice Top Up</span>
        </a>
        <a class="billing-tab" href="#balance-history" data-billing-tab="balance-history">
            <i class="fi fi-rr-time-past" aria-hidden="true"></i><span>Riwayat Saldo</span>
        </a>
        <span class="billing-tab-indicator" id="billingTabIndicator" aria-hidden="true"></span>
    </nav>

    <div class="billing-grid">
        <section class="billing-card" id="reports" aria-labelledby="reportsTitle">
            <header class="billing-card__header">
                <div>
                    <h2 class="billing-card__title" id="reportsTitle">Laporan Pemakaian</h2>
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
                <div class="billing-empty">Belum ada laporan pemakaian yang dapat ditampilkan.</div>
            @endif
        </section>

        <section class="billing-card" id="topup-invoices" aria-labelledby="topupTitle">
                <header class="billing-card__header">
                    <div>
                        <h2 class="billing-card__title" id="topupTitle">Invoice Top Up</h2>
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
                    <div class="billing-empty">Belum ada invoice top up.</div>
                @endif
        </section>

        <section class="billing-card" id="balance-history" aria-labelledby="historyTitle">
                <header class="billing-card__header">
                    <div>
                        <h2 class="billing-card__title" id="historyTitle">Riwayat Saldo</h2>
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
                    <div class="billing-empty">Belum ada riwayat saldo.</div>
                @endif
        </section>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var tabs = Array.from(document.querySelectorAll('[data-billing-tab]'));
        var tabStrip = document.getElementById('billingTabs');
        var indicator = document.getElementById('billingTabIndicator');

        function activateTab(key) {
            var activeTab = null;

            tabs.forEach(function (tab) {
                var active = tab.getAttribute('data-billing-tab') === key;
                tab.classList.toggle('is-active', active);

                if (active) {
                    tab.setAttribute('aria-current', 'true');
                    activeTab = tab;
                } else {
                    tab.removeAttribute('aria-current');
                }
            });

            if (! activeTab || ! indicator || ! tabStrip) {
                return;
            }

            indicator.style.width = activeTab.offsetWidth + 'px';
            indicator.style.transform = 'translateX(' + activeTab.offsetLeft + 'px)';
            tabStrip.classList.add('is-indicator-ready');
        }

        tabs.forEach(function (tab) {
            tab.addEventListener('click', function () {
                activateTab(tab.getAttribute('data-billing-tab'));
            });
        });

        window.addEventListener('resize', function () {
            var active = document.querySelector('[data-billing-tab].is-active');
            if (active) {
                activateTab(active.getAttribute('data-billing-tab'));
            }
        });

        if ('IntersectionObserver' in window) {
            var sectionObserver = new IntersectionObserver(function (entries) {
                var visible = entries
                    .filter(function (entry) { return entry.isIntersecting; })
                    .sort(function (left, right) { return right.intersectionRatio - left.intersectionRatio; })[0];

                if (visible) {
                    activateTab(visible.target.id);
                }
            }, { rootMargin: '-18% 0px -68% 0px', threshold: [0.05, 0.2, 0.5] });

            ['reports', 'topup-invoices', 'balance-history'].forEach(function (id) {
                var section = document.getElementById(id);
                if (section) {
                    sectionObserver.observe(section);
                }
            });
        }

        activateTab('reports');

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
