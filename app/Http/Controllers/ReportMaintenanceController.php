<?php

namespace App\Http\Controllers;

use App\Enums\MaintenanceStatus;
use App\Http\Controllers\Concerns\AutosavesDraftReports;
use App\Http\Controllers\Concerns\ResolvesMaintenanceMeta;
use App\Models\MaintenanceReport;
use App\Models\MaintenanceUnitCondition;
use App\Models\MaintenanceWorkItem;
use App\Models\MasterEmployee;
use App\Models\MasterUnit;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Throwable;

class ReportMaintenanceController extends Controller
{
    use AutosavesDraftReports;
    use ResolvesMaintenanceMeta;

    private const MASTER_DATA_CACHE_TTL = 60 * 60 * 24;

    /**
     * Empat baris tetap Pekerjaan Utama mengikuti Group I-IV.
     */
    private const WORK_GROUPS = ['I', 'II', 'III', 'IV'];

    public function index()
    {
        $user = auth()->user();

        MaintenanceReport::pruneStaleDrafts();

        $draftReports = MaintenanceReport::with('creator')
            ->where('created_by', $user->id)
            ->where('status', MaintenanceStatus::Draft)
            ->latest('updated_at')
            ->get();

        $historyReports = MaintenanceReport::with(['creator', 'approver', 'attendances'])
            ->where('created_by', $user->id)
            ->whereIn('status', [MaintenanceStatus::Submitted, MaintenanceStatus::Approved])
            ->latest('report_date')
            ->latest('updated_at')
            ->paginate(10)
            ->withQueryString();

        $activeTab = request('tab') === 'riwayat' || request()->has('page') ? 'riwayat' : 'draft';

        return view('pemeliharaan.index', compact('draftReports', 'historyReports', 'activeTab'));
    }

    public function create()
    {
        MaintenanceReport::pruneStaleDrafts();

        return view('pemeliharaan.create', $this->masterData());
    }

    public function store(Request $request)
    {
        if ($this->isAutosaveRequest($request)) {
            $request->merge(['status' => MaintenanceStatus::Draft->value]);
        }

        $status = $request->input('status') === MaintenanceStatus::Draft->value
            ? MaintenanceStatus::Draft->value
            : MaintenanceStatus::Submitted->value;
        $request->merge(['status' => $status]);

        $validated = $request->validate($this->rules($status === MaintenanceStatus::Draft->value), [], $this->attributes());
        $report = null;

        try {
            DB::transaction(function () use ($request, $validated, $status, &$report): void {
                $reportDate = $validated['report_date'] ?? null;

                $report = MaintenanceReport::create([
                    'report_date'            => $reportDate,
                    'day_name'               => $this->dayName($reportDate),
                    'status'                 => $status,
                    'created_by'             => $request->user()->id,
                    'submitted_at'           => $status === MaintenanceStatus::Submitted->value ? now() : null,
                    'karu_pemeliharaan_name' => $this->string($request->input('karu_pemeliharaan_name')),
                    'karu_peralatan_name'    => $this->string($request->input('karu_peralatan_name')),
                    'work_time_start'        => $this->string($request->input('work_time_start')),
                    'work_time_end'          => $this->string($request->input('work_time_end')),
                ]);

                $this->storeDetails($report, $request);
            });
        } catch (Throwable $exception) {
            Log::error('Gagal menyimpan laporan pemeliharaan.', [
                'user_id' => $request->user()?->id,
                'message' => $exception->getMessage(),
            ]);

            return back()->withInput()->with('error', 'Laporan belum bisa disimpan. Silakan periksa data lalu coba lagi.');
        }

        if ($this->isAutosaveRequest($request)) {
            return $this->autosaveResponse($report, 'pemeliharaan.update');
        }

        return redirect()->route('pemeliharaan.index')->with(
            'success',
            $status === MaintenanceStatus::Draft->value
                ? 'Draft laporan pemeliharaan berhasil disimpan.'
                : 'Laporan pemeliharaan berhasil dikirim.'
        );
    }

    public function show(MaintenanceReport $report)
    {
        abort_unless($this->canAccess($report, auth()->user()), 403);

        return view('pemeliharaan.viewpdf', [
            'report'  => $this->loadMaintenanceReport($report),
            'isPdf'   => false,
            'backUrl' => route('pemeliharaan.index'),
            'pdfUrl'  => route('pemeliharaan.pdf', $report),
        ]);
    }

    public function edit(MaintenanceReport $report)
    {
        abort_unless($this->canEdit($report, auth()->user()), 403);

        return view('pemeliharaan.edit', array_merge($this->masterData($report), [
            'report' => $this->loadMaintenanceReport($report),
        ]));
    }

    public function update(Request $request, MaintenanceReport $report)
    {
        abort_unless($this->canEdit($report, $request->user()), 403);

        if ($this->isAutosaveRequest($request)) {
            $request->merge(['status' => MaintenanceStatus::Draft->value]);
        }

        $status = $report->status === MaintenanceStatus::Draft && $request->input('status') === MaintenanceStatus::Draft->value
            ? MaintenanceStatus::Draft->value
            : MaintenanceStatus::Submitted->value;
        $request->merge(['status' => $status]);

        $validated = $request->validate($this->rules($status === MaintenanceStatus::Draft->value), [], $this->attributes());

        try {
            DB::transaction(function () use ($request, $report, $validated, $status): void {
                $reportDate = $validated['report_date'] ?? null;

                $report->update([
                    'report_date'            => $reportDate,
                    'day_name'               => $this->dayName($reportDate),
                    'status'                 => $status,
                    'submitted_at'           => $status === MaintenanceStatus::Submitted->value ? ($report->submitted_at ?? now()) : null,
                    'karu_pemeliharaan_name' => $this->string($request->input('karu_pemeliharaan_name')),
                    'karu_peralatan_name'    => $this->string($request->input('karu_peralatan_name')),
                    'work_time_start'        => $this->string($request->input('work_time_start')),
                    'work_time_end'          => $this->string($request->input('work_time_end')),
                ]);

                $this->deleteDetails($report);
                $this->storeDetails($report, $request);
            });
        } catch (Throwable $exception) {
            Log::error('Gagal memperbarui laporan pemeliharaan.', [
                'report_id' => $report->id,
                'user_id'   => $request->user()?->id,
                'message'   => $exception->getMessage(),
            ]);

            return back()->withInput()->with('error', 'Laporan belum bisa diperbarui. Silakan periksa data lalu coba lagi.');
        }

        if ($this->isAutosaveRequest($request)) {
            return $this->autosaveResponse($report, 'pemeliharaan.update');
        }

        return redirect()->route('pemeliharaan.index')->with(
            'success',
            $status === MaintenanceStatus::Draft->value
                ? 'Draft laporan pemeliharaan berhasil diperbarui.'
                : 'Laporan pemeliharaan berhasil dikirim.'
        );
    }

    public function destroy(MaintenanceReport $report)
    {
        abort_unless($this->canDelete($report, auth()->user()), 403);

        try {
            $report->delete();
        } catch (Throwable $exception) {
            Log::error('Gagal menghapus draft laporan pemeliharaan.', [
                'report_id' => $report->id,
                'message'   => $exception->getMessage(),
            ]);

            return back()->with('error', 'Draft belum bisa dihapus. Silakan coba lagi.');
        }

        return redirect()->route('pemeliharaan.index')->with('success', 'Draft laporan pemeliharaan berhasil dihapus.');
    }

    public function extendDraft(MaintenanceReport $report)
    {
        abort_unless($this->canDelete($report, auth()->user()), 403);

        // Menyentuh updated_at me-reset hitungan masa simpan draft (DRAFT_TTL_DAYS).
        $report->touch();

        return redirect()
            ->route('pemeliharaan.index', ['tab' => 'draft'])
            ->with('success', 'Masa simpan draft diperpanjang '.MaintenanceReport::DRAFT_TTL_DAYS.' hari sejak sekarang.');
    }

    public function exportPdf(MaintenanceReport $report)
    {
        abort_unless($this->canAccess($report, auth()->user()), 403);

        if (! class_exists(Pdf::class)) {
            return view('pemeliharaan.viewpdf', [
                'report'  => $this->loadMaintenanceReport($report),
                'isPdf'   => false,
                'backUrl' => route('pemeliharaan.index'),
                'pdfUrl'  => null,
            ]);
        }

        $pdf = Pdf::loadView('pemeliharaan.pdf', [
            'report' => $this->loadMaintenanceReport($report),
            'isPdf'  => true,
        ]);
        $pdf->setPaper([0, 0, 612.00, 936.00], 'portrait');

        return response($pdf->output(), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$this->maintenanceFileName($report, 'pdf').'"',
        ]);
    }

    // ============================================================
    // Persistensi detail
    // ============================================================

    private function storeDetails(MaintenanceReport $report, Request $request): void
    {
        $this->storeWorkItems($report, $request, MaintenanceWorkItem::TYPE_UTAMA, 'main_items');
        $this->storeWorkItems($report, $request, MaintenanceWorkItem::TYPE_PRIORITAS, 'priority_items');
        $this->storeUnitConditions($report, $request);
        $this->storeAttendances($report, $request);
    }

    private function storeWorkItems(MaintenanceReport $report, Request $request, string $workType, string $field): void
    {
        $sort = 0;

        foreach ($this->rows($request->input($field, [])) as $row) {
            $description = $this->text($row['description'] ?? null);
            $assignee = $this->string($row['assignee'] ?? null);
            $notes = $this->string($row['notes'] ?? null);
            $workGroup = $workType === MaintenanceWorkItem::TYPE_UTAMA
                ? $this->string($row['work_group'] ?? null)
                : null;

            [$unitId, $unitLabel] = $this->resolveUnit($row);

            // Lewati baris yang benar-benar kosong.
            if ($description === null && $assignee === null && $notes === null && $unitId === null && $unitLabel === null) {
                continue;
            }

            $report->workItems()->create([
                'work_type'           => $workType,
                'work_group'          => $workGroup,
                'master_unit_id'      => $unitId,
                'unit_label'          => $unitLabel,
                'description'         => $description,
                'assignee'            => $assignee,
                'is_completed'        => $this->boolean($row['is_completed'] ?? null),
                'notes'               => $notes,
                'sort_order'          => $sort++,
            ]);
        }
    }

    private function storeUnitConditions(MaintenanceReport $report, Request $request): void
    {
        $seen = [];

        foreach ((array) $request->input('conditions', []) as $unitId => $row) {
            $unitId = (int) $unitId;
            if ($unitId <= 0 || isset($seen[$unitId]) || ! is_array($row)) {
                continue;
            }

            // Pastikan unit ada di master tunggal (preload mengirim seluruh unit pemeliharaan aktif).
            $unit = MasterUnit::whereKey($unitId)->whereNotNull('macro_category')->first();
            if (! $unit) {
                continue;
            }

            $condition = strtolower((string) ($row['condition'] ?? 'ready')) === 'rusak' ? 'rusak' : 'ready';

            $report->unitConditions()->create([
                'master_unit_id'      => $unitId,
                'unit_label'          => $unit->maintenance_name,
                'condition'           => $condition,
                'notes'               => $this->string($row['notes'] ?? null),
            ]);

            $seen[$unitId] = true;
        }
    }

    private function storeAttendances(MaintenanceReport $report, Request $request): void
    {
        $sort = 0;

        foreach ($this->rows($request->input('attendances', [])) as $row) {
            $name = $this->string($row['employee_name'] ?? null);
            if ($name === null) {
                continue;
            }

            $employeeId = isset($row['master_employee_id']) && is_numeric($row['master_employee_id'])
                ? (int) $row['master_employee_id']
                : null;

            if ($employeeId !== null && ! MasterEmployee::forMaintenance()->whereKey($employeeId)->exists()) {
                $employeeId = null;
            }

            $report->attendances()->create([
                'master_employee_id'      => $employeeId,
                'employee_name'           => $name,
                'position'                => $this->string($row['position'] ?? null),
                'time_in'                 => $this->time($row['time_in'] ?? null),
                'time_out'                => $this->time($row['time_out'] ?? null),
                'notes'                   => $this->string($row['notes'] ?? null),
                'sort_order'              => $sort++,
            ]);
        }
    }

    private function deleteDetails(MaintenanceReport $report): void
    {
        $report->workItems()->delete();
        $report->unitConditions()->delete();
        $report->attendances()->delete();
    }

    /**
     * @return array{0: ?int, 1: ?string} [master_unit_id, unit_label]
     */
    private function resolveUnit(array $row): array
    {
        $unitId = $row['unit_id'] ?? null;
        $unitLabel = $this->string($row['unit_label'] ?? null);

        if (is_numeric($unitId)) {
            $unit = MasterUnit::query()
                ->whereKey((int) $unitId)
                ->whereNotNull('macro_category')
                ->first();

            if ($unit) {
                return [$unit->id, $unit->maintenance_name];
            }
        }

        return [null, $unitLabel];
    }

    // ============================================================
    // Master data & otorisasi
    // ============================================================

    private function masterData(?MaintenanceReport $report = null): array
    {
        $units = Cache::remember(
            MasterUnit::MAINTENANCE_DATA_CACHE_KEY,
            self::MASTER_DATA_CACHE_TTL,
            fn () => MasterUnit::where('status', 'active')
                ->whereIn('macro_category', [MasterUnit::MACRO_TRUCK, MasterUnit::MACRO_HEAVY])
                ->orderBy('macro_category')
                ->orderBy('unit_code')
                ->orderBy('unit_number')
                ->get()
                ->map(fn (MasterUnit $unit): array => [
                    'id'             => $unit->id,
                    'label'          => $unit->maintenance_name,
                    'code'           => $unit->maintenance_code,
                    'macro_category' => $unit->macro_category,
                ])
                ->toArray()
        );

        $employees = Cache::remember(
            MasterEmployee::MAINTENANCE_DATA_CACHE_KEY,
            self::MASTER_DATA_CACHE_TTL,
            fn () => MasterEmployee::forMaintenance()
                ->where('status', 'active')
                ->orderBy('id')
                ->get(['id', 'name', 'position', 'work_time'])
                ->toArray()
        );

        return [
            'units'      => $units,
            'unitsTruck' => array_values(array_filter($units, fn ($u) => $u['macro_category'] === 'truck')),
            'unitsHeavy' => array_values(array_filter($units, fn ($u) => $u['macro_category'] === 'heavy')),
            'employees'  => $employees,
            'workGroups' => self::WORK_GROUPS,
            'latestUnitConditions' => $this->latestUnitConditions($report),
            // Carry-over hanya untuk laporan baru; saat edit, baris prioritas
            // memakai data laporan itu sendiri.
            'carryOverPriority' => $report ? collect() : $this->unfinishedPriorityItems(),
            'previousReportPeek' => $this->previousReportPeek($report),
        ];
    }

    /**
     * Laporan pemeliharaan non-draft terakhir milik user — untuk panel
     * "Intip Laporan Sebelumnya" di form tanpa harus keluar dari halaman.
     */
    private function previousReportPeek(?MaintenanceReport $current = null): ?array
    {
        $user = auth()->user();

        if (! $user) {
            return null;
        }

        $previous = MaintenanceReport::query()
            ->select(['id', 'report_date', 'day_name'])
            ->where('created_by', $user->id)
            ->whereIn('status', [MaintenanceStatus::Submitted->value, MaintenanceStatus::Approved->value])
            ->when($current, fn ($query) => $query->whereKeyNot($current->getKey()))
            ->orderByDesc('report_date')
            ->orderByDesc('id')
            ->first();

        if (! $previous) {
            return null;
        }

        $meta = collect([
            $previous->day_name,
            $previous->report_date
                ? $previous->report_date->locale('id')->translatedFormat('d F Y')
                : null,
        ])->filter()->implode(', ');

        return [
            'url' => route('pemeliharaan.show', $previous),
            'title' => 'Laporan Harian Pemeliharaan Sebelumnya',
            'meta' => $meta,
        ];
    }

    /**
     * Pekerjaan prioritas yang belum selesai dari laporan terkirim/disetujui
     * terakhir — dimuat otomatis sebagai baris awal laporan baru supaya
     * pekerjaan lanjutan tidak hilang antar periode kerja.
     */
    private function unfinishedPriorityItems()
    {
        $latest = MaintenanceReport::query()
            ->whereIn('status', [
                MaintenanceStatus::Submitted->value,
                MaintenanceStatus::Approved->value,
            ])
            ->orderByDesc('report_date')
            ->orderByDesc('id')
            ->first();

        if (! $latest) {
            return collect();
        }

        $sourceDate = $latest->report_date
            ? $latest->report_date->locale('id')->translatedFormat('d F Y')
            : null;

        return $latest->priorityWorkItems()
            ->where('is_completed', false)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (MaintenanceWorkItem $item): array => [
                'unit_id'     => $item->master_unit_id,
                'unit_label'  => $item->unit_label,
                'description' => $item->description,
                'assignee'    => $item->assignee,
                'notes'       => $item->notes,
                'source_date' => $sourceDate,
            ])
            ->values();
    }

    private function latestUnitConditions(?MaintenanceReport $report = null)
    {
        $conditions = collect();

        MaintenanceUnitCondition::query()
            ->select('maintenance_unit_conditions.*')
            ->join('maintenance_reports', 'maintenance_reports.id', '=', 'maintenance_unit_conditions.maintenance_report_id')
            ->whereNotNull('maintenance_unit_conditions.master_unit_id')
            ->whereIn('maintenance_reports.status', [
                MaintenanceStatus::Submitted->value,
                MaintenanceStatus::Approved->value,
            ])
            ->when($report, fn ($query) => $query->where('maintenance_reports.id', '!=', $report->id))
            ->orderByDesc('maintenance_reports.report_date')
            ->orderByDesc('maintenance_reports.id')
            ->orderByDesc('maintenance_unit_conditions.id')
            ->get()
            ->each(function (MaintenanceUnitCondition $condition) use ($conditions): void {
                $unitId = (int) $condition->master_unit_id;
                if ($unitId > 0 && ! $conditions->has((string) $unitId)) {
                    $conditions->put((string) $unitId, $condition);
                }
            });

        return $conditions;
    }

    private function canAccess(MaintenanceReport $report, mixed $user): bool
    {
        return $user && (int) $report->created_by === (int) $user->id;
    }

    private function canEdit(MaintenanceReport $report, mixed $user): bool
    {
        return $this->canAccess($report, $user)
            && in_array($report->status, [MaintenanceStatus::Draft, MaintenanceStatus::Submitted], true);
    }

    private function canDelete(MaintenanceReport $report, mixed $user): bool
    {
        return $this->canAccess($report, $user) && $report->status === MaintenanceStatus::Draft;
    }

    // ============================================================
    // Validasi & util
    // ============================================================

    private function rules(bool $isDraft): array
    {
        $requiredWhenSubmit = $isDraft ? 'nullable' : 'required';

        return [
            'status'                 => ['required', Rule::in([MaintenanceStatus::Draft->value, MaintenanceStatus::Submitted->value])],
            'report_date'            => [
                $requiredWhenSubmit,
                'date',
                function (string $attribute, mixed $value, callable $fail) use ($isDraft): void {
                    if ($isDraft || blank($value)) {
                        return;
                    }

                    $startTime = trim((string) request()->input('work_time_start'));
                    if ($startTime === '') {
                        // Tanpa jam mulai tidak ada pembeda untuk dibandingkan; rule
                        // work_time_start di bawah yang mewajibkan pengisiannya saat submit.
                        return;
                    }

                    $current = request()->route('report');

                    $duplicate = MaintenanceReport::query()
                        ->whereDate('report_date', $value)
                        ->where('status', '!=', MaintenanceStatus::Draft->value)
                        ->where('work_time_start', $startTime)
                        ->when($current instanceof MaintenanceReport, fn ($query) => $query->whereKeyNot($current->getKey()))
                        ->exists();

                    if ($duplicate) {
                        $fail('Sudah ada laporan pemeliharaan terkirim untuk tanggal dan jam mulai kerja yang sama. Periksa Riwayat Laporan agar tidak terjadi laporan ganda.');
                    }
                },
            ],
            'work_time_start'        => [$requiredWhenSubmit, 'string', 'max:10'],
            'work_time_end'          => ['nullable', 'string', 'max:10'],
            'karu_pemeliharaan_name' => ['nullable', 'string', 'max:255'],
            'karu_peralatan_name'    => ['nullable', 'string', 'max:255'],
            'main_items'             => ['nullable', 'array'],
            'main_items.*.unit_id'    => ['nullable', 'integer', Rule::exists('master_units', 'id')],
            'priority_items'         => ['nullable', 'array'],
            'priority_items.*.unit_id' => ['nullable', 'integer', Rule::exists('master_units', 'id')],
            'conditions'             => ['nullable', 'array'],
            'attendances'            => ['nullable', 'array'],
        ];
    }

    private function attributes(): array
    {
        return [
            'report_date'     => 'hari/tanggal',
            'work_time_start' => 'jam mulai kerja',
        ];
    }

    private function dayName(?string $date): ?string
    {
        if (! $date) {
            return null;
        }

        try {
            return Carbon::parse($date)->locale('id')->translatedFormat('l');
        } catch (Throwable) {
            return null;
        }
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

    private function text(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function boolean(mixed $value): bool
    {
        return in_array((string) $value, ['1', 'true', 'on', 'selesai', 'Selesai'], true);
    }

    private function time(mixed $value): ?string
    {
        $value = $this->string($value);

        if ($value === null || preg_match('/^\d{1,2}:\d{2}$/', $value) !== 1) {
            return null;
        }

        return $value;
    }
}
