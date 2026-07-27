<?php

namespace Database\Seeders;

use App\Models\MasterSafetyLocation;
use App\Models\SafetyReport;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\Concerns\GuardsSampleData;
use Illuminate\Database\Seeder;

/**
 * 3 contoh laporan harian K3, memakai template lokasi/item dari SafetySeeder
 * dan akun 'karu.safety' sebagai pembuat.
 *
 * Idempotent: updateOrCreate berdasarkan report_date + shift.
 */
class SafetyReportSeeder extends Seeder
{
    use GuardsSampleData;

    private const SOURCE = 'SafetyReportSeeder';

    private const ACTIVITIES = [
        'GRESIK NIAGA',
        'GOLDEN REJEKI',
        'PENGIRIMAN KE GD TURBA',
        'RENTAL UNIT PP&P',
        'RENTAL TRL PT.KAD',
        'RENTAL FL OP6 & OP7',
    ];

    public function run(): void
    {
        if ($this->shouldSkipSampleData()) {
            return;
        }

        $creator = User::where('username', 'karu.safety')->first() ?? User::first();
        $approver = User::where('username', 'manajer')->first();
        $locations = MasterSafetyLocation::active()->with('items')->orderBy('sort_order')->orderBy('id')->get();

        $reports = [
            ['offset' => 2, 'shift' => 'Malam', 'time_range' => '19:00 - 03:00', 'status' => 'submitted'],
            ['offset' => 5, 'shift' => 'Malam', 'time_range' => '19:00 - 03:00', 'status' => 'approved'],
            ['offset' => 8, 'shift' => 'Malam', 'time_range' => '19:00 - 03:00', 'status' => 'approved'],
        ];

        foreach ($reports as $index => $data) {
            $date = Carbon::today()->subDays($data['offset']);
            $submittedAt = $data['status'] !== 'draft' ? $date->copy()->setTime(4, 0) : null;
            $approvedAt = $data['status'] === 'approved' ? $date->copy()->setTime(9, 0) : null;

            $report = SafetyReport::updateOrCreate(
                [
                    'report_date' => $date->toDateString(),
                    'shift' => $data['shift'],
                ],
                [
                    'document_number' => 'DOC-'.$date->format('Y').'-'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT),
                    'time_range' => $data['time_range'],
                    'status' => $data['status'],
                    'created_by' => $creator?->id,
                    'submitted_at' => $submittedAt,
                    'approved_by' => $approvedAt ? $approver?->id : null,
                    'approved_at' => $approvedAt,
                ]
            );

            $this->resetDetails($report);
            $this->seedInspections($report, $locations, $index);
            $this->seedOperationLogs($report, $index);
            $this->seedIncidentLogs($report, $index);
        }

        $this->command?->info('3 laporan K3 dibuat untuk 10 hari terakhir.');
    }

    private function seedInspections(SafetyReport $report, $locations, int $index): void
    {
        $sort = 0;

        foreach ($locations as $location) {
            foreach ($location->items as $item) {
                // Satu item per laporan sengaja ditandai perlu perhatian, sisanya aman.
                $needsAttention = $index === 0 && $item->name === 'Kebersihan' && $location->name === 'Work Shop dan Sekitarnya';

                $report->inspections()->create([
                    'location_id' => $location->id,
                    'item_id' => $item->id,
                    'location_name_snapshot' => $location->name,
                    'item_name_snapshot' => $item->name,
                    'qty' => $item->is_countable ? ($item->pivot->default_qty ?? 1) : null,
                    // Enum 4-nilai (MD §2.4): bagus/rusak/normal/tidak_normal.
                    'condition' => $needsAttention ? 'tidak_normal' : 'bagus',
                    'recommendation' => $needsAttention ? 'Jadwalkan pembersihan area work shop.' : '',
                    'sort_order' => $sort++,
                ]);
            }
        }
    }

    private function seedOperationLogs(SafetyReport $report, int $index): void
    {
        foreach (self::ACTIVITIES as $sort => $activity) {
            $report->operationLogs()->create([
                'activity_name' => $activity,
                'condition' => 'Aman',
                'action' => '',
                'notes' => '',
                'sort_order' => $sort,
            ]);
        }
    }

    private function seedIncidentLogs(SafetyReport $report, int $index): void
    {
        if ($index !== 0) {
            return;
        }

        $report->incidentLogs()->create([
            'description' => 'Ceceran granul di area bagging-2 akibat karung sobek.',
            'condition' => 'Waspada',
            'action' => 'Area dibersihkan dan karung sobek dipisahkan dari stok.',
            'notes' => 'Sudah ditindaklanjuti sebelum serah terima shift.',
            'sort_order' => 0,
        ]);
    }

    private function resetDetails(SafetyReport $report): void
    {
        $report->inspections()->delete();
        $report->operationLogs()->delete();
        $report->incidentLogs()->delete();
    }
}
