<?php

namespace App\Services;

use App\Models\AdminActivityLog;
use App\Models\DailyReport;
use App\Models\SystemMetricSnapshot;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Metrik sistem untuk dashboard admin.
 *
 * Dua jenis angka digabung di sini. Yang pertama bersifat kumulatif — storage
 * terpakai dan jumlah pengguna hanya bisa dibaca sebagai keadaan sekarang,
 * jadi pembandingnya diambil dari tabel system_metric_snapshots yang diisi
 * sekali sehari. Yang kedua bersifat kejadian — aktivitas dan insiden keamanan
 * punya stempel waktu di log, jadi periodenya bisa dihitung langsung.
 */
class SystemMetricsService
{
    /**
     * Kuota storage yang dikomunikasikan ke admin. Bukan hasil pengecekan disk
     * fisik — server bisa saja punya kapasitas berbeda.
     */
    public const STORAGE_CAPACITY_BYTES = 30 * 1024 * 1024 * 1024;

    /**
     * Pengelompokan jenis log untuk grafik. Tipe yang tidak terdaftar masuk ke
     * "Perubahan Data" supaya tidak ada aktivitas yang hilang dari grafik.
     */
    private const ACTIVITY_GROUPS = [
        'login' => ['login'],
        'security' => ['security', 'error', 'delete'],
    ];

    // ============================================================
    // Kartu KPI dashboard
    // ============================================================

    /**
     * Empat kartu ringkasan beserta perubahannya.
     *
     * @return array<int, array<string, mixed>>
     */
    public function dashboardCards(): array
    {
        $today = Carbon::today();
        $usedBytes = $this->storageUsedBytes();
        $usedPercent = self::STORAGE_CAPACITY_BYTES > 0
            ? min(100.0, ($usedBytes / self::STORAGE_CAPACITY_BYTES) * 100)
            : 0.0;

        $activeUsers = User::where('status', 'aktif')->count();

        // Pembanding kumulatif: baris terakhir yang tercatat sebelum 7 hari lalu.
        $baseline = $this->snapshotBefore($today->copy()->subDays(7));
        $baselineLabel = $baseline
            ? 'vs '.$baseline->captured_on->locale('id')->translatedFormat('j M')
            : null;

        $activityToday = $this->activityCount($today, $today);
        $activityYesterday = $this->activityCount($today->copy()->subDay(), $today->copy()->subDay());

        $securityToday = $this->activityCount($today, $today, 'security');
        $securityYesterday = $this->activityCount($today->copy()->subDay(), $today->copy()->subDay(), 'security');

        return [
            [
                'key' => 'users',
                'label' => 'Pengguna Aktif',
                'value' => number_format($activeUsers, 0, ',', '.'),
                'icon' => 'fi fi-sr-user',
                'tint' => 'blue',
                'unit' => 'akun',
                'delta' => $baseline
                    ? $this->countDelta($activeUsers, $baseline->active_users)
                    : $this->unavailable('Belum ada riwayat'),
                'note' => $baselineLabel,
            ],
            [
                'key' => 'storage',
                // Pemakaian nyata jauh di bawah kuota, sehingga persentasenya
                // membulat ke angka yang tidak berarti. Ukuran terpakai dipakai
                // sebagai nilai utama, persentase turun jadi keterangan.
                'label' => 'Storage Terpakai',
                'value' => $this->splitBytes($usedBytes)['value'],
                'icon' => 'fi fi-sr-database',
                'tint' => 'cyan',
                'unit' => $this->splitBytes($usedBytes)['unit'],
                // Naiknya pemakaian storage bukan kabar baik, jadi nadanya dibalik.
                'delta' => $baseline
                    ? $this->countDelta($usedBytes, $baseline->storage_used_bytes, downIsGood: true, humanBytes: true)
                    : $this->unavailable('Belum ada riwayat'),
                'note' => number_format($usedPercent, 1, ',', '.').'% dari '.$this->formatBytes(self::STORAGE_CAPACITY_BYTES),
            ],
            [
                'key' => 'activity',
                'label' => 'Aktivitas Hari Ini',
                'value' => number_format($activityToday, 0, ',', '.'),
                'icon' => 'fi fi-sr-chart-histogram',
                'tint' => 'green',
                'unit' => 'kejadian',
                // Hari ini belum selesai, jadi angkanya hampir selalu lebih
                // rendah dari kemarin. Perubahannya ditampilkan tanpa nada
                // baik/buruk supaya tidak terbaca sebagai peringatan.
                'delta' => $this->countDelta($activityToday, $activityYesterday, neutral: true),
                'note' => 'vs kemarin, hari masih berjalan',
            ],
            [
                'key' => 'security',
                'label' => 'Kejadian Keamanan',
                'value' => number_format($securityToday, 0, ',', '.'),
                'icon' => 'fi fi-sr-shield-exclamation',
                'tint' => 'orange',
                'unit' => 'kejadian',
                'delta' => $this->countDelta($securityToday, $securityYesterday, downIsGood: true),
                'note' => 'vs kemarin',
            ],
        ];
    }

    /**
     * Deret harian aktivitas sistem untuk grafik area bertumpuk.
     *
     * @return array<int, array<string, mixed>>
     */
    public function activityTrend(int $days = 30): array
    {
        $end = Carbon::today();
        $start = $end->copy()->subDays($days - 1);

        $buckets = [];
        $cursor = $start->copy();

        while ($cursor->lessThanOrEqualTo($end)) {
            $buckets[$cursor->toDateString()] = [
                'key' => $cursor->toDateString(),
                'label' => $cursor->locale('id')->translatedFormat('j M'),
                'Login' => 0,
                'Perubahan Data' => 0,
                'Keamanan' => 0,
            ];
            $cursor->addDay();
        }

        $rows = AdminActivityLog::query()
            ->whereBetween('created_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->selectRaw($this->dateBucket('created_at').' as bucket')
            ->selectRaw('type as type')
            ->selectRaw('COUNT(*) as total')
            ->groupBy(DB::raw($this->dateBucket('created_at')), 'type')
            ->get();

        foreach ($rows as $row) {
            if (! isset($buckets[$row->bucket])) {
                continue;
            }

            $buckets[$row->bucket][$this->activityGroup((string) $row->type)] += (int) $row->total;
        }

        return array_values(array_map(static function (array $bucket): array {
            $bucket['total'] = $bucket['Login'] + $bucket['Perubahan Data'] + $bucket['Keamanan'];

            return $bucket;
        }, $buckets));
    }

    // ============================================================
    // Perekaman harian
    // ============================================================

    /**
     * Simpan keadaan sistem untuk satu tanggal. Dipanggil ulang pada tanggal
     * yang sama akan menimpa barisnya, sehingga perintah boleh dijalankan
     * beberapa kali sehari tanpa menggandakan data.
     */
    public function capture(?CarbonInterface $date = null): SystemMetricSnapshot
    {
        $date = ($date ?? Carbon::today())->copy()->startOfDay();

        return SystemMetricSnapshot::updateOrCreate(
            ['captured_on' => $date->toDateString()],
            [
                'storage_used_bytes' => $this->storageUsedBytes(),
                'active_users' => User::where('status', 'aktif')->count(),
                'total_users' => User::count(),
                'security_events' => $this->activityCount($date, $date, 'security'),
                'activity_events' => $this->activityCount($date, $date),
                'reports_created' => DailyReport::whereDate('created_at', $date->toDateString())->count(),
            ]
        );
    }

    // ============================================================
    // Pemakaian storage
    // ============================================================

    /**
     * Total pemakaian: kode aplikasi (tanpa dependency dan riwayat git) +
     * dokumen laporan tersimpan + ukuran database. Di-cache singkat karena
     * memindai filesystem tiap muat halaman terlalu mahal.
     */
    public function storageUsedBytes(): int
    {
        return Cache::remember('admin.storage-usage-bytes', now()->addMinutes(5), function (): int {
            return $this->codebaseSizeBytes()
                + $this->storedReportDocumentsSizeBytes()
                + $this->databaseSizeBytes();
        });
    }

    public function formatBytes(int $bytes, int $decimals = 1): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = min((int) floor(log($bytes, 1024)), count($units) - 1);
        $value = $bytes / (1024 ** $power);

        return number_format($value, $power === 0 ? 0 : $decimals, ',', '.').' '.$units[$power];
    }

    /**
     * Ukuran kode aplikasi: seluruh proyek dikurangi dependency yang di-install
     * dan metadata version control — mengikuti definisi yang sama dipakai
     * .gitignore untuk apa yang "bukan" milik repo ini.
     */
    private function codebaseSizeBytes(): int
    {
        return $this->directorySizeBytes(base_path(), ['vendor', 'node_modules', '.git', 'storage']);
    }

    /**
     * Ukuran seluruh dokumen laporan yang tersimpan di ketiga divisi — bukan
     * berkas backup, yang dihitung terpisah.
     */
    private function storedReportDocumentsSizeBytes(): int
    {
        return collect(['reports', 'maintenance-reports', 'safety-reports'])
            ->sum(fn (string $dir): int => $this->directorySizeBytes(Storage::disk('public')->path($dir)));
    }

    /**
     * Ukuran database sesuai driver koneksi: MySQL/MariaDB lewat katalog
     * information_schema, SQLite lewat PRAGMA page_count x page_size — keduanya
     * membaca metadata, bukan memindai tabel. Driver lain atau kegagalan query
     * mengembalikan 0: widget boleh kurang akurat, tapi tidak boleh mematikan
     * halaman.
     */
    private function databaseSizeBytes(): int
    {
        try {
            return match (DB::connection()->getDriverName()) {
                'mysql', 'mariadb' => (int) (DB::selectOne(
                    'SELECT COALESCE(SUM(data_length + index_length), 0) AS bytes FROM information_schema.tables WHERE table_schema = ?',
                    [DB::getDatabaseName()]
                )->bytes ?? 0),
                'sqlite' => (int) ((DB::selectOne('PRAGMA page_count')->page_count ?? 0)
                    * (DB::selectOne('PRAGMA page_size')->page_size ?? 0)),
                default => 0,
            };
        } catch (Throwable) {
            return 0;
        }
    }

    /**
     * Jumlahkan ukuran seluruh berkas di bawah $path secara rekursif. Direktori
     * yang disebut di $skipDirs tidak pernah dimasuki sama sekali (bukan
     * disaring belakangan) — penting agar folder besar seperti vendor/ tidak
     * pernah benar-benar dipindai satu per satu.
     *
     * @param  array<int, string>  $skipDirs
     */
    private function directorySizeBytes(string $path, array $skipDirs = []): int
    {
        if (! is_dir($path)) {
            return 0;
        }

        $filter = new \RecursiveCallbackFilterIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            static fn (\SplFileInfo $current): bool => ! ($current->isDir() && in_array($current->getFilename(), $skipDirs, true))
        );

        $total = 0;

        foreach (new \RecursiveIteratorIterator($filter) as $file) {
            if ($file->isFile()) {
                $total += $file->getSize();
            }
        }

        return $total;
    }

    // ============================================================
    // Pembantu
    // ============================================================

    private function snapshotBefore(CarbonInterface $date): ?SystemMetricSnapshot
    {
        return SystemMetricSnapshot::where('captured_on', '<=', $date->toDateString())
            ->orderByDesc('captured_on')
            ->first();
    }

    private function activityCount(CarbonInterface $start, CarbonInterface $end, ?string $group = null): int
    {
        $query = AdminActivityLog::whereBetween('created_at', [
            $start->copy()->startOfDay(),
            $end->copy()->endOfDay(),
        ]);

        if ($group === 'security') {
            $query->whereIn('type', self::ACTIVITY_GROUPS['security']);
        }

        return $query->count();
    }

    private function activityGroup(string $type): string
    {
        if (in_array($type, self::ACTIVITY_GROUPS['login'], true)) {
            return 'Login';
        }

        if (in_array($type, self::ACTIVITY_GROUPS['security'], true)) {
            return 'Keamanan';
        }

        return 'Perubahan Data';
    }

    /**
     * Pengelompokan per tanggal dalam format YYYY-MM-DD.
     */
    private function dateBucket(string $column): string
    {
        return DB::connection()->getDriverName() === 'sqlite'
            ? "strftime('%Y-%m-%d', ".$column.')'
            : 'DATE('.$column.')';
    }

    /**
     * @return array<string, mixed>
     */
    private function unavailable(string $text): array
    {
        return ['available' => false, 'text' => $text, 'direction' => 'flat', 'tone' => 'flat'];
    }

    /**
     * Perubahan jumlah. Baseline nol tidak diubah menjadi "+100%" karena
     * menyesatkan — yang ditampilkan adalah selisih mutlaknya.
     *
     * @return array<string, mixed>
     */
    private function countDelta(
        int $current,
        int $previous,
        bool $downIsGood = false,
        bool $humanBytes = false,
        bool $neutral = false
    ): array {
        $change = $current - $previous;

        if ($change === 0 || ($humanBytes && abs($change) < 1024)) {
            return ['available' => true, 'text' => 'Tetap', 'direction' => 'flat', 'tone' => 'flat'];
        }

        $direction = $change > 0 ? 'up' : 'down';
        $isGood = $downIsGood ? $change < 0 : $change > 0;
        $tone = $neutral ? 'flat' : ($isGood ? 'up' : 'down');

        if ($humanBytes) {
            return [
                'available' => true,
                'text' => $this->formatBytes(abs($change)),
                'direction' => $direction,
                'tone' => $tone,
            ];
        }

        $suffix = $previous > 0
            ? ' ('.number_format(abs($change / $previous) * 100, 1, ',', '.').'%)'
            : '';

        return [
            'available' => true,
            'text' => number_format(abs($change), 0, ',', '.').$suffix,
            'direction' => $direction,
            'tone' => $tone,
        ];
    }

    /**
     * Pisahkan ukuran menjadi angka dan satuannya, supaya kartu bisa
     * menampilkan keduanya dengan ukuran huruf berbeda.
     *
     * @return array{value: string, unit: string}
     */
    private function splitBytes(int $bytes): array
    {
        $parts = explode(' ', $this->formatBytes($bytes));

        return ['value' => $parts[0] ?? '0', 'unit' => $parts[1] ?? 'B'];
    }
}
