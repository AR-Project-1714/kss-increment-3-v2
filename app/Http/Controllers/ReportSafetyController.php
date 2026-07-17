<?php

namespace App\Http\Controllers;

use App\Enums\SafetyStatus;
use App\Http\Controllers\Concerns\AutosavesDraftReports;
use App\Http\Controllers\Concerns\ResolvesSafetyMeta;
use App\Models\MasterSafetyItem;
use App\Models\MasterSafetyLocation;
use App\Models\SafetyReport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Throwable;

class ReportSafetyController extends Controller
{
    use AutosavesDraftReports;
    use ResolvesSafetyMeta;

    /**
     * Empat nilai enum kondisi inspeksi (lihat MD §2.4).
     */
    private const CONDITIONS = ['bagus', 'rusak', 'normal', 'tidak_normal'];

    /**
     * Kegiatan operasi & pemeliharaan umum untuk men-seed Section 8 laporan baru
     * (MD §4.2 langkah 3).
     */
    private const DEFAULT_ACTIVITIES = [
        'GRESIK NIAGA',
        'GOLDEN REJEKI',
        'PENGIRIMAN KE GD TURBA',
        'RENTAL UNIT PP&P',
        'RENTAL TRL PT.KAD',
        'RENTAL FL OP6 & OP7',
    ];

    public function history()
    {
        $user = auth()->user();

        SafetyReport::pruneStaleDrafts();

        $draftReports = SafetyReport::with('creator')
            ->where('created_by', $user->id)
            ->where('status', SafetyStatus::Draft)
            ->latest('updated_at')
            ->get();

        $historyReports = SafetyReport::with(['creator', 'approver'])
            ->where('created_by', $user->id)
            ->whereIn('status', [SafetyStatus::Submitted, SafetyStatus::Approved])
            ->latest('report_date')
            ->latest('updated_at')
            ->paginate(10)
            ->withQueryString();

        $activeTab = request('tab') === 'riwayat' || request()->has('page') ? 'riwayat' : 'draft';

        return view('report-safety.index', compact('draftReports', 'historyReports', 'activeTab'));
    }

    public function create()
    {
        SafetyReport::pruneStaleDrafts();

        return view('report-safety.create', $this->formData());
    }

    public function store(Request $request)
    {
        if ($this->isAutosaveRequest($request)) {
            $request->merge(['status' => SafetyStatus::Draft->value]);
        }

        $status = $request->input('status') === SafetyStatus::Draft->value
            ? SafetyStatus::Draft->value
            : SafetyStatus::Submitted->value;
        $request->merge(['status' => $status]);

        $validated = $request->validate($this->rules($status === SafetyStatus::Draft->value), [], $this->attributes());
        $report = null;

        try {
            DB::transaction(function () use ($request, $validated, $status, &$report): void {
                $report = SafetyReport::create([
                    'report_date'  => $validated['report_date'] ?? null,
                    'time_range'   => $this->workTimeRange($request),
                    'status'       => $status,
                    'created_by'   => $request->user()->id,
                    'submitted_at' => $status === SafetyStatus::Submitted->value ? now() : null,
                ]);

                $this->storeDetails($report, $request);
            });
        } catch (Throwable $exception) {
            Log::error('Gagal menyimpan laporan K3.', [
                'user_id' => $request->user()?->id,
                'message' => $exception->getMessage(),
            ]);

            return back()->withInput()->with('error', 'Laporan belum bisa disimpan. Silakan periksa data lalu coba lagi.');
        }

        if ($this->isAutosaveRequest($request)) {
            return $this->autosaveResponse($report, 'safety.update');
        }

        return redirect()->route('safety.index')->with(
            'success',
            $status === SafetyStatus::Draft->value
                ? 'Draft laporan K3 berhasil disimpan.'
                : 'Laporan K3 berhasil dikirim.'
        );
    }

    public function show(SafetyReport $report)
    {
        abort_unless($this->canAccess($report, auth()->user()), 403);

        return view('report-safety.viewpdf', [
            'report'  => $this->loadSafetyReport($report),
            'isPdf'   => false,
            'backUrl' => route('safety.index'),
            'pdfUrl'  => route('safety.pdf', $report),
        ]);
    }

    public function edit(SafetyReport $report)
    {
        abort_unless($this->canEdit($report, auth()->user()), 403);

        return view('report-safety.edit', array_merge($this->formData($report), [
            'report' => $this->loadSafetyReport($report),
        ]));
    }

    public function update(Request $request, SafetyReport $report)
    {
        abort_unless($this->canEdit($report, $request->user()), 403);

        if ($this->isAutosaveRequest($request)) {
            $request->merge(['status' => SafetyStatus::Draft->value]);
        }

        $status = $report->status === SafetyStatus::Draft && $request->input('status') === SafetyStatus::Draft->value
            ? SafetyStatus::Draft->value
            : SafetyStatus::Submitted->value;
        $request->merge(['status' => $status]);

        $validated = $request->validate($this->rules($status === SafetyStatus::Draft->value), [], $this->attributes());

        try {
            DB::transaction(function () use ($request, $report, $validated, $status): void {
                $report->update([
                    'report_date'  => $validated['report_date'] ?? null,
                    'time_range'   => $this->workTimeRange($request),
                    'status'       => $status,
                    'submitted_at' => $status === SafetyStatus::Submitted->value ? ($report->submitted_at ?? now()) : null,
                ]);

                $this->deleteDetails($report);
                $this->storeDetails($report, $request);
            });
        } catch (Throwable $exception) {
            Log::error('Gagal memperbarui laporan K3.', [
                'report_id' => $report->id,
                'user_id'   => $request->user()?->id,
                'message'   => $exception->getMessage(),
            ]);

            return back()->withInput()->with('error', 'Laporan belum bisa diperbarui. Silakan periksa data lalu coba lagi.');
        }

        if ($this->isAutosaveRequest($request)) {
            return $this->autosaveResponse($report, 'safety.update');
        }

        return redirect()->route('safety.index')->with(
            'success',
            $status === SafetyStatus::Draft->value
                ? 'Draft laporan K3 berhasil diperbarui.'
                : 'Laporan K3 berhasil dikirim.'
        );
    }

    public function destroy(SafetyReport $report)
    {
        abort_unless($this->canDelete($report, auth()->user()), 403);

        try {
            $report->delete();
        } catch (Throwable $exception) {
            Log::error('Gagal menghapus draft laporan K3.', [
                'report_id' => $report->id,
                'message'   => $exception->getMessage(),
            ]);

            return back()->with('error', 'Draft belum bisa dihapus. Silakan coba lagi.');
        }

        return redirect()->route('safety.index')->with('success', 'Draft laporan K3 berhasil dihapus.');
    }

    public function extendDraft(SafetyReport $report)
    {
        abort_unless($this->canDelete($report, auth()->user()), 403);

        // Menyentuh updated_at me-reset hitungan masa simpan draft (DRAFT_TTL_DAYS).
        $report->touch();

        return redirect()
            ->route('safety.index', ['tab' => 'draft'])
            ->with('success', 'Masa simpan draft diperpanjang '.SafetyReport::DRAFT_TTL_DAYS.' hari sejak sekarang.');
    }

    public function exportPdf(SafetyReport $report)
    {
        abort_unless($this->canAccess($report, auth()->user()), 403);

        if (! class_exists(Pdf::class)) {
            return view('report-safety.viewpdf', [
                'report'  => $this->loadSafetyReport($report),
                'isPdf'   => false,
                'backUrl' => route('safety.index'),
                'pdfUrl'  => null,
            ]);
        }

        $pdf = Pdf::loadView('report-safety.pdf', [
            'report' => $this->loadSafetyReport($report),
            'isPdf'  => true,
        ]);
        $pdf->setPaper([0, 0, 612.00, 936.00], 'portrait');
        $pdf->setOption('isRemoteEnabled', true);

        return response($pdf->output(), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$this->safetyFileName($report, 'pdf').'"',
        ]);
    }

    // ============================================================
    // Persistensi detail
    // ============================================================

    private function storeDetails(SafetyReport $report, Request $request): void
    {
        $this->storeInspections($report, $request);
        $this->storeOperationLogs($report, $request);
        $this->storeIncidentLogs($report, $request);
    }

    private function storeInspections(SafetyReport $report, Request $request): void
    {
        $sort = 0;

        foreach ($this->rows($request->input('locations', [])) as $locationGroup) {
            [$locationId, $locationName] = $this->resolveLocation($locationGroup);

            if ($locationName === null) {
                continue;
            }

            foreach ($this->rows($locationGroup['items'] ?? []) as $itemRow) {
                [$itemId, $itemName] = $this->resolveItem($itemRow);

                if ($itemName === null) {
                    continue;
                }

                $report->inspections()->create([
                    'location_id'            => $locationId,
                    'item_id'                => $itemId,
                    'location_name_snapshot' => $locationName,
                    'item_name_snapshot'     => $itemName,
                    'qty'                    => $this->qty($itemRow['qty'] ?? null),
                    'condition'              => $this->condition($itemRow['condition'] ?? null),
                    'recommendation'         => $this->string($itemRow['recommendation'] ?? null),
                    'sort_order'             => $sort++,
                ]);
            }
        }
    }

    private function storeOperationLogs(SafetyReport $report, Request $request): void
    {
        $sort = 0;

        foreach ($this->rows($request->input('operations', [])) as $row) {
            $name = $this->string($row['activity_name'] ?? null);
            $condition = $this->string($row['condition'] ?? null);
            $action = $this->string($row['action'] ?? null);
            $notes = $this->string($row['notes'] ?? null);

            if ($name === null && $condition === null && $action === null && $notes === null) {
                continue;
            }

            // Baris tanpa nama kegiatan dilewati agar tidak menyimpan baris kosong.
            if ($name === null) {
                continue;
            }

            $report->operationLogs()->create([
                'activity_name' => $name,
                'condition'     => $condition,
                'action'        => $action,
                'notes'         => $notes,
                'sort_order'    => $sort++,
            ]);
        }
    }

    private function storeIncidentLogs(SafetyReport $report, Request $request): void
    {
        $sort = 0;

        foreach ($this->rows($request->input('incidents', [])) as $row) {
            $description = $this->string($row['description'] ?? null);
            $condition = $this->string($row['condition'] ?? null);
            $action = $this->string($row['action'] ?? null);
            $notes = $this->string($row['notes'] ?? null);

            if ($description === null && $condition === null && $action === null && $notes === null) {
                continue;
            }

            $report->incidentLogs()->create([
                'description' => $description,
                'condition'   => $condition,
                'action'      => $action,
                'notes'       => $notes,
                'sort_order'  => $sort++,
            ]);
        }
    }

    private function deleteDetails(SafetyReport $report): void
    {
        $report->inspections()->delete();
        $report->operationLogs()->delete();
        $report->incidentLogs()->delete();
    }

    /**
     * @return array{0: ?int, 1: ?string} [location_id, location_name_snapshot]
     */
    private function resolveLocation(array $group): array
    {
        $name = $this->string($group['location_name'] ?? null);
        $id = $group['location_id'] ?? null;

        if (is_numeric($id)) {
            $location = MasterSafetyLocation::whereKey((int) $id)->first();
            if ($location) {
                return [$location->id, $name ?? $location->name];
            }
        }

        return [null, $name];
    }

    /**
     * @return array{0: ?int, 1: ?string} [item_id, item_name_snapshot]
     */
    private function resolveItem(array $row): array
    {
        $name = $this->string($row['item_name'] ?? null);
        $id = $row['item_id'] ?? null;

        if (is_numeric($id)) {
            $item = MasterSafetyItem::whereKey((int) $id)->first();
            if ($item) {
                return [$item->id, $name ?? $item->name];
            }
        }

        return [null, $name];
    }

    // ============================================================
    // Data form (template & laporan tersimpan)
    // ============================================================

    private function formData(?SafetyReport $report = null): array
    {
        $catalogItems = MasterSafetyItem::active()
            ->orderBy('name')
            ->get(['id', 'name', 'is_countable'])
            ->map(fn (MasterSafetyItem $item): array => [
                'id'           => $item->id,
                'name'         => $item->name,
                'is_countable' => (bool) $item->is_countable,
            ])
            ->values()
            ->all();

        return [
            'locationGroups' => $report ? $this->locationGroupsFromReport($report) : $this->locationGroupsFromTemplate(),
            'operationRows'  => $report ? $this->operationRowsFromReport($report) : $this->defaultOperationRows(),
            'incidentRows'   => $report ? $this->incidentRowsFromReport($report) : [],
            'catalogItems'   => $catalogItems,
            'conditions'     => self::CONDITIONS,
            'previousReportPeek' => $this->previousReportPeek($report),
        ];
    }

    /**
     * Laporan K3 non-draft terakhir milik user — untuk panel "Intip Laporan
     * Sebelumnya" di form tanpa harus keluar dari halaman.
     */
    private function previousReportPeek(?SafetyReport $current = null): ?array
    {
        $user = auth()->user();

        if (! $user) {
            return null;
        }

        $previous = SafetyReport::query()
            ->select(['id', 'report_date'])
            ->where('created_by', $user->id)
            ->whereIn('status', [SafetyStatus::Submitted->value, SafetyStatus::Approved->value])
            ->when($current, fn ($query) => $query->whereKeyNot($current->getKey()))
            ->orderByDesc('report_date')
            ->orderByDesc('id')
            ->first();

        if (! $previous) {
            return null;
        }

        return [
            'url' => route('safety.show', $previous),
            'title' => 'Laporan Harian K3 Sebelumnya',
            'meta' => $previous->report_date
                ? $previous->report_date->locale('id')->translatedFormat('d F Y')
                : null,
        ];
    }

    private function locationGroupsFromTemplate(): array
    {
        return MasterSafetyLocation::active()
            ->with('items')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (MasterSafetyLocation $location): array => [
                'location_id'   => $location->id,
                'location_name' => $location->name,
                'items'         => $location->items->map(fn (MasterSafetyItem $item): array => [
                    'item_id'        => $item->id,
                    'item_name'      => $item->name,
                    'is_countable'   => (bool) $item->is_countable,
                    'qty'            => $item->pivot->default_qty,
                    'condition'      => '',
                    'recommendation' => '',
                ])->values()->all(),
            ])
            ->values()
            ->all();
    }

    private function locationGroupsFromReport(SafetyReport $report): array
    {
        $groups = [];

        foreach ($report->inspections as $inspection) {
            $key = ($inspection->location_id ?? 'x').'|'.$inspection->location_name_snapshot;

            if (! isset($groups[$key])) {
                $groups[$key] = [
                    'location_id'   => $inspection->location_id,
                    'location_name' => $inspection->location_name_snapshot,
                    'items'         => [],
                ];
            }

            $groups[$key]['items'][] = [
                'item_id'        => $inspection->item_id,
                'item_name'      => $inspection->item_name_snapshot,
                'is_countable'   => $inspection->qty !== null || (bool) ($inspection->item?->is_countable),
                'qty'            => $inspection->qty,
                'condition'      => $inspection->condition ?? '',
                'recommendation' => $inspection->recommendation ?? '',
            ];
        }

        return array_values($groups);
    }

    private function defaultOperationRows(): array
    {
        return array_map(fn (string $name): array => [
            'activity_name' => $name,
            'condition'     => 'Aman',
            'action'        => '',
            'notes'         => '',
        ], self::DEFAULT_ACTIVITIES);
    }

    private function operationRowsFromReport(SafetyReport $report): array
    {
        if ($report->operationLogs->isEmpty()) {
            return [];
        }

        return $report->operationLogs->map(fn ($log): array => [
            'activity_name' => $log->activity_name,
            'condition'     => $log->condition ?? '',
            'action'        => $log->action ?? '',
            'notes'         => $log->notes ?? '',
        ])->values()->all();
    }

    private function incidentRowsFromReport(SafetyReport $report): array
    {
        return $report->incidentLogs->map(fn ($log): array => [
            'description' => $log->description ?? '',
            'condition'   => $log->condition ?? '',
            'action'      => $log->action ?? '',
            'notes'       => $log->notes ?? '',
        ])->values()->all();
    }

    // ============================================================
    // Otorisasi
    // ============================================================

    private function canAccess(SafetyReport $report, mixed $user): bool
    {
        return $user && (int) $report->created_by === (int) $user->id;
    }

    private function canEdit(SafetyReport $report, mixed $user): bool
    {
        return $this->canAccess($report, $user)
            && in_array($report->status, [SafetyStatus::Draft, SafetyStatus::Submitted], true);
    }

    private function canDelete(SafetyReport $report, mixed $user): bool
    {
        return $this->canAccess($report, $user) && $report->status === SafetyStatus::Draft;
    }

    // ============================================================
    // Validasi & util
    // ============================================================

    private function rules(bool $isDraft): array
    {
        $requiredWhenSubmit = $isDraft ? 'nullable' : 'required';

        return [
            'status'      => ['required', Rule::in([SafetyStatus::Draft->value, SafetyStatus::Submitted->value])],
            'report_date' => [
                $requiredWhenSubmit,
                'date',
                function (string $attribute, mixed $value, callable $fail) use ($isDraft): void {
                    if ($isDraft || blank($value)) {
                        return;
                    }

                    $current = request()->route('report');

                    $duplicate = SafetyReport::query()
                        ->whereDate('report_date', $value)
                        ->where('status', '!=', SafetyStatus::Draft->value)
                        ->when($current instanceof SafetyReport, fn ($query) => $query->whereKeyNot($current->getKey()))
                        ->exists();

                    if ($duplicate) {
                        $fail('Sudah ada laporan K3 terkirim untuk tanggal tersebut. Periksa Riwayat Laporan agar tidak terjadi laporan ganda.');
                    }
                },
            ],
            'work_time_start' => ['nullable', 'string', 'max:10'],
            'work_time_end'   => ['nullable', 'string', 'max:10'],
            'locations'   => ['nullable', 'array'],
            'operations'  => ['nullable', 'array'],
            'incidents'   => ['nullable', 'array'],
        ];
    }

    private function attributes(): array
    {
        return [
            'report_date' => 'tanggal',
        ];
    }

    private function rows(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter($value, fn ($row): bool => is_array($row)));
    }

    private function string(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : mb_substr($value, 0, 255);
    }

    /**
     * Gabungkan input jam masuk & pulang (manual, tanpa otomatisasi) menjadi
     * satu kolom "time_range" sesuai format laporan (mis. "07:00 - 16:00").
     */
    private function workTimeRange(Request $request): ?string
    {
        $start = $this->string($request->input('work_time_start'));
        $end = $this->string($request->input('work_time_end'));

        if ($start !== null && $end !== null) {
            return $start.' - '.$end;
        }

        return $start ?? $end;
    }

    private function qty(mixed $value): ?int
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        if (! is_numeric($value)) {
            return null;
        }

        return max(0, (int) $value);
    }

    private function condition(mixed $value): ?string
    {
        $value = strtolower(trim((string) $value));

        return in_array($value, self::CONDITIONS, true) ? $value : null;
    }
}
