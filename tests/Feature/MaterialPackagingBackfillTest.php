<?php

namespace Tests\Feature;

use App\Models\DailyReport;
use App\Models\MaterialActivity;
use App\Models\MaterialItem;
use App\Services\OperationalPerformanceService;
use App\Support\MaterialPackaging;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Laporan yang sudah tersimpan tidak boleh berubah angkanya setelah kemasan
 * dipisah dari namanya. Uji ini mengisi tabel dengan baris bergaya lama —
 * hanya berlabel kemasan, tanpa kode dan faktor — lalu menjalankan pengisian
 * data milik migrasinya.
 */
class MaterialPackagingBackfillTest extends TestCase
{
    use RefreshDatabase;

    public function test_migrasi_mengisi_kode_dan_faktor_data_lama(): void
    {
        $this->seedLegacyItems();
        $this->runPackagingBackfill();

        $jumbo = MaterialItem::where('raw_material_type', 'Clay')->firstOrFail();
        $this->assertSame(MaterialPackaging::CODE_JUMBO_1000, $jumbo->packaging_code);
        $this->assertSame('Jumbo Bag 1 Ton', $jumbo->packaging_type);
        $this->assertSame(1.0, (float) $jumbo->packaging_factor);

        $bag = MaterialItem::where('raw_material_type', 'MgO 18%')->firstOrFail();
        $this->assertSame(MaterialPackaging::CODE_BAG_50, $bag->packaging_code);
        $this->assertSame('Bag 50 Kg', $bag->packaging_type);
        $this->assertSame(0.05, (float) $bag->packaging_factor);

        // Baris pra-kemasan tetap apa adanya: isinya memang sudah berupa Ton
        // dan tidak boleh dibaca sebagai jumlah Bag lalu dikonversi ulang.
        $legacy = MaterialItem::where('raw_material_type', 'Limestone')->firstOrFail();
        $this->assertNull($legacy->packaging_code);
        $this->assertNull($legacy->packaging_factor);
    }

    public function test_tonase_laporan_lama_tidak_bergeser_setelah_migrasi(): void
    {
        $this->seedLegacyItems();

        // Tanpa pengisian data, baris berlabel kemasan kehilangan faktornya
        // dan jumlah Bag-nya ikut terbaca sebagai Ton. Inilah yang dicegah
        // migrasi: 200 + 1.500 + 75.
        $this->assertSame(1775.0, $this->materialTonnage());

        $this->runPackagingBackfill();

        // Sama persis dengan hasil perhitungan sebelum kemasan berkode:
        // 200 Ton + (1.500 / 20) Ton + 75 Ton warisan.
        $this->assertSame(350.0, $this->materialTonnage());
    }

    /** Baris gaya lama: berlabel kemasan, tetapi belum berkode dan berfaktor. */
    private function seedLegacyItems(): void
    {
        $report = DailyReport::create([
            'report_date' => '2026-07-10',
            'shift' => 'Pagi',
            'group_name' => 'A',
            'status' => 'approved',
        ]);

        $activity = MaterialActivity::create([
            'daily_report_id' => $report->id,
            'sequence' => 1,
            'ship_name' => 'KM. Hasil Bahari 8',
            'capacity' => 4750,
        ]);

        $activity->items()->createMany([
            ['raw_material_type' => 'Clay', 'packaging_type' => 'Jumbo Bag', 'qty_current' => 200],
            ['raw_material_type' => 'MgO 18%', 'packaging_type' => 'Bag 50 Kg', 'qty_current' => 1500],
            ['raw_material_type' => 'Limestone', 'packaging_type' => null, 'qty_current' => 75],
        ]);
    }

    private function runPackagingBackfill(): void
    {
        $migration = require database_path('migrations/2026_08_14_000001_add_packaging_code_to_material_items_table.php');
        $migration->backfill();
    }

    private function materialTonnage(): float
    {
        return (float) app(OperationalPerformanceService::class)->activityDetail('bongkar_bahan_baku', [
            'start' => Carbon::parse('2026-07-10')->startOfDay(),
            'end' => Carbon::parse('2026-07-10')->endOfDay(),
            'group' => null,
            'shift' => null,
        ])['value'];
    }
}
