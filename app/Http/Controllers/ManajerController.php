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
use App\Models\AdminActivityLog;
use App\Models\DailyReport;
use App\Models\MaintenanceReport;
use App\Models\Role;
use App\Models\SafetyReport;
use App\Services\ActivityDetailExportService;
use App\Services\ArchiveBundleService;
use App\Services\ArchiveMetricsService;
use App\Services\OperationalPerformanceService;
use App\Services\PerformanceExportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Throwable;

class ManajerController extends Controller
{
    use BuildsDivisionArchive;
    use ResolvesMaintenanceMeta;
    use ResolvesReportMeta;
    use ResolvesSafetyMeta;
    use SearchesReports;

    /**
     * Preset periode pada toolbar Kinerja Operasi & Rincian Kegiatan.
     * Urutannya menentukan urutan tombol.
     */
    private const PERIOD_PRESETS = [
        'tahun-berjalan' => 'Januari–Sekarang',
        'tahun-lalu' => 'Tahun Lalu',
        'bulan-ini' => 'Bulan Ini',
        'bulan-lalu' => 'Bulan Lalu',
        '3-bulan' => '3 Bulan',
    ];

    /** Kinerja Operasi dibuka pada capaian tahun berjalan sejak Januari. */
    private const PERFORMANCE_DEFAULT_PRESET = 'tahun-berjalan';

    /** Rincian Kegiatan juga dibuka pada capaian tahun berjalan sejak Januari. */
    private const ACTIVITY_DEFAULT_PRESET = 'tahun-berjalan';

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

        $dashboardKpi = $this->dashboardKpi();

        return view('manajer.index', [
            'stats' => $this->dashboardStats(),
            'kpi' => $dashboardKpi,
            'operationalSummary' => $dashboardKpi['activitySummary'] ?? [],
            'incomingReports' => $incomingReports,
            'incomingMaintenanceReports' => $incomingMaintenanceReports,
            'incomingSafetyReports' => $incomingSafetyReports,
            'divisionCounts' => $divisionCounts,
        ]);
    }

    public function archive(Request $request)
    {
        $this->authorizeManagementAccess($request);

        $filters = $this->archiveFiltersFromRequest($request);

        $reports = $this->buildDivisionArchivePaginator($request, $filters, 'manajer');

        return view('manajer.archive', [
            'stats' => app(ArchiveMetricsService::class)->cards(),
            'reports' => $reports,
            'archiveSearch' => $filters['archiveSearch'],
            'sort' => $filters['sort'],
            'selectedDate' => $filters['selectedDate'],
            'selectedGroup' => $filters['selectedGroup'],
            'selectedShift' => $filters['selectedShift'],
            'selectedDivision' => $filters['selectedDivision'],
            'selectedStatus' => $filters['selectedStatus'],
            'archiveInstantLimit' => ArchiveBundleService::INSTANT_LIMIT,
            'archiveBundleLimit' => ArchiveBundleService::BUNDLE_LIMIT,
        ]);
    }

    public function archiveExport(Request $request)
    {
        $this->authorizeManagementAccess($request);

        return $this->archiveExportResponse($request, 'manajer');
    }

    public function archiveBulkDownload(Request $request)
    {
        $this->authorizeManagementAccess($request);

        return $this->archiveBulkDownloadResponse($request);
    }

    public function archiveBundleStore(Request $request)
    {
        $this->authorizeManagementAccess($request);

        return $this->archiveBundleStoreResponse($request, 'manajer');
    }

    public function archiveBundleShow(Request $request, string $token)
    {
        $this->authorizeManagementAccess($request);

        return $this->archiveBundleStatusResponse($request, $token);
    }

    public function archiveBundleDownload(Request $request, string $token)
    {
        $this->authorizeManagementAccess($request);

        return $this->archiveBundleDownloadResponse($request, $token);
    }

    public function archiveBundleDestroy(Request $request, string $token)
    {
        $this->authorizeManagementAccess($request);

        return $this->archiveBundleDestroyResponse($request, $token);
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

        $canSign = $report->status === ReportStatus::Acknowledged;

        return view('report-ops.viewpdf', [
            'report' => $this->loadReport($report),
            'isPdf' => false,
            'backUrl' => route('manajer.index'),
            'pdfUrl' => route('manajer.reports.download', $report),
            'signAction' => $canSign ? route('manajer.reports.approve', $report) : null,
            'signMessage' => 'Setujui & tanda tangani laporan ini? Laporan akan masuk ke arsip setelah ditandatangani.',
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

    /**
     * Halaman analisis performa divisi operasi. Semua angkanya diturunkan dari
     * laporan harian yang sudah ada lewat OperationalPerformanceService.
     *
     * Periode bawaannya 1 Januari sampai hari berjalan: halaman ini dipakai
     * untuk membaca capaian tahun berjalan, bukan sekadar bulan terakhir.
     */
    public function performa(Request $request, OperationalPerformanceService $performance)
    {
        $this->authorizeManagementAccess($request);

        [
            'presets' => $presets,
            'preset' => $preset,
            'filters' => $filters,
            'selectedGroup' => $selectedGroup,
            'selectedShift' => $selectedShift,
            'start' => $start,
            'end' => $end,
        ] = $this->performanceFiltersFromRequest($request, self::PERFORMANCE_DEFAULT_PRESET);

        $report = Cache::remember(
            $this->performanceCacheKey($filters),
            now()->addMinutes(10),
            fn (): array => $performance->performanceReport($filters)
        );

        return view('manajer.performa', [
            'report' => $report,
            'kpi' => array_merge($report['summary'], [
                'comparisonLabel' => $report['kpiComparisonLabel'] ?? $report['comparisonLabel'],
                'sparklines' => $report['sparklines'],
            ]),
            'presets' => $presets,
            'selectedPreset' => $preset,
            'selectedStart' => $start->toDateString(),
            'selectedEnd' => $end->toDateString(),
            'selectedGroup' => $selectedGroup,
            'selectedShift' => $selectedShift,
            'filterOptions' => $performance->filterOptions(),
            'hasActiveFilter' => $preset !== self::PERFORMANCE_DEFAULT_PRESET || $selectedGroup !== 'all' || $selectedShift !== 'all',
        ]);
    }

    /**
     * Halaman rincian per jenis kegiatan operasi. Dipisah dari Performa karena
     * keduanya menjawab pertanyaan berbeda: Performa merangkum divisi secara
     * keseluruhan, halaman ini membedah satu jenis kegiatan.
     *
     * Filter periodenya sengaja identik dan ikut terbawa lewat query string,
     * sehingga berpindah menu tidak berarti menyaring ulang dari awal. Kedua
     * menu dibuka pada 1 Januari sampai hari berjalan.
     */
    public function kegiatan(Request $request, OperationalPerformanceService $performance)
    {
        $this->authorizeManagementAccess($request);

        [
            'presets' => $presets,
            'preset' => $preset,
            'filters' => $filters,
            'selectedGroup' => $selectedGroup,
            'selectedShift' => $selectedShift,
            'start' => $start,
            'end' => $end,
        ] = $this->performanceFiltersFromRequest($request, self::ACTIVITY_DEFAULT_PRESET);

        // Kunci cache-nya sama dengan halaman Performa: keduanya memakai
        // laporan agregat yang sama, jadi cukup dihitung sekali per filter.
        $report = Cache::remember(
            $this->performanceCacheKey($filters),
            now()->addMinutes(10),
            fn (): array => $performance->performanceReport($filters)
        );

        // Daftar tab diturunkan dari katalog, bukan disalin ulang di view.
        // Menambah atau menyembunyikan kegiatan cukup lewat penandanya.
        $activities = [];

        foreach ($performance->activitiesFor('activityDetail') as $key => $activity) {
            $activities[] = $activity + ['key' => $key];
        }

        return view('manajer.kegiatan', [
            'report' => $report,
            'activities' => $activities,
            'presets' => $presets,
            'selectedPreset' => $preset,
            'selectedStart' => $start->toDateString(),
            'selectedEnd' => $end->toDateString(),
            'selectedGroup' => $selectedGroup,
            'selectedShift' => $selectedShift,
            'filterOptions' => $performance->filterOptions(),
            'hasActiveFilter' => $preset !== self::ACTIVITY_DEFAULT_PRESET || $selectedGroup !== 'all' || $selectedShift !== 'all',
        ]);
    }

    /**
     * Isi satu panel kegiatan, diambil terpisah saat tabnya dibuka.
     *
     * Panel tidak ikut dihitung ketika halaman utama dirender: kelima panel
     * sekaligus berarti lima kali beban query untuk sesuatu yang biasanya
     * hanya dibaca satu. Tiap panel punya kunci cache sendiri supaya baris
     * cache-nya tetap kecil — penyimpanan cache aplikasi ini ada di database.
     */
    public function kegiatanPanel(Request $request, OperationalPerformanceService $performance, string $key)
    {
        $this->authorizeManagementAccess($request);

        // Kegiatan yang tidak ditandai tampil pada menu ini — Pemuatan Pupuk
        // Kantong — ditolak juga ketika endpoint-nya dibuka langsung, bukan
        // hanya disembunyikan dari tab.
        if (! $performance->activityVisibleOn($key, 'activityDetail')) {
            abort(404);
        }

        ['filters' => $filters] = $this->performanceFiltersFromRequest($request, self::ACTIVITY_DEFAULT_PRESET);

        $detail = Cache::remember(
            $this->performanceCacheKey($filters, 'kegiatan.'.$key),
            now()->addMinutes(10),
            fn (): array => $performance->activityDetail($key, $filters)
        );

        return view('manajer.partials.activity-detail', ['detail' => $detail]);
    }

    /**
     * Workbook khusus menu Rincian Kegiatan: sheet pertama merangkum seluruh
     * kegiatan, lalu setiap kegiatan mendapat sheet sendiri beserta tabel
     * operasional dan dua chart yang dapat dianalisis langsung di Excel.
     */
    public function kegiatanExport(
        Request $request,
        OperationalPerformanceService $performance,
        ActivityDetailExportService $exporter
    ) {
        $this->authorizeManagementAccess($request);

        [
            'filters' => $filters,
            'selectedGroup' => $selectedGroup,
            'selectedShift' => $selectedShift,
        ] = $this->performanceFiltersFromRequest($request, self::ACTIVITY_DEFAULT_PRESET);

        // Workbook harus memakai satu pembacaan aktual: overview tidak boleh
        // berasal dari cache lama sementara sheet rinci baru dihitung ulang.
        $report = $performance->performanceReport($filters);

        $details = [];
        foreach (array_keys($performance->activitiesFor('activityDetail')) as $key) {
            $details[$key] = $performance->activityDetailForExport($key, $filters);
        }

        $containerTeus = 0.0;
        foreach ($report['activityCards'] ?? [] as $card) {
            if (($card['unit'] ?? null) === 'Teus') {
                $containerTeus += (float) ($card['value'] ?? 0);
            }
        }

        $damage = $report['summary']['damageRatio'] ?? [];
        $damageText = ($damage['hasBase'] ?? false)
            ? number_format((float) ($damage['value'] ?? 0), 2, ',', '.').'%'
            : 'belum tersedia';

        $contextLines = [
            'Periode: '.$report['periodLabel'].' · pembanding '.$report['comparisonLabel'],
            'Filter: Regu '.($selectedGroup === 'all' ? 'Semua' : strtoupper($selectedGroup))
                .' · Shift '.($selectedShift === 'all' ? 'Semua' : ucfirst($selectedShift)),
            'Sorotan: '.number_format((float) ($report['summary']['tonnage']['value'] ?? 0), 1, ',', '.')
                .' Ton · '.number_format($containerTeus, 0, ',', '.').' Teus · '
                .(int) ($report['reportCount'] ?? 0).' laporan · rasio kerusakan '.$damageText,
            'Container dicatat dalam Teus dan tidak dijumlahkan ke total Ton. '
                .'Tren pada sheet kegiatan selalu memakai enam bulan kalender terakhir.',
            'Diekspor: '.now()->locale('id')->translatedFormat('d F Y, H:i').' oleh '.($request->user()->name ?? '-'),
        ];

        $spreadsheet = $exporter->build($report, $details, $contextLines);

        $auditData = [
            'user_id' => $request->user()?->id,
            'type' => 'export',
            'description' => 'Mengekspor rincian kegiatan operasional ('.$report['periodLabel'].')',
            'ip_address' => $request->ip(),
        ];

        $fileName = 'Rincian-Kegiatan-Operasional_'.now()->format('Y-m-d_Hi').'.xlsx';

        return response()->streamDownload(function () use ($spreadsheet, $auditData): void {
            try {
                $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
                $writer->setIncludeCharts(true);
                $writer->save('php://output');

                // Catat sesudah serialisasi berhasil agar kegagalan writer
                // tidak terlihat sebagai ekspor sukses di audit trail.
                AdminActivityLog::create($auditData);
            } finally {
                $spreadsheet->disconnectWorksheets();
            }
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Unduh rekap performa sebagai Excel. Filternya diambil dengan cara yang
     * persis sama dengan halaman — termasuk cache-nya — sehingga berkas yang
     * diunduh selalu menggambarkan apa yang sedang tampil di layar.
     */
    public function performaExport(
        Request $request,
        OperationalPerformanceService $performance,
        PerformanceExportService $exporter
    ) {
        $this->authorizeManagementAccess($request);

        [
            'filters' => $filters,
            'selectedGroup' => $selectedGroup,
            'selectedShift' => $selectedShift,
        ] = $this->performanceFiltersFromRequest($request, self::PERFORMANCE_DEFAULT_PRESET);

        $report = Cache::remember(
            $this->performanceCacheKey($filters),
            now()->addMinutes(10),
            fn (): array => $performance->performanceReport($filters)
        );

        $spreadsheet = $exporter->build($report, [
            'Periode: '.$report['periodLabel'].' · pembanding '.$report['comparisonLabel'],
            'Filter: Regu '.($selectedGroup === 'all' ? 'Semua' : strtoupper($selectedGroup))
                .' · Shift '.($selectedShift === 'all' ? 'Semua' : ucfirst($selectedShift)),
            'Diekspor: '.now()->locale('id')->translatedFormat('d F Y, H:i').' oleh '.($request->user()->name ?? '-'),
        ]);

        AdminActivityLog::create([
            'user_id' => $request->user()?->id,
            'type' => 'export',
            'description' => 'Mengekspor rekap performa operasional ('.$report['periodLabel'].')',
            'ip_address' => $request->ip(),
        ]);

        $fileName = 'Performa-Operasional_'.now()->format('Y-m-d_Hi').'.xlsx';

        return response()->streamDownload(function () use ($spreadsheet): void {
            $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Preset, rentang tanggal, dan filter regu/shift dari query string.
     * Dipakai halaman performa maupun ekspornya — satu sumber supaya keduanya
     * tidak pernah membaca filter dengan cara berbeda.
     *
     * Kedua menu membuka tahun berjalan sejak Januari. Parameter bawaan tetap
     * diterima agar satu resolver ini juga aman dipakai konteks lain.
     *
     * @return array{presets: array<string, string>, preset: string, filters: array<string, mixed>, selectedGroup: string, selectedShift: string, start: Carbon, end: Carbon}
     */
    private function performanceFiltersFromRequest(Request $request, string $defaultPreset = self::PERFORMANCE_DEFAULT_PRESET): array
    {
        $presets = self::PERIOD_PRESETS;

        $selectedGroup = (string) $request->input('regu', 'all');
        $selectedShift = (string) $request->input('shift', 'all');
        [$start, $end, $preset] = $this->resolvePerformancePeriod($request, array_keys($presets), $defaultPreset);

        return [
            'presets' => $presets,
            'preset' => $preset,
            'selectedGroup' => $selectedGroup,
            'selectedShift' => $selectedShift,
            'start' => $start,
            'end' => $end,
            'filters' => [
                'start' => $start,
                'end' => $end,
                'group' => $selectedGroup !== 'all' ? $selectedGroup : null,
                'shift' => $selectedShift !== 'all' ? $selectedShift : null,
            ],
        ];
    }

    /**
     * Tentukan rentang tanggal dari preset atau dari input tanggal bebas.
     * Tanggal yang terbalik ditukar supaya rentangnya selalu valid.
     *
     * @param  array<int, string>  $allowedPresets
     * @return array{0: Carbon, 1: Carbon, 2: string}
     */
    private function resolvePerformancePeriod(Request $request, array $allowedPresets, string $defaultPreset): array
    {
        $rawStart = $request->input('dari');
        $rawEnd = $request->input('sampai');

        if ($rawStart || $rawEnd) {
            try {
                $start = $rawStart ? Carbon::parse($rawStart)->startOfDay() : Carbon::today()->startOfMonth();
                $end = $rawEnd ? Carbon::parse($rawEnd)->startOfDay() : Carbon::today();

                if ($start->greaterThan($end)) {
                    [$start, $end] = [$end, $start];
                }

                return [$start, $end, 'custom'];
            } catch (Throwable) {
                // Tanggal tidak terbaca — jatuh kembali ke preset bawaan menu.
            }
        }

        $preset = (string) $request->input('periode', $defaultPreset);

        if (! in_array($preset, $allowedPresets, true)) {
            $preset = $defaultPreset;
        }

        $performance = app(OperationalPerformanceService::class);

        return match ($preset) {
            // Tahun berjalan: 1 Januari sampai hari ini.
            'tahun-berjalan' => [...$performance->yearToDateRange(), $preset],
            // Tahun sebelumnya utuh, 1 Januari sampai 31 Desember.
            'tahun-lalu' => [...$performance->yearToDateRange((int) Carbon::today()->year - 1), $preset],
            'bulan-lalu' => [
                Carbon::today()->subMonthNoOverflow()->startOfMonth(),
                Carbon::today()->subMonthNoOverflow()->endOfMonth()->startOfDay(),
                $preset,
            ],
            '3-bulan' => [
                Carbon::today()->startOfMonth()->subMonthsNoOverflow(2),
                Carbon::today(),
                $preset,
            ],
            default => [...$performance->currentMonthRange(), 'bulan-ini'],
        };
    }

    /**
     * Kunci cache satu bagian halaman performa.
     *
     * Cap waktu laporan terakhir ikut masuk kunci, sehingga laporan yang baru
     * disetujui langsung tercermin tanpa perlu menghapus kunci satu per satu —
     * kombinasi filternya terlalu banyak untuk dibersihkan manual.
     *
     * @param  array<string, mixed>  $filters
     */
    private function performanceCacheKey(array $filters, string $section = 'ringkasan'): string
    {
        return sprintf(
            'manajer.performa.v6.%s.%s.%s.%s.%s.%s',
            $section,
            $this->performanceStamp(),
            $filters['start']->toDateString(),
            $filters['end']->toDateString(),
            $filters['group'] ?? 'all',
            $filters['shift'] ?? 'all'
        );
    }

    /**
     * Cap waktu laporan terakhir yang ikut dihitung. Dicari maksimal sekali
     * per menit karena query-nya murah tetapi tetap tidak perlu diulang tiap
     * kali kunci cache disusun.
     */
    private function performanceStamp(): string
    {
        return Cache::remember('manajer.performa.stamp', now()->addSeconds(60), function (): string {
            $latest = DailyReport::query()
                ->whereIn('status', OperationalPerformanceService::COUNTED_STATUSES)
                ->max('updated_at');

            return $latest ? Carbon::parse($latest)->format('YmdHis') : 'kosong';
        });
    }

    // ============================================================
    // Persetujuan laporan pemeliharaan (submitted -> approved)
    // ============================================================

    public function showMaintenance(Request $request, MaintenanceReport $report)
    {
        $this->authorizeManagementAccess($request);
        abort_unless(in_array($report->status, [MaintenanceStatus::Submitted, MaintenanceStatus::Approved], true), 404);

        $canSign = $report->status === MaintenanceStatus::Submitted;

        return view('pemeliharaan.viewpdf', [
            'report' => $this->loadMaintenanceReport($report),
            'isPdf' => false,
            'backUrl' => route('manajer.index'),
            'pdfUrl' => route('manajer.pemeliharaan.download', $report),
            'signAction' => $canSign ? route('manajer.pemeliharaan.approve', $report) : null,
            'signMessage' => 'Setujui & tanda tangani laporan pemeliharaan ini? Laporan akan masuk ke arsip setelah ditandatangani.',
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
                'status' => MaintenanceStatus::Approved,
                'approved_by' => $request->user()->id,
                'approved_at' => now(),
            ]);

            $this->forgetManagerStatsCache();
            $this->cacheApprovedMaintenancePdf($report->fresh());
        } catch (Throwable $exception) {
            Log::error('Gagal menyetujui laporan pemeliharaan.', [
                'report_id' => $report->id,
                'user_id' => $request->user()?->id,
                'message' => $exception->getMessage(),
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
                'isPdf' => true,
            ]);
            $pdf->setPaper([0, 0, 612.00, 936.00], 'portrait');
            $pdf->setOption('isRemoteEnabled', true);

            return $pdf->download($this->maintenanceFileName($report, 'pdf'));
        }

        return view('pemeliharaan.viewpdf', [
            'report' => $this->loadMaintenanceReport($report),
            'isPdf' => false,
            'backUrl' => route('manajer.index'),
            'pdfUrl' => null,
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

        $canSign = $report->status === SafetyStatus::Submitted;

        return view('report-safety.viewpdf', [
            'report' => $this->loadSafetyReport($report),
            'isPdf' => false,
            'backUrl' => route('manajer.index'),
            'pdfUrl' => route('manajer.safety.download', $report),
            'signAction' => $canSign ? route('manajer.safety.approve', $report) : null,
            'signMessage' => 'Setujui & tanda tangani laporan K3 ini? Laporan akan masuk ke arsip setelah ditandatangani.',
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
                'status' => SafetyStatus::Approved,
                'approved_by' => $request->user()->id,
                'approved_at' => now(),
                'approver_signature_path' => $request->user()->signature_path,
            ]);

            $this->forgetManagerStatsCache();
            $this->cacheApprovedSafetyPdf($report->fresh());
        } catch (Throwable $exception) {
            Log::error('Gagal menyetujui laporan K3.', [
                'report_id' => $report->id,
                'user_id' => $request->user()?->id,
                'message' => $exception->getMessage(),
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
                'isPdf' => true,
            ]);
            $pdf->setPaper([0, 0, 612.00, 936.00], 'portrait');
            $pdf->setOption('isRemoteEnabled', true);

            return $pdf->download($this->safetyFileName($report, 'pdf'));
        }

        return view('report-safety.viewpdf', [
            'report' => $this->loadSafetyReport($report),
            'isPdf' => false,
            'backUrl' => route('manajer.index'),
            'pdfUrl' => null,
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

    /**
     * Empat kartu KPI performa operasi di dashboard. Agregasinya menyentuh lima
     * tabel kegiatan sekaligus, jadi hasilnya disimpan lebih lama daripada
     * statistik jumlah laporan yang murah dihitung.
     */
    private function dashboardKpi(): array
    {
        return Cache::remember(
            $this->managerStatsCacheKey('kpi'),
            now()->addMinutes(10),
            fn (): array => app(OperationalPerformanceService::class)->dashboardKpi()
        );
    }

    private function dashboardStats(): array
    {
        return Cache::remember($this->managerStatsCacheKey('dashboard'), now()->addSeconds(60), function (): array {
            $counts = $this->archiveStatusDateCounts(
                DailyReport::query(),
                [ReportStatus::Submitted, ReportStatus::Acknowledged, ReportStatus::Approved],
                [ReportStatus::Acknowledged]
            );

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
        Cache::forget($this->managerStatsCacheKey('kpi'));
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
                'isPdf' => true,
            ]);
            $pdf->setPaper([0, 0, 612.00, 936.00], 'portrait');
            $pdf->setOption('isRemoteEnabled', true);
            $pdf->save($storagePath.'/maintenance-report-'.$report->id.'.pdf');
        } catch (Throwable $exception) {
            Log::error('Gagal menyimpan PDF arsip laporan pemeliharaan.', [
                'report_id' => $report->id,
                'message' => $exception->getMessage(),
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
                'isPdf' => true,
            ]);
            $pdf->setPaper([0, 0, 612.00, 936.00], 'portrait');
            $pdf->setOption('isRemoteEnabled', true);
            $pdf->save($storagePath.'/safety-report-'.$report->id.'.pdf');
        } catch (Throwable $exception) {
            Log::error('Gagal menyimpan PDF arsip laporan K3.', [
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
