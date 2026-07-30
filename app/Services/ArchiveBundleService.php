<?php

namespace App\Services;

use App\Http\Controllers\Concerns\ResolvesMaintenanceMeta;
use App\Http\Controllers\Concerns\ResolvesReportMeta;
use App\Http\Controllers\Concerns\ResolvesSafetyMeta;
use App\Models\DailyReport;
use App\Models\MaintenanceReport;
use App\Models\SafetyReport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;
use ZipArchive;

/**
 * Perakitan berkas ZIP arsip laporan.
 *
 * Satu-satunya tempat logika bundel hidup, dipakai dua jalur:
 *   - unduh instan (BuildsDivisionArchive::archiveBulkDownloadResponse) untuk
 *     pilihan kecil yang selesai dalam satu request;
 *   - bundel latar (App\Jobs\BuildArchiveBundle) untuk permintaan besar.
 * Dengan begitu isi ZIP dan nama berkasnya tidak mungkin berbeda antar jalur.
 */
class ArchiveBundleService
{
    use ResolvesMaintenanceMeta;
    use ResolvesReportMeta;
    use ResolvesSafetyMeta;

    /**
     * Batas laporan untuk unduhan instan (satu request HTTP). Di atas ini,
     * permintaan harus lewat bundel latar supaya request tidak kehabisan waktu.
     */
    public const INSTANT_LIMIT = 50;

    /**
     * Batas keras satu bundel latar. Bukan soal waktu request (job bebas
     * berjalan lama), tapi menjaga ukuran ZIP dan pemakaian disk tetap wajar.
     */
    public const BUNDLE_LIMIT = 1000;

    /**
     * Model laporan dimuat sepotong-sepotong (bukan seluruh bundel sekaligus)
     * agar puncak pemakaian memori tetap rendah walau bundelnya ratusan berkas
     * — worker queue punya batas memori dan akan mati kalau dilampaui.
     */
    private const HYDRATE_CHUNK = 20;

    /**
     * Prefiks kunci baris tabel arsip -> jenis laporan.
     */
    private const KINDS = [
        'ops' => 'operasional',
        'pml' => 'pemeliharaan',
        'safety' => 'safety',
    ];

    /**
     * Kunci baris tabel ("ops-12", "pml-3", "safety-7") menjadi ref kind+id.
     * Kunci yang tidak dikenal diabaikan supaya input dari klien tidak bisa
     * mengarahkan bundel ke tabel lain.
     *
     * @return Collection<int, array{kind: string, id: int}>
     */
    public function refsFromKeys(array $keys): Collection
    {
        return collect($keys)
            ->map(function ($key): ?array {
                if (! is_string($key) || ! preg_match('/^(ops|pml|safety)-(\d+)$/', $key, $matches)) {
                    return null;
                }

                return ['kind' => self::KINDS[$matches[1]], 'id' => (int) $matches[2]];
            })
            ->filter()
            ->unique(fn (array $ref): string => $ref['kind'].'-'.$ref['id'])
            ->values();
    }

    /**
     * Buang atribut selain kind+id agar refs yang dibekukan ke database ringkas
     * dan tidak menyimpan kunci sortir yang tak lagi relevan.
     *
     * @return array<int, array{kind: string, id: int}>
     */
    public function normalizeRefs(Collection $refs): array
    {
        return $refs
            ->map(fn (array $ref): array => ['kind' => (string) $ref['kind'], 'id' => (int) $ref['id']])
            ->values()
            ->all();
    }

    /**
     * Tulis ZIP berisi PDF seluruh ref ke $absolutePath.
     *
     * $onProgress(int $processed, int $total) dipanggil setiap satu laporan
     * selesai — dipakai job latar untuk memperbarui bilah kemajuan.
     *
     * `matched` = ref yang masih menemukan laporannya di arsip. Dibedakan dari
     * `added` supaya pemanggil bisa membedakan "laporannya sudah tidak ada"
     * dari "laporannya ada tapi PDF-nya gagal dibuat".
     *
     * @param  Collection<int, array{kind: string, id: int}>  $refs
     * @return array{added: int, skipped: int, matched: int}
     */
    public function writeZip(Collection $refs, string $absolutePath, ?callable $onProgress = null): array
    {
        if (! class_exists(ZipArchive::class)) {
            throw new \RuntimeException('Ekstensi ZIP tidak tersedia di server.');
        }

        if ($refs->isEmpty()) {
            return ['added' => 0, 'skipped' => 0, 'matched' => 0];
        }

        $zip = new ZipArchive;

        if ($zip->open($absolutePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Gagal membuat berkas ZIP di '.$absolutePath.'.');
        }

        $added = 0;
        $skipped = 0;
        $matched = 0;
        $total = $refs->count();
        $processed = 0;

        foreach ($refs->chunk(self::HYDRATE_CHUNK) as $chunk) {
            foreach ($this->entriesFor($chunk->values()) as $entry) {
                $matched++;

                if (is_file($entry['cache'])) {
                    $zip->addFile($entry['cache'], $entry['name']);
                    $added++;
                } elseif (! class_exists(Pdf::class)) {
                    $skipped++;
                } else {
                    try {
                        $pdf = Pdf::loadView($entry['view'], [
                            'report' => $entry['report'],
                            'isPdf' => true,
                        ]);
                        $pdf->setPaper([0, 0, 612.00, 936.00], 'portrait');
                        $pdf->setOption('isRemoteEnabled', true);

                        $zip->addFromString($entry['name'], $pdf->output());
                        $added++;
                    } catch (Throwable $exception) {
                        Log::warning('Gagal menyiapkan PDF untuk bundel arsip.', [
                            'file' => $entry['name'],
                            'message' => $exception->getMessage(),
                        ]);
                        $skipped++;
                    } finally {
                        // Instance dompdf menyimpan seluruh dokumen di memori;
                        // lepaskan sebelum laporan berikutnya dirender.
                        unset($pdf);
                    }
                }

                $processed++;

                if ($onProgress !== null) {
                    $onProgress($processed, $total);
                }
            }

            // Ref sudah selesai: buang model & relasinya sebelum potongan berikut.
            gc_collect_cycles();
        }

        $zip->close();

        return ['added' => $added, 'skipped' => $skipped, 'matched' => $matched];
    }

    /**
     * Nama file, path cache PDF, dan view render untuk setiap ref. Status ikut
     * difilter di query supaya laporan di luar arsip tidak bisa ikut terbundel.
     *
     * @param  Collection<int, array{kind: string, id: int}>  $refs
     * @return Collection<int, array{name: string, cache: string, view: string, report: mixed}>
     */
    public function entriesFor(Collection $refs): Collection
    {
        $idsByKind = $refs->groupBy('kind')->map(fn (Collection $group) => $group->pluck('id')->all());

        $models = [
            'operasional' => filled($idsByKind['operasional'] ?? null)
                ? DailyReport::with($this->reportRelations())
                    ->whereIn('id', $idsByKind['operasional'])
                    ->whereIn('status', $this->archiveStatuses())
                    ->get()->keyBy('id')
                : collect(),
            'pemeliharaan' => filled($idsByKind['pemeliharaan'] ?? null)
                ? MaintenanceReport::with($this->maintenanceReportRelations())
                    ->whereIn('id', $idsByKind['pemeliharaan'])
                    ->whereIn('status', $this->maintenanceArchiveStatuses())
                    ->get()->keyBy('id')
                : collect(),
            'safety' => filled($idsByKind['safety'] ?? null)
                ? SafetyReport::with($this->safetyReportRelations())
                    ->whereIn('id', $idsByKind['safety'])
                    ->whereIn('status', $this->safetyArchiveStatuses())
                    ->get()->keyBy('id')
                : collect(),
        ];

        return $refs
            ->map(function (array $ref) use ($models): ?array {
                $report = $models[$ref['kind']][$ref['id']] ?? null;

                if ($report === null) {
                    return null;
                }

                return match ($ref['kind']) {
                    'pemeliharaan' => [
                        'name' => $this->maintenanceFileName($report, 'pdf'),
                        'cache' => storage_path('app/public/maintenance-reports/maintenance-report-'.$report->id.'.pdf'),
                        'view' => 'pemeliharaan.pdf',
                        'report' => $report,
                    ],
                    'safety' => [
                        'name' => $this->safetyFileName($report, 'pdf'),
                        'cache' => storage_path('app/public/safety-reports/safety-report-'.$report->id.'.pdf'),
                        'view' => 'report-safety.pdf',
                        'report' => $report,
                    ],
                    default => [
                        'name' => $this->reportFileName($report, 'pdf'),
                        'cache' => storage_path('app/public/reports/report-'.$report->id.'.pdf'),
                        'view' => 'report-ops.pdf',
                        'report' => $report,
                    ],
                };
            })
            ->filter()
            ->values();
    }

    /**
     * Nama berkas unduhan yang dilihat pengguna.
     */
    public function downloadFileName(int $count): string
    {
        return 'Arsip-Laporan_'.$count.'-berkas_'.now()->format('Y-m-d_Hi').'.zip';
    }
}
