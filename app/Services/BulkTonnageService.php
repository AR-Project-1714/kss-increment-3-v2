<?php

namespace App\Services;

use App\Support\ShipNameNormalizer;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Menerjemahkan pembacaan COB menjadi tonase yang boleh dijumlahkan.
 *
 * MASALAHNYA. Pada form muat curah, petugas mencatat COB (Cargo On Board):
 * berapa ton yang SUDAH ADA di kapal saat itu. Angkanya kumulatif dan naik
 * terus sepanjang pelayaran — persis seperti odometer. Satu pelayaran
 * KM. Golden Rejeki yang benar-benar memuat 41.130 ton meninggalkan puluhan
 * baris log: 5.000, 7.840, 9.140, 10.000, 12.500, ... , 41.130. Menjumlahkan
 * seluruh baris itu menghasilkan ratusan ribu ton untuk satu kapal, dan itulah
 * sebabnya total sebulan bisa membengkak sampai puluhan kali lipat.
 *
 * CARA MEMBACANYA. Tonase yang benar untuk satu pelayaran adalah SELISIH,
 * bukan jumlah: COB terakhir dikurangi COB saat mulai (0 ketika kapal sandar
 * kosong). Supaya angka per shift dan per regu tetap bisa dipotong, selisih itu
 * dibagi kembali ke baris log yang menyebabkannya lewat kolom cob_delta.
 * Dengan begitu SUM(cob_delta) benar di semua tingkat — per shift, per regu,
 * per bulan — dan jumlah seluruh shift satu pelayaran otomatis sama dengan
 * COB terakhirnya.
 *
 * EMPAT CACAT DATA YANG IKUT DITANGANI. Semuanya nyata, terbaca pada backup
 * produksi Juli 2026:
 *
 *   1. Pembacaan kosong / 0. Bukan berarti muatan nol, melainkan tidak ada
 *      penimbangan pada kejadian itu. Diabaikan, bukan dianggap turun ke nol.
 *   2. Satuan campur. Sebagian shift menulis "16.75" untuk 16.750 ton.
 *      Dikembalikan ke ton penuh bila — dan hanya bila — nilai itu tampak
 *      mundur, kelipatan seribunya melanjutkan pembacaan sebelumnya, dan
 *      hasilnya masih masuk kapasitas kapal.
 *   3. Koreksi kecil ke bawah (4.863 lalu 4.280 karena draft survey ulang).
 *      Tidak boleh menambah tonase, juga tidak boleh dianggap pelayaran baru.
 *   4. Kapal yang sama datang lagi. COB mulai dari nol lagi, jadi rangkaiannya
 *      dipotong menjadi pelayaran terpisah.
 *
 * Perhitungan ini deterministik dan bisa dijalankan ulang kapan saja lewat
 * `php artisan ops:repair-ship-identity`.
 */
class BulkTonnageService
{
    /**
     * Jeda antarpembacaan yang menandai kapal sudah berangkat dan kembali lagi
     * sebagai pelayaran baru. Operasi muat curah bisa tertahan beberapa hari
     * karena cuaca atau antrean jetty, jadi ambangnya dibuat longgar.
     */
    public const VOYAGE_GAP_DAYS = 7;

    /**
     * Batas atas kewajaran: pembacaan di atas kapasitas kapal dikali angka ini
     * ditolak sebagai salah ketik, bukan dijadikan tonase.
     */
    public const CAPACITY_TOLERANCE = 1.15;

    /** Urutan shift dalam satu hari dinas. */
    private const SHIFT_RANK = ['Pagi' => 1, 'Sore' => 2, 'Malam' => 3];

    /**
     * Hitung ulang cob_delta. Tanpa argumen, seluruh pelayaran dihitung ulang.
     *
     * @param  array<int, string>|null  $voyageKeys  hasil voyageKey(), atau null untuk semua
     * @return int jumlah baris log yang nilainya berubah
     */
    public function recalculate(?array $voyageKeys = null): int
    {
        $updates = [];

        foreach ($this->readings($voyageKeys) as $voyage) {
            foreach ($this->walk($voyage) as $id => $computed) {
                $updates[$id] = $computed;
            }
        }

        return $this->flush($updates);
    }

    /**
     * Hitung ulang seluruh pelayaran yang disentuh sebuah laporan.
     *
     * Menyimpan satu laporan bisa mengubah tonase shift-shift SESUDAHNYA pada
     * pelayaran yang sama, karena selisih dihitung terhadap pembacaan
     * sebelumnya. Karena itu yang dihitung ulang adalah pelayarannya, bukan
     * baris laporan itu saja.
     */
    public function recalculateForReport(int $reportId): int
    {
        $keys = DB::table('bulk_loading_activities')
            ->where('daily_report_id', $reportId)
            ->get(['ship_operation_id', 'ship_name', 'ship_name_key', 'activity_type'])
            ->map(fn ($row): string => $this->voyageKeyFor($row))
            ->unique()
            ->values()
            ->all();

        return $keys === [] ? 0 : $this->recalculate($keys);
    }

    /**
     * Kunci pengelompokan satu pelayaran.
     *
     * Nomor operasi kapal dipakai bila ada karena itulah identitas yang sudah
     * disepakati petugas. Bila petugas melewati fitur simpan operasi kapal,
     * nama kanonik menjadi cadangannya — jauh lebih baik daripada nama mentah
     * yang ejaannya berbeda tiap shift.
     */
    public function voyageKey(?int $shipOperationId, string $shipNameKey, string $activityType): string
    {
        if ($shipOperationId !== null && $shipOperationId > 0) {
            return 'op:'.$shipOperationId;
        }

        return 'name:'.$activityType.'|'.$shipNameKey;
    }

    /**
     * Kunci pelayaran untuk satu baris aktivitas.
     *
     * ship_name_key dihitung ulang dari nama bila kolomnya masih kosong —
     * baris lama yang belum tersentuh perintah perbaikan tetap terkelompok
     * benar, dan bukan tumpah menjadi satu pelayaran tanpa nama bersama-sama.
     */
    private function voyageKeyFor(object $row): string
    {
        $key = (string) ($row->ship_name_key ?? '');

        if ($key === '') {
            $key = ShipNameNormalizer::key($row->ship_name ?? null);
        }

        return $this->voyageKey(
            $row->ship_operation_id !== null ? (int) $row->ship_operation_id : null,
            $key,
            (string) ($row->activity_type ?? ''),
        );
    }

    /**
     * Pembacaan COB per pelayaran, sudah urut kronologis.
     *
     * @param  array<int, string>|null  $voyageKeys
     * @return array<string, array<int, object>>
     */
    private function readings(?array $voyageKeys): array
    {
        $rows = DB::table('bulk_loading_logs')
            ->join('bulk_loading_activities', 'bulk_loading_logs.bulk_loading_activity_id', '=', 'bulk_loading_activities.id')
            ->join('daily_reports', 'daily_reports.id', '=', 'bulk_loading_activities.daily_report_id')
            // Draft masih bisa berubah dan tidak pernah ikut statistik. Bila
            // ikut dirangkai, pembacaannya akan "mencuri" selisih milik shift
            // berikutnya yang sudah terkirim.
            ->whereIn('daily_reports.status', OperationalPerformanceService::COUNTED_STATUSES)
            ->orderBy('daily_reports.report_date')
            ->orderByRaw($this->shiftRankExpression())
            ->orderBy('bulk_loading_activities.sequence')
            ->orderBy('bulk_loading_logs.id')
            ->get([
                'bulk_loading_logs.id',
                'bulk_loading_logs.cob',
                'bulk_loading_logs.cob_normalized',
                'bulk_loading_logs.cob_delta',
                'bulk_loading_activities.ship_operation_id',
                'bulk_loading_activities.ship_name',
                'bulk_loading_activities.ship_name_key',
                'bulk_loading_activities.activity_type',
                'bulk_loading_activities.capacity',
                'daily_reports.report_date',
            ]);

        $voyages = [];

        foreach ($rows as $row) {
            $key = $this->voyageKeyFor($row);

            if ($voyageKeys !== null && ! in_array($key, $voyageKeys, true)) {
                continue;
            }

            $voyages[$key][] = $row;
        }

        return $voyages;
    }

    /**
     * Telusuri satu pelayaran dan bagikan selisihnya ke tiap baris log.
     *
     * @param  array<int, object>  $readings
     * @return array<int, array{cob_normalized: float|null, cob_delta: float}>
     */
    private function walk(array $readings): array
    {
        $capacity = 0.0;

        foreach ($readings as $row) {
            $capacity = max($capacity, (float) ($row->capacity ?? 0));
        }

        $ceiling = $capacity > 0 ? $capacity * self::CAPACITY_TOLERANCE : 0.0;

        $result = [];
        $highWater = 0.0;
        $lastReadingAt = null;

        foreach ($readings as $row) {
            $id = (int) $row->id;
            $raw = $row->cob === null ? null : (float) $row->cob;
            $at = $row->report_date ? Carbon::parse($row->report_date) : null;

            // Baris tanpa penimbangan: tidak menambah, tidak mengurangi.
            if ($raw === null || $raw <= 0.0) {
                $result[$id] = ['cob_normalized' => null, 'cob_delta' => 0.0];

                continue;
            }

            // Satuan diseragamkan LEBIH DULU, baru diputuskan apakah rangkaian
            // terputus. Terbalik urutannya, "37.04" pada kapal yang sudah
            // memuat 35.000 ton akan terbaca sebagai muatan anjlok — pelayaran
            // dipotong, dan pembacaan berikutnya (38.170) dihitung penuh
            // sebagai tonase baru.
            $value = $this->repairScale($raw, $highWater, $ceiling);

            if ($this->isNewVoyage($value, $highWater, $capacity, $lastReadingAt, $at)) {
                $highWater = 0.0;

                // Alasan penskalaan ikut gugur bersama rangkaian sebelumnya:
                // pembacaan pertama pelayaran baru memang bernilai kecil.
                $value = $raw;
            }

            if ($ceiling > 0.0 && $value > $ceiling) {
                // Di atas kapasitas kapal + toleransi: angkanya tidak mungkin
                // benar, jadi dicatat apa adanya tetapi tidak menambah tonase.
                $result[$id] = ['cob_normalized' => $value, 'cob_delta' => 0.0];
                $lastReadingAt = $at ?? $lastReadingAt;

                continue;
            }

            $delta = max(0.0, $value - $highWater);
            $highWater = max($highWater, $value);
            $lastReadingAt = $at ?? $lastReadingAt;

            $result[$id] = [
                'cob_normalized' => round($value, 2),
                'cob_delta' => round($delta, 2),
            ];
        }

        return $result;
    }

    /**
     * Apakah pembacaan ini memulai pelayaran baru pada kapal yang sama.
     *
     * Dua penanda harus terlihat bersama-sama supaya koreksi kecil ke bawah
     * tidak salah dibaca sebagai kedatangan berikutnya: muatan sempat terisi
     * banyak, lalu pembacaan anjlok — atau catatannya memang terputus lama.
     */
    private function isNewVoyage(float $reading, float $highWater, float $capacity, ?Carbon $previousAt, ?Carbon $at): bool
    {
        if ($highWater <= 0.0) {
            return false;
        }

        if ($previousAt && $at && abs($previousAt->diffInDays($at)) >= self::VOYAGE_GAP_DAYS) {
            return true;
        }

        $wasSubstantiallyLoaded = $capacity > 0.0
            ? $highWater >= $capacity * 0.5
            : false;

        return $wasSubstantiallyLoaded && $reading < $highWater * 0.5;
    }

    /**
     * Kembalikan pembacaan yang ditulis dalam ribuan ton ke ton penuh.
     *
     * Syaratnya sengaja berlapis supaya angka yang memang kecil — awal
     * pemuatan, atau kapal kecil — tidak ikut dikali seribu:
     *   • angkanya tampak MUNDUR dari pembacaan tertinggi sejauh ini,
     *   • kelipatan seribunya justru MELANJUTKAN pembacaan itu, dan
     *   • hasilnya masih di dalam kapasitas kapal.
     */
    private function repairScale(float $reading, float $highWater, float $ceiling): float
    {
        if ($ceiling <= 0.0 || $reading >= 1000.0 || $reading >= $highWater) {
            return $reading;
        }

        $rescaled = $reading * 1000.0;

        return $rescaled >= $highWater && $rescaled <= $ceiling
            ? $rescaled
            : $reading;
    }

    /**
     * Tulis balik hasil hitungan; hanya baris yang nilainya berubah.
     *
     * @param  array<int, array{cob_normalized: float|null, cob_delta: float}>  $updates
     */
    private function flush(array $updates): int
    {
        if ($updates === []) {
            return 0;
        }

        $written = 0;

        // Satu UPDATE ... CASE per potongan, bukan satu UPDATE per baris:
        // menghitung ulang seluruh riwayat hanya perlu beberapa query, bukan
        // sebanyak baris log.
        foreach (array_chunk($updates, 500, true) as $chunk) {
            $ids = array_keys($chunk);

            $normalizedCase = '';
            $deltaCase = '';
            $bindings = [];

            foreach ($chunk as $id => $values) {
                $normalizedCase .= ' WHEN ? THEN ?';
                $bindings[] = $id;
                $bindings[] = $values['cob_normalized'];
            }

            foreach ($chunk as $id => $values) {
                $deltaCase .= ' WHEN ? THEN ?';
                $bindings[] = $id;
                $bindings[] = $values['cob_delta'];
            }

            $written += DB::update(
                'UPDATE bulk_loading_logs SET'
                .' cob_normalized = CASE id'.$normalizedCase.' ELSE cob_normalized END,'
                .' cob_delta = CASE id'.$deltaCase.' ELSE cob_delta END'
                .' WHERE id IN ('.implode(', ', array_fill(0, count($ids), '?')).')',
                [...$bindings, ...$ids],
            );
        }

        return $written;
    }

    private function shiftRankExpression(): string
    {
        $cases = '';

        foreach (self::SHIFT_RANK as $shift => $rank) {
            $cases .= " WHEN '".$shift."' THEN ".$rank;
        }

        return 'CASE daily_reports.shift'.$cases.' ELSE 9 END';
    }

    /**
     * Nilai ship_name_key untuk sebuah nama, sebagai satu titik pemanggilan.
     */
    public function nameKey(?string $shipName): ?string
    {
        $key = ShipNameNormalizer::key($shipName);

        return $key === '' ? null : $key;
    }
}
