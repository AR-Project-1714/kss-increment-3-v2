<?php

namespace App\Http\Controllers\Concerns;

use App\Enums\MaintenanceStatus;
use App\Enums\ReportStatus;
use App\Enums\SafetyStatus;
use App\Models\AdminActivityLog;
use App\Models\DailyReport;
use App\Models\MaintenanceReport;
use App\Jobs\BuildArchiveBundle;
use App\Models\ArchiveBundle;
use App\Models\SafetyReport;
use App\Services\ArchiveBundleService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Throwable;

trait BuildsDivisionArchive
{
    use BuildsExportSpreadsheet;

    /**
     * Parameter filter arsip dari query string — SATU-SATUNYA tempat parsing
     * ini boleh hidup, dipakai halaman arsip dan ekspornya di admin & manajer
     * agar keduanya tidak mungkin menafsirkan filter secara berbeda.
     */
    protected function archiveFiltersFromRequest(Request $request): array
    {
        $perPage = (int) $request->input('per_page', 10);

        return [
            'archiveSearch' => trim((string) $request->input('q', '')),
            'sort' => $request->input('sort', 'newest') === 'oldest' ? 'oldest' : 'newest',
            'perPage' => in_array($perPage, [10, 20, 50], true) ? $perPage : 10,
            'selectedDate' => $request->input('tanggal'),
            'selectedGroup' => strtoupper((string) $request->input('regu', 'all')),
            'selectedShift' => strtolower((string) $request->input('shift', 'all')),
            'selectedDivision' => strtolower((string) $request->input('divisi', 'all')),
            'selectedStatus' => strtolower((string) $request->input('status', 'all')),
        ];
    }

    /**
     * Ekspor Excel seluruh baris arsip yang lolos filter aktif (bukan hanya
     * halaman yang tampil) — refs+hydrate yang sama dengan paginator sehingga
     * isinya identik dengan yang dilihat pengguna di tabel.
     */
    protected function archiveExportResponse(Request $request, string $context)
    {
        $filters = $this->archiveFiltersFromRequest($request);

        $refs = $this->archiveRowRefs($filters);

        if ($refs->isEmpty()) {
            return back()->with('error', 'Tidak ada laporan pada filter aktif untuk diekspor.');
        }

        abort_if(
            $refs->count() > self::EXPORT_ROW_LIMIT,
            422,
            'Data terlalu banyak untuk diekspor sekaligus (maks '.self::EXPORT_ROW_LIMIT.' baris). Persempit filter terlebih dahulu.'
        );

        $rows = $this->hydrateArchiveRows($refs, $context);

        $spreadsheet = $this->buildExportSpreadsheet(
            'Arsip Laporan KSS',
            [
                'Diekspor: '.now()->locale('id')->translatedFormat('d F Y, H:i').' oleh '.($request->user()->name ?? '-'),
                'Filter aktif: '.$this->describeArchiveFilters($filters),
                'Jumlah baris: '.$rows->count(),
            ],
            ['No', 'ID Dokumen', 'Nama Laporan', 'Tanggal', 'Divisi', 'Regu', 'Shift', 'Status', 'Penyetuju', 'Terakhir Diperbarui'],
            $rows->values()->map(fn (array $row, int $index): array => [
                $index + 1,
                $row['id'],
                $row['title'],
                $row['date'],
                $row['division_label'],
                $row['regu'],
                $row['shift_label'],
                $row['status_label'],
                $row['approver'],
                $row['updated_diff'],
            ])
        );

        $fileName = 'Arsip-Laporan_'.now()->format('Y-m-d_Hi').'.xlsx';

        AdminActivityLog::create([
            'user_id' => $request->user()?->id,
            'type' => 'export',
            'description' => 'Mengekspor arsip laporan ('.$rows->count().' baris)',
            'ip_address' => $request->ip(),
        ]);

        return response()->streamDownload(function () use ($spreadsheet): void {
            $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Unduh massal instan: bundel PDF laporan yang dicentang di tabel (atau
     * SELURUH hasil filter aktif bila `all=1`) menjadi satu ZIP dalam satu
     * request. Hanya untuk pilihan kecil — di atas INSTANT_LIMIT permintaan
     * harus lewat bundel latar (lihat archiveBundleStoreResponse).
     */
    protected function archiveBulkDownloadResponse(Request $request)
    {
        $service = app(ArchiveBundleService::class);
        $refs = $this->archiveBulkRefs($request, $service);

        if ($refs->isEmpty()) {
            return $this->archiveBulkDownloadFailed($request, 'Belum ada laporan yang dipilih untuk diunduh.');
        }

        if ($refs->count() > ArchiveBundleService::INSTANT_LIMIT) {
            return $this->archiveBulkDownloadFailed(
                $request,
                'Unduhan langsung maksimal '.ArchiveBundleService::INSTANT_LIMIT.' laporan ('.$refs->count().' terpilih). Gunakan penyiapan di latar untuk jumlah sebesar ini.',
                ['needs_background' => true, 'total' => $refs->count()]
            );
        }

        $zipPath = tempnam(sys_get_temp_dir(), 'kss-arsip-');

        if ($zipPath === false) {
            return $this->archiveBulkDownloadFailed($request, 'Server gagal menyiapkan berkas ZIP sementara. Silakan coba lagi.');
        }

        // Laporan yang belum punya cache PDF dirender ulang di sini, jadi satu
        // permintaan bisa jauh lebih lama daripada unduh satu laporan.
        if (function_exists('set_time_limit')) {
            @set_time_limit(600);
        }

        try {
            $result = $service->writeZip($refs, $zipPath);
        } catch (Throwable $exception) {
            @unlink($zipPath);

            Log::error('Unduh massal arsip gagal.', ['message' => $exception->getMessage()]);

            return $this->archiveBulkDownloadFailed($request, 'Server gagal membuat berkas ZIP. Silakan coba lagi.');
        }

        if ($result['added'] === 0) {
            @unlink($zipPath);

            // Bedakan "laporannya sudah hilang dari arsip" (mis. kunci basi dari
            // halaman yang lama dibuka) dari "laporannya ada, PDF-nya gagal".
            return $this->archiveBulkDownloadFailed($request, $result['matched'] === 0
                ? 'Laporan yang dipilih tidak ditemukan lagi di arsip.'
                : 'Tidak ada PDF yang berhasil disiapkan dari laporan terpilih.');
        }

        $this->logArchiveBundleActivity(
            $request,
            'Mengunduh massal '.$result['added'].' laporan arsip sebagai ZIP',
            $result['skipped']
        );

        return response()
            ->download($zipPath, $service->downloadFileName($result['added']), [
                'Content-Type' => 'application/zip',
            ])
            ->deleteFileAfterSend(true);
    }

    /**
     * Titip permintaan bundel besar ke queue: baris ArchiveBundle dibuat lalu
     * job BuildArchiveBundle merakit ZIP-nya di latar. Balasannya berisi token
     * untuk memantau progres, jadi pengguna boleh menutup halaman.
     */
    protected function archiveBundleStoreResponse(Request $request, string $context)
    {
        if (! class_exists(\ZipArchive::class)) {
            return $this->archiveBulkDownloadFailed($request, 'Ekstensi ZIP tidak tersedia di server, penyiapan bundel belum bisa dijalankan.');
        }

        $service = app(ArchiveBundleService::class);
        $refs = $this->archiveBulkRefs($request, $service);

        if ($refs->isEmpty()) {
            return $this->archiveBulkDownloadFailed($request, 'Belum ada laporan yang dipilih untuk disiapkan.');
        }

        if ($refs->count() > ArchiveBundleService::BUNDLE_LIMIT) {
            return $this->archiveBulkDownloadFailed(
                $request,
                'Satu bundel maksimal '.ArchiveBundleService::BUNDLE_LIMIT.' laporan ('.$refs->count().' terpilih). Persempit filter terlebih dahulu.'
            );
        }

        // Satu bundel aktif per pengguna: dua permintaan besar sekaligus hanya
        // akan berebut CPU render PDF dan memperlambat keduanya.
        $running = ArchiveBundle::query()
            ->where('user_id', $request->user()?->id)
            ->whereIn('status', [ArchiveBundle::STATUS_QUEUED, ArchiveBundle::STATUS_PROCESSING])
            ->latest('id')
            ->first();

        if ($running !== null) {
            return response()->json([
                'message' => 'Masih ada bundel yang sedang disiapkan. Tunggu sampai selesai atau batalkan dulu.',
                'bundle' => $this->archiveBundlePayload($running),
            ], 409);
        }

        $bundle = ArchiveBundle::create([
            'token' => (string) Str::uuid(),
            'user_id' => $request->user()?->id,
            'context' => $context,
            'status' => ArchiveBundle::STATUS_QUEUED,
            'total_reports' => $refs->count(),
            'processed_reports' => 0,
            'skipped_reports' => 0,
            'refs' => $service->normalizeRefs($refs),
            'filter_summary' => $request->boolean('all')
                ? $this->describeArchiveFilters($this->archiveFiltersFromRequest($request))
                : $refs->count().' laporan dipilih manual',
            'expires_at' => now()->addHours(24),
        ]);

        BuildArchiveBundle::dispatch($bundle->id);

        $this->logArchiveBundleActivity(
            $request,
            'Menjadwalkan bundel ZIP arsip berisi '.$bundle->total_reports.' laporan'
        );

        return response()->json([
            'message' => 'Bundel sedang disiapkan di latar.',
            'bundle' => $this->archiveBundlePayload($bundle),
        ], 202);
    }

    /**
     * Progres bundel untuk polling dari halaman arsip.
     */
    protected function archiveBundleStatusResponse(Request $request, string $token)
    {
        $bundle = $this->findArchiveBundle($request, $token);

        return response()->json(['bundle' => $this->archiveBundlePayload($bundle)]);
    }

    /**
     * Unduh berkas bundel yang sudah selesai dirakit.
     */
    protected function archiveBundleDownloadResponse(Request $request, string $token)
    {
        $bundle = $this->findArchiveBundle($request, $token);

        abort_unless($bundle->isReady(), 409, 'Bundel belum selesai disiapkan.');
        abort_if($bundle->isExpired(), 410, 'Bundel sudah kedaluwarsa, silakan siapkan ulang.');

        $path = $bundle->absolutePath();
        abort_unless($path !== null && is_file($path), 404, 'Berkas bundel tidak ditemukan lagi di server.');

        $bundle->update(['downloaded_at' => now()]);

        return response()->download($path, $bundle->file_name ?? 'Arsip-Laporan.zip', [
            'Content-Type' => 'application/zip',
        ]);
    }

    /**
     * Batalkan / buang bundel milik sendiri beserta berkasnya.
     */
    protected function archiveBundleDestroyResponse(Request $request, string $token)
    {
        $this->findArchiveBundle($request, $token)->purge();

        return response()->json(['message' => 'Bundel dibatalkan.']);
    }

    /**
     * Bundel hanya boleh diakses pemiliknya — token saja tidak cukup kalau
     * bocor lewat riwayat peramban bersama.
     */
    private function findArchiveBundle(Request $request, string $token): ArchiveBundle
    {
        $bundle = ArchiveBundle::where('token', $token)->first();
        $userId = $request->user()?->id;

        abort_if($bundle === null, 404);
        // Pemilik null tidak boleh cocok dengan penonton null: bundel tanpa
        // pemilik harus tetap tertutup, bukan jadi milik siapa saja.
        abort_unless($userId !== null && $bundle->user_id === $userId, 403);

        return $bundle;
    }

    /**
     * @return array<string, mixed>
     */
    private function archiveBundlePayload(ArchiveBundle $bundle): array
    {
        $routePrefix = $bundle->context === 'manajer' ? 'manajer.archive.bundles' : 'admin.archive.bundles';

        return [
            'token' => $bundle->token,
            'status' => $bundle->status,
            'total' => (int) $bundle->total_reports,
            'processed' => (int) $bundle->processed_reports,
            'skipped' => (int) $bundle->skipped_reports,
            'percent' => $bundle->progressPercent(),
            'file_name' => $bundle->file_name,
            'file_size' => $bundle->file_size,
            'error' => $bundle->error,
            'filter_summary' => $bundle->filter_summary,
            'queued_seconds' => $bundle->created_at ? (int) $bundle->created_at->diffInSeconds(now()) : 0,
            'status_url' => route($routePrefix.'.show', $bundle->token),
            'download_url' => $bundle->isReady() ? route($routePrefix.'.download', $bundle->token) : null,
            'cancel_url' => route($routePrefix.'.destroy', $bundle->token),
        ];
    }

    /**
     * Refs dari pilihan manual (`keys[]`) atau dari seluruh hasil filter aktif
     * (`all=1`) — dipakai jalur instan maupun jalur latar agar keduanya
     * menafsirkan permintaan yang sama secara identik.
     *
     * @return Collection<int, array{kind: string, id: int}>
     */
    private function archiveBulkRefs(Request $request, ArchiveBundleService $service): Collection
    {
        return $request->boolean('all')
            ? $this->archiveRowRefs($this->archiveFiltersFromRequest($request))
            : $service->refsFromKeys((array) $request->input('keys', []));
    }

    private function logArchiveBundleActivity(Request $request, string $description, int $skipped = 0): void
    {
        AdminActivityLog::create([
            'user_id' => $request->user()?->id,
            'type' => 'export',
            'description' => $description.($skipped > 0 ? ' ('.$skipped.' laporan gagal disiapkan)' : ''),
            'ip_address' => $request->ip(),
        ]);
    }

    /**
     * Kegagalan unduh massal dilaporkan sebagai JSON untuk pemanggil fetch (agar
     * pesannya bisa ditampilkan tanpa meninggalkan halaman) dan sebagai flash
     * message untuk submit form biasa.
     *
     * @param  array<string, mixed>  $extra
     */
    private function archiveBulkDownloadFailed(Request $request, string $message, array $extra = [])
    {
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(array_merge(['message' => $message], $extra), 422);
        }

        return back()->with('error', $message);
    }

    protected function describeArchiveFilters(array $filters): string
    {
        $parts = [];

        if (filled($filters['archiveSearch'])) {
            $parts[] = 'Pencarian "'.$filters['archiveSearch'].'"';
        }
        if (filled($filters['selectedDate'])) {
            $parts[] = 'Tanggal '.Carbon::parse($filters['selectedDate'])->locale('id')->translatedFormat('d F Y');
        }
        if ($filters['selectedDivision'] !== 'all') {
            $parts[] = 'Divisi '.$this->divisionMeta($filters['selectedDivision'])['label'];
        }
        if ($filters['selectedGroup'] !== 'ALL') {
            $parts[] = 'Regu '.$filters['selectedGroup'];
        }
        if ($filters['selectedShift'] !== 'all') {
            $parts[] = 'Shift '.ucfirst($filters['selectedShift']);
        }
        if ($filters['selectedStatus'] !== 'all') {
            $parts[] = 'Status '.match ($filters['selectedStatus']) {
                'submitted' => 'Diserahkan',
                'acknowledged' => 'Diterima',
                'approved' => 'Diarsipkan',
                default => ucfirst($filters['selectedStatus']),
            };
        }

        return $parts === [] ? 'Tidak ada filter (seluruh arsip)' : implode(', ', $parts);
    }
    protected function buildDivisionArchivePaginator(Request $request, array $filters, string $context): LengthAwarePaginator
    {
        $perPage = $filters['perPage'] ?? 10;
        $page = LengthAwarePaginator::resolveCurrentPage();

        // Ambil hanya tuple ringan (kind, id, kunci sort) dari database — filter,
        // pencarian, dan urutan dikerjakan di SQL. Model lengkap hanya dimuat
        // untuk baris pada halaman aktif.
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

    // maintenanceArchiveStatuses() & safetyArchiveStatuses() kini tinggal di
    // ResolvesMaintenanceMeta / ResolvesSafetyMeta, sejajar dengan
    // archiveStatuses() milik operasional, agar service bundel latar memakai
    // daftar status yang sama tanpa menduplikasinya.

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
