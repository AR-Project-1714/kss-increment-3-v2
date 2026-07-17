<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Master unit KSS: kendaraan jalan (Truck/Bus) + alat berat (Heavy).
 * Sumber: daftar resmi armada. Baris: [id, type, unit_number, plate_number, brand, year].
 * Diurutkan per jenis menurut nomor urut (id naik = grup per tipe berurutan).
 *
 *  - unit_code   : singkatan tipe (TRL/TRT/DT/BUS/PU/FL/EXC/WL), diturunkan dari type.
 *  - unit_number : kode urut aset / nomor lambung ("Kode" di admin): TRL-01,
 *                  DT-01, KSS-02, FL-01. Diselaraskan dengan daftar armada resmi.
 *  - plate_number: nomor polisi format standar "KT 8512 DE"; null untuk alat berat.
 *  - name        : sama dengan type (generik, sesuai daftar).
 */
class MasterUnitSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $data = array_map(fn (array $unit): array => [
            'id' => $unit[0],
            // Nama = gabungan tipe + nomor urut (mis. "Minibus KSS-02", "Bus KSS-06").
            'name' => trim($unit[1].' '.$unit[2]),
            'type' => $unit[1],
            'unit_code' => $this->codeFromType($unit[1]),
            'unit_number' => $unit[2],
            'plate_number' => $unit[3],
            'brand' => $unit[4],
            'macro_category' => $this->macroFromType($unit[1]),
            'year' => $unit[5],
            'status' => 'active',
            // Kategori baru: apakah unit tampil di seksi Cek Unit laporan
            // operasional. Avanza (Minibus) dikecualikan.
            'in_operational_check' => $this->inOperationalCheck($unit[1]),
            'created_at' => $now,
            'updated_at' => $now,
        ], $this->units());

        // Lambung (unit_number) digeser antar-id pada rilis ini, mis. KSS-02
        // pindah ke id 49 dan KSS-03 dipakai ulang di id 14. Karena ada unique
        // index (unit_code, unit_number), bebaskan dulu SEMUA lambung ke nilai
        // sementara unik per id agar upsert tidak bentrok — termasuk dengan baris
        // lama (mis. KSS-03 lama) yang kini lambungnya dipakai unit aktif lain.
        // CONCAT() hanya ada di MySQL/MariaDB; SQLite (dipakai test) & Postgres memakai '||'.
        $tempNumberExpr = in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)
            ? DB::raw("CONCAT('TMP-', id)")
            : DB::raw("'TMP-' || id");

        DB::table('master_units')
            ->update(['unit_number' => $tempNumberExpr, 'updated_at' => $now]);

        DB::table('master_units')->upsert(
            $data,
            ['id'],
            ['name', 'type', 'unit_code', 'unit_number', 'plate_number', 'brand', 'macro_category', 'year', 'status', 'in_operational_check', 'updated_at']
        );

        // Baris di luar daftar armada resmi (lambung lama yang sudah dipensiunkan
        // atau dipakai ulang oleh unit lain) masih bertanda TMP- — non-aktifkan
        // dan bersihkan kolom identitasnya agar tidak ada plat/lambung kembar di
        // pencarian admin. Baris tetap disimpan demi integritas FK (mis.
        // unit_check_logs historis yang menunjuk ke id tersebut).
        DB::table('master_units')
            ->where('unit_number', 'like', 'TMP-%')
            ->update([
                'status' => 'inactive',
                'name' => 'Unit nonaktif',
                'unit_number' => null,
                'plate_number' => null,
                'brand' => null,
                'year' => null,
                'in_operational_check' => false,
                'updated_at' => $now,
            ]);
    }

    /** [id, type, unit_number, plate_number, brand, year] — urut per jenis. */
    private function units(): array
    {
        return [
            // ── Trailer (Truck) ──────────────────────────────────────────────
            [1, 'Trailer', 'TRL-01', 'KT 8512 DE', 'NISSAN CWM 330', 2010],
            [2, 'Trailer', 'TRL-02', 'KT 8570 DE', 'NISSAN CWM 330', 2010],
            [3, 'Trailer', 'TRL-03', 'KT 8663 DE', 'NISSAN CWA 260', 2010],
            [4, 'Trailer', 'TRL-04', 'KT 8620 DG', 'EURO 3 GWE 280', 2016],
            [5, 'Trailer', 'TRL-05', 'KT 8723 DG', 'EURO 3 GWE 280', 2019],
            [6, 'Trailer', 'TRL-06', 'KT 8735 DG', 'EURO 3 GWE 280', 2019],
            [7, 'Trailer', 'TRL-07', 'KT 8928 DG', 'EURO 5 GKE 280', 2023],
            [8, 'Trailer', 'TRL-08', 'KT 8615 DH', 'EURO 5 GKE 280', 2024],
            [9, 'Trailer', 'TRL-09', 'KT 8725 DH', 'EURO 5 GKE 280', 2025],
            [10, 'Trailer', 'TRL-10', 'KT 8850 DG', 'EURO 5 GWE 280', 2026],

            // ── Tronton (Truck) ──────────────────────────────────────────────
            [11, 'Tronton', 'TRT-01', 'KT 8840 DE', 'NISSAN CWA 53', 2012],

            // ── Dump Truck (Truck) ───────────────────────────────────────────
            [12, 'Dump Truck', 'DT-01', 'KT 8586 DG', 'ISUZU NKR71 HARIMAU', 2016],

            // ── Minibus AVANZA (kategori Bus) — sarana jemputan, dikecualikan
            //    dari cek unit operasi (scopeOrderedForReport buang type Minibus).
            //    id 13/15 = unit AVANZA 2020 lama (dulu lambung KSS-01/KSS-03),
            //    dipetakan ke lambung benar KSS-02/KSS-04 agar riwayat cek unit
            //    (unit_check_logs) tetap menempel ke unit fisik yang sama. ─────
            [13, 'Minibus', 'KSS-02', 'KT 1538 QM', 'TOYOTA AVANZA', 2020],
            [14, 'Minibus', 'KSS-03', 'KT 1421 QE', 'TOYOTA AVANZA', 2024],
            [15, 'Minibus', 'KSS-04', 'KT 1537 QM', 'TOYOTA AVANZA', 2020],
            [18, 'Minibus', 'KSS-09', 'KT 1886 DJ', 'TOYOTA AVANZA', 2011],

            // ── ISUZU (kategori Bus) — masuk cek unit operasi, jadi type 'Bus' ─
            [16, 'Bus', 'KSS-06', 'KT 7210 DE', 'ISUZU NHR 55', 2011],
            [17, 'Bus', 'KSS-07', 'KT 7353 DF', 'ISUZU NLR 55', 2019],
            [19, 'Bus', 'KSS-10', 'KT 7544 DF', 'ISUZU NLR 55', 2025],

            // ── Pickup (Truck) — lambung KSS sesuai daftar armada ─────────────
            [20, 'Pickup', 'KSS-08', 'KT 8058 DK', 'TOYOTA HILUX', 2020],
            [21, 'Pickup', 'KSS-11', 'KT 8997 QB', 'DAIHATSU PICKUP', 2026],
            [22, 'Pickup', 'KSS-12', 'KT 8501 QC', 'DAIHATSU PICKUP', 2026],

            // ── Forklift (Heavy) ─────────────────────────────────────────────
            [23, 'Forklift', 'FL-01', null, 'YALE', 2007],
            [24, 'Forklift', 'FL-03', null, 'YALE', 2009],
            [25, 'Forklift', 'FL-04', null, 'YALE', 2011],
            [26, 'Forklift', 'FL-05', null, 'YALE', 2011],
            [27, 'Forklift', 'FL-08', null, 'KOMATSU', 2016],
            [28, 'Forklift', 'FL-09', null, 'KOMATSU', 2016],
            [29, 'Forklift', 'FL-11', null, 'KOMATSU', 2016],
            [30, 'Forklift', 'FL-12', null, 'KOMATSU', 2016],
            [31, 'Forklift', 'FL-13', null, 'YALE', 2023],
            [32, 'Forklift', 'FL-14', null, 'YALE', 2023],
            [33, 'Forklift', 'FL-15', null, 'KOMATSU', 2025],
            [34, 'Forklift', 'FL-16', null, 'KOMATSU', 2025],
            [35, 'Forklift', 'FL-17', null, 'KOMATSU', 2025],
            [36, 'Forklift', 'FL-18', null, 'TOYOTA', 2022],
            [37, 'Forklift', 'FL-19', null, 'TOYOTA', 2022],
            [38, 'Forklift', 'FL-20', null, 'TOYOTA', 2022],
            [39, 'Forklift', 'FL-73', null, 'YALE', 2023],
            [40, 'Forklift', 'FL-74', null, 'YALE', 2023],
            [41, 'Forklift', 'FL-75', null, 'YALE', 2023],
            [42, 'Forklift', 'FL-100', null, 'TOYOTA', 2025],
            [43, 'Forklift', 'FL-101', null, 'TOYOTA', 2025],
            [44, 'Forklift', 'FL-102', null, 'TOYOTA', 2025],
            [45, 'Forklift', 'FL-103', null, 'TOYOTA', 2025],

            // ── Excavator (Heavy) ────────────────────────────────────────────
            [46, 'Excavator', 'EXCA-01', null, 'KOBELCO', 2016],
            [47, 'Excavator', 'EXCA-02', null, 'KOBELCO', 2022],

            // ── Wheel Loader (Heavy) ─────────────────────────────────────────
            [48, 'Wheel Loader', 'WL-03', null, 'LONKING', 2022],
        ];
    }

    private function codeFromType(string $type): string
    {
        return match ($type) {
            'Trailer' => 'TRL',
            'Tronton' => 'TRT',
            'Dump Truck' => 'DT',
            'Minibus', 'Bus' => 'BUS',
            'Pickup' => 'PU',
            'Forklift' => 'FL',
            'Excavator' => 'EXC',
            'Wheel Loader' => 'WL',
            default => '',
        };
    }

    private function macroFromType(string $type): string
    {
        return match ($type) {
            'Minibus', 'Bus' => 'bus',
            'Forklift', 'Excavator', 'Wheel Loader' => 'heavy',
            default => 'truck',
        };
    }

    /** Apakah tipe ini ikut seksi Cek Unit laporan operasional (Avanza tidak). */
    private function inOperationalCheck(string $type): bool
    {
        return in_array($type, \App\Models\MasterUnit::OPERATIONAL_CHECK_TYPES, true);
    }
}
