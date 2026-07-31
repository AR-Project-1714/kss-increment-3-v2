<?php

namespace Tests\Feature;

use App\Models\BulkLoadingActivity;
use App\Models\BulkLoadingLog;
use App\Models\DailyReport;
use App\Models\ShipOperation;
use App\Support\ShipNameNormalizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sebagian kecil nilai COB yang tersimpan di server memang keliru.
 *
 * Pembantu integer() yang lama membuang SEMUA titik. Untuk angka bergaya
 * Indonesia — "16.750", titik sebagai pemisah ribuan — hasilnya justru benar
 * (16750). Yang rusak hanyalah koma desimal sejati: "4420.25" menjadi 442025.
 *
 * Karena itu pemulihan harus selektif. Nilai dibaca ulang dari isian form asli
 * di daily_reports.payload memakai TonnageNumber, yang membedakan pemisah
 * ribuan dari koma desimal; nilai yang sudah benar tidak boleh ikut tersentuh.
 */
class RestoreCorruptedCobTest extends TestCase
{
    use RefreshDatabase;

    /** Persis perilaku integer() sebelum perbaikan. */
    private function corrupt(string $typed): int
    {
        return max(0, (int) preg_replace('/[^\d\-]/', '', $typed));
    }

    /**
     * Titik sebagai pemisah ribuan sudah tersimpan BENAR oleh integer() lama —
     * "16.750" menjadi 16750. Nilai seperti itu tidak boleh ikut diubah.
     */
    public function test_pemisah_ribuan_tidak_ikut_diubah(): void
    {
        $typed = ['15000', '16.750', '19.400', '20280'];

        $this->voyageWithCorruptedCob($typed);

        $before = BulkLoadingLog::orderBy('id')->pluck('cob')->map(fn ($v): float => (float) $v)->all();
        $this->assertSame([15000.0, 16750.0, 19400.0, 20280.0], $before);

        $this->artisan('ops:repair-ship-identity')->assertSuccessful();

        $this->assertSame(
            $before,
            BulkLoadingLog::orderBy('id')->pluck('cob')->map(fn ($v): float => (float) $v)->all(),
            'Nilai yang sudah benar tidak boleh ikut tersentuh.',
        );
    }

    /**
     * Yang benar-benar rusak hanyalah koma desimal sejati: "4420.25" tersimpan
     * sebagai 442025.
     */
    public function test_koma_desimal_sejati_dipulihkan(): void
    {
        $this->voyageWithCorruptedCob(['4000', '4420.25'], capacity: 4420.25);

        $this->assertSame(
            [4000.0, 442025.0],
            BulkLoadingLog::orderBy('id')->pluck('cob')->map(fn ($v): float => (float) $v)->all(),
        );

        $this->artisan('ops:repair-ship-identity')->assertSuccessful();

        $this->assertSame(
            [4000.0, 4420.25],
            BulkLoadingLog::orderBy('id')->pluck('cob')->map(fn ($v): float => (float) $v)->all(),
        );
    }

    /**
     * Sesudah nilainya pulih, penskalaan ribuan ton mengambil alih dan
     * tonasenya menjadi benar.
     */
    public function test_tonase_menjadi_benar_setelah_cob_dipulihkan(): void
    {
        $this->voyageWithCorruptedCob(['15000', '16.750', '19.400', '20280']);

        $this->artisan('ops:repair-ship-identity')->assertSuccessful();

        $this->assertSame(
            [15000.0, 16750.0, 19400.0, 20280.0],
            BulkLoadingLog::orderBy('id')->pluck('cob_normalized')->map(fn ($v): float => (float) $v)->all(),
        );
        $this->assertSame(20_280.0, (float) BulkLoadingLog::sum('cob_delta'));
    }

    /**
     * Baris payload yang seluruhnya kosong tidak pernah menjadi baris log, jadi
     * pemasangannya harus melewatinya persis seperti controller.
     */
    public function test_baris_payload_kosong_tidak_menggeser_pemasangan(): void
    {
        $this->voyageWithCorruptedCob(['1000', '', '2.500'], withBlankRow: true);

        $this->artisan('ops:repair-ship-identity')->assertSuccessful();

        $this->assertSame(
            [1000.0, null, 2500.0],
            BulkLoadingLog::orderBy('id')->pluck('cob')->map(fn ($v): ?float => $v === null ? null : (float) $v)->all(),
        );
    }

    /**
     * Laporan lama tanpa payload tidak bisa dipulihkan. Nilainya dibiarkan apa
     * adanya — menebak faktornya lebih berbahaya daripada membiarkannya.
     */
    public function test_laporan_tanpa_payload_dibiarkan_apa_adanya(): void
    {
        $this->voyageWithCorruptedCob(['4420.25'], withPayload: false, capacity: 4420.25);

        $this->artisan('ops:repair-ship-identity')->assertSuccessful();

        $this->assertSame(442025.0, (float) BulkLoadingLog::first()->cob);
    }

    /**
     * @param  array<int, string>  $typed
     */
    private function voyageWithCorruptedCob(array $typed, bool $withPayload = true, bool $withBlankRow = false, float $capacity = 40000): void
    {
        $fields = [
            ['key' => 'report_date', 'value' => '2026-07-10'],
            ['key' => 'shift', 'value' => 'Pagi'],
        ];

        foreach ($typed as $index => $value) {
            $fields[] = ['key' => "bulk_logs[1][{$index}][time]", 'value' => '0'.($index + 1).':00'];
            $fields[] = ['key' => "bulk_logs[1][{$index}][activity]", 'value' => 'Pemuatan'];
            $fields[] = ['key' => "bulk_logs[1][{$index}][cob]", 'value' => $value];
        }

        if ($withBlankRow) {
            // Baris terakhir form yang tidak pernah diisi petugas.
            $next = count($typed);
            $fields[] = ['key' => "bulk_logs[1][{$next}][time]", 'value' => ''];
            $fields[] = ['key' => "bulk_logs[1][{$next}][activity]", 'value' => ''];
            $fields[] = ['key' => "bulk_logs[1][{$next}][cob]", 'value' => ''];
        }

        $report = DailyReport::create([
            'report_date' => '2026-07-10',
            'shift' => 'Pagi',
            'group_name' => 'A',
            'status' => 'approved',
            'payload' => $withPayload ? ['fields' => $fields] : null,
        ]);

        $operation = ShipOperation::create([
            'type' => ShipOperation::TYPE_BULK_LOADING,
            'status' => ShipOperation::STATUS_ACTIVE,
            'ship_name' => 'KM. Contoh',
            'ship_name_key' => ShipNameNormalizer::key('KM. Contoh'),
            'capacity' => $capacity,
        ]);

        $activity = BulkLoadingActivity::create([
            'daily_report_id' => $report->id,
            'ship_operation_id' => $operation->id,
            'activity_type' => BulkLoadingActivity::TYPE_BULK_LOADING,
            'sequence' => 1,
            'ship_name' => 'KM. Contoh',
            'ship_name_key' => ShipNameNormalizer::key('KM. Contoh'),
            'capacity' => $capacity,
            'berthing_time' => '2026-07-10 06:00:00',
        ]);

        foreach ($typed as $index => $value) {
            $stored = $this->corrupt($value);

            $activity->logs()->create([
                'datetime' => '2026-07-10 0'.($index + 1).':00:00',
                'activity' => 'Pemuatan',
                'cob' => $stored > 0 ? $stored : null,
            ]);
        }
    }
}
