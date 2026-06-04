<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('master_employees', function (Blueprint $table): void {
            if (! Schema::hasColumn('master_employees', 'division')) {
                $table->string('division')->nullable()->after('position');
            }

            if (! Schema::hasColumn('master_employees', 'work_time')) {
                $table->string('work_time')->nullable()->after('division');
            }
        });

        Schema::table('master_employees', function (Blueprint $table): void {
            $table->index(['division', 'status'], 'master_employees_division_status_index');
        });

        DB::table('master_employees')
            ->whereNull('division')
            ->update(['division' => 'operasional']);

        if (Schema::hasTable('maintenance_attendances') && ! Schema::hasColumn('maintenance_attendances', 'master_employee_id')) {
            Schema::table('maintenance_attendances', function (Blueprint $table): void {
                $table->foreignId('master_employee_id')->nullable()->after('maintenance_employee_id')->constrained('master_employees')->nullOnDelete();
            });
        }

        $this->backfillMaintenanceEmployees();
        $this->backfillMaintenanceAttendanceReferences();
    }

    public function down(): void
    {
        if (Schema::hasTable('maintenance_attendances') && Schema::hasColumn('maintenance_attendances', 'master_employee_id')) {
            Schema::table('maintenance_attendances', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('master_employee_id');
            });
        }

        Schema::table('master_employees', function (Blueprint $table): void {
            $table->dropIndex('master_employees_division_status_index');
            $table->dropColumn(['division', 'work_time']);
        });
    }

    private function backfillMaintenanceEmployees(): void
    {
        if (! Schema::hasTable('maintenance_employees')) {
            return;
        }

        DB::table('maintenance_employees')->orderBy('id')->get()->each(function (object $employee): void {
            $master = DB::table('master_employees')->where('name', $employee->name)->first();
            $status = ($employee->is_active ?? true) ? 'active' : 'inactive';

            if ($master) {
                DB::table('master_employees')->where('id', $master->id)->update([
                    'position' => $employee->position ?: $master->position,
                    'division' => $this->mergeDivision((string) ($master->division ?? 'operasional'), 'pemeliharaan'),
                    'work_time' => $employee->work_time ?: $master->work_time,
                    'status' => $master->status === 'active' ? 'active' : $status,
                    'updated_at' => now(),
                ]);

                return;
            }

            DB::table('master_employees')->insert([
                'npk' => $this->uniqueMaintenanceNpk((int) $employee->id),
                'name' => $employee->name,
                'group_name' => null,
                'position' => $employee->position,
                'division' => 'pemeliharaan',
                'work_time' => $employee->work_time ?: 'Non Shift',
                'status' => $status,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }

    private function backfillMaintenanceAttendanceReferences(): void
    {
        if (! Schema::hasTable('maintenance_attendances') || ! Schema::hasColumn('maintenance_attendances', 'master_employee_id')) {
            return;
        }

        DB::table('maintenance_attendances')
            ->whereNull('master_employee_id')
            ->orderBy('id')
            ->get()
            ->each(function (object $attendance): void {
                $employeeName = $attendance->employee_name;

                if (Schema::hasTable('maintenance_employees') && $attendance->maintenance_employee_id) {
                    $employeeName = DB::table('maintenance_employees')
                        ->where('id', $attendance->maintenance_employee_id)
                        ->value('name') ?: $employeeName;
                }

                $masterId = DB::table('master_employees')
                    ->where('name', $employeeName)
                    ->whereIn('division', ['pemeliharaan', 'both'])
                    ->value('id');

                if ($masterId) {
                    DB::table('maintenance_attendances')
                        ->where('id', $attendance->id)
                        ->update(['master_employee_id' => $masterId]);
                }
            });
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

    private function uniqueMaintenanceNpk(int $seed): string
    {
        $number = max(1, $seed);

        do {
            $npk = 'MNT-'.str_pad((string) $number, 3, '0', STR_PAD_LEFT);
            $number++;
        } while (DB::table('master_employees')->where('npk', $npk)->exists());

        return $npk;
    }
};
