<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('master_units', function (Blueprint $table): void {
            if (! Schema::hasColumn('master_units', 'unit_code')) {
                $table->string('unit_code')->nullable()->after('type');
            }
            if (! Schema::hasColumn('master_units', 'brand')) {
                $table->string('brand')->nullable()->after('unit_code');
            }
            if (! Schema::hasColumn('master_units', 'unit_number')) {
                $table->string('unit_number')->nullable()->after('brand');
            }
            if (! Schema::hasColumn('master_units', 'macro_category')) {
                $table->string('macro_category')->nullable()->after('unit_number');
            }
        });

        Schema::table('master_units', function (Blueprint $table): void {
            $table->index(['macro_category', 'status'], 'master_units_macro_status_index');
            $table->unique(['unit_code', 'unit_number'], 'master_units_code_number_unique');
        });

        if (Schema::hasTable('maintenance_work_items') && ! Schema::hasColumn('maintenance_work_items', 'master_unit_id')) {
            Schema::table('maintenance_work_items', function (Blueprint $table): void {
                $table->foreignId('master_unit_id')->nullable()->after('maintenance_unit_id')->constrained('master_units')->nullOnDelete();
            });
        }

        if (Schema::hasTable('maintenance_unit_conditions') && ! Schema::hasColumn('maintenance_unit_conditions', 'master_unit_id')) {
            Schema::table('maintenance_unit_conditions', function (Blueprint $table): void {
                $table->foreignId('master_unit_id')->nullable()->after('maintenance_unit_id')->constrained('master_units')->nullOnDelete();
            });
        }

        $this->backfillMasterUnitMetadata();
        $this->backfillMaintenanceReferences();
        $this->relaxLegacyMaintenanceConditionColumn();

        if (Schema::hasTable('maintenance_unit_conditions')) {
            Schema::table('maintenance_unit_conditions', function (Blueprint $table): void {
                $table->unique(['maintenance_report_id', 'master_unit_id'], 'maintenance_unit_condition_master_unique');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('maintenance_unit_conditions') && Schema::hasColumn('maintenance_unit_conditions', 'master_unit_id')) {
            Schema::table('maintenance_unit_conditions', function (Blueprint $table): void {
                $table->dropUnique('maintenance_unit_condition_master_unique');
                $table->dropConstrainedForeignId('master_unit_id');
            });
        }

        if (Schema::hasTable('maintenance_work_items') && Schema::hasColumn('maintenance_work_items', 'master_unit_id')) {
            Schema::table('maintenance_work_items', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('master_unit_id');
            });
        }

        Schema::table('master_units', function (Blueprint $table): void {
            $table->dropIndex('master_units_macro_status_index');
            $table->dropUnique('master_units_code_number_unique');
            $table->dropColumn(['unit_code', 'brand', 'unit_number', 'macro_category']);
        });
    }

    private function backfillMasterUnitMetadata(): void
    {
        DB::table('master_units')->orderBy('id')->get()->each(function (object $unit): void {
            $unitCode = $unit->unit_code ?: $this->unitCodeFromType((string) $unit->type);
            $unitNumber = $unit->unit_number ?: $this->unitNumberFromName((string) $unit->name);
            $macroCategory = $unit->macro_category ?: $this->macroCategoryFromType((string) $unit->type);

            DB::table('master_units')->where('id', $unit->id)->update([
                'unit_code' => $unitCode,
                'unit_number' => $unitNumber,
                'macro_category' => $macroCategory,
                'updated_at' => $unit->updated_at,
            ]);
        });

        if (! Schema::hasTable('maintenance_units')) {
            return;
        }

        DB::table('maintenance_units')->orderBy('id')->get()->each(function (object $unit): void {
            $master = DB::table('master_units')
                ->where('unit_code', $unit->unit_code)
                ->where('unit_number', $unit->unit_number)
                ->first();

            $payload = [
                'brand' => $unit->brand,
                'macro_category' => $unit->macro_category,
                'status' => ($unit->is_active ?? true) ? 'active' : 'inactive',
                'updated_at' => now(),
            ];

            if ($master) {
                DB::table('master_units')->where('id', $master->id)->update($payload);

                return;
            }

            DB::table('master_units')->insert(array_merge($payload, [
                'name' => $this->operationalName($unit->unit_code, $unit->unit_number),
                'type' => $this->typeFromUnitCode($unit->unit_code),
                'unit_code' => $unit->unit_code,
                'unit_number' => $unit->unit_number,
                'created_at' => now(),
            ]));
        });
    }

    private function backfillMaintenanceReferences(): void
    {
        if (! Schema::hasTable('maintenance_units')) {
            return;
        }

        $map = DB::table('maintenance_units')
            ->get()
            ->mapWithKeys(function (object $unit): array {
                $masterId = DB::table('master_units')
                    ->where('unit_code', $unit->unit_code)
                    ->where('unit_number', $unit->unit_number)
                    ->value('id');

                return $masterId ? [$unit->id => $masterId] : [];
            });

        $map->each(function (int $masterId, int $legacyId): void {
            if (Schema::hasTable('maintenance_work_items') && Schema::hasColumn('maintenance_work_items', 'master_unit_id')) {
                DB::table('maintenance_work_items')
                    ->where('maintenance_unit_id', $legacyId)
                    ->whereNull('master_unit_id')
                    ->update(['master_unit_id' => $masterId]);
            }

            if (Schema::hasTable('maintenance_unit_conditions') && Schema::hasColumn('maintenance_unit_conditions', 'master_unit_id')) {
                DB::table('maintenance_unit_conditions')
                    ->where('maintenance_unit_id', $legacyId)
                    ->whereNull('master_unit_id')
                    ->update(['master_unit_id' => $masterId]);
            }
        });
    }

    private function relaxLegacyMaintenanceConditionColumn(): void
    {
        if (! Schema::hasTable('maintenance_unit_conditions') || ! Schema::hasColumn('maintenance_unit_conditions', 'maintenance_unit_id')) {
            return;
        }

        try {
            Schema::table('maintenance_unit_conditions', function (Blueprint $table): void {
                $table->dropForeign(['maintenance_unit_id']);
            });
        } catch (Throwable) {
            // SQLite may rebuild constraints differently; changing nullability below is what matters.
        }

        try {
            Schema::table('maintenance_unit_conditions', function (Blueprint $table): void {
                $table->unsignedBigInteger('maintenance_unit_id')->nullable()->change();
            });
        } catch (Throwable) {
            // Kept for database engines that cannot alter this legacy column in-place.
        }
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

    private function typeFromUnitCode(?string $code): ?string
    {
        return match (strtoupper((string) $code)) {
            'TRL' => 'Trailer',
            'TRT' => 'Tronton',
            'DT' => 'Dump Truck',
            'FL' => 'Forklift',
            'WL' => 'Wheel Loader',
            'EXC' => 'Excavator',
            'PU' => 'Pick Up',
            'BUS' => 'Bus',
            default => null,
        };
    }

    private function operationalName(?string $code, ?string $unitNumber): string
    {
        $type = $this->typeFromUnitCode($code) ?: trim((string) $code);

        return trim($type.' '.(string) $unitNumber);
    }
};
