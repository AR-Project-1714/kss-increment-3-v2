@extends('manajer.layouts.app')

@section('title', 'Rincian Kegiatan - Manajer')

@section('content')
    @php
        $requestedActivity = (string) request('kegiatan', '');
        $activityKeys = array_column($activities, 'key');
        $activeActivity = in_array($requestedActivity, $activityKeys, true)
            ? $requestedActivity
            : ($activityKeys[0] ?? '');
    @endphp
    <main class="page-content page-content--has-mobile-tabbar">
        <div class="page-header performance-page-header">
            <div class="performance-page-header__heading">
                <span class="page-title">Rincian Kegiatan Operasi</span>
                <span class="page-subtitle">
                    Pilih satu kegiatan pada {{ $report['periodLabel'] }} untuk melihat rekap, peringkat regu,
                    beban kerja, lembur, dan daftar kegiatannya.
                </span>
            </div>

            @include('manajer.partials.performance-toolbar', [
                'formRoute' => 'manajer.kegiatan',
                'exportRoute' => 'manajer.kegiatan.export',
                'exportTitle' => 'Unduh gambaran besar dan rincian seluruh kegiatan (Excel)',
                'primaryExport' => true,
                'hidePopoverExport' => true,
            ])
        </div>

        @if (($report['provisionalReportCount'] ?? 0) > 0)
            <div class="performance-verification-note" role="status">
                <i class="fi fi-rr-clock-three" aria-hidden="true"></i>
                <span>
                    <strong>{{ $report['provisionalReportCount'] }} laporan masih menunggu serah-terima.</strong>
                    Rincian di bawah sudah mencakup laporan tersebut sebagai data lapangan sementara sampai diterima regu berikutnya.
                </span>
            </div>
        @endif

        <div class="act-tabs" id="activityTabs" role="tablist" aria-label="Rincian per kegiatan">
            @foreach ($activities as $index => $activity)
                @php($isActive = $activity['key'] === $activeActivity)
                <button type="button"
                        class="act-tab {{ $isActive ? 'is-active' : '' }}"
                        id="activity-tab-{{ $activity['key'] }}"
                        role="tab"
                        aria-selected="{{ $isActive ? 'true' : 'false' }}"
                        aria-controls="activity-panel"
                        aria-label="{{ $activity['short'] }}, {{ $activity['unit'] }}"
                        title="{{ $activity['short'] }} ({{ $activity['unit'] }})"
                        data-activity-tab="{{ $activity['key'] }}"
                        data-activity-url="{{ route('manajer.kegiatan.panel', array_merge(['key' => $activity['key']], request()->query())) }}">
                    <i class="{{ $activity['icon'] }}" aria-hidden="true"></i>
                    <span>{{ $activity['short'] }}</span>
                    <span class="act-tab__unit">{{ $activity['unit'] }}</span>
                </button>
            @endforeach
            <span class="act-tab-indicator" id="activityTabIndicator" aria-hidden="true"></span>
        </div>

        {{-- Diisi lewat permintaan terpisah setelah halaman siap. --}}
        <div class="act-panel" id="activity-panel" role="tabpanel" aria-live="polite" aria-labelledby="activity-tab-{{ $activeActivity }}">
            <div class="act-panel__loading">
                <span class="act-skeleton act-skeleton--metrics"></span>
                <span class="act-skeleton act-skeleton--block"></span>
            </div>
        </div>
    </main>
@endsection

@push('scripts')
    <script src="{{ asset('js/components/charts.js') }}?v={{ @filemtime(public_path('js/components/charts.js')) }}"></script>
@endpush
