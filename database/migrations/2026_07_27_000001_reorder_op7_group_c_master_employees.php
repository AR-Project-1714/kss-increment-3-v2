<?php

use App\Models\MasterEmployee;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Menyelaraskan roster OP.7 Group C dengan data resmi petugas lapangan.
 *
 * Urutan baris OP.7 pada form mengikuti urutan `id` master_employees, sedangkan
 * MasterEmployeeSeeder melakukan upsert by NPK (memperbarui di tempat) sehingga
 * tidak pernah bisa mengubah urutan yang sudah terlanjur salah. Migrasi ini
 * menulis ulang anggota group tersebut agar id-nya urut sesuai daftar lapangan:
 *
 *   Bakri > Fikri > Agita > Fikirianur > Dwian > Ali > Sholaiman > Yaser >
 *   Dandi > Amar
 *
 * Sekaligus mengganti anggota usang 'Abd. Aziz' dengan 'Muhammad Fikri'.
 * Idempotent: bila urutan sudah benar, migrasi tidak melakukan apa pun.
 */
return new class extends Migration
{
    private const GROUP = 'OP.7 Group C';

    /** [npk, name] sesuai urutan baris FL.KSS-101 s/d FL.KSS-110. */
    private const ROSTER = [
        ['2025.K.026', 'Muhammad Bakri'],
        [null, 'Muhammad Fikri'],
        ['2025.K.011', 'Mochamad Agita'],
        ['2025.K.030', 'Muhammad Fikirianur'],
        ['2025.K.029', 'Muhammad Dwian Jaya Grahita'],
        ['2025.K.001', 'Ali Murdani'],
        ['2025.K.016', 'Sholaiman'],
        ['2025.K.019', 'Yaser Daniel'],
        ['2025.K.013', 'Muhammad Dandi'],
        ['2025.K.012', 'Muhammad Amar M'],
    ];

    public function up(): void
    {
        if (! Schema::hasTable('master_employees')) {
            return;
        }

        $existing = DB::table('master_employees')
            ->where('group_name', self::GROUP)
            ->orderBy('id')
            ->get();

        if ($existing->isEmpty()) {
            return;
        }

        $desiredNames = array_column(self::ROSTER, 1);

        // Sudah sesuai (mis. database lokal yang lebih dulu diperbaiki manual).
        if ($existing->pluck('name')->all() === $desiredNames) {
            return;
        }

        $byName = $existing->keyBy('name');
        $now = now();

        DB::transaction(function () use ($byName, $now) {
            // Simpan referensi absensi pemeliharaan (jika ada) supaya bisa
            // dipetakan ulang ke id baru setelah baris ditulis ulang.
            $attendanceRefs = $this->maintenanceReferences($byName->pluck('id')->all());

            DB::table('master_employees')->where('group_name', self::GROUP)->delete();

            foreach (self::ROSTER as [$npk, $name]) {
                $previous = $byName->get($name);

                DB::table('master_employees')->insert([
                    'npk' => $previous->npk ?? $npk,
                    'name' => $name,
                    'group_name' => self::GROUP,
                    'position' => $previous->position ?? 'Operator FL',
                    'division' => MasterEmployee::DIVISION_OPERATIONAL,
                    'work_time' => $previous->work_time ?? 'Shift',
                    'status' => $previous->status ?? 'active',
                    'created_at' => $previous->created_at ?? $now,
                    'updated_at' => $now,
                ]);
            }

            $this->restoreMaintenanceReferences($attendanceRefs);
        });

        foreach (MasterEmployee::MASTER_DATA_CACHE_KEYS as $key) {
            Cache::forget($key);
        }
    }

    public function down(): void
    {
        // Koreksi data satu arah: urutan lama tidak pernah benar menurut data
        // lapangan, jadi tidak ada yang perlu dipulihkan.
    }

    /** @return array<int, string> attendance id => nama karyawan */
    private function maintenanceReferences(array $employeeIds): array
    {
        if ($employeeIds === [] || ! Schema::hasTable('maintenance_attendances')) {
            return [];
        }

        if (! Schema::hasColumn('maintenance_attendances', 'master_employee_id')) {
            return [];
        }

        return DB::table('maintenance_attendances as a')
            ->join('master_employees as e', 'e.id', '=', 'a.master_employee_id')
            ->whereIn('a.master_employee_id', $employeeIds)
            ->pluck('e.name', 'a.id')
            ->all();
    }

    /** @param array<int, string> $references attendance id => nama karyawan */
    private function restoreMaintenanceReferences(array $references): void
    {
        if ($references === []) {
            return;
        }

        $newIds = DB::table('master_employees')
            ->where('group_name', self::GROUP)
            ->pluck('id', 'name');

        foreach ($references as $attendanceId => $name) {
            if ($newIds->has($name)) {
                DB::table('maintenance_attendances')
                    ->where('id', $attendanceId)
                    ->update(['master_employee_id' => $newIds->get($name)]);
            }
        }
    }
};
