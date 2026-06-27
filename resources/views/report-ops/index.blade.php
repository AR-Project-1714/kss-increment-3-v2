@extends('report-ops.layouts.app')

@push('styles')
    <style>
        .inline-action-form {
            display: inline-flex;
            margin: 0;
        }

        .action-link {
            text-decoration: none;
            white-space: nowrap;
        }

        .empty-state {
            width: 100%;
            padding: 36px 20px;
            border: 1px dashed var(--divider);
            border-radius: 10px;
            color: var(--muted);
            background: var(--blue-main-2);
        }

        .empty-laporan {
            width: 100%;
        }

        .icon-empty {
            font-size: 48px;
            color: var(--cyan-main);
            line-height: 1;
        }

        .icon-empty i {
            position: relative;
            top: 3px;
        }

        .btn.new-report {
            display: flex;
            align-items: center;
            background: none;
            border: none;
            font-size: 12px;
            color: var(--blue-main);
            gap: 10px;
            transition: .2s ease-out;
        }

        .btn.new-report:hover {
            color: var(--blue-hover);
            transform: translateY(-2px);
        }

        .history-actions {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: nowrap;
            justify-content: flex-end;
            width: 100%;
        }

        .history-actions .btn,
        .report-button .btn,
        .draft-button .btn-draft-edit,
        .draft-button .btn-delete {
            text-decoration: none;
            white-space: nowrap;
        }

        .table-responsive-wrapper table.history-table,
        .history-table {
            min-width: 960px;
            max-width: 100%;
            margin-left: 0;
            margin-right: auto;
        }

        .history-table .thead,
        .history-table .tbody {
            justify-content: flex-start !important;
            column-gap: 4px;
        }

        .history-table th.action-column,
        .history-table td.aksi {
            margin-left: auto;
        }

        .history-table th.column-1,
        .history-table .tbody td.column-2:not(.date-column):not(.shift-column):not(.status-column):not(.receiver-column) {
            flex: 0 0 165px !important;
            max-width: 165px;
        }

        .history-table .thead th,
        .history-table .tbody td {
            padding-left: 6px;
            padding-right: 6px;
        }

        .history-table th.nomor,
        .history-table td.nomor {
            width: 38px;
        }

        .history-table th.column-1,
        .history-table .tbody td.column-2:not(.date-column):not(.shift-column):not(.status-column):not(.receiver-column) {
            min-width: 150px;
        }

        .history-table th.receiver-column,
        .history-table td.receiver-column {
            flex: 0 0 112px;
            max-width: 112px;
            min-width: 112px;
        }

        .history-table .thead th.date-column,
        .history-table .tbody td.date-column {
            flex: 0 0 180px;
            max-width: 185px;
            min-width: 180px;
        }

        .history-table .thead th.status-column,
        .history-table .tbody td.status-column {
            flex: 0 0 128px;
            max-width: 132px;
            min-width: 128px;
        }

        .history-table .tbody td.status-column .status {
            white-space: nowrap;
        }

        .history-table .thead th.shift-column,
        .history-table .tbody td.shift-column {
            flex: 0 0 108px;
            max-width: 112px;
            min-width: 108px;
        }

        .history-table .tbody td.shift-column .shift {
            white-space: nowrap;
        }

        .history-table th.action-column,
        .history-table td.aksi {
            flex: 0 0 245px;
            max-width: 245px;
            min-width: 245px;
        }

        .history-table th.action-column {
            justify-content: flex-end;
            padding-left: 0;
        }

        .history-table td.aksi {
            justify-content: flex-end;
        }

        .history-table .history-actions {
            gap: 3px;
        }

        .history-table td.aksi .btn {
            padding: 5px 7px;
            gap: 4px;
            font-size: 10px;
        }

        .history-table td.aksi .btn.print-report {
            width: 28px;
            min-width: 28px;
            padding-left: 0;
            padding-right: 0;
        }

        .history-table td.aksi .btn .button-icon i,
        .history-table td.aksi .btn i {
            font-size: 10px;
        }

        .history-searchbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 16px;
            flex-wrap: wrap;
        }

        .history-search-input {
            position: relative;
            flex: 1 1 520px;
            max-width: 880px;
        }

        .history-search-input .custom-input {
            border-radius: 16px;
        }

        .history-search-input .custom-input {
            cursor: text;
            padding-left: 42px;
            padding-right: 42px;
        }

        .history-search-input input[type="search"]::-webkit-search-cancel-button,
        .history-search-input input[type="search"]::-webkit-search-decoration {
            display: none;
            -webkit-appearance: none;
        }

        .history-search-icon {
            position: absolute;
            left: 15px;
            top: calc(50% + 1px);
            transform: translateY(-50%);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: var(--blue-main);
            font-size: 14px;
            z-index: 1;
        }

        .history-search-clear {
            position: absolute;
            right: 9px;
            top: calc(50% + 1px);
            transform: translateY(-50%);
            display: inline-flex;
            width: 28px;
            height: 28px;
            padding: 0;
            align-items: center;
            justify-content: center;
            border: none;
            border-radius: 7px;
            background-color: var(--blue-main-10);
            color: var(--blue-main);
            font-size: 12px;
            line-height: 1;
            transition: .2s ease-out;
        }

        .history-search-clear i {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 1em;
            height: 1em;
            line-height: 1;
        }

        .history-search-clear:hover {
            background-color: var(--blue-main-25);
            transform: translateY(-50%) scale(1.03);
        }

        .history-search-meta {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 12px;
            border-radius: 999px;
            color: var(--blue-main);
            background-color: var(--blue-main-10);
            white-space: nowrap;
        }

        .history-search-meta.is-searching {
            background-color: rgba(37, 99, 235, .14);
        }

        .history-search-empty.d-none,
        .history-search-clear.d-none {
            display: none !important;
        }

        .history-suggest-dropdown {
            position: absolute;
            top: calc(100% + 6px);
            left: 0;
            right: 0;
            z-index: 30;
            display: none;
            max-height: 420px;
            overflow-y: auto;
            padding: 8px;
            background-color: var(--white);
            border: 1px solid var(--blue-main-10);
            border-radius: 18px;
            box-shadow: 0 18px 38px rgba(15, 23, 42, .12);
        }

        .history-suggest-dropdown.show {
            display: block;
        }

        .history-suggest-header,
        .history-suggest-footer {
            padding: 8px 12px;
            font-size: 11px;
            font-weight: 600;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        .history-suggest-footer {
            text-transform: none;
            letter-spacing: 0;
            color: var(--blue-main);
            cursor: pointer;
            border-top: 1px dashed var(--blue-main-10);
            margin-top: 4px;
        }

        .history-suggest-footer:hover {
            background-color: var(--blue-main-10);
            border-radius: 8px;
        }

        .history-suggest-empty,
        .history-suggest-loading {
            padding: 18px 14px;
            text-align: center;
            font-size: 12px;
            color: var(--muted);
        }

        .history-suggest-item {
            display: flex;
            flex-direction: column;
            gap: 4px;
            width: 100%;
            padding: 10px 14px;
            border: none;
            border-radius: 12px;
            background: transparent;
            color: inherit;
            font-family: inherit;
            text-align: left;
            text-decoration: none;
            transition: background-color .15s ease-out;
            cursor: pointer;
        }

        .history-suggest-item + .history-suggest-item {
            margin-top: 2px;
        }

        .history-suggest-item:hover,
        .history-suggest-item.is-active {
            background-color: var(--blue-main-10);
        }

        .history-suggest-item-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            font-weight: 600;
            color: var(--dark-main, #0f172a);
        }

        .history-suggest-item-id {
            font-size: 10px;
            font-weight: 500;
            color: var(--muted);
        }

        .history-suggest-item-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            font-size: 10px;
            color: var(--muted);
        }

        .history-suggest-item-meta .chip {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 2px 8px;
            border-radius: 999px;
            background-color: var(--blue-main-10);
            color: var(--blue-main);
            font-weight: 600;
        }

        .history-suggest-item-meta .chip.status-draft {
            background-color: var(--dark-secondary-10);
            color: var(--dark-secondary);
            border: 1px solid var(--dark-secondary);
        }
        .history-suggest-item-meta .chip.status-submit {
            background-color: var(--orange-main-10);
            color: var(--orange-main);
            border: 1px solid var(--orange-main);
        }
        .history-suggest-item-meta .chip.status-approve {
            background-color: var(--success-10);
            color: var(--success);
            border: 1px solid var(--success);
        }
        .history-suggest-item-meta .chip.status-confirm {
            background-color: var(--cyan-main-10);
            color: var(--cyan-main);
            border: 1px solid var(--cyan-main);
        }

        .history-suggest-item-meta .chip.shift-pagi { background-color: var(--cyan-main-10); color: var(--cyan-main); }
        .history-suggest-item-meta .chip.shift-sore { background-color: var(--orange-main-10); color: var(--orange-main); }
        .history-suggest-item-meta .chip.shift-malam { background-color: var(--blue-main-10); color: var(--blue-main); }

        .history-suggest-actions {
            display: flex;
            gap: 6px;
            margin-top: 4px;
        }

        .history-suggest-actions .history-suggest-action {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 8px;
            border-radius: 6px;
            font-size: 10px;
            font-weight: 600;
            text-decoration: none;
        }

        .history-suggest-action.view { background-color: var(--blue-main-10); color: var(--blue-main); }
        .history-suggest-action.pdf { background-color: rgba(239, 68, 68, .12); color: #b91c1c; }
        .history-suggest-action.excel { background-color: rgba(22, 163, 74, .12); color: #15803d; }

        .history-suggest-action:hover {
            filter: brightness(.95);
        }

        .history-pagination {
            display: flex;
            width: 100%;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            margin-top: 16px;
            flex-wrap: wrap;
        }

        .history-pagination-info {
            color: var(--muted);
        }

        .history-page-list {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
        }

        .history-page-link,
        .history-page-disabled {
            display: inline-flex;
            min-width: 34px;
            height: 34px;
            padding: 0 10px;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--blue-main-10);
            border-radius: 8px;
            background-color: var(--white);
            color: var(--blue-main);
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
            transition: .2s ease-out;
        }

        .history-page-link:hover {
            background-color: var(--blue-main-10);
            border-color: var(--blue-main-25);
            transform: translateY(-1px);
        }

        .history-page-link.active {
            background-color: var(--blue-main);
            border-color: var(--blue-main);
            color: #ffffff;
            box-shadow: 0 8px 18px var(--blue-main-25);
        }

        .history-page-disabled {
            color: var(--muted);
            opacity: .55;
            cursor: not-allowed;
        }

        .action-modal-trigger {
            border: none;
            cursor: pointer;
        }

        .sign-modal-signature {
            align-items: center;
        }

        .sign-modal-signature .img-sign {
            flex-shrink: 0;
        }

        .sign-modal-signature-placeholder {
            color: var(--muted);
            font-size: 10px;
            font-weight: 600;
        }

        .sign-modal-note {
            margin: 0;
            display: flex;
            align-items: flex-start;
            gap: 8px;
            color: var(--muted);
            line-height: 1.6;
        }

        .sign-modal-note i {
            position: relative;
            top: 3px;
            flex-shrink: 0;
        }

        .pop-up.footer .btn.check-report {
            background-color: var(--orange-main);
            color: #ffffff;
            text-decoration: none;
        }

        .pop-up.footer .btn.check-report:hover {
            background-color: var(--orange-hover);
            transform: translateY(-2px);
        }

        .history-actions .btn.print-report {
            background-color: var(--cyan-main);
            color: #ffffff;
            justify-content: center;
        }

        .history-actions .btn.print-report:hover {
            background-color: #0284c7;
            transform: translateY(-2px);
        }

        .history-actions .btn.delete-icon {
            background-color: var(--red-main);
            color: #ffffff;
            justify-content: center;
        }

        .history-actions .btn.delete-icon:hover {
            background-color: var(--red-hover);
            transform: translateY(-2px);
        }

        .history-actions .btn.export-pdf {
            background-color: #ef4444;
            color: #ffffff;
        }

        .history-actions .btn.export-pdf:hover {
            background-color: #dc2626;
            transform: translateY(-2px);
        }

        .history-actions .btn.export-excel {
            background-color: #16a34a;
            color: #ffffff;
        }

        .history-actions .btn.export-excel:hover {
            background-color: #15803d;
            transform: translateY(-2px);
        }

        .history-actions .btn .spinner-icon {
            display: none;
            width: 12px;
            height: 12px;
            border: 2px solid rgba(255, 255, 255, .45);
            border-top-color: #ffffff;
            border-radius: 999px;
            animation: exportSpin .7s linear infinite;
        }

        .history-actions .btn.is-loading .button-icon {
            display: none;
        }

        .history-actions .btn.is-loading .spinner-icon {
            display: inline-flex;
            flex: 0 0 auto;
        }

        @keyframes exportSpin {
            to {
                transform: rotate(360deg);
            }
        }
    </style>
@endpush

@section('content')
    @php
        $user = auth()->user();
        $userGroup = strtoupper((string) ($user->group ?? ''));
        $isAdmin = \App\Models\Role::hasManagementAccess($user->role->name ?? null);
        $latestDraft = $draftReports->first();
        $activeTab = $activeTab ?? 'laporan';
        $historySearch = trim((string) ($historySearch ?? request('history_search', '')));
        $historyTotal = method_exists($historyReports, 'total') ? $historyReports->total() : $historyReports->count();
        $historyFirstItem = method_exists($historyReports, 'firstItem') ? $historyReports->firstItem() : ($historyTotal > 0 ? 1 : null);
        $historyLastItem = method_exists($historyReports, 'lastItem') ? $historyReports->lastItem() : $historyReports->count();
        $showHistorySearch = $historyTotal > 0 || $historySearch !== '';

        $receivedSearch = trim((string) ($receivedSearch ?? request('received_search', '')));
        $receivedTotal = method_exists($receivedReports, 'total') ? $receivedReports->total() : $receivedReports->count();
        $receivedFirstItem = method_exists($receivedReports, 'firstItem') ? $receivedReports->firstItem() : ($receivedTotal > 0 ? 1 : null);
        $receivedLastItem = method_exists($receivedReports, 'lastItem') ? $receivedReports->lastItem() : $receivedReports->count();
        $showReceivedSearch = $receivedTotal > 0 || $receivedSearch !== '';

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

        $statusMeta = function ($status): array {
            $value = $status instanceof \App\Enums\ReportStatus ? $status->value : (string) $status;

            return match ($value) {
                \App\Enums\ReportStatus::Draft->value => ['label' => 'Draft', 'class' => 'draft', 'icon' => 'fi fi-rr-blueprint'],
                \App\Enums\ReportStatus::Submitted->value => ['label' => 'Diserahkan', 'class' => 'submit', 'icon' => 'fi fi-rr-memo-circle-check'],
                \App\Enums\ReportStatus::Acknowledged->value => ['label' => 'Diterima', 'class' => 'confirm', 'icon' => 'fi fi-rr-memo-circle-check'],
                \App\Enums\ReportStatus::Approved->value => ['label' => 'Diarsipkan', 'class' => 'archive', 'icon' => 'fi fi-rr-box-open'],
                default => ['label' => ucfirst($value), 'class' => 'submit', 'icon' => 'fi fi-rr-info'],
            };
        };

        $flattenSearchValues = function ($value) use (&$flattenSearchValues): array {
            if ($value instanceof \Illuminate\Database\Eloquent\Model) {
                $attributes = $value->attributesToArray();

                foreach ($value->getRelations() as $relationName => $relationValue) {
                    $attributes[$relationName] = $relationValue;
                }

                $value = $attributes;
            } elseif ($value instanceof \Illuminate\Support\Collection) {
                $value = $value->all();
            } elseif ($value instanceof \Illuminate\Contracts\Support\Arrayable) {
                $value = $value->toArray();
            } elseif ($value instanceof \DateTimeInterface) {
                return [$value->format('Y-m-d H:i:s')];
            }

            if (is_array($value)) {
                $result = [];

                foreach ($value as $key => $item) {
                    if (is_string($key)) {
                        $result[] = str_replace(['_', '-'], ' ', $key);
                    }

                    array_push($result, ...$flattenSearchValues($item));
                }

                return $result;
            }

            return is_scalar($value) && filled($value) ? [(string) $value] : [];
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
        $userSignatureUrl = $signatureUrl($user?->signature_path);

        $canEdit = fn ($report) => ($isAdmin || (int) $report->created_by === (int) auth()->id())
            && in_array($report->status, [\App\Enums\ReportStatus::Draft, \App\Enums\ReportStatus::Submitted], true);
        $canDelete = fn ($report) => ($isAdmin || (int) $report->created_by === (int) auth()->id())
            && $report->status === \App\Enums\ReportStatus::Draft;
        $canSign = fn ($report) => $report->status === \App\Enums\ReportStatus::Submitted
            && $userGroup !== ''
            && strtoupper((string) $report->received_by_group) === $userGroup
            && (int) $report->created_by !== (int) auth()->id();
    @endphp

    <div class="content d-flex flex-column align-items-start align-self-stretch gap-30 p-content">
        <div class="content-header d-flex justify-content-between align-items-center align-content-center align-self-stretch flex-wrap p-20" style="row-gap: 10px;border-radius:16px">
            <div class="title-header d-flex flex-column align-items-start gap-2 flexible">
                <span class="text-header fw-600 fsize-20 align-self-stretch">Laporan Operasional</span>
                <span class="note fw-300 fsize-12 text-secondary align-self-stretch">Lihat laporan masuk, draft, riwayat laporan, dan buat laporan baru dari sini.</span>
            </div>
            <a href="{{ route('report-ops.create') }}" class="btn-new d-flex justify-content-center align-items-center gap-10 br-12 action-link" style="cursor: pointer;">
                <div class="icon-new"><i class="fi fi-rr-add fs-12"></i></div>
                <span class="white pure fsize-14 fw-500">Buat Laporan Operasional</span>
            </a>
        </div>

        @if ($latestDraft)
            @php($draftShift = $shiftMeta($latestDraft->shift))
            <div class="reminder-draft">
                <div class="reminder d-flex align-items-center gap-10 flexible" style="min-width: 300px;">
                    <div class="icon-reminder"><i class="fi fi-rr-info"></i></div>
                    <div class="text-reminder d-flex flex-column align-items-start flexible" style="gap: 2px;">
                        <span class="fsize-12 fw-600 align-self-stretch">Laporan Belum Diselesaikan</span>
                        <span class="fsize-9 fw-400 align-self-stretch">
                            Anda memiliki
                            <span class="fw-600">{{ $draftReports->count() }} draft</span>
                            laporan
                            <span class="text-cyan">{{ $draftShift['label'] }}</span>
                            yang belum diselesaikan.
                        </span>
                    </div>
                </div>
                <div class="reminder-button d-flex justify-content-end align-items-center gap-10">
                    <button type="button" class="btn draft-edit action-link action-modal-trigger" data-open-modal="continue-draft-modal-{{ $latestDraft->id }}">
                        <span class="text">Lanjutkan Draft</span>
                        <div class="icon-edit"><i class="fi fi-br-arrow-small-right"></i></div>
                    </button>
                    <button type="button" class="btn close" onclick="this.closest('.reminder-draft').remove()">
                        <i class="fi fi-br-cross"></i>
                    </button>
                </div>
            </div>
        @endif

        <div class="main-content d-flex flex-column align-items-start align-self-stretch p-main gap-20" style="border-radius: 16px">
            <div class="tab-content d-flex align-items-center align-content-center align-self-stretch gap-15">
                <a class="list-tab {{ $activeTab === 'laporan' ? 'active' : '' }}" id="tab-laporan">
                    <div class="list-item">
                        <div class="icon-tab">
                            <i class="fi fi-rr-document-signed"></i>
                        </div>
                        <span class="text-tab">Laporan Masuk</span>
                        @if ($incomingReports->count() > 0)
                            <div class="tab-amount fsize-10 fw-500">{{ $incomingReports->count() }}</div>
                        @endif
                    </div>
                </a>
                <a class="list-tab {{ $activeTab === 'draft' ? 'active' : '' }}" id="tab-draft">
                    <div class="list-item">
                        <div class="icon-tab">
                            <i class="fi fi-rr-edit-alt"></i>
                        </div>
                        <span class="text-tab">Draft</span>
                        @if ($draftReports->count() > 0)
                            <div class="tab-amount fsize-10 fw-500">{{ $draftReports->count() }}</div>
                        @endif
                    </div>
                </a>
                <a class="list-tab {{ $activeTab === 'riwayat' ? 'active' : '' }}" id="tab-riwayat">
                    <div class="list-item">
                        <div class="icon-tab">
                            <i class="fi fi-rr-folder"></i>
                        </div>
                        <span class="text-tab">Riwayat Laporan</span>
                        @if ($historyTotal > 0)
                            <div class="tab-amount fsize-10 fw-500">{{ $historyTotal }}</div>
                        @endif
                    </div>
                </a>
                <a class="list-tab {{ $activeTab === 'diterima' ? 'active' : '' }}" id="tab-diterima">
                    <div class="list-item">
                        <div class="icon-tab">
                            <i class="fi fi-rr-inbox-in"></i>
                        </div>
                        <span class="text-tab">Laporan Diterima</span>
                        @if ($receivedTotal > 0)
                            <div class="tab-amount fsize-10 fw-500">{{ $receivedTotal }}</div>
                        @endif
                    </div>
                </a>
            </div>

            <div id="content-laporan" class="report-in {{ $activeTab === 'laporan' ? 'd-flex' : 'd-none' }} flex-column align-items-start align-self-stretch gap-20 w-100">
                @forelse ($incomingReports as $report)
                    @php($shift = $shiftMeta($report->shift))
                    <div class="report-item">
                        <div class="report-detail d-flex flex-column align-items-start gap-8 flexible">
                            <div class="report-time d-flex align-items-center align-self-stretch gap-10">
                                <div class="shift {{ $shift['class'] }}">
                                    <span class="icon-shift"><i class="{{ $shift['icon'] }}"></i></span>
                                    <span class="text">{{ $shift['label'] }}</span>
                                </div>
                                <div class="upload-time d-flex align-items-center flexible">
                                    <span class="icon-clock"><i class="fi fi-rr-clock"></i></span>
                                    <span class="text align-self-stretch">Diunggah: {{ $formatDiff($report->updated_at) }}</span>
                                </div>
                            </div>
                            <div class="report-title d-flex flex-column align-items-start align-self-stretch">
                                <span class="title fsize-16 fw-600 text-main align-self-stretch">Laporan Operasi Harian</span>
                                <span class="id fsize-10 text-muted align-self-stretch">ID Dokumen: {{ $documentId($report) }}</span>
                            </div>
                            <div class="report-group d-flex align-items-center gap-10 br-20 white-bg">
                                <div class="group d-flex align-items-center gap-6">
                                    <div class="letter-group out">{{ strtoupper((string) $report->group_name) ?: '-' }}</div>
                                    <span class="text fsize-10 fw-600">Regu {{ strtoupper((string) $report->group_name) ?: '-' }}</span>
                                </div>
                                <span class="icon-arrow fsize-12"><i class="fi fi-rr-arrow-small-right"></i></span>
                                <div class="group d-flex align-items-center gap-6">
                                    <div class="letter-group in">{{ strtoupper((string) $report->received_by_group) ?: '-' }}</div>
                                    <span class="text fsize-10 fw-600">
                                        Regu {{ strtoupper((string) $report->received_by_group) ?: '-' }}
                                        @if ($userGroup !== '' && strtoupper((string) $report->received_by_group) === $userGroup)
                                            (Regu Anda)
                                        @endif
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="report-button d-flex justify-content-end align-items-start gap-8 flexible" style="min-width: 220px;">
                            <a href="{{ route('report-ops.show', $report) }}" class="btn see action-link" target="_blank" rel="noopener">
                                <span class="icon-eye"><i class="fi fi-rr-eye"></i></span>
                                <span class="text fw-500">Lihat Laporan</span>
                            </a>
                            @if ($canSign($report))
                                <button type="button" class="btn signed" data-sign-modal="sign-modal-{{ $report->id }}">
                                    <span class="icon-sign"><i class="fi fi-rr-file-signature"></i></span>
                                    <span class="text fw-500">Tanda Tangani</span>
                                </button>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="empty-laporan d-flex flex-column align-items-center align-self-stretch p-empty gap-10">
                        <span class="icon-empty"><i class="fi fi-rr-check-circle"></i></span>
                        <div class="empty-text d-flex flex-column align-items-center align-self-stretch" style="gap: 5px;">
                            <span class="align-self-stretch text-center fw-600">Semua Sudah Beres!!!</span>
                            <span class="align-self-stretch text-center fsize-12 text-secondary">Tidak ada laporan yang perlu ditandatangi saat ini.</span>
                        </div>
                    </div>
                @endforelse
            </div>

            <div id="content-draft" class="flex-column align-items-start align-self-stretch gap-20 w-100 {{ $activeTab === 'draft' ? 'd-flex' : 'd-none' }}">
                @forelse ($draftReports as $report)
                    @php($shift = $shiftMeta($report->shift))
                    <div class="draft-item d-flex flex-column align-items-start align-self-stretch gap-8 br-10">
                        <div class="info-time d-flex justify-content-between align-items-start align-content-start align-self-stretch flex-wrap" style="row-gap: 8px;">
                            <div class="status d-flex align-items-start gap-10">
                                <div class="badge-draft d-flex align-items-center">
                                    <span class="icon-draft"><i class="fi fi-rr-edit"></i></span>
                                    <span class="text">Draft</span>
                                </div>
                                <div class="shift {{ $shift['class'] }}">
                                    <span class="icon-shift"><i class="{{ $shift['icon'] }}"></i></span>
                                    <span class="text">{{ $shift['label'] }}</span>
                                </div>
                            </div>
                            <span class="date text-right fsize-10 text-muted align-self-stretch">{{ $formatDate($report->report_date) }}</span>
                        </div>
                        <div class="draft-report">
                            <div class="draft-detail">
                                <div class="draft-title d-flex flex-column align-items-start align-self-stretch">
                                    <span class="title fsize-16 fw-600 text-main align-self-stretch">Draft Laporan Operasi Harian</span>
                                    <span class="id fsize-10 text-muted align-self-stretch">ID Dokumen: {{ $documentId($report) }}</span>
                                </div>
                                <div class="last-edit d-flex align-items-center align-self-stretch text-muted fsize-10" style="gap:5px">
                                    <span class="icon-edit"><i class="fi fi-rr-time-forward"></i></span>
                                    <span class="text fsize-10 text-muted align-self-stretch">Terakhir diedit {{ $formatDiff($report->updated_at) }}</span>
                                </div>
                            </div>
                            <div class="draft-button d-flex justify-content-end align-items-center gap-10 flexible" style="min-width: 220px;">
                                <button type="button" class="btn-draft-edit action-link action-modal-trigger" data-open-modal="continue-draft-modal-{{ $report->id }}">
                                    <div class="icon-edit"><i class="fi fi-rr-pencil"></i></div>
                                    <span class="text">Lanjutkan Draft</span>
                                </button>
                                <button type="button" class="btn-delete action-modal-trigger" data-open-modal="delete-draft-modal-{{ $report->id }}">
                                    <div class="icon-delete"><i class="fi fi-rr-trash"></i></div>
                                </button>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="empty-laporan flex-column align-items-center align-self-stretch p-empty gap-10 d-flex">
                        <span class="icon-empty"><i class="fi fi-rr-edit"></i></span>
                        <div class="empty-text d-flex flex-column align-items-center align-self-stretch" style="gap: 5px;">
                            <span class="align-self-stretch text-center fw-600">Semua Sudah Beres!!!</span>
                            <span class="align-self-stretch text-center fsize-12 text-secondary">Tidak ada draft laporan yang perlu diselesaikan saat ini.</span>
                        </div>
                    </div>
                @endforelse
            </div>

            <div id="content-riwayat" class="w-100 {{ $activeTab === 'riwayat' ? '' : 'd-none' }}">
                @if ($showHistorySearch)
                    <form method="GET" action="{{ route('report-ops.index') }}" id="history-search-form" class="history-searchbar" autocomplete="off">
                        <input type="hidden" name="tab" value="riwayat">
                        <div class="history-search-input">
                            <span class="history-search-icon"><i class="fi fi-rr-search"></i></span>
                            <input
                                type="search"
                                id="history-search-input"
                                name="history_search"
                                class="custom-input"
                                placeholder="Cari ID, tanggal (mis. Mei 2026), shift, group, atau isi laporan..."
                                value="{{ $historySearch }}"
                                data-initial-value="{{ $historySearch }}"
                                data-page-start="{{ $historyFirstItem ?? 1 }}"
                                data-suggest-url="{{ route('report-ops.history.suggestions') }}"
                                autocomplete="off"
                                role="combobox"
                                aria-autocomplete="list"
                                aria-expanded="false"
                                aria-controls="history-suggest-dropdown"
                            >
                            <button type="button" id="history-search-clear" class="history-search-clear d-none" aria-label="Bersihkan pencarian">
                                <i class="fi fi-br-cross-small"></i>
                            </button>
                            <div id="history-suggest-dropdown" class="history-suggest-dropdown" role="listbox" aria-label="Saran pencarian laporan"></div>
                        </div>
                        <span
                            id="history-search-count"
                            class="history-search-meta fsize-10 fw-600"
                            data-total="{{ $historyTotal }}"
                            data-label="{{ $historySearch !== '' ? 'hasil' : 'laporan' }}"
                        >
                            <i class="fi fi-rr-folder-open"></i>
                            <span>{{ $historySearch !== '' ? $historyTotal.' hasil' : $historyTotal.' laporan' }}</span>
                        </span>
                    </form>
                @endif

                <div class="table-responsive-wrapper">
                    <table class="w-100 history-table">
                        <tr class="thead d-flex justify-content-between align-items-center bg-blue br-4 align-self-stretch">
                            <th class="nomor">No</th>
                            <th class="column-1">Info Dokumen</th>
                            <th class="column-1 date-column">Tanggal Laporan</th>
                            <th class="shift-column">Shift</th>
                            <th class="receiver-column">Group Penerima</th>
                            <th class="status-column">Status</th>
                            <th class="action-column">Aksi</th>
                        </tr>
                        @forelse ($historyReports as $report)
                            @php($shift = $shiftMeta($report->shift))
                            @php($status = $statusMeta($report->status))
                            <?php
                                $historySearchParts = array_merge([
                                    'Laporan Operasi Harian',
                                    $documentId($report),
                                    optional($report->report_date)->format('Y-m-d'),
                                    $formatDate($report->report_date),
                                    $formatDiff($report->updated_at),
                                    $shift['label'],
                                    $status['label'],
                                    'Group '.strtoupper((string) $report->group_name),
                                    'Regu '.strtoupper((string) $report->group_name),
                                    'Group '.strtoupper((string) $report->received_by_group),
                                    'Regu '.strtoupper((string) $report->received_by_group),
                                ], $flattenSearchValues($report));

                                $historySearchText = \Illuminate\Support\Str::lower(
                                    collect($historySearchParts)
                                        ->filter(fn ($value) => filled($value))
                                        ->map(fn ($value) => trim(strip_tags((string) $value)))
                                        ->implode(' ')
                                );
                            ?>
                            <tr
                                class="tbody d-flex justify-content-between align-items-center align-self-stretch"
                                style="padding: 6px 0;"
                                data-history-row
                                data-history-search="{{ $historySearchText }}"
                            >
                                <td class="nomor">{{ $historyFirstItem ? $historyFirstItem + $loop->index : $loop->iteration }}</td>
                                <td class="column-2">
                                    <span>Laporan Operasi Harian</span>
                                    <span class="fsize-10 fw-400 text-muted">ID: {{ $documentId($report) }}</span>
                                </td>
                                <td class="column-2 date-column">
                                    <span>{{ $formatDate($report->report_date) }}</span>
                                    <span class="fsize-10 fw-400 text-muted">Terakhir diedit: {{ $formatDiff($report->updated_at) }}</span>
                                </td>
                                <td class="column-3 shift-column">
                                    <div class="shift {{ $shift['class'] }}">
                                        <span class="icon-shift"><i class="{{ $shift['icon'] }}"></i></span>
                                        <span class="text">{{ $shift['label'] }}</span>
                                    </div>
                                </td>
                                <td class="column-3 receiver-column">
                                    <div class="report-group d-flex align-items-center gap-6 br-20 white-bg">
                                        <div class="letter-group in">{{ strtoupper((string) $report->received_by_group) ?: '-' }}</div>
                                        <span class="text fsize-10 fw-600">Regu {{ strtoupper((string) $report->received_by_group) ?: '-' }}</span>
                                    </div>
                                </td>
                                <td class="column-3 status-column">
                                    <div class="status {{ $status['class'] }}">
                                        <span class="icon-status"><i class="{{ $status['icon'] }}"></i></span>
                                        <span class="text">{{ $status['label'] }}</span>
                                    </div>
                                </td>
                                <td class="aksi">
                                    <div class="history-actions">
                                        <a href="{{ route('report-ops.show', $report) }}" class="btn see action-link" target="_blank" rel="noopener">
                                            <span><i class="fi fi-rr-eye"></i></span>
                                            <span>Lihat</span>
                                        </a>
                                        <a href="{{ route('report-ops.pdf', $report) }}" class="btn export-pdf action-link" data-export-loading>
                                            <span class="button-icon"><i class="fi fi-rr-file-pdf"></i></span>
                                            <span class="spinner-icon" aria-hidden="true"></span>
                                            <span>PDF</span>
                                        </a>
                                        <a href="{{ route('report-ops.excel', $report) }}" class="btn export-excel action-link" data-export-loading>
                                            <span class="button-icon"><i class="fi fi-rr-file-excel"></i></span>
                                            <span class="spinner-icon" aria-hidden="true"></span>
                                            <span>Excel</span>
                                        </a>
                                        @if ($canEdit($report))
                                            <button type="button" class="btn edit action-link action-modal-trigger" data-open-modal="edit-report-modal-{{ $report->id }}">
                                                <span><i class="fi fi-rr-pencil"></i></span>
                                                <span>Edit</span>
                                            </button>
                                        @endif
                                        <a href="{{ route('report-ops.show', $report) }}?print=1" class="btn print-report action-link" target="_blank" rel="noopener" title="Print laporan" aria-label="Print laporan">
                                            <span><i class="fi fi-rr-print"></i></span>
                                        </a>
                                        @if ($canDelete($report))
                                            <button type="button" class="btn delete-icon action-modal-trigger" data-open-modal="delete-draft-modal-{{ $report->id }}" title="Hapus laporan" aria-label="Hapus laporan">
                                                <i class="fi fi-rr-trash"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="border-0 p-0">
                                    <div class="empty-laporan d-flex flex-column align-items-center justify-content-center align-self-stretch p-empty gap-10 w-100">
                                        <span class="icon-empty"><i class="{{ $historySearch !== '' ? 'fi fi-rr-search-alt' : 'fi fi-rr-folder-open' }}"></i></span>
                                        <div class="empty-text d-flex flex-column align-items-center align-self-stretch" style="gap: 5px;">
                                            <span class="align-self-stretch text-center fw-600">{{ $historySearch !== '' ? 'Laporan Tidak Ditemukan' : 'Riwayat Masih Kosong!!!' }}</span>
                                            <span class="align-self-stretch text-center fsize-12 text-secondary">
                                                {{ $historySearch !== '' ? 'Coba gunakan ID, tanggal, shift, regu, nama kapal, karyawan, aktivitas, truck, atau isi laporan lain.' : 'Belum ada riwayat laporan yang dibuat saat ini.' }}
                                            </span>
                                        </div>
                                        @if ($historySearch === '')
                                            <a href="{{ route('report-ops.create') }}" class="btn new-report action-link">
                                                <span><i class="fi fi-rr-add-document"></i></span>
                                                <span>Buat Laporan Baru</span>
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                        @if ($historyReports->count() > 0)
                            <tr id="history-search-empty" class="history-search-empty d-none">
                                <td colspan="7" class="border-0 p-0">
                                    <div class="empty-laporan d-flex flex-column align-items-center justify-content-center align-self-stretch p-empty gap-10 w-100">
                                        <span class="icon-empty"><i class="fi fi-rr-search-alt"></i></span>
                                        <div class="empty-text d-flex flex-column align-items-center align-self-stretch" style="gap: 5px;">
                                            <span class="align-self-stretch text-center fw-600">Laporan Tidak Ditemukan</span>
                                            <span class="align-self-stretch text-center fsize-12 text-secondary">Sedang mencari ke seluruh riwayat. Jika belum muncul, coba gunakan ID, tanggal, shift, regu, kapal, atau isi laporan lain.</span>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    </table>
                </div>

                @if (method_exists($historyReports, 'hasPages') && $historyReports->hasPages())
                    <div class="history-pagination">
                        <div class="history-pagination-info fsize-11 fw-500">
                            Menampilkan {{ $historyFirstItem }}-{{ $historyLastItem }} dari {{ $historyTotal }} {{ $historySearch !== '' ? 'hasil' : 'laporan' }}
                        </div>
                        <div class="history-page-list">
                            @if ($historyReports->onFirstPage())
                                <span class="history-page-disabled"><i class="fi fi-rr-angle-small-left"></i></span>
                            @else
                                <a href="{{ $historyReports->previousPageUrl() }}" class="history-page-link" aria-label="Halaman sebelumnya">
                                    <i class="fi fi-rr-angle-small-left"></i>
                                </a>
                            @endif

                            @foreach ($historyReports->getUrlRange(1, $historyReports->lastPage()) as $page => $url)
                                <a href="{{ $url }}" class="history-page-link {{ $historyReports->currentPage() === $page ? 'active' : '' }}" aria-label="Halaman {{ $page }}">
                                    {{ $page }}
                                </a>
                            @endforeach

                            @if ($historyReports->hasMorePages())
                                <a href="{{ $historyReports->nextPageUrl() }}" class="history-page-link" aria-label="Halaman berikutnya">
                                    <i class="fi fi-rr-angle-small-right"></i>
                                </a>
                            @else
                                <span class="history-page-disabled"><i class="fi fi-rr-angle-small-right"></i></span>
                            @endif
                        </div>
                    </div>
                @endif
            </div>

            <div id="content-diterima" class="w-100 {{ $activeTab === 'diterima' ? '' : 'd-none' }}">
                @if ($showReceivedSearch)
                    <form method="GET" action="{{ route('report-ops.index') }}" id="received-search-form" class="history-searchbar" autocomplete="off">
                        <input type="hidden" name="tab" value="diterima">
                        <div class="history-search-input">
                            <span class="history-search-icon"><i class="fi fi-rr-search"></i></span>
                            <input
                                type="search"
                                id="received-search-input"
                                name="received_search"
                                class="custom-input"
                                placeholder="Cari ID, tanggal (mis. Mei 2026), shift, group pengirim, atau isi laporan..."
                                value="{{ $receivedSearch }}"
                                data-initial-value="{{ $receivedSearch }}"
                                data-page-start="{{ $receivedFirstItem ?? 1 }}"
                                data-suggest-url="{{ route('report-ops.received.suggestions') }}"
                                autocomplete="off"
                                role="combobox"
                                aria-autocomplete="list"
                                aria-expanded="false"
                                aria-controls="received-suggest-dropdown"
                            >
                            <button type="button" id="received-search-clear" class="history-search-clear d-none" aria-label="Bersihkan pencarian">
                                <i class="fi fi-br-cross-small"></i>
                            </button>
                            <div id="received-suggest-dropdown" class="history-suggest-dropdown" role="listbox" aria-label="Saran pencarian laporan diterima"></div>
                        </div>
                        <span
                            id="received-search-count"
                            class="history-search-meta fsize-10 fw-600"
                            data-total="{{ $receivedTotal }}"
                            data-label="{{ $receivedSearch !== '' ? 'hasil' : 'laporan' }}"
                        >
                            <i class="fi fi-rr-folder-open"></i>
                            <span>{{ $receivedSearch !== '' ? $receivedTotal.' hasil' : $receivedTotal.' laporan' }}</span>
                        </span>
                    </form>
                @endif

                <div class="table-responsive-wrapper">
                    <table class="w-100 history-table">
                        <tr class="thead d-flex justify-content-between align-items-center bg-blue br-4 align-self-stretch">
                            <th class="nomor">No</th>
                            <th class="column-1">Info Dokumen</th>
                            <th class="column-1 date-column">Tanggal Laporan</th>
                            <th class="shift-column">Shift</th>
                            <th class="receiver-column">Group Pengirim</th>
                            <th class="status-column">Status</th>
                            <th class="action-column">Aksi</th>
                        </tr>
                        @forelse ($receivedReports as $report)
                            @php($shift = $shiftMeta($report->shift))
                            @php($status = $statusMeta($report->status))
                            <?php
                                $receivedSearchParts = array_merge([
                                    'Laporan Operasi Harian',
                                    $documentId($report),
                                    optional($report->report_date)->format('Y-m-d'),
                                    $formatDate($report->report_date),
                                    $formatDiff($report->updated_at),
                                    $shift['label'],
                                    $status['label'],
                                    'Group '.strtoupper((string) $report->group_name),
                                    'Regu '.strtoupper((string) $report->group_name),
                                ], $flattenSearchValues($report));

                                $receivedSearchText = \Illuminate\Support\Str::lower(
                                    collect($receivedSearchParts)
                                        ->filter(fn ($value) => filled($value))
                                        ->map(fn ($value) => trim(strip_tags((string) $value)))
                                        ->implode(' ')
                                );
                            ?>
                            <tr
                                class="tbody d-flex justify-content-between align-items-center align-self-stretch"
                                style="padding: 6px 0;"
                                data-received-row
                                data-received-search="{{ $receivedSearchText }}"
                            >
                                <td class="nomor">{{ $receivedFirstItem ? $receivedFirstItem + $loop->index : $loop->iteration }}</td>
                                <td class="column-2">
                                    <span>Laporan Operasi Harian</span>
                                    <span class="fsize-10 fw-400 text-muted">ID: {{ $documentId($report) }}</span>
                                </td>
                                <td class="column-2 date-column">
                                    <span>{{ $formatDate($report->report_date) }}</span>
                                    <span class="fsize-10 fw-400 text-muted">Terakhir diedit: {{ $formatDiff($report->updated_at) }}</span>
                                </td>
                                <td class="column-3 shift-column">
                                    <div class="shift {{ $shift['class'] }}">
                                        <span class="icon-shift"><i class="{{ $shift['icon'] }}"></i></span>
                                        <span class="text">{{ $shift['label'] }}</span>
                                    </div>
                                </td>
                                <td class="column-3 receiver-column">
                                    <div class="report-group d-flex align-items-center gap-6 br-20 white-bg">
                                        <div class="letter-group out">{{ strtoupper((string) $report->group_name) ?: '-' }}</div>
                                        <span class="text fsize-10 fw-600">Regu {{ strtoupper((string) $report->group_name) ?: '-' }}</span>
                                    </div>
                                </td>
                                <td class="column-3 status-column">
                                    <div class="status {{ $status['class'] }}">
                                        <span class="icon-status"><i class="{{ $status['icon'] }}"></i></span>
                                        <span class="text">{{ $status['label'] }}</span>
                                    </div>
                                </td>
                                <td class="aksi">
                                    <div class="history-actions">
                                        <a href="{{ route('report-ops.show', $report) }}" class="btn see action-link" target="_blank" rel="noopener">
                                            <span><i class="fi fi-rr-eye"></i></span>
                                            <span>Lihat</span>
                                        </a>
                                        <a href="{{ route('report-ops.pdf', $report) }}" class="btn export-pdf action-link" data-export-loading>
                                            <span class="button-icon"><i class="fi fi-rr-file-pdf"></i></span>
                                            <span class="spinner-icon" aria-hidden="true"></span>
                                            <span>PDF</span>
                                        </a>
                                        <a href="{{ route('report-ops.excel', $report) }}" class="btn export-excel action-link" data-export-loading>
                                            <span class="button-icon"><i class="fi fi-rr-file-excel"></i></span>
                                            <span class="spinner-icon" aria-hidden="true"></span>
                                            <span>Excel</span>
                                        </a>
                                        <a href="{{ route('report-ops.show', $report) }}?print=1" class="btn print-report action-link" target="_blank" rel="noopener" title="Print laporan" aria-label="Print laporan">
                                            <span><i class="fi fi-rr-print"></i></span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="border-0 p-0">
                                    <div class="empty-laporan d-flex flex-column align-items-center justify-content-center align-self-stretch p-empty gap-10 w-100">
                                        <span class="icon-empty"><i class="{{ $receivedSearch !== '' ? 'fi fi-rr-search-alt' : 'fi fi-rr-inbox' }}"></i></span>
                                        <div class="empty-text d-flex flex-column align-items-center align-self-stretch" style="gap: 5px;">
                                            <span class="align-self-stretch text-center fw-600">{{ $receivedSearch !== '' ? 'Laporan Tidak Ditemukan' : 'Belum Ada Laporan Masuk' }}</span>
                                            <span class="align-self-stretch text-center fsize-12 text-secondary">
                                                {{ $receivedSearch !== '' ? 'Coba gunakan ID, tanggal, shift, regu pengirim, nama kapal, atau isi laporan lain.' : 'Laporan dari regu lain yang ditujukan ke regu Anda akan tampil di sini setelah Anda menerimanya.' }}
                                            </span>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                        @if ($receivedReports->count() > 0)
                            <tr id="received-search-empty" class="history-search-empty d-none">
                                <td colspan="7" class="border-0 p-0">
                                    <div class="empty-laporan d-flex flex-column align-items-center justify-content-center align-self-stretch p-empty gap-10 w-100">
                                        <span class="icon-empty"><i class="fi fi-rr-search-alt"></i></span>
                                        <div class="empty-text d-flex flex-column align-items-center align-self-stretch" style="gap: 5px;">
                                            <span class="align-self-stretch text-center fw-600">Laporan Tidak Ditemukan</span>
                                            <span class="align-self-stretch text-center fsize-12 text-secondary">Coba gunakan ID, tanggal, shift, regu pengirim, kapal, atau isi laporan lain. Tekan Enter untuk mencari ke seluruh laporan diterima.</span>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    </table>
                </div>

                @if (method_exists($receivedReports, 'hasPages') && $receivedReports->hasPages())
                    <div class="history-pagination">
                        <div class="history-pagination-info fsize-11 fw-500">
                            Menampilkan {{ $receivedFirstItem }}-{{ $receivedLastItem }} dari {{ $receivedTotal }} {{ $receivedSearch !== '' ? 'hasil' : 'laporan' }}
                        </div>
                        <div class="history-page-list">
                            @if ($receivedReports->onFirstPage())
                                <span class="history-page-disabled"><i class="fi fi-rr-angle-small-left"></i></span>
                            @else
                                <a href="{{ $receivedReports->previousPageUrl() }}" class="history-page-link" aria-label="Halaman sebelumnya">
                                    <i class="fi fi-rr-angle-small-left"></i>
                                </a>
                            @endif

                            @foreach ($receivedReports->getUrlRange(1, $receivedReports->lastPage()) as $page => $url)
                                <a href="{{ $url }}" class="history-page-link {{ $receivedReports->currentPage() === $page ? 'active' : '' }}" aria-label="Halaman {{ $page }}">
                                    {{ $page }}
                                </a>
                            @endforeach

                            @if ($receivedReports->hasMorePages())
                                <a href="{{ $receivedReports->nextPageUrl() }}" class="history-page-link" aria-label="Halaman berikutnya">
                                    <i class="fi fi-rr-angle-small-right"></i>
                                </a>
                            @else
                                <span class="history-page-disabled"><i class="fi fi-rr-angle-small-right"></i></span>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('modals')
    @foreach ($incomingReports as $report)
        @if ($canSign($report))
            @php($shift = $shiftMeta($report->shift))
            <div class="modal-overlay" id="sign-modal-{{ $report->id }}">
                <div class="pop-up signed d-flex flex-column gap-20">
                    <div class="pop-up-header d-flex justify-content-between align-items-center">
                        <span class="fw-600 fsize-16">Konfirmasi Tanda Tangan</span>
                        <button type="button" class="btn-close-modal border-0 bg-transparent text-muted" data-close-sign-modal>
                            <i class="fi fi-br-cross fsize-10"></i>
                        </button>
                    </div>

                    <div class="pop-up-content d-flex flex-column gap-15">
                        <div class="pop-up detail d-flex align-items-center">
                            <span class="icon-document"><i class="fi fi-rr-file-signature"></i></span>
                            <div class="d-flex flex-column">
                                <span class="fw-600 fsize-14">Tanda tangani laporan ini?</span>
                                <span class="fsize-10 text-secondary">ID: {{ $documentId($report) }} - {{ $shift['label'] }}</span>
                            </div>
                        </div>
                        <div class="signature-box sign-modal-signature d-flex">
                            @if ($userSignatureUrl)
                                <img src="{{ $userSignatureUrl }}" class="img-sign" alt="Tanda tangan {{ $user->name }}">
                            @else
                                <div class="img-sign sign-modal-signature-placeholder">[TTD]</div>
                            @endif
                            <div class="d-flex flex-column" style="gap: 4px; min-width: 0;">
                                <span class="fw-600 fsize-14 text-main">{{ $user->name ?? 'Petugas Operasional' }}</span>
                                <span class="fsize-10 text-secondary">Regu {{ $userGroup ?: '-' }} &bull; {{ $shift['label'] }}</span>
                                <span class="verified fsize-10 align-self-start">
                                    <span class="icon-verified"><i class="fi fi-rr-shield-check"></i></span>
                                    Terverifikasi Sistem
                                </span>
                            </div>
                        </div>
                        <p class="fsize-12 sign-modal-note">
                            <i class="fi fi-rr-info"></i>
                            <span>
                                Laporan ini diterima grup Anda dari Regu {{ strtoupper((string) $report->group_name) ?: '-' }}, lalu akan dikirim ke manajer setelah tanda tangan dikonfirmasi.
                            </span>
                        </p>
                    </div>

                    <div class="pop-up footer d-flex justify-content-end gap-10 flex-wrap">
                        <button type="button" class="btn cancel btn-close-modal" data-close-sign-modal>Batal</button>
                        <a href="{{ route('report-ops.show', $report) }}" class="btn check-report" target="_blank" rel="noopener">
                            <i class="fi fi-rr-eye me-1"></i> Periksa Laporan
                        </a>
                        <form action="{{ route('report-ops.sign', $report) }}" method="POST" class="inline-action-form">
                            @csrf
                            <button type="submit" class="btn confirm">
                                <i class="fi fi-rr-file-signature me-1"></i> Ya, Tanda Tangani
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    @endforeach

    @foreach ($draftReports as $report)
        @php($shift = $shiftMeta($report->shift))
        <div class="modal-overlay" id="continue-draft-modal-{{ $report->id }}">
            <div class="pop-up signed d-flex flex-column gap-20">
                <div class="pop-up-header d-flex justify-content-between align-items-center">
                    <span class="fw-600 fsize-16">Lanjutkan Draft</span>
                    <button type="button" class="btn-close-modal border-0 bg-transparent text-muted" data-close-action-modal>
                        <i class="fi fi-br-cross fsize-10"></i>
                    </button>
                </div>

                <div class="pop-up-content d-flex flex-column gap-15">
                    <div class="pop-up detail d-flex align-items-center">
                        <span class="icon-document"><i class="fi fi-rr-edit"></i></span>
                        <div class="d-flex flex-column">
                            <span class="fw-600 fsize-14">Buka draft ini?</span>
                            <span class="fsize-10 text-secondary">ID: {{ $documentId($report) }} - {{ $shift['label'] }}</span>
                        </div>
                    </div>
                    <p class="fsize-12 sign-modal-note">Draft akan dibuka kembali dengan data terakhir yang tersimpan agar bisa dilanjutkan.</p>
                </div>

                <div class="pop-up footer d-flex justify-content-end gap-10 flex-wrap">
                    <button type="button" class="btn cancel btn-close-modal" data-close-action-modal>Batal</button>
                    <a href="{{ route('report-ops.edit', $report) }}" class="btn edit-confirm">
                        <i class="fi fi-rr-pencil me-1"></i> Ya, Lanjutkan
                    </a>
                </div>
            </div>
        </div>

        <div class="modal-overlay" id="delete-draft-modal-{{ $report->id }}">
            <div class="pop-up signed d-flex flex-column gap-20">
                <div class="pop-up-header d-flex justify-content-between align-items-center">
                    <span class="fw-600 fsize-16">Hapus Draft</span>
                    <button type="button" class="btn-close-modal border-0 bg-transparent text-muted" data-close-action-modal>
                        <i class="fi fi-br-cross fsize-10"></i>
                    </button>
                </div>

                <div class="pop-up-content d-flex flex-column gap-15">
                    <div class="pop-up detail danger d-flex align-items-center">
                        <span class="icon-document danger"><i class="fi fi-rr-trash"></i></span>
                        <div class="d-flex flex-column">
                            <span class="fw-600 fsize-14">Hapus draft ini?</span>
                            <span class="fsize-10 text-secondary">ID: {{ $documentId($report) }} - {{ $shift['label'] }}</span>
                        </div>
                    </div>
                    <p class="fsize-12 sign-modal-note">Draft yang dihapus tidak akan muncul lagi di daftar draft dan tidak bisa dilanjutkan.</p>
                </div>

                <div class="pop-up footer d-flex justify-content-end gap-10 flex-wrap">
                    <button type="button" class="btn cancel btn-close-modal" data-close-action-modal>Batal</button>
                    <form action="{{ route('report-ops.destroy', $report) }}" method="POST" class="inline-action-form">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn delete-confirm">
                            <i class="fi fi-rr-trash me-1"></i> Ya, Hapus Draft
                        </button>
                    </form>
                </div>
            </div>
        </div>
    @endforeach

    @foreach ($historyReports as $report)
        @if ($canEdit($report))
            @php($shift = $shiftMeta($report->shift))
            <div class="modal-overlay" id="edit-report-modal-{{ $report->id }}">
                <div class="pop-up signed d-flex flex-column gap-20">
                    <div class="pop-up-header d-flex justify-content-between align-items-center">
                        <span class="fw-600 fsize-16">Edit Laporan</span>
                        <button type="button" class="btn-close-modal border-0 bg-transparent text-muted" data-close-action-modal>
                            <i class="fi fi-br-cross fsize-10"></i>
                        </button>
                    </div>

                    <div class="pop-up-content d-flex flex-column gap-15">
                        <div class="pop-up detail d-flex align-items-center">
                            <span class="icon-document"><i class="fi fi-rr-pencil"></i></span>
                            <div class="d-flex flex-column">
                                <span class="fw-600 fsize-14">Edit laporan ini?</span>
                                <span class="fsize-10 text-secondary">ID: {{ $documentId($report) }} - {{ $shift['label'] }}</span>
                            </div>
                        </div>
                        <p class="fsize-12 sign-modal-note">Laporan akan dibuka di form edit. Perubahan yang dikirim akan memperbarui data laporan ini.</p>
                    </div>

                    <div class="pop-up footer d-flex justify-content-end gap-10 flex-wrap">
                        <button type="button" class="btn cancel btn-close-modal" data-close-action-modal>Batal</button>
                        <a href="{{ route('report-ops.show', $report) }}" class="btn check-report" target="_blank" rel="noopener">
                            <i class="fi fi-rr-eye me-1"></i> Periksa Laporan
                        </a>
                        <a href="{{ route('report-ops.edit', $report) }}" class="btn edit-confirm">
                            <i class="fi fi-rr-pencil me-1"></i> Ya, Edit
                        </a>
                    </div>
                </div>
            </div>
        @endif
    @endforeach
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('[data-open-modal]').forEach(button => {
                button.addEventListener('click', function () {
                    const modal = document.getElementById(this.dataset.openModal);
                    if (modal) modal.classList.add('show');
                });
            });

            document.querySelectorAll('[data-sign-modal]').forEach(button => {
                button.addEventListener('click', function () {
                    const modal = document.getElementById(this.dataset.signModal);
                    if (modal) modal.classList.add('show');
                });
            });

            document.querySelectorAll('[data-close-sign-modal], [data-close-action-modal]').forEach(button => {
                button.addEventListener('click', function () {
                    this.closest('.modal-overlay')?.classList.remove('show');
                });
            });

            const filenameFromDisposition = (disposition) => {
                if (!disposition) return '';
                const match = disposition.match(/filename\*?=(?:UTF-8'')?["']?([^"';]+)/i);
                if (!match) return '';
                try { return decodeURIComponent(match[1]); } catch (_) { return match[1]; }
            };

            document.querySelectorAll('[data-export-loading]').forEach(button => {
                button.addEventListener('click', async function (event) {
                    event.preventDefault();

                    // Cegah klik ganda saat unduhan sedang berjalan.
                    if (this.classList.contains('is-loading')) return;

                    const url = this.getAttribute('href');
                    if (!url) return;

                    this.classList.add('is-loading');

                    try {
                        // Ambil berkas lewat fetch agar tahu persis kapan file selesai
                        // diterima — spinner berhenti tepat saat unduhan selesai,
                        // tanpa timer/perkiraan waktu.
                        const response = await fetch(url, {
                            headers: { 'X-Requested-With': 'XMLHttpRequest' },
                            credentials: 'same-origin',
                        });

                        if (!response.ok) throw new Error('Gagal mengunduh berkas.');

                        const blob = await response.blob();
                        const filename = filenameFromDisposition(response.headers.get('Content-Disposition'));
                        const objectUrl = URL.createObjectURL(blob);
                        const link = document.createElement('a');
                        link.href = objectUrl;
                        link.download = filename || '';
                        document.body.appendChild(link);
                        link.click();
                        link.remove();
                        window.setTimeout(() => URL.revokeObjectURL(objectUrl), 10000);
                    } catch (error) {
                        // Bila fetch gagal, jatuh ke navigasi biasa sebagai cadangan.
                        window.location.href = url;
                    } finally {
                        this.classList.remove('is-loading');
                    }
                });
            });

            const historySearchInput = document.getElementById('history-search-input');
            const historySearchForm = document.getElementById('history-search-form');
            const historySearchClear = document.getElementById('history-search-clear');
            const historySearchCountBadge = document.getElementById('history-search-count');
            const historySearchCount = historySearchCountBadge?.querySelector('span');
            const historySearchEmpty = document.getElementById('history-search-empty');
            const historyRows = Array.from(document.querySelectorAll('[data-history-row]'));
            const historySuggestDropdown = document.getElementById('history-suggest-dropdown');
            const historySearchBox = historySearchInput?.closest('.history-search-input');
            const historySearchDebounceMs = 250;
            const historyLiveSearchMinLength = 2;
            const historySearchInitialValue = historySearchInput?.dataset.initialValue || '';
            const historySuggestUrl = historySearchInput?.dataset.suggestUrl || '';
            const historyPageStart = Number(historySearchInput?.dataset.pageStart || 1);
            const historyServerTotal = Number(historySearchCountBadge?.dataset.total || historyRows.length);
            const historyServerLabel = historySearchCountBadge?.dataset.label || 'laporan';
            let historySearchTimer = null;
            let historySuggestController = null;
            let historySuggestItems = [];
            let historySuggestActiveIndex = -1;

            function normalizeHistoryKeyword(value) {
                return String(value || '')
                    .toLowerCase()
                    .normalize('NFD')
                    .replace(/[\u0300-\u036f]/g, '')
                    .trim();
            }

            function escapeHtml(value) {
                return String(value ?? '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            function updateHistoryRowsFilter() {
                if (!historySearchInput) return;

                const keyword = normalizeHistoryKeyword(historySearchInput.value);
                const initialKeyword = normalizeHistoryKeyword(historySearchInitialValue);
                let visibleCount = 0;

                historyRows.forEach(row => {
                    const searchTarget = normalizeHistoryKeyword(row.dataset.historySearch || row.textContent);
                    const isMatch = keyword === '' || searchTarget.includes(keyword);

                    row.classList.toggle('d-none', !isMatch);

                    if (isMatch) {
                        visibleCount += 1;
                        const numberCell = row.querySelector('.nomor');
                        if (numberCell) numberCell.textContent = historyPageStart + visibleCount - 1;
                    }
                });

                if (historySearchEmpty) {
                    historySearchEmpty.classList.toggle('d-none', keyword === '' || visibleCount > 0);
                }

                if (historySearchClear) {
                    historySearchClear.classList.toggle('d-none', keyword === '');
                }

                if (historySearchCount) {
                    if (keyword === '' || keyword === initialKeyword) {
                        historySearchCountBadge?.classList.remove('is-searching');
                        historySearchCount.textContent = `${historyServerTotal} ${historyServerLabel}`;
                    } else {
                        historySearchCount.textContent = `${visibleCount} dari ${historyRows.length} di halaman ini`;
                    }
                }
            }

            function closeSuggestDropdown() {
                if (!historySuggestDropdown) return;
                if (historySearchTimer) {
                    window.clearTimeout(historySearchTimer);
                    historySearchTimer = null;
                }

                if (historySuggestController) {
                    historySuggestController.abort();
                    historySuggestController = null;
                }

                historySuggestDropdown.classList.remove('show');
                historySuggestDropdown.innerHTML = '';
                historySuggestItems = [];
                historySuggestActiveIndex = -1;
                historySearchInput?.setAttribute('aria-expanded', 'false');
            }

            function renderSuggestState(html) {
                if (!historySuggestDropdown) return;
                historySuggestDropdown.innerHTML = html;
                historySuggestDropdown.classList.add('show');
                historySearchInput?.setAttribute('aria-expanded', 'true');
            }

            function renderSuggestItems(payload) {
                if (!historySuggestDropdown) return;

                const items = Array.isArray(payload?.items) ? payload.items : [];
                historySuggestItems = items;
                historySuggestActiveIndex = items.length > 0 ? 0 : -1;

                if (items.length === 0) {
                    renderSuggestState(`<div class="history-suggest-empty">Tidak ada laporan yang cocok. Coba kata kunci lain seperti tanggal, ID, atau nama kapal.</div>`);
                    return;
                }

                const header = `<div class="history-suggest-header">${items.length} saran teratas</div>`;
                const list = items.map((item, index) => {
                    const statusClass = `status-${escapeHtml(item.status_class || 'submit')}`;
                    const shiftClass = `shift-${escapeHtml(item.shift_class || 'pagi')}`;
                    return `
                        <button
                            type="button"
                            class="history-suggest-item${index === 0 ? ' is-active' : ''}"
                            data-index="${index}"
                        >
                            <div class="history-suggest-item-title">
                                <span>${escapeHtml(item.title)} \u00b7 ${escapeHtml(item.report_date)}</span>
                                <span class="history-suggest-item-id">${escapeHtml(item.document_id)}</span>
                            </div>
                            <div class="history-suggest-item-meta">
                                <span class="chip ${shiftClass}">${escapeHtml(item.shift_label)}</span>
                                <span class="chip">Regu ${escapeHtml(item.group_from)} \u2192 ${escapeHtml(item.group_to)}</span>
                                <span class="chip ${statusClass}">${escapeHtml(item.status_label)}</span>
                                <span>Terakhir diedit ${escapeHtml(item.updated_diff)}</span>
                            </div>
                        </button>
                    `;
                }).join('');

                renderSuggestState(header + list);
            }

            function historySuggestSearchTerm(item) {
                return String(item?.document_id || historySearchInput?.value || '').trim();
            }

            function setActiveSuggest(index) {
                if (!historySuggestDropdown) return;
                const nodes = historySuggestDropdown.querySelectorAll('.history-suggest-item');
                if (!nodes.length) return;

                const normalized = ((index % nodes.length) + nodes.length) % nodes.length;
                historySuggestActiveIndex = normalized;

                nodes.forEach((node, i) => node.classList.toggle('is-active', i === normalized));
                nodes[normalized]?.scrollIntoView({ block: 'nearest' });
            }

            async function fetchHistorySuggestions(keyword) {
                if (!historySuggestUrl || !historySuggestDropdown) return;

                if (historySuggestController) historySuggestController.abort();
                historySuggestController = new AbortController();

                renderSuggestState(`<div class="history-suggest-loading">Memuat saran...</div>`);

                try {
                    const url = new URL(historySuggestUrl, window.location.origin);
                    url.searchParams.set('q', keyword);

                    const response = await fetch(url.toString(), {
                        signal: historySuggestController.signal,
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin',
                    });

                    if (!response.ok) throw new Error('request failed');

                    const payload = await response.json();
                    renderSuggestItems(payload);
                } catch (error) {
                    if (error.name === 'AbortError') return;
                    renderSuggestState(`<div class="history-suggest-empty">Tidak bisa memuat saran. Coba lagi.</div>`);
                }
            }

            function openSuggestDropdownFromSearch() {
                const keyword = historySearchInput?.value.trim() ?? '';
                if (normalizeHistoryKeyword(keyword).length >= historyLiveSearchMinLength) {
                    fetchHistorySuggestions(keyword);
                }
            }

            function isPointerInsideSuggestArea(event) {
                if (!historySearchBox || !historySuggestDropdown?.classList.contains('show')) {
                    return false;
                }

                const searchRect = historySearchBox.getBoundingClientRect();
                const dropdownRect = historySuggestDropdown.getBoundingClientRect();
                const safeGap = 10;
                const left = Math.min(searchRect.left, dropdownRect.left) - safeGap;
                const right = Math.max(searchRect.right, dropdownRect.right) + safeGap;
                const top = Math.min(searchRect.top, dropdownRect.top) - safeGap;
                const bottom = Math.max(searchRect.bottom, dropdownRect.bottom) + safeGap;

                return event.clientX >= left
                    && event.clientX <= right
                    && event.clientY >= top
                    && event.clientY <= bottom;
            }

            function submitHistorySearchToServer(keyword) {
                if (!historySearchForm) return;

                const normalizedKeyword = String(keyword || '').trim();
                const url = new URL(historySearchForm.action, window.location.origin);
                url.searchParams.set('tab', 'riwayat');
                url.searchParams.delete('history_page');

                if (normalizedKeyword !== '') {
                    url.searchParams.set('history_search', normalizedKeyword);
                } else {
                    url.searchParams.delete('history_search');
                }

                window.location.assign(url.toString());
            }

            function scheduleHistorySearch() {
                if (historySearchTimer) {
                    window.clearTimeout(historySearchTimer);
                }

                updateHistoryRowsFilter();

                const keyword = historySearchInput?.value.trim() ?? '';

                if (keyword === '') {
                    closeSuggestDropdown();
                    return;
                }

                if (normalizeHistoryKeyword(keyword).length >= historyLiveSearchMinLength) {
                    historySearchTimer = window.setTimeout(() => fetchHistorySuggestions(keyword), historySearchDebounceMs);
                } else {
                    closeSuggestDropdown();
                }
            }

            if (historySearchForm) {
                historySearchForm.addEventListener('submit', event => {
                    if (historySearchTimer) window.clearTimeout(historySearchTimer);
                    closeSuggestDropdown();
                });
            }

            if (historySearchInput) {
                historySearchInput.addEventListener('input', scheduleHistorySearch);

                historySearchInput.addEventListener('focus', () => {
                    openSuggestDropdownFromSearch();
                });

                historySearchInput.addEventListener('keydown', event => {
                    if (event.key === 'Escape') {
                        if (historySearchTimer) window.clearTimeout(historySearchTimer);
                        historySearchInput.value = '';
                        updateHistoryRowsFilter();
                        closeSuggestDropdown();
                        if (normalizeHistoryKeyword(historySearchInitialValue) !== '') {
                            submitHistorySearchToServer('');
                        }
                        return;
                    }

                    if (event.key === 'ArrowDown') {
                        if (historySuggestItems.length) {
                            event.preventDefault();
                            setActiveSuggest(historySuggestActiveIndex + 1);
                        }
                    } else if (event.key === 'ArrowUp') {
                        if (historySuggestItems.length) {
                            event.preventDefault();
                            setActiveSuggest(historySuggestActiveIndex - 1);
                        }
                    }
                });
            }

            if (historySearchBox) {
                historySearchBox.addEventListener('click', event => {
                    const item = event.target.closest('.history-suggest-item');

                    if (item) {
                        if (historySearchTimer) window.clearTimeout(historySearchTimer);
                        const index = Number(item.dataset.index || -1);
                        const keyword = historySuggestSearchTerm(historySuggestItems[index]);
                        if (historySearchInput) historySearchInput.value = keyword;
                        submitHistorySearchToServer(keyword);
                        return;
                    }

                    if (!event.target.closest('.history-suggest-dropdown')) {
                        openSuggestDropdownFromSearch();
                    }
                });
            }

            document.addEventListener('mousemove', event => {
                if (!historySuggestDropdown?.classList.contains('show')) return;
                if (!isPointerInsideSuggestArea(event)) closeSuggestDropdown();
            });

            if (historySearchClear) {
                historySearchClear.addEventListener('click', () => {
                    if (historySearchTimer) window.clearTimeout(historySearchTimer);
                    historySearchInput.value = '';
                    updateHistoryRowsFilter();
                    closeSuggestDropdown();
                    if (normalizeHistoryKeyword(historySearchInitialValue) !== '') {
                        submitHistorySearchToServer('');
                    } else {
                        historySearchInput.focus();
                    }
                });
            }

            document.addEventListener('click', event => {
                if (!historySuggestDropdown) return;
                const inside = event.target.closest('.history-search-input');
                if (!inside) closeSuggestDropdown();
            });

            updateHistoryRowsFilter();

            // ====== Tab "Laporan Diterima": filter instan + saran (autocomplete) + cari ke server ======
            const receivedSearchInput = document.getElementById('received-search-input');

            if (receivedSearchInput) {
                const receivedSearchForm = document.getElementById('received-search-form');
                const receivedSearchClear = document.getElementById('received-search-clear');
                const receivedSearchCountBadge = document.getElementById('received-search-count');
                const receivedSearchCount = receivedSearchCountBadge?.querySelector('span');
                const receivedSearchEmpty = document.getElementById('received-search-empty');
                const receivedSuggestDropdown = document.getElementById('received-suggest-dropdown');
                const receivedSearchBox = receivedSearchInput.closest('.history-search-input');
                const receivedRows = Array.from(document.querySelectorAll('[data-received-row]'));
                const receivedPageStart = Number(receivedSearchInput.dataset.pageStart || 1);
                const receivedServerTotal = Number(receivedSearchCountBadge?.dataset.total || receivedRows.length);
                const receivedServerLabel = receivedSearchCountBadge?.dataset.label || 'laporan';
                const receivedInitialValue = receivedSearchInput.dataset.initialValue || '';
                const receivedSuggestUrl = receivedSearchInput.dataset.suggestUrl || '';
                const receivedDebounceMs = 250;
                const receivedMinLength = 2;
                let receivedTimer = null;
                let receivedController = null;
                let receivedItems = [];
                let receivedActiveIndex = -1;

                function updateReceivedRowsFilter() {
                    const keyword = normalizeHistoryKeyword(receivedSearchInput.value);
                    const initialKeyword = normalizeHistoryKeyword(receivedInitialValue);
                    let visibleCount = 0;

                    receivedRows.forEach(row => {
                        const target = normalizeHistoryKeyword(row.dataset.receivedSearch || row.textContent);
                        const isMatch = keyword === '' || target.includes(keyword);
                        row.classList.toggle('d-none', !isMatch);

                        if (isMatch) {
                            visibleCount += 1;
                            const numberCell = row.querySelector('.nomor');
                            if (numberCell) numberCell.textContent = receivedPageStart + visibleCount - 1;
                        }
                    });

                    if (receivedSearchEmpty) {
                        receivedSearchEmpty.classList.toggle('d-none', keyword === '' || visibleCount > 0);
                    }

                    if (receivedSearchClear) {
                        receivedSearchClear.classList.toggle('d-none', keyword === '');
                    }

                    if (receivedSearchCount) {
                        receivedSearchCount.textContent = (keyword === '' || keyword === initialKeyword)
                            ? `${receivedServerTotal} ${receivedServerLabel}`
                            : `${visibleCount} dari ${receivedRows.length} di halaman ini`;
                    }
                }

                function closeReceivedDropdown() {
                    if (receivedTimer) { window.clearTimeout(receivedTimer); receivedTimer = null; }
                    if (receivedController) { receivedController.abort(); receivedController = null; }
                    if (!receivedSuggestDropdown) return;
                    receivedSuggestDropdown.classList.remove('show');
                    receivedSuggestDropdown.innerHTML = '';
                    receivedItems = [];
                    receivedActiveIndex = -1;
                    receivedSearchInput.setAttribute('aria-expanded', 'false');
                }

                function renderReceivedState(html) {
                    if (!receivedSuggestDropdown) return;
                    receivedSuggestDropdown.innerHTML = html;
                    receivedSuggestDropdown.classList.add('show');
                    receivedSearchInput.setAttribute('aria-expanded', 'true');
                }

                function renderReceivedItems(payload) {
                    if (!receivedSuggestDropdown) return;
                    receivedItems = Array.isArray(payload?.items) ? payload.items : [];
                    receivedActiveIndex = receivedItems.length > 0 ? 0 : -1;

                    if (receivedItems.length === 0) {
                        renderReceivedState(`<div class="history-suggest-empty">Tidak ada laporan yang cocok. Coba kata kunci lain seperti tanggal, ID, atau nama kapal.</div>`);
                        return;
                    }

                    const header = `<div class="history-suggest-header">${receivedItems.length} saran teratas</div>`;
                    const list = receivedItems.map((item, index) => {
                        const statusClass = `status-${escapeHtml(item.status_class || 'submit')}`;
                        const shiftClass = `shift-${escapeHtml(item.shift_class || 'pagi')}`;
                        return `
                            <button type="button" class="history-suggest-item${index === 0 ? ' is-active' : ''}" data-index="${index}">
                                <div class="history-suggest-item-title">
                                    <span>${escapeHtml(item.title)} · ${escapeHtml(item.report_date)}</span>
                                    <span class="history-suggest-item-id">${escapeHtml(item.document_id)}</span>
                                </div>
                                <div class="history-suggest-item-meta">
                                    <span class="chip ${shiftClass}">${escapeHtml(item.shift_label)}</span>
                                    <span class="chip">Regu ${escapeHtml(item.group_from)} → ${escapeHtml(item.group_to)}</span>
                                    <span class="chip ${statusClass}">${escapeHtml(item.status_label)}</span>
                                    <span>Terakhir diedit ${escapeHtml(item.updated_diff)}</span>
                                </div>
                            </button>
                        `;
                    }).join('');

                    renderReceivedState(header + list);
                }

                function setActiveReceived(index) {
                    if (!receivedSuggestDropdown) return;
                    const nodes = receivedSuggestDropdown.querySelectorAll('.history-suggest-item');
                    if (!nodes.length) return;
                    receivedActiveIndex = ((index % nodes.length) + nodes.length) % nodes.length;
                    nodes.forEach((node, i) => node.classList.toggle('is-active', i === receivedActiveIndex));
                    nodes[receivedActiveIndex]?.scrollIntoView({ block: 'nearest' });
                }

                async function fetchReceivedSuggestions(keyword) {
                    if (!receivedSuggestUrl || !receivedSuggestDropdown) return;
                    if (receivedController) receivedController.abort();
                    receivedController = new AbortController();
                    renderReceivedState(`<div class="history-suggest-loading">Memuat saran...</div>`);

                    try {
                        const url = new URL(receivedSuggestUrl, window.location.origin);
                        url.searchParams.set('q', keyword);
                        const response = await fetch(url.toString(), {
                            signal: receivedController.signal,
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                            credentials: 'same-origin',
                        });
                        if (!response.ok) throw new Error('request failed');
                        renderReceivedItems(await response.json());
                    } catch (error) {
                        if (error.name === 'AbortError') return;
                        renderReceivedState(`<div class="history-suggest-empty">Tidak bisa memuat saran. Coba lagi.</div>`);
                    }
                }

                function openReceivedDropdownFromSearch() {
                    if (!receivedSuggestDropdown) return;
                    const keyword = receivedSearchInput.value.trim();
                    if (normalizeHistoryKeyword(keyword).length >= receivedMinLength) fetchReceivedSuggestions(keyword);
                }

                function isPointerInsideReceivedArea(event) {
                    if (!receivedSearchBox || !receivedSuggestDropdown?.classList.contains('show')) return false;
                    const searchRect = receivedSearchBox.getBoundingClientRect();
                    const dropdownRect = receivedSuggestDropdown.getBoundingClientRect();
                    const safeGap = 10;
                    const left = Math.min(searchRect.left, dropdownRect.left) - safeGap;
                    const right = Math.max(searchRect.right, dropdownRect.right) + safeGap;
                    const top = Math.min(searchRect.top, dropdownRect.top) - safeGap;
                    const bottom = Math.max(searchRect.bottom, dropdownRect.bottom) + safeGap;
                    return event.clientX >= left && event.clientX <= right && event.clientY >= top && event.clientY <= bottom;
                }

                function submitReceivedSearch(keyword) {
                    if (!receivedSearchForm) return;
                    const url = new URL(receivedSearchForm.action, window.location.origin);
                    url.searchParams.set('tab', 'diterima');
                    url.searchParams.delete('received_page');

                    const normalized = String(keyword || '').trim();
                    if (normalized !== '') {
                        url.searchParams.set('received_search', normalized);
                    } else {
                        url.searchParams.delete('received_search');
                    }

                    window.location.assign(url.toString());
                }

                function scheduleReceivedSearch() {
                    if (receivedTimer) window.clearTimeout(receivedTimer);
                    updateReceivedRowsFilter();
                    const keyword = receivedSearchInput.value.trim();
                    if (keyword === '') { closeReceivedDropdown(); return; }
                    if (receivedSuggestDropdown && normalizeHistoryKeyword(keyword).length >= receivedMinLength) {
                        receivedTimer = window.setTimeout(() => fetchReceivedSuggestions(keyword), receivedDebounceMs);
                    } else {
                        closeReceivedDropdown();
                    }
                }

                if (receivedSearchForm) {
                    receivedSearchForm.addEventListener('submit', () => {
                        if (receivedTimer) window.clearTimeout(receivedTimer);
                        closeReceivedDropdown();
                    });
                }

                receivedSearchInput.addEventListener('input', scheduleReceivedSearch);
                receivedSearchInput.addEventListener('focus', openReceivedDropdownFromSearch);
                receivedSearchInput.addEventListener('keydown', event => {
                    if (event.key === 'Escape') {
                        if (receivedTimer) window.clearTimeout(receivedTimer);
                        receivedSearchInput.value = '';
                        updateReceivedRowsFilter();
                        closeReceivedDropdown();
                        if (normalizeHistoryKeyword(receivedInitialValue) !== '') submitReceivedSearch('');
                        return;
                    }
                    if (event.key === 'ArrowDown' && receivedItems.length) {
                        event.preventDefault();
                        setActiveReceived(receivedActiveIndex + 1);
                    } else if (event.key === 'ArrowUp' && receivedItems.length) {
                        event.preventDefault();
                        setActiveReceived(receivedActiveIndex - 1);
                    }
                });

                if (receivedSearchBox) {
                    receivedSearchBox.addEventListener('click', event => {
                        const item = event.target.closest('.history-suggest-item');
                        if (item) {
                            if (receivedTimer) window.clearTimeout(receivedTimer);
                            const index = Number(item.dataset.index || -1);
                            const keyword = String(receivedItems[index]?.document_id || receivedSearchInput.value || '').trim();
                            receivedSearchInput.value = keyword;
                            submitReceivedSearch(keyword);
                            return;
                        }
                        if (!event.target.closest('.history-suggest-dropdown')) openReceivedDropdownFromSearch();
                    });
                }

                document.addEventListener('mousemove', event => {
                    if (!receivedSuggestDropdown?.classList.contains('show')) return;
                    if (!isPointerInsideReceivedArea(event)) closeReceivedDropdown();
                });

                document.addEventListener('click', event => {
                    if (!receivedSuggestDropdown) return;
                    if (!event.target.closest('.history-search-input')) closeReceivedDropdown();
                });

                updateReceivedRowsFilter();
            }
        });
    </script>
@endpush
