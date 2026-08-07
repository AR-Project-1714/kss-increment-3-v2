<?php

namespace App\Console\Commands;

use App\Models\ContainerItem;
use App\Models\DailyReport;
use App\Support\ContainerStatusNormalizer;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Penyembuhan data lama: seragamkan penanda Empty/Full pada baris container.
 *
 * Kolom "Ket" pada form dulu berupa teks bebas, sementara laporan manajer
 * memilah Bongkar dari Muat dengan pencocokan kata persis. Akibatnya baris
 * yang ditulis "Container empty" atau "Coutener isi" tidak masuk kegiatan mana
 * pun — angkanya hilang tanpa peringatan. Dropdown pada form menghentikan
 * masalah ini untuk laporan baru; perintah ini merapikan yang sudah tersimpan.
 *
 * Dua hal dirapikan sekaligus dan keduanya aman diulang:
 *
 *   1. Kolom container_items.status — sumber angka pada seluruh laporan manajer.
 *   2. Penanda yang sama di dalam daily_reports.payload — yang diputar ulang ke
 *      form saat laporan dibuka. Tanpa ini, membuka laporan lama menampilkan
 *      kolom Empty / Full dalam keadaan kosong karena nilai lamanya tidak cocok
 *      dengan opsi mana pun.
 *
 * Baris yang penandanya memang dikosongkan TIDAK ditebak. Baris seperti itu
 * hanya didaftar agar bisa dicocokkan dengan laporan kertas, lalu dilengkapi
 * lewat menu edit laporan. Menebak maksud operator justru mengulang kesalahan
 * yang sedang diperbaiki.
 *
 * Jalankan dengan --dry-run lebih dulu untuk melihat rencananya tanpa menulis.
 */
class RepairContainerStatus extends Command
{
    protected $signature = 'container:repair-status
                            {--dry-run : Tampilkan rencana perubahan tanpa menyimpan apa pun}';

    protected $description = 'Seragamkan penanda Empty/Full pada baris bongkar-muat container';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->warn('Mode pratinjau: tidak ada perubahan yang disimpan.');
        }

        $items = ContainerItem::query()->orderBy('id')->get(['id', 'container_activity_id', 'status', 'qty_current']);

        $this->reportTranslations($items);
        $this->reportUnmarked($items);

        if ($dryRun) {
            $this->newLine();
            $this->info('Pratinjau selesai. Jalankan tanpa --dry-run untuk menyimpan.');

            return self::SUCCESS;
        }

        $changed = $this->applyItemStatuses($items);
        $payloads = $this->applyPayloadStatuses();
        $refreshed = $this->refreshAffectedReports($changed);

        $this->newLine();
        $this->info(sprintf('%d baris container diperbarui.', $changed));
        $this->info(sprintf('%d payload laporan ikut diseragamkan.', $payloads));
        $this->info(sprintf('%d laporan ditandai berubah agar laporan manajer dihitung ulang.', $refreshed));

        return self::SUCCESS;
    }

    /**
     * Tampilkan pemetaan yang akan dipakai, dikelompokkan menurut tulisan
     * aslinya. Inilah yang perlu ditinjau sebelum perintah dijalankan sungguhan.
     *
     * @param  Collection<int, ContainerItem>  $items
     */
    private function reportTranslations(Collection $items): void
    {
        $groups = $items
            ->filter(fn (ContainerItem $item): bool => ! ContainerStatusNormalizer::isCanonical($item->status)
                && trim((string) $item->status) !== '')
            ->groupBy(fn (ContainerItem $item): string => (string) $item->status);

        if ($groups->isEmpty()) {
            $this->info('Tidak ada penanda bebas yang perlu diterjemahkan.');

            return;
        }

        $this->newLine();
        $this->line('Penanda yang akan diseragamkan:');

        $this->table(
            ['Tulisan asli', 'Menjadi', 'Baris', 'Teus'],
            $groups->map(fn (Collection $rows, string $status): array => [
                $status,
                ContainerStatusNormalizer::normalize($status) ?? '(tidak dapat dipastikan)',
                $rows->count(),
                (string) $rows->sum(fn (ContainerItem $item): float => (float) $item->qty_current),
            ])->values()->all()
        );
    }

    /**
     * Daftar baris yang penandanya kosong tetapi membawa jumlah. Tidak ada yang
     * ditebak di sini — daftar ini bahan pencocokan dengan laporan kertas.
     *
     * @param  Collection<int, ContainerItem>  $items
     */
    private function reportUnmarked(Collection $items): void
    {
        $blank = $items->filter(
            fn (ContainerItem $item): bool => trim((string) $item->status) === ''
                && (float) $item->qty_current !== 0.0
        );

        if ($blank->isEmpty()) {
            return;
        }

        // Identitas laporannya ditarik sekali untuk seluruh baris, bukan per
        // baris, supaya jumlah query tidak ikut tumbuh bersama daftarnya.
        $reports = DB::table('container_activities')
            ->join('daily_reports', 'daily_reports.id', '=', 'container_activities.daily_report_id')
            ->whereIn('container_activities.id', $blank->pluck('container_activity_id')->unique()->all())
            ->select([
                'container_activities.id as activity_id',
                'container_activities.ship_name',
                'daily_reports.id as report_id',
                'daily_reports.report_date',
                'daily_reports.shift',
                'daily_reports.group_name',
            ])
            ->get()
            ->keyBy('activity_id');

        $this->newLine();
        $this->warn(sprintf(
            '%d baris membawa jumlah tetapi penandanya dikosongkan (%s Teus). '
            .'Nilai ini TIDAK ditebak — cocokkan dengan laporan kertas, lalu lengkapi lewat menu edit laporan.',
            $blank->count(),
            number_format($blank->sum(fn (ContainerItem $item): float => (float) $item->qty_current), 0, ',', '.')
        ));

        $this->table(
            ['Laporan', 'Tanggal', 'Shift', 'Regu', 'Kapal', 'Teus'],
            $blank->map(function (ContainerItem $item) use ($reports): array {
                $report = $reports->get($item->container_activity_id);

                return [
                    $report->report_id ?? '-',
                    $report?->report_date ? substr((string) $report->report_date, 0, 10) : '-',
                    $report->shift ?? '-',
                    $report->group_name ?? '-',
                    $report->ship_name ?: '(nama kapal kosong)',
                    (string) (float) $item->qty_current,
                ];
            })->values()->all()
        );
    }

    /**
     * Laporan yang barisnya berubah, untuk menggeser updated_at-nya.
     *
     * @var array<int, true>
     */
    private array $touchedActivities = [];

    /**
     * @param  Collection<int, ContainerItem>  $items
     */
    private function applyItemStatuses(Collection $items): int
    {
        $changed = 0;

        foreach ($items as $item) {
            $normalized = ContainerStatusNormalizer::normalize($item->status);

            if ($normalized === $item->status) {
                continue;
            }

            // Penanda yang sudah kosong dan tetap tidak dapat dipastikan tidak
            // perlu ditulis ulang; menyentuhnya hanya menggeser updated_at.
            if ($normalized === null && trim((string) $item->status) === '') {
                continue;
            }

            $item->status = $normalized;
            $item->save();
            $this->touchedActivities[(int) $item->container_activity_id] = true;
            $changed++;
        }

        return $changed;
    }

    /**
     * Geser updated_at laporan yang barisnya berubah.
     *
     * Laporan manajer di-cache dengan kunci yang memuat updated_at laporan
     * terbaru. Tanpa langkah ini, perbaikan sudah masuk database tetapi angka
     * di layar manajer masih yang lama sampai cache-nya kedaluwarsa — persis
     * hal yang paling membingungkan sesudah perbaikan data dijalankan.
     */
    private function refreshAffectedReports(int $changed): int
    {
        if ($changed === 0 || $this->touchedActivities === []) {
            return 0;
        }

        $reportIds = DB::table('container_activities')
            ->whereIn('id', array_keys($this->touchedActivities))
            ->pluck('daily_report_id')
            ->unique()
            ->all();

        if ($reportIds === []) {
            return 0;
        }

        return DailyReport::whereIn('id', $reportIds)->update(['updated_at' => now()]);
    }

    /**
     * Seragamkan penanda pada payload laporan, yang diputar ulang ke form.
     */
    private function applyPayloadStatuses(): int
    {
        $changed = 0;

        DailyReport::query()
            ->whereNotNull('payload')
            ->select(['id', 'payload'])
            ->chunkById(100, function (Collection $reports) use (&$changed): void {
                foreach ($reports as $report) {
                    $payload = $report->payload;

                    if (! is_array($payload) || ! isset($payload['fields']) || ! is_array($payload['fields'])) {
                        continue;
                    }

                    $touched = false;

                    foreach ($payload['fields'] as $index => $field) {
                        if (! is_array($field) || ! isset($field['key']) || ! is_string($field['key'])) {
                            continue;
                        }

                        if (preg_match('/^unloading_containers_\d+\[\d+\]\[status\]$/', $field['key']) !== 1) {
                            continue;
                        }

                        $normalized = ContainerStatusNormalizer::normalize($field['value'] ?? null) ?? '';

                        if ($normalized === ($field['value'] ?? '')) {
                            continue;
                        }

                        $payload['fields'][$index]['value'] = $normalized;
                        $touched = true;
                    }

                    if (! $touched) {
                        continue;
                    }

                    $report->payload = $payload;
                    $report->save();
                    $changed++;
                }
            });

        return $changed;
    }
}
