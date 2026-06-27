<?php

namespace App\Http\Controllers;

use App\Enums\ReportStatus;
use App\Http\Controllers\Concerns\BuildsDivisionArchive;
use App\Http\Controllers\Concerns\ResolvesMaintenanceMeta;
use App\Http\Controllers\Concerns\ResolvesReportMeta;
use App\Http\Controllers\Concerns\ResolvesSafetyMeta;
use App\Http\Controllers\Concerns\SearchesReports;
use App\Models\AdminActivityLog;
use App\Models\DailyReport;
use App\Models\MaintenanceReport;
use App\Models\MasterEmployee;
use App\Models\MasterInventoryItem;
use App\Models\MasterSafetyItem;
use App\Models\MasterSafetyLocation;
use App\Models\MasterTruck;
use App\Models\MasterUnit;
use App\Models\Role;
use App\Models\SafetyReport;
use App\Models\User;
use App\Services\SystemBackupService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminV2Controller extends Controller
{
    use BuildsDivisionArchive;
    use ResolvesMaintenanceMeta;
    use ResolvesReportMeta;
    use ResolvesSafetyMeta;
    use SearchesReports;

    public function index(Request $request)
    {
        return view('admin.index', array_merge($this->shellData($request), [
            'stats' => $this->dashboardCards(),
            'auditLogs' => $this->latestAuditCards(),
            'roles' => Role::orderBy('name')->get(),
        ]));
    }

    public function archive(Request $request)
    {
        $archiveSearch = trim((string) $request->input('q', ''));
        $sort = $request->input('sort', 'newest') === 'oldest' ? 'oldest' : 'newest';
        $selectedDate = $request->input('tanggal');
        $selectedGroup = strtoupper((string) $request->input('regu', 'all'));
        $selectedShift = strtolower((string) $request->input('shift', 'all'));
        $selectedDivision = strtolower((string) $request->input('divisi', 'all'));
        $selectedStatus = strtolower((string) $request->input('status', 'all'));

        $reports = $this->buildDivisionArchivePaginator($request, [
            'archiveSearch' => $archiveSearch,
            'sort' => $sort,
            'selectedDate' => $selectedDate,
            'selectedGroup' => $selectedGroup,
            'selectedShift' => $selectedShift,
            'selectedDivision' => $selectedDivision,
            'selectedStatus' => $selectedStatus,
        ], 'admin');

        return view('admin.archive', array_merge($this->shellData($request), [
            'stats' => $this->archiveCards(),
            'reports' => $reports,
            'archiveSearch' => $archiveSearch,
            'sort' => $sort,
            'selectedDate' => $selectedDate,
            'selectedGroup' => $selectedGroup,
            'selectedShift' => $selectedShift,
            'selectedDivision' => $selectedDivision,
            'selectedStatus' => $selectedStatus,
        ]));
    }

    public function archiveSuggestions(Request $request)
    {
        $keyword = trim((string) $request->input('q', ''));

        $items = $this->buildDivisionArchiveSuggestions($keyword, 'admin');

        return response()->json([
            'keyword' => $keyword,
            'total' => $items->count(),
            'items' => $items,
        ]);
    }

    public function log(Request $request)
    {
        $activitySearch = trim((string) $request->input('q', ''));
        $sort = $request->input('sort', 'newest') === 'oldest' ? 'oldest' : 'newest';
        $selectedDate = $request->input('tanggal');
        $selectedRole = strtolower((string) $request->input('role', 'all'));
        $selectedType = strtolower((string) $request->input('type', 'all'));

        $query = AdminActivityLog::query()->with('user.role');

        if ($activitySearch !== '') {
            $like = '%'.$activitySearch.'%';
            $query->where(function (Builder $builder) use ($like): void {
                $builder->where('description', 'like', $like)
                    ->orWhere('ip_address', 'like', $like)
                    ->orWhereHas('user', function (Builder $userQuery) use ($like): void {
                        $userQuery->where('name', 'like', $like)
                            ->orWhere('username', 'like', $like);
                    });
            });
        }

        if ($selectedDate) {
            $query->whereDate('created_at', $selectedDate);
        }

        if ($selectedRole !== '' && $selectedRole !== 'all') {
            $query->whereHas('user.role', fn (Builder $roleQuery) => $roleQuery->where('name', $selectedRole));
        }

        if ($selectedType !== '' && $selectedType !== 'all') {
            $query->where('type', $selectedType);
        }

        $logs = $query
            ->when($sort === 'oldest', fn (Builder $builder) => $builder->oldest())
            ->when($sort === 'newest', fn (Builder $builder) => $builder->latest())
            ->limit(60)
            ->get()
            ->map(fn (AdminActivityLog $activity): array => $this->activityRow($activity));

        return view('admin.log', array_merge($this->shellData($request), [
            'stats' => $this->dashboardCards(),
            'logs' => $logs,
            'activitySearch' => $activitySearch,
            'sort' => $sort,
            'selectedDate' => $selectedDate,
            'selectedRole' => $selectedRole,
            'selectedType' => $selectedType,
        ]));
    }

    public function userManage(Request $request)
    {
        $search = trim((string) $request->input('q', ''));

        $users = User::query()
            ->with('role')
            ->when($search !== '', function (Builder $query) use ($search): void {
                $like = '%'.$search.'%';
                $query->where(function (Builder $builder) use ($like): void {
                    $builder->where('name', 'like', $like)
                        ->orWhere('username', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('group', 'like', $like)
                        ->orWhereHas('role', fn (Builder $roleQuery) => $roleQuery->where('name', 'like', $like));
                });
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        $users->getCollection()->transform(fn (User $user, int $index): array => $this->userRow($user, $users->firstItem() + $index));

        return view('admin.user-manage', array_merge($this->shellData($request), [
            'users' => $users,
            'roles' => Role::orderBy('name')->get(),
            'userSearch' => $search,
        ]));
    }

    public function dataMaster(Request $request)
    {
        $validPanes = ['karyawan', 'unit', 'truck', 'inventaris', 'safety_lokasi', 'safety_item'];
        $pane = in_array($request->input('pane'), $validPanes, true)
            ? $request->input('pane')
            : 'karyawan';
        $search = trim((string) $request->input('q', ''));

        // Filter cepat (dropdown) per pane.
        $empGroup = trim((string) $request->input('f_group', ''));
        $empDivision = trim((string) $request->input('f_division', ''));
        $empPosition = trim((string) $request->input('f_position', ''));
        $unitType = trim((string) $request->input('f_type', ''));
        $unitCategory = trim((string) $request->input('f_category', ''));

        $paginationQuery = fn (string $targetPane): array => array_filter([
            'pane' => $targetPane,
            'q' => $targetPane === $pane ? $search : null,
            'f_group' => $targetPane === 'karyawan' ? $empGroup : null,
            'f_division' => $targetPane === 'karyawan' ? $empDivision : null,
            'f_position' => $targetPane === 'karyawan' ? $empPosition : null,
            'f_type' => $targetPane === 'unit' ? $unitType : null,
            'f_category' => $targetPane === 'unit' ? $unitCategory : null,
        ], fn ($value): bool => filled($value));

        $employees = MasterEmployee::query()
            ->when($pane === 'karyawan' && $search !== '', function (Builder $query) use ($search): void {
                $like = '%'.$search.'%';
                $query->where(fn (Builder $builder) => $builder
                    ->where('npk', 'like', $like)
                    ->orWhere('name', 'like', $like)
                    ->orWhere('group_name', 'like', $like)
                    ->orWhere('position', 'like', $like)
                    ->orWhere('division', 'like', $like)
                    ->orWhere('work_time', 'like', $like));
            })
            ->when($pane === 'karyawan' && $empGroup !== '', fn (Builder $query) => $this->applyEmployeeGroupFilter($query, $empGroup))
            ->when($pane === 'karyawan' && $empDivision !== '', fn (Builder $query) => $query->whereIn('division', $this->divisionFilterValues($empDivision)))
            ->when($pane === 'karyawan' && $empPosition !== '', fn (Builder $query) => $query->where('position', $empPosition))
            ->orderBy('name')
            ->paginate(10, ['*'], 'employees_page')
            ->appends($paginationQuery('karyawan'));

        $units = MasterUnit::query()
            ->when($pane === 'unit' && $search !== '', function (Builder $query) use ($search): void {
                $like = '%'.$search.'%';
                $query->where(fn (Builder $builder) => $builder
                    ->where('name', 'like', $like)
                    ->orWhere('type', 'like', $like)
                    ->orWhere('unit_code', 'like', $like)
                    ->orWhere('brand', 'like', $like)
                    ->orWhere('unit_number', 'like', $like)
                    ->orWhere('plate_number', 'like', $like)
                    ->orWhere('macro_category', 'like', $like));
            })
            ->when($pane === 'unit' && $unitType !== '', fn (Builder $query) => $query->where('type', $unitType))
            ->when($pane === 'unit' && $unitCategory !== '', fn (Builder $query) => $unitCategory === '-'
                ? $query->whereNull('macro_category')
                : $query->where('macro_category', $unitCategory))
            ->orderBy('id')
            ->paginate(10, ['*'], 'units_page')
            ->appends($paginationQuery('unit'));

        $trucks = MasterTruck::query()
            ->when($pane === 'truck' && $search !== '', function (Builder $query) use ($search): void {
                $like = '%'.$search.'%';
                $query->where(fn (Builder $builder) => $builder
                    ->where('name', 'like', $like)
                    ->orWhere('plate_number', 'like', $like)
                    ->orWhere('description', 'like', $like));
            })
            ->orderBy('name')
            ->paginate(10, ['*'], 'trucks_page')
            ->appends($paginationQuery('truck'));

        $inventories = MasterInventoryItem::query()
            ->when($pane === 'inventaris' && $search !== '', function (Builder $query) use ($search): void {
                $like = '%'.$search.'%';
                $query->where(fn (Builder $builder) => $builder
                    ->where('name', 'like', $like)
                    ->orWhere('category', 'like', $like));
            })
            ->orderBy('name')
            ->paginate(10, ['*'], 'inventories_page')
            ->appends($paginationQuery('inventaris'));

        $safetyLocations = MasterSafetyLocation::query()
            ->withCount('items')
            ->when($pane === 'safety_lokasi' && $search !== '', function (Builder $query) use ($search): void {
                $query->where('name', 'like', '%'.$search.'%');
            })
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate(10, ['*'], 'safety_locations_page')
            ->appends($paginationQuery('safety_lokasi'));

        $safetyItems = MasterSafetyItem::query()
            ->when($pane === 'safety_item' && $search !== '', function (Builder $query) use ($search): void {
                $query->where('name', 'like', '%'.$search.'%');
            })
            ->orderBy('name')
            ->paginate(10, ['*'], 'safety_items_page')
            ->appends($paginationQuery('safety_item'));

        $employees->getCollection()->transform(fn (MasterEmployee $employee, int $index): array => [
            'no' => $employees->firstItem() + $index,
            'id' => $employee->id,
            'npk' => $employee->npk ?: '-',
            'name' => $employee->name,
            'group' => $this->displayGroup($employee->group_name),
            'position' => $employee->position ?: '-',
            'division' => $this->displayDivision($employee->division),
            'work_time' => $employee->work_time ?: '-',
            'update_url' => route('admin.master.employees.update', $employee),
            'destroy_url' => route('admin.master.employees.destroy', $employee),
        ]);

        $units->getCollection()->transform(fn (MasterUnit $unit, int $index): array => [
            'no' => $units->firstItem() + $index,
            'id' => $unit->id,
            'name' => $unit->name,
            'type' => $unit->type ?: '-',
            'unit_code' => $unit->unit_code ?: '-',
            'brand' => $unit->brand ?: '-',
            'unit_number' => $unit->unit_number ?: '-',
            'plate' => $unit->plate_number ?: '-',
            'macro_category' => match ($unit->macro_category) {
                MasterUnit::MACRO_TRUCK => 'Truck',
                MasterUnit::MACRO_HEAVY => 'Heavy',
                MasterUnit::MACRO_BUS => 'Bus',
                default => '-',
            },
            'in_operational_check' => $unit->in_operational_check ? 'Ya' : 'Tidak',
            'year' => $unit->year ?: '-',
            'update_url' => route('admin.master.units.update', $unit),
            'destroy_url' => route('admin.master.units.destroy', $unit),
        ]);

        $trucks->getCollection()->transform(fn (MasterTruck $truck, int $index): array => [
            'no' => $trucks->firstItem() + $index,
            'id' => $truck->id,
            'name' => $truck->name,
            'plate' => $truck->plate_number ?: '-',
            'desc' => $truck->description ?: '-',
            'update_url' => route('admin.master.trucks.update', $truck),
            'destroy_url' => route('admin.master.trucks.destroy', $truck),
        ]);

        $inventories->getCollection()->transform(fn (MasterInventoryItem $inventory, int $index): array => [
            'no' => $inventories->firstItem() + $index,
            'id' => $inventory->id,
            'name' => $inventory->name,
            'category' => $inventory->category ?: 'Umum',
            'stock' => (int) $inventory->stock,
            'update_url' => route('admin.master.inventories.update', $inventory),
            'destroy_url' => route('admin.master.inventories.destroy', $inventory),
        ]);

        $safetyLocations->getCollection()->transform(fn (MasterSafetyLocation $location, int $index): array => [
            'no' => $safetyLocations->firstItem() + $index,
            'id' => $location->id,
            'name' => $location->name,
            'sort_order' => (int) $location->sort_order,
            'item_count' => (int) $location->items_count,
            'is_active' => $location->is_active ? 'Aktif' : 'Nonaktif',
            'update_url' => route('admin.master.safety-locations.update', $location),
            'destroy_url' => route('admin.master.safety-locations.destroy', $location),
        ]);

        $safetyItems->getCollection()->transform(fn (MasterSafetyItem $item, int $index): array => [
            'no' => $safetyItems->firstItem() + $index,
            'id' => $item->id,
            'name' => $item->name,
            'is_countable' => $item->is_countable ? 'Ya' : 'Tidak',
            'is_active' => $item->is_active ? 'Aktif' : 'Nonaktif',
            'update_url' => route('admin.master.safety-items.update', $item),
            'destroy_url' => route('admin.master.safety-items.destroy', $item),
        ]);

        return view('admin.datamaster', array_merge($this->shellData($request), [
            'activePane' => $pane,
            'masterSearch' => $search,
            'masterFilters' => [
                'group' => $empGroup,
                'division' => $empDivision,
                'position' => $empPosition,
                'type' => $unitType,
                'category' => $unitCategory,
            ],
            'employees' => $employees,
            'units' => $units,
            'trucks' => $trucks,
            'inventories' => $inventories,
            'safetyLocations' => $safetyLocations,
            'safetyItems' => $safetyItems,
            'masterActions' => [
                'karyawan' => ['store' => route('admin.master.employees.store')],
                'unit' => ['store' => route('admin.master.units.store')],
                'truck' => ['store' => route('admin.master.trucks.store')],
                'inventaris' => ['store' => route('admin.master.inventories.store')],
                'safety_lokasi' => ['store' => route('admin.master.safety-locations.store')],
                'safety_item' => ['store' => route('admin.master.safety-items.store')],
            ],
        ]));
    }

    public function backup(Request $request)
    {
        $schedule = $this->backupSchedule();
        $backups = $this->backupFiles();
        $lastBackup = collect($backups)->first();
        $usedBytes = collect($backups)->sum('bytes');
        $capacityBytes = 30 * 1024 * 1024 * 1024;
        $usedPercent = $capacityBytes > 0 ? min(100, (int) round(($usedBytes / $capacityBytes) * 100)) : 0;
        $annualYear = $this->annualBackupYear();

        return view('admin.backup', array_merge($this->shellData($request), [
            'stats' => [
                ['label' => 'Backup Terakhir', 'value' => $lastBackup['date_short'] ?? 'Belum Ada', 'icon' => 'fi fi-sr-cloud-check', 'color' => 'green'],
                ['label' => 'Total Cadangan', 'value' => (string) count($backups), 'icon' => 'fi fi-sr-folder', 'color' => 'blue'],
                ['label' => 'Storage Terpakai', 'value' => $usedPercent.'%', 'icon' => 'fi fi-sr-database', 'color' => 'cyan'],
                ['label' => 'Retensi Aktif', 'value' => $schedule['retention'], 'icon' => 'fi fi-sr-calendar', 'color' => 'orange'],
            ],
            'backups' => $backups,
            'backupSchedule' => $schedule,
            'backupStorage' => [
                'used_label' => $this->formatBytes($usedBytes).' dipakai',
                'capacity_label' => '30 GB tersedia',
                'percent' => $usedPercent,
            ],
            'annualBackup' => [
                'eligible' => $annualYear !== null,
                'year' => $annualYear,
                'count' => $annualYear !== null ? DailyReport::whereYear('report_date', $annualYear)->count() : 0,
                'file_name' => $annualYear !== null ? 'Laporan_Harian_KSS_Tahun_'.$annualYear.'.zip' : null,
            ],
        ]));
    }

    public function help(Request $request)
    {
        return view('admin.help', array_merge($this->shellData($request), [
            'topics' => $this->helpTopics(),
            'faqs' => $this->helpFaqs(),
        ]));
    }

    public function showReport(Request $request, DailyReport $report)
    {
        abort_unless(in_array($report->status, $this->archiveStatuses(), true), 404);

        return view('report-ops.viewpdf', [
            'report' => $this->loadReport($report),
            'isPdf' => false,
            'backUrl' => route('admin.archive'),
            'pdfUrl' => route('admin.reports.download', $report),
        ]);
    }

    public function downloadReport(DailyReport $report)
    {
        abort_unless(in_array($report->status, $this->archiveStatuses(), true), 404);

        $path = storage_path('app/public/reports/report-'.$report->id.'.pdf');

        if (is_file($path)) {
            return response()->download($path, $this->reportFileName($report, 'pdf'));
        }

        if (class_exists(Pdf::class)) {
            $pdf = Pdf::loadView('report-ops.pdf', [
                'report' => $this->loadReport($report),
                'isPdf' => true,
            ]);
            $pdf->setPaper([0, 0, 612.00, 936.00], 'portrait');
            $pdf->setOption('isRemoteEnabled', true);

            return $pdf->download($this->reportFileName($report, 'pdf'));
        }

        return view('report-ops.viewpdf', [
            'report' => $this->loadReport($report),
            'isPdf' => false,
            'backUrl' => route('admin.archive'),
            'pdfUrl' => null,
        ]);
    }

    public function destroyReport(Request $request, DailyReport $report)
    {
        abort_unless(in_array($report->status, $this->archiveStatuses(), true), 404);

        $documentId = $this->documentId($report);
        $path = storage_path('app/public/reports/report-'.$report->id.'.pdf');

        if (is_file($path)) {
            @unlink($path);
        }

        $report->delete();
        $this->recordActivity($request, 'delete', 'Menghapus arsip laporan '.$documentId);

        return back()->with('success', 'Arsip laporan berhasil dihapus.');
    }

    public function showMaintenanceReport(Request $request, MaintenanceReport $report)
    {
        abort_unless(in_array($report->status, $this->maintenanceArchiveStatuses(), true), 404);

        return view('pemeliharaan.viewpdf', [
            'report'  => $this->loadMaintenanceReport($report),
            'isPdf'   => false,
            'backUrl' => route('admin.archive'),
            'pdfUrl'  => route('admin.maintenance-reports.download', $report),
        ]);
    }

    public function downloadMaintenanceReport(MaintenanceReport $report)
    {
        abort_unless(in_array($report->status, $this->maintenanceArchiveStatuses(), true), 404);

        $path = storage_path('app/public/maintenance-reports/maintenance-report-'.$report->id.'.pdf');

        if (is_file($path)) {
            return response()->download($path, $this->maintenanceFileName($report, 'pdf'));
        }

        if (class_exists(Pdf::class)) {
            $pdf = Pdf::loadView('pemeliharaan.pdf', [
                'report' => $this->loadMaintenanceReport($report),
                'isPdf'  => true,
            ]);
            $pdf->setPaper([0, 0, 612.00, 936.00], 'portrait');
            $pdf->setOption('isRemoteEnabled', true);

            return $pdf->download($this->maintenanceFileName($report, 'pdf'));
        }

        return view('pemeliharaan.viewpdf', [
            'report'  => $this->loadMaintenanceReport($report),
            'isPdf'   => false,
            'backUrl' => route('admin.archive'),
            'pdfUrl'  => null,
        ]);
    }

    public function destroyMaintenanceReport(Request $request, MaintenanceReport $report)
    {
        abort_unless(in_array($report->status, $this->maintenanceArchiveStatuses(), true), 404);

        $documentId = $this->maintenanceDocumentId($report);

        $path = storage_path('app/public/maintenance-reports/maintenance-report-'.$report->id.'.pdf');
        if (is_file($path)) {
            @unlink($path);
        }

        $report->delete();
        $this->recordActivity($request, 'delete', 'Menghapus arsip laporan pemeliharaan '.$documentId);

        return back()->with('success', 'Arsip laporan pemeliharaan berhasil dihapus.');
    }

    public function showSafetyReport(Request $request, SafetyReport $report)
    {
        abort_unless(in_array($report->status, $this->safetyArchiveStatuses(), true), 404);

        return view('report-safety.viewpdf', [
            'report'  => $this->loadSafetyReport($report),
            'isPdf'   => false,
            'backUrl' => route('admin.archive'),
            'pdfUrl'  => route('admin.safety-reports.download', $report),
        ]);
    }

    public function downloadSafetyReport(SafetyReport $report)
    {
        abort_unless(in_array($report->status, $this->safetyArchiveStatuses(), true), 404);

        $path = storage_path('app/public/safety-reports/safety-report-'.$report->id.'.pdf');

        if (is_file($path)) {
            return response()->download($path, $this->safetyFileName($report, 'pdf'));
        }

        if (class_exists(Pdf::class)) {
            $pdf = Pdf::loadView('report-safety.pdf', [
                'report' => $this->loadSafetyReport($report),
                'isPdf'  => true,
            ]);
            $pdf->setPaper([0, 0, 612.00, 936.00], 'portrait');
            $pdf->setOption('isRemoteEnabled', true);

            return $pdf->download($this->safetyFileName($report, 'pdf'));
        }

        return view('report-safety.viewpdf', [
            'report'  => $this->loadSafetyReport($report),
            'isPdf'   => false,
            'backUrl' => route('admin.archive'),
            'pdfUrl'  => null,
        ]);
    }

    public function destroySafetyReport(Request $request, SafetyReport $report)
    {
        abort_unless(in_array($report->status, $this->safetyArchiveStatuses(), true), 404);

        $documentId = $this->safetyDocumentId($report);

        $path = storage_path('app/public/safety-reports/safety-report-'.$report->id.'.pdf');
        if (is_file($path)) {
            @unlink($path);
        }

        $report->delete();
        $this->recordActivity($request, 'delete', 'Menghapus arsip laporan K3 '.$documentId);

        return back()->with('success', 'Arsip laporan K3 berhasil dihapus.');
    }

    public function storeUser(Request $request)
    {
        $data = $this->validateUser($request, null, true);

        $payload = $this->userPayload($data, true);
        $signaturePath = $this->storeSignature($request, $data['username']);

        if ($signaturePath) {
            $payload['signature_path'] = $signaturePath;
        }

        $user = User::create($payload);
        $this->recordActivity($request, 'update', 'Menambahkan pengguna '.$user->name, ['username' => $user->username]);

        return back()->with('success', 'Pengguna berhasil ditambahkan.');
    }

    public function updateUser(Request $request, User $user)
    {
        $data = $this->validateUser($request, $user, false);

        $payload = $this->userPayload($data, false);
        $signaturePath = $this->storeSignature($request, $data['username']);

        if ($signaturePath) {
            $payload['signature_path'] = $signaturePath;
        }

        $user->update($payload);
        $this->recordActivity($request, 'update', 'Memperbarui pengguna '.$user->name, ['username' => $user->username]);

        return back()->with('success', 'Data pengguna berhasil diperbarui.');
    }

    public function toggleUserStatus(Request $request, User $user)
    {
        if ($request->user()->is($user)) {
            return back()->with('error', 'Akun admin yang sedang dipakai tidak bisa dinonaktifkan.');
        }

        $status = $user->status === 'aktif' ? 'nonaktif' : 'aktif';
        $user->update(['status' => $status]);

        $this->recordActivity($request, 'update', 'Mengubah status pengguna '.$user->name.' menjadi '.$status, ['username' => $user->username]);

        return back()->with('success', 'Status pengguna berhasil diperbarui.');
    }

    public function destroyUser(Request $request, User $user)
    {
        if ($request->user()->is($user)) {
            return back()->with('error', 'Akun admin yang sedang dipakai tidak bisa dihapus.');
        }

        $name = $user->name;
        $user->delete();

        $this->recordActivity($request, 'delete', 'Menghapus pengguna '.$name);

        return back()->with('success', 'Pengguna berhasil dihapus.');
    }

    public function storeEmployee(Request $request)
    {
        $request->merge(['npk' => $this->nullableString($request->input('npk'))]);

        $data = $request->validate([
            'npk' => ['nullable', 'string', 'max:50', 'unique:master_employees,npk'],
            'name' => ['required', 'string', 'max:255'],
            'group' => ['nullable', 'string', 'max:20'],
            'position' => ['nullable', 'string', 'max:255'],
            'division' => ['nullable', Rule::in(['Operasional', 'Pemeliharaan', 'Safety (Coming Soon)', 'Safety', 'Office', 'Keduanya', MasterEmployee::DIVISION_OPERATIONAL, MasterEmployee::DIVISION_MAINTENANCE, MasterEmployee::DIVISION_SAFETY, MasterEmployee::DIVISION_OFFICE, MasterEmployee::DIVISION_BOTH])],
            'work_time' => ['nullable', Rule::in(['Non Shift', 'Shift', 'Relief', 'non shift', 'shift', 'relief'])],
        ]);

        $employee = MasterEmployee::create([
            'npk' => $data['npk'],
            'name' => $data['name'],
            'group_name' => $this->normalizeGroup($data['group'] ?? null),
            'position' => $this->nullableSelectValue($data['position'] ?? null),
            'division' => $this->normalizeDivision($data['division'] ?? null),
            'work_time' => $this->normalizeWorkTime($data['work_time'] ?? null),
            'status' => 'active',
        ]);

        $this->recordActivity($request, 'update', 'Menambahkan master karyawan '.$employee->name);

        return redirect()->route('admin.datamaster', ['pane' => 'karyawan'])->with('success', 'Data karyawan berhasil ditambahkan.');
    }

    public function updateEmployee(Request $request, MasterEmployee $employee)
    {
        $request->merge(['npk' => $this->nullableString($request->input('npk'))]);

        $data = $request->validate([
            'npk' => ['nullable', 'string', 'max:50', Rule::unique('master_employees', 'npk')->ignore($employee->id)],
            'name' => ['required', 'string', 'max:255'],
            'group' => ['nullable', 'string', 'max:20'],
            'position' => ['nullable', 'string', 'max:255'],
            'division' => ['nullable', Rule::in(['Operasional', 'Pemeliharaan', 'Safety (Coming Soon)', 'Safety', 'Office', 'Keduanya', MasterEmployee::DIVISION_OPERATIONAL, MasterEmployee::DIVISION_MAINTENANCE, MasterEmployee::DIVISION_SAFETY, MasterEmployee::DIVISION_OFFICE, MasterEmployee::DIVISION_BOTH])],
            'work_time' => ['nullable', Rule::in(['Non Shift', 'Shift', 'Relief', 'non shift', 'shift', 'relief'])],
        ]);

        $employee->update([
            'npk' => $data['npk'],
            'name' => $data['name'],
            'group_name' => $this->normalizeGroup($data['group'] ?? null),
            'position' => $this->nullableSelectValue($data['position'] ?? null),
            'division' => $this->normalizeDivision($data['division'] ?? null),
            'work_time' => $this->normalizeWorkTime($data['work_time'] ?? null),
        ]);

        $this->recordActivity($request, 'update', 'Memperbarui master karyawan '.$employee->name);

        return redirect()->route('admin.datamaster', ['pane' => 'karyawan'])->with('success', 'Data karyawan berhasil diperbarui.');
    }

    public function destroyEmployee(Request $request, MasterEmployee $employee)
    {
        $name = $employee->name;
        $employee->delete();
        $this->recordActivity($request, 'delete', 'Menghapus master karyawan '.$name);

        return redirect()->route('admin.datamaster', ['pane' => 'karyawan'])->with('success', 'Data karyawan berhasil dihapus.');
    }

    public function storeUnit(Request $request)
    {
        $data = $this->validateUnit($request);

        $unit = MasterUnit::create(array_merge($data, [
            'status' => 'active',
        ]));

        $this->recordActivity($request, 'update', 'Menambahkan master unit '.$unit->name);

        return redirect()->route('admin.datamaster', ['pane' => 'unit'])->with('success', 'Data unit berhasil ditambahkan.');
    }

    public function updateUnit(Request $request, MasterUnit $unit)
    {
        $data = $this->validateUnit($request);

        $unit->update($data);
        $this->recordActivity($request, 'update', 'Memperbarui master unit '.$unit->name);

        return redirect()->route('admin.datamaster', ['pane' => 'unit'])->with('success', 'Data unit berhasil diperbarui.');
    }

    private function validateUnit(Request $request): array
    {
        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'max:255'],
            'unit_code' => ['nullable', 'string', 'max:30'],
            'brand' => ['nullable', 'string', 'max:80'],
            'unit_number' => ['nullable', 'string', 'max:80'],
            'plate_number' => ['nullable', 'string', 'max:80'],
            'macro_category' => ['nullable', Rule::in([MasterUnit::MACRO_TRUCK, MasterUnit::MACRO_HEAVY, MasterUnit::MACRO_BUS, '-', ''])],
            'in_operational_check' => ['nullable', 'string'],
            'year' => ['nullable', 'integer', 'min:1900', 'max:'.((int) date('Y') + 1)],
        ]);

        // "Kode" admin = nomor urut aset (unit_number). Singkatan tipe (unit_code)
        // diturunkan otomatis dari Tipe agar form operasional tetap konsisten.
        $data['unit_code'] = $this->nullableUpper($data['unit_code'] ?? null)
            ?: $this->unitCodeFromType($data['type'] ?? null);
        $data['brand'] = $this->nullableUpper($data['brand'] ?? null);
        $data['unit_number'] = $this->nullableUpper($data['unit_number'] ?? null);
        $data['plate_number'] = $this->nullableUpper($data['plate_number'] ?? null);
        $data['macro_category'] = in_array($data['macro_category'] ?? null, [MasterUnit::MACRO_TRUCK, MasterUnit::MACRO_HEAVY, MasterUnit::MACRO_BUS], true)
            ? $data['macro_category']
            : null;
        $data['year'] = $data['year'] ?? null;

        // Kategori "Masuk Cek Unit Operasional": pakai pilihan form (Ya/Tidak)
        // kalau dikirim; kalau tidak, turunkan default dari tipe (Avanza/Minibus
        // dikecualikan). Disimpan sebagai boolean.
        $opsCheck = $data['in_operational_check'] ?? null;
        $data['in_operational_check'] = $opsCheck !== null
            ? in_array(Str::lower(trim((string) $opsCheck)), ['ya', '1', 'true', 'on'], true)
            : in_array($data['type'] ?? '', MasterUnit::OPERATIONAL_CHECK_TYPES, true);

        // Nama unit = gabungan Tipe + Kode unit (mis. "Minibus KSS-01"); kalau tak
        // ada keduanya, pakai nama yang dikirim atau fallback aman.
        $composedName = trim((string) ($data['type'] ?? '').' '.(string) ($data['unit_number'] ?? ''));
        $data['name'] = $composedName !== ''
            ? $composedName
            : (trim((string) ($data['name'] ?? '')) ?: 'Unit');

        return $data;
    }

    private function unitCodeFromType(?string $type): ?string
    {
        return match (Str::lower(trim((string) $type))) {
            'trailer', 'trailler' => 'TRL',
            'tronton' => 'TRT',
            'dump truck' => 'DT',
            'minibus', 'bus' => 'BUS',
            'pickup', 'pick up' => 'PU',
            'forklift' => 'FL',
            'wheel loader' => 'WL',
            'excavator' => 'EXC',
            default => null,
        };
    }

    public function destroyUnit(Request $request, MasterUnit $unit)
    {
        $name = $unit->name;
        $unit->delete();
        $this->recordActivity($request, 'delete', 'Menghapus master unit '.$name);

        return redirect()->route('admin.datamaster', ['pane' => 'unit'])->with('success', 'Data unit berhasil dihapus.');
    }

    public function storeTruck(Request $request)
    {
        $data = $this->validateTruck($request);

        $truck = MasterTruck::create($data);
        $this->recordActivity($request, 'update', 'Menambahkan master truck '.$truck->name);

        return redirect()->route('admin.datamaster', ['pane' => 'truck'])->with('success', 'Data truck berhasil ditambahkan.');
    }

    public function updateTruck(Request $request, MasterTruck $truck)
    {
        $truck->update($this->validateTruck($request));
        $this->recordActivity($request, 'update', 'Memperbarui master truck '.$truck->name);

        return redirect()->route('admin.datamaster', ['pane' => 'truck'])->with('success', 'Data truck berhasil diperbarui.');
    }

    public function destroyTruck(Request $request, MasterTruck $truck)
    {
        $name = $truck->name;
        $truck->delete();
        $this->recordActivity($request, 'delete', 'Menghapus master truck '.$name);

        return redirect()->route('admin.datamaster', ['pane' => 'truck'])->with('success', 'Data truck berhasil dihapus.');
    }

    public function storeInventory(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'stock' => ['nullable', 'integer', 'min:0'],
        ]);

        $inventory = MasterInventoryItem::create([
            'name' => $data['name'],
            'category' => $data['category'] ?? 'Umum',
            'stock' => $data['stock'] ?? 0,
            'status' => 'active',
        ]);

        $this->recordActivity($request, 'update', 'Menambahkan master inventaris '.$inventory->name);

        return redirect()->route('admin.datamaster', ['pane' => 'inventaris'])->with('success', 'Data inventaris berhasil ditambahkan.');
    }

    public function updateInventory(Request $request, MasterInventoryItem $inventory)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'stock' => ['nullable', 'integer', 'min:0'],
        ]);

        $inventory->update([
            'name' => $data['name'],
            'category' => $data['category'] ?? 'Umum',
            'stock' => $data['stock'] ?? 0,
        ]);

        $this->recordActivity($request, 'update', 'Memperbarui master inventaris '.$inventory->name);

        return redirect()->route('admin.datamaster', ['pane' => 'inventaris'])->with('success', 'Data inventaris berhasil diperbarui.');
    }

    public function destroyInventory(Request $request, MasterInventoryItem $inventory)
    {
        $name = $inventory->name;
        $inventory->delete();
        $this->recordActivity($request, 'delete', 'Menghapus master inventaris '.$name);

        return redirect()->route('admin.datamaster', ['pane' => 'inventaris'])->with('success', 'Data inventaris berhasil dihapus.');
    }

    public function storeSafetyLocation(Request $request)
    {
        $location = MasterSafetyLocation::create($this->validateSafetyLocation($request));
        $this->recordActivity($request, 'update', 'Menambahkan master lokasi K3 '.$location->name);

        return redirect()->route('admin.datamaster', ['pane' => 'safety_lokasi'])->with('success', 'Data lokasi K3 berhasil ditambahkan.');
    }

    public function updateSafetyLocation(Request $request, MasterSafetyLocation $location)
    {
        $location->update($this->validateSafetyLocation($request));
        $this->recordActivity($request, 'update', 'Memperbarui master lokasi K3 '.$location->name);

        return redirect()->route('admin.datamaster', ['pane' => 'safety_lokasi'])->with('success', 'Data lokasi K3 berhasil diperbarui.');
    }

    public function destroySafetyLocation(Request $request, MasterSafetyLocation $location)
    {
        $name = $location->name;
        $location->delete();
        $this->recordActivity($request, 'delete', 'Menghapus master lokasi K3 '.$name);

        return redirect()->route('admin.datamaster', ['pane' => 'safety_lokasi'])->with('success', 'Data lokasi K3 berhasil dihapus.');
    }

    private function validateSafetyLocation(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'is_active' => ['nullable', 'string'],
        ]);

        return [
            'name' => $data['name'],
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => ($data['is_active'] ?? 'Aktif') !== 'Nonaktif',
        ];
    }

    public function storeSafetyItem(Request $request)
    {
        $item = MasterSafetyItem::create($this->validateSafetyItem($request));
        $this->recordActivity($request, 'update', 'Menambahkan master item K3 '.$item->name);

        return redirect()->route('admin.datamaster', ['pane' => 'safety_item'])->with('success', 'Data item K3 berhasil ditambahkan.');
    }

    public function updateSafetyItem(Request $request, MasterSafetyItem $item)
    {
        $item->update($this->validateSafetyItem($request));
        $this->recordActivity($request, 'update', 'Memperbarui master item K3 '.$item->name);

        return redirect()->route('admin.datamaster', ['pane' => 'safety_item'])->with('success', 'Data item K3 berhasil diperbarui.');
    }

    public function destroySafetyItem(Request $request, MasterSafetyItem $item)
    {
        $name = $item->name;
        $item->delete();
        $this->recordActivity($request, 'delete', 'Menghapus master item K3 '.$name);

        return redirect()->route('admin.datamaster', ['pane' => 'safety_item'])->with('success', 'Data item K3 berhasil dihapus.');
    }

    private function validateSafetyItem(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'is_countable' => ['nullable', 'string'],
            'is_active' => ['nullable', 'string'],
        ]);

        return [
            'name' => $data['name'],
            'is_countable' => ($data['is_countable'] ?? 'Tidak') === 'Ya',
            'is_active' => ($data['is_active'] ?? 'Aktif') !== 'Nonaktif',
        ];
    }

    public function generateBackup(Request $request, SystemBackupService $backup)
    {
        $filename = $backup->createSnapshot('manual', $request->user()?->only(['id', 'name', 'username']));
        $this->recordActivity($request, 'backup', 'Membuat backup manual '.$filename);

        return back()->with('success', 'Backup manual berhasil dibuat.');
    }

    /**
     * Backup tahunan: arsipkan SELURUH laporan tahun sebelumnya ke satu file ZIP
     * (untuk dipindahkan ke penyimpanan lokal di luar sistem), lalu hapus laporan
     * tersebut dari database agar penyimpanan server lebih ringan.
     */
    public function annualBackup(Request $request)
    {
        $year = $this->annualBackupYear();

        if ($year === null) {
            return back()->with('error', 'Backup tahunan belum tersedia. Fitur ini aktif saat sudah memasuki tahun baru dan masih ada laporan tahun sebelumnya di sistem.');
        }

        if (! class_exists(\ZipArchive::class)) {
            return back()->with('error', 'Ekstensi ZIP tidak tersedia di server, backup tahunan tidak dapat dibuat.');
        }

        $reports = DailyReport::with([
            'creator:id,name,username',
            'receiver:id,name,username',
            'approver:id,name,username',
            'loadingActivities.timesheets',
            'bulkLoadingActivities.logs',
            'materialActivity.items',
            'containerActivity.items',
            'turbaActivity.deliveries',
            'unitCheckLogs',
            'employeeLogs',
        ])
            ->whereYear('report_date', $year)
            ->orderBy('report_date')
            ->orderBy('id')
            ->get();

        if ($reports->isEmpty()) {
            return back()->with('error', 'Tidak ada laporan tahun '.$year.' yang bisa diarsipkan.');
        }

        $fileName = 'Laporan_Harian_KSS_Tahun_'.$year.'.zip';
        Storage::disk('local')->makeDirectory('admin-backups');
        $absolutePath = Storage::disk('local')->path('admin-backups/'.$fileName);

        $zip = new \ZipArchive;
        if ($zip->open($absolutePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            return back()->with('error', 'Gagal membuat file arsip backup tahunan.');
        }

        $zip->addFromString(
            'Laporan_Harian_KSS_Tahun_'.$year.'.json',
            json_encode([
                'generated_at' => now()->toIso8601String(),
                'generated_by' => $request->user()?->only(['id', 'name', 'username']),
                'year' => $year,
                'total_reports' => $reports->count(),
                'reports' => $reports->toArray(),
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

        foreach ($reports as $report) {
            $pdfPath = storage_path('app/public/reports/report-'.$report->id.'.pdf');
            if (is_file($pdfPath)) {
                $zip->addFile($pdfPath, 'pdf/report-'.$report->id.'.pdf');
            }
        }

        $zip->close();

        // Operasi destruktif: pastikan arsip benar-benar tertulis & konsisten sebelum
        // menghapus laporan dari database, supaya data tidak hilang kalau ZIP korup
        // (misalnya disk penuh saat menulis arsip).
        $verify = new \ZipArchive;
        $verified = $verify->open($absolutePath, \ZipArchive::CHECKCONS) === true
            && $verify->numFiles > 0;
        if ($verified) {
            $verify->close();
        }

        if (! $verified || ! is_file($absolutePath) || filesize($absolutePath) === 0) {
            return back()->with('error', 'Arsip backup tahunan gagal diverifikasi. Laporan TIDAK dihapus dari sistem, silakan coba lagi.');
        }

        $total = $reports->count();

        DB::transaction(function () use ($reports): void {
            foreach ($reports as $report) {
                $pdfPath = storage_path('app/public/reports/report-'.$report->id.'.pdf');
                if (is_file($pdfPath)) {
                    @unlink($pdfPath);
                }

                // Tabel detail terhapus otomatis lewat foreign key cascadeOnDelete.
                $report->delete();
            }
        });

        $this->recordActivity($request, 'backup', 'Backup tahunan '.$year.': '.$total.' laporan diarsipkan ke '.$fileName.' lalu dihapus dari sistem.', [
            'year' => $year,
            'file' => $fileName,
            'total_reports' => $total,
        ]);

        return back()->with('success', 'Backup laporan tahun '.$year.' berhasil ('.$total.' laporan) dan dihapus dari sistem untuk meringankan penyimpanan. Silakan unduh "'.$fileName.'" lalu simpan ke penyimpanan lokal Anda.');
    }

    /**
     * Tahun laporan terlama yang sudah lewat (lebih kecil dari tahun berjalan) dan
     * masih tersimpan. Mengembalikan null bila tidak ada — backup tahunan hanya
     * berlaku untuk laporan tahun sebelumnya, bukan tahun yang sedang berjalan.
     */
    private function annualBackupYear(): ?int
    {
        $currentYear = (int) now()->year;

        $oldest = DailyReport::query()
            ->whereNotNull('report_date')
            ->whereYear('report_date', '<', $currentYear)
            ->orderBy('report_date')
            ->value('report_date');

        return $oldest !== null ? (int) Carbon::parse($oldest)->year : null;
    }

    public function updateBackupSchedule(Request $request)
    {
        $data = $request->validate([
            'frequency' => ['required', 'in:Harian,Mingguan,Bulanan'],
            'time' => ['required', 'date_format:H:i'],
            'retention' => ['required', 'in:14 Hari,30 Hari,60 Hari,90 Hari'],
            'target' => ['required', 'in:Local Storage,External Drive,Cloud Storage'],
        ]);

        Storage::disk('local')->put('admin-backups/schedule.json', json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        $this->recordActivity($request, 'update', 'Memperbarui jadwal backup sistem');

        return back()->with('success', 'Jadwal backup berhasil diperbarui.');
    }

    public function downloadBackup(Request $request, string $file)
    {
        $path = $this->backupPath($file);
        abort_unless(Storage::disk('local')->exists($path), 404);

        $this->recordActivity($request, 'backup', 'Mengunduh backup '.basename($path));

        return Storage::disk('local')->download($path, basename($path));
    }

    public function destroyBackup(Request $request, string $file)
    {
        $path = $this->backupPath($file);
        abort_unless(Storage::disk('local')->exists($path), 404);

        Storage::disk('local')->delete($path);
        $this->recordActivity($request, 'delete', 'Menghapus backup '.basename($path));

        return back()->with('success', 'File backup berhasil dihapus.');
    }

    /**
     * Restore TIDAK dijalankan otomatis. File snapshot hanya berisi sebagian data
     * (mis. laporan dibatasi 500 terbaru, tanpa seluruh relasi anak), sehingga
     * memulihkannya otomatis berisiko menimpa/merusak data yang sedang berjalan.
     * Aksi ini hanya mencatat permintaan; pemulihan tetap dilakukan manual oleh
     * admin server terhadap database.
     */
    public function restoreBackup(Request $request, string $file)
    {
        $path = $this->backupPath($file);
        abort_unless(Storage::disk('local')->exists($path), 404);

        $this->recordActivity($request, 'backup', 'Mencatat permintaan restore dari '.basename($path));

        return back()->with('error', 'Restore tidak dijalankan otomatis oleh sistem. Permintaan sudah dicatat di log; pemulihan data harus dilakukan manual oleh admin server (unduh file lalu impor ke database).');
    }

    public function storeHelpTicket(Request $request)
    {
        $data = $request->validate([
            'category' => ['required', 'string', 'max:80'],
            'priority' => ['required', 'string', 'max:30'],
            'title' => ['required', 'string', 'max:160'],
            'description' => ['required', 'string', 'max:1000'],
        ]);

        $this->recordActivity($request, 'support', 'Membuat tiket bantuan: '.$data['title'], [
            'category' => $data['category'],
            'priority' => $data['priority'],
        ]);

        return back()->with('success', 'Tiket bantuan berhasil dicatat.');
    }

    private function validateUser(Request $request, ?User $user, bool $isCreate): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', Rule::unique('users', 'username')->ignore($user?->id)],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user?->id)],
            'role_id' => ['required', 'exists:roles,id'],
            'group' => ['nullable', 'string', 'max:20'],
            'status' => ['nullable', 'in:aktif,nonaktif'],
            'password' => [$isCreate ? 'required' : 'nullable', 'string', 'min:6'],
            'signature' => ['nullable', 'file', 'mimes:png', 'mimetypes:image/png', 'max:2048'],
        ]);
    }

    private function userPayload(array $data, bool $isCreate): array
    {
        $payload = [
            'name' => $data['name'],
            'username' => $data['username'],
            'email' => $data['email'] ?? $this->generatedEmail($data['username']),
            'role_id' => $data['role_id'],
            'group' => $this->normalizeGroup($data['group'] ?? null),
            'status' => $data['status'] ?? 'aktif',
        ];

        if ($isCreate || ! empty($data['password'])) {
            $payload['password'] = $data['password'];
        }

        return $payload;
    }

    private function storeSignature(Request $request, string $username): ?string
    {
        if (! $request->hasFile('signature')) {
            return null;
        }

        $directory = public_path('signatures');

        if (! File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $filename = sprintf(
            'signature-%s-%s-%s.png',
            Str::slug($username) ?: 'user',
            now()->format('YmdHis'),
            Str::lower(Str::random(6))
        );

        $request->file('signature')->move($directory, $filename);

        return 'signatures/'.$filename;
    }

    private function validateTruck(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'plate' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:255'],
            'desc' => ['nullable', 'string', 'max:255'],
        ]);

        return [
            'name' => $data['name'],
            'plate_number' => $data['plate'] ?? null,
            'description' => $data['description'] ?? $data['desc'] ?? null,
        ];
    }

    private function shellData(Request $request): array
    {
        $user = $request->user();

        return [
            'greeting' => 'Selamat datang, '.($user?->name ?? 'Admin'),
            'role' => Role::displayName($user?->role?->name ?? Role::ADMIN),
        ];
    }

    private function dashboardCards(): array
    {
        $backupFiles = $this->backupFiles();
        $usedBytes = collect($backupFiles)->sum('bytes');
        $lastBackup = collect($backupFiles)->first();
        $securityEventsToday = AdminActivityLog::where('type', 'security')
            ->whereDate('created_at', Carbon::today())
            ->count();

        return [
            ['label' => 'Total Pengguna Aktif', 'value' => (string) User::where('status', 'aktif')->count(), 'icon' => 'fi fi-sr-user', 'color' => 'blue', 'success' => false],
            ['label' => 'Kapasitas Storage Terpakai', 'value' => min(100, (int) round(($usedBytes / (30 * 1024 * 1024 * 1024)) * 100)).'%', 'icon' => 'fi fi-sr-database', 'color' => 'cyan', 'success' => false],
            ['label' => 'Status Backup Terakhir', 'value' => $lastBackup ? $lastBackup['status_label'] : 'Belum Ada', 'icon' => 'fi fi-sr-cloud-upload', 'color' => $lastBackup ? 'green' : 'orange', 'success' => (bool) $lastBackup],
            ['label' => 'Kejadian Keamanan Hari Ini', 'value' => (string) $securityEventsToday, 'icon' => 'fi fi-sr-shield-exclamation', 'color' => $securityEventsToday > 0 ? 'red' : 'green', 'success' => false],
        ];
    }

    private function archiveCards(): array
    {
        $counts = $this->archiveTotalCounts();

        return [
            ['label' => 'Laporan Hari Ini', 'value' => (string) $counts['today'], 'icon' => 'fi fi-sr-calendar', 'color' => 'green'],
            ['label' => 'Laporan Pending', 'value' => (string) $counts['pending'], 'icon' => 'fi fi-sr-document', 'color' => 'orange'],
            ['label' => 'Laporan Bulan Ini', 'value' => (string) $counts['month'], 'icon' => 'fi fi-sr-folder', 'color' => 'cyan'],
            ['label' => 'Total Laporan', 'value' => (string) $counts['total'], 'icon' => 'fi fi-sr-book-alt', 'color' => 'blue'],
        ];
    }

    private function latestAuditCards(): array
    {
        $logs = AdminActivityLog::latest()->limit(4)->get();

        if ($logs->isEmpty()) {
            return [
                ['time' => now()->format('H:i'), 'type' => 'blue', 'text' => 'Admin dashboard siap digunakan dengan data sistem aktif.'],
            ];
        }

        return $logs->map(fn (AdminActivityLog $log): array => [
            'time' => $log->created_at?->format('H:i') ?? '-',
            'type' => $this->activityTone($log->type),
            'text' => e($log->description),
        ])->all();
    }

    private function activityRow(AdminActivityLog $activity): array
    {
        $roleName = $activity->user?->role?->name;
        $properties = $activity->properties ?? [];
        $attemptedLogin = $properties['attempted_login'] ?? null;

        return [
            'user' => $activity->user?->name ?? ($attemptedLogin ? 'Unknown Login' : 'System'),
            'sub' => $activity->user
                ? 'Role: '.Role::displayName($roleName)
                : ($attemptedLogin ? 'Username: '.$attemptedLogin : 'Aktivitas otomatis'),
            'unknown' => ! $activity->user,
            'time' => $activity->created_at?->locale('id')->translatedFormat('d F Y, H:i') ?? '-',
            'type' => $this->activityTone($activity->type),
            'type_label' => $this->activityLabel($activity->type),
            'desc' => e($activity->description),
            'ip' => $activity->ip_address ?: '-',
        ];
    }

    private function archiveRow(DailyReport $report, int $number): array
    {
        $shift = $this->shiftMeta($report->shift);
        $status = $this->statusMeta($report->status);
        $date = $report->report_date ?: $report->created_at;

        return [
            'no' => $number,
            'title' => 'Laporan Operasi Harian',
            'id' => $this->documentId($report),
            'raw_id' => $report->id,
            'date' => $date ? Carbon::parse($date)->locale('id')->translatedFormat('d F Y') : '-',
            'regu' => $this->displayGroup($report->group_name),
            'shift' => $shift['class'],
            'shift_label' => $shift['label'],
            'shift_icon' => $shift['icon'],
            'status' => $status['class'],
            'status_label' => $status['label'],
            'summary' => $this->documentId($report).' - '.($date ? Carbon::parse($date)->locale('id')->translatedFormat('d F Y') : '-'),
            'view_url' => route('admin.reports.show', $report),
            'download_url' => route('admin.reports.download', $report),
            'destroy_url' => route('admin.reports.destroy', $report),
            'search' => $this->archiveRowSearchBlob($report, $shift['label'], $status['label'], $date),
        ];
    }

    /**
     * Teks pencarian per baris untuk filter instan di sisi klien (halaman ini saja).
     * Mengikuti perilaku dashboard manajer: metadata + isi payload laporan.
     */
    private function archiveRowSearchBlob(DailyReport $report, string $shiftLabel, string $statusLabel, mixed $date): string
    {
        $parts = array_merge([
            'Laporan Operasi Harian',
            $this->documentId($report),
            $report->report_date?->format('Y-m-d'),
            $date ? Carbon::parse($date)->locale('id')->translatedFormat('d F Y') : null,
            $shiftLabel,
            $statusLabel,
            $this->displayGroup($report->group_name),
            'Regu '.strtoupper((string) $report->received_by_group),
            $report->creator?->name,
            $report->approver?->name,
        ], $this->flattenSearchable($report->payload));

        return Str::lower(
            collect($parts)
                ->filter(fn ($value) => filled($value))
                ->map(fn ($value) => trim(strip_tags((string) $value)))
                ->implode(' ')
        );
    }

    private function flattenSearchable(mixed $value): array
    {
        if (is_array($value)) {
            $result = [];

            foreach ($value as $key => $item) {
                if (is_string($key)) {
                    $result[] = str_replace(['_', '-'], ' ', $key);
                }

                array_push($result, ...$this->flattenSearchable($item));
            }

            return $result;
        }

        return is_scalar($value) && filled($value) ? [(string) $value] : [];
    }

    private function archiveSuggestionItem(DailyReport $report): array
    {
        $shift = $this->shiftMeta($report->shift);
        $status = $this->statusMeta($report->status);
        $date = $report->report_date ?: $report->created_at;

        return [
            'id' => $report->id,
            'document_id' => $this->documentId($report),
            'title' => 'Laporan Operasi Harian',
            'report_date' => $date ? Carbon::parse($date)->locale('id')->translatedFormat('d F Y') : '-',
            'updated_diff' => $report->updated_at ? Carbon::parse($report->updated_at)->locale('id')->diffForHumans() : '-',
            'shift_label' => $shift['label'],
            'shift_class' => $shift['class'],
            'status_label' => $status['label'],
            'status_class' => $status['class'],
            'group_from' => strtoupper((string) $report->group_name) ?: '-',
            'group_to' => strtoupper((string) $report->received_by_group) ?: '-',
            'approver' => $report->approver?->name ?? '-',
            'view_url' => route('admin.reports.show', $report),
            'download_url' => route('admin.reports.download', $report),
        ];
    }

    private function userRow(User $user, int $number): array
    {
        $roleName = $user->role?->name;
        $status = $user->status === 'nonaktif' ? 'nonaktif' : 'aktif';

        return [
            'no' => $number,
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'role' => Role::displayName($roleName),
            'role_id' => $user->role_id,
            'regu' => $this->displayGroup($user->group),
            'group_value' => $this->normalizeGroup($user->group) ?: 'Kantor',
            'status' => $status,
            'status_label' => $status === 'aktif' ? 'Aktif' : 'Non-Aktif',
            'update_url' => route('admin.users.update', $user),
            'status_url' => route('admin.users.status', $user),
            'destroy_url' => route('admin.users.destroy', $user),
            'signature_path' => $user->signature_path,
            'signature_url' => $user->signature_path ? asset($user->signature_path) : '',
        ];
    }

    private function backupFiles(): array
    {
        Storage::disk('local')->makeDirectory('admin-backups');

        return collect(Storage::disk('local')->files('admin-backups'))
            ->filter(fn (string $path): bool => (Str::endsWith($path, '.json') || Str::endsWith($path, '.zip')) && basename($path) !== 'schedule.json')
            ->map(function (string $path): array {
                $modified = Carbon::createFromTimestamp(Storage::disk('local')->lastModified($path));
                $file = basename($path);
                $isAnnual = Str::endsWith($file, '.zip');

                return [
                    'name' => $file,
                    'meta' => $isAnnual ? 'Arsip laporan tahunan' : 'Snapshot data aplikasi',
                    'date' => $modified->locale('id')->translatedFormat('d F Y, H:i'),
                    'date_short' => $modified->locale('id')->translatedFormat('d M Y'),
                    'bytes' => Storage::disk('local')->size($path),
                    'size' => $this->formatBytes(Storage::disk('local')->size($path)),
                    'type' => $isAnnual ? 'Tahunan' : (Str::contains($file, 'manual') ? 'Manual' : 'Otomatis'),
                    'status' => 'success',
                    'status_label' => 'Berhasil',
                    'download_url' => route('admin.backup.download', $file),
                    'restore_url' => route('admin.backup.restore', $file),
                    'destroy_url' => route('admin.backup.destroy', $file),
                ];
            })
            ->sortByDesc('name')
            ->values()
            ->all();
    }

    private function backupSchedule(): array
    {
        $default = [
            'frequency' => 'Harian',
            'time' => '02:00',
            'retention' => '30 Hari',
            'target' => 'Local Storage',
        ];

        if (! Storage::disk('local')->exists('admin-backups/schedule.json')) {
            return $default;
        }

        $decoded = json_decode(Storage::disk('local')->get('admin-backups/schedule.json'), true);

        return array_merge($default, is_array($decoded) ? $decoded : []);
    }

    private function backupPath(string $file): string
    {
        $file = basename($file);

        abort_if($file === '' || str_contains($file, '..') || ! (Str::endsWith($file, '.json') || Str::endsWith($file, '.zip')) || $file === 'schedule.json', 404);

        return 'admin-backups/'.$file;
    }

    private function formatBytes(int|float $bytes): string
    {
        if ($bytes >= 1024 * 1024 * 1024) {
            return round($bytes / 1024 / 1024 / 1024, 1).' GB';
        }

        if ($bytes >= 1024 * 1024) {
            return round($bytes / 1024 / 1024, 1).' MB';
        }

        if ($bytes >= 1024) {
            return round($bytes / 1024, 1).' KB';
        }

        return $bytes.' B';
    }

    private function recordActivity(Request $request, string $type, string $description, array $properties = []): void
    {
        AdminActivityLog::create([
            'user_id' => $request->user()?->id,
            'type' => $type,
            'description' => $description,
            'ip_address' => $request->ip(),
            'properties' => $properties ?: null,
        ]);
    }

    private function activityTone(string $type): string
    {
        return match ($type) {
            'delete', 'error', 'security' => 'red',
            'backup', 'support' => 'green',
            'login' => 'blue',
            default => 'blue',
        };
    }

    private function activityLabel(string $type): string
    {
        return match ($type) {
            'delete' => 'Hapus',
            'backup' => 'Backup',
            'support' => 'Bantuan',
            'login' => 'Login',
            'security' => 'Keamanan',
            'error' => 'Error',
            default => 'Update',
        };
    }

    private function generatedEmail(string $username): string
    {
        return Str::slug($username, '.').'@kss.local';
    }

    /**
     * Terapkan filter group untuk master karyawan. Token berasal dari dropdown
     * filter dan dipetakan ke nilai group_name aktual di database (yang bisa
     * tersimpan sebagai 'Group A' maupun 'A', dsb).
     */
    private function applyEmployeeGroupFilter(Builder $query, string $token): Builder
    {
        return match ($token) {
            'kantor' => $query->whereNull('group_name'),
            'bengkel' => $query->where('group_name', 'Bengkel'),
            'Relief 1', 'Relief 2' => $query->where('group_name', $token),
            'A', 'B', 'C', 'D' => $query->whereIn('group_name', ['Group '.$token, $token]),
            'OP7 A', 'OP7 B', 'OP7 C', 'OP7 D' => $query->whereIn('group_name', ['OP.7 Group '.substr($token, -1), $token]),
            default => $query,
        };
    }

    /**
     * Daftar nilai division yang cocok untuk filter (mengikutkan 'both').
     */
    private function divisionFilterValues(string $label): array
    {
        return match ($label) {
            'Pemeliharaan' => [MasterEmployee::DIVISION_MAINTENANCE, MasterEmployee::DIVISION_BOTH],
            'Safety (Coming Soon)', 'Safety' => [MasterEmployee::DIVISION_SAFETY, MasterEmployee::DIVISION_BOTH],
            'Office' => [MasterEmployee::DIVISION_OFFICE],
            default => [MasterEmployee::DIVISION_OPERATIONAL, MasterEmployee::DIVISION_BOTH],
        };
    }

    private function normalizeGroup(?string $group): ?string
    {
        $value = trim((string) $group);

        if ($value === '' || in_array(Str::lower($value), ['-', 'kantor', 'tidak ada'], true)) {
            return null;
        }

        if (Str::lower($value) === 'bengkel') {
            return 'Bengkel';
        }

        if (preg_match('/^relief\s*([12])$/i', $value, $matches)) {
            return 'Relief '.$matches[1];
        }

        if (preg_match('/^op\.?\s*7\s*(?:group|grup|regu)?\s*([a-d])$/i', $value, $matches)) {
            return 'OP.7 Group '.Str::upper($matches[1]);
        }

        $value = preg_replace('/^(?:regu|group|grup)\s+/i', '', $value) ?? $value;

        return strtoupper($value);
    }

    private function nullableUpper(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : Str::upper($value);
    }

    private function nullableString(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function nullableSelectValue(?string $value): ?string
    {
        $value = $this->nullableString($value);

        return $value === '-' ? null : $value;
    }

    private function normalizeDivision(?string $division): string
    {
        $value = Str::lower(trim((string) $division));

        return match ($value) {
            'pemeliharaan' => MasterEmployee::DIVISION_MAINTENANCE,
            'safety', 'safety (coming soon)' => MasterEmployee::DIVISION_SAFETY,
            'office', 'kantor' => MasterEmployee::DIVISION_OFFICE,
            'keduanya', 'both' => MasterEmployee::DIVISION_BOTH,
            default => MasterEmployee::DIVISION_OPERATIONAL,
        };
    }

    private function normalizeWorkTime(?string $workTime): ?string
    {
        $value = Str::lower(trim((string) $workTime));

        return match ($value) {
            'non shift' => 'Non Shift',
            'shift' => 'Shift',
            'relief' => 'Relief',
            default => null,
        };
    }

    private function displayDivision(?string $division): string
    {
        return match ($division) {
            MasterEmployee::DIVISION_MAINTENANCE => 'Pemeliharaan',
            MasterEmployee::DIVISION_SAFETY => 'Safety (Coming Soon)',
            MasterEmployee::DIVISION_OFFICE => 'Office',
            MasterEmployee::DIVISION_BOTH => 'Keduanya',
            default => 'Operasional',
        };
    }

    private function displayGroup(?string $group): string
    {
        $value = $this->normalizeGroup($group);

        if ($value === 'Bengkel') {
            return 'Bengkel';
        }

        if ($value && preg_match('/^Relief ([12])$/', $value, $matches)) {
            return 'Relief '.$matches[1];
        }

        if ($value && preg_match('/^OP\.7 Group ([A-D])$/', $value, $matches)) {
            return 'OP7 '.$matches[1];
        }

        return $value ? 'Regu '.$value : 'Kantor';
    }

    private function helpTopics(): array
    {
        return [
            ['title' => 'Akun & Role', 'text' => 'Status akun, role pengguna, dan pembagian akses.', 'icon' => 'fi fi-sr-user', 'color' => ''],
            ['title' => 'Laporan Operasional', 'text' => 'Alur laporan, arsip, tanda tangan, dan export dokumen.', 'icon' => 'fi fi-sr-document', 'color' => 'green'],
            ['title' => 'Backup Sistem', 'text' => 'Jadwal cadangan, restore, dan validasi file backup.', 'icon' => 'fi fi-sr-cloud-upload', 'color' => 'orange'],
            ['title' => 'Master Data', 'text' => 'Data karyawan, unit, truck, dan inventaris sistem.', 'icon' => 'fi fi-sr-database', 'color' => ''],
            ['title' => 'Audit Log', 'text' => 'Rekam aktivitas pengguna dan kejadian keamanan.', 'icon' => 'fi fi-sr-document-signed', 'color' => 'red'],
            ['title' => 'Integrasi File', 'text' => 'Lampiran laporan, tanda tangan, dan file export.', 'icon' => 'fi fi-sr-folder', 'color' => 'green'],
        ];
    }

    private function helpFaqs(): array
    {
        return [
            ['q' => 'Bagaimana admin masuk ke dashboard?', 'a' => 'Gunakan akun role Admin. Seeder bawaan menyiapkan username admin dengan password password untuk pengujian lokal.'],
            ['q' => 'Apa yang perlu dicek jika backup gagal?', 'a' => 'Periksa kapasitas storage, izin tulis folder penyimpanan, koneksi database, dan log sistem pada waktu eksekusi backup.'],
            ['q' => 'Bagaimana status pengguna dinonaktifkan?', 'a' => 'Status pengguna dapat diubah lewat toggle pada tabel Kelola Pengguna. Perubahan dicatat ke log aktivitas admin.'],
            ['q' => 'Data master apa saja yang perlu dijaga?', 'a' => 'Data karyawan, unit, truck, dan inventaris menjadi referensi laporan. Perubahan data master perlu mempertimbangkan riwayat laporan yang sudah dibuat.'],
        ];
    }
}
