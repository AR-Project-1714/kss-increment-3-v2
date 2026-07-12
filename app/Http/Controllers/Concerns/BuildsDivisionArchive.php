<?php

namespace App\Http\Controllers\Concerns;

use App\Enums\MaintenanceStatus;
use App\Enums\ReportStatus;
use App\Enums\SafetyStatus;
use App\Models\DailyReport;
use App\Models\MaintenanceReport;
use App\Models\SafetyReport;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

trait BuildsDivisionArchive
{
    protected function buildDivisionArchivePaginator(Request $request, array $filters, string $context): LengthAwarePaginator
    {
        $perPage = 10;
        $page = LengthAwarePaginator::resolveCurrentPage();

        // Ambil hanya tuple ringan (kind, id, kunci sort) dari database — filter,
        // pencarian, dan urutan dikerjakan di SQL. Model lengkap hanya dimuat
        // untuk 10 baris halaman aktif.
        $refs = $this->archiveRowRefs($filters);
        $total = $refs->count();
        $pageRefs = $refs->slice(($page - 1) * $perPage, $perPage)->values();

        $items = $this->hydrateArchiveRows($pageRefs, $context)
            ->map(function (array $row, int $index) use ($page, $perPage): array {
                $row['no'] = (($page - 1) * $perPage) + $index + 1;

                return $row;
            });

        return new LengthAwarePaginator($items, $total, $perPage, $page, [
            'path' => $request->url(),
            'query' => $request->query(),
        ]);
    }

    protected function buildDivisionArchiveSuggestions(string $keyword, string $context): Collection
    {
        $filters = [
            'archiveSearch' => $keyword,
            'sort' => 'newest',
            'selectedDate' => null,
            'selectedGroup' => 'ALL',
            'selectedShift' => 'all',
            'selectedDivision' => 'all',
            'selectedStatus' => 'all',
        ];

        $refs = $this->archiveRowRefs($filters)->take(8)->values();

        return $this->hydrateArchiveRows($refs, $context)
            ->map(fn (array $row): array => [
                'id' => $row['raw_id'],
                'document_id' => $row['id'],
                'title' => $row['title'],
                'report_date' => $row['date'],
                'updated_diff' => $row['updated_diff'],
                'shift_label' => $row['shift_label'],
                'shift_class' => $row['shift'],
                'status_label' => $row['status_label'],
                'status_class' => $row['status'],
                'division_label' => $row['division_label'],
                'division_class' => $row['division_class'],
                'group_from' => $row['group_from'],
                'group_to' => $row['group_to'],
                'approver' => $row['approver'],
                'view_url' => $row['view_url'],
                'download_url' => $row['download_url'],
            ])
            ->values();
    }

    protected function archiveTotalCounts(): array
    {
        // Satu query agregat per tabel (bukan 4 COUNT terpisah per tabel).
        $operational = $this->archiveStatusDateCounts(
            DailyReport::query(),
            $this->archiveStatuses(),
            [ReportStatus::Acknowledged]
        );
        $maintenance = $this->archiveStatusDateCounts(
            MaintenanceReport::query(),
            $this->maintenanceArchiveStatuses(),
            [MaintenanceStatus::Submitted]
        );
        $safety = $this->archiveStatusDateCounts(
            SafetyReport::query(),
            $this->safetyArchiveStatuses(),
            [SafetyStatus::Submitted]
        );

        return [
            'today' => $operational['today'] + $maintenance['today'] + $safety['today'],
            'pending' => $operational['pending'] + $maintenance['pending'] + $safety['pending'],
            'month' => $operational['month'] + $maintenance['month'] + $safety['month'],
            'total' => $operational['total'] + $maintenance['total'] + $safety['total'],
        ];
    }

    /**
     * Hitung today/pending/month/total dalam satu query agregat kondisional.
     * Memakai perbandingan rentang tanggal (portabel MySQL & SQLite).
     */
    private function archiveStatusDateCounts(Builder $query, array $statuses, array $pendingStatuses): array
    {
        $statusValues = array_map(fn ($status) => $status->value, $statuses);
        $pendingValues = array_map(fn ($status) => $status->value, $pendingStatuses);

        $statusIn = implode(',', array_fill(0, count($statusValues), '?'));
        $pendingIn = implode(',', array_fill(0, count($pendingValues), '?'));

        $todayStart = Carbon::today()->toDateString();
        $todayEnd = Carbon::today()->addDay()->toDateString();
        $monthStart = Carbon::now()->startOfMonth()->toDateString();
        $monthEnd = Carbon::now()->startOfMonth()->addMonth()->toDateString();

        $row = $query->selectRaw(
            "SUM(CASE WHEN status IN ({$statusIn}) AND report_date >= ? AND report_date < ? THEN 1 ELSE 0 END) AS today_count,"
            ."SUM(CASE WHEN status IN ({$pendingIn}) THEN 1 ELSE 0 END) AS pending_count,"
            ."SUM(CASE WHEN status IN ({$statusIn}) AND report_date >= ? AND report_date < ? THEN 1 ELSE 0 END) AS month_count,"
            ."SUM(CASE WHEN status IN ({$statusIn}) THEN 1 ELSE 0 END) AS total_count",
            [
                ...$statusValues, $todayStart, $todayEnd,
                ...$pendingValues,
                ...$statusValues, $monthStart, $monthEnd,
                ...$statusValues,
            ]
        )->first();

        return [
            'today' => (int) ($row->today_count ?? 0),
            'pending' => (int) ($row->pending_count ?? 0),
            'month' => (int) ($row->month_count ?? 0),
            'total' => (int) ($row->total_count ?? 0),
        ];
    }

    protected function divisionMeta(string $division): array
    {
        return match ($division) {
            'pemeliharaan' => ['label' => 'Pemeliharaan', 'class' => 'pemeliharaan', 'icon' => 'fi fi-rr-tools'],
            'safety' => ['label' => 'Safety', 'class' => 'safety', 'icon' => 'fi fi-rr-shield-check'],
            default => ['label' => 'Operasional', 'class' => 'operasional', 'icon' => 'fi fi-rr-ship'],
        };
    }

    protected function maintenanceArchiveStatuses(): array
    {
        return [MaintenanceStatus::Submitted, MaintenanceStatus::Approved];
    }

    protected function safetyArchiveStatuses(): array
    {
        return [SafetyStatus::Submitted, SafetyStatus::Approved];
    }

    /**
     * Kumpulan tuple ringan (kind, id, kunci sort) untuk seluruh baris arsip yang
     * lolos filter — sudah terurut. Pengganti pemuatan semua model ke memori.
     */
    private function archiveRowRefs(array $filters): Collection
    {
        $division = strtolower((string) ($filters['selectedDivision'] ?? 'all'));
        $keyword = trim((string) ($filters['archiveSearch'] ?? ''));

        // Kata kunci berupa nama divisi/judul laporan ("pemeliharaan", "operasi",
        // "k3", dst.) dulunya cocok lewat blob teks. Perlakukan sebagai filter
        // divisi agar perilakunya tetap sama.
        $keywordDivisions = $this->archiveKeywordDivisions($keyword);
        if ($keywordDivisions !== []) {
            $keyword = '';
        }

        $includes = fn (string $kind): bool => in_array($division, ['', 'all', $kind], true)
            && ($keywordDivisions === [] || in_array($kind, $keywordDivisions, true));

        $refs = collect();

        if ($includes('operasional')) {
            $refs = $refs->merge($this->operationalArchiveRefs($filters, $keyword));
        }

        if ($includes('pemeliharaan')) {
            $refs = $refs->merge($this->maintenanceArchiveRefs($filters, $keyword));
        }

        if ($includes('safety')) {
            $refs = $refs->merge($this->safetyArchiveRefs($filters, $keyword));
        }

        return $this->sortArchiveRefs($refs, ($filters['sort'] ?? 'newest') === 'oldest' ? 'oldest' : 'newest')->values();
    }

    /**
     * Muat model lengkap hanya untuk baris pada halaman aktif, urut sesuai refs.
     */
    private function hydrateArchiveRows(Collection $refs, string $context): Collection
    {
        $idsByKind = $refs->groupBy('kind')->map(fn (Collection $group) => $group->pluck('id')->all());

        $models = [
            'operasional' => filled($idsByKind['operasional'] ?? null)
                ? DailyReport::with(['creator:id,name,username,group', 'approver:id,name'])
                    ->whereIn('id', $idsByKind['operasional'])->get()->keyBy('id')
                : collect(),
            'pemeliharaan' => filled($idsByKind['pemeliharaan'] ?? null)
                ? MaintenanceReport::with(['creator:id,name', 'approver:id,name', 'workItems.unit', 'unitConditions.unit', 'attendances'])
                    ->whereIn('id', $idsByKind['pemeliharaan'])->get()->keyBy('id')
                : collect(),
            'safety' => filled($idsByKind['safety'] ?? null)
                ? SafetyReport::with($this->safetyReportRelations())
                    ->whereIn('id', $idsByKind['safety'])->get()->keyBy('id')
                : collect(),
        ];

        return $refs
            ->map(function (array $ref) use ($models, $context): ?array {
                $report = $models[$ref['kind']][$ref['id']] ?? null;

                if ($report === null) {
                    return null;
                }

                return match ($ref['kind']) {
                    'pemeliharaan' => $this->maintenanceArchiveRow($report, $context),
                    'safety' => $this->safetyArchiveRow($report, $context),
                    default => $this->operationalArchiveRow($report, $context),
                };
            })
            ->filter()
            ->values();
    }

    private function operationalArchiveRefs(array $filters, string $keyword): Collection
    {
        $query = DailyReport::query()->whereIn('status', $this->archiveStatuses());

        if ($filters['selectedDate'] ?? null) {
            $query->whereDate('report_date', $filters['selectedDate']);
        }

        $selectedGroup = strtoupper((string) ($filters['selectedGroup'] ?? 'ALL'));
        if ($selectedGroup !== '' && $selectedGroup !== 'ALL') {
            $query->where('group_name', $selectedGroup);
        }

        $selectedShift = strtolower((string) ($filters['selectedShift'] ?? 'all'));
        if ($selectedShift !== '' && $selectedShift !== 'all') {
            $shiftValues = $this->shiftSearchValues($selectedShift);
            if ($shiftValues !== []) {
                $query->where(function (Builder $shiftQuery) use ($shiftValues): void {
                    foreach ($shiftValues as $value) {
                        $shiftQuery->orWhereRaw('LOWER(shift) = ?', [$value]);
                    }
                });
            }
        }

        $selectedStatus = strtolower((string) ($filters['selectedStatus'] ?? 'all'));
        if ($selectedStatus !== '' && $selectedStatus !== 'all') {
            $statusFilter = ReportStatus::tryFrom($selectedStatus);

            if ($statusFilter !== null && in_array($statusFilter, $this->archiveStatuses(), true)) {
                $query->where('status', $statusFilter);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        $this->applyOperationalArchiveSearch($query, $keyword);

        return $query->get(['id', 'report_date', 'created_at', 'updated_at'])
            ->map(fn (DailyReport $report): array => $this->archiveRef('operasional', $report));
    }

    private function maintenanceArchiveRefs(array $filters, string $keyword): Collection
    {
        $selectedGroup = strtoupper((string) ($filters['selectedGroup'] ?? 'ALL'));
        $selectedShift = strtolower((string) ($filters['selectedShift'] ?? 'all'));

        if (($selectedGroup !== '' && $selectedGroup !== 'ALL') || ($selectedShift !== '' && $selectedShift !== 'all')) {
            return collect();
        }

        $query = MaintenanceReport::query()->whereIn('status', $this->maintenanceArchiveStatuses());

        if ($filters['selectedDate'] ?? null) {
            $query->whereDate('report_date', $filters['selectedDate']);
        }

        $selectedStatus = strtolower((string) ($filters['selectedStatus'] ?? 'all'));
        if ($selectedStatus !== '' && $selectedStatus !== 'all') {
            $statusFilter = MaintenanceStatus::tryFrom($selectedStatus);

            if ($statusFilter !== null && in_array($statusFilter, $this->maintenanceArchiveStatuses(), true)) {
                $query->where('status', $statusFilter);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        $this->applyMaintenanceArchiveSearch($query, $keyword);

        return $query->get(['id', 'report_date', 'created_at', 'updated_at'])
            ->map(fn (MaintenanceReport $report): array => $this->archiveRef('pemeliharaan', $report));
    }

    private function safetyArchiveRefs(array $filters, string $keyword): Collection
    {
        $selectedGroup = strtoupper((string) ($filters['selectedGroup'] ?? 'ALL'));
        $selectedShift = strtolower((string) ($filters['selectedShift'] ?? 'all'));

        if (($selectedGroup !== '' && $selectedGroup !== 'ALL') || ($selectedShift !== '' && $selectedShift !== 'all')) {
            return collect();
        }

        $query = SafetyReport::query()->whereIn('status', $this->safetyArchiveStatuses());

        if ($filters['selectedDate'] ?? null) {
            $query->whereDate('report_date', $filters['selectedDate']);
        }

        $selectedStatus = strtolower((string) ($filters['selectedStatus'] ?? 'all'));
        if ($selectedStatus !== '' && $selectedStatus !== 'all') {
            $statusFilter = SafetyStatus::tryFrom($selectedStatus);

            if ($statusFilter !== null && in_array($statusFilter, $this->safetyArchiveStatuses(), true)) {
                $query->where('status', $statusFilter);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        $this->applySafetyArchiveSearch($query, $keyword);

        return $query->get(['id', 'report_date', 'created_at', 'updated_at'])
            ->map(fn (SafetyReport $report): array => $this->archiveRef('safety', $report));
    }

    private function archiveRef(string $kind, $report): array
    {
        return [
            'kind' => $kind,
            'id' => $report->id,
            'sort_date' => $this->archiveTimestamp($report->report_date ?: $report->created_at),
            'sort_updated' => $this->archiveTimestamp($report->updated_at),
        ];
    }

    /**
     * Pencarian arsip operasional di SQL: memakai pencarian laporan standar plus
     * pencocokan label status Indonesia ("diterima", "diarsipkan", dst.).
     */
    private function applyOperationalArchiveSearch(Builder $query, string $keyword): void
    {
        if ($keyword === '') {
            return;
        }

        $statusValues = $this->archiveStatusValuesForKeyword($keyword, [
            ReportStatus::Submitted->value => 'diserahkan',
            ReportStatus::Acknowledged->value => 'diterima',
            ReportStatus::Approved->value => 'diarsipkan',
        ]);

        $query->where(function (Builder $outer) use ($keyword, $statusValues): void {
            $outer->where(function (Builder $inner) use ($keyword): void {
                $this->applyReportSearch($inner, $keyword, true);
            });

            if ($statusValues !== []) {
                $outer->orWhereIn('status', $statusValues);
            }
        });
    }

    private function applyMaintenanceArchiveSearch(Builder $query, string $keyword): void
    {
        if ($keyword === '') {
            return;
        }

        $like = '%'.$keyword.'%';
        $datePatterns = $this->buildDateSearchPatterns($keyword);

        if (! empty($datePatterns)) {
            $query->where(function (Builder $dateQuery) use ($datePatterns): void {
                foreach ($datePatterns as $pattern) {
                    $dateQuery->orWhere('report_date', 'like', $pattern);
                }
            });

            return;
        }

        $statusValues = $this->archiveStatusValuesForKeyword($keyword, [
            MaintenanceStatus::Submitted->value => 'diserahkan',
            MaintenanceStatus::Approved->value => 'diarsipkan',
        ]);

        $query->where(function (Builder $searchQuery) use ($keyword, $like, $statusValues): void {
            $this->whereColumnsLike($searchQuery, ['day_name', 'karu_pemeliharaan_name', 'karu_peralatan_name'], $like);

            if (preg_match('/mnt[-\s]?\d{4}[-\s]?(\d+)/i', $keyword, $match)) {
                $searchQuery->orWhere('id', (int) $match[1]);
            } elseif (ctype_digit($keyword)) {
                $searchQuery->orWhere('id', (int) $keyword);
            }

            if ($statusValues !== []) {
                $searchQuery->orWhereIn('status', $statusValues);
            }

            $searchQuery
                ->orWhere('report_date', 'like', $like)
                ->orWhereHas('creator', fn ($relation) => $this->whereColumnsLike($relation, ['name', 'username'], $like))
                ->orWhereHas('approver', fn ($relation) => $this->whereColumnsLike($relation, ['name', 'username'], $like))
                ->orWhereHas('workItems', function ($relation) use ($like): void {
                    $relation->where(function ($workItem) use ($like): void {
                        $this->whereColumnsLike($workItem, ['work_type', 'work_group', 'unit_label', 'description', 'assignee', 'notes'], $like);
                        $workItem->orWhereHas('unit', fn ($unit) => $this->whereColumnsLike($unit, ['name', 'unit_code', 'unit_number'], $like));
                    });
                })
                ->orWhereHas('unitConditions', function ($relation) use ($like): void {
                    $relation->where(function ($condition) use ($like): void {
                        $this->whereColumnsLike($condition, ['condition', 'notes'], $like);
                        $condition->orWhereHas('unit', fn ($unit) => $this->whereColumnsLike($unit, ['name', 'unit_code', 'unit_number'], $like));
                    });
                })
                ->orWhereHas('attendances', fn ($relation) => $this->whereColumnsLike($relation, ['employee_name', 'position', 'notes'], $like));
        });
    }

    private function applySafetyArchiveSearch(Builder $query, string $keyword): void
    {
        if ($keyword === '') {
            return;
        }

        $like = '%'.$keyword.'%';
        $datePatterns = $this->buildDateSearchPatterns($keyword);

        if (! empty($datePatterns)) {
            $query->where(function (Builder $dateQuery) use ($datePatterns): void {
                foreach ($datePatterns as $pattern) {
                    $dateQuery->orWhere('report_date', 'like', $pattern);
                }
            });

            return;
        }

        $statusValues = $this->archiveStatusValuesForKeyword($keyword, [
            SafetyStatus::Submitted->value => 'diserahkan',
            SafetyStatus::Approved->value => 'diarsipkan',
        ]);

        $query->where(function (Builder $searchQuery) use ($keyword, $like, $statusValues): void {
            $this->whereColumnsLike($searchQuery, ['document_number', 'time_range', 'shift'], $like);

            if (preg_match('/k3[-\s]?\d{4}[-\s]?(\d+)/i', $keyword, $match)) {
                $searchQuery->orWhere('id', (int) $match[1]);
            } elseif (ctype_digit($keyword)) {
                $searchQuery->orWhere('id', (int) $keyword);
            }

            if ($statusValues !== []) {
                $searchQuery->orWhereIn('status', $statusValues);
            }

            $searchQuery
                ->orWhere('report_date', 'like', $like)
                ->orWhereHas('creator', fn ($relation) => $this->whereColumnsLike($relation, ['name', 'username'], $like))
                ->orWhereHas('approver', fn ($relation) => $this->whereColumnsLike($relation, ['name', 'username'], $like))
                ->orWhereHas('inspections', fn ($relation) => $this->whereColumnsLike($relation, ['location_name_snapshot', 'item_name_snapshot', 'condition', 'recommendation'], $like))
                ->orWhereHas('operationLogs', fn ($relation) => $this->whereColumnsLike($relation, ['activity_name', 'condition', 'action', 'notes'], $like))
                ->orWhereHas('incidentLogs', fn ($relation) => $this->whereColumnsLike($relation, ['description', 'condition', 'action', 'notes'], $like));
        });
    }

    /**
     * Divisi yang judul/nama-nya memuat kata kunci — meniru perilaku blob lama
     * saat pengguna mengetik "pemeliharaan", "operasi", "k3", "laporan", dsb.
     */
    private function archiveKeywordDivisions(string $keyword): array
    {
        $normalized = ltrim($this->archiveNormalize($keyword), '#');

        if ($normalized === '' || strlen($normalized) < 2) {
            return [];
        }

        // Prefiks nomor dokumen ("ops", "mnt", "k3", boleh diikuti sebagian tahun,
        // mis. "ops-2026") berarti pengguna mencari laporan divisi tersebut. ID
        // lengkap ("ops-2026-12") tidak lewat sini — ditangani pencarian SQL.
        if (preg_match('/^(ops|mnt|k3)(?:[-\s]?\d{1,4})?$/', $normalized, $match)) {
            return [match ($match[1]) {
                'ops' => 'operasional',
                'mnt' => 'pemeliharaan',
                default => 'safety',
            }];
        }

        $terms = [
            'operasional' => ['operasional', 'laporan operasi harian'],
            'pemeliharaan' => ['pemeliharaan', 'laporan pemeliharaan harian'],
            'safety' => ['safety', 'k3', 'laporan k3 safety'],
        ];

        $matches = [];

        foreach ($terms as $division => $phrases) {
            foreach ($phrases as $phrase) {
                // Cocok bila kata kunci merupakan awal frasa ("laporan opera...")
                // atau awal salah satu katanya ("pemel", "opera", "k3"). Prefiks
                // saja — substring bebas membuat kata pendek seperti "me" (bulan
                // Mei) salah tangkap sebagai "pe-ME-liharaan".
                $words = explode(' ', $phrase);

                if (str_starts_with($phrase, $normalized)
                    || array_filter($words, fn (string $word): bool => str_starts_with($word, $normalized)) !== []) {
                    $matches[] = $division;
                    break;
                }
            }
        }

        return $matches;
    }

    private function archiveStatusValuesForKeyword(string $keyword, array $labelsByStatus): array
    {
        $normalized = $this->archiveNormalize($keyword);

        if (strlen($normalized) < 4) {
            return [];
        }

        return array_keys(array_filter(
            $labelsByStatus,
            fn (string $label): bool => str_contains($label, $normalized)
        ));
    }

    private function operationalArchiveRow(DailyReport $report, string $context): array
    {
        $shift = $this->shiftMeta($report->shift);
        $status = $this->statusMeta($report->status);
        $date = $report->report_date ?: $report->created_at;
        $division = $this->divisionMeta('operasional');
        $documentId = $this->documentId($report);

        return [
            'kind' => 'operasional',
            'key' => 'ops-'.$report->id,
            'raw_id' => $report->id,
            'title' => 'Laporan Operasi Harian',
            'id' => $documentId,
            'date' => $this->archiveDateLabel($date),
            'sort_date' => $this->archiveTimestamp($date),
            'sort_updated' => $this->archiveTimestamp($report->updated_at),
            'regu' => $this->archiveDisplayGroup($report->group_name),
            'shift' => $shift['class'],
            'shift_label' => $shift['label'],
            'shift_icon' => $shift['icon'],
            'status' => $status['class'],
            'status_label' => $status['label'],
            'division' => 'operasional',
            'division_label' => $division['label'],
            'division_class' => $division['class'],
            'division_icon' => $division['icon'],
            'summary' => $documentId.' - '.$this->archiveDateLabel($date),
            'view_url' => route($context === 'admin' ? 'admin.reports.show' : 'manajer.reports.show', $report),
            'download_url' => route($context === 'admin' ? 'admin.reports.download' : 'manajer.reports.download', $report),
            'destroy_url' => route($context === 'admin' ? 'admin.reports.destroy' : 'manajer.reports.destroy', $report),
            'updated_diff' => $report->updated_at ? Carbon::parse($report->updated_at)->locale('id')->diffForHumans() : '-',
            'group_from' => strtoupper((string) $report->group_name) ?: '-',
            'group_to' => strtoupper((string) $report->received_by_group) ?: '-',
            'approver' => $report->approver?->name ?? '-',
            'search' => $this->archiveSearchBlob([
                'Operasional',
                'Laporan Operasi Harian',
                $documentId,
                $report->report_date?->format('Y-m-d'),
                $this->archiveDateLabel($date),
                $shift['label'],
                $status['label'],
                $this->archiveDisplayGroup($report->group_name),
                'Regu '.strtoupper((string) $report->received_by_group),
                $report->creator?->name,
                $report->approver?->name,
                ...$this->archiveFlattenSearchable($report),
            ]),
        ];
    }

    private function maintenanceArchiveRow(MaintenanceReport $report, string $context): array
    {
        $status = $this->maintenanceStatusMeta($report->status);
        $date = $report->report_date ?: $report->created_at;
        $division = $this->divisionMeta('pemeliharaan');
        $documentId = $this->maintenanceDocumentId($report);

        return [
            'kind' => 'pemeliharaan',
            'key' => 'pml-'.$report->id,
            'raw_id' => $report->id,
            'title' => 'Laporan Pemeliharaan Harian',
            'id' => $documentId,
            'date' => $this->archiveDateLabel($date),
            'sort_date' => $this->archiveTimestamp($date),
            'sort_updated' => $this->archiveTimestamp($report->updated_at),
            'regu' => '-',
            'shift' => 'nonshift',
            'shift_label' => 'Non Shift',
            'shift_icon' => 'fi fi-rr-calendar-clock',
            'status' => $status['class'],
            'status_label' => $status['label'],
            'division' => 'pemeliharaan',
            'division_label' => $division['label'],
            'division_class' => $division['class'],
            'division_icon' => $division['icon'],
            'summary' => $documentId.' - '.$this->archiveDateLabel($date),
            'view_url' => route($context === 'admin' ? 'admin.maintenance-reports.show' : 'manajer.pemeliharaan.show', $report),
            'download_url' => route($context === 'admin' ? 'admin.maintenance-reports.download' : 'manajer.pemeliharaan.download', $report),
            'destroy_url' => route($context === 'admin' ? 'admin.maintenance-reports.destroy' : 'manajer.pemeliharaan.destroy', $report),
            'updated_diff' => $report->updated_at ? Carbon::parse($report->updated_at)->locale('id')->diffForHumans() : '-',
            'group_from' => '-',
            'group_to' => '-',
            'approver' => $report->approver?->name ?? '-',
            'search' => $this->archiveSearchBlob([
                'Pemeliharaan',
                'Laporan Pemeliharaan Harian',
                $documentId,
                $report->report_date?->format('Y-m-d'),
                $this->archiveDateLabel($date),
                'Non Shift',
                $status['label'],
                $report->creator?->name,
                $report->approver?->name,
                ...$this->archiveFlattenSearchable($report),
            ]),
        ];
    }

    private function safetyArchiveRow(SafetyReport $report, string $context): array
    {
        $status = $this->safetyStatusMeta($report->status);
        $date = $report->report_date ?: $report->created_at;
        $division = $this->divisionMeta('safety');
        $documentId = $this->safetyDocumentId($report);

        return [
            'kind' => 'safety',
            'key' => 'safety-'.$report->id,
            'raw_id' => $report->id,
            'title' => 'Laporan K3 / Safety',
            'id' => $documentId,
            'date' => $this->archiveDateLabel($date),
            'sort_date' => $this->archiveTimestamp($date),
            'sort_updated' => $this->archiveTimestamp($report->updated_at),
            'regu' => '-',
            'shift' => 'nonshift',
            'shift_label' => 'Non Shift',
            'shift_icon' => 'fi fi-rr-calendar-clock',
            'status' => $status['class'],
            'status_label' => $status['label'],
            'division' => 'safety',
            'division_label' => $division['label'],
            'division_class' => $division['class'],
            'division_icon' => $division['icon'],
            'summary' => $documentId.' - '.$this->archiveDateLabel($date),
            'view_url' => route($context === 'admin' ? 'admin.safety-reports.show' : 'manajer.safety.show', $report),
            'download_url' => route($context === 'admin' ? 'admin.safety-reports.download' : 'manajer.safety.download', $report),
            'destroy_url' => route($context === 'admin' ? 'admin.safety-reports.destroy' : 'manajer.safety.destroy', $report),
            'updated_diff' => $report->updated_at ? Carbon::parse($report->updated_at)->locale('id')->diffForHumans() : '-',
            'group_from' => '-',
            'group_to' => '-',
            'approver' => $report->approver?->name ?? '-',
            'search' => $this->archiveSearchBlob([
                'Safety',
                'K3',
                'Laporan K3 Safety',
                $documentId,
                $report->report_date?->format('Y-m-d'),
                $this->archiveDateLabel($date),
                'Non Shift',
                $status['label'],
                $report->creator?->name,
                $report->approver?->name,
                ...$this->archiveFlattenSearchable($report),
            ]),
        ];
    }

    private function sortArchiveRefs(Collection $refs, string $sort): Collection
    {
        return $refs->sort(function (array $a, array $b) use ($sort): int {
            $left = [$a['sort_date'] ?? 0, $a['sort_updated'] ?? 0, $a['id'] ?? 0];
            $right = [$b['sort_date'] ?? 0, $b['sort_updated'] ?? 0, $b['id'] ?? 0];
            $compare = $left <=> $right;

            return $sort === 'oldest' ? $compare : -$compare;
        });
    }

    private function archiveDisplayGroup(?string $group): string
    {
        $value = trim((string) $group);
        $value = preg_replace('/^regu\s+/i', '', $value) ?? $value;
        $value = strtoupper($value);

        return $value === '' ? '-' : 'Regu '.$value;
    }

    private function archiveDateLabel(mixed $date): string
    {
        return $date ? Carbon::parse($date)->locale('id')->translatedFormat('d F Y') : '-';
    }

    private function archiveTimestamp(mixed $date): int
    {
        return $date ? Carbon::parse($date)->timestamp : 0;
    }

    private function archiveSearchBlob(array $parts): string
    {
        return Str::lower(
            collect($parts)
                ->filter(fn ($value) => filled($value))
                ->map(fn ($value) => trim(strip_tags((string) $value)))
                ->implode(' ')
        );
    }

    private function archiveNormalize(string $value): string
    {
        return Str::of($value)
            ->lower()
            ->ascii()
            ->trim()
            ->toString();
    }

    private function archiveFlattenSearchable(mixed $value): array
    {
        if ($value instanceof \Illuminate\Database\Eloquent\Model) {
            $attributes = $value->attributesToArray();

            foreach ($value->getRelations() as $relationName => $relationValue) {
                $attributes[$relationName] = $relationValue;
            }

            $value = $attributes;
        } elseif ($value instanceof Collection) {
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

                array_push($result, ...$this->archiveFlattenSearchable($item));
            }

            return $result;
        }

        return is_scalar($value) && filled($value) ? [(string) $value] : [];
    }
}
