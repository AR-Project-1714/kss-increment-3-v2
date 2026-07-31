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

// Tonase muat curah adalah SELISIH antar pembacaan COB sepanjang satu
// pelayaran, bukan jumlah pembacaannya. Selisih itu sudah dihitung ulang tiap
// kali laporan disimpan, tetapi status laporan masih bisa berubah setelahnya
// (draft menjadi terkirim, laporan disetujui) dan itu mengubah rangkaian yang
// ikut dihitung. Sekali sehari seluruh pelayaran dirangkai ulang agar angkanya
// tidak pernah tertinggal. Penggabungan operasi kapal TIDAK ikut dijalankan di
// sini — itu mengubah struktur data dan sebaiknya dijalankan sadar lewat
// `php artisan ops:repair-ship-identity`.
Schedule::command('ops:repair-ship-identity --recalculate-only')->dailyAt('01:45');

// Bundel ZIP arsip dirakit oleh job di queue. Di VPS tanpa worker daemon
// (supervisor/systemd), cron per menit yang sudah ada sekaligus menguras
// antrean: satu proses per menit, berhenti begitu antrean kosong, dan
// withoutOverlapping mencegah dua worker berjalan bersamaan.
// Kalau server sudah punya worker daemon sendiri, baris ini boleh dihapus.
// --memory=512: render dompdf memakan ~100 MB, jauh di atas batas bawaan
// 128 MB, dan worker yang melampauinya berhenti di tengah bundel.
// runInBackground() penting: merakit satu bundel bisa berjalan beberapa menit,
// dan tanpa ini proses schedule:run ikut tertahan selama itu sehingga tugas
// terjadwal lain pada menit yang sama (backup, snapshot) ikut mundur.
Schedule::command('queue:work --stop-when-empty --max-time=55 --memory=512')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();

// Buang bundel ZIP yang sudah kedaluwarsa (24 jam) agar disk tidak menumpuk,
// dan tandai bundel yang terhenti supaya UI tidak menunggu selamanya.
Schedule::command('archive:prune-bundles')->hourly();

// Rekam keadaan sistem menjelang tengah malam, selagi hitungan aktivitas hari
// itu masih utuh. Storage dan jumlah pengguna tidak punya jejak historis, jadi
// tanpa rekaman ini kartu dashboard admin kehilangan angka pembandingnya.
Schedule::command('system:snapshot')->dailyAt('23:50');

// Saldo cloud di-cache agar dashboard tidak memanggil API eksternal pada
// setiap request. Snapshot berkala juga menjadi fallback saat API terganggu.
Schedule::command('idcloudhost:refresh-credit')
    ->everyThirtyMinutes()
    ->withoutOverlapping();

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
