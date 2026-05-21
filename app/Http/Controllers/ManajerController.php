<?php

namespace App\Http\Controllers;

use App\Models\DailyReport;
use App\Models\Role;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class ManajerController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeManagementAccess($request);

        $incomingReports = DailyReport::query()
            ->select($this->incomingReportColumns())
            ->where('status', 'acknowledged')
            ->latest('received_at')
            ->latest('updated_at')
            ->get();

        $divisionCounts = [
            'all' => $incomingReports->count(),
            'operasional' => $incomingReports->count(),
            'pemeliharaan' => 0,
            'safety' => 0,
        ];

        return view('manajer.index', [
            'stats' => $this->dashboardStats(),
            'incomingReports' => $incomingReports,
            'divisionCounts' => $divisionCounts,
        ]);
    }

    public function archive(Request $request)
    {
        $this->authorizeManagementAccess($request);

        $archiveSearch = trim((string) $request->input('q', ''));
        $sort = $request->input('sort', 'newest') === 'oldest' ? 'oldest' : 'newest';
        $selectedDate = $request->input('tanggal');
        $selectedGroup = strtoupper((string) $request->input('regu', 'all'));
        $selectedShift = strtolower((string) $request->input('shift', 'all'));

        $query = DailyReport::query()
            ->select($this->archiveListColumns())
            ->with('approver:id,name')
            ->whereIn('status', $this->archiveStatuses());

        $this->applyArchiveSearch($query, $archiveSearch);

        if ($selectedDate) {
            $query->whereDate('report_date', $selectedDate);
        }

        if ($selectedGroup !== '' && $selectedGroup !== 'ALL') {
            $query->where('group_name', $selectedGroup);
        }

        if ($selectedShift !== '' && $selectedShift !== 'all') {
            $shiftValues = $this->shiftSearchValues($selectedShift);

            if (! empty($shiftValues)) {
                $query->where(function ($shiftQuery) use ($shiftValues): void {
                    foreach ($shiftValues as $value) {
                        $shiftQuery->orWhereRaw('LOWER(shift) = ?', [$value]);
                    }
                });
            }
        }

        $reports = $query
            ->when($sort === 'oldest', fn ($builder) => $builder->oldest('report_date')->oldest('updated_at')->oldest('id'))
            ->when($sort === 'newest', fn ($builder) => $builder->latest('report_date')->latest('updated_at')->latest('id'))
            ->paginate(10)
            ->withQueryString();

        return view('manajer.archive', [
            'stats' => $this->archiveStats(),
            'reports' => $reports,
            'archiveSearch' => $archiveSearch,
            'sort' => $sort,
            'selectedDate' => $selectedDate,
            'selectedGroup' => $selectedGroup,
            'selectedShift' => $selectedShift,
        ]);
    }

    public function archiveSuggestions(Request $request)
    {
        $this->authorizeManagementAccess($request);

        $keyword = trim((string) $request->input('q', ''));

        $query = DailyReport::query()
            ->select($this->archiveSuggestionColumns())
            ->with('approver:id,name')
            ->whereIn('status', $this->archiveStatuses());

        $this->applyArchiveSearch($query, $keyword);

        $items = $query
            ->latest('report_date')
            ->latest('updated_at')
            ->limit(8)
            ->get()
            ->map(fn (DailyReport $report): array => $this->archiveSuggestionItem($report))
            ->values();

        return response()->json([
            'keyword' => $keyword,
            'total' => $items->count(),
            'items' => $items,
        ]);
    }

    public function approve(Request $request, DailyReport $report)
    {
        $this->authorizeManagementAccess($request);

        if ($report->status !== 'acknowledged') {
            return back()->with('error', 'Status laporan tidak valid untuk tanda tangan manajer.');
        }

        try {
            $report->update([
                'status' => 'approved',
                'approved_by' => $request->user()->id,
                'approved_at' => now(),
            ]);

            $this->forgetManagerStatsCache();
            $this->cacheApprovedPdf($report->fresh());
        } catch (Throwable $exception) {
            Log::error('Gagal menyetujui laporan dari dashboard manajer.', [
                'report_id' => $report->id,
                'user_id' => $request->user()?->id,
                'message' => $exception->getMessage(),
            ]);

            return back()->with('error', 'Laporan belum bisa ditanda tangani. Silakan coba lagi.');
        }

        return redirect()
            ->route('manajer.archive')
            ->with('success', 'Laporan berhasil ditanda tangani dan masuk ke arsip.');
    }

    public function show(Request $request, DailyReport $report)
    {
        $this->authorizeManagementAccess($request);
        abort_unless(in_array($report->status, $this->archiveStatuses(), true), 404);

        return view('report-ops.viewpdf', [
            'report' => $this->loadReport($report),
            'isPdf' => false,
        ]);
    }

    public function download(DailyReport $report)
    {
        $this->authorizeManagementAccess(request());
        abort_unless(in_array($report->status, $this->archiveStatuses(), true), 404);

        $path = storage_path('app/public/reports/report-'.$report->id.'.pdf');

        if (is_file($path)) {
            return response()->download($path, $this->reportFileName($report, 'pdf'));
        }

        if (class_exists(Pdf::class)) {
            $report = $this->loadReport($report);
            $pdf = Pdf::loadView('report-ops.pdf', [
                'report' => $report,
                'isPdf' => true,
            ]);
            $pdf->setPaper([0, 0, 612.00, 1008.00], 'portrait');
            $pdf->setOption('isRemoteEnabled', true);

            return $pdf->download($this->reportFileName($report, 'pdf'));
        }

        return view('report-ops.viewpdf', [
            'report' => $this->loadReport($report),
            'isPdf' => false,
        ]);
    }

    public function destroy(Request $request, DailyReport $report)
    {
        $this->authorizeManagementAccess($request);

        if (! in_array($report->status, $this->archiveStatuses(), true)) {
            return back()->with('error', 'Hanya laporan pada arsip yang bisa dihapus dari menu ini.');
        }

        $path = storage_path('app/public/reports/report-'.$report->id.'.pdf');

        if (is_file($path)) {
            @unlink($path);
        }

        $report->delete();
        $this->forgetManagerStatsCache();

        return back()->with('success', 'Laporan arsip berhasil dihapus.');
    }

    public function bantuan(Request $request)
    {
        $this->authorizeManagementAccess($request);

        return view('manajer.bantuan');
    }

    private function dashboardStats(): array
    {
        $activeStatuses = ['submitted', 'acknowledged', 'approved'];
        $today = Carbon::today();
        $now = Carbon::now();

        return Cache::remember($this->managerStatsCacheKey('dashboard'), now()->addSeconds(60), function () use ($activeStatuses, $today, $now): array {
            return [
                'todayReports' => DailyReport::whereIn('status', $activeStatuses)
                    ->whereDate('report_date', $today)
                    ->count(),
                'pendingReports' => DailyReport::where('status', 'acknowledged')->count(),
                'monthlyReports' => DailyReport::whereIn('status', $activeStatuses)
                    ->whereMonth('report_date', $now->month)
                    ->whereYear('report_date', $now->year)
                    ->count(),
                'totalReports' => DailyReport::whereIn('status', $activeStatuses)->count(),
            ];
        });
    }

    private function archiveStats(): array
    {
        $archiveStatuses = $this->archiveStatuses();
        $today = Carbon::today();
        $now = Carbon::now();

        return Cache::remember($this->managerStatsCacheKey('archive'), now()->addSeconds(60), function () use ($archiveStatuses, $today, $now): array {
            return [
                'todayReports' => DailyReport::whereIn('status', $archiveStatuses)
                    ->whereDate('report_date', $today)
                    ->count(),
                'pendingReports' => DailyReport::where('status', 'acknowledged')->count(),
                'monthlyReports' => DailyReport::whereIn('status', $archiveStatuses)
                    ->whereMonth('report_date', $now->month)
                    ->whereYear('report_date', $now->year)
                    ->count(),
                'totalReports' => DailyReport::whereIn('status', $archiveStatuses)->count(),
            ];
        });
    }

    private function archiveStatuses(): array
    {
        return ['submitted', 'acknowledged', 'approved'];
    }

    private function reportRelations(): array
    {
        return [
            'creator',
            'receiver',
            'approver',
            'loadingActivities.timesheets',
            'bulkLoadingActivities.logs',
            'materialActivity.items',
            'containerActivity.items',
            'turbaActivity.deliveries',
            'unitCheckLogs',
            'employeeLogs',
        ];
    }

    private function incomingReportColumns(): array
    {
        return [
            'id',
            'report_date',
            'shift',
            'group_name',
            'received_by_group',
            'received_at',
            'updated_at',
            'created_at',
            'status',
        ];
    }

    private function archiveListColumns(): array
    {
        return array_merge($this->incomingReportColumns(), [
            'approved_by',
            'approved_at',
            'payload',
        ]);
    }

    private function archiveSuggestionColumns(): array
    {
        return array_merge($this->incomingReportColumns(), [
            'approved_by',
            'approved_at',
        ]);
    }

    private function managerStatsCacheKey(string $scope): string
    {
        return sprintf('manajer.%s-stats.%s.%s', $scope, Carbon::today()->toDateString(), Carbon::now()->format('Y-m'));
    }

    private function forgetManagerStatsCache(): void
    {
        Cache::forget($this->managerStatsCacheKey('dashboard'));
        Cache::forget($this->managerStatsCacheKey('archive'));
    }

    private function loadReport(DailyReport $report): DailyReport
    {
        return $report->load($this->reportRelations());
    }

    private function cacheApprovedPdf(DailyReport $report): void
    {
        if (! class_exists(Pdf::class)) {
            return;
        }

        try {
            $storagePath = storage_path('app/public/reports');

            if (! is_dir($storagePath)) {
                mkdir($storagePath, 0755, true);
            }

            $pdf = Pdf::loadView('report-ops.pdf', [
                'report' => $this->loadReport($report),
                'isPdf' => true,
            ]);
            $pdf->setPaper([0, 0, 612.00, 1008.00], 'portrait');
            $pdf->setOption('isRemoteEnabled', true);
            $pdf->save($storagePath.'/report-'.$report->id.'.pdf');
        } catch (Throwable $exception) {
            Log::error('Gagal menyimpan PDF arsip laporan manajer.', [
                'report_id' => $report->id,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    private function archiveSuggestionItem(DailyReport $report): array
    {
        $shift = $this->shiftMeta($report->shift);
        $status = $this->statusMeta($report->status);
        $date = $report->report_date ?: $report->created_at;

        return [
            'id' => $report->id,
            'document_id' => $this->documentId($report),
            'title' => 'Laporan Shift Harian',
            'report_date' => $date ? Carbon::parse($date)->locale('id')->translatedFormat('d F Y') : '-',
            'updated_diff' => $report->updated_at ? Carbon::parse($report->updated_at)->locale('id')->diffForHumans() : '-',
            'shift_label' => $shift['label'],
            'shift_class' => $shift['class'],
            'status_label' => $status['label'],
            'status_class' => $status['class'],
            'group_from' => strtoupper((string) $report->group_name) ?: '-',
            'group_to' => strtoupper((string) $report->received_by_group) ?: '-',
            'approver' => $report->approver?->name ?? '-',
            'view_url' => route('manajer.reports.show', $report),
            'download_url' => route('manajer.reports.download', $report),
        ];
    }

    private function documentId(DailyReport $report): string
    {
        try {
            $date = $report->report_date ?: $report->created_at;
            $year = $date ? Carbon::parse($date)->format('Y') : now()->format('Y');
        } catch (Throwable) {
            $year = now()->format('Y');
        }

        return '#OPS-'.$year.'-'.str_pad((string) $report->id, 3, '0', STR_PAD_LEFT);
    }

    private function reportFileName(DailyReport $report, string $extension): string
    {
        $date = $report->report_date
            ? Carbon::parse($report->report_date)->format('Y-m-d')
            : now()->format('Y-m-d');
        $shift = $this->shiftMeta($report->shift)['short'];
        $group = strtoupper(trim((string) $report->group_name)) ?: '-';

        return "Laporan-Operasional-{$date}-Shift-{$shift}-Regu-{$group}.{$extension}";
    }

    private function shiftMeta(mixed $shift): array
    {
        $normalized = strtolower(trim((string) $shift));

        return match (true) {
            in_array($normalized, ['1', 'pagi', 'shift 1', 'shift pagi'], true) => ['label' => 'Shift Pagi', 'short' => 'Pagi', 'class' => 'pagi', 'icon' => 'fi fi-rr-sunrise'],
            in_array($normalized, ['2', 'sore', 'siang', 'shift 2', 'shift sore', 'shift siang'], true) => ['label' => 'Shift Sore', 'short' => 'Sore', 'class' => 'sore', 'icon' => 'fi fi-rr-sun'],
            in_array($normalized, ['3', 'malam', 'shift 3', 'shift malam'], true) => ['label' => 'Shift Malam', 'short' => 'Malam', 'class' => 'malam', 'icon' => 'fi fi-rr-moon-stars'],
            default => ['label' => $shift ? 'Shift '.$shift : 'Shift -', 'short' => $shift ? trim((string) $shift) : '-', 'class' => 'pagi', 'icon' => 'fi fi-rr-clock'],
        };
    }

    private function statusMeta(mixed $status): array
    {
        return match ((string) $status) {
            'submitted' => ['label' => 'Diserahkan', 'class' => 'submit'],
            'acknowledged', 'approved' => ['label' => 'Ditanda Tangani', 'class' => 'approve'],
            default => ['label' => ucfirst((string) $status), 'class' => 'submit'],
        };
    }

    private function shiftSearchValues(string $shift): array
    {
        return match ($shift) {
            'pagi' => ['1', 'pagi', 'shift 1', 'shift pagi'],
            'sore' => ['2', 'sore', 'siang', 'shift 2', 'shift sore', 'shift siang'],
            'malam' => ['3', 'malam', 'shift 3', 'shift malam'],
            default => [],
        };
    }

    private function applyArchiveSearch($query, string $keyword): void
    {
        if ($keyword === '') {
            return;
        }

        $like = '%'.$keyword.'%';
        $datePatterns = $this->buildDateSearchPatterns($keyword);

        if (! empty($datePatterns)) {
            $query->where(function ($dateQuery) use ($datePatterns): void {
                foreach ($datePatterns as $pattern) {
                    $dateQuery->orWhere('report_date', 'like', $pattern);
                }
            });

            return;
        }

        $query->where(function ($searchQuery) use ($keyword, $like): void {
            $this->whereColumnsLike($searchQuery, [
                'shift',
                'group_name',
                'received_by_group',
                'time_range',
                'status',
                'payload',
            ], $like);

            if (preg_match('/ops[-\s]?\d{4}[-\s]?(\d+)/i', $keyword, $match)) {
                $searchQuery->orWhere('id', (int) $match[1]);
            } elseif (ctype_digit($keyword)) {
                $searchQuery->orWhere('id', (int) $keyword);
            }

            $searchQuery
                ->orWhere('report_date', 'like', $like)
                ->orWhereHas('creator', fn ($relation) => $this->whereColumnsLike($relation, ['name', 'username', 'email', 'group'], $like))
                ->orWhereHas('receiver', fn ($relation) => $this->whereColumnsLike($relation, ['name', 'username', 'email', 'group'], $like))
                ->orWhereHas('approver', fn ($relation) => $this->whereColumnsLike($relation, ['name', 'username', 'email', 'group'], $like))
                ->orWhereHas('loadingActivities', function ($relation) use ($like): void {
                    $relation->where(function ($activity) use ($like): void {
                        $this->whereColumnsLike($activity, [
                            'ship_name',
                            'agent',
                            'jetty',
                            'destination',
                            'capacity',
                            'wo_number',
                            'cargo_type',
                            'marking',
                            'operating_gang',
                            'foreman',
                            'tally_warehouse',
                            'driver_name',
                            'truck_number',
                            'tally_ship',
                            'operator_ship',
                            'forklift_ship',
                            'operator_warehouse',
                            'forklift_warehouse',
                        ], $like);

                        $activity->orWhereHas('timesheets', fn ($timesheet) => $this->whereColumnsLike($timesheet, ['category', 'time', 'activity'], $like));
                    });
                })
                ->orWhereHas('bulkLoadingActivities', function ($relation) use ($like): void {
                    $relation->where(function ($activity) use ($like): void {
                        $this->whereColumnsLike($activity, [
                            'ship_name',
                            'jetty',
                            'destination',
                            'agent',
                            'stevedoring',
                            'commodity',
                            'capacity',
                            'berthing_time',
                            'start_loading_time',
                        ], $like);

                        $activity->orWhereHas('logs', fn ($log) => $this->whereColumnsLike($log, ['datetime', 'activity', 'cob'], $like));
                    });
                })
                ->orWhereHas('materialActivity', fn ($relation) => $this->whereColumnsLike($relation, [
                    'ship_name',
                    'agent',
                    'capacity',
                    'ship_tally_names',
                    'forklift_operator_names',
                    'delivery_tally_names',
                    'driver_names',
                    'working_hours',
                ], $like))
                ->orWhereHas('materialActivity.items', fn ($relation) => $this->whereColumnsLike($relation, ['raw_material_type', 'qty_current', 'qty_prev', 'qty_total'], $like))
                ->orWhereHas('containerActivity', fn ($relation) => $this->whereColumnsLike($relation, [
                    'ship_name',
                    'agent',
                    'capacity',
                    'ship_tally_names',
                    'gudang_tally_names',
                    'driver_names',
                ], $like))
                ->orWhereHas('containerActivity.items', fn ($relation) => $this->whereColumnsLike($relation, ['time', 'qty_current', 'qty_prev', 'qty_total', 'status'], $like))
                ->orWhereHas('turbaActivity', fn ($relation) => $this->whereColumnsLike($relation, [
                    'tally_gudang_names',
                    'forklift_operator_names',
                    'driver_names',
                    'working_hours',
                ], $like))
                ->orWhereHas('turbaActivity.deliveries', fn ($relation) => $this->whereColumnsLike($relation, [
                    'truck_name',
                    'do_so_number',
                    'capacity',
                    'marking_type',
                    'qty_current',
                    'qty_prev',
                    'qty_accumulated',
                ], $like))
                ->orWhereHas('unitCheckLogs', fn ($relation) => $this->whereColumnsLike($relation, [
                    'category',
                    'item_name',
                    'master_id',
                    'fuel_level',
                    'condition_received',
                    'condition_handed_over',
                    'quantity',
                ], $like))
                ->orWhereHas('employeeLogs', fn ($relation) => $this->whereColumnsLike($relation, [
                    'category',
                    'name',
                    'no_forklift_',
                    'work_area',
                    'personil_count',
                    'time_in',
                    'time_out',
                    'work_time',
                    'description',
                ], $like));
        });
    }

    private function whereColumnsLike($query, array $columns, string $like): void
    {
        $query->where(function ($columnQuery) use ($columns, $like): void {
            foreach ($columns as $column) {
                $columnQuery->orWhere($column, 'like', $like);
            }
        });
    }

    private function buildDateSearchPatterns(string $keyword): array
    {
        $months = [
            'januari' => '01', 'jan' => '01', 'january' => '01',
            'februari' => '02', 'feb' => '02', 'february' => '02', 'pebruari' => '02',
            'maret' => '03', 'mar' => '03', 'march' => '03',
            'april' => '04', 'apr' => '04',
            'mei' => '05', 'may' => '05',
            'juni' => '06', 'jun' => '06', 'june' => '06',
            'juli' => '07', 'jul' => '07', 'july' => '07',
            'agustus' => '08', 'agu' => '08', 'agus' => '08', 'ags' => '08', 'august' => '08', 'aug' => '08',
            'september' => '09', 'sep' => '09', 'sept' => '09',
            'oktober' => '10', 'okt' => '10', 'october' => '10', 'oct' => '10',
            'november' => '11', 'nov' => '11', 'nop' => '11', 'nopember' => '11',
            'desember' => '12', 'des' => '12', 'december' => '12', 'dec' => '12',
        ];

        $normalized = mb_strtolower(trim($keyword));

        if ($normalized === '') {
            return [];
        }

        $tokens = array_values(array_filter(
            preg_split('/[\s,\/\-\.]+/', $normalized) ?: [],
            fn ($token) => $token !== ''
        ));

        if (empty($tokens)) {
            return [];
        }

        $resolveMonth = function (string $token) use ($months): ?string {
            if (isset($months[$token])) {
                return $months[$token];
            }

            if (strlen($token) < 2) {
                return null;
            }

            $matches = [];

            foreach ($months as $monthName => $monthNumber) {
                if (str_starts_with($monthName, $token)) {
                    $matches[$monthNumber] = true;
                }
            }

            return count($matches) === 1 ? array_key_first($matches) : null;
        };

        foreach ($tokens as $token) {
            if ($resolveMonth($token) === null && ! ctype_digit($token)) {
                return [];
            }
        }

        $monthFromName = null;
        $year = null;
        $numerics = [];

        foreach ($tokens as $token) {
            $resolvedMonth = $resolveMonth($token);

            if ($resolvedMonth !== null) {
                $monthFromName = $resolvedMonth;
            } else {
                $value = (int) $token;

                if (strlen($token) === 4 && $value >= 1900 && $value <= 2100) {
                    $year = $token;
                } else {
                    $numerics[] = $value;
                }
            }
        }

        if ($monthFromName === null && $year === null && count($numerics) === 1) {
            return [];
        }

        $candidates = [];
        $yearPart = $year ?? '%';
        $pad = fn (int $value) => str_pad((string) $value, 2, '0', STR_PAD_LEFT);

        if ($monthFromName !== null) {
            if (empty($numerics)) {
                $candidates[] = $yearPart.'-'.$monthFromName.'-%';
            } else {
                foreach ($numerics as $value) {
                    if ($value >= 1 && $value <= 31) {
                        $candidates[] = $yearPart.'-'.$monthFromName.'-'.$pad($value).'%';
                    }
                }
            }
        } elseif (count($numerics) === 1) {
            $value = $numerics[0];

            if ($year !== null && $value >= 1 && $value <= 12) {
                $candidates[] = $yearPart.'-'.$pad($value).'-%';
            }
            if ($value >= 1 && $value <= 31) {
                $candidates[] = $yearPart.'-%-'.$pad($value).'%';
            }
        } elseif (count($numerics) >= 2) {
            [$first, $second] = $numerics;

            if ($first >= 1 && $first <= 31 && $second >= 1 && $second <= 12) {
                $candidates[] = $yearPart.'-'.$pad($second).'-'.$pad($first).'%';
            }
            if ($first >= 1 && $first <= 12 && $second >= 1 && $second <= 31 && $first !== $second) {
                $candidates[] = $yearPart.'-'.$pad($first).'-'.$pad($second).'%';
            }
        } elseif ($year !== null) {
            $candidates[] = $yearPart.'-%-%';
        }

        return array_values(array_unique($candidates));
    }

    private function authorizeManagementAccess(Request $request): void
    {
        $user = $request->user();

        abort_unless($user && Role::hasManagementAccess($user->role->name ?? null), 403);
    }
}
