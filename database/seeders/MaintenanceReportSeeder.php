<?php

namespace Database\Seeders;

use App\Models\MaintenanceReport;
use App\Models\MaintenanceWorkItem;
use App\Models\MasterEmployee;
use App\Models\MasterUnit;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\Concerns\GuardsSampleData;
use Illuminate\Database\Seeder;

/**
 * 3 contoh laporan pemeliharaan, memakai roster Bengkel asli dari
 * MasterEmployeeSeeder dan unit asli dari MasterUnitSeeder.
 *
 * Idempotent: updateOrCreate berdasarkan report_date.
 */
class MaintenanceReportSeeder extends Seeder
{
    use GuardsSampleData;

    private const SOURCE = 'MaintenanceReportSeeder';

    /** Pekerjaan utama Group I-IV: [deskripsi, penanggung jawab]. */
    private const MAIN_WORK = [
        'I' => ['Perawatan berkala forklift area gudang', 'Usman'],
        'II' => ['Pengecekan sistem hidrolik wheel loader', 'Arman'],
        'III' => ['Servis rutin trailer & tronton', 'Muhammad Suaiban'],
        'IV' => ['Pengecekan kelistrikan unit bus/minibus', 'Rahul'],
    ];

    public function run(): void
    {
        if ($this->shouldSkipSampleData()) {
            return;
        }

        $creator = User::where('username', 'kasi.pemeliharaan')->first() ?? User::first();
        $approver = User::where('username', 'manajer')->first();

        $reports = [
            ['offset' => 1, 'status' => 'submitted'],
            ['offset' => 4, 'status' => 'approved'],
            ['offset' => 7, 'status' => 'approved'],
        ];

        foreach ($reports as $data) {
            $date = Carbon::today()->subDays($data['offset']);
            $submittedAt = $date->copy()->setTime(16, 0);
            $approvedAt = $data['status'] === 'approved' ? $date->copy()->setTime(19, 0) : null;

            $report = MaintenanceReport::updateOrCreate(
                ['report_date' => $date->toDateString()],
                [
                    'day_name' => $date->locale('id')->translatedFormat('l'),
                    'work_time_start' => '08:00',
                    'work_time_end' => '16:00',
                    'status' => $data['status'],
                    'created_by' => $creator?->id,
                    'submitted_at' => $submittedAt,
                    'approved_by' => $approvedAt ? $approver?->id : null,
                    'approved_at' => $approvedAt,
                    'karu_pemeliharaan_name' => 'Achmad Saiful Anwari',
                    'karu_peralatan_name' => 'Akhmad Yani Siregar',
                ]
            );

            $this->resetDetails($report);
            $this->seedMainWorkItems($report);
            $this->seedPriorityWorkItems($report);
            $this->seedUnitConditions($report);
            $this->seedAttendances($report);
        }

        $this->command?->info('3 laporan pemeliharaan dibuat untuk 10 hari terakhir.');
    }

    private function seedMainWorkItems(MaintenanceReport $report): void
    {
        $sort = 0;

        foreach (self::MAIN_WORK as $group => [$description, $assignee]) {
            $report->workItems()->create([
                'work_type' => MaintenanceWorkItem::TYPE_UTAMA,
                'work_group' => $group,
                'description' => $description,
                'assignee' => $assignee,
                'is_completed' => true,
                'sort_order' => $sort++,
            ]);
        }
    }

    private function seedPriorityWorkItems(MaintenanceReport $report): void
    {
        $forklift = MasterUnit::where('name', 'like', 'Forklift%')->orderBy('id')->first();

        $report->workItems()->create([
            'work_type' => MaintenanceWorkItem::TYPE_PRIORITAS,
            'master_unit_id' => $forklift?->id,
            'unit_label' => $forklift?->maintenance_name,
            'description' => 'Ganti oli hidrolik & cek kebocoran seal',
            'assignee' => 'Usman',
            'is_completed' => false,
            'notes' => 'Menunggu spare part datang',
            'sort_order' => 0,
        ]);
    }

    private function seedUnitConditions(MaintenanceReport $report): void
    {
        $units = MasterUnit::whereNotNull('macro_category')->orderBy('id')->limit(4)->get();

        foreach ($units as $index => $unit) {
            $report->unitConditions()->create([
                'master_unit_id' => $unit->id,
                'unit_label' => $unit->maintenance_name,
                'condition' => $index === 0 ? 'rusak' : 'ready',
                'notes' => $index === 0 ? 'Menunggu suku cadang' : null,
            ]);
        }
    }

    private function seedAttendances(MaintenanceReport $report): void
    {
        $bengkel = MasterEmployee::forMaintenance()
            ->where('group_name', 'Bengkel')
            ->where('status', 'active')
            ->orderBy('id')
            ->get();

        foreach ($bengkel as $sort => $employee) {
            $report->attendances()->create([
                'master_employee_id' => $employee->id,
                'employee_name' => $employee->name,
                'position' => $employee->position,
                'time_in' => '08:00',
                'time_out' => '16:00',
                'sort_order' => $sort,
            ]);
        }
    }

    private function resetDetails(MaintenanceReport $report): void
    {
        $report->workItems()->delete();
        $report->unitConditions()->delete();
        $report->attendances()->delete();
    }
}
