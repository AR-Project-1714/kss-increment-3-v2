<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $employees = [
            ['npk' => '2000.1.007', 'name' => 'Sungkono', 'position' => 'Kepala Seksi'],
            ['npk' => '2008.1.058', 'name' => 'Achmad Saiful Anwari', 'position' => 'Kepala Regu'],
            ['npk' => '2023.K.017', 'name' => 'Usman', 'position' => 'Mekanik'],
            ['npk' => '2023.K.035', 'name' => 'Arman', 'position' => 'Mekanik'],
            ['npk' => '2024.K.058', 'name' => 'Muhammad Suaiban', 'position' => 'Helper'],
            ['npk' => '2024.K.059', 'name' => 'Rahul', 'position' => 'Helper'],
            ['npk' => null, 'name' => 'Usriadi', 'position' => 'Helper'],
            ['npk' => null, 'name' => 'Fakhruddin', 'position' => 'Helper'],
            ['npk' => '2003.1.031', 'name' => 'Akhmad Yani Siregar', 'position' => 'Kepala Regu'],
            ['npk' => null, 'name' => 'Rizal Paselleri', 'position' => 'Driver'],
            ['npk' => '2023.K.019', 'name' => 'Irfan Teguh Andriyanto', 'position' => 'Rigger'],
            ['npk' => '2006.1.049', 'name' => 'Amiruddin', 'position' => 'Checker'],
        ];

        foreach ($employees as $employee) {
            $master = DB::table('master_employees')->where('name', $employee['name'])->first();

            if ($employee['npk'] !== null) {
                DB::table('master_employees')
                    ->where('npk', $employee['npk'])
                    ->when($master, fn ($query) => $query->where('id', '!=', $master->id))
                    ->update(['npk' => null, 'updated_at' => $now]);
            }

            if ($master) {
                DB::table('master_employees')->where('id', $master->id)->update([
                    'npk' => $employee['npk'],
                    'position' => $employee['position'],
                    'division' => $this->mergeDivision((string) ($master->division ?? 'operasional'), 'pemeliharaan'),
                    'work_time' => 'Non Shift',
                    'status' => 'active',
                    'updated_at' => $now,
                ]);

                continue;
            }

            DB::table('master_employees')->insert([
                'npk' => $employee['npk'],
                'name' => $employee['name'],
                'group_name' => null,
                'position' => $employee['position'],
                'division' => 'pemeliharaan',
                'work_time' => 'Non Shift',
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('master_employees')
            ->whereIn('name', [
                'Sungkono',
                'Achmad Saiful Anwari',
                'Usman',
                'Arman',
                'Muhammad Suaiban',
                'Rahul',
                'Usriadi',
                'Fakhruddin',
                'Akhmad Yani Siregar',
                'Rizal Paselleri',
                'Irfan Teguh Andriyanto',
                'Amiruddin',
            ])
            ->update(['npk' => null, 'updated_at' => now()]);
    }

    private function mergeDivision(string $current, string $incoming): string
    {
        if ($current === $incoming || $current === 'both') {
            return $current;
        }

        if ($incoming === 'both' || $current === '') {
            return $incoming;
        }

        return 'both';
    }
};
