<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Roster pemeliharaan kini memperkaya master_employees sebagai sumber tunggal.
 */
class MaintenanceEmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        // [npk, name, position]
        $employees = [
            ['2000.1.007', 'Sungkono', 'Kepala Seksi'],
            ['2008.1.058', 'Achmad Saiful Anwari', 'Kepala Regu'],
            ['2023.K.017', 'Usman', 'Mekanik'],
            ['2023.K.035', 'Arman', 'Mekanik'],
            ['2024.K.058', 'Muhammad Suaiban', 'Helper'],
            ['2024.K.059', 'Rahul', 'Helper'],
            [null, 'Usriadi', 'Helper'],
            [null, 'Fakhruddin', 'Helper'],
            ['2003.1.031', 'Akhmad Yani Siregar', 'Kepala Regu'],
            [null, 'Rizal Paselleri', 'Driver'],
            ['2023.K.019', 'Irfan Teguh Andriyanto', 'Rigger'],
            ['2006.1.049', 'Amiruddin', 'Checker'],
        ];

        foreach ($employees as $employee) {
            [$npk, $name, $position] = $employee;
            $master = DB::table('master_employees')->where('name', $name)->first();

            if ($npk !== null) {
                DB::table('master_employees')
                    ->where('npk', $npk)
                    ->when($master, fn ($query) => $query->where('id', '!=', $master->id))
                    ->update(['npk' => null, 'updated_at' => $now]);
            }

            if ($master) {
                DB::table('master_employees')->where('id', $master->id)->update([
                    'npk' => $npk,
                    'group_name' => 'Bengkel',
                    'position' => $position,
                    // Karyawan pemeliharaan selalu berdivisi 'pemeliharaan' saja
                    // (tidak ada lagi status gabungan 'both').
                    'division' => 'pemeliharaan',
                    'work_time' => 'Non Shift',
                    'status' => 'active',
                    'updated_at' => $now,
                ]);

                continue;
            }

            DB::table('master_employees')->updateOrInsert([
                'name' => $name,
            ], [
                'npk'        => $npk,
                'name'       => $name,
                'group_name' => 'Bengkel',
                'position'   => $position,
                'division'   => 'pemeliharaan',
                'work_time'  => 'Non Shift',
                'status'     => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
