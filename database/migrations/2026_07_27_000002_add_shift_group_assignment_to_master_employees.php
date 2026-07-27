<?php

use App\Models\MasterEmployee;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Menyelaraskan daftar "Karyawan Shift Yang Bertugas" dengan data resmi
 * petugas lapangan (Regu A-D, 13 orang per regu).
 *
 * Selisihnya: tiap regu punya satu personil pendamping tetap di posisi 3 yang
 * asalnya BUKAN dari regu itu — tiga dari Relief dan satu dari Bengkel:
 *
 *   Regu A  Rahardian Efendi   (Relief 1)
 *   Regu B  Hermanto Susanto   (Relief 1)
 *   Regu C  Awaluddin Fitroh   (Relief 2)
 *   Regu D  Supriadi Budianto  (Bengkel, divisi pemeliharaan)
 *
 * Mereka tidak dipindahkan, karena Relief punya tabnya sendiri dan Supriadi
 * Budianto masih terpakai di absensi pemeliharaan. Sebagai gantinya ditambah
 * kolom `shift_group_name`: asal karyawan tetap, tapi ia ikut tampil di daftar
 * shift regu tersebut. Khusus Supriadi Budianto divisinya menjadi 'both' agar
 * terbaca operasional TANPA keluar dari modul pemeliharaan.
 *
 * Posisi 3 tercapai dengan sendirinya: form menaruh KARU dan Wakil KARU di dua
 * baris pertama, sisanya urut id — dan id Relief/Bengkel (21-25) lebih kecil
 * daripada seluruh anggota Group A-D (28+).
 *
 * Sekalian memperbaiki urutan Regu B: Habibi naik ke atas Agus Hendra Jaya.
 */
return new class extends Migration
{
    private const SHIFT_ASSIGNMENTS = [
        'Rahardian Efendi' => 'Group A',
        'Hermanto Susanto' => 'Group B',
        'Awaluddin Fitroh' => 'Group C',
        'Supriadi Budianto' => 'Group D',
    ];

    /** Urutan resmi Regu B: [npk, nama, jabatan]. */
    private const GROUP_B_ROSTER = [
        ['2002.1.028', 'Nurul Huda', 'Kepala Regu ( KARU )'],
        ['2004.1.043', 'Ryman Oloan Manurung', 'Wakil Kepala Regu'],
        ['2006.1.051', 'Mulyadi', 'Checker'],
        ['2006.1.052', 'Ruben Marbun', 'Checker'],
        ['2023.K.026', 'Muh. Agung Hidayat Sinaga', 'Operator FL'],
        ['2023.K.032', 'Ronaldes Romba Pratama', 'Operator FL'],
        ['2005.1.047', 'Ahmad Nur', 'Operator FL'],
        ['2023.K.001', 'Freddy Widiarto', 'Driver'],
        ['2025.1.072', 'Agus Ibnu Thufail', 'Driver'],
        ['2023.K.037', 'Habibi', 'Operator FL'],
        [null, 'Agus Hendra Jaya', 'Driver'],
        [null, 'Andre Oktavianus Damanik', 'Operator FL'],
    ];

    public function up(): void
    {
        if (! Schema::hasTable('master_employees')) {
            return;
        }

        if (! Schema::hasColumn('master_employees', 'shift_group_name')) {
            Schema::table('master_employees', function (Blueprint $table): void {
                $table->string('shift_group_name')->nullable()->after('group_name');
            });
        }

        $now = now();

        foreach (self::SHIFT_ASSIGNMENTS as $name => $group) {
            DB::table('master_employees')
                ->where('name', $name)
                ->update(['shift_group_name' => $group, 'updated_at' => $now]);
        }

        // Dibaca modul operasional sekaligus pemeliharaan.
        DB::table('master_employees')
            ->where('name', 'Supriadi Budianto')
            ->update(['division' => MasterEmployee::DIVISION_BOTH, 'updated_at' => $now]);

        $this->reorderGroupB($now);

        foreach (MasterEmployee::MASTER_DATA_CACHE_KEYS as $key) {
            Cache::forget($key);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('master_employees')) {
            return;
        }

        DB::table('master_employees')
            ->where('name', 'Supriadi Budianto')
            ->update(['division' => MasterEmployee::DIVISION_MAINTENANCE, 'updated_at' => now()]);

        if (Schema::hasColumn('master_employees', 'shift_group_name')) {
            Schema::table('master_employees', function (Blueprint $table): void {
                $table->dropColumn('shift_group_name');
            });
        }

        foreach (MasterEmployee::MASTER_DATA_CACHE_KEYS as $key) {
            Cache::forget($key);
        }
    }

    /**
     * Urutan baris mengikuti urutan id, jadi memindahkan Habibi hanya bisa
     * dilakukan dengan menulis ulang anggota regu. Idempotent: berhenti kalau
     * urutannya sudah benar.
     */
    private function reorderGroupB($now): void
    {
        $existing = DB::table('master_employees')
            ->where('group_name', 'Group B')
            ->orderBy('id')
            ->get();

        if ($existing->isEmpty()) {
            return;
        }

        $desiredNames = array_column(self::GROUP_B_ROSTER, 1);

        if ($existing->pluck('name')->all() === $desiredNames) {
            return;
        }

        $byName = $existing->keyBy('name');

        // Anggota di luar daftar resmi dipertahankan (bisa jadi tambahan admin
        // lewat panel Data Master), ditempatkan setelah roster resmi. Tidak ada
        // penghapusan personil di Regu B — migrasi ini murni mengurutkan ulang.
        $extras = $existing
            ->reject(fn ($row) => in_array($row->name, $desiredNames, true))
            ->values();

        DB::transaction(function () use ($byName, $extras, $now): void {
            $attendanceRefs = $this->maintenanceReferences($byName->pluck('id')->all());

            DB::table('master_employees')->where('group_name', 'Group B')->delete();

            foreach (self::GROUP_B_ROSTER as [$npk, $name, $position]) {
                $previous = $byName->get($name);

                DB::table('master_employees')->insert([
                    'npk' => $previous->npk ?? $npk,
                    'name' => $name,
                    'group_name' => 'Group B',
                    'shift_group_name' => $previous->shift_group_name ?? null,
                    'position' => $previous->position ?? $position,
                    'division' => $previous->division ?? MasterEmployee::DIVISION_OPERATIONAL,
                    'work_time' => $previous->work_time ?? 'Shift',
                    'status' => $previous->status ?? 'active',
                    'created_at' => $previous->created_at ?? $now,
                    'updated_at' => $now,
                ]);
            }

            foreach ($extras as $row) {
                DB::table('master_employees')->insert([
                    'npk' => $row->npk,
                    'name' => $row->name,
                    'group_name' => 'Group B',
                    'shift_group_name' => $row->shift_group_name ?? null,
                    'position' => $row->position,
                    'division' => $row->division,
                    'work_time' => $row->work_time,
                    'status' => $row->status,
                    'created_at' => $row->created_at ?? $now,
                    'updated_at' => $now,
                ]);
            }

            $this->restoreMaintenanceReferences($attendanceRefs);
        });
    }

    /**
     * Absensi pemeliharaan memakai FK nullOnDelete, jadi tautannya putus diam-
     * diam saat baris karyawan ditulis ulang. Referensi disimpan lalu dipulihkan
     * ke id baru berdasarkan nama.
     *
     * @return array<int, string> attendance id => nama karyawan
     */
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
            ->where('group_name', 'Group B')
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
