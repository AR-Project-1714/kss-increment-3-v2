<?php

namespace App\Services;

use App\Enums\ReportStatus;
use App\Models\DailyReport;
use App\Models\ShipOperation;
use App\Models\ShipOperationDecision;
use App\Support\ShipNameNormalizer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Serah terima operasi kapal antar regu.
 *
 * Operasi yang masih berjalan dibawa ke form regu penerima laporan terakhirnya
 * (lihat ReportOpsController::carryForwardOperations). Laporan regu penerima
 * itulah yang memutuskan nasibnya: dilanjutkan, ditandai selesai, atau tidak
 * dicantumkan sama sekali.
 *
 * Pilihan ketiga dulu tidak pernah tercatat. Operasi yang barisnya dihapus
 * petugas tetap berstatus berjalan dan laporan terakhirnya tetap laporan yang
 * sama, sehingga ia disisipkan lagi ke SETIAP form regu itu berikutnya — kapal
 * yang sudah lama berangkat terus muncul sebagai "Dibawa dari shift
 * sebelumnya". Kasus nyatanya: KM. Dolphin Nusantara 2 (September 2026), yang
 * operasinya terbelah dua karena namanya dikoreksi di tengah serah terima.
 * Belahan yang ditandai selesai beres, belahan lainnya terus menghantui form
 * regu penerimanya.
 *
 * Kelas ini menutup dua celah itu:
 *
 *   1. releaseAbandoned()   — operasi yang tidak dicantumkan regu penerima
 *                             diarsipkan, dengan keputusan tercatat pada
 *                             laporan yang melepasnya.
 *   2. correctedHandover()  — baris serah terima yang namanya dikoreksi tetap
 *                             menyambung ke operasi asalnya, bukan membentuk
 *                             operasi kembar.
 */
final class ShipHandoverService
{
    private const SENT_STATUSES = [
        ReportStatus::Submitted->value,
        ReportStatus::Acknowledged->value,
        ReportStatus::Approved->value,
    ];

    /** @var array<int, string> */
    private const ACTIVITY_TABLES = [
        'loading_activities',
        'bulk_loading_activities',
        'material_activities',
        'container_activities',
    ];

    /**
     * Operasi berjalan yang diserahkan kepada regu penulis $report sebelum
     * laporan ini — persis yang disisipkan ke form regu tersebut.
     *
     * @return Collection<int, ShipOperation>
     */
    public function pendingFor(DailyReport $report, ?string $type = null): Collection
    {
        $group = strtoupper(trim((string) $report->group_name));

        if ($group === '') {
            return collect();
        }

        return ShipOperation::query()
            ->with('lastReport')
            ->where('status', ShipOperation::STATUS_ACTIVE)
            ->when($type !== null, fn ($query) => $query->where('type', $type))
            ->whereHas('lastReport', function ($query) use ($group, $report): void {
                $query->where('received_by_group', $group)
                    ->whereIn('status', self::SENT_STATUSES)
                    ->whereKeyNot($report->id);
            })
            ->get()
            ->filter(fn (ShipOperation $operation): bool => self::isBefore($operation->lastReport, $report))
            ->values();
    }

    /**
     * Arsipkan operasi berjalan yang tidak dicantumkan oleh laporan regu
     * penerimanya. Aman dijalankan berulang kali.
     *
     * Diarsipkan, bukan ditandai selesai: tidak ada petugas yang memutuskan
     * kapalnya selesai. Operasi terarsip tetap ikut pencocokan nama, jadi bila
     * barisnya ternyata terhapus tidak sengaja, mengetik nama kapalnya lagi
     * menyambungkannya kembali lengkap dengan akumulasinya.
     */
    public function releaseAbandoned(): int
    {
        $released = 0;

        ShipOperation::query()
            ->with('lastReport')
            ->whereIn('type', ShipOperation::types())
            ->where('status', ShipOperation::STATUS_ACTIVE)
            ->whereHas('lastReport', function ($query): void {
                $query->whereNotNull('received_by_group')
                    ->where('received_by_group', '!=', '')
                    ->whereIn('status', self::SENT_STATUSES);
            })
            ->get()
            ->each(function (ShipOperation $operation) use (&$released): void {
                $successor = $this->releasingReport($operation);

                if (! $successor) {
                    return;
                }

                DB::transaction(function () use ($operation, $successor): void {
                    $operation->update([
                        'status' => ShipOperation::STATUS_INACTIVE,
                        'completed_at' => null,
                        'completed_by' => null,
                    ]);

                    ShipOperationDecision::updateOrCreate(
                        [
                            'ship_operation_id' => $operation->id,
                            'daily_report_id' => $successor->id,
                        ],
                        [
                            'status' => ShipOperation::STATUS_INACTIVE,
                            'decided_by' => $successor->user_id ?? $successor->created_by,
                            'decided_at' => now(),
                        ],
                    );
                });

                Log::info('Operasi kapal yang tidak dilanjutkan regu penerima diarsipkan.', [
                    'ship_operation_id' => $operation->id,
                    'type' => $operation->type,
                    'kapal' => $operation->ship_name,
                    'laporan_serah_terima' => $operation->last_report_id,
                    'laporan_penerima' => $successor->id,
                    'regu' => $successor->group_name,
                ]);

                $released++;
            });

        return $released;
    }

    /**
     * Operasi serah terima yang namanya dikoreksi petugas pada laporan ini.
     *
     * Form membuang ID operasi begitu nama kapal diketik ulang, lalu
     * pencocokan nama biasa menolak beda nomor lambung ("Dolphin Nusantara"
     * dan "Dolphin Nusantara 2") karena di luar konteks itu angka tersebut
     * membedakan dua kapal kembar. Di sini kandidatnya sudah sesempit mungkin:
     * hanya operasi yang memang diserahkan kepada regu ini dan belum dipakai
     * baris lain di laporan yang sama. Pada muat kantong, nomor WO yang
     * berlainan tetap menandakan pekerjaan berbeda.
     */
    public function correctedHandover(DailyReport $report, string $type, string $shipName, ?string $workOrder = null): ?ShipOperation
    {
        $base = ShipNameNormalizer::baseKey($shipName);

        if ($base === '') {
            return null;
        }

        return $this->pendingFor($report, $type)
            ->reject(fn (ShipOperation $operation): bool => $this->reportLinksOperation($report->id, $operation->id))
            ->reject(fn (ShipOperation $operation): bool => $type === ShipOperation::TYPE_BAG_LOADING
                && ShipOperation::workOrdersConflict($operation->wo_number, $workOrder))
            ->first(fn (ShipOperation $operation): bool => ShipNameNormalizer::isSameShip(
                $base,
                ShipNameNormalizer::baseKey($operation->ship_name),
            ));
    }

    /**
     * Laporan regu penerima yang melepas operasi ini, atau null bila regu
     * itu belum memutuskan apa pun.
     *
     * Laporan penerima yang formnya dibuka SEBELUM serah terimanya terkirim
     * (laporan pengirim yang terlambat dibuat) tidak dihitung: petugasnya
     * belum pernah melihat kapal tersebut, jadi tidak mencantumkannya bukan
     * berarti melepasnya. Operasi itu tetap dibawa ke laporan regu tersebut
     * berikutnya.
     */
    private function releasingReport(ShipOperation $operation): ?DailyReport
    {
        $handover = $operation->lastReport;

        if (! $handover || ! $handover->report_date) {
            return null;
        }

        $group = strtoupper(trim((string) $handover->received_by_group));
        $visibleSince = ShipOperationDecision::query()
            ->where('ship_operation_id', $operation->id)
            ->where('daily_report_id', $handover->id)
            ->value('decided_at') ?? $handover->created_at;

        $successor = DailyReport::query()
            ->where('group_name', $group)
            ->whereIn('status', self::SENT_STATUSES)
            ->whereKeyNot($handover->id)
            ->whereDate('report_date', '>=', $handover->report_date->toDateString())
            ->when($visibleSince !== null, fn ($query) => $query->where('created_at', '>=', $visibleSince))
            ->get()
            ->filter(fn (DailyReport $report): bool => self::isBefore($handover, $report))
            ->sort(fn (DailyReport $left, DailyReport $right): int => self::chronology($left) <=> self::chronology($right))
            ->first();

        // Bila laporan penerima ternyata mencantumkan operasinya, tautan
        // laporan terakhirnya yang tertinggal — bukan operasinya yang dilepas.
        if (! $successor || $this->reportLinksOperation($successor->id, $operation->id)) {
            return null;
        }

        return $successor;
    }

    private function reportLinksOperation(int $reportId, int $operationId): bool
    {
        foreach (self::ACTIVITY_TABLES as $table) {
            if (DB::table($table)->where('daily_report_id', $reportId)->where('ship_operation_id', $operationId)->exists()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Apakah $earlier terjadi sebelum $later menurut tanggal dinas dan urutan
     * shift — urutan yang sama dengan latestLinkedReportForOperation().
     */
    public static function isBefore(?DailyReport $earlier, DailyReport $later): bool
    {
        if (! $earlier || ! $earlier->report_date || ! $later->report_date) {
            return false;
        }

        return self::chronology($earlier) < self::chronology($later);
    }

    /** @return array{0: string, 1: int, 2: int} */
    private static function chronology(DailyReport $report): array
    {
        $rank = match (strtolower(trim((string) $report->shift))) {
            'malam' => 3,
            'sore', 'siang' => 2,
            default => 1,
        };

        return [$report->report_date?->toDateString() ?? '', $rank, (int) $report->id];
    }
}
