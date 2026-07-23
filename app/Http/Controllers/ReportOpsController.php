<?php

namespace App\Http\Controllers;

use App\Enums\ReportStatus;
use App\Http\Controllers\Concerns\AutosavesDraftReports;
use App\Http\Controllers\Concerns\ResolvesReportMeta;
use App\Http\Controllers\Concerns\SearchesReports;
use App\Models\DailyReport;
use App\Models\MasterEmployee;
use App\Models\MasterEnvironmentItem;
use App\Models\MasterInventoryItem;
use App\Models\MasterTruck;
use App\Models\MasterUnit;
use App\Models\ShipOperation;
use App\Models\UnitCheckLog;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Throwable;

class ReportOpsController extends Controller
{
    use AutosavesDraftReports;
    use ResolvesReportMeta;
    use SearchesReports;

    private const MASTER_DATA_CACHE_TTL = 60 * 60 * 24;

    private const PENDING_PDF_CACHE_TTL = 60 * 10;

    /**
     * Batas jumlah laporan terkirim per regu untuk satu tanggal. Satu hari terdiri
     * dari 3 shift (Pagi, Sore, Malam), jadi satu regu wajar mengirim hingga 3
     * laporan pada tanggal yang sama. Laporan ke-4 dianggap berlebih dan ditolak.
     */
    private const MAX_DAILY_REPORTS_PER_GROUP = 3;

    public function index()
    {
        $user = auth()->user();
        $userGroup = strtoupper((string) ($user->group ?? ''));

        $this->pruneStaleDraftReports();

        $incomingReports = DailyReport::with(['creator', 'receiver', 'approver'])
            ->where('status', ReportStatus::Submitted)
            ->when(
                $userGroup === '',
                fn ($query) => $query->whereRaw('1 = 0'),
                fn ($query) => $query->where('received_by_group', $userGroup)
            )
            ->latest('updated_at')
            ->get();

        $draftReports = DailyReport::with('creator')
            ->where('created_by', $user->id)
            ->where('status', ReportStatus::Draft)
            ->latest('updated_at')
            ->get();

        $historySearch = trim((string) request('history_search', ''));
        $receivedSearch = trim((string) request('received_search', ''));

        $activeTab = match (true) {
            request('tab') === 'diterima' || request()->has('received_page') || $receivedSearch !== '' => 'diterima',
            request('tab') === 'riwayat' || request()->has('history_page') || $historySearch !== '' => 'riwayat',
            default => 'laporan',
        };

        // Relasi anak (aktivitas, log, dsb.) tidak dirender di daftar; teks filter
        // client-side dibangun dari kolom payload yang sudah ada di tabel laporan.
        $reportRelations = [
            'creator',
            'receiver',
            'approver',
        ];

        // Riwayat = laporan yang DIBUAT oleh regu pengguna sendiri (group pengirim).
        $historyQuery = DailyReport::with($reportRelations)
            ->where(function ($innerQuery) use ($user, $userGroup) {
                if ($userGroup !== '') {
                    $innerQuery->where('group_name', $userGroup);
                } else {
                    $innerQuery->where('created_by', $user->id);
                }
            });

        $this->applyReportSearch($historyQuery, $historySearch);

        $historyReports = $historyQuery
            ->latest('report_date')
            ->latest('updated_at')
            ->paginate(10, ['*'], 'history_page')
            ->withQueryString();

        $historyReports->appends(['tab' => 'riwayat']);

        // Laporan Diterima = laporan yang MASUK dari regu lain (ditujukan ke regu pengguna).
        $receivedQuery = DailyReport::with($reportRelations)
            ->when(
                $userGroup === '',
                fn ($query) => $query->whereRaw('1 = 0'),
                fn ($query) => $query->where('received_by_group', $userGroup)
            );

        $this->applyReportSearch($receivedQuery, $receivedSearch);

        $receivedReports = $receivedQuery
            ->latest('report_date')
            ->latest('updated_at')
            ->paginate(10, ['*'], 'received_page')
            ->withQueryString();

        $receivedReports->appends(['tab' => 'diterima']);

        return view('report-ops.index', compact(
            'incomingReports',
            'draftReports',
            'historyReports',
            'historySearch',
            'receivedReports',
            'receivedSearch',
            'activeTab'
        ));
    }

    public function historySuggestions(Request $request)
    {
        $user = $request->user();
        $userGroup = strtoupper((string) ($user->group ?? ''));
        $keyword = trim((string) $request->input('q', ''));

        // Saran pencarian hanya merender metadata laporan (tanggal, shift, status, group);
        // tidak ada relasi anak yang dipakai di output, jadi tidak perlu di-eager-load.
        // Saran riwayat dibatasi pada laporan yang dibuat oleh regu pengguna sendiri,
        // selaras dengan isi tab Riwayat.
        $query = DailyReport::query()
            ->where(function ($innerQuery) use ($user, $userGroup): void {
                if ($userGroup !== '') {
                    $innerQuery->where('group_name', $userGroup);
                } else {
                    $innerQuery->where('created_by', $user->id);
                }
            });

        $this->applyReportSearch($query, $keyword);

        $reports = $query
            ->latest('report_date')
            ->latest('updated_at')
            ->limit(8)
            ->get();

        $items = $reports->map(fn (DailyReport $report): array => $this->reportSuggestionItem($report))->values();

        return response()->json([
            'keyword' => $keyword,
            'total' => $items->count(),
            'items' => $items,
        ]);
    }

    public function receivedSuggestions(Request $request)
    {
        $user = $request->user();
        $userGroup = strtoupper((string) ($user->group ?? ''));
        $keyword = trim((string) $request->input('q', ''));

        // Saran tab "Laporan Diterima" dibatasi pada laporan yang ditujukan ke regu pengguna.
        $query = DailyReport::query()
            ->when(
                $userGroup === '',
                fn ($innerQuery) => $innerQuery->whereRaw('1 = 0'),
                fn ($innerQuery) => $innerQuery->where('received_by_group', $userGroup)
            );

        $this->applyReportSearch($query, $keyword);

        $items = $query
            ->latest('report_date')
            ->latest('updated_at')
            ->limit(8)
            ->get()
            ->map(fn (DailyReport $report): array => $this->reportSuggestionItem($report))
            ->values();

        return response()->json([
            'keyword' => $keyword,
            'total' => $items->count(),
            'items' => $items,
        ]);
    }

    private function reportSuggestionItem(DailyReport $report): array
    {
        $shift = $this->shiftMeta($report->shift);
        $status = $this->statusMeta($report->status);

        return [
            'id' => $report->id,
            'document_id' => $this->documentId($report),
            'title' => 'Laporan Operasi Harian',
            'report_date' => $report->report_date
                ? Carbon::parse($report->report_date)->locale('id')->translatedFormat('d F Y')
                : '-',
            'updated_diff' => $report->updated_at
                ? Carbon::parse($report->updated_at)->locale('id')->diffForHumans()
                : '-',
            'shift_label' => $shift['label'],
            'shift_class' => $shift['class'],
            'status_label' => $status['label'],
            'status_class' => $status['class'],
            'group_from' => strtoupper((string) $report->group_name) ?: '-',
            'group_to' => strtoupper((string) $report->received_by_group) ?: '-',
            'view_url' => route('report-ops.show', $report),
            'pdf_url' => route('report-ops.pdf', $report),
            'excel_url' => route('report-ops.excel', $report),
        ];
    }

    public function shipOperationSuggestions(Request $request)
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in([ShipOperation::TYPE_BAG_LOADING, ShipOperation::TYPE_BULK_LOADING])],
            'q' => ['nullable', 'string', 'max:255'],
            'exclude_report_id' => ['nullable', 'integer'],
        ]);

        $keyword = trim((string) ($validated['q'] ?? ''));
        $excludeReportId = isset($validated['exclude_report_id']) ? (int) $validated['exclude_report_id'] : null;

        $this->pruneStaleShipOperations();

        // Tanpa kata kunci hanya kapal aktif yang disarankan; saat petugas
        // mengetik, kapal terarsip (jeda >TTL hari) ikut dicari supaya operasi
        // yang tertunda bisa dilanjutkan tanpa kehilangan akumulasi.
        $operations = ShipOperation::query()
            ->where('type', $validated['type'])
            ->when(
                $keyword === '',
                fn ($query) => $query->where('status', ShipOperation::STATUS_ACTIVE),
                fn ($query) => $query->whereIn('status', [ShipOperation::STATUS_ACTIVE, ShipOperation::STATUS_INACTIVE])
            )
            ->when($keyword !== '', function ($query) use ($keyword): void {
                $like = '%'.$keyword.'%';

                $query->where(function ($search) use ($like): void {
                    $this->whereColumnsLike($search, [
                        'ship_name',
                        'agent',
                        'jetty',
                        'destination',
                        'wo_number',
                        'cargo_type',
                        'marking',
                        'commodity',
                    ], $like);
                });
            })
            ->orderByRaw("CASE WHEN status = '".ShipOperation::STATUS_ACTIVE."' THEN 0 ELSE 1 END")
            ->latest('updated_at')
            ->limit(8)
            ->get();

        return response()->json([
            'keyword' => $keyword,
            'items' => $operations
                ->map(fn (ShipOperation $operation): array => $this->shipOperationSuggestionItem($operation, $excludeReportId))
                ->values(),
        ]);
    }

    /**
     * Hitung jumlah laporan terkirim milik satu regu pada tanggal tertentu.
     * Dipakai form untuk menampilkan peringatan ringan sebelum mengirim bila
     * pada tanggal itu regu tersebut sudah memiliki laporan lain (mendekati
     * batas MAX_DAILY_REPORTS_PER_GROUP). Endpoint ini hanya informatif; penjaga
     * sebenarnya tetap ada di validasi store/update.
     */
    public function dayReportCount(Request $request)
    {
        $group = strtoupper(trim((string) $request->query('group_name')));
        $rawDate = trim((string) $request->query('report_date'));
        $exceptId = (int) $request->query('except');

        $limit = self::MAX_DAILY_REPORTS_PER_GROUP;

        try {
            $date = $rawDate === '' ? null : Carbon::parse($rawDate)->toDateString();
        } catch (Throwable) {
            $date = null;
        }

        if ($group === '' || $date === null) {
            return response()->json(['count' => 0, 'limit' => $limit, 'remaining' => $limit]);
        }

        $count = DailyReport::query()
            ->whereDate('report_date', $date)
            ->where('group_name', $group)
            ->where('status', '!=', ReportStatus::Draft->value)
            ->when($exceptId > 0, fn ($query) => $query->whereKeyNot($exceptId))
            ->count();

        return response()->json([
            'count' => $count,
            'limit' => $limit,
            'remaining' => max(0, $limit - $count),
        ]);
    }

    public function create()
    {
        $this->pruneStaleDraftReports();

        return view('report-ops.create', $this->masterData());
    }

    public function store(Request $request)
    {
        if ($this->isAutosaveRequest($request)) {
            $request->merge(['status' => ReportStatus::Draft->value]);
        }

        $status = $request->input('status') === ReportStatus::Draft->value ? ReportStatus::Draft->value : ReportStatus::Submitted->value;
        $request->merge(['status' => $status]);

        $validated = $request->validate($this->rules($status === ReportStatus::Draft->value, $request), [], $this->attributes());
        $payload = $this->decodePayload($request->input('form_payload'));
        $report = null;

        try {
            DB::transaction(function () use ($request, $validated, $payload, &$report): void {
                $userId = $request->user()->id;

                $report = DailyReport::create([
                    'user_id' => $userId,
                    'created_by' => $userId,
                    'report_date' => $validated['report_date'] ?? null,
                    'shift' => $validated['shift'] ?? null,
                    'group_name' => isset($validated['group_name']) ? strtoupper($validated['group_name']) : null,
                    'received_by_group' => isset($validated['received_by_group']) ? strtoupper($validated['received_by_group']) : null,
                    'time_range' => $validated['time_range'] ?? null,
                    'status' => $validated['status'],
                    'payload' => $payload,
                ]);

                $this->storeDetails($report, $request);
            });
        } catch (Throwable $exception) {
            Log::error('Gagal menyimpan laporan operasional.', [
                'user_id' => $request->user()?->id,
                'message' => $exception->getMessage(),
            ]);

            return back()
                ->withInput()
                ->with('error', 'Laporan belum bisa disimpan. Silakan periksa data lalu coba lagi.');
        }

        if ($this->isAutosaveRequest($request)) {
            return $this->autosaveResponse($report, 'report-ops.update');
        }

        $message = $status === ReportStatus::Draft->value
            ? 'Draft laporan berhasil disimpan.'
            : 'Laporan operasional berhasil dikirim.';

        return redirect()->route('report-ops.index')->with('success', $message);
    }

    public function show(DailyReport $report)
    {
        $user = auth()->user();
        abort_unless($this->canAccessReport($report, $user), 403);

        $canSign = $report->status === ReportStatus::Submitted && $this->canReceiveReport($report, $user);

        return view('report-ops.viewpdf', [
            'report' => $this->loadReport($report),
            'isPdf' => false,
            'backUrl' => route('report-ops.index'),
            'pdfUrl' => route('report-ops.pdf', $report),
            'signAction' => $canSign ? route('report-ops.sign', $report) : null,
            'signMessage' => 'Tanda tangani laporan ini sebagai bukti serah terima ke regu Anda?',
        ]);
    }

    public function edit(DailyReport $report)
    {
        abort_unless($this->canEditReport($report, auth()->user()), 403);

        if ($this->deleteIfStaleDraft($report)) {
            return redirect()
                ->route('report-ops.index', ['tab' => 'draft'])
                ->with('error', 'Draft sudah melewati 3 hari tanpa dilanjutkan, sehingga data draft tersebut dihapus otomatis.');
        }

        return view('report-ops.edit', array_merge($this->masterData($report), [
            'report' => $this->loadReport($report),
        ]));
    }

    public function update(Request $request, DailyReport $report)
    {
        abort_unless($this->canEditReport($report, $request->user()), 403);

        if ($this->deleteIfStaleDraft($report)) {
            return redirect()
                ->route('report-ops.index', ['tab' => 'draft'])
                ->with('error', 'Draft sudah melewati 3 hari tanpa dilanjutkan, sehingga data draft tersebut dihapus otomatis.');
        }

        if ($this->isAutosaveRequest($request)) {
            $request->merge(['status' => ReportStatus::Draft->value]);
        }

        $status = $report->status === ReportStatus::Draft && $request->input('status') === ReportStatus::Draft->value
            ? ReportStatus::Draft->value
            : ReportStatus::Submitted->value;

        $request->merge(['status' => $status]);

        $validated = $request->validate($this->rules($status === ReportStatus::Draft->value, $request), [], $this->attributes());
        $payload = $this->decodePayload($request->input('form_payload'));

        try {
            DB::transaction(function () use ($request, $report, $validated, $payload): void {
                $report->update([
                    'report_date' => $validated['report_date'] ?? null,
                    'shift' => $validated['shift'] ?? null,
                    'group_name' => isset($validated['group_name']) ? strtoupper($validated['group_name']) : null,
                    'received_by_group' => isset($validated['received_by_group']) ? strtoupper($validated['received_by_group']) : null,
                    'time_range' => $validated['time_range'] ?? null,
                    'status' => $validated['status'],
                    'payload' => $payload,
                ]);

                $this->deleteDetails($report);
                $this->storeDetails($report, $request);
            });
        } catch (Throwable $exception) {
            Log::error('Gagal memperbarui laporan operasional.', [
                'report_id' => $report->id,
                'user_id' => $request->user()?->id,
                'message' => $exception->getMessage(),
            ]);

            return back()
                ->withInput()
                ->with('error', 'Laporan belum bisa diperbarui. Silakan periksa data lalu coba lagi.');
        }

        if ($this->isAutosaveRequest($request)) {
            return $this->autosaveResponse($report, 'report-ops.update');
        }

        $message = $status === ReportStatus::Draft->value
            ? 'Draft laporan berhasil diperbarui.'
            : 'Laporan operasional berhasil dikirim.';

        return redirect()->route('report-ops.index')->with('success', $message);
    }

    public function destroy(DailyReport $report)
    {
        abort_unless($this->canDeleteReport($report, auth()->user()), 403);

        try {
            $report->delete();
        } catch (Throwable $exception) {
            Log::error('Gagal menghapus draft laporan operasional.', [
                'report_id' => $report->id,
                'user_id' => auth()->id(),
                'message' => $exception->getMessage(),
            ]);

            return back()->with('error', 'Draft belum bisa dihapus. Silakan coba lagi.');
        }

        return redirect()->route('report-ops.index')->with('success', 'Draft laporan berhasil dihapus.');
    }

    public function extendDraft(DailyReport $report)
    {
        abort_unless($this->canDeleteReport($report, auth()->user()), 403);

        // Menyentuh updated_at me-reset hitungan masa simpan draft (DRAFT_TTL_DAYS).
        $report->touch();

        return redirect()
            ->route('report-ops.index', ['tab' => 'draft'])
            ->with('success', 'Masa simpan draft diperpanjang '.DailyReport::DRAFT_TTL_DAYS.' hari sejak sekarang.');
    }

    public function sign(DailyReport $report)
    {
        $user = auth()->user();

        abort_unless($this->canAccessReport($report, $user), 403);

        try {
            if ($report->status === ReportStatus::Submitted) {
                if (! $this->canReceiveReport($report, $user)) {
                    return back()->with('error', 'Anda tidak dapat menandatangani laporan untuk regu lain.');
                }

                $report->update([
                    'status' => ReportStatus::Acknowledged,
                    'received_by_user_id' => $user->id,
                    'received_at' => now(),
                ]);

                return back()->with('success', 'Laporan berhasil diterima dan ditanda tangani.');
            }
        } catch (Throwable $exception) {
            Log::error('Gagal menandatangani laporan operasional.', [
                'report_id' => $report->id,
                'user_id' => $user?->id,
                'message' => $exception->getMessage(),
            ]);

            return back()->with('error', 'Tanda tangan belum bisa diproses. Silakan coba lagi.');
        }

        return back()->with('error', 'Status laporan belum dapat ditanda tangani.');
    }

    public function exportPdf(DailyReport $report)
    {
        abort_unless($this->canAccessReport($report, auth()->user()), 403);

        if (! class_exists(Pdf::class)) {
            return view('report-ops.viewpdf', [
                'report' => $this->loadReport($report),
                'isPdf' => false,
                'backUrl' => route('report-ops.index'),
                'pdfUrl' => null,
            ]);
        }

        return response($this->renderReportPdf($report), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$this->reportFileName($report, 'pdf').'"',
        ]);
    }

    private function renderReportPdf(DailyReport $report): string
    {
        $generate = function () use ($report): string {
            $pdf = Pdf::loadView('report-ops.pdf', [
                'report' => $this->loadReport($report),
                'isPdf' => true,
            ]);

            $pdf->setPaper([0, 0, 612.00, 936.00], 'portrait');
            $pdf->setOption('isRemoteEnabled', true);

            return $pdf->output();
        };

        // Laporan approved sudah punya PDF arsip permanen di storage; hanya laporan
        // yang belum di-approve yang di-cache sementara agar tidak digenerate ulang.
        if ($report->status === ReportStatus::Approved) {
            $archivedPath = storage_path('app/public/reports/report-'.$report->id.'.pdf');

            if (is_file($archivedPath)) {
                return (string) file_get_contents($archivedPath);
            }

            return $generate();
        }

        // Output PDF berupa byte biner. Cache driver "database" menyimpan value pada
        // kolom teks (utf8) sehingga byte biner memicu error "Incorrect string value".
        // Simpan sebagai base64 (aman untuk semua driver cache) lalu decode saat dibaca.
        $encoded = Cache::remember(
            $this->pendingPdfCacheKey($report),
            self::PENDING_PDF_CACHE_TTL,
            fn (): string => base64_encode($generate())
        );

        return base64_decode($encoded);
    }

    private function pendingPdfCacheKey(DailyReport $report): string
    {
        return sprintf('report_pdf.pending.f4.%d.%d', $report->id, $report->updated_at?->timestamp ?? 0);
    }

    public function exportExcel(DailyReport $report)
    {
        abort_unless($this->canAccessReport($report, auth()->user()), 403);

        $report = $this->loadReport($report);
        $templatePath = storage_path('app/templates/Format_Laporan_Shift.xlsx');

        if (! is_file($templatePath)) {
            return back()->with('error', 'Template Excel laporan belum tersedia.');
        }

        try {
            $spreadsheet = IOFactory::load($templatePath);
            $sheet = $spreadsheet->getActiveSheet();

            $this->fillExcelReport($sheet, $report);

            $fileName = $this->reportFileName($report, 'xlsx');

            return response()->streamDownload(function () use ($spreadsheet): void {
                $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
                $writer->setPreCalculateFormulas(true);
                $writer->save('php://output');
                $spreadsheet->disconnectWorksheets();
            }, $fileName, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);
        } catch (Throwable $exception) {
            Log::error('Gagal membuat export Excel laporan operasional.', [
                'report_id' => $report->id,
                'user_id' => auth()->id(),
                'message' => $exception->getMessage(),
            ]);

            return back()->with('error', 'Excel laporan belum bisa dibuat. Silakan coba lagi.');
        }
    }

    private function fillExcelReport(Worksheet $sheet, DailyReport $report): void
    {
        $sheet->setTitle('Laporan Operasi');

        $this->fillExcelGeneralInfo($sheet, $report);
        $this->fillExcelBagLoading($sheet, $report);
        $this->fillExcelBulkLoading($sheet, $report);
        $this->fillExcelMaterialAndContainer($sheet, $report);
        $this->fillExcelTurba($sheet, $report);
        $this->fillExcelUnits($sheet, $report);
        $this->fillExcelEmployees($sheet, $report);
    }

    private function fillExcelGeneralInfo(Worksheet $sheet, DailyReport $report): void
    {
        $this->setExcelText($sheet, 'AD1', $this->excelDay($report->report_date));
        $this->setExcelText($sheet, 'AD2', $this->excelDate($report->report_date));
        $this->setExcelText($sheet, 'AD3', $report->time_range);
        $this->setExcelText($sheet, 'AD4', $report->shift);
        $this->setExcelText($sheet, 'AD5', $report->group_name);
        $this->setExcelText($sheet, 'AC165', $this->excelDate($report->report_date));
        $this->setExcelText($sheet, 'O172', 'Foreman Group. '.($report->received_by_group ?: '-'));
        $this->setExcelText($sheet, 'Z172', 'Foreman Group. '.($report->group_name ?: '-'));
    }

    private function fillExcelBagLoading(Worksheet $sheet, DailyReport $report): void
    {
        $activities = $report->loadingActivities->sortBy('sequence')->values();

        foreach ([8, 25, 42] as $index => $row) {
            $activity = $activities->get($index);

            foreach (range(0, 3) as $offset) {
                $this->clearExcelCells($sheet, ['H'.($row + $offset), 'S'.($row + $offset), 'AD'.($row + $offset)]);
            }

            foreach (range(5, 7) as $offset) {
                $this->clearExcelCells($sheet, ['H'.($row + $offset), 'S'.($row + $offset), 'AD'.($row + $offset)]);
            }

            foreach (range(10, 13) as $offset) {
                $this->clearExcelCells($sheet, ['C'.($row + $offset), 'G'.($row + $offset), 'R'.($row + $offset), 'W'.($row + $offset)]);
            }

            foreach (range(14, 16) as $offset) {
                $this->clearExcelCells($sheet, ['H'.($row + $offset), 'Q'.($row + $offset), 'AB'.($row + $offset)]);
            }

            $this->setExcelFormula($sheet, 'AD'.($row + 7), '=AD'.($row + 6).'+AD'.($row + 5));

            if (! $activity) {
                continue;
            }

            $this->setExcelText($sheet, 'H'.$row, $activity->ship_name);
            $this->setExcelText($sheet, 'H'.($row + 1), $activity->agent);
            $this->setExcelText($sheet, 'H'.($row + 2), $activity->jetty);
            $this->setExcelText($sheet, 'H'.($row + 3), $activity->destination);

            $this->setExcelNumber($sheet, 'S'.$row, $activity->capacity);
            $this->setExcelText($sheet, 'S'.($row + 1), $activity->wo_number);
            $this->setExcelText($sheet, 'S'.($row + 2), $activity->cargo_type);
            $this->setExcelText($sheet, 'S'.($row + 3), $activity->marking);

            $this->setExcelText($sheet, 'AD'.$row, $this->excelDateTime($activity->arrival_time));
            $this->setExcelText($sheet, 'AD'.($row + 1), $activity->operating_gang);
            $this->setExcelNumber($sheet, 'AD'.($row + 2), $activity->tkbm_count);
            $this->setExcelText($sheet, 'AD'.($row + 3), $activity->foreman);

            $this->setExcelNumber($sheet, 'H'.($row + 5), $activity->qty_delivery_current);
            $this->setExcelNumber($sheet, 'H'.($row + 6), $activity->qty_delivery_prev);
            $this->setExcelNumber($sheet, 'H'.($row + 7), (float) $activity->qty_delivery_current + (float) $activity->qty_delivery_prev);
            $this->setExcelNumber($sheet, 'S'.($row + 5), $activity->qty_loading_current);
            $this->setExcelNumber($sheet, 'S'.($row + 6), $activity->qty_loading_prev);
            $this->setExcelNumber($sheet, 'S'.($row + 7), (float) $activity->qty_loading_current + (float) $activity->qty_loading_prev);
            $this->setExcelNumber($sheet, 'AD'.($row + 5), $activity->qty_damage_current);
            $this->setExcelNumber($sheet, 'AD'.($row + 6), $activity->qty_damage_prev);

            $deliveryLogs = $activity->timesheets->where('category', 'delivery')->values();
            $loadingLogs = $activity->timesheets->where('category', 'loading')->values();

            foreach (range(0, 3) as $logIndex) {
                $targetRow = $row + 10 + $logIndex;
                $deliveryLog = $deliveryLogs->get($logIndex);
                $loadingLog = $loadingLogs->get($logIndex);

                $this->setExcelText($sheet, 'C'.$targetRow, $deliveryLog ? $this->excelTime($deliveryLog->time) : null);
                $this->setExcelText($sheet, 'G'.$targetRow, $deliveryLog?->activity);
                $this->setExcelText($sheet, 'R'.$targetRow, $loadingLog ? $this->excelTime($loadingLog->time) : null);
                $this->setExcelText($sheet, 'W'.$targetRow, $loadingLog?->activity);
            }

            $this->setExcelText($sheet, 'H'.($row + 14), $activity->tally_warehouse);
            $this->setExcelText($sheet, 'H'.($row + 15), $activity->driver_name);
            $this->setExcelText($sheet, 'H'.($row + 16), $activity->truck_number);
            $this->setExcelText($sheet, 'Q'.($row + 14), $activity->operator_warehouse);
            $this->setExcelText($sheet, 'Q'.($row + 15), $activity->forklift_warehouse);
            $this->setExcelText($sheet, 'AB'.($row + 14), $activity->tally_ship);
            $this->setExcelText($sheet, 'AB'.($row + 15), $activity->operator_ship);
            $this->setExcelText($sheet, 'AB'.($row + 16), $activity->forklift_ship);
        }
    }

    private function fillExcelBulkLoading(Worksheet $sheet, DailyReport $report): void
    {
        $activities = $report->bulkLoadingActivities->sortBy('sequence')->values();

        foreach ([[61, 66], [70, 75]] as $index => [$row, $logRow]) {
            $activity = $activities->get($index);

            foreach (range(0, 3) as $offset) {
                $this->clearExcelCells($sheet, ['H'.($row + $offset), 'Z'.($row + $offset), 'AF'.($row + $offset)]);
            }

            foreach (range(0, 3) as $offset) {
                $this->clearExcelCells($sheet, ['C'.($logRow + $offset), 'G'.($logRow + $offset), 'L'.($logRow + $offset), 'AC'.($logRow + $offset)]);
            }

            if (! $activity) {
                continue;
            }

            $this->setExcelText($sheet, 'H'.$row, $activity->ship_name);
            $this->setExcelText($sheet, 'H'.($row + 1), $activity->agent);
            $this->setExcelText($sheet, 'H'.($row + 2), $activity->commodity);
            $this->setExcelNumber($sheet, 'H'.($row + 3), $activity->capacity);
            $this->setExcelText($sheet, 'Z'.$row, $this->excelDate($activity->berthing_time));
            $this->setExcelText($sheet, 'AF'.$row, $this->excelTime($activity->berthing_time));
            $this->setExcelText($sheet, 'Z'.($row + 1), $this->excelDate($activity->start_loading_time));
            $this->setExcelText($sheet, 'AF'.($row + 1), $this->excelTime($activity->start_loading_time));
            $this->setExcelText($sheet, 'Z'.($row + 2), $activity->destination);
            $this->setExcelText($sheet, 'Z'.($row + 3), $activity->stevedoring);

            foreach (range(0, 3) as $offset) {
                $log = $activity->logs->values()->get($offset);
                $targetRow = $logRow + $offset;

                $this->setExcelText($sheet, 'C'.$targetRow, $log ? $this->excelDate($log->datetime) : null);
                $this->setExcelText($sheet, 'G'.$targetRow, $log ? $this->excelTime($log->datetime) : null);
                $this->setExcelText($sheet, 'L'.$targetRow, $log?->activity);
                $this->setExcelNumber($sheet, 'AC'.$targetRow, $log?->cob);
            }
        }
    }

    private function fillExcelMaterialAndContainer(Worksheet $sheet, DailyReport $report): void
    {
        // Template Excel hanya punya sel tetap untuk satu kegiatan; kalau ada
        // beberapa kegiatan bongkar, yang diekspor ke Excel adalah yang pertama.
        $material = $report->materialActivity->sortBy('sequence')->first();
        $container = $report->containerActivity->sortBy('sequence')->first();

        $this->clearExcelCells($sheet, ['H81', 'H82', 'H83', 'G89', 'O89', 'G90', 'O90', 'G91']);
        $this->clearExcelCells($sheet, ['Y81', 'Y82', 'Z83', 'AF83', 'S85', 'W85', 'Z85', 'S86', 'W86', 'Z86', 'S87', 'W87', 'Z87', 'AC87', 'W89', 'W90', 'W91']);

        foreach ([85, 86, 87] as $row) {
            $this->clearExcelCells($sheet, ['B'.$row, 'F'.$row, 'J'.$row, 'N'.$row]);
        }

        if ($material) {
            $this->setExcelText($sheet, 'H81', $material->ship_name);
            $this->setExcelText($sheet, 'H82', $material->agent);
            $this->setExcelNumber($sheet, 'H83', $material->capacity);
            $this->setExcelText($sheet, 'G89', $material->ship_tally_names);
            $this->setExcelText($sheet, 'O89', $material->forklift_operator_names);
            $this->setExcelText($sheet, 'G90', $material->delivery_tally_names);
            $this->setExcelText($sheet, 'O90', $material->working_hours);
            $this->setExcelText($sheet, 'G91', $material->driver_names);

            foreach ($material->items->values()->take(3) as $index => $item) {
                $row = 85 + $index;

                $this->setExcelText($sheet, 'B'.$row, $item->raw_material_type);
                $this->setExcelNumber($sheet, 'F'.$row, $item->qty_current);
                $this->setExcelNumber($sheet, 'J'.$row, $item->qty_prev);
                $this->setExcelNumber($sheet, 'N'.$row, $item->qty_total);
            }
        }

        if ($container) {
            $this->setExcelText($sheet, 'Y81', $container->ship_name);
            $this->setExcelText($sheet, 'Y82', $container->agent);
            $this->setExcelNumber($sheet, 'Z83', $container->capacity_empty ?? $container->capacity);
            $this->setExcelNumber($sheet, 'AF83', $container->capacity_full);
            $this->setExcelText($sheet, 'W89', $container->ship_tally_names);
            $this->setExcelText($sheet, 'W90', $container->gudang_tally_names);
            $this->setExcelText($sheet, 'W91', $container->driver_names);

            $usedRows = [];

            foreach ($container->items->values()->take(3) as $index => $item) {
                $status = strtolower((string) $item->status);
                $row = str_contains($status, 'empty') ? 85 : (str_contains($status, 'full') ? 86 : 87);

                if (in_array($row, $usedRows, true)) {
                    $row = 87;
                }

                $usedRows[] = $row;

                $this->setExcelText($sheet, 'S'.$row, $item->time ? $this->excelTime($item->time) : null);
                $this->setExcelNumber($sheet, 'W'.$row, $item->qty_current);
                $this->setExcelNumber($sheet, 'Z'.$row, $item->qty_prev);

                if ($row === 87) {
                    $this->setExcelNumber($sheet, 'AC'.$row, $item->qty_total);
                } else {
                    $this->setExcelFormula($sheet, 'AC'.$row, '=Z'.$row.'+W'.$row);
                }
            }
        }
    }

    private function fillExcelTurba(Worksheet $sheet, DailyReport $report): void
    {
        $turba = $report->turbaActivity;

        foreach (range(97, 101) as $row) {
            $this->clearExcelCells($sheet, ['E'.$row, 'L'.$row, 'O'.$row, 'S'.$row, 'W'.$row, 'AA'.$row]);
            $this->setExcelFormula($sheet, 'AE'.$row, '=AA'.$row.'+W'.$row);
        }

        $this->clearExcelCells($sheet, ['I102', 'U102', 'AF102', 'I103']);

        if (! $turba) {
            return;
        }

        foreach ($turba->deliveries->values()->take(5) as $index => $delivery) {
            $row = 97 + $index;

            $this->setExcelText($sheet, 'E'.$row, $delivery->truck_name);
            $this->setExcelText($sheet, 'L'.$row, $delivery->do_so_number);
            $this->setExcelNumber($sheet, 'O'.$row, $delivery->capacity);
            $this->setExcelText($sheet, 'S'.$row, $delivery->marking_type);
            $this->setExcelNumber($sheet, 'W'.$row, $delivery->qty_current);
            $this->setExcelNumber($sheet, 'AA'.$row, $delivery->qty_prev);
        }

        $this->setExcelText($sheet, 'I102', $turba->tally_gudang_names);
        $this->setExcelText($sheet, 'U102', $turba->forklift_operator_names);
        $this->setExcelText($sheet, 'AF102', $turba->working_hours);
        $this->setExcelText($sheet, 'I103', $turba->driver_names);
    }

    private function fillExcelUnits(Worksheet $sheet, DailyReport $report): void
    {
        $vehicleRows = array_merge(
            array_map(fn ($row): array => [$row, 'B', 'D', 'I', 'L', 'O'], range(109, 135)),
            array_map(fn ($row): array => [$row, 'S', 'U', 'Z', 'AC', 'AF'], range(109, 123)),
        );

        $vehicles = $report->unitCheckLogs
            ->where('category', 'vehicle')
            ->sortBy(fn ($log): int => (int) ($log->master_id ?: $log->id))
            ->values();

        foreach ($vehicleRows as $index => [$row, $noCol, $nameCol, $fuelCol, $receivedCol, $handedCol]) {
            $log = $vehicles->get($index);
            $this->clearExcelCells($sheet, [$fuelCol.$row, $receivedCol.$row, $handedCol.$row]);

            if (! $log) {
                continue;
            }

            $this->setExcelNumber($sheet, $noCol.$row, $index + 1);
            $this->setExcelText($sheet, $nameCol.$row, $log->item_name);
            $this->setExcelText($sheet, $fuelCol.$row, $log->fuel_level);
            $this->setExcelText($sheet, $receivedCol.$row, $log->condition_received);
            $this->setExcelText($sheet, $handedCol.$row, $log->condition_handed_over);
        }

        $inventories = $report->unitCheckLogs
            ->where('category', 'inventory')
            ->sortBy(fn ($log): int => (int) ($log->master_id ?: $log->id))
            ->values();

        foreach (range(128, 140) as $index => $row) {
            $log = $inventories->get($index);
            $this->clearExcelCells($sheet, ['AC'.$row, 'AF'.$row]);

            if (! $log) {
                continue;
            }

            $this->setExcelNumber($sheet, 'S'.$row, $index + 1);
            $this->setExcelText($sheet, 'U'.$row, $log->item_name);
            $this->setExcelNumber($sheet, 'Z'.$row, $log->quantity);
            $this->setExcelText($sheet, 'AC'.$row, $log->condition_received);
            $this->setExcelText($sheet, 'AF'.$row, $log->condition_handed_over);
        }

        $shelterLogs = $report->unitCheckLogs->where('category', 'shelter')->keyBy('item_name');
        $shelterRows = [
            'Ruangan Shelter' => 146,
            'Halaman Shelter' => 147,
            'Selokan/Parit' => 148,
            'Jala-Jala Angkat' => 150,
            'Jala-Jala Lambung' => 151,
            'Terpal' => 152,
            'Chain Sling' => 153,
        ];

        foreach ($shelterRows as $itemName => $row) {
            $log = $shelterLogs->get($itemName);
            $this->clearExcelCells($sheet, ['AC'.$row, 'AF'.$row]);

            if (! $log) {
                continue;
            }

            $this->setExcelText($sheet, 'AC'.$row, $log->condition_received);
            $this->setExcelText($sheet, 'AF'.$row, $log->condition_handed_over);
        }
    }

    private function fillExcelEmployees(Worksheet $sheet, DailyReport $report): void
    {
        $employees = $report->employeeLogs;
        $shiftEmployees = $employees->where('category', 'shift')->values();

        $sheet->getStyle('P140:Q152')->getFont()->setSize(10)->setBold(false);
        $sheet->getStyle('P140:Q152')->getAlignment()->setWrapText(true)->setShrinkToFit(true);

        foreach (range(140, 152) as $index => $row) {
            $employee = $shiftEmployees->get($index);
            $this->clearExcelCells($sheet, ['D'.$row, 'J'.$row, 'M'.$row, 'P'.$row]);
            $this->setExcelNumber($sheet, 'B'.$row, $index + 1);

            if (! $employee) {
                continue;
            }

            $this->setExcelText($sheet, 'D'.$row, $employee->name);
            $this->setExcelText($sheet, 'J'.$row, $this->excelTime($employee->time_in));
            $this->setExcelText($sheet, 'M'.$row, $this->excelTime($employee->time_out));
            $this->setExcelText($sheet, 'P'.$row, $employee->description);
        }

        $overtimeEmployees = $employees
            ->where('category', 'operasi')
            ->filter(fn ($employee): bool => str_contains(strtolower((string) $employee->description), 'lembur'))
            ->values();
        $reliefEmployees = $employees
            ->where('category', 'operasi')
            ->filter(fn ($employee): bool => str_contains(strtolower((string) $employee->description), 'relief'))
            ->values();
        $otherActivities = $employees->where('category', 'lain')->values();

        foreach (range(156, 162) as $index => $row) {
            $overtime = $overtimeEmployees->get($index);
            $relief = $reliefEmployees->get($index);

            $this->clearExcelCells($sheet, ['D'.$row, 'L'.$row]);
            $this->setExcelText($sheet, 'D'.$row, $overtime?->name);
            $this->setExcelText($sheet, 'L'.$row, $relief?->name);
        }

        foreach (range(157, 162) as $index => $row) {
            $activity = $otherActivities->get($index);

            $this->clearExcelCells($sheet, ['S'.$row, 'Z'.$row, 'AE'.$row]);

            if ($activity) {
                $this->setExcelText($sheet, 'S'.$row, $activity->description);
                $this->setExcelText($sheet, 'Z'.$row, $activity->personil_count ?: $activity->name);
                $this->setExcelText($sheet, 'AE'.$row, $activity->work_time ?: $this->excelTimeRange($activity->time_in, $activity->time_out));
            }
        }
    }

    private function clearExcelCells(Worksheet $sheet, array $cells): void
    {
        foreach ($cells as $cell) {
            $sheet->setCellValue($cell, null);
        }
    }

    private function setExcelText(Worksheet $sheet, string $cell, mixed $value): void
    {
        if ($value === null || $value === '') {
            $sheet->setCellValue($cell, null);

            return;
        }

        $sheet->setCellValueExplicit($cell, (string) $value, DataType::TYPE_STRING);
    }

    private function setExcelNumber(Worksheet $sheet, string $cell, mixed $value): void
    {
        if ($value === null || $value === '') {
            $sheet->setCellValue($cell, null);

            return;
        }

        $sheet->setCellValue($cell, is_numeric($value) ? (float) $value : $value);
    }

    private function setExcelFormula(Worksheet $sheet, string $cell, string $formula): void
    {
        $sheet->setCellValue($cell, $formula);
    }

    private function excelDay(mixed $value): ?string
    {
        return $this->formatExcelDateValue($value, 'l');
    }

    private function excelDate(mixed $value): ?string
    {
        return $this->formatExcelDateValue($value, 'd F Y');
    }

    private function excelDateTime(mixed $value): ?string
    {
        return $this->formatExcelDateValue($value, 'd/m H:i');
    }

    private function excelTime(mixed $value): ?string
    {
        return $this->formatExcelDateValue($value, 'H:i');
    }

    private function excelTimeRange(mixed $start, mixed $end): ?string
    {
        $start = $this->excelTime($start);
        $end = $this->excelTime($end);

        if ($start === null && $end === null) {
            return null;
        }

        return trim(($start ?? '').' - '.($end ?? ''), ' -');
    }

    private function formatExcelDateValue(mixed $value, string $format): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->locale('id')->translatedFormat($format);
        } catch (Throwable) {
            return null;
        }
    }

    private function rules(bool $isDraft, ?Request $request = null): array
    {
        $requiredWhenSubmit = $isDraft ? 'nullable' : 'required';
        $senderGroup = strtoupper(trim((string) $request?->input('group_name')));

        return [
            'status' => ['required', Rule::in([ReportStatus::Draft->value, ReportStatus::Submitted->value])],
            'form_payload' => ['nullable', 'json'],
            'report_date' => [
                $requiredWhenSubmit,
                'date',
                function (string $attribute, mixed $value, callable $fail) use ($isDraft, $request): void {
                    if ($isDraft || ! $request || blank($value)) {
                        return;
                    }

                    $shift = trim((string) $request->input('shift'));
                    $group = strtoupper(trim((string) $request->input('group_name')));

                    if ($shift === '' || $group === '') {
                        return;
                    }

                    $current = $request->route('report');

                    // Batas maksimal laporan per regu untuk satu tanggal (3 shift).
                    // Sesuai kebijakan, regu boleh mengirim hingga 3 laporan pada
                    // tanggal yang sama -- termasuk pada shift yang sama (mis. koreksi
                    // atau kiriman ulang) -- sehingga tidak lagi diblokir keras hanya
                    // karena kombinasi tanggal+shift+regu berulang. Petugas cukup
                    // diingatkan lewat peringatan ringan di modal konfirmasi sebelum
                    // mengirim. Yang ditolak keras hanya laporan ke-4 (berlebih).
                    $dailyCount = DailyReport::query()
                        ->whereDate('report_date', $value)
                        ->where('group_name', $group)
                        ->where('status', '!=', ReportStatus::Draft->value)
                        ->when($current instanceof DailyReport, fn ($query) => $query->whereKeyNot($current->getKey()))
                        ->count();

                    if ($dailyCount >= self::MAX_DAILY_REPORTS_PER_GROUP) {
                        $fail('Sudah ada '.self::MAX_DAILY_REPORTS_PER_GROUP.' laporan dari regu '.$group.' untuk tanggal ini (batas maksimal per hari). Periksa Riwayat Laporan bila ada yang perlu diperbaiki.');

                        return;
                    }

                    // Kelonggaran shift malam: shift Malam melewati tengah malam
                    // (23.00-07.00), jadi satu shift yang sama bisa terlanjur diisi
                    // dengan tanggal berbeda -- jam 23.00 (hari mulai) atau setelah
                    // lewat tengah malam (hari berikutnya). Untuk itu, laporan Malam
                    // regu sama di tanggal berdekatan (+-1 hari) TIDAK diblokir keras,
                    // melainkan diberi peringatan agar petugas memastikan ini bukan
                    // shift yang sama. Petugas mengonfirmasi via confirm_adjacent_night.
                    if (strtolower($shift) !== 'malam') {
                        return;
                    }

                    if ($request->boolean('confirm_adjacent_night')) {
                        return;
                    }

                    $date = Carbon::parse($value);

                    $adjacent = DailyReport::query()
                        ->where('shift', $shift)
                        ->where('group_name', $group)
                        ->where('status', '!=', ReportStatus::Draft->value)
                        ->whereDate('report_date', '!=', $date->toDateString())
                        ->whereBetween('report_date', [
                            $date->copy()->subDay()->toDateString(),
                            $date->copy()->addDay()->toDateString(),
                        ])
                        ->when($current instanceof DailyReport, fn ($query) => $query->whereKeyNot($current->getKey()))
                        ->orderBy('report_date')
                        ->first(['report_date']);

                    if ($adjacent) {
                        $adjacentDate = Carbon::parse($adjacent->report_date)
                            ->locale('id')->translatedFormat('d F Y');

                        session()->flash('night_shift_adjacent', $adjacentDate);

                        $fail("Sudah ada laporan Shift Malam regu {$group} di tanggal berdekatan ({$adjacentDate}). Karena shift malam melewati tengah malam, pastikan ini bukan shift yang sama yang terlanjur beda tanggal. Jika memang shift malam yang berbeda, centang konfirmasi lalu kirim ulang.");
                    }
                },
            ],
            'shift' => [$requiredWhenSubmit, 'string', 'max:20'],
            'group_name' => [$requiredWhenSubmit, 'string', 'max:10'],
            'received_by_group' => [
                $requiredWhenSubmit,
                'string',
                'max:10',
                function (string $attribute, mixed $value, callable $fail) use ($isDraft, $senderGroup): void {
                    $receiverGroup = strtoupper(trim((string) $value));

                    if (! $isDraft && $senderGroup !== '' && $receiverGroup !== '' && $senderGroup === $receiverGroup) {
                        $fail('Group/regu penerima harus berbeda dari group/regu pengirim.');
                    }
                },
            ],
            'time_range' => [$requiredWhenSubmit, 'string', 'max:50'],
            'timesheets' => ['nullable', 'array'],
            'bulk_logs' => ['nullable', 'array'],
            'unloading_materials' => ['nullable', 'array'],
            'unloading_containers' => ['nullable', 'array'],
            'turba_deliveries' => ['nullable', 'array'],
            'unit_logs' => ['nullable', 'array'],
            'inventory_logs' => ['nullable', 'array'],
            'shelter_logs' => ['nullable', 'array'],
            'employee_shift_logs' => ['nullable', 'array'],
            'relief_logs' => ['nullable', 'array'],
            'overtime_logs' => ['nullable', 'array'],
            'op7_logs' => ['nullable', 'array'],
            'replacement_logs' => ['nullable', 'array'],
            'other_activity_logs' => ['nullable', 'array'],
        ];
    }

    private function attributes(): array
    {
        return [
            'report_date' => 'hari/tanggal',
            'group_name' => 'group/regu',
            'received_by_group' => 'group/regu penerima',
            'time_range' => 'jam kerja',
        ];
    }

    private function storeDetails(DailyReport $report, Request $request): void
    {
        $reportDate = $request->input('report_date');

        // Draft belum dikirim ke regu penerima, jadi operasi kapalnya tidak boleh
        // ikut tersimpan/menjadi saran (suggestion) bagi laporan lain. Ship operation
        // baru dibuat/diperbarui saat laporan benar-benar dikirim (status Submitted).
        $isDraft = $report->status === ReportStatus::Draft;

        $this->pruneStaleShipOperations();

        for ($i = 1; $i <= 20; $i++) {
            if (! $this->hasAny($request, [
                "ship_name_{$i}",
                "agent_{$i}",
                "jetty_{$i}",
                "destination_{$i}",
                "capacity_{$i}",
                "timesheets.{$i}",
                "tally_warehouse_{$i}",
                "driver_name_{$i}",
                "truck_number_{$i}",
                "tally_ship_{$i}",
                "operator_ship_{$i}",
                "forklift_ship_{$i}",
                "operator_warehouse_{$i}",
                "forklift_warehouse_{$i}",
            ])) {
                continue;
            }

            $loadingData = [
                'sequence' => $i,
                'ship_name' => $this->string($request->input("ship_name_{$i}")),
                'agent' => $this->string($request->input("agent_{$i}")),
                'jetty' => $this->string($request->input("jetty_{$i}")),
                'destination' => $this->string($request->input("destination_{$i}")),
                'capacity' => $this->decimal($request->input("capacity_{$i}")),
                'wo_number' => $this->string($request->input("wo_number_{$i}")),
                'cargo_type' => $this->string($request->input("cargo_type_{$i}")),
                'marking' => $this->string($request->input("marking_{$i}")),
                'arrival_time' => $this->dateTime($request->input("arrival_time_{$i}"), $reportDate),
                'operating_gang' => $this->string($request->input("operating_gang_{$i}")),
                'tkbm_count' => $this->integer($request->input("tkbm_count_{$i}")),
                'foreman' => $this->string($request->input("foreman_{$i}")),
                'qty_delivery_current' => $this->decimal($request->input("qty_delivery_current_{$i}")),
                'qty_delivery_prev' => $this->decimal($request->input("qty_delivery_prev_{$i}")),
                'qty_loading_current' => $this->decimal($request->input("qty_loading_current_{$i}")),
                'qty_loading_prev' => $this->decimal($request->input("qty_loading_prev_{$i}")),
                'qty_damage_current' => $this->decimal($request->input("qty_damage_current_{$i}")),
                'qty_damage_prev' => $this->decimal($request->input("qty_damage_prev_{$i}")),
                'tally_warehouse' => $this->string($request->input("tally_warehouse_{$i}")),
                'driver_name' => $this->string($request->input("driver_name_{$i}")),
                'truck_number' => $this->string($request->input("truck_number_{$i}")),
                'tally_ship' => $this->string($request->input("tally_ship_{$i}")),
                'operator_ship' => $this->string($request->input("operator_ship_{$i}")),
                'forklift_ship' => $this->string($request->input("forklift_ship_{$i}")),
                'operator_warehouse' => $this->string($request->input("operator_warehouse_{$i}")),
                'forklift_warehouse' => $this->string($request->input("forklift_warehouse_{$i}")),
            ];

            $shipOperation = $isDraft
                ? null
                : $this->resolveShipOperation($report, $request, ShipOperation::TYPE_BAG_LOADING, $i, $loadingData);
            $loadingData['ship_operation_id'] = $shipOperation?->id;

            $loadingActivity = $report->loadingActivities()->create($loadingData);

            foreach ((array) $request->input("timesheets.{$i}", []) as $category => $entries) {
                foreach ($this->rows($entries) as $entry) {
                    if ($this->rowHasAny($entry, ['time', 'activity'])) {
                        $loadingActivity->timesheets()->create([
                            'category' => $this->string($category) ?: 'general',
                            'time' => $this->time($entry['time'] ?? null),
                            'activity' => $this->string($entry['activity'] ?? null),
                        ]);
                    }
                }
            }
        }

        for ($i = 1; $i <= 20; $i++) {
            if (! $this->hasAny($request, [
                "ship_name_urea_{$i}",
                "jetty_urea_{$i}",
                "destination_urea_{$i}",
                "agent_urea_{$i}",
                "bulk_logs.{$i}",
            ])) {
                continue;
            }

            $bulkData = [
                'sequence' => $i,
                'ship_name' => $this->string($request->input("ship_name_urea_{$i}")),
                'jetty' => $this->string($request->input("jetty_urea_{$i}")),
                'destination' => $this->string($request->input("destination_urea_{$i}")),
                'agent' => $this->string($request->input("agent_urea_{$i}")),
                'stevedoring' => $this->string($request->input("stevedoring_urea_{$i}")),
                'commodity' => $this->string($request->input("commodity_urea_{$i}")),
                'capacity' => $this->decimal($request->input("capacity_urea_{$i}")),
                'berthing_time' => $this->dateTime($request->input("berthing_time_urea_{$i}"), $reportDate),
                'start_loading_time' => $this->dateTime($request->input("start_loading_time_urea_{$i}"), $reportDate),
            ];

            $shipOperation = $isDraft
                ? null
                : $this->resolveShipOperation($report, $request, ShipOperation::TYPE_BULK_LOADING, $i, $bulkData);
            $bulkData['ship_operation_id'] = $shipOperation?->id;

            $bulkActivity = $report->bulkLoadingActivities()->create($bulkData);

            foreach ($this->rows($request->input("bulk_logs.{$i}", [])) as $log) {
                if ($this->rowHasAny($log, ['time', 'activity', 'cob'])) {
                    $bulkActivity->logs()->create([
                        'datetime' => $this->dateTime($log['time'] ?? null, $reportDate),
                        'activity' => $this->string($log['activity'] ?? null),
                        'cob' => $this->integer($log['cob'] ?? null) ?: null,
                    ]);
                }
            }
        }

        for ($i = 1; $i <= 20; $i++) {
            if (! $this->hasAny($request, [
                "ship_name_material_{$i}", "agent_material_{$i}", "jetty_material_{$i}", "capacity_material_{$i}",
                "unloading_materials_{$i}", "tally_kapal_{$i}", "opr_forklift_{$i}", "no_forklift_bb_{$i}", "tally_pengiriman_{$i}",
                "driver_petugas_bb_{$i}", "truck_petugas_bb_{$i}", "material_work_start_{$i}", "material_work_end_{$i}",
            ])) {
                continue;
            }

            $materialActivity = $report->materialActivity()->create([
                'sequence' => $i,
                'ship_name' => $this->string($request->input("ship_name_material_{$i}")),
                'agent' => $this->string($request->input("agent_material_{$i}")),
                'jetty' => $this->string($request->input("jetty_material_{$i}")),
                'capacity' => $this->decimal($request->input("capacity_material_{$i}")),
                'ship_tally_names' => $this->string($request->input("material_ship_tally_names_{$i}", $request->input("tally_kapal_{$i}"))),
                'forklift_operator_names' => $this->string($request->input("material_forklift_operator_names_{$i}", $request->input("opr_forklift_{$i}"))),
                'forklift_number' => $this->string($request->input("no_forklift_bb_{$i}")),
                'delivery_tally_names' => $this->string($request->input("material_delivery_tally_names_{$i}", $request->input("tally_pengiriman_{$i}"))),
                'driver_names' => $this->string($request->input("material_driver_names_{$i}", $request->input("driver_petugas_bb_{$i}"))),
                'truck_number' => $this->string($request->input("truck_petugas_bb_{$i}")),
                'working_hours' => $this->timeRange($request, "material_work_start_{$i}", "material_work_end_{$i}", "material_working_hours_{$i}"),
            ]);

            foreach ($this->rows($request->input("unloading_materials_{$i}", [])) as $material) {
                if ($this->rowHasAny($material, ['raw_material_type', 'qty_current', 'qty_prev', 'qty_total'])) {
                    $materialActivity->items()->create([
                        'raw_material_type' => $this->string($material['raw_material_type'] ?? null),
                        'qty_current' => $this->decimal($material['qty_current'] ?? null),
                        'qty_prev' => $this->decimal($material['qty_prev'] ?? null),
                        'qty_total' => $this->decimal($material['qty_total'] ?? null),
                    ]);
                }
            }
        }

        for ($i = 1; $i <= 20; $i++) {
            $containerRows = $this->rows($request->input("unloading_containers_{$i}", []));
            $hasContainerRows = array_filter(
                $containerRows,
                fn (array $container): bool => $this->rowHasAny($container, ['time', 'time_text', 'status', 'qty_current', 'qty_prev', 'qty_total'])
            ) !== [];

            if (! $this->hasAny($request, [
                "ship_name_container_{$i}", "agent_container_{$i}", "jetty_container_{$i}", "capacity_container_{$i}", "capacity_full_container_{$i}",
                "tally_muat_{$i}", "tally_gudang_{$i}", "driver_petugas_cont_{$i}", "truck_petugas_cont_{$i}",
            ]) && ! $hasContainerRows) {
                continue;
            }

            $containerCapacityEmpty = $this->decimal($request->input("capacity_container_{$i}"));
            $containerCapacityFull = $this->decimal($request->input("capacity_full_container_{$i}"));

            $containerActivity = $report->containerActivity()->create([
                'sequence' => $i,
                'ship_name' => $this->string($request->input("ship_name_container_{$i}")),
                'agent' => $this->string($request->input("agent_container_{$i}")),
                'jetty' => $this->string($request->input("jetty_container_{$i}")),
                'capacity' => $containerCapacityEmpty,
                'capacity_empty' => $containerCapacityEmpty,
                'capacity_full' => $containerCapacityFull,
                'ship_tally_names' => $this->string($request->input("container_ship_tally_names_{$i}", $request->input("tally_muat_{$i}"))),
                'gudang_tally_names' => $this->string($request->input("container_gudang_tally_names_{$i}", $request->input("tally_gudang_{$i}"))),
                'driver_names' => $this->string($request->input("container_driver_names_{$i}", $request->input("driver_petugas_cont_{$i}"))),
                'truck_number' => $this->string($request->input("truck_petugas_cont_{$i}")),
            ]);

            foreach ($containerRows as $container) {
                if ($this->rowHasAny($container, ['time', 'time_text', 'status', 'qty_current', 'qty_prev', 'qty_total'])) {
                    $containerActivity->items()->create([
                        'time' => $this->time($container['time'] ?? null),
                        'time_text' => $this->string($container['time_text'] ?? null),
                        'status' => $this->string($container['status'] ?? null),
                        'qty_current' => $this->decimal($container['qty_current'] ?? null),
                        'qty_prev' => $this->decimal($container['qty_prev'] ?? null),
                        'qty_total' => $this->decimal($container['qty_total'] ?? null),
                    ]);
                }
            }
        }

        if ($this->hasAny($request, ['tally_gudang_names', 'turba_tally_gudang_terima', 'turba_fl_no', 'turba_trl_no', 'turba_forklift_operator', 'turba_driver_names', 'turba_working_hours', 'turba_work_start', 'turba_work_end', 'turba_ship_name', 'turba_agent', 'turba_jetty', 'turba_deliveries'])) {
            $turba = $report->turbaActivity()->create([
                'tally_gudang_names' => $this->string($request->input('tally_gudang_names')),
                'tally_gudang_terima' => $this->string($request->input('turba_tally_gudang_terima')),
                'forklift_operator_names' => $this->string($request->input('turba_forklift_operator')),
                'fl_no' => $this->string($request->input('turba_fl_no')),
                'driver_names' => $this->string($request->input('turba_driver_names')),
                'trl_no' => $this->string($request->input('turba_trl_no')),
                'working_hours' => $this->timeRange($request, 'turba_work_start', 'turba_work_end', 'turba_working_hours'),
            ]);

            foreach ($this->rows($request->input('turba_deliveries', [])) as $delivery) {
                if ($this->rowHasAny($delivery, ['truck_name', 'do_so_number', 'capacity', 'marking_type', 'qty_current', 'qty_prev', 'qty_accumulated'])) {
                    $turba->deliveries()->create([
                        'truck_name' => $this->string($delivery['truck_name'] ?? null),
                        'do_so_number' => $this->string($delivery['do_so_number'] ?? null),
                        'capacity' => $this->decimal($delivery['capacity'] ?? null),
                        'marking_type' => $this->string($delivery['marking_type'] ?? null),
                        'qty_current' => $this->decimal($delivery['qty_current'] ?? null),
                        'qty_prev' => $this->decimal($delivery['qty_prev'] ?? null),
                        'qty_accumulated' => $this->decimal($delivery['qty_accumulated'] ?? null),
                    ]);
                }
            }
        }

        $this->storeUnitChecks($report, $request);
        $this->storeEmployeeLogs($report, $request);
    }

    private function storeUnitChecks(DailyReport $report, Request $request): void
    {
        foreach ($this->rows($request->input('unit_logs', [])) as $log) {
            if ($this->rowHasAny($log, ['item_name', 'master_unit_id', 'fuel_level', 'condition_received', 'condition_handed_over'])) {
                $masterId = $this->string($log['master_unit_id'] ?? null);

                $report->unitCheckLogs()->create([
                    'category' => 'vehicle',
                    'item_name' => $this->string($log['item_name'] ?? null)
                        ?: ($masterId ? MasterUnit::find($masterId)?->name : null)
                        ?: 'Unit ID: '.$this->string($log['master_unit_id'] ?? 'Unknown'),
                    'master_id' => $masterId,
                    'fuel_level' => $this->nonNegativeNumericString($log['fuel_level'] ?? null),
                    'condition_received' => $this->string($log['condition_received'] ?? null),
                    'condition_handed_over' => $this->string($log['condition_handed_over'] ?? null),
                ]);
            }
        }

        foreach ($this->rows($request->input('inventory_logs', [])) as $log) {
            if ($this->rowHasAny($log, ['item_name', 'master_inventory_item_id', 'quantity', 'condition_received', 'condition_handed_over'])) {
                $masterId = $this->string($log['master_inventory_item_id'] ?? null);

                $report->unitCheckLogs()->create([
                    'category' => 'inventory',
                    'item_name' => $this->string($log['item_name'] ?? null)
                        ?: ($masterId ? MasterInventoryItem::find($masterId)?->name : null)
                        ?: 'Item ID: '.$this->string($log['master_inventory_item_id'] ?? 'Unknown'),
                    'master_id' => $masterId,
                    'quantity' => max(1, $this->integer($log['quantity'] ?? 1)),
                    'condition_received' => $this->string($log['condition_received'] ?? null),
                    'condition_handed_over' => $this->string($log['condition_handed_over'] ?? null),
                ]);
            }
        }

        foreach ($this->rows($request->input('shelter_logs', [])) as $log) {
            if ($this->rowHasAny($log, ['item_name', 'condition_received', 'condition_handed_over'])) {
                $report->unitCheckLogs()->create([
                    'category' => 'shelter',
                    'item_name' => $this->string($log['item_name'] ?? null),
                    'condition_received' => $this->string($log['condition_received'] ?? null),
                    'condition_handed_over' => $this->string($log['condition_handed_over'] ?? null),
                ]);
            }
        }
    }

    private function storeEmployeeLogs(DailyReport $report, Request $request): void
    {
        for ($i = 1; $i <= 80; $i++) {
            if ($this->hasAny($request, ["shift_nama_{$i}", "shift_masuk_{$i}", "shift_pulang_{$i}", "shift_ket_{$i}"])) {
                $report->employeeLogs()->create([
                    'category' => 'shift',
                    'name' => $this->string($request->input("shift_nama_{$i}")),
                    'time_in' => $this->time($request->input("shift_masuk_{$i}")),
                    'time_out' => $this->time($request->input("shift_pulang_{$i}")),
                    'description' => $this->string($request->input("shift_ket_{$i}")),
                ]);
            }
        }

        for ($i = 1; $i <= 80; $i++) {
            if ($this->filled($request->input("lembur_{$i}"))) {
                $report->employeeLogs()->create([
                    'category' => 'operasi',
                    'name' => $this->string($request->input("lembur_{$i}")),
                    'description' => 'Lembur',
                ]);
            }

            if ($this->filled($request->input("relief_{$i}"))) {
                $report->employeeLogs()->create([
                    'category' => 'operasi',
                    'name' => $this->string($request->input("relief_{$i}")),
                    'description' => 'Relief',
                ]);
            }
        }

        for ($i = 1; $i <= 20; $i++) {
            if ($this->hasAny($request, ["kegiatan_desc_{$i}", "kegiatan_personil_{$i}", "kegiatan_jam_{$i}"])) {
                [$timeIn, $timeOut] = $this->splitTimeRange($request->input("kegiatan_jam_{$i}"));

                $report->employeeLogs()->create([
                    'category' => 'lain',
                    'name' => $this->string($request->input("kegiatan_personil_{$i}")),
                    'personil_count' => $this->string($request->input("kegiatan_personil_{$i}")),
                    'time_in' => $timeIn,
                    'time_out' => $timeOut,
                    'work_time' => $this->string($request->input("kegiatan_jam_{$i}")),
                    'description' => $this->string($request->input("kegiatan_desc_{$i}")),
                ]);
            }
        }

        foreach ($this->rows($request->input('employee_shift_logs', [])) as $log) {
            if ($this->rowHasAny($log, ['name', 'time_in', 'time_out', 'description'])) {
                $report->employeeLogs()->create([
                    'category' => 'shift',
                    'name' => $this->string($log['name'] ?? null),
                    'time_in' => $this->time($log['time_in'] ?? null),
                    'time_out' => $this->time($log['time_out'] ?? null),
                    'description' => $this->string($log['description'] ?? null),
                ]);
            }
        }

        foreach ($this->rows($request->input('relief_logs', [])) as $log) {
            if ($this->rowHasAny($log, ['name', 'work_time'])) {
                [$timeIn, $timeOut] = $this->splitTimeRange($log['work_time'] ?? null);

                $report->employeeLogs()->create([
                    'category' => 'operasi',
                    'name' => $this->string($log['name'] ?? null),
                    'time_in' => $timeIn,
                    'time_out' => $timeOut,
                    'work_time' => $this->string($log['work_time'] ?? null),
                    'description' => 'Relief',
                ]);
            }
        }

        foreach ($this->rows($request->input('overtime_logs', [])) as $log) {
            if ($this->rowHasAny($log, ['name', 'work_time'])) {
                [$timeIn, $timeOut] = $this->splitTimeRange($log['work_time'] ?? null);

                $report->employeeLogs()->create([
                    'category' => 'operasi',
                    'name' => $this->string($log['name'] ?? null),
                    'time_in' => $timeIn,
                    'time_out' => $timeOut,
                    'work_time' => $this->string($log['work_time'] ?? null),
                    'description' => 'Lembur',
                ]);
            }
        }

        foreach ($this->rows($request->input('op7_logs', [])) as $log) {
            if ($this->rowHasAny($log, ['name', 'no_forklift_', 'work_area', 'time_in', 'time_out', 'description'])) {
                $report->employeeLogs()->create([
                    'category' => 'op7',
                    'name' => $this->string($log['name'] ?? null),
                    'no_forklift_' => $this->string($log['no_forklift_'] ?? null),
                    'work_area' => $this->string($log['work_area'] ?? null),
                    'time_in' => $this->time($log['time_in'] ?? null),
                    'time_out' => $this->time($log['time_out'] ?? null),
                    'description' => $this->string($log['description'] ?? null),
                ]);
            }
        }

        foreach ($this->rows($request->input('replacement_logs', [])) as $log) {
            if ($this->rowHasAny($log, ['name', 'no_forklift_', 'work_area', 'time_in', 'time_out', 'description'])) {
                $report->employeeLogs()->create([
                    'category' => 'replacement',
                    'name' => $this->string($log['name'] ?? null),
                    'no_forklift_' => $this->string($log['no_forklift_'] ?? null),
                    'work_area' => $this->string($log['work_area'] ?? null),
                    'time_in' => $this->time($log['time_in'] ?? null),
                    'time_out' => $this->time($log['time_out'] ?? null),
                    'description' => $this->string($log['description'] ?? null),
                ]);
            }
        }

        foreach ($this->rows($request->input('other_activity_logs', [])) as $log) {
            if ($this->rowHasAny($log, ['name', 'description', 'time_in', 'time_out', 'work_time'])) {
                [$timeIn, $timeOut] = $this->splitTimeRange($log['work_time'] ?? null);

                $report->employeeLogs()->create([
                    'category' => 'lain',
                    'name' => $this->string($log['name'] ?? null),
                    'personil_count' => $this->string($log['personil_count'] ?? $log['name'] ?? null),
                    'time_in' => $this->time($log['time_in'] ?? null) ?: $timeIn,
                    'time_out' => $this->time($log['time_out'] ?? null) ?: $timeOut,
                    'work_time' => $this->string($log['work_time'] ?? null),
                    'description' => $this->string($log['description'] ?? null),
                ]);
            }
        }
    }

    private function shipOperationSuggestionItem(ShipOperation $operation, ?int $excludeReportId = null): array
    {
        $accumulation = $this->shipOperationAccumulation($operation, $excludeReportId);

        return [
            'id' => $operation->id,
            'type' => $operation->type,
            'status' => $operation->status,
            'status_label' => match ($operation->status) {
                ShipOperation::STATUS_COMPLETED => 'Selesai',
                ShipOperation::STATUS_INACTIVE => 'Diarsipkan',
                default => 'Aktif',
            },
            'ship_name' => $operation->ship_name,
            'agent' => $operation->agent,
            'jetty' => $operation->jetty,
            'destination' => $operation->destination,
            'capacity' => (float) $operation->capacity,
            'wo_number' => $operation->wo_number,
            'cargo_type' => $operation->cargo_type,
            'marking' => $operation->marking,
            'stevedoring' => $operation->stevedoring,
            'commodity' => $operation->commodity,
            'arrival_time' => $this->dateTimeLocal($operation->arrival_time),
            'berthing_time' => $this->dateTimeLocal($operation->berthing_time),
            'start_loading_time' => $this->dateTimeLocal($operation->start_loading_time),
            'last_report_date' => $operation->last_report_date?->toDateString(),
            'updated_diff' => $operation->updated_at
                ? Carbon::parse($operation->updated_at)->locale('id')->diffForHumans()
                : '-',
            'accumulation' => $accumulation,
        ];
    }

    private function pruneStaleShipOperations(): void
    {
        ShipOperation::pruneStaleActiveSuggestions();
    }

    private function pruneStaleDraftReports(): int
    {
        return DailyReport::pruneStaleDrafts();
    }

    private function deleteIfStaleDraft(DailyReport $report): bool
    {
        if (! $this->isStaleDraft($report)) {
            return false;
        }

        $report->delete();

        return true;
    }

    private function isStaleDraft(DailyReport $report): bool
    {
        if ($report->status !== ReportStatus::Draft) {
            return false;
        }

        $lastTouchedAt = $report->updated_at ?? $report->created_at;

        return $lastTouchedAt !== null && $lastTouchedAt->lt($this->draftExpiryCutoff());
    }

    private function draftExpiryCutoff(): Carbon
    {
        return now()->subDays(DailyReport::DRAFT_TTL_DAYS);
    }

    private function shipOperationAccumulation(ShipOperation $operation, ?int $excludeReportId = null): array
    {
        if ($operation->type !== ShipOperation::TYPE_BAG_LOADING) {
            return [];
        }

        // "Nilai lalu" untuk laporan berikutnya = akumulasi total laporan
        // terakhir (Sekarang + Lalu), bukan sekadar penjumlahan seluruh nilai
        // Sekarang. Dengan begitu nilai Lalu awal yang diisi manual pada
        // laporan pertama ikut terbawa. Contoh: laporan pertama Sekarang 100 &
        // Lalu 50 → laporan berikutnya Lalu = 150.
        $latest = $operation->loadingActivities()
            ->when($excludeReportId, fn ($query) => $query->where('loading_activities.daily_report_id', '!=', $excludeReportId))
            ->join('daily_reports', 'daily_reports.id', '=', 'loading_activities.daily_report_id')
            ->orderByDesc('daily_reports.report_date')
            ->orderByDesc('loading_activities.id')
            ->select('loading_activities.*')
            ->first();

        if (! $latest) {
            return [
                'qty_delivery_prev' => 0.0,
                'qty_loading_prev' => 0.0,
                'qty_damage_prev' => 0.0,
            ];
        }

        return [
            'qty_delivery_prev' => (float) $latest->qty_delivery_current + (float) $latest->qty_delivery_prev,
            'qty_loading_prev' => (float) $latest->qty_loading_current + (float) $latest->qty_loading_prev,
            'qty_damage_prev' => (float) $latest->qty_damage_current + (float) $latest->qty_damage_prev,
        ];
    }

    private function resolveShipOperation(DailyReport $report, Request $request, string $type, int $sequence, array $data): ?ShipOperation
    {
        $shipName = $this->string($data['ship_name'] ?? null);

        if ($shipName === null) {
            return null;
        }

        $idKey = $type === ShipOperation::TYPE_BAG_LOADING
            ? "ship_operation_id_{$sequence}"
            : "ship_operation_urea_id_{$sequence}";
        $statusKey = $type === ShipOperation::TYPE_BAG_LOADING
            ? "ship_operation_status_{$sequence}"
            : "ship_operation_urea_status_{$sequence}";
        $requestedStatus = $request->input($statusKey) === ShipOperation::STATUS_COMPLETED
            ? ShipOperation::STATUS_COMPLETED
            : ShipOperation::STATUS_ACTIVE;

        $operationId = $this->integer($request->input($idKey));
        $operation = $operationId > 0
            ? ShipOperation::where('type', $type)->whereKey($operationId)->first()
            : null;

        if (! $operation) {
            // Kapal terarsip ikut dicocokkan agar operasi yang jeda lama otomatis
            // tersambung kembali (status aktif di-set ulang saat disimpan).
            $query = ShipOperation::query()
                ->where('type', $type)
                ->whereIn('status', [ShipOperation::STATUS_ACTIVE, ShipOperation::STATUS_INACTIVE])
                ->orderByRaw("CASE WHEN status = '".ShipOperation::STATUS_ACTIVE."' THEN 0 ELSE 1 END")
                ->where('ship_name', $shipName);

            if ($type === ShipOperation::TYPE_BAG_LOADING && $this->string($data['wo_number'] ?? null) !== null) {
                $query->where('wo_number', $this->string($data['wo_number']));
            }

            if ($type === ShipOperation::TYPE_BULK_LOADING && $this->string($data['commodity'] ?? null) !== null) {
                $query->where('commodity', $this->string($data['commodity']));
            }

            $operation = $query->first();
        }

        $operation ??= new ShipOperation([
            'type' => $type,
            'created_by' => $request->user()?->id,
            'started_at' => now(),
        ]);

        $operation->fill([
            'status' => $requestedStatus,
            'ship_name' => $shipName,
            'agent' => $this->string($data['agent'] ?? null),
            'jetty' => $this->string($data['jetty'] ?? null),
            'destination' => $this->string($data['destination'] ?? null),
            'capacity' => $this->decimal($data['capacity'] ?? null),
            'wo_number' => $this->string($data['wo_number'] ?? null),
            'cargo_type' => $this->string($data['cargo_type'] ?? null),
            'marking' => $this->string($data['marking'] ?? null),
            'stevedoring' => $this->string($data['stevedoring'] ?? null),
            'commodity' => $this->string($data['commodity'] ?? null),
            'arrival_time' => $data['arrival_time'] ?? null,
            'berthing_time' => $data['berthing_time'] ?? null,
            'start_loading_time' => $data['start_loading_time'] ?? null,
            'last_report_id' => $report->id,
            'last_report_date' => $report->report_date,
        ]);

        if (! $operation->started_at) {
            $operation->started_at = $operation->arrival_time
                ?? $operation->berthing_time
                ?? $operation->start_loading_time
                ?? now();
        }

        if ($requestedStatus === ShipOperation::STATUS_COMPLETED) {
            $operation->completed_at = now();
            $operation->completed_by = $request->user()?->id;
        } else {
            $operation->completed_at = null;
            $operation->completed_by = null;
        }

        $operation->save();

        return $operation;
    }

    private function dateTimeLocal(mixed $value): ?string
    {
        if (! $value) {
            return null;
        }

        try {
            return Carbon::parse($value)->format('Y-m-d\TH:i');
        } catch (Throwable) {
            return null;
        }
    }

    private function masterData(?DailyReport $report = null): array
    {
        return [
            'vehicles' => Cache::remember(
                MasterUnit::MASTER_DATA_CACHE_KEY,
                self::MASTER_DATA_CACHE_TTL,
                fn () => MasterUnit::select('id', 'name', 'unit_code', 'unit_number', 'type')->orderedForReport()->get()->toArray()
            ),
            'inventories' => Cache::remember(
                MasterInventoryItem::MASTER_DATA_CACHE_KEY,
                self::MASTER_DATA_CACHE_TTL,
                fn () => MasterInventoryItem::select('id', 'name', 'stock as qty')->orderBy('id')->get()->toArray()
            ),
            'environments' => Cache::remember(
                MasterEnvironmentItem::MASTER_DATA_CACHE_KEY,
                self::MASTER_DATA_CACHE_TTL,
                fn () => MasterEnvironmentItem::select('id', 'name', 'category')
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->get()
                    ->toArray()
            ),
            'trucks' => Cache::remember(
                MasterTruck::MASTER_DATA_CACHE_KEY,
                self::MASTER_DATA_CACHE_TTL,
                fn () => MasterTruck::select('id', 'name', 'plate_number', 'description')->orderBy('id')->get()->toArray()
            ),
            'employeesGrouped' => Cache::remember(
                MasterEmployee::MASTER_DATA_CACHE_KEY,
                self::MASTER_DATA_CACHE_TTL,
                fn () => MasterEmployee::forOperational()
                    ->where('status', 'active')
                    ->orderBy('id')
                    ->get()
                    ->groupBy('group_name')
                    ->toArray()
            ),
            'lastUnitHandoverConditions' => $this->lastUnitHandoverConditions($report),
            'previousReportPeek' => $this->previousReportPeek($report),
        ];
    }

    /**
     * Laporan non-draft terakhir yang bisa diakses user (dibuat sendiri, atau
     * regu user sebagai pengirim/penerima) — untuk panel "Intip Laporan
     * Sebelumnya" di form tanpa harus keluar dari halaman.
     */
    private function previousReportPeek(?DailyReport $current = null): ?array
    {
        $user = auth()->user();

        if (! $user) {
            return null;
        }

        $userGroup = strtoupper((string) ($user->group ?? ''));

        $previous = DailyReport::query()
            ->select(['id', 'report_date', 'shift', 'group_name', 'received_by_group', 'created_at'])
            ->where('status', '!=', ReportStatus::Draft->value)
            ->when($current, fn ($query) => $query->whereKeyNot($current->getKey()))
            ->where(function ($query) use ($user, $userGroup): void {
                $query->where('created_by', $user->id);

                if ($userGroup !== '') {
                    $query->orWhere('group_name', $userGroup)
                        ->orWhere('received_by_group', $userGroup);
                }
            })
            ->orderByDesc('report_date')
            ->orderByDesc('id')
            ->first();

        if (! $previous) {
            return null;
        }

        $meta = collect([
            $previous->report_date
                ? Carbon::parse($previous->report_date)->locale('id')->translatedFormat('d F Y')
                : null,
            $previous->shift ? 'Shift '.ucfirst((string) $previous->shift) : null,
            $previous->group_name ? 'Regu '.strtoupper((string) $previous->group_name) : null,
        ])->filter()->implode(' — ');

        return [
            'url' => route('report-ops.show', $previous),
            'title' => 'Laporan Operasi Harian Sebelumnya',
            'meta' => $meta,
        ];
    }

    private function lastUnitHandoverConditions(?DailyReport $report = null): array
    {
        $conditions = [
            'vehicle' => ['master' => [], 'name' => []],
            'inventory' => ['master' => [], 'name' => []],
            'shelter' => ['master' => [], 'name' => []],
        ];

        UnitCheckLog::query()
            ->select('unit_check_logs.*')
            ->join('daily_reports', 'daily_reports.id', '=', 'unit_check_logs.daily_report_id')
            ->whereIn('daily_reports.status', [ReportStatus::Submitted, ReportStatus::Acknowledged, ReportStatus::Approved])
            ->when($report, fn ($query) => $query->where('daily_reports.id', '!=', $report->id))
            ->whereNotNull('unit_check_logs.condition_handed_over')
            ->where('unit_check_logs.condition_handed_over', '!=', '')
            ->orderByDesc('daily_reports.report_date')
            ->orderByDesc('daily_reports.id')
            ->orderByDesc('unit_check_logs.id')
            ->get()
            ->each(function (UnitCheckLog $log) use (&$conditions): void {
                $category = (string) $log->category;
                if (! array_key_exists($category, $conditions)) {
                    return;
                }

                $condition = $this->string($log->condition_handed_over);
                if ($condition === null) {
                    return;
                }

                $masterId = $this->string($log->master_id);
                if ($masterId !== null && ! array_key_exists($masterId, $conditions[$category]['master'])) {
                    $conditions[$category]['master'][$masterId] = $condition;
                }

                $itemName = mb_strtolower((string) $this->string($log->item_name));
                if ($itemName !== '' && ! array_key_exists($itemName, $conditions[$category]['name'])) {
                    $conditions[$category]['name'][$itemName] = $condition;
                }
            });

        return $conditions;
    }

    private function deleteDetails(DailyReport $report): void
    {
        $report->loadingActivities()->with('timesheets')->get()->each(function ($activity): void {
            $activity->timesheets()->delete();
            $activity->delete();
        });

        $report->bulkLoadingActivities()->with('logs')->get()->each(function ($activity): void {
            $activity->logs()->delete();
            $activity->delete();
        });

        $report->materialActivity()->with('items')->get()->each(function ($activity): void {
            $activity->items()->delete();
            $activity->delete();
        });

        $report->containerActivity()->with('items')->get()->each(function ($activity): void {
            $activity->items()->delete();
            $activity->delete();
        });

        $turbaActivity = $report->turbaActivity()->with('deliveries')->first();
        if ($turbaActivity) {
            $turbaActivity->deliveries()->delete();
            $turbaActivity->delete();
        }

        $report->unitCheckLogs()->delete();
        $report->employeeLogs()->delete();
    }

    private function canAccessReport(DailyReport $report, mixed $user): bool
    {
        if (! $user) {
            return false;
        }

        if ((int) $report->created_by === (int) $user->id) {
            return true;
        }

        if ($report->status === ReportStatus::Draft) {
            return false;
        }

        $userGroup = strtoupper((string) ($user->group ?? ''));

        if ($userGroup === '') {
            return false;
        }

        return in_array($userGroup, [
            strtoupper((string) $report->group_name),
            strtoupper((string) $report->received_by_group),
        ], true);
    }

    private function canEditReport(DailyReport $report, mixed $user): bool
    {
        if (! $user || ! in_array($report->status, [ReportStatus::Draft, ReportStatus::Submitted], true)) {
            return false;
        }

        return (int) $report->created_by === (int) $user->id;
    }

    private function canDeleteReport(DailyReport $report, mixed $user): bool
    {
        if (! $user || $report->status !== ReportStatus::Draft) {
            return false;
        }

        return (int) $report->created_by === (int) $user->id;
    }

    private function canReceiveReport(DailyReport $report, mixed $user): bool
    {
        if (! $user) {
            return false;
        }

        $userGroup = strtoupper((string) ($user->group ?? ''));

        return $userGroup !== ''
            && strtoupper((string) $report->received_by_group) === $userGroup
            && (int) $report->created_by !== (int) $user->id;
    }

    private function decodePayload(?string $payload): ?array
    {
        if ($payload === null || trim($payload) === '') {
            return null;
        }

        $decoded = json_decode($payload, true);

        return is_array($decoded) ? $decoded : null;
    }

    private function hasAny(Request $request, array $keys): bool
    {
        foreach ($keys as $key) {
            if ($this->filled($request->input($key))) {
                return true;
            }
        }

        return false;
    }

    private function rowHasAny(array $row, array $keys): bool
    {
        foreach ($keys as $key) {
            if ($this->filled($row[$key] ?? null)) {
                return true;
            }
        }

        return false;
    }

    private function rows(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter($value, fn ($row): bool => is_array($row)));
    }

    private function filled(mixed $value): bool
    {
        if (is_array($value)) {
            return array_filter($value, fn ($item): bool => $this->filled($item)) !== [];
        }

        return $value !== null && $value !== '';
    }

    private function string(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : mb_substr($value, 0, 255);
    }

    private function decimal(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        return max(0.0, (float) str_replace(',', '.', preg_replace('/[^\d,.\-]/', '', (string) $value)));
    }

    private function integer(mixed $value): int
    {
        if ($value === null || $value === '') {
            return 0;
        }

        return max(0, (int) preg_replace('/[^\d\-]/', '', (string) $value));
    }

    private function nonNegativeNumericString(mixed $value): ?string
    {
        $text = $this->string($value);

        if ($text === null) {
            return null;
        }

        $normalized = str_replace(',', '.', $text);

        if (preg_match('/^-?\d+(?:\.\d+)?$/', $normalized) === 1) {
            return (string) max(0, (float) $normalized);
        }

        return $text;
    }

    private function time(mixed $value): ?string
    {
        $value = $this->string($value);

        if ($value === null) {
            return null;
        }

        if (preg_match('/^\d{2}:\d{2}$/', $value) !== 1) {
            return null;
        }

        return $value;
    }

    private function timeRange(Request $request, string $startKey, string $endKey, ?string $fallbackKey = null): ?string
    {
        $start = $this->time($request->input($startKey));
        $end = $this->time($request->input($endKey));

        if ($start !== null || $end !== null) {
            return trim(($start ?? '').' - '.($end ?? ''), ' -');
        }

        return $fallbackKey ? $this->string($request->input($fallbackKey)) : null;
    }

    private function splitTimeRange(mixed $value): array
    {
        $value = $this->string($value);

        if ($value === null) {
            return [null, null];
        }

        $parts = preg_split('/\s*[-–]\s*/', $value, 2);

        return [
            $this->time($parts[0] ?? null),
            $this->time($parts[1] ?? null),
        ];
    }

    private function dateTime(mixed $value, ?string $reportDate = null): ?string
    {
        $value = $this->string($value);

        if ($value === null) {
            return null;
        }

        try {
            if (preg_match('/^\d{2}:\d{2}$/', $value) === 1) {
                $date = $reportDate ?: now()->toDateString();

                return Carbon::parse($date.' '.$value)->format('Y-m-d H:i:s');
            }

            return Carbon::parse($value)->format('Y-m-d H:i:s');
        } catch (Throwable) {
            return null;
        }
    }
}
