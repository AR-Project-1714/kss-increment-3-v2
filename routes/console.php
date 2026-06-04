<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Storage;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Tugas Terjadwal
|--------------------------------------------------------------------------
| Agar berjalan di VPS, daftarkan satu cron berikut di server:
|   * * * * * cd /path-ke-aplikasi && php artisan schedule:run >> /dev/null 2>&1
*/

// Bersihkan draft & saran operasi kapal kadaluarsa setiap hari, sehingga tetap
// terjadi walau tidak ada yang membuka halaman (pembersihan on-request tetap ada).
Schedule::command('reports:prune-stale')->dailyAt('01:30');

// Backup otomatis mengikuti pengaturan admin (admin-backups/schedule.json).
$backupSchedule = ['frequency' => 'Harian', 'time' => '02:00'];

if (Storage::disk('local')->exists('admin-backups/schedule.json')) {
    $decoded = json_decode((string) Storage::disk('local')->get('admin-backups/schedule.json'), true);

    if (is_array($decoded)) {
        $backupSchedule = array_merge($backupSchedule, $decoded);
    }
}

$backupTime = preg_match('/^\d{2}:\d{2}$/', (string) ($backupSchedule['time'] ?? ''))
    ? $backupSchedule['time']
    : '02:00';

$backupEvent = Schedule::command('backup:run');

// Frekuensi diset lebih dulu (mengubah jam/menit dasar), baru jam spesifik.
match ($backupSchedule['frequency'] ?? 'Harian') {
    'Mingguan' => $backupEvent->weekly(),
    'Bulanan' => $backupEvent->monthly(),
    default => $backupEvent->daily(),
};

$backupEvent->at($backupTime);
