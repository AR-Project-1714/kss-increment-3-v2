<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Data pemeliharaan kini memperkaya master_units sebagai sumber tunggal unit.
 *   - macro_category "truck"  -> Kelompok A (Trailer/Tronton/Dump Truck)
 *   - macro_category "heavy"  -> Kelompok B (Forklift/Excavator/Wheel Loader)
 */
class MaintenanceUnitSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        // [unit_code, brand, unit_number, macro_category]
        $units = [
            // Kelompok A - truck
            ['TRL', 'UD',   'KSS-01', 'truck'],
            ['TRL', 'UD',   'KSS-02', 'truck'],
            ['TRL', 'UD',   'KSS-03', 'truck'],
            ['TRL', 'UD',   'KSS-04', 'truck'],
            ['TRL', 'UD',   'KSS-05', 'truck'],
            ['TRL', 'UD',   'KSS-06', 'truck'],
            ['TRL', 'UD',   'KSS-08', 'truck'],
            ['TRL', 'UD',   'KSS-09', 'truck'],
            ['TRL', 'UD',   'KAD-63', 'truck'],
            ['TRT', 'HINO', 'KSS-01', 'truck'],
            ['DT',  'HINO', 'KSS-01', 'truck'],

            // Kelompok B - heavy
            ['FL',  'YALE',   'KSS-01',  'heavy'],
            ['FL',  'YALE',   'KSS-03',  'heavy'],
            ['FL',  'YALE',   'KSS-04',  'heavy'],
            ['FL',  'YALE',   'KSS-05',  'heavy'],
            ['FL',  'TOYOTA', 'KSS-08',  'heavy'],
            ['FL',  'TOYOTA', 'KSS-09',  'heavy'],
            ['FL',  'TOYOTA', 'KSS-11',  'heavy'],
            ['FL',  'TOYOTA', 'KSS-12',  'heavy'],
            ['FL',  'TOYOTA', 'KSS-13',  'heavy'],
            ['FL',  'TOYOTA', 'KSS-14',  'heavy'],
            ['FL',  'TOYOTA', 'KSS-15',  'heavy'],
            ['FL',  'TOYOTA', 'KSS-16',  'heavy'],
            ['FL',  'TOYOTA', 'KSS-17',  'heavy'],
            ['FL',  'YALE',   'KSS-70',  'heavy'],
            ['FL',  'YALE',   'KSS-71',  'heavy'],
            ['FL',  'YALE',   'KSS-72',  'heavy'],
            ['FL',  'YALE',   'KSS-73',  'heavy'],
            ['FL',  'YALE',   'KSS-74',  'heavy'],
            ['FL',  'YALE',   'KSS-75',  'heavy'],
            ['FL',  'YALE',   'KSS-100', 'heavy'],
            ['FL',  'YALE',   'KSS-101', 'heavy'],
            ['FL',  'YALE',   'KSS-102', 'heavy'],
            ['FL',  'YALE',   'KSS-103', 'heavy'],
            ['WL',  null,     'KSS-02',  'heavy'],
            ['WL',  null,     'KSS-03',  'heavy'],
            ['EXC', null,     'KSS-01',  'heavy'],
            ['EXC', null,     'KSS-02',  'heavy'],
        ];

        $data = array_map(fn (array $unit): array => [
            'unit_code'      => $unit[0],
            'brand'          => $unit[1],
            'unit_number'    => $unit[2],
            'name'           => $this->operationalName($unit[0], $unit[2]),
            'type'           => $this->typeFromUnitCode($unit[0]),
            'macro_category' => $unit[3],
            'status'         => 'active',
            'created_at'     => $now,
            'updated_at'     => $now,
        ], $units);

        DB::table('master_units')->upsert(
            $data,
            ['unit_code', 'unit_number'],
            ['brand', 'name', 'type', 'macro_category', 'status', 'updated_at']
        );
    }

    private function typeFromUnitCode(string $code): string
    {
        return match (strtoupper($code)) {
            'TRL' => 'Trailer',
            'TRT' => 'Tronton',
            'DT' => 'Dump Truck',
            'FL' => 'Forklift',
            'WL' => 'Wheel Loader',
            'EXC' => 'Excavator',
            default => $code,
        };
    }

    private function operationalName(string $code, string $unitNumber): string
    {
        return $this->typeFromUnitCode($code).' '.$unitNumber;
    }
}
