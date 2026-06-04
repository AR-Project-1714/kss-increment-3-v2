@extends('manajer.layouts.app')

@push('styles')
    <style>
        .empty-state-manager {
            width: 100%;
            padding: 34px 18px;
            border: 1px dashed var(--divider);
            border-radius: 10px;
            color: var(--muted);
            background-color: var(--blue-main-3);
        }

        .empty-state-manager .empty-icon {
            width: 44px;
            height: 44px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            color: var(--success);
            background-color: var(--success-10);
            font-size: 20px;
        }

        .modal-box__signature img {
            width: 90px;
            height: 56px;
            object-fit: contain;
        }

        .report-button a.btn {
            text-decoration: none;
        }
    </style>
@endpush

@section('content')
    @php
        $manager = auth()->user();
        $documentId = function ($report): string {
            $date = $report->report_date ?: $report->created_at;

            try {
                $year = $date ? \Carbon\Carbon::parse($date)->format('Y') : now()->format('Y');
            } catch (\Throwable) {
                $year = now()->format('Y');
            }

            return '#OPS-'.$year.'-'.str_pad((string) $report->id, 3, '0', STR_PAD_LEFT);
        };
        $formatDate = fn ($date) => $date ? $date->locale('id')->translatedFormat('d F Y') : '-';
        $formatDiff = fn ($date) => $date ? $date->locale('id')->diffForHumans() : '-';
        $shiftMeta = function ($shift): array {
            $normalized = strtolower(trim((string) $shift));

            return match (true) {
                in_array($normalized, ['1', 'pagi', 'shift 1', 'shift pagi'], true) => ['label' => 'Shift Pagi', 'class' => 'pagi', 'icon' => 'fi fi-rr-sunrise'],
                in_array($normalized, ['2', 'sore', 'siang', 'shift 2', 'shift sore', 'shift siang'], true) => ['label' => 'Shift Sore', 'class' => 'sore', 'icon' => 'fi fi-rr-sun'],
                in_array($normalized, ['3', 'malam', 'shift 3', 'shift malam'], true) => ['label' => 'Shift Malam', 'class' => 'malam', 'icon' => 'fi fi-rr-moon-stars'],
                default => ['label' => $shift ? 'Shift '.$shift : 'Shift -', 'class' => 'pagi', 'icon' => 'fi fi-rr-clock'],
            };
        };
        $signatureUrl = function ($path): ?string {
            $normalized = ltrim(trim((string) $path), '/');

            if ($normalized === '') {
                return null;
            }

            if (preg_match('/^https?:\/\//i', $normalized)) {
                return $normalized;
            }

            if (file_exists(public_path($normalized))) {
                return asset($normalized);
            }

            if (file_exists(public_path('storage/'.$normalized))) {
                return asset('storage/'.$normalized);
            }

            if (file_exists(storage_path('app/public/'.$normalized))) {
                return asset('storage/'.$normalized);
            }

            return null;
        };
        $managerSignatureUrl = $signatureUrl($manager?->signature_path);
        $allIncomingCount = (int) ($divisionCounts['all'] ?? $incomingReports->count());
        $operationalIncomingCount = (int) ($divisionCounts['operasional'] ?? 0);
        $maintenanceIncomingCount = (int) ($divisionCounts['pemeliharaan'] ?? 0);
        $safetyIncomingCount = (int) ($divisionCounts['safety'] ?? 0);
    @endphp

    <main class="page-content">
        <div class="page-header">
            <span class="page-title">Dashboard</span>
            <span class="page-subtitle">Ringkasan performa dan aktivitas pelaporan dari ketiga divisi.</span>
        </div>

        @include('manajer.layouts.card')

        <div class="section-card">
            <div class="section-card__header d-flex flex-column align-items-start">
                <div class="div title-section-box d-flex align-items-center" style="gap: 10px;">
                    <span class="section-card__title">Laporan Masuk</span>
                    @if ($allIncomingCount > 0)
                        <div class="section-card__badge">{{ $allIncomingCount }}</div>
                    @endif
                </div>
                <span class="section-card__subtitle">
                    Laporan berikut telah diterima oleh regu tujuan dan menunggu tanda tangan digital manajer untuk masuk ke arsip.
                </span>
            </div>

            <div class="report-tabs">
                <div class="report-tab active" data-filter="all">
                    <span class="text">Semua</span>
                    @if ($allIncomingCount > 0)
                        <span class="report-tab__count">{{ $allIncomingCount }}</span>
                    @endif
                </div>
                <div class="report-tab" data-filter="operasional">
                    <span class="text">Operasional</span>
                    @if ($operationalIncomingCount > 0)
                        <span class="report-tab__count">{{ $operationalIncomingCount }}</span>
                    @endif
                </div>
                <div class="report-tab" data-filter="pemeliharaan">
                    <span class="text">Pemeliharaan</span>
                    @if ($maintenanceIncomingCount > 0)
                        <span class="report-tab__count">{{ $maintenanceIncomingCount }}</span>
                    @endif
                </div>
                <div class="report-tab" data-filter="safety">
                    <span class="text">Safety / K3</span>
                    @if ($safetyIncomingCount > 0)
                        <span class="report-tab__count">{{ $safetyIncomingCount }}</span>
                    @endif
                </div>
            </div>

            <div class="section-card__body">
                @php($maintenanceDocId = fn ($report) => '#MNT-'.(($report->report_date ?: $report->created_at)?->format('Y') ?? now()->format('Y')).'-'.str_pad((string) $report->id, 3, '0', STR_PAD_LEFT))
                @php($safetyDocId = fn ($report) => '#K3-'.(($report->report_date ?: $report->created_at)?->format('Y') ?? now()->format('Y')).'-'.str_pad((string) $report->id, 3, '0', STR_PAD_LEFT))
                @php($incomingSafetyReports = $incomingSafetyReports ?? collect())
                @if ($incomingReports->isEmpty() && $incomingMaintenanceReports->isEmpty() && $incomingSafetyReports->isEmpty())
                    <div class="empty-state-manager d-flex flex-column align-items-center gap-10">
                        <span class="empty-icon"><i class="fi fi-rr-check-circle"></i></span>
                        <div class="d-flex flex-column align-items-center gap-2">
                            <span class="fw-600 text-center" style="color: var(--black);">Tidak ada laporan yang menunggu tanda tangan.</span>
                            <span class="fsize-12 text-center">Laporan yang siap disetujui akan muncul di sini.</span>
                        </div>
                    </div>
                @endif

                @foreach ($incomingReports as $report)
                    @php($shift = $shiftMeta($report->shift))
                    @php($groupName = strtoupper((string) $report->group_name) ?: '-')
                    @php($receiverGroup = strtoupper((string) $report->received_by_group) ?: '-')
                    <div class="report-item" data-category="operasional">
                        <div class="report-detail d-flex flex-column align-items-start gap-8 flexible">
                            <div class="report-time d-flex align-items-center align-self-stretch gap-10 flex-wrap">
                                <div class="category operasional">
                                    <span class="icon-cat"><i class="fi fi-sr-briefcase"></i></span>
                                    <span class="text">Operasional</span>
                                </div>
                                <div class="shift {{ $shift['class'] }}">
                                    <span class="icon-shift"><i class="{{ $shift['icon'] }}"></i></span>
                                    <span class="text">{{ $shift['label'] }}</span>
                                </div>
                                <div class="upload-time d-flex align-items-center gap-6">
                                    <span class="icon-clock"><i class="fi fi-rr-clock"></i></span>
                                    <span class="text">Diterima: {{ $formatDiff($report->received_at ?? $report->updated_at) }}</span>
                                </div>
                            </div>

                            <div class="report-title d-flex flex-column align-items-start align-self-stretch">
                                <span class="title">Laporan Operasi Harian</span>
                                <span class="id">ID Dokumen: {{ $documentId($report) }} &bull; {{ $formatDate($report->report_date) }}</span>
                            </div>

                            <div class="report-group d-flex align-items-center gap-10">
                                <div class="group d-flex align-items-center gap-6">
                                    <div class="letter-group out">{{ $groupName }}</div>
                                    <span class="text fsize-10 fw-600">Regu {{ $groupName }}</span>
                                </div>
                                <span class="icon-arrow fsize-12"><i class="fi fi-rr-arrow-small-right"></i></span>
                                <div class="group d-flex align-items-center gap-6">
                                    <div class="letter-group in">{{ $receiverGroup }}</div>
                                    <span class="text fsize-10 fw-600">Regu {{ $receiverGroup }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="report-button d-flex justify-content-end align-items-start gap-8 flexible" style="min-width: 220px;">
                            <a href="{{ route('manajer.reports.show', $report) }}" class="btn see" target="_blank" rel="noopener">
                                <span class="icon-eye"><i class="fi fi-rr-eye"></i></span>
                                <span class="text">Baca Laporan</span>
                            </a>
                            <button type="button" class="btn signed js-open-modal" data-modal="approve-report-modal-{{ $report->id }}">
                                <span class="icon-sign"><i class="fi fi-rr-file-signature"></i></span>
                                <span class="text">Tanda Tangani</span>
                            </button>
                        </div>
                    </div>
                @endforeach

                @foreach ($incomingMaintenanceReports as $report)
                    <div class="report-item" data-category="pemeliharaan">
                        <div class="report-detail d-flex flex-column align-items-start gap-8 flexible">
                            <div class="report-time d-flex align-items-center align-self-stretch gap-10 flex-wrap">
                                <div class="category pemeliharaan">
                                    <span class="icon-cat"><i class="fi fi-sr-wrench-simple"></i></span>
                                    <span class="text">Pemeliharaan</span>
                                </div>
                                @if ($report->day_name)
                                    <div class="shift pagi">
                                        <span class="icon-shift"><i class="fi fi-rr-calendar"></i></span>
                                        <span class="text">{{ $report->day_name }}</span>
                                    </div>
                                @endif
                                <div class="upload-time d-flex align-items-center gap-6">
                                    <span class="icon-clock"><i class="fi fi-rr-clock"></i></span>
                                    <span class="text">Dikirim: {{ $formatDiff($report->submitted_at ?? $report->updated_at) }}</span>
                                </div>
                            </div>

                            <div class="report-title d-flex flex-column align-items-start align-self-stretch">
                                <span class="title">Laporan Harian Pemeliharaan</span>
                                <span class="id">ID Dokumen: {{ $maintenanceDocId($report) }} &bull; {{ $formatDate($report->report_date) }}</span>
                            </div>

                            <div class="report-group d-flex align-items-center gap-10">
                                <div class="group d-flex align-items-center gap-6">
                                    <span class="text fsize-10 fw-600">Dibuat oleh: {{ $report->creator->name ?? 'Kasi Pemeliharaan' }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="report-button d-flex justify-content-end align-items-start gap-8 flexible" style="min-width: 220px;">
                            <a href="{{ route('manajer.pemeliharaan.show', $report) }}" class="btn see" target="_blank" rel="noopener">
                                <span class="icon-eye"><i class="fi fi-rr-eye"></i></span>
                                <span class="text">Baca Laporan</span>
                            </a>
                            <button type="button" class="btn signed js-open-modal" data-modal="approve-maintenance-modal-{{ $report->id }}">
                                <span class="icon-sign"><i class="fi fi-rr-file-signature"></i></span>
                                <span class="text">Tanda Tangani</span>
                            </button>
                        </div>
                    </div>
                @endforeach

                @foreach ($incomingSafetyReports as $report)
                    <div class="report-item" data-category="safety">
                        <div class="report-detail d-flex flex-column align-items-start gap-8 flexible">
                            <div class="report-time d-flex align-items-center align-self-stretch gap-10 flex-wrap">
                                <div class="category safety">
                                    <span class="icon-cat"><i class="fi fi-sr-helmet-safety"></i></span>
                                    <span class="text">Safety / K3</span>
                                </div>
                                @if ($report->time_range)
                                    <div class="shift malam">
                                        <span class="icon-shift"><i class="fi fi-rr-clock"></i></span>
                                        <span class="text">{{ $report->time_range }}</span>
                                    </div>
                                @endif
                                <div class="upload-time d-flex align-items-center gap-6">
                                    <span class="icon-clock"><i class="fi fi-rr-clock"></i></span>
                                    <span class="text">Dikirim: {{ $formatDiff($report->submitted_at ?? $report->updated_at) }}</span>
                                </div>
                            </div>

                            <div class="report-title d-flex flex-column align-items-start align-self-stretch">
                                <span class="title">Laporan Harian K3</span>
                                <span class="id">ID Dokumen: {{ $safetyDocId($report) }} &bull; {{ $formatDate($report->report_date) }}</span>
                            </div>

                            <div class="report-group d-flex align-items-center gap-10">
                                <div class="group d-flex align-items-center gap-6">
                                    <span class="text fsize-10 fw-600">Dibuat oleh: {{ $report->creator->name ?? 'Karu Safety' }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="report-button d-flex justify-content-end align-items-start gap-8 flexible" style="min-width: 220px;">
                            <a href="{{ route('manajer.safety.show', $report) }}" class="btn see" target="_blank" rel="noopener">
                                <span class="icon-eye"><i class="fi fi-rr-eye"></i></span>
                                <span class="text">Baca Laporan</span>
                            </a>
                            <button type="button" class="btn signed js-open-modal" data-modal="approve-safety-modal-{{ $report->id }}">
                                <span class="icon-sign"><i class="fi fi-rr-file-signature"></i></span>
                                <span class="text">Tanda Tangani</span>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </main>
@endsection

@push('modals')
    @foreach ($incomingReports as $report)
        @php($shift = $shiftMeta($report->shift))
        @php($groupName = strtoupper((string) $report->group_name) ?: '-')
        @php($receiverGroup = strtoupper((string) $report->received_by_group) ?: '-')
        <div class="modal-overlay" id="approve-report-modal-{{ $report->id }}">
            <div class="modal-box">
                <div class="modal-box__header">
                    <span class="modal-box__title">Konfirmasi Tanda Tangan</span>
                    <button type="button" class="btn-modal-close js-close-modal" data-modal="approve-report-modal-{{ $report->id }}">
                        <i class="fi fi-br-cross"></i>
                    </button>
                </div>

                <div class="modal-box__doc-detail">
                    <span class="modal-box__doc-icon"><i class="fi fi-rr-file-signature"></i></span>
                    <div>
                        <div class="modal-box__doc-title">Laporan Operasi Harian</div>
                        <div class="modal-box__doc-sub">{{ $documentId($report) }} &bull; Regu {{ $groupName }} ke Regu {{ $receiverGroup }}</div>
                    </div>
                </div>

                <div style="display:flex;flex-direction:column;gap:8px;">
                    <span class="modal-box__section-label">Tanda Tangan Digital</span>
                    <div class="modal-box__signature">
                        @if ($managerSignatureUrl)
                            <img src="{{ $managerSignatureUrl }}" alt="Tanda tangan {{ $manager->name }}">
                        @else
                            <div class="sign-img-placeholder">[TTD]</div>
                        @endif
                        <div style="display:flex;flex-direction:column;gap:4px;flex:1;">
                            <div class="sign-officer__name">{{ $manager->name ?? 'Manajer' }}</div>
                            <div class="sign-officer__id">{{ $shift['label'] }} &bull; {{ $formatDate($report->report_date) }}</div>
                            <div class="verified-badge">
                                <i class="fi fi-rr-shield-check"></i> Terverifikasi Sistem
                            </div>
                        </div>
                    </div>
                    <div class="modal-box__note">
                        <i class="fi fi-rr-info"></i>
                        <span>Setelah dikonfirmasi, laporan ini akan berstatus diarsipkan dan bisa dicari pada menu Arsip Laporan.</span>
                    </div>
                </div>

                <div class="modal-box__footer">
                    <button type="button" class="btn-modal btn-modal--cancel js-close-modal" data-modal="approve-report-modal-{{ $report->id }}">Batal</button>
                    <a href="{{ route('manajer.reports.show', $report) }}" class="btn-modal btn-modal--cancel" target="_blank" rel="noopener">Tinjau</a>
                    <form action="{{ route('manajer.reports.approve', $report) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn-modal btn-modal--confirm">
                            <i class="fi fi-br-check-circle"></i> Konfirmasi TTD
                        </button>
                    </form>
                </div>
            </div>
        </div>
    @endforeach

    @foreach ($incomingMaintenanceReports as $report)
        @php($mntDocId = '#MNT-'.(($report->report_date ?: $report->created_at)?->format('Y') ?? now()->format('Y')).'-'.str_pad((string) $report->id, 3, '0', STR_PAD_LEFT))
        <div class="modal-overlay" id="approve-maintenance-modal-{{ $report->id }}">
            <div class="modal-box">
                <div class="modal-box__header">
                    <span class="modal-box__title">Konfirmasi Persetujuan</span>
                    <button type="button" class="btn-modal-close js-close-modal" data-modal="approve-maintenance-modal-{{ $report->id }}">
                        <i class="fi fi-br-cross"></i>
                    </button>
                </div>

                <div class="modal-box__doc-detail">
                    <span class="modal-box__doc-icon"><i class="fi fi-rr-file-signature"></i></span>
                    <div>
                        <div class="modal-box__doc-title">Laporan Harian Pemeliharaan</div>
                        <div class="modal-box__doc-sub">{{ $mntDocId }} &bull; {{ $report->day_name ? $report->day_name.', ' : '' }}{{ $formatDate($report->report_date) }}</div>
                    </div>
                </div>

                <div style="display:flex;flex-direction:column;gap:8px;">
                    <span class="modal-box__section-label">Tanda Tangan Digital</span>
                    <div class="modal-box__signature">
                        @if ($managerSignatureUrl)
                            <img src="{{ $managerSignatureUrl }}" alt="Tanda tangan {{ $manager->name }}">
                        @else
                            <div class="sign-img-placeholder">[TTD]</div>
                        @endif
                        <div style="display:flex;flex-direction:column;gap:4px;flex:1;">
                            <div class="sign-officer__name">{{ $manager->name ?? 'Manajer' }}</div>
                            <div class="sign-officer__id">Pembuat: {{ $report->creator->name ?? 'Kasi Pemeliharaan' }}</div>
                            <div class="verified-badge"><i class="fi fi-rr-shield-check"></i> Terverifikasi Sistem</div>
                        </div>
                    </div>
                    <div class="modal-box__note">
                        <i class="fi fi-rr-info"></i>
                        <span>Setelah ditandatangani, laporan pemeliharaan ini berstatus Diarsipkan dan tidak dapat diedit lagi oleh petugas.</span>
                    </div>
                </div>

                <div class="modal-box__footer">
                    <button type="button" class="btn-modal btn-modal--cancel js-close-modal" data-modal="approve-maintenance-modal-{{ $report->id }}">Batal</button>
                    <a href="{{ route('manajer.pemeliharaan.show', $report) }}" class="btn-modal btn-modal--cancel" target="_blank" rel="noopener">Tinjau</a>
                    <form action="{{ route('manajer.pemeliharaan.approve', $report) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn-modal btn-modal--confirm">
                            <i class="fi fi-br-check-circle"></i> Konfirmasi TTD
                        </button>
                    </form>
                </div>
            </div>
        </div>
    @endforeach

    @foreach (($incomingSafetyReports ?? collect()) as $report)
        @php($k3DocId = '#K3-'.(($report->report_date ?: $report->created_at)?->format('Y') ?? now()->format('Y')).'-'.str_pad((string) $report->id, 3, '0', STR_PAD_LEFT))
        <div class="modal-overlay" id="approve-safety-modal-{{ $report->id }}">
            <div class="modal-box">
                <div class="modal-box__header">
                    <span class="modal-box__title">Konfirmasi Persetujuan</span>
                    <button type="button" class="btn-modal-close js-close-modal" data-modal="approve-safety-modal-{{ $report->id }}">
                        <i class="fi fi-br-cross"></i>
                    </button>
                </div>

                <div class="modal-box__doc-detail">
                    <span class="modal-box__doc-icon"><i class="fi fi-rr-file-signature"></i></span>
                    <div>
                        <div class="modal-box__doc-title">Laporan Harian K3</div>
                        <div class="modal-box__doc-sub">{{ $k3DocId }} &bull; {{ $formatDate($report->report_date) }}</div>
                    </div>
                </div>

                <div style="display:flex;flex-direction:column;gap:8px;">
                    <span class="modal-box__section-label">Tanda Tangan Digital</span>
                    <div class="modal-box__signature">
                        @if ($managerSignatureUrl)
                            <img src="{{ $managerSignatureUrl }}" alt="Tanda tangan {{ $manager->name }}">
                        @else
                            <div class="sign-img-placeholder">[TTD]</div>
                        @endif
                        <div style="display:flex;flex-direction:column;gap:4px;flex:1;">
                            <div class="sign-officer__name">{{ $manager->name ?? 'Manajer' }}</div>
                            <div class="sign-officer__id">Pembuat: {{ $report->creator->name ?? 'Karu Safety' }}</div>
                            <div class="verified-badge"><i class="fi fi-rr-shield-check"></i> Terverifikasi Sistem</div>
                        </div>
                    </div>
                    <div class="modal-box__note">
                        <i class="fi fi-rr-info"></i>
                        <span>Setelah ditandatangani, laporan K3 ini berstatus Diarsipkan dan tidak dapat diedit lagi oleh petugas.</span>
                    </div>
                </div>

                <div class="modal-box__footer">
                    <button type="button" class="btn-modal btn-modal--cancel js-close-modal" data-modal="approve-safety-modal-{{ $report->id }}">Batal</button>
                    <a href="{{ route('manajer.safety.show', $report) }}" class="btn-modal btn-modal--cancel" target="_blank" rel="noopener">Tinjau</a>
                    <form action="{{ route('manajer.safety.approve', $report) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn-modal btn-modal--confirm">
                            <i class="fi fi-br-check-circle"></i> Konfirmasi TTD
                        </button>
                    </form>
                </div>
            </div>
        </div>
    @endforeach
@endpush
