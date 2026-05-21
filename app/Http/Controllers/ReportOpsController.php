<?php

namespace App\Http\Controllers;

use App\Models\DailyReport;
use App\Models\MasterEmployee;
use App\Models\MasterInventoryItem;
use App\Models\MasterTruck;
use App\Models\MasterUnit;
use App\Models\Role;
use App\Models\ShipOperation;
use App\Models\UnitCheckLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Throwable;

class ReportOpsController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $userGroup = strtoupper((string) ($user->group ?? ''));
        $isAdmin = $this->isAdmin($user);

        $this->pruneStaleDraftReports();

        $incomingReports = DailyReport::with(['creator', 'receiver', 'approver'])
            ->when(! $isAdmin, function ($query) use ($userGroup) {
                if ($userGroup === '') {
                    return $query->whereRaw('1 = 0');
                }

                return $query->where('received_by_group', $userGroup);
            })
            ->where('status', 'submitted')
            ->latest('updated_at')
            ->get();

        $draftReports = DailyReport::with('creator')
            ->where('created_by', $user->id)
            ->where('status', 'draft')
            ->latest('updated_at')
            ->get();

        $historySearch = trim((string) request('history_search', ''));
        $activeTab = request('tab') === 'riwayat' || request()->has('history_page') || $historySearch !== ''
            ? 'riwayat'
            : 'laporan';

        $historyQuery = DailyReport::with([
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
        ])
            ->when(! $isAdmin, function ($query) use ($user, $userGroup) {
                $query->where(function ($innerQuery) use ($user, $userGroup) {
                    $innerQuery->where('created_by', $user->id);

                    if ($userGroup !== '') {
                        $innerQuery->orWhere('group_name', $userGroup)
                            ->orWhere('received_by_group', $userGroup);
                    }
                });
            });

        $this->applyHistorySearch($historyQuery, $historySearch);

        $historyReports = $historyQuery
            ->latest('report_date')
            ->latest('updated_at')
            ->paginate(10, ['*'], 'history_page')
            ->withQueryString();

        $historyReports->appends(['tab' => 'riwayat']);

        return view('report-ops.index', compact('incomingReports', 'draftReports', 'historyReports', 'historySearch', 'activeTab'));
    }

    public function historySuggestions(Request $request)
    {
        $user = $request->user();
        $userGroup = strtoupper((string) ($user->group ?? ''));
        $isAdmin = $this->isAdmin($user);
        $keyword = trim((string) $request->input('q', ''));

        $query = DailyReport::with([
            'creator',
            'receiver',
            'loadingActivities.timesheets',
            'bulkLoadingActivities.logs',
            'materialActivity.items',
            'containerActivity.items',
            'turbaActivity.deliveries',
            'unitCheckLogs',
            'employeeLogs',
        ])
            ->when(! $isAdmin, function ($builder) use ($user, $userGroup): void {
                $builder->where(function ($innerQuery) use ($user, $userGroup): void {
                    $innerQuery->where('created_by', $user->id);

                    if ($userGroup !== '') {
                        $innerQuery->orWhere('group_name', $userGroup)
                            ->orWhere('received_by_group', $userGroup);
                    }
                });
            });

        $this->applyHistorySearch($query, $keyword);

        $reports = $query
            ->latest('report_date')
            ->latest('updated_at')
            ->limit(8)
            ->get();

        $shiftMeta = function ($shift): array {
            $normalized = strtolower(trim((string) $shift));

            return match (true) {
                in_array($normalized, ['1', 'pagi', 'shift 1', 'shift pagi'], true) => ['label' => 'Shift Pagi', 'class' => 'pagi'],
                in_array($normalized, ['2', 'sore', 'siang', 'shift 2', 'shift sore', 'shift siang'], true) => ['label' => 'Shift Sore', 'class' => 'sore'],
                in_array($normalized, ['3', 'malam', 'shift 3', 'shift malam'], true) => ['label' => 'Shift Malam', 'class' => 'malam'],
                default => ['label' => $shift ? 'Shift '.$shift : 'Shift -', 'class' => 'pagi'],
            };
        };

        $statusMeta = function ($status): array {
            return match ($status) {
                'draft' => ['label' => 'Draft', 'class' => 'draft'],
                'submitted' => ['label' => 'Diserahkan', 'class' => 'submit'],
                'acknowledged' => ['label' => 'Ditanda Tangani', 'class' => 'approve'],
                'approved' => ['label' => 'Dikonfirmasi', 'class' => 'confirm'],
                default => ['label' => ucfirst((string) $status), 'class' => 'submit'],
            };
        };

        $items = $reports->map(function (DailyReport $report) use ($shiftMeta, $statusMeta) {
            $date = $report->report_date ?: $report->created_at;
            $year = $date ? Carbon::parse($date)->format('Y') : now()->format('Y');
            $documentId = '#OPS-'.$year.'-'.str_pad((string) $report->id, 3, '0', STR_PAD_LEFT);

            $shift = $shiftMeta($report->shift);
            $status = $statusMeta($report->status);

            return [
                'id' => $report->id,
                'document_id' => $documentId,
                'title' => 'Laporan Shift Harian',
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
        })->values();

        return response()->json([
            'keyword' => $keyword,
            'total' => $items->count(),
            'items' => $items,
        ]);
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

        $operations = ShipOperation::query()
            ->where('type', $validated['type'])
            ->where('status', ShipOperation::STATUS_ACTIVE)
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

    public function create()
    {
        $this->pruneStaleDraftReports();

        return view('report-ops.create', $this->masterData());
    }

    public function store(Request $request)
    {
        $status = $request->input('status') === 'draft' ? 'draft' : 'submitted';
        $request->merge(['status' => $status]);

        $validated = $request->validate($this->rules($status === 'draft', $request), [], $this->attributes());
        $payload = $this->decodePayload($request->input('form_payload'));

        try {
            DB::transaction(function () use ($request, $validated, $payload): void {
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

        $message = $status === 'draft'
            ? 'Draft laporan berhasil disimpan.'
            : 'Laporan operasional berhasil dikirim.';

        return redirect()->route('report-ops.index')->with('success', $message);
    }

    public function show(DailyReport $report)
    {
        abort_unless($this->canAccessReport($report, auth()->user()), 403);

        return view('report-ops.viewpdf', [
            'report' => $this->loadReport($report),
            'isPdf' => false,
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

        $status = $report->status === 'draft' && $request->input('status') === 'draft'
            ? 'draft'
            : 'submitted';

        $request->merge(['status' => $status]);

        $validated = $request->validate($this->rules($status === 'draft', $request), [], $this->attributes());
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

        $message = $status === 'draft'
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

    public function sign(DailyReport $report)
    {
        $user = auth()->user();

        abort_unless($this->canAccessReport($report, $user), 403);

        try {
            if ($report->status === 'submitted') {
                if (! $this->canReceiveReport($report, $user)) {
                    return back()->with('error', 'Anda tidak dapat menandatangani laporan untuk regu lain.');
                }

                $report->update([
                    'status' => 'acknowledged',
                    'received_by_user_id' => $user->id,
                    'received_at' => now(),
                ]);

                return back()->with('success', 'Laporan berhasil diterima dan ditanda tangani.');
            }

            if ($report->status === 'acknowledged') {
                if (! $this->isAdmin($user)) {
                    return back()->with('error', 'Hanya admin atau manajer yang dapat menyetujui laporan yang sudah diterima.');
                }

                $report->update([
                    'status' => 'approved',
                    'approved_by' => $user->id,
                    'approved_at' => now(),
                ]);

                return back()->with('success', 'Laporan berhasil disetujui.');
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

        $report = $this->loadReport($report);

        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('report-ops.pdf', [
                'report' => $report,
                'isPdf' => true,
            ]);

            $pdf->setPaper([0, 0, 612.00, 1008.00], 'portrait');
            $pdf->setOption('isRemoteEnabled', true);

            return $pdf->stream($this->reportExportFileName($report, 'pdf'));
        }

        return view('report-ops.viewpdf', [
            'report' => $report,
            'isPdf' => false,
        ]);
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

            $fileName = $this->reportExportFileName($report, 'xlsx');

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
        $sheet->setTitle('Laporan Shift');

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
        $material = $report->materialActivity;
        $container = $report->containerActivity;

        $this->clearExcelCells($sheet, ['H81', 'H82', 'H83', 'G89', 'O89', 'G90', 'O90', 'G91']);
        $this->clearExcelCells($sheet, ['Y81', 'Y82', 'S85', 'W85', 'Z85', 'S86', 'W86', 'Z86', 'S87', 'W87', 'Z87', 'AC87', 'W89', 'W90', 'W91']);

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

    private function reportExportFileName(DailyReport $report, string $extension): string
    {
        $date = $report->report_date
            ? Carbon::parse($report->report_date)->format('Y-m-d')
            : now()->format('Y-m-d');
        $shift = $this->excelShiftLabel($report->shift);
        $group = strtoupper(trim((string) $report->group_name)) ?: '-';
        $extension = ltrim(strtolower($extension), '.');

        return "Operasional-{$date}-Shift {$shift}-Group {$group}.{$extension}";
    }

    private function excelShiftLabel(mixed $shift): string
    {
        $normalized = strtolower(trim((string) $shift));

        return match (true) {
            in_array($normalized, ['1', 'pagi', 'shift 1', 'shift pagi'], true) => 'Pagi',
            in_array($normalized, ['2', 'siang', 'sore', 'shift 2', 'shift siang', 'shift sore'], true) => 'Siang',
            in_array($normalized, ['3', 'malam', 'shift 3', 'shift malam'], true) => 'Malam',
            default => $shift ? trim((string) $shift) : '-',
        };
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
            'status' => ['required', Rule::in(['draft', 'submitted'])],
            'form_payload' => ['nullable', 'json'],
            'report_date' => [$requiredWhenSubmit, 'date'],
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

        $this->pruneStaleShipOperations();

        for ($i = 1; $i <= 20; $i++) {
            if (! $this->hasAny($request, [
                "ship_name_{$i}",
                "agent_{$i}",
                "jetty_{$i}",
                "destination_{$i}",
                "capacity_{$i}",
                "timesheets.{$i}",
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

            $shipOperation = $this->resolveShipOperation($report, $request, ShipOperation::TYPE_BAG_LOADING, $i, $loadingData);
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

            $shipOperation = $this->resolveShipOperation($report, $request, ShipOperation::TYPE_BULK_LOADING, $i, $bulkData);
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

        if ($this->hasAny($request, ['ship_name_material', 'agent_material', 'capacity_material', 'unloading_materials', 'tally_kapal', 'opr_forklift', 'tally_pengiriman', 'driver_petugas_bb', 'material_work_start', 'material_work_end'])) {
            $materialActivity = $report->materialActivity()->create([
                'ship_name' => $this->string($request->input('ship_name_material')),
                'agent' => $this->string($request->input('agent_material')),
                'capacity' => $this->decimal($request->input('capacity_material')),
                'ship_tally_names' => $this->string($request->input('material_ship_tally_names', $request->input('tally_kapal'))),
                'forklift_operator_names' => $this->string($request->input('material_forklift_operator_names', $request->input('opr_forklift'))),
                'delivery_tally_names' => $this->string($request->input('material_delivery_tally_names', $request->input('tally_pengiriman'))),
                'driver_names' => $this->string($request->input('material_driver_names', $request->input('driver_petugas_bb'))),
                'working_hours' => $this->timeRange($request, 'material_work_start', 'material_work_end', 'material_working_hours'),
            ]);

            foreach ($this->rows($request->input('unloading_materials', [])) as $material) {
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

        $containerRows = $this->rows($request->input('unloading_containers', []));
        $hasContainerRows = array_filter(
            $containerRows,
            fn (array $container): bool => $this->rowHasAny($container, ['time', 'type', 'qty_current', 'qty_prev', 'qty_total'])
        ) !== [];

        if ($this->hasAny($request, ['ship_name_container', 'agent_container', 'capacity_container', 'tally_muat', 'tally_gudang', 'driver_petugas_cont']) || $hasContainerRows) {
            $containerActivity = $report->containerActivity()->create([
                'ship_name' => $this->string($request->input('ship_name_container')),
                'agent' => $this->string($request->input('agent_container')),
                'capacity' => $this->decimal($request->input('capacity_container')),
                'ship_tally_names' => $this->string($request->input('container_ship_tally_names', $request->input('tally_muat'))),
                'gudang_tally_names' => $this->string($request->input('container_gudang_tally_names', $request->input('tally_gudang'))),
                'driver_names' => $this->string($request->input('container_driver_names', $request->input('driver_petugas_cont'))),
            ]);

            foreach ($containerRows as $container) {
                if ($this->rowHasAny($container, ['time', 'type', 'qty_current', 'qty_prev', 'qty_total'])) {
                    $containerActivity->items()->create([
                        'time' => $this->time($container['time'] ?? null),
                        'status' => $this->string($container['status'] ?? null),
                        'qty_current' => $this->decimal($container['qty_current'] ?? null),
                        'qty_prev' => $this->decimal($container['qty_prev'] ?? null),
                        'qty_total' => $this->decimal($container['qty_total'] ?? null),
                    ]);
                }
            }
        }

        if ($this->hasAny($request, ['tally_gudang_names', 'turba_forklift_operator', 'turba_driver_names', 'turba_working_hours', 'turba_work_start', 'turba_work_end', 'turba_ship_name', 'turba_agent', 'turba_jetty', 'turba_deliveries'])) {
            $turba = $report->turbaActivity()->create([
                'tally_gudang_names' => $this->string($request->input('tally_gudang_names')),
                'forklift_operator_names' => $this->string($request->input('turba_forklift_operator')),
                'driver_names' => $this->string($request->input('turba_driver_names')),
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
            if ($this->rowHasAny($log, ['name'])) {
                $report->employeeLogs()->create([
                    'category' => 'operasi',
                    'name' => $this->string($log['name'] ?? null),
                    'description' => 'Relief',
                ]);
            }
        }

        foreach ($this->rows($request->input('overtime_logs', [])) as $log) {
            if ($this->rowHasAny($log, ['name'])) {
                $report->employeeLogs()->create([
                    'category' => 'operasi',
                    'name' => $this->string($log['name'] ?? null),
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
            if ($this->rowHasAny($log, ['name', 'description', 'time_in', 'time_out'])) {
                $report->employeeLogs()->create([
                    'category' => 'lain',
                    'name' => $this->string($log['name'] ?? null),
                    'personil_count' => $this->string($log['personil_count'] ?? $log['name'] ?? null),
                    'time_in' => $this->time($log['time_in'] ?? null),
                    'time_out' => $this->time($log['time_out'] ?? null),
                    'description' => $this->string($log['description'] ?? null),
                ]);
            }
        }
    }

    private function loadReport(DailyReport $report): DailyReport
    {
        return $report->load([
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
        ]);
    }

    private function applyHistorySearch($query, string $keyword): void
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

    private function shipOperationSuggestionItem(ShipOperation $operation, ?int $excludeReportId = null): array
    {
        $accumulation = $this->shipOperationAccumulation($operation, $excludeReportId);

        return [
            'id' => $operation->id,
            'type' => $operation->type,
            'status' => $operation->status,
            'status_label' => $operation->status === ShipOperation::STATUS_COMPLETED ? 'Selesai' : 'Aktif',
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
        $cutoff = now()->subDays(ShipOperation::ACTIVE_SUGGESTION_TTL_DAYS);

        ShipOperation::query()
            ->whereIn('type', [ShipOperation::TYPE_BAG_LOADING, ShipOperation::TYPE_BULK_LOADING])
            ->where('status', ShipOperation::STATUS_ACTIVE)
            ->where(function ($query) use ($cutoff): void {
                $query->where('updated_at', '<', $cutoff)
                    ->orWhere(function ($fallback) use ($cutoff): void {
                        $fallback->whereNull('updated_at')
                            ->where('created_at', '<', $cutoff);
                    });
            })
            ->delete();
    }

    private function pruneStaleDraftReports(): int
    {
        return DailyReport::query()
            ->where('status', 'draft')
            ->where(function ($query): void {
                $query->where('updated_at', '<', $this->draftExpiryCutoff())
                    ->orWhere(function ($fallback): void {
                        $fallback->whereNull('updated_at')
                            ->where('created_at', '<', $this->draftExpiryCutoff());
                    });
            })
            ->delete();
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
        if ($report->status !== 'draft') {
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

        $sum = function (string $column) use ($operation, $excludeReportId): float {
            return (float) $operation->loadingActivities()
                ->when($excludeReportId, fn ($query) => $query->where('daily_report_id', '!=', $excludeReportId))
                ->sum($column);
        };

        return [
            'qty_delivery_prev' => $sum('qty_delivery_current'),
            'qty_loading_prev' => $sum('qty_loading_current'),
            'qty_damage_prev' => $sum('qty_damage_current'),
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
            $query = ShipOperation::query()
                ->where('type', $type)
                ->where('status', ShipOperation::STATUS_ACTIVE)
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
            'vehicles' => MasterUnit::select('id', 'name')->orderBy('id')->get(),
            'inventories' => MasterInventoryItem::select('id', 'name', 'stock as qty')->orderBy('id')->get(),
            'trucks' => MasterTruck::select('id', 'name', 'plate_number', 'description')->orderBy('id')->get(),
            'employeesGrouped' => MasterEmployee::where('status', 'active')
                ->orderBy('id')
                ->get()
                ->groupBy('group_name'),
            'lastUnitHandoverConditions' => $this->lastUnitHandoverConditions($report),
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
            ->whereIn('daily_reports.status', ['submitted', 'acknowledged', 'approved'])
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

        $materialActivity = $report->materialActivity()->with('items')->first();
        if ($materialActivity) {
            $materialActivity->items()->delete();
            $materialActivity->delete();
        }

        $containerActivity = $report->containerActivity()->with('items')->first();
        if ($containerActivity) {
            $containerActivity->items()->delete();
            $containerActivity->delete();
        }

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

        if ($this->isAdmin($user) || (int) $report->created_by === (int) $user->id) {
            return true;
        }

        if ($report->status === 'draft') {
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
        if (! $user || ! in_array($report->status, ['draft', 'submitted'], true)) {
            return false;
        }

        return $this->isAdmin($user) || (int) $report->created_by === (int) $user->id;
    }

    private function canDeleteReport(DailyReport $report, mixed $user): bool
    {
        if (! $user || $report->status !== 'draft') {
            return false;
        }

        return $this->isAdmin($user) || (int) $report->created_by === (int) $user->id;
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

    private function isAdmin(mixed $user): bool
    {
        return Role::hasManagementAccess($user->role->name ?? null);
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
