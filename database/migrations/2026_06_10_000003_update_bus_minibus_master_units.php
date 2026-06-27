<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('master_units')->count() === 0) {
            return;
        }

        foreach (['KSS-01', 'KSS-03'] as $unitNumber) {
            $this->updateExistingBusUnit($unitNumber, ['status' => 'inactive']);
        }

        foreach (['KSS-02', 'KSS-04', 'KSS-05', 'KSS-09'] as $unitNumber) {
            $this->upsertBusUnit('Minibus', $unitNumber);
        }

        foreach (['KSS-06', 'KSS-07', 'KSS-10'] as $unitNumber) {
            $this->upsertBusUnit('Bus', $unitNumber);
        }
    }

    public function down(): void
    {
        if (DB::table('master_units')->count() === 0) {
            return;
        }

        foreach (['KSS-01', 'KSS-02', 'KSS-03', 'KSS-06'] as $unitNumber) {
            $this->upsertBusUnit('Minibus', $unitNumber);
        }

        foreach (['KSS-04', 'KSS-05', 'KSS-07'] as $unitNumber) {
            $this->upsertBusUnit('Bus', $unitNumber);
        }

        foreach (['KSS-09', 'KSS-10'] as $unitNumber) {
            $this->updateExistingBusUnit($unitNumber, ['status' => 'inactive']);
        }
    }

    private function upsertBusUnit(string $type, string $unitNumber): void
    {
        $payload = $this->payload($type, $unitNumber);
        $existing = DB::table('master_units')
            ->where('unit_code', 'BUS')
            ->where('unit_number', $unitNumber)
            ->first();

        if ($existing) {
            DB::table('master_units')->where('id', $existing->id)->update($payload);
            return;
        }

        DB::table('master_units')->insert(array_merge($payload, [
            'created_at' => now(),
        ]));
    }

    private function updateExistingBusUnit(string $unitNumber, array $payload): void
    {
        $payload['updated_at'] = now();

        DB::table('master_units')
            ->where('unit_code', 'BUS')
            ->where('unit_number', $unitNumber)
            ->update($payload);
    }

    private function payload(string $type, string $unitNumber): array
    {
        $payload = [
            'name' => $type.' '.$unitNumber,
            'type' => $type,
            'status' => 'active',
            'updated_at' => now(),
        ];

        if (Schema::hasColumn('master_units', 'unit_code')) {
            $payload['unit_code'] = 'BUS';
        }

        if (Schema::hasColumn('master_units', 'unit_number')) {
            $payload['unit_number'] = $unitNumber;
        }

        if (Schema::hasColumn('master_units', 'macro_category')) {
            $payload['macro_category'] = 'bus';
        }

        return $payload;
    }
};
