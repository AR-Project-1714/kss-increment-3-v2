@extends('manajer.layouts.app')

@section('title', 'Rincian Kegiatan - Manajer')

@section('content')
    <main class="page-content">
        <div class="page-header">
            <span class="page-title">Rincian Kegiatan Operasi</span>
            <span class="page-subtitle">Bedah lima jenis kegiatan satu per satu: capaian, tren, peringkat regu, dan daftar kegiatannya.</span>
        </div>

        @include('manajer.partials.performance-toolbar', ['formRoute' => 'manajer.kegiatan'])

        {{-- Strip kartu ringkas selalu tampil, panel rinciannya baru diambil
             saat tabnya dibuka. --}}
        <div class="section-card">
            <div class="section-card__header">
                <div class="section-card__heading">
                    <span class="section-card__title">Performa per Kegiatan</span>
                    <span class="section-card__subtitle">
                        {{ $report['periodLabel'] }} · container bersatuan Teus sehingga tidak ikut dijumlahkan ke Total Tonase.
                    </span>
                </div>
            </div>
            <div class="section-card__body">
                @include('manajer.partials.activity-strip', [
                    'cards' => $activityCards,
                    'comparisonLabel' => $report['comparisonLabel'],
                ])

                <div class="act-tabs" role="tablist" aria-label="Rincian per kegiatan">
                    @foreach ($activityCards as $index => $card)
                        <button type="button"
                                class="act-tab {{ $index === 0 ? 'is-active' : '' }}"
                                role="tab"
                                aria-selected="{{ $index === 0 ? 'true' : 'false' }}"
                                data-activity-tab="{{ $card['key'] }}"
                                data-activity-url="{{ route('manajer.kegiatan.panel', array_merge(['key' => $card['key']], request()->query())) }}">
                            <i class="{{ $card['icon'] }}" aria-hidden="true"></i>
                            <span>{{ $card['short'] }}</span>
                        </button>
                    @endforeach
                </div>

                {{-- Diisi lewat permintaan terpisah saat panel masuk layar. --}}
                <div class="act-panel" id="activity-panel" role="tabpanel" aria-live="polite">
                    <div class="act-panel__loading">
                        <span class="act-skeleton act-skeleton--metrics"></span>
                        <span class="act-skeleton act-skeleton--block"></span>
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection

@push('scripts')
    <script src="{{ asset('js/components/charts.js') }}?v={{ @filemtime(public_path('js/components/charts.js')) }}"></script>
@endpush
