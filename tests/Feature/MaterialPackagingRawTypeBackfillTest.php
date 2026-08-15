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
 * Laporan 10–13 Agustus 2026 dibuat sebelum kolom kemasan ada, jadi kemasannya
 * menyatu dengan nama bahan. Uji ini memakai ejaan yang benar-benar tercatat di
 * lapangan — termasuk yang disingkat dan salah ketik — lalu menjalankan
 * pengisian data milik migrasinya.
 */
class MaterialPackagingRawTypeBackfillTest extends TestCase
{
    use RefreshDatabase;

    public function test_kemasan_dibaca_dari_nama_bahan(): void
    {
        $this->seedFieldSpellings();
        $this->runBackfill();

        $bag = MaterialItem::where('raw_material_type', 'MGO 18% Bag @50Kg')->firstOrFail();
        $this->assertSame(MaterialPackaging::CODE_BAG_50, $bag->packaging_code);
        $this->assertSame('Bag 50 Kg', $bag->packaging_type);
        $this->assertSame(0.05, (float) $bag->packaging_factor);

        foreach (['Clay Jumbo Bag @ 1 Ton', 'CLAY JUMBO 17%', 'Limestone Jumbo bag'] as $name) {
            $jumbo = MaterialItem::where('raw_material_type', $name)->firstOrFail();
            $this->assertSame(MaterialPackaging::CODE_JUMBO_1000, $jumbo->packaging_code, $name);
            $this->assertSame(1.0, (float) $jumbo->packaging_factor, $name);
        }
    }

    public function test_nama_yang_disingkat_mengikuti_baris_sekapal(): void
    {
        $this->seedFieldSpellings();
        $this->runBackfill();

        // "MGO" dan "Mgo 18%" tidak menyebut kemasan sama sekali, tetapi deret
        // akumulasinya satu rangkaian dengan baris yang menyebutnya lengkap.
        foreach (['MGO', 'Mgo 18%'] as $name) {
            $item = MaterialItem::where('raw_material_type', $name)->firstOrFail();
            $this->assertSame(MaterialPackaging::CODE_BAG_50, $item->packaging_code, $name);
        }

        // Salah ketik satu huruf tidak boleh memutus penelusuran.
        $this->assertSame(
            MaterialPackaging::CODE_JUMBO_1000,
            MaterialItem::where('raw_material_type', 'Limeston')->firstOrFail()->packaging_code
        );
    }

    public function test_baris_pra_kemasan_tanpa_petunjuk_tidak_disentuh(): void
    {
        $report = $this->seedFieldSpellings();

        // Kapal lain, tanpa satu pun baris berkemasan sebagai rujukan: angkanya
        // memang dicatat dalam Ton dan tidak boleh dikonversi ulang.
        MaterialActivity::create([
            'daily_report_id' => $report->id,
            'sequence' => 2,
            'ship_name' => 'KM. Sinar Mulia',
            'ship_name_key' => 'SINAR MULIA',
            'capacity' => 3000,
        ])->items()->create(['raw_material_type' => 'Gypsum', 'qty_current' => 75]);

        $this->runBackfill();

        $legacy = MaterialItem::where('raw_material_type', 'Gypsum')->firstOrFail();
        $this->assertNull($legacy->packaging_code);
        $this->assertNull($legacy->packaging_factor);
    }

    public function test_tonase_bongkar_kembali_wajar(): void
    {
        $this->seedFieldSpellings();

        // Sebelum diperbaiki seluruh jumlah Bag terbaca sebagai Ton:
        // 110 + 130 + 182 + 140 + 1.900 + 3.300 + 2.120.
        $this->assertSame(7882.0, $this->materialTonnage());

        $this->runBackfill();

        // Jumbo bag tetap 562 Ton, sedangkan 7.320 Bag MgO menjadi 366 Ton.
        $this->assertSame(928.0, $this->materialTonnage());
    }

    public function test_baris_yang_sudah_berkemasan_tidak_ditimpa(): void
    {
        $report = $this->seedFieldSpellings();

        // Kemasan buatan petugas: kodenya sudah terisi, jadi penebakan dari nama
        // tidak boleh menggesernya ke katalog.
        $item = MaterialActivity::where('daily_report_id', $report->id)->firstOrFail()
            ->items()->create([
                'raw_material_type' => 'Clay Jumbo Bag @ 1 Ton',
                'packaging_type' => 'Jumbo Bag 800 Kg',
                'packaging_code' => MaterialPackaging::CODE_CUSTOM,
                'packaging_factor' => 0.8,
                'qty_current' => 10,
            ]);

        $this->runBackfill();

        $item->refresh();
        $this->assertSame(MaterialPackaging::CODE_CUSTOM, $item->packaging_code);
        $this->assertSame(0.8, (float) $item->packaging_factor);
    }

    /** Satu kapal, empat shift, dengan ejaan apa adanya dari lapangan. */
    private function seedFieldSpellings(): DailyReport
    {
        $report = DailyReport::create([
            'report_date' => '2026-08-10',
            'shift' => 'Pagi',
            'group_name' => 'A',
            'status' => 'approved',
        ]);

        $activity = MaterialActivity::create([
            'daily_report_id' => $report->id,
            'sequence' => 1,
            'ship_name' => 'KM. Hasil Bahari 8',
            'ship_name_key' => 'HASIL BAHARI 8',
            'capacity' => 4750,
        ]);

        $activity->items()->createMany([
            ['raw_material_type' => 'Clay Jumbo Bag @ 1 Ton', 'qty_current' => 110],
            ['raw_material_type' => 'MGO 18% Bag @50Kg', 'qty_current' => 1900],
            ['raw_material_type' => 'CLAY JUMBO 17%', 'qty_current' => 130],
            ['raw_material_type' => 'Mgo 18%', 'qty_current' => 3300],
            ['raw_material_type' => 'Limestone Jumbo bag', 'qty_current' => 182],
            ['raw_material_type' => 'MGO', 'qty_current' => 2120],
            ['raw_material_type' => 'Limeston', 'qty_current' => 140],
        ]);

        return $report;
    }

    private function runBackfill(): void
    {
        $migration = require database_path('migrations/2026_08_15_000001_backfill_material_packaging_from_raw_type.php');
        $migration->backfill();
    }

    private function materialTonnage(): float
    {
        return (float) app(OperationalPerformanceService::class)->activityDetail('bongkar_bahan_baku', [
            'start' => Carbon::parse('2026-08-10')->startOfDay(),
            'end' => Carbon::parse('2026-08-10')->endOfDay(),
            'group' => null,
            'shift' => null,
        ])['value'];
    }
}
