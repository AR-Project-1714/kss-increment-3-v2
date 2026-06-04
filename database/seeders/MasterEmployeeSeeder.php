<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MasterEmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $employees = [
            ['npk' => '2000.1.010', 'name' => 'Asmuni Syukur', 'group_name' => 'Group A'],
            ['npk' => '2003.1.030', 'name' => 'Zainuddin', 'group_name' => 'Group A'],
            ['npk' => '2006.1.050', 'name' => 'Mustafa', 'group_name' => 'Group A'],
            ['npk' => '2008.1.055', 'name' => 'Asri Sahibu', 'group_name' => 'Group A'],
            ['npk' => '2004.1.045', 'name' => 'Syamsuddin R', 'group_name' => 'Group A'],
            ['npk' => '2023.K.029', 'name' => 'Muhammad Zein Al-Fiqri', 'group_name' => 'Group A'],
            ['npk' => '2004.1.044', 'name' => 'Nasrayuddin', 'group_name' => 'Group A'],
            ['npk' => '2023.K.011', 'name' => 'Samsul Zainuddin', 'group_name' => 'Group A'],
            ['npk' => '2023.K.062', 'name' => 'Arlis', 'group_name' => 'Group A'],
            ['npk' => '2023.K.034', 'name' => 'Zulkifli A', 'group_name' => 'Group A'],
            ['npk' => '2023.K.020', 'name' => 'Ahmad Exca', 'group_name' => 'Group A'],
            ['npk' => '2023.K.018', 'name' => 'Ilham', 'group_name' => 'Group A'],
            ['npk' => '2023.K.041', 'name' => 'Musliady', 'group_name' => 'Group A'],
            ['npk' => '2023.K.043', 'name' => 'Rifky Rana Juliansyah', 'group_name' => 'Group A'],
            ['npk' => '2000.1.008', 'name' => 'Sabaruddin', 'group_name' => 'Group B'],
            ['npk' => '2002.1.028', 'name' => 'Nurul Huda', 'group_name' => 'Group B'],
            ['npk' => '2006.1.051', 'name' => 'Mulyadi', 'group_name' => 'Group B'],
            ['npk' => '2006.1.052', 'name' => 'Ruben Marbun', 'group_name' => 'Group B'],
            ['npk' => '2003.1.037', 'name' => 'Ahmad Bisri', 'group_name' => 'Group B'],
            ['npk' => '2004.1.043', 'name' => 'Ryman Olloan', 'group_name' => 'Group B'],
            ['npk' => '2005.1.047', 'name' => 'Ahmad Nur', 'group_name' => 'Group B'],
            ['npk' => '2023.K.036', 'name' => 'Agus Hendra Jaya', 'group_name' => 'Group B'],
            ['npk' => '2023.K.001', 'name' => 'Freddy Widiarto', 'group_name' => 'Group B'],
            ['npk' => '2023.K.008', 'name' => 'Agus Ibnu Thufail', 'group_name' => 'Group B'],
            ['npk' => '2023.K.021', 'name' => 'Hermanto Susanto', 'group_name' => 'Group B'],
            ['npk' => '2023.K.023', 'name' => 'Sayyed Riduansyah', 'group_name' => 'Group B'],
            ['npk' => '2023.K.034.B', 'name' => 'Andre Oktavianus Damanik', 'group_name' => 'Group B'],
            ['npk' => '2023.K.037', 'name' => 'Habibi', 'group_name' => 'Group B'],
            ['npk' => '2001.1.020', 'name' => 'Jawawi', 'group_name' => 'Group C'],
            ['npk' => '2002.1.029', 'name' => 'Muchtar', 'group_name' => 'Group C'],
            ['npk' => '2006.1.049', 'name' => 'Amiruddin', 'group_name' => 'Group C'],
            ['npk' => '2023.K.004', 'name' => 'Hamsyah', 'group_name' => 'Group C'],
            ['npk' => '2023.K.031', 'name' => 'Prasetya Perdana', 'group_name' => 'Group C'],
            ['npk' => '2023.K.030', 'name' => 'Mus Fajry', 'group_name' => 'Group C'],
            ['npk' => '2008.1.056', 'name' => 'Edi Irawan', 'group_name' => 'Group C'],
            ['npk' => '2023.K.025', 'name' => 'H. Usman Hasan', 'group_name' => 'Group C'],
            ['npk' => '2023.K.012', 'name' => 'Sudirman', 'group_name' => 'Group C'],
            ['npk' => '2023.K.013', 'name' => 'Usman DT', 'group_name' => 'Group C'],
            ['npk' => '2023.K.016', 'name' => 'Heri Bin Arsyad', 'group_name' => 'Group C'],
            ['npk' => '2023.K.015', 'name' => 'Djemain Dul Haid', 'group_name' => 'Group C'],
            ['npk' => '2023.K.036.C1', 'name' => 'Agil Akbar', 'group_name' => 'Group C'],
            ['npk' => '2023.K.036.C2', 'name' => 'Muhammad Ichsanul Yakin', 'group_name' => 'Group C'],
            ['npk' => '2001.1.021', 'name' => 'Sugianto', 'group_name' => 'Group D'],
            ['npk' => '2002.1.024', 'name' => 'Jhon Maradona Maylor', 'group_name' => 'Group D'],
            ['npk' => '2023.K.006', 'name' => 'Saddam Hassanuddin', 'group_name' => 'Group D'],
            ['npk' => '2008.1.057', 'name' => 'Yakop Bendon', 'group_name' => 'Group D'],
            ['npk' => '2005.1.048', 'name' => 'Wirawan', 'group_name' => 'Group D'],
            ['npk' => '2023.K.005', 'name' => 'Jefri Parianto', 'group_name' => 'Group D'],
            ['npk' => '2023.K.042', 'name' => 'Syamrisal', 'group_name' => 'Group D'],
            ['npk' => '2024.K.055', 'name' => 'Abd Rahim', 'group_name' => 'Group D'],
            ['npk' => '2024.K.056', 'name' => 'Sukirman', 'group_name' => 'Group D'],
            ['npk' => '2023.K.024', 'name' => 'Dony Amping', 'group_name' => 'Group D'],
            ['npk' => '2023.K.019', 'name' => 'Irfan Teguh A', 'group_name' => 'Group D'],
            ['npk' => '2023.K.017', 'name' => 'Supriadi Budianto', 'group_name' => 'Group D'],
            ['npk' => '2023.K.040', 'name' => 'Muhammad Reza Al Habsyi', 'group_name' => 'Group D'],
            ['npk' => '2023.K.038', 'name' => 'Muhammad Fadli', 'group_name' => 'Group D'],
        ];

        $data = array_map(fn (array $employee): array => array_merge($employee, [
            'position' => null,
            'division' => 'operasional',
            'work_time' => 'Shift',
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]), $employees);

        DB::table('master_employees')->upsert($data, ['npk'], ['name', 'group_name', 'position', 'division', 'work_time', 'status', 'updated_at']);
    }
}
