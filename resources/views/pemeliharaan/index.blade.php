@extends('pemeliharaan.layouts.app')

@push('styles')
<style>
    /* Lebar kolom tabel riwayat diatur oleh kontrak bersama
       resources/css/components/report-table.css (varian .report-table--daily-history).
       Di sini hanya badge jam kerja dan warna tombol cetak. */
    .time-badge { display:inline-flex; min-height:20px; padding:2px 8px; align-items:center; gap:4px; border-radius:10px; font-size:10px; font-weight:500; line-height:1; background-color:var(--success-10); color:var(--success); white-space:nowrap; }
    .time-badge i { display:block; font-size:10px; line-height:1; }
    .report-table .tbody td.col-action .btn.print-report { background-color: var(--cyan-main); color: #fff; }
    .report-table .tbody td.col-action .btn.print-report:hover { filter: brightness(.93); transform: translateY(-1px); }
</style>
@endpush

@section('content')
    @php
        use App\Enums\MaintenanceStatus;

        $documentId = function ($report): string {
            $date = $report->report_date ?: $report->created_at;
            try { $year = $date ? \Carbon\Carbon::parse($date)->format('Y') : now()->format('Y'); }
            catch (\Throwable) { $year = now()->format('Y'); }
            return '#MNT-'.$year.'-'.str_pad((string) $report->id, 3, '0', STR_PAD_LEFT);
        };
        $formatDate = fn ($date) => $date ? $date->locale('id')->translatedFormat('d F Y') : '-';
        $formatDiff = fn ($date) => $date ? $date->locale('id')->diffForHumans() : '-';
        $formatTime = function ($value): string {
            $value = trim((string) $value);
            return $value === '' ? '' : substr($value, 0, 5);
        };
        $workTimeRange = function ($report) use ($formatTime): string {
            $attendance = $report->attendances
                ->first(fn ($row) => filled($row->time_in) || filled($row->time_out));

            if (! $attendance) {
                return '-';
            }

            $start = $formatTime($attendance->time_in);
            $end = $formatTime($attendance->time_out);

            return $start !== '' && $end !== ''
                ? $start.' - '.$end
                : ($start !== '' ? $start : ($end !== '' ? $end : '-'));
        };
        $statusMeta = function ($status): array {
            $case = $status instanceof MaintenanceStatus ? $status : (MaintenanceStatus::tryFrom((string) $status) ?? MaintenanceStatus::Draft);
            return ['label' => $case->label(), 'class' => $case->badgeClass(), 'icon' => $case->icon()];
        };

        $latestDraft = $draftReports->first();
        $historyTotal = method_exists($historyReports, 'total') ? $historyReports->total() : $historyReports->count();
        $historyFirstItem = method_exists($historyReports, 'firstItem') ? $historyReports->firstItem() : 1;
    @endphp

    <div class="content d-flex flex-column align-items-start align-self-stretch gap-30 p-content">
        <div class="content-header d-flex justify-content-between align-items-center align-content-center align-self-stretch flex-wrap p-20" style="row-gap:10px;border-radius:16px">
            <div class="title-header d-flex flex-column align-items-start gap-2 flexible">
                <span class="text-header fw-600 fsize-20 align-self-stretch">Laporan Pemeliharaan</span>
                <span class="note fw-300 fsize-12 text-secondary align-self-stretch">Kelola laporan harian unit pemeliharaan: draft, riwayat, dan buat laporan baru.</span>
            </div>
            <a href="{{ route('pemeliharaan.create') }}" class="btn-new d-flex justify-content-center align-items-center gap-10 br-12" style="cursor:pointer;text-decoration:none">
                <div class="icon-new"><i class="fi fi-rr-add"></i></div>
                <span class="white-pure fsize-14 fw-500">Buat Laporan Pemeliharaan</span>
            </a>
        </div>

        @if ($latestDraft)
            <div class="reminder-draft">
                <div class="reminder d-flex align-items-center gap-10 flexible" style="min-width:300px;">
                    <div class="icon-reminder"><i class="fi fi-rr-info"></i></div>
                    <div class="text-reminder d-flex flex-column align-items-start flexible" style="gap:2px;">
                        <span class="fsize-12 fw-600 align-self-stretch">Laporan Belum Diselesaikan</span>
                        <span class="fsize-9 fw-400 align-self-stretch">
                            Anda memiliki <span class="fw-600">{{ $draftReports->count() }} draft</span> laporan harian pemeliharaan yang belum diselesaikan.
                        </span>
                    </div>
                </div>
                <div class="reminder-button d-flex justify-content-end align-items-center gap-10">
                    <button type="button" class="btn draft-edit" data-open-modal="continue-draft-modal-{{ $latestDraft->id }}">
                        <span class="text">Lanjutkan Draft</span>
                        <div class="icon-edit"><i class="fi fi-br-arrow-small-right"></i></div>
                    </button>
                    <button type="button" class="btn close" onclick="this.closest('.reminder-draft').remove()"><i class="fi fi-br-cross"></i></button>
                </div>
            </div>
        @endif

        <div class="main-content d-flex flex-column align-items-start align-self-stretch p-main gap-20" style="border-radius:16px">
            <div class="tab-content d-flex align-items-center align-self-stretch gap-15">
                <a class="list-tab {{ $activeTab === 'draft' ? 'active' : '' }}" id="tab-draft">
                    <div class="list-item">
                        <div class="icon-tab"><i class="fi fi-rr-edit-alt"></i></div>
                        <span class="text-tab">Draft</span>
                        @if ($draftReports->count() > 0)
                            <div class="tab-amount fsize-10 fw-500">{{ $draftReports->count() }}</div>
                        @endif
                    </div>
                </a>
                <a class="list-tab {{ $activeTab === 'riwayat' ? 'active' : '' }}" id="tab-riwayat">
                    <div class="list-item">
                        <div class="icon-tab"><i class="fi fi-rr-folder"></i></div>
                        <span class="text-tab">Riwayat Laporan</span>
                        @if ($historyTotal > 0)
                            <div class="tab-amount fsize-10 fw-500">{{ $historyTotal }}</div>
                        @endif
                    </div>
                </a>
            </div>

            {{-- ===== TAB DRAFT ===== --}}
            <div id="content-draft" class="flex-column align-items-start align-self-stretch gap-20 w-100 {{ $activeTab === 'draft' ? 'd-flex' : 'd-none' }}">
                @forelse ($draftReports as $report)
                    <div class="draft-item d-flex flex-column align-items-start align-self-stretch gap-8 br-10">
                        <div class="info-time d-flex justify-content-between align-items-start align-self-stretch flex-wrap" style="row-gap:8px;">
                            <div class="status d-flex align-items-start gap-10">
                                <div class="badge-draft d-flex align-items-center">
                                    <span class="icon-draft"><i class="fi fi-rr-edit"></i></span>
                                    <span class="text">Draft</span>
                                </div>
                                @if ($report->day_name)
                                    <div class="day-badge"><i class="fi fi-rr-calendar"></i><span>{{ $report->day_name }}</span></div>
                                @endif
                            </div>
                            <span class="date text-right fsize-10 text-muted align-self-stretch">{{ $formatDate($report->report_date) }}</span>
                        </div>
                        <div class="draft-report">
                            <div class="draft-detail">
                                <div class="draft-title d-flex flex-column align-items-start align-self-stretch">
                                    <span class="title fsize-16 fw-600 text-main align-self-stretch">Draft Laporan Harian Pemeliharaan</span>
                                    <span class="id fsize-10 text-muted align-self-stretch">ID Dokumen: {{ $documentId($report) }}</span>
                                </div>
                                <div class="last-edit d-flex align-items-center align-self-stretch text-muted fsize-10" style="gap:5px">
                                    <span class="icon-edit"><i class="fi fi-rr-time-forward"></i></span>
                                    <span class="text fsize-10 text-muted">Terakhir diedit {{ $formatDiff($report->updated_at) }}</span>
                                </div>
                                @include('partials.draft-expiry', [
                                    'report' => $report,
                                    'ttlDays' => \App\Models\MaintenanceReport::DRAFT_TTL_DAYS,
                                    'extendUrl' => route('pemeliharaan.extend-draft', $report),
                                ])
                            </div>
                            <div class="draft-button d-flex justify-content-end align-items-center gap-10 flexible" style="min-width:220px;">
                                <button type="button" class="btn-draft-edit" data-open-modal="continue-draft-modal-{{ $report->id }}">
                                    <div class="icon-edit"><i class="fi fi-rr-pencil"></i></div>
                                    <span class="text">Lanjutkan Draft</span>
                                </button>
                                <button type="button" class="btn-delete" data-open-modal="delete-draft-modal-{{ $report->id }}">
                                    <div class="icon-delete"><i class="fi fi-rr-trash"></i></div>
                                </button>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="empty-state d-flex flex-column align-items-center align-self-stretch gap-10">
                        <span class="icon-empty"><i class="fi fi-rr-edit"></i></span>
                        <div class="empty-text d-flex flex-column align-items-center align-self-stretch" style="gap:5px;">
                            <span class="text-center fw-600 text-main">Tidak Ada Draft</span>
                            <span class="text-center fsize-12 text-secondary">Belum ada draft laporan pemeliharaan yang perlu diselesaikan.</span>
                        </div>
                    </div>
                @endforelse
            </div>

            {{-- ===== TAB RIWAYAT ===== --}}
            <div id="content-riwayat" class="w-100 {{ $activeTab === 'riwayat' ? '' : 'd-none' }}">
                <div class="table-responsive-wrapper">
                    <table class="w-100 report-table report-table--daily-history" role="table">
                        <tr class="thead d-flex align-items-center bg-blue br-4 align-self-stretch" role="row">
                            <th class="col-no" scope="col" role="columnheader">No</th>
                            <th class="col-doc" scope="col" role="columnheader">Info Dokumen</th>
                            <th class="col-date" scope="col" role="columnheader">Tanggal Laporan</th>
                            <th class="col-day" scope="col" role="columnheader">Hari</th>
                            <th class="col-time" scope="col" role="columnheader">Jam Kerja</th>
                            <th class="col-status" scope="col" role="columnheader">Status</th>
                            <th class="col-action" scope="col" role="columnheader">Aksi</th>
                        </tr>
                        @forelse ($historyReports as $report)
                            @php($status = $statusMeta($report->status))
                            <tr class="tbody d-flex align-items-center align-self-stretch" role="row">
                                <td class="col-no" role="cell">{{ $historyFirstItem + $loop->index }}</td>
                                <td class="col-doc cell-stack" role="cell">
                                    <span class="cell-title">Laporan Harian Pemeliharaan</span>
                                    <span class="cell-meta fsize-10 fw-400 text-muted">ID: {{ $documentId($report) }}</span>
                                </td>
                                <td class="col-date cell-stack" role="cell">
                                    <span class="cell-title">{{ $formatDate($report->report_date) }}</span>
                                    <span class="cell-meta fsize-10 fw-400 text-muted">Diperbarui: {{ $formatDiff($report->updated_at) }}</span>
                                </td>
                                <td class="col-day" role="cell">
                                    <div class="day-badge"><i class="fi fi-rr-calendar"></i><span>{{ $report->day_name ?: '-' }}</span></div>
                                </td>
                                <td class="col-time" role="cell">
                                    <div class="time-badge"><i class="fi fi-rr-clock"></i><span>{{ $workTimeRange($report) }}</span></div>
                                </td>
                                <td class="col-status" role="cell">
                                    <div class="status {{ $status['class'] }}">
                                        <span class="icon-status"><i class="{{ $status['icon'] }}"></i></span>
                                        <span class="text">{{ $status['label'] }}</span>
                                    </div>
                                </td>
                                <td class="aksi col-action" role="cell">
                                    <a href="{{ route('pemeliharaan.show', $report) }}" class="btn see" target="_blank" rel="noopener">
                                        <span><i class="fi fi-rr-eye"></i></span><span>Lihat</span>
                                    </a>
                                    <a href="{{ route('pemeliharaan.pdf', $report) }}" class="btn export-pdf" target="_blank" rel="noopener">
                                        <span><i class="fi fi-rr-file-pdf"></i></span><span>PDF</span>
                                    </a>
                                    @if ($report->status === MaintenanceStatus::Submitted)
                                        <a href="{{ route('pemeliharaan.edit', $report) }}" class="btn edit">
                                            <span><i class="fi fi-rr-pencil"></i></span><span>Edit</span>
                                        </a>
                                    @endif
                                    <a href="{{ route('pemeliharaan.show', $report) }}?print=1" class="btn print-report" target="_blank" rel="noopener" title="Cetak laporan" aria-label="Cetak laporan">
                                        <span><i class="fi fi-rr-print"></i></span>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr class="row-full">
                                <td colspan="7" class="border-0 p-0">
                                    <div class="empty-state d-flex flex-column align-items-center justify-content-center align-self-stretch gap-10 w-100">
                                        <span class="icon-empty"><i class="fi fi-rr-folder-open"></i></span>
                                        <div class="empty-text d-flex flex-column align-items-center align-self-stretch" style="gap:5px;">
                                            <span class="text-center fw-600 text-main">Riwayat Masih Kosong</span>
                                            <span class="text-center fsize-12 text-secondary">Laporan yang sudah dikirim akan tampil di sini.</span>
                                        </div>
                                        <a href="{{ route('pemeliharaan.create') }}" class="btn new-report">
                                            <span><i class="fi fi-rr-add-document"></i></span><span>Buat Laporan Baru</span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </table>
                </div>

                @include('partials.pagination', ['paginator' => $historyReports, 'label' => 'laporan'])
            </div>
        </div>
    </div>
@endsection

@push('modals')
    @foreach ($draftReports as $report)
        <div class="modal-overlay" id="continue-draft-modal-{{ $report->id }}">
            <div class="pop-up signed d-flex flex-column gap-20">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="fw-600 fsize-16 text-main">Lanjutkan Draft</span>
                    <button type="button" class="button-close" data-close-modal><i class="fi fi-br-cross"></i></button>
                </div>
                <div class="pop-up-content d-flex flex-column gap-15">
                    <div class="pop-up detail d-flex align-items-center">
                        <span class="icon-document"><i class="fi fi-rr-edit"></i></span>
                        <div class="d-flex flex-column">
                            <span class="fw-600 fsize-14 text-main">Buka draft ini?</span>
                            <span class="fsize-10 text-secondary">ID: {{ $documentId($report) }} &bull; {{ $formatDate($report->report_date) }}</span>
                        </div>
                    </div>
                    <p class="fsize-12 text-muted m-0">Draft laporan pemeliharaan akan dibuka kembali dengan data terakhir yang tersimpan agar bisa dilanjutkan.</p>
                </div>
                <div class="pop-up footer d-flex justify-content-end gap-10 flex-wrap">
                    <button type="button" class="btn cancel" data-close-modal>Batal</button>
                    <a href="{{ route('pemeliharaan.edit', $report) }}" class="btn edit-confirm">
                        <i class="fi fi-rr-pencil"></i> Ya, Lanjutkan
                    </a>
                </div>
            </div>
        </div>

        <div class="modal-overlay" id="delete-draft-modal-{{ $report->id }}">
            <div class="pop-up signed d-flex flex-column gap-20">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="fw-600 fsize-16 text-main">Hapus Draft?</span>
                    <button type="button" class="button-close" data-close-modal><i class="fi fi-br-cross"></i></button>
                </div>
                <div class="pop-up detail danger d-flex align-items-center">
                    <span class="icon-document danger"><i class="fi fi-rr-trash"></i></span>
                    <div>
                        <div class="fw-600 fsize-13 text-main">Draft Laporan Harian Pemeliharaan</div>
                        <div class="fsize-11 text-muted">{{ $documentId($report) }} &bull; {{ $formatDate($report->report_date) }}</div>
                    </div>
                </div>
                <p class="fsize-12 text-secondary m-0">Draft yang dihapus tidak dapat dikembalikan. Lanjutkan?</p>
                <div class="pop-up footer d-flex justify-content-end gap-10">
                    <button type="button" class="btn cancel" data-close-modal>Batal</button>
                    <form action="{{ route('pemeliharaan.destroy', $report) }}" method="POST" class="m-0">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn delete-confirm"><i class="fi fi-br-trash"></i> Hapus Draft</button>
                    </form>
                </div>
            </div>
        </div>
    @endforeach
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const tabDraft = document.getElementById('tab-draft');
        const tabRiwayat = document.getElementById('tab-riwayat');
        const contentDraft = document.getElementById('content-draft');
        const contentRiwayat = document.getElementById('content-riwayat');

        function activate(tab, content, isFlex) {
            [tabDraft, tabRiwayat].forEach(t => t?.classList.remove('active'));
            [contentDraft, contentRiwayat].forEach(c => { c?.classList.add('d-none'); c?.classList.remove('d-flex', 'animate-slide-right'); });
            tab.classList.add('active');
            content.classList.remove('d-none');
            if (isFlex) content.classList.add('d-flex');
            content.classList.add('animate-slide-right');
        }
        tabDraft?.addEventListener('click', e => { e.preventDefault(); activate(tabDraft, contentDraft, true); });
        tabRiwayat?.addEventListener('click', e => { e.preventDefault(); activate(tabRiwayat, contentRiwayat, false); });
    });
</script>
@endpush
