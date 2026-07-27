<?php

namespace Database\Seeders\Concerns;

/**
 * Pengaman untuk seeder yang menghasilkan laporan CONTOH.
 *
 * Seeder laporan memakai updateOrCreate berdasarkan tanggal + shift + regu,
 * sehingga bila dijalankan di server production ia dapat MENIMPA laporan asli
 * yang tanggalnya kebetulan sama. Karena itu seeder contoh menolak berjalan di
 * environment production kecuali benar-benar disengaja.
 *
 * Untuk memaksa (mis. server staging yang APP_ENV-nya production):
 *   SEED_SAMPLE_REPORTS=true php artisan db:seed --class=OperationalReportSeeder
 */
trait GuardsSampleData
{
    private function shouldSkipSampleData(): bool
    {
        if (! app()->environment('production')) {
            return false;
        }

        if (filter_var(env('SEED_SAMPLE_REPORTS', false), FILTER_VALIDATE_BOOLEAN)) {
            return false;
        }

        $this->command?->warn(
            static::class.' dilewati: environment production. '
            .'Seeder ini membuat laporan contoh dan dapat menimpa laporan asli. '
            .'Set SEED_SAMPLE_REPORTS=true bila memang disengaja.'
        );

        return true;
    }
}
