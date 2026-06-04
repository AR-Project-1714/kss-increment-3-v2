<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Sumber kebenaran roster Shift Regular (Group A-D) karyawan KSS.
 *
 * Dijalankan setelah MasterEmployeeSeeder/MaintenanceEmployeeSeeder untuk
 * menegaskan nama, group, dan jabatan sesuai roster resmi. Idempotent:
 * - karyawan dengan NPK dicocokkan via NPK, selain itu via nama.
 * - karyawan Group A-D yang tidak ada di roster dikeluarkan dari group
 *   (group_name = null / "Kantor"), data tetap dipertahankan.
 */
class ShiftRegularRosterSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        // group => [ [npk|null, name, position|null], ... ]
        $roster = [
            'Group A' => [
                ['2002.1.024', 'Jhon Maradona Mailoor', 'Kepala Regu ( KARU )'],
                ['2003.1.030', 'Zainuddin', 'Wakil Kepala Regu'],
                ['2006.1.050', 'Mustafa', 'Checker'],
                ['2008.1.055', 'Asri Sahibu', 'Checker'],
                ['2023.K.011', 'Samsul Zainuddin', 'Driver'],
                ['2023.K.029', 'Muhammad Zein Al-Fiqri', 'Operator FL'],
                ['2023.K.034', 'Zulkifli A', 'Operator Exca/ WL'],
                ['2023.K.041', 'Musliady', 'Operator FL'],
                ['2023.K.043', 'Rifky Rana Juliansyah', 'Operator FL'],
                ['2023.K.062', 'Arlis', 'Driver'],
                [null, 'Jenri Tangruru', null],
                [null, 'Boyska Albian', null],
            ],
            'Group B' => [
                ['2002.1.028', 'Nurul Huda', 'Kepala Regu ( KARU )'],
                ['2004.1.043', 'Ryman Oloan Manurung', 'Wakil Kepala Regu'],
                ['2005.1.047', 'Ahmad Nur', 'Operator FL'],
                ['2006.1.051', 'Mulyadi', 'Checker'],
                ['2006.1.052', 'Ruben Marbun', 'Checker'],
                ['2023.K.001', 'Freddy Widiarto', 'Driver'],
                ['2023.K.008', 'Agus Ibnu Thufail', 'Driver'],
                [null, 'Muh. Agung Hidayat Sinaga', null],
                [null, 'Ronaldes Romba Pratama', null],
                ['2023.K.034.B', 'Andre Oktavianus Damanik', 'Operator FL'],
                ['2023.K.036', 'Agus Hendra Jaya', 'Driver'],
                ['2023.K.037', 'Habibi', 'Operator FL'],
            ],
            'Group C' => [
                ['2001.1.020', 'Jawawi', 'Kepala Regu ( KARU )'],
                ['2003.1.037', 'Ahmad Bisri', 'Wakil Kepala Regu'],
                ['2008.1.056', 'Edi Irawan', 'Operator FL'],
                ['2023.K.004', 'Hamsyah', 'Checker'],
                ['2023.K.012', 'Sudirman', 'Driver'],
                ['2023.K.013', 'Usman DT', 'Driver'],
                ['2023.K.025', 'H. Usman Hasan', 'Driver'],
                [null, 'M. Ikram Jaya Pratama', null],
                ['2023.K.030', 'Mus Fajry', 'Operator FL'],
                ['2023.K.031', 'Prasetya Perdana', 'Operator FL'],
                ['2023.K.036.C1', 'Agil Akbar', 'Operator FL'],
                ['2023.K.036.C2', 'Muhammad Ichsanul Yakin', 'Operator FL'],
            ],
            'Group D' => [
                ['2001.1.021', 'Sugianto', 'Kepala Regu ( KARU )'],
                ['2004.1.045', 'Syamsuddin R', 'Wakil Kepala Regu'],
                ['2005.1.048', 'Wirawan', 'Operator FL'],
                ['2008.1.057', 'Yakop Bendon', 'Checker'],
                ['2023.K.005', 'Jefri Parianto', 'Operator FL'],
                ['2023.K.006', 'Saddam Hassanuddin', 'Checker'],
                ['2023.K.024', 'Dony Amping', 'Driver'],
                ['2023.K.038', 'Muhammad Fadli', 'Operator FL'],
                ['2023.K.040', 'Muhammad Reza Al Habsyi', 'Operator FL'],
                ['2023.K.042', 'Syamrisal', 'Operator FL'],
                ['2024.K.055', 'Abd Rahim', 'Driver'],
                [null, 'Abd. Aziz', null],
            ],
        ];

        // 1) Bersihkan baris duplikat tanpa NPK (sisakan id terkecil per nama).
        foreach (['Irfan Teguh A', 'Supriadi Budianto'] as $dupName) {
            $ids = DB::table('master_employees')
                ->where('name', $dupName)
                ->orderBy('id')
                ->pluck('id');

            if ($ids->count() > 1) {
                DB::table('master_employees')->whereIn('id', $ids->slice(1)->all())->delete();
            }
        }

        // 2) Terapkan roster: cocokkan via NPK, jika tidak ada via nama, jika
        //    tetap tidak ada maka insert baru.
        $rosterNames = [];

        foreach ($roster as $group => $members) {
            foreach ($members as [$npk, $name, $position]) {
                $rosterNames[] = $name;

                $attributes = [
                    'name' => $name,
                    'group_name' => $group,
                    'position' => $position,
                    'division' => 'operasional',
                    'work_time' => 'Shift',
                    'status' => 'active',
                    'updated_at' => $now,
                ];

                $row = null;
                if ($npk !== null) {
                    $row = DB::table('master_employees')->where('npk', $npk)->first();
                }
                if (! $row) {
                    $row = DB::table('master_employees')->where('name', $name)->first();
                }

                if ($row) {
                    DB::table('master_employees')->where('id', $row->id)->update($attributes + [
                        'npk' => $npk,
                    ]);
                } else {
                    DB::table('master_employees')->insert($attributes + [
                        'npk' => $npk,
                        'created_at' => $now,
                    ]);
                }
            }
        }

        // 3) Karyawan operasional di Group A-D yang tidak ada di roster
        //    dikeluarkan dari group (data dipertahankan, jadi "Kantor").
        //    Dibatasi ke divisi operasional agar tidak mengganggu data
        //    pemeliharaan/Bengkel.
        DB::table('master_employees')
            ->where('division', 'operasional')
            ->whereIn('group_name', ['Group A', 'Group B', 'Group C', 'Group D'])
            ->whereNotIn('name', $rosterNames)
            ->update(['group_name' => null, 'updated_at' => $now]);
    }
}
