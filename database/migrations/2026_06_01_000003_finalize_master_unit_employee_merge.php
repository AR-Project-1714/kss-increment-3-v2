<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->makeEmployeeNpkNullable();
        $this->ensureMaintenanceUnitReferences();
        $this->ensureMaintenanceEmployeeReferences();
        $this->dropLegacyReferenceColumns();

        Schema::dropIfExists('maintenance_units');
        Schema::dropIfExists('maintenance_employees');
    }

    public function down(): void
    {
        if (! Schema::hasTable('maintenance_units')) {
            Schema::create('maintenance_units', function (Blueprint $table): void {
                $table->id();
                $table->string('unit_code');
                $table->string('brand')->nullable();
                $table->string('unit_number');
                $table->string('macro_category');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->unique(['unit_code', 'unit_number']);
                $table->index(['macro_category', 'is_active']);
            });
        }

        if (! Schema::hasTable('maintenance_employees')) {
            Schema::create('maintenance_employees', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('position')->nullable();
                $table->string('work_time')->default('Non Shift');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->index('is_active');
            });
        }

        if (Schema::hasTable('maintenance_work_items') && ! Schema::hasColumn('maintenance_work_items', 'maintenance_unit_id')) {
            Schema::table('maintenance_work_items', function (Blueprint $table): void {
                $table->unsignedBigInteger('maintenance_unit_id')->nullable()->after('work_group');
            });
        }

        if (Schema::hasTable('maintenance_unit_conditions') && ! Schema::hasColumn('maintenance_unit_conditions', 'maintenance_unit_id')) {
            Schema::table('maintenance_unit_conditions', function (Blueprint $table): void {
                $table->unsignedBigInteger('maintenance_unit_id')->nullable()->after('maintenance_report_id');
            });
        }

        if (Schema::hasTable('maintenance_attendances') && ! Schema::hasColumn('maintenance_attendances', 'maintenance_employee_id')) {
            Schema::table('maintenance_attendances', function (Blueprint $table): void {
                $table->unsignedBigInteger('maintenance_employee_id')->nullable()->after('maintenance_report_id');
            });
        }
    }

    private function makeEmployeeNpkNullable(): void
    {
        if (! Schema::hasTable('master_employees') || ! Schema::hasColumn('master_employees', 'npk')) {
            return;
        }

        try {
            Schema::table('master_employees', function (Blueprint $table): void {
                $table->dropUnique('master_employees_npk_unique');
            });
        } catch (Throwable) {
            // The index may already have been dropped in local databases.
        }

        try {
            Schema::table('master_employees', function (Blueprint $table): void {
                $table->string('npk')->nullable()->change();
            });
        } catch (Throwable) {
            // Keep the migration portable on engines that cannot alter this column in-place.
        }

        try {
            Schema::table('master_employees', function (Blueprint $table): void {
                $table->unique('npk', 'master_employees_npk_unique');
            });
        } catch (Throwable) {
            // Re-adding is best-effort; validation still prevents duplicate non-empty NPKs.
        }

        DB::table('master_employees')
            ->whereIn('division', ['pemeliharaan', 'both'])
            ->where('npk', 'like', 'MNT-%')
            ->update(['npk' => null]);
    }

    private function ensureMaintenanceUnitReferences(): void
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

    private function ensureMaintenanceEmployeeReferences(): void
    {
        if (! Schema::hasTable('maintenance_attendances') || ! Schema::hasColumn('maintenance_attendances', 'master_employee_id')) {
            return;
        }

        DB::table('maintenance_attendances')
            ->whereNull('master_employee_id')
            ->orderBy('id')
            ->get()
            ->each(function (object $attendance): void {
                $name = $attendance->employee_name;

                if (Schema::hasTable('maintenance_employees') && isset($attendance->maintenance_employee_id) && $attendance->maintenance_employee_id) {
                    $name = DB::table('maintenance_employees')
                        ->where('id', $attendance->maintenance_employee_id)
                        ->value('name') ?: $name;
                }

                $masterId = DB::table('master_employees')
                    ->where('name', $name)
                    ->whereIn('division', ['pemeliharaan', 'both'])
                    ->value('id');

                if ($masterId) {
                    DB::table('maintenance_attendances')
                        ->where('id', $attendance->id)
                        ->update(['master_employee_id' => $masterId]);
                }
            });
    }

    private function dropLegacyReferenceColumns(): void
    {
        $this->dropForeignIfExists('maintenance_work_items', ['maintenance_unit_id']);
        $this->dropColumnIfExists('maintenance_work_items', 'maintenance_unit_id');

        if (Schema::hasTable('maintenance_unit_conditions')) {
            try {
                Schema::table('maintenance_unit_conditions', function (Blueprint $table): void {
                    $table->dropUnique('maintenance_unit_condition_unique');
                });
            } catch (Throwable) {
                // Already gone on databases where the old unique was removed manually.
            }
        }

        $this->dropForeignIfExists('maintenance_unit_conditions', ['maintenance_unit_id']);
        $this->dropColumnIfExists('maintenance_unit_conditions', 'maintenance_unit_id');

        $this->dropForeignIfExists('maintenance_attendances', ['maintenance_employee_id']);
        $this->dropColumnIfExists('maintenance_attendances', 'maintenance_employee_id');
    }

    private function dropForeignIfExists(string $tableName, array $columns): void
    {
        if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, $columns[0])) {
            return;
        }

        try {
            Schema::table($tableName, function (Blueprint $table) use ($columns): void {
                $table->dropForeign($columns);
            });
        } catch (Throwable) {
            // Constraint names differ across engines; dropping the column below is authoritative.
        }
    }

    private function dropColumnIfExists(string $tableName, string $column): void
    {
        if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, $column)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($column): void {
            $table->dropColumn($column);
        });
    }
};
