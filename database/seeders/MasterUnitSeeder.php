<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MasterUnitSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $units = [
            ['id' => 1, 'name' => 'Trailler KSS-01', 'type' => 'Trailer'],
            ['id' => 2, 'name' => 'Trailler KSS-02', 'type' => 'Trailer'],
            ['id' => 3, 'name' => 'Trailler KSS-03', 'type' => 'Trailer'],
            ['id' => 4, 'name' => 'Trailler KSS-04', 'type' => 'Trailer'],
            ['id' => 5, 'name' => 'Trailler KSS-05', 'type' => 'Trailer'],
            ['id' => 6, 'name' => 'Trailler KSS-06', 'type' => 'Trailer'],
            ['id' => 7, 'name' => 'Trailler KSS-08', 'type' => 'Trailer'],
            ['id' => 8, 'name' => 'Trailer KSS-09', 'type' => 'Trailer'],
            ['id' => 9, 'name' => 'Tronton-KSS-01', 'type' => 'Tronton'],
            ['id' => 10, 'name' => 'Trailer KAD-63', 'type' => 'Trailer'],
            ['id' => 11, 'name' => 'DT KSS-01', 'type' => 'Dump Truck'],
            ['id' => 12, 'name' => 'Forklift KSS-01', 'type' => 'Forklift'],
            ['id' => 13, 'name' => 'Forklift KSS-03', 'type' => 'Forklift'],
            ['id' => 14, 'name' => 'Forklift KSS-04', 'type' => 'Forklift'],
            ['id' => 15, 'name' => 'Forklift KSS-05', 'type' => 'Forklift'],
            ['id' => 16, 'name' => 'Forklift KSS-08', 'type' => 'Forklift'],
            ['id' => 17, 'name' => 'Forklift KSS-09', 'type' => 'Forklift'],
            ['id' => 18, 'name' => 'Forklift KSS-11', 'type' => 'Forklift'],
            ['id' => 19, 'name' => 'Forklift KSS-12', 'type' => 'Forklift'],
            ['id' => 20, 'name' => 'Forklift KSS-13', 'type' => 'Forklift'],
            ['id' => 21, 'name' => 'Forklift KSS-14', 'type' => 'Forklift'],
            ['id' => 22, 'name' => 'Forklift KSS-15', 'type' => 'Forklift'],
            ['id' => 23, 'name' => 'Forklift KSS-16', 'type' => 'Forklift'],
            ['id' => 24, 'name' => 'Forklift KSS-17', 'type' => 'Forklift'],
            ['id' => 25, 'name' => 'Forklift KSS-70', 'type' => 'Forklift'],
            ['id' => 26, 'name' => 'Forklift KSS-71', 'type' => 'Forklift'],
            ['id' => 27, 'name' => 'Forklift KSS-72', 'type' => 'Forklift'],
            ['id' => 28, 'name' => 'Forklift KSS-75', 'type' => 'Forklift'],
            ['id' => 29, 'name' => 'Forklift KSS-73', 'type' => 'Forklift'],
            ['id' => 30, 'name' => 'Forklift KSS-74', 'type' => 'Forklift'],
            ['id' => 31, 'name' => 'Forklift KSS-100', 'type' => 'Forklift'],
            ['id' => 32, 'name' => 'Forklift KSS-101', 'type' => 'Forklift'],
            ['id' => 33, 'name' => 'Forklift KSS-102', 'type' => 'Forklift'],
            ['id' => 34, 'name' => 'Forklift KSS-103', 'type' => 'Forklift'],
            ['id' => 35, 'name' => 'WL.KSS-02', 'type' => 'Wheel Loader'],
            ['id' => 36, 'name' => 'WL.KSS-03', 'type' => 'Wheel Loader'],
            ['id' => 37, 'name' => 'Exc.KSS-01', 'type' => 'Excavator'],
            ['id' => 38, 'name' => 'Exc.KSS-02', 'type' => 'Excavator'],
            ['id' => 39, 'name' => 'Pick Up KSS-05', 'type' => 'Pick Up'],
            ['id' => 40, 'name' => 'Pick Up KSS-08', 'type' => 'Pick Up'],
            ['id' => 41, 'name' => 'Bus KSS-06', 'type' => 'Bus'],
            ['id' => 42, 'name' => 'Bus KSS-07', 'type' => 'Bus'],
            ['id' => 43, 'name' => 'Bus KSS-10', 'type' => 'Bus'],
        ];

        $data = array_map(function (array $unit) use ($now): array {
            $unitCode = $this->unitCodeFromType($unit['type']);
            $unitNumber = $this->unitNumberFromName($unit['name']);

            return array_merge($unit, [
                'unit_code' => $unitCode,
                'brand' => $this->brandFor($unitCode, $unitNumber),
                'unit_number' => $unitNumber,
                'macro_category' => $this->macroCategoryFromType($unit['type']),
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }, $units);

        DB::table('master_units')->upsert($data, ['id'], ['name', 'type', 'unit_code', 'brand', 'unit_number', 'macro_category', 'status', 'updated_at']);
    }

    private function unitCodeFromType(string $type): ?string
    {
        return match (strtolower(trim($type))) {
            'trailer', 'trailler' => 'TRL',
            'tronton' => 'TRT',
            'dump truck' => 'DT',
            'forklift' => 'FL',
            'wheel loader' => 'WL',
            'excavator' => 'EXC',
            'pick up' => 'PU',
            'bus' => 'BUS',
            default => null,
        };
    }

    private function macroCategoryFromType(string $type): ?string
    {
        return match (strtolower(trim($type))) {
            'trailer', 'trailler', 'tronton', 'dump truck' => 'truck',
            'forklift', 'wheel loader', 'excavator' => 'heavy',
            default => null,
        };
    }

    private function unitNumberFromName(string $name): ?string
    {
        if (! preg_match('/\b(KSS|KAD)[\s.-]*(\d+)\b/i', $name, $matches)) {
            return null;
        }

        return strtoupper($matches[1]).'-'.str_pad($matches[2], 2, '0', STR_PAD_LEFT);
    }

    private function brandFor(?string $unitCode, ?string $unitNumber): ?string
    {
        if ($unitCode === 'TRL') {
            return 'UD';
        }

        if (in_array($unitCode, ['TRT', 'DT'], true)) {
            return 'HINO';
        }

        if ($unitCode !== 'FL') {
            return null;
        }

        return in_array($unitNumber, [
            'KSS-01', 'KSS-03', 'KSS-04', 'KSS-05',
            'KSS-70', 'KSS-71', 'KSS-72', 'KSS-73', 'KSS-74', 'KSS-75',
            'KSS-100', 'KSS-101', 'KSS-102', 'KSS-103',
        ], true) ? 'YALE' : 'TOYOTA';
    }
}
