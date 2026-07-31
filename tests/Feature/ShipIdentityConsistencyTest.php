<?php

namespace Tests\Feature;

use App\Models\BulkLoadingActivity;
use App\Models\ContainerActivity;
use App\Models\ContainerItem;
use App\Models\DailyReport;
use App\Models\LoadingActivity;
use App\Models\MaterialActivity;
use App\Models\MaterialItem;
use App\Models\ShipOperation;
use App\Services\BulkTonnageService;
use App\Services\OperationalPerformanceService;
use App\Support\ShipNameNormalizer;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Satu kapal yang ejaannya berbeda antar shift harus terhitung satu kapal pada
 * SEMUA kegiatan yang mencatat nama kapal — bukan hanya muat curah.
 *
 * Bongkar bahan baku dan bongkar/muat container tidak punya operasi kapal sama
 * sekali (formnya memang tidak menyediakan tombol Simpan Operasi Kapal), jadi
 * di sana nama kanonik adalah satu-satunya pengaman yang tersedia.
 */
class ShipIdentityConsistencyTest extends TestCase
{
    use RefreshDatabase;

    /** Tiga ejaan yang di lapangan menunjuk kapal yang sama persis. */
    private const SPELLINGS = ['MV. Sumber Rezeki', 'MV.SUMBER REZEKI', 'mv sumber rezeki'];

    public function test_bongkar_bahan_baku_menyatukan_ejaan_yang_berbeda(): void
    {
        foreach (self::SPELLINGS as $index => $spelling) {
            $activity = MaterialActivity::create([
                'daily_report_id' => $this->report($index)->id,
                'sequence' => 1,
                'ship_name' => $spelling,
                'ship_name_key' => ShipNameNormalizer::key($spelling),
                'agent' => 'PT. Karya Samudera',
                'jetty' => 'Jetty 2',
                'capacity' => 8000,
            ]);

            MaterialItem::create([
                'material_activity_id' => $activity->id,
                'raw_material_type' => 'Clay JB',
                'qty_current' => 1000,
            ]);
        }

        $detail = app(OperationalPerformanceService::class)->activityDetail('bongkar_bahan_baku', $this->filters());

        $this->assertCount(1, $detail['table']['rows'], 'Tiga ejaan satu kapal harus menjadi satu baris.');
        $this->assertSame(3000.0, $this->cell($detail, 0, 'Bongkar'), 'Tonasenya harus utuh, bukan terbagi tiga.');
        $this->assertSame(1, $this->metric($detail, 'Kapal dilayani'));
    }

    public function test_container_menyatukan_ejaan_yang_berbeda(): void
    {
        foreach (self::SPELLINGS as $index => $spelling) {
            $activity = ContainerActivity::create([
                'daily_report_id' => $this->report($index)->id,
                'sequence' => 1,
                'ship_name' => $spelling,
                'ship_name_key' => ShipNameNormalizer::key($spelling),
                'capacity' => 200,
                'capacity_empty' => 200,
                'capacity_full' => 120,
            ]);

            ContainerItem::create([
                'container_activity_id' => $activity->id,
                'qty_current' => 20,
                'status' => 'Empty',
            ]);
        }

        $detail = app(OperationalPerformanceService::class)->activityDetail('bongkar_container', $this->filters());

        $this->assertSame(1, $this->metric($detail, 'Kapal dilayani'));
        $this->assertSame(60.0, (float) $detail['value']);
    }

    /**
     * Rekap bulanan menghitung kolom "Kapal" dengan COUNT(DISTINCT ...) atas
     * penanda kapal. Sebelum perbaikan, penandanya nama mentah.
     */
    public function test_rekap_bulanan_tidak_menggandakan_kapal_pada_semua_kegiatan(): void
    {
        foreach (self::SPELLINGS as $index => $spelling) {
            $report = $this->report($index);

            LoadingActivity::create([
                'daily_report_id' => $report->id,
                'ship_name' => $spelling,
                'ship_name_key' => ShipNameNormalizer::key($spelling),
                'arrival_time' => '2026-07-10 06:00:00',
                'capacity' => 5000,
                'qty_loading_current' => 100,
            ]);

            $material = MaterialActivity::create([
                'daily_report_id' => $report->id,
                'sequence' => 1,
                'ship_name' => $spelling,
                'ship_name_key' => ShipNameNormalizer::key($spelling),
                'capacity' => 8000,
            ]);

            MaterialItem::create([
                'material_activity_id' => $material->id,
                'raw_material_type' => 'Clay JB',
                'qty_current' => 500,
            ]);

            $container = ContainerActivity::create([
                'daily_report_id' => $report->id,
                'sequence' => 1,
                'ship_name' => $spelling,
                'ship_name_key' => ShipNameNormalizer::key($spelling),
                'capacity' => 200,
                'capacity_empty' => 200,
                'capacity_full' => 120,
            ]);

            ContainerItem::create([
                'container_activity_id' => $container->id,
                'qty_current' => 10,
                'status' => 'Full',
            ]);
        }

        $recap = app(OperationalPerformanceService::class)->activityRecap($this->filters());
        $rows = collect($recap['rows'])->keyBy('key');

        foreach (['muat_kantong', 'bongkar_bahan_baku', 'muat_container'] as $key) {
            $this->assertSame(
                1,
                (int) $rows[$key]['total']['count'],
                'Kegiatan '.$key.' masih menghitung satu kapal sebagai beberapa kapal.',
            );
        }
    }

    /**
     * Muat kantong berbeda dari bongkar: ia punya operasi kapal, dan kolom
     * "Nilai Lalu" ikut terputus setiap kali identitasnya pecah. Panel
     * rincian harus tetap satu baris dengan akumulasi yang utuh.
     */
    public function test_muat_kantong_menyatukan_ejaan_beserta_akumulasinya(): void
    {
        $operation = ShipOperation::create([
            'type' => ShipOperation::TYPE_BAG_LOADING,
            'status' => ShipOperation::STATUS_ACTIVE,
            'ship_name' => self::SPELLINGS[0],
            'ship_name_key' => ShipNameNormalizer::key(self::SPELLINGS[0]),
            'capacity' => 5000,
        ]);

        foreach (self::SPELLINGS as $index => $spelling) {
            LoadingActivity::create([
                'daily_report_id' => $this->report($index)->id,
                'ship_operation_id' => $operation->id,
                'ship_name' => $spelling,
                'ship_name_key' => ShipNameNormalizer::key($spelling),
                'arrival_time' => '2026-07-10 06:00:00',
                'capacity' => 5000,
                'qty_loading_current' => 100,
                'qty_loading_prev' => $index * 100,
            ]);
        }

        $detail = app(OperationalPerformanceService::class)->activityDetail('muat_kantong', $this->filters());

        $this->assertCount(1, $detail['table']['rows']);
        // Akumulasi tertinggi (Sekarang + Lalu) pada shift terakhir: 100 + 200.
        $this->assertSame(300.0, $this->cell($detail, 0, 'Termuat'));
    }

    /**
     * Muat curah tetap ikut terlindungi walau petugas melewati fitur simpan
     * operasi kapal: pengelompokan jatuh ke nama kanonik, bukan nama mentah.
     */
    public function test_muat_curah_tanpa_operasi_kapal_tetap_satu_pelayaran(): void
    {
        $cob = [1000, 2500, 4000];

        foreach (self::SPELLINGS as $index => $spelling) {
            $activity = BulkLoadingActivity::create([
                'daily_report_id' => $this->report($index)->id,
                'activity_type' => BulkLoadingActivity::TYPE_BULK_LOADING,
                'sequence' => 1,
                'ship_name' => $spelling,
                'ship_name_key' => ShipNameNormalizer::key($spelling),
                'capacity' => 9000,
                'berthing_time' => '2026-07-10 06:00:00',
            ]);

            $activity->logs()->create([
                'datetime' => '2026-07-1'.$index.' 09:00:00',
                'activity' => 'Pemuatan',
                'cob' => $cob[$index],
            ]);
        }

        app(BulkTonnageService::class)->recalculate();

        $detail = app(OperationalPerformanceService::class)->activityDetail('muat_curah', $this->filters());

        $this->assertCount(1, $detail['table']['rows']);
        $this->assertSame(4000.0, (float) $detail['value']);
    }

    private function report(int $index): DailyReport
    {
        return DailyReport::create([
            'report_date' => Carbon::parse('2026-07-10')->addDays($index)->toDateString(),
            'shift' => ['Pagi', 'Sore', 'Malam'][$index],
            'group_name' => 'A',
            'status' => 'approved',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function filters(): array
    {
        return [
            'start' => Carbon::parse('2026-07-01'),
            'end' => Carbon::parse('2026-07-31'),
            'group' => null,
            'shift' => null,
        ];
    }

    /**
     * Ambil sel menurut JUDUL kolom, bukan posisinya. Panel rincian membuang
     * kolom yang seluruhnya kosong, sehingga nomor kolom bisa bergeser.
     *
     * @param  array<string, mixed>  $detail
     */
    private function cell(array $detail, int $row, string $column): float
    {
        foreach ($detail['table']['columns'] as $index => $definition) {
            if ($definition['label'] === $column) {
                return (float) $detail['table']['rows'][$row][$index];
            }
        }

        $this->fail('Kolom "'.$column.'" tidak ditemukan.');
    }

    /**
     * @param  array<string, mixed>  $detail
     */
    private function metric(array $detail, string $label): int
    {
        foreach ($detail['metrics'] as $metric) {
            if ($metric['label'] === $label) {
                return (int) $metric['value'];
            }
        }

        $this->fail('Metrik "'.$label.'" tidak ditemukan.');
    }
}
