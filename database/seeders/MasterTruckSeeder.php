<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MasterTruckSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $trucks = [
            ['id' => 1, 'name' => 'Buffer Stock'],
            ['id' => 2, 'name' => 'Buffer Stufing'],
            ['id' => 3, 'name' => 'Buffer Stock'],
            ['id' => 4, 'name' => 'Buffer Stufing'],
        ];

        $data = array_map(fn (array $truck): array => array_merge($truck, [
            'plate_number' => null,
            'description' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]), $trucks);

        DB::table('master_trucks')->upsert($data, ['id'], ['name', 'plate_number', 'description', 'updated_at']);
    }
}
