<?php

namespace App\Http\Controllers\Concerns;

use App\Enums\MaintenanceStatus;
use App\Enums\ReportStatus;
use App\Models\DailyReport;
use App\Models\MaintenanceReport;
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
        $rows = $this->buildDivisionArchiveRows($filters, $context);

        $rows = $this->sortArchiveRows($rows, $filters['sort'] ?? 'newest')->values();
        $total = $rows->count();
        $items = $rows->slice(($page - 1) * $perPage, $perPage)
            ->values()
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

        return $this->sortArchiveRows($this->buildDivisionArchiveRows($filters, $context), 'newest')
            ->take(8)
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
        $today = Carbon::today();
        $now = Carbon::now();
        $operationStatuses = $this->archiveStatuses();
        $maintenanceStatuses = $this->maintenanceArchiveStatuses();

        return [
            'today' => DailyReport::whereIn('status', $operationStatuses)->whereDate('report_date', $today)->count()
                + MaintenanceReport::whereIn('status', $maintenanceStatuses)->whereDate('report_date', $today)->count(),
            'pending' => DailyReport::where('status', ReportStatus::Acknowledged)->count()
                + MaintenanceReport::where('status', MaintenanceStatus::Submitted)->count(),
            'month' => DailyReport::whereIn('status', $operationStatuses)->whereMonth('report_date', $now->month)->whereYear('report_date', $now->year)->count()
                + MaintenanceReport::whereIn('status', $maintenanceStatuses)->whereMonth('report_date', $now->month)->whereYear('report_date', $now->year)->count(),
            'total' => DailyReport::whereIn('status', $operationStatuses)->count()
                + MaintenanceReport::whereIn('status', $maintenanceStatuses)->count(),
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

    private function buildDivisionArchiveRows(array $filters, string $context): Collection
    {
        $division = strtolower((string) ($filters['selectedDivision'] ?? 'all'));
        $rows = collect();

        if (in_array($division, ['', 'all', 'operasional'], true)) {
            $rows = $rows->merge($this->operationalArchiveRows($filters, $context));
        }

        if (in_array($division, ['', 'all', 'pemeliharaan'], true)) {
            $rows = $rows->merge($this->maintenanceArchiveRows($filters, $context));
        }

        $keyword = $this->archiveNormalize((string) ($filters['archiveSearch'] ?? ''));
        if ($keyword !== '') {
            $rows = $rows->filter(fn (array $row): bool => str_contains($this->archiveNormalize($row['search'] ?? ''), $keyword));
        }

        return $rows->values();
    }

    private function operationalArchiveRows(array $filters, string $context): Collection
    {
        $query = DailyReport::query()
            ->with(['creator:id,name,username,group', 'approver:id,name'])
            ->whereIn('status', $this->archiveStatuses());

        if (filled($filters['archiveSearch'] ?? '')) {
            $query->with($this->reportRelations());
        }

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

        return $query->get()
            ->map(fn (DailyReport $report): array => $this->operationalArchiveRow($report, $context));
    }

    private function maintenanceArchiveRows(array $filters, string $context): Collection
    {
        $selectedGroup = strtoupper((string) ($filters['selectedGroup'] ?? 'ALL'));
        $selectedShift = strtolower((string) ($filters['selectedShift'] ?? 'all'));

        if (($selectedGroup !== '' && $selectedGroup !== 'ALL') || ($selectedShift !== '' && $selectedShift !== 'all')) {
            return collect();
        }

        $query = MaintenanceReport::query()
            ->with(['creator:id,name', 'approver:id,name', 'workItems.unit', 'unitConditions.unit', 'attendances'])
            ->whereIn('status', $this->maintenanceArchiveStatuses());

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

        return $query->get()
            ->map(fn (MaintenanceReport $report): array => $this->maintenanceArchiveRow($report, $context));
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

    private function sortArchiveRows(Collection $rows, string $sort): Collection
    {
        return $rows->sort(function (array $a, array $b) use ($sort): int {
            $left = [$a['sort_date'] ?? 0, $a['sort_updated'] ?? 0, $a['raw_id'] ?? 0];
            $right = [$b['sort_date'] ?? 0, $b['sort_updated'] ?? 0, $b['raw_id'] ?? 0];
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
