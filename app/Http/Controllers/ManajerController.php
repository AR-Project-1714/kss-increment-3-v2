<?php

namespace App\Http\Controllers;

use App\Enums\MaintenanceStatus;
use App\Enums\ReportStatus;
use App\Enums\SafetyStatus;
use App\Http\Controllers\Concerns\BuildsDivisionArchive;
use App\Http\Controllers\Concerns\ResolvesMaintenanceMeta;
use App\Http\Controllers\Concerns\ResolvesReportMeta;
use App\Http\Controllers\Concerns\ResolvesSafetyMeta;
use App\Http\Controllers\Concerns\SearchesReports;
use App\Models\DailyReport;
use App\Models\MaintenanceReport;
use App\Models\Role;
use App\Models\SafetyReport;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class ManajerController extends Controller
{
    use BuildsDivisionArchive;
    use ResolvesMaintenanceMeta;
    use ResolvesReportMeta;
    use ResolvesSafetyMeta;
    use SearchesReports;

    public function index(Request $request)
    {
        $this->authorizeManagementAccess($request);

        $incomingReports = DailyReport::query()
            ->select($this->incomingReportColumns())
            ->where('status', ReportStatus::Acknowledged)
            ->latest('received_at')
            ->latest('updated_at')
            ->get();

        // Laporan pemeliharaan masuk: alur submitted -> approved (tanpa acknowledged).
        $incomingMaintenanceReports = MaintenanceReport::with('creator:id,name')
            ->where('status', MaintenanceStatus::Submitted)
            ->latest('submitted_at')
            ->latest('updated_at')
            ->get();

        // Laporan K3/Safety masuk: alur submitted -> approved (tanpa acknowledged).
        $incomingSafetyReports = SafetyReport::with('creator:id,name')
            ->where('status', SafetyStatus::Submitted)
            ->latest('submitted_at')
            ->latest('updated_at')
            ->get();

        $divisionCounts = [
            'all' => $incomingReports->count() + $incomingMaintenanceReports->count() + $incomingSafetyReports->count(),
            'operasional' => $incomingReports->count(),
            'pemeliharaan' => $incomingMaintenanceReports->count(),
            'safety' => $incomingSafetyReports->count(),
        ];

        return view('manajer.index', [
            'stats' => $this->dashboardStats(),
            'incomingReports' => $incomingReports,
            'incomingMaintenanceReports' => $incomingMaintenanceReports,
            'incomingSafetyReports' => $incomingSafetyReports,
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
        ], 'manajer');

        return view('manajer.archive', [
            'stats' => $this->archiveStats(),
            'reports' => $reports,
            'archiveSearch' => $archiveSearch,
            'sort' => $sort,
            'selectedDate' => $selectedDate,
            'selectedGroup' => $selectedGroup,
            'selectedShift' => $selectedShift,
            'selectedDivision' => $selectedDivision,
            'selectedStatus' => $selectedStatus,
        ]);
    }

    public function archiveSuggestions(Request $request)
    {
        $this->authorizeManagementAccess($request);

        $keyword = trim((string) $request->input('q', ''));

        $items = $this->buildDivisionArchiveSuggestions($keyword, 'manajer');

        return response()->json([
            'keyword' => $keyword,
            'total' => $items->count(),
            'items' => $items,
        ]);
    }

    public function approve(Request $request, DailyReport $report)
    {
        $this->authorizeManagementAccess($request);

        if ($report->status !== ReportStatus::Acknowledged) {
            return back()->with('error', 'Status laporan tidak valid untuk tanda tangan manajer.');
        }

        try {
            $report->update([
                'status' => ReportStatus::Approved,
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
            'backUrl' => route('manajer.index'),
            'pdfUrl' => route('manajer.reports.download', $report),
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
            $pdf->setPaper([0, 0, 612.00, 936.00], 'portrait');
            $pdf->setOption('isRemoteEnabled', true);

            return $pdf->download($this->reportFileName($report, 'pdf'));
        }

        return view('report-ops.viewpdf', [
            'report' => $this->loadReport($report),
            'isPdf' => false,
            'backUrl' => route('manajer.index'),
            'pdfUrl' => null,
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

    // ============================================================
    // Persetujuan laporan pemeliharaan (submitted -> approved)
    // ============================================================

    public function showMaintenance(Request $request, MaintenanceReport $report)
    {
        $this->authorizeManagementAccess($request);
        abort_unless(in_array($report->status, [MaintenanceStatus::Submitted, MaintenanceStatus::Approved], true), 404);

        return view('pemeliharaan.viewpdf', [
            'report'  => $this->loadMaintenanceReport($report),
            'isPdf'   => false,
            'backUrl' => route('manajer.index'),
            'pdfUrl'  => route('manajer.pemeliharaan.download', $report),
        ]);
    }

    public function approveMaintenance(Request $request, MaintenanceReport $report)
    {
        $this->authorizeManagementAccess($request);

        if ($report->status !== MaintenanceStatus::Submitted) {
            return back()->with('error', 'Status laporan pemeliharaan tidak valid untuk ditandatangani.');
        }

        try {
            $report->update([
                'status'      => MaintenanceStatus::Approved,
                'approved_by' => $request->user()->id,
                'approved_at' => now(),
            ]);

            $this->forgetManagerStatsCache();
            $this->cacheApprovedMaintenancePdf($report->fresh());
        } catch (Throwable $exception) {
            Log::error('Gagal menyetujui laporan pemeliharaan.', [
                'report_id' => $report->id,
                'user_id'   => $request->user()?->id,
                'message'   => $exception->getMessage(),
            ]);

            return back()->with('error', 'Laporan pemeliharaan belum bisa ditandatangani. Silakan coba lagi.');
        }

        return redirect()->route('manajer.index')->with('success', 'Laporan pemeliharaan berhasil diarsipkan.');
    }

    public function downloadMaintenance(Request $request, MaintenanceReport $report)
    {
        $this->authorizeManagementAccess($request);
        abort_unless(in_array($report->status, [MaintenanceStatus::Submitted, MaintenanceStatus::Approved], true), 404);

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
            'backUrl' => route('manajer.index'),
            'pdfUrl'  => null,
        ]);
    }

    public function destroyMaintenance(Request $request, MaintenanceReport $report)
    {
        $this->authorizeManagementAccess($request);

        if (! in_array($report->status, $this->maintenanceArchiveStatuses(), true)) {
            return back()->with('error', 'Hanya laporan pemeliharaan pada arsip yang bisa dihapus dari menu ini.');
        }

        $path = storage_path('app/public/maintenance-reports/maintenance-report-'.$report->id.'.pdf');

        if (is_file($path)) {
            @unlink($path);
        }

        $report->delete();
        $this->forgetManagerStatsCache();

        return back()->with('success', 'Arsip laporan pemeliharaan berhasil dihapus.');
    }

    // ============================================================
    // Persetujuan laporan K3/Safety (submitted -> approved)
    // ============================================================

    public function showSafety(Request $request, SafetyReport $report)
    {
        $this->authorizeManagementAccess($request);
        abort_unless(in_array($report->status, [SafetyStatus::Submitted, SafetyStatus::Approved], true), 404);

        return view('report-safety.viewpdf', [
            'report'  => $this->loadSafetyReport($report),
            'isPdf'   => false,
            'backUrl' => route('manajer.index'),
            'pdfUrl'  => route('manajer.safety.download', $report),
        ]);
    }

    public function approveSafety(Request $request, SafetyReport $report)
    {
        $this->authorizeManagementAccess($request);

        if ($report->status !== SafetyStatus::Submitted) {
            return back()->with('error', 'Status laporan K3 tidak valid untuk ditandatangani.');
        }

        try {
            $report->update([
                'status'                  => SafetyStatus::Approved,
                'approved_by'             => $request->user()->id,
                'approved_at'             => now(),
                'approver_signature_path' => $request->user()->signature_path,
            ]);

            $this->forgetManagerStatsCache();
            $this->cacheApprovedSafetyPdf($report->fresh());
        } catch (Throwable $exception) {
            Log::error('Gagal menyetujui laporan K3.', [
                'report_id' => $report->id,
                'user_id'   => $request->user()?->id,
                'message'   => $exception->getMessage(),
            ]);

            return back()->with('error', 'Laporan K3 belum bisa ditandatangani. Silakan coba lagi.');
        }

        return redirect()->route('manajer.index')->with('success', 'Laporan K3 berhasil diarsipkan.');
    }

    public function downloadSafety(Request $request, SafetyReport $report)
    {
        $this->authorizeManagementAccess($request);
        abort_unless(in_array($report->status, [SafetyStatus::Submitted, SafetyStatus::Approved], true), 404);

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
            'backUrl' => route('manajer.index'),
            'pdfUrl'  => null,
        ]);
    }

    public function destroySafety(Request $request, SafetyReport $report)
    {
        $this->authorizeManagementAccess($request);

        if (! in_array($report->status, [SafetyStatus::Submitted, SafetyStatus::Approved], true)) {
            return back()->with('error', 'Hanya laporan K3 pada arsip yang bisa dihapus dari menu ini.');
        }

        $path = storage_path('app/public/safety-reports/safety-report-'.$report->id.'.pdf');

        if (is_file($path)) {
            @unlink($path);
        }

        $report->delete();
        $this->forgetManagerStatsCache();

        return back()->with('success', 'Arsip laporan K3 berhasil dihapus.');
    }

    private function dashboardStats(): array
    {
        $activeStatuses = [ReportStatus::Submitted, ReportStatus::Acknowledged, ReportStatus::Approved];
        $today = Carbon::today();
        $now = Carbon::now();

        return Cache::remember($this->managerStatsCacheKey('dashboard'), now()->addSeconds(60), function () use ($activeStatuses, $today, $now): array {
            return [
                'todayReports' => DailyReport::whereIn('status', $activeStatuses)
                    ->whereDate('report_date', $today)
                    ->count(),
                'pendingReports' => DailyReport::where('status', ReportStatus::Acknowledged)->count(),
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
        return Cache::remember($this->managerStatsCacheKey('archive'), now()->addSeconds(60), function (): array {
            $counts = $this->archiveTotalCounts();

            return [
                'todayReports' => $counts['today'],
                'pendingReports' => $counts['pending'],
                'monthlyReports' => $counts['month'],
                'totalReports' => $counts['total'],
            ];
        });
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
            $pdf->setPaper([0, 0, 612.00, 936.00], 'portrait');
            $pdf->setOption('isRemoteEnabled', true);
            $pdf->save($storagePath.'/report-'.$report->id.'.pdf');
        } catch (Throwable $exception) {
            Log::error('Gagal menyimpan PDF arsip laporan manajer.', [
                'report_id' => $report->id,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    private function cacheApprovedMaintenancePdf(MaintenanceReport $report): void
    {
        if (! class_exists(Pdf::class)) {
            return;
        }

        try {
            $storagePath = storage_path('app/public/maintenance-reports');

            if (! is_dir($storagePath)) {
                mkdir($storagePath, 0755, true);
            }

            $pdf = Pdf::loadView('pemeliharaan.pdf', [
                'report' => $this->loadMaintenanceReport($report),
                'isPdf'  => true,
            ]);
            $pdf->setPaper([0, 0, 612.00, 936.00], 'portrait');
            $pdf->setOption('isRemoteEnabled', true);
            $pdf->save($storagePath.'/maintenance-report-'.$report->id.'.pdf');
        } catch (Throwable $exception) {
            Log::error('Gagal menyimpan PDF arsip laporan pemeliharaan.', [
                'report_id' => $report->id,
                'message'   => $exception->getMessage(),
            ]);
        }
    }

    private function cacheApprovedSafetyPdf(SafetyReport $report): void
    {
        if (! class_exists(Pdf::class)) {
            return;
        }

        try {
            $storagePath = storage_path('app/public/safety-reports');

            if (! is_dir($storagePath)) {
                mkdir($storagePath, 0755, true);
            }

            $pdf = Pdf::loadView('report-safety.pdf', [
                'report' => $this->loadSafetyReport($report),
                'isPdf'  => true,
            ]);
            $pdf->setPaper([0, 0, 612.00, 936.00], 'portrait');
            $pdf->setOption('isRemoteEnabled', true);
            $pdf->save($storagePath.'/safety-report-'.$report->id.'.pdf');
        } catch (Throwable $exception) {
            Log::error('Gagal menyimpan PDF arsip laporan K3.', [
                'report_id' => $report->id,
                'message'   => $exception->getMessage(),
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
            'view_url' => route('manajer.reports.show', $report),
            'download_url' => route('manajer.reports.download', $report),
        ];
    }

    private function authorizeManagementAccess(Request $request): void
    {
        $user = $request->user();

        abort_unless($user && Role::hasManagementAccess($user->role->name ?? null), 403);
    }
}
