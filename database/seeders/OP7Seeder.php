<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OP7Seeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $groupsData = [
            'A' => [
                'Aziz Bukhari S',
                'Juprianto',
                'Ahmad Faitsal',
                'Ashar',
                'Edi Ansyah',
                'Artanto Adhiguna',
                'Kiki Arfin Saputra',
                'Firman',
                'Aji Faisal',
                'Edi Sutomo',
            ],
            'B' => [
                'Abdul Salim',
                'Julyo Gabriel',
                'Yonas',
                'Wahyu',
                'Muchlas Abduh',
                'Junaidi',
                'M.Azhar Fadly Sinaga',
                'Wahyudi',
                'Imam Buchori',
                'Sutrisno Sikombong',
            ],
            'C' => [
                'Muhammad Bakri',
                'Muhammad Fikri',
                'Muhammad Agita',
                'Muhammad Dwian Jaya.G',
                'Muhammad Fikrianur',
                'Ali Murdani',
                'Sholaiman',
                'Yasser Daniel',
                'Muhammad Dandi',
                "M.Amar Ma'ruf.M",
            ],
            'D' => [
                'Muhammad Ridwan',
                'Samsir',
                'Yodi Fatir.AN',
                'Randi Satrio.W',
                'Rusbandi',
                'Muhammad Rizki',
                'Salama',
                'Boyska',
                'Herwin Saputra',
                'Rustam',
                'Nurdin',
            ],
        ];

        $employees = [];

        foreach ($groupsData as $groupCode => $names) {
            foreach ($names as $index => $name) {
                $employees[] = [
                    'npk' => sprintf('2025.7.%s%03d', $groupCode, $index + 1),
                    'name' => $name,
                    'group_name' => 'OP.7 Group '.$groupCode,
                    'position' => 'Operator OP.7',
                    'division' => 'operasional',
                    'work_time' => 'Shift',
                    'status' => 'active',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        DB::table('master_employees')->upsert(
            $employees,
            ['npk'],
            ['name', 'group_name', 'position', 'division', 'work_time', 'status', 'updated_at']
        );
    }
}
