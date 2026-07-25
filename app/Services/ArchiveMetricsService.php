<?php

namespace App\Services;

use App\Enums\MaintenanceStatus;
use App\Enums\ReportStatus;
use App\Enums\SafetyStatus;
use App\Models\DailyReport;
use App\Models\MaintenanceReport;
use App\Models\SafetyReport;
use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * Statistik halaman Arsip Laporan.
 *
 * Angkanya mencakup tiga divisi sekaligus. Semua metrik di sini punya stempel
 * tanggal di tabelnya sendiri, jadi pembandingnya dihitung langsung dari data
 * — tidak perlu rekaman harian seperti metrik sistem.
 */
class ArchiveMetricsService
{
    /**
     * Tiga sumber laporan beserta status yang dianggap "sudah masuk arsip"
     * dan status yang masih menunggu tindakan.
     *
     * @return array<int, array<string, mixed>>
     */
    private function sources(): array
    {
        return [
            [
                'key' => 'operasional',
                'label' => 'Operasional',
                'model' => DailyReport::class,
                'archived' => [ReportStatus::Submitted->value, ReportStatus::Acknowledged->value, ReportStatus::Approved->value],
                'pending' => [ReportStatus::Acknowledged->value],
            ],
            [
                'key' => 'pemeliharaan',
                'label' => 'Pemeliharaan',
                'model' => MaintenanceReport::class,
                'archived' => [MaintenanceStatus::Submitted->value, MaintenanceStatus::Approved->value],
                'pending' => [MaintenanceStatus::Submitted->value],
            ],
            [
                'key' => 'safety',
                'label' => 'Safety / K3',
                'model' => SafetyReport::class,
                'archived' => [SafetyStatus::Submitted->value, SafetyStatus::Approved->value],
                'pending' => [SafetyStatus::Submitted->value],
            ],
        ];
    }

    /**
     * Empat kartu ringkasan arsip beserta perubahannya.
     *
     * @return array<int, array<string, mixed>>
     */
    public function cards(): array
    {
        $today = Carbon::today();
        $monthStart = $today->copy()->startOfMonth();

        $todayCount = $this->countBetween($today, $today);
        $yesterdayCount = $this->countBetween($today->copy()->subDay(), $today->copy()->subDay());

        // Bulan lalu dipotong pada tanggal yang sama supaya perbandingannya
        // adil — 1-25 Juli dibanding 1-25 Juni, bukan Juni sebulan penuh.
        $monthCount = $this->countBetween($monthStart, $today);
        $prevMonthStart = $monthStart->copy()->subMonthNoOverflow();
        $prevMonthCount = $this->countBetween($prevMonthStart, $today->copy()->subMonthNoOverflow());

        $pendingCount = $this->pendingCount();
        $totalCount = $this->totalCount();

        $completed = $totalCount - $pendingCount;
        $completionRate = $totalCount > 0 ? ($completed / $totalCount) * 100 : 0.0;

        return [
            [
                'key' => 'today',
                'label' => 'Laporan Hari Ini',
                'value' => number_format($todayCount, 0, ',', '.'),
                'unit' => 'laporan',
                'icon' => 'fi fi-sr-calendar',
                'tint' => 'green',
                'delta' => $this->countDelta($todayCount, $yesterdayCount),
                'note' => 'vs kemarin',
            ],
            [
                'key' => 'pending',
                'label' => 'Menunggu Tindakan',
                'value' => number_format($pendingCount, 0, ',', '.'),
                'unit' => 'laporan',
                'icon' => 'fi fi-sr-document',
                'tint' => 'orange',
                // Menumpuknya antrean bukan kabar baik, jadi nadanya dibalik.
                'delta' => $this->shareDelta($pendingCount, $totalCount, downIsGood: true),
                'note' => $pendingCount > 0
                    ? 'menunggu tanda tangan manajer'
                    : 'tidak ada antrean',
            ],
            [
                'key' => 'month',
                'label' => 'Laporan Bulan Ini',
                'value' => number_format($monthCount, 0, ',', '.'),
                'unit' => 'laporan',
                'icon' => 'fi fi-sr-folder',
                'tint' => 'cyan',
                'delta' => $this->countDelta($monthCount, $prevMonthCount),
                'note' => 'vs '.$prevMonthStart->locale('id')->translatedFormat('F'),
            ],
            [
                'key' => 'total',
                'label' => 'Total Arsip',
                'value' => number_format($totalCount, 0, ',', '.'),
                'unit' => 'laporan',
                'icon' => 'fi fi-sr-book-alt',
                'tint' => 'blue',
                // Ini tingkat penyelesaian, bukan perubahan antar periode —
                // panah naik/turun akan salah dibaca, jadi badge-nya polos.
                'delta' => [
                    'available' => true,
                    'text' => number_format($completionRate, 1, ',', '.').'% selesai',
                    'direction' => 'none',
                    'tone' => $completionRate >= 90 ? 'up' : 'flat',
                ],
                'note' => number_format($completed, 0, ',', '.').' sudah selesai diproses',
            ],
        ];
    }

    // ============================================================
    // Perhitungan
    // ============================================================

    private function countBetween(CarbonInterface $start, CarbonInterface $end): int
    {
        $total = 0;

        foreach ($this->sources() as $source) {
            $total += $source['model']::query()
                ->whereIn('status', $source['archived'])
                ->whereBetween('report_date', [$start->toDateString(), $end->toDateString()])
                ->count();
        }

        return $total;
    }

    private function pendingCount(): int
    {
        $total = 0;

        foreach ($this->sources() as $source) {
            $total += $source['model']::query()->whereIn('status', $source['pending'])->count();
        }

        return $total;
    }

    private function totalCount(): int
    {
        $total = 0;

        foreach ($this->sources() as $source) {
            $total += $source['model']::query()->whereIn('status', $source['archived'])->count();
        }

        return $total;
    }

    /**
     * @return array<string, mixed>
     */
    private function countDelta(int $current, int $previous): array
    {
        if ($previous <= 0) {
            return [
                'available' => false,
                'text' => $current > 0 ? 'Baru pada periode ini' : 'Belum ada data',
                'direction' => 'flat',
                'tone' => 'flat',
            ];
        }

        $change = $current - $previous;

        if ($change === 0) {
            return ['available' => true, 'text' => 'Tetap', 'direction' => 'flat', 'tone' => 'flat'];
        }

        return [
            'available' => true,
            'text' => number_format(abs($change / $previous) * 100, 1, ',', '.').'%',
            'direction' => $change > 0 ? 'up' : 'down',
            'tone' => $change > 0 ? 'up' : 'down',
        ];
    }

    /**
     * Porsi sebuah angka terhadap keseluruhan, ditampilkan sebagai persentase.
     *
     * @return array<string, mixed>
     */
    private function shareDelta(int $part, int $whole, bool $downIsGood = false): array
    {
        if ($whole <= 0) {
            return ['available' => false, 'text' => 'Belum ada arsip', 'direction' => 'flat', 'tone' => 'flat'];
        }

        $share = ($part / $whole) * 100;

        return [
            'available' => true,
            'text' => number_format($share, 1, ',', '.').'% dari arsip',
            // Porsi antrean bukan perubahan antar periode, jadi tanpa panah.
            'direction' => 'none',
            'tone' => match (true) {
                $part === 0 => 'up',
                $share >= 10 => $downIsGood ? 'down' : 'up',
                default => 'flat',
            },
        ];
    }
}
