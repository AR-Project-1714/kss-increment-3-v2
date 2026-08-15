<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Penyembuhan data lama: lengkapi kemasan baris bongkar bahan baku yang
 * kemasannya hanya tertulis pada nama bahan.
 *
 * Laporan sebelum 13 Agustus 2026 belum punya kolom kemasan, jadi petugas
 * menulisnya menyatu dengan nama bahan — "MGO 18% Bag @50Kg". Tanpa faktor
 * konversi, rekap kinerja membaca jumlah Bag sebagai Ton, dan bag 50 Kg
 * membengkak dua puluh kali lipat.
 *
 * Perbaikannya sudah dijalankan sekali oleh migrasi
 * 2026_08_15_000001_backfill_material_packaging_from_raw_type. Perintah ini
 * memanggil pengisian data yang sama supaya rencananya bisa dilihat lebih dulu
 * pada basis data produksi, dan supaya baris bergaya lama yang menyusul —
 * misalnya laporan yang baru dipulihkan dari cadangan — bisa dirapikan tanpa
 * memutar ulang migrasi.
 *
 * Aman diulang: baris yang kodenya sudah terisi tidak pernah disentuh.
 *
 * Jalankan dengan --dry-run lebih dulu untuk melihat rencananya tanpa menulis.
 */
class RepairMaterialPackaging extends Command
{
    protected $signature = 'material:repair-packaging
                            {--dry-run : Tampilkan rencana perubahan tanpa menyimpan apa pun}';

    protected $description = 'Lengkapi kemasan baris bongkar bahan baku yang kemasannya menyatu dengan nama bahan';

    /** Batas baris rencana yang dicetak; sisanya cukup diringkas jumlahnya. */
    private const PREVIEW_ROWS = 60;

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->warn('Mode pratinjau: tidak ada perubahan yang disimpan.');
        }

        $report = $this->backfiller()->backfill($dryRun);

        $this->reportResolved($report['resolved']);
        $this->reportUnresolved($report['unresolved']);

        $this->newLine();
        $this->info(sprintf(
            'Tonase bongkar bahan baku: %s Ton -> %s Ton.',
            number_format($report['tonnage_before'], 2),
            number_format($report['tonnage_after'], 2)
        ));

        if ($dryRun) {
            $this->info('Pratinjau selesai. Jalankan tanpa --dry-run untuk menyimpan.');

            return self::SUCCESS;
        }

        $this->info(sprintf('%d baris dilengkapi kemasannya.', count($report['resolved'])));

        return self::SUCCESS;
    }

    /**
     * Logika pengisiannya tinggal satu tempat, yaitu migrasinya, supaya perintah
     * ini dan migrasi tidak pernah menilai baris yang sama dengan cara berbeda.
     */
    private function backfiller(): object
    {
        return require database_path('migrations/2026_08_15_000001_backfill_material_packaging_from_raw_type.php');
    }

    /** @param  array<int, array<string, mixed>>  $rows */
    private function reportResolved(array $rows): void
    {
        if ($rows === []) {
            $this->newLine();
            $this->info('Tidak ada baris bergaya lama yang perlu dilengkapi.');

            return;
        }

        $this->newLine();
        $this->line(sprintf('Kemasan yang dikenali (%d baris):', count($rows)));

        $this->table(
            ['ID', 'Nama bahan tercatat', 'Kemasan', 'Jumlah', 'Terbaca sebelum', 'Menjadi'],
            collect($rows)->take(self::PREVIEW_ROWS)->map(fn (array $row): array => [
                $row['id'],
                $row['raw_material_type'],
                $row['code'],
                $this->number($row['qty_current']).' Bag',
                $this->number($row['before']).' Ton',
                $this->number($row['after']).' Ton',
            ])->all()
        );

        if (count($rows) > self::PREVIEW_ROWS) {
            $this->line(sprintf('… dan %d baris lainnya.', count($rows) - self::PREVIEW_ROWS));
        }
    }

    /**
     * Baris yang dilewati bukan kegagalan: sebagian besar memang dicatat dalam
     * Ton sejak awal. Yang bernilai lebih dari nol tetap didaftar agar bisa
     * dicocokkan dengan laporan kertas bila angkanya terasa janggal.
     *
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function reportUnresolved(array $rows): void
    {
        $named = collect($rows)
            ->filter(fn (array $row): bool => $row['qty_current'] > 0 && trim((string) $row['raw_material_type']) !== '')
            ->values();

        if ($named->isEmpty()) {
            return;
        }

        $this->newLine();
        $this->warn(sprintf('Dilewati, kemasannya tidak dapat dipastikan (%d baris berisi):', $named->count()));

        $this->table(
            ['ID', 'Nama bahan tercatat', 'Jumlah', 'Dibaca sebagai'],
            $named->take(self::PREVIEW_ROWS)->map(fn (array $row): array => [
                $row['id'],
                $row['raw_material_type'],
                $this->number($row['qty_current']),
                $this->number($row['qty_current']).' Ton',
            ])->all()
        );

        if ($named->count() > self::PREVIEW_ROWS) {
            $this->line(sprintf('… dan %d baris lainnya.', $named->count() - self::PREVIEW_ROWS));
        }
    }

    private function number(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2), '0'), '.');
    }
}
