<?php

namespace Tests\Feature;

use App\Models\ContainerActivity;
use App\Models\MaterialActivity;
use App\Models\ShipOperation;
use App\Models\User;
use App\Support\ShipNameNormalizer;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Bongkar bahan baku dan bongkar/muat container kini punya operasi kapal,
 * sama seperti pemuatan. Sebelumnya keduanya hanya menyimpan nama kapal
 * sebagai teks bebas tanpa induk apa pun, sehingga kunjungan kapal yang sama
 * pada bulan berbeda tidak bisa dibedakan dari satu kunjungan panjang.
 */
class UnloadingShipOperationTest extends TestCase
{
    use RefreshDatabase;

    public function test_menyimpan_laporan_membuat_operasi_kapal_bongkar(): void
    {
        $this->submit('2026-05-19', 'Pagi', 'A', [
            'ship_name_material_1' => 'MV. Sumber Rezeki',
            'agent_material_1' => 'PT. Karya Samudera',
            'jetty_material_1' => 'Jetty 2',
            'capacity_material_1' => '8000',
            'ship_operation_material_status_1' => ShipOperation::STATUS_ACTIVE,
            'unloading_materials_1' => [['raw_material_type' => 'Clay JB', 'qty_current' => '500']],

            'ship_name_container_1' => 'KM. Ayer Mas',
            'agent_container_1' => 'Meratus Line',
            'jetty_container_1' => 'Tursina',
            'capacity_container_1' => '120',
            'capacity_full_container_1' => '80',
            'ship_operation_container_status_1' => ShipOperation::STATUS_ACTIVE,
            'unloading_containers_1' => [['status' => 'Empty', 'qty_current' => '20']],
        ]);

        $material = ShipOperation::where('type', ShipOperation::TYPE_MATERIAL_UNLOADING)->firstOrFail();
        $this->assertSame('MV. Sumber Rezeki', $material->ship_name);
        $this->assertSame('SUMBER REZEKI', $material->ship_name_key);
        $this->assertSame('PT. Karya Samudera', $material->agent);
        $this->assertSame(ShipOperation::STATUS_ACTIVE, $material->status);

        $container = ShipOperation::where('type', ShipOperation::TYPE_CONTAINER)->firstOrFail();
        $this->assertSame('AYER MAS', $container->ship_name_key);

        $this->assertSame($material->id, MaterialActivity::firstOrFail()->ship_operation_id);
        $this->assertSame($container->id, ContainerActivity::firstOrFail()->ship_operation_id);
    }

    /**
     * Inti masalahnya: shift berikutnya mengetik nama kapal dengan ejaan lain,
     * tetapi tetap harus menempel pada operasi kapal yang sama.
     */
    public function test_ejaan_berbeda_antar_shift_menempel_pada_operasi_yang_sama(): void
    {
        $this->submit('2026-05-19', 'Pagi', 'A', [
            'ship_name_material_1' => 'MV. Sumber Rezeki',
            'capacity_material_1' => '8000',
            'unloading_materials_1' => [['raw_material_type' => 'Clay JB', 'qty_current' => '500']],
        ]);

        $this->submit('2026-05-19', 'Sore', 'B', [
            'ship_name_material_1' => 'MV.SUMBER REZEKI',
            'capacity_material_1' => '8000',
            'unloading_materials_1' => [['raw_material_type' => 'Clay JB', 'qty_current' => '400']],
        ]);

        $this->submit('2026-05-20', 'Pagi', 'C', [
            'ship_name_material_1' => 'mv sumber rezeki',
            'capacity_material_1' => '8000',
            'unloading_materials_1' => [['raw_material_type' => 'Clay JB', 'qty_current' => '300']],
        ]);

        $this->assertSame(
            1,
            ShipOperation::where('type', ShipOperation::TYPE_MATERIAL_UNLOADING)->count(),
            'Tiga ejaan satu kapal harus menjadi satu operasi kapal.',
        );

        $operation = ShipOperation::firstOrFail();
        $this->assertSame(3, MaterialActivity::where('ship_operation_id', $operation->id)->count());
    }

    /**
     * Kapal yang ditandai "Selesai" tidak lagi ikut dicocokkan, sehingga
     * kunjungan berikutnya menjadi operasi kapal yang baru.
     */
    public function test_kapal_yang_sudah_selesai_tidak_menarik_kunjungan_berikutnya(): void
    {
        $this->submit('2026-05-19', 'Pagi', 'A', [
            'ship_name_container_1' => 'KM. Ayer Mas',
            'capacity_container_1' => '120',
            'ship_operation_container_status_1' => ShipOperation::STATUS_COMPLETED,
            'unloading_containers_1' => [['status' => 'Empty', 'qty_current' => '20']],
        ]);

        $this->submit('2026-05-20', 'Pagi', 'C', [
            'ship_name_container_1' => 'KM. Ayer Mas',
            'capacity_container_1' => '120',
            'unloading_containers_1' => [['status' => 'Empty', 'qty_current' => '15']],
        ]);

        $this->assertSame(2, ShipOperation::where('type', ShipOperation::TYPE_CONTAINER)->count());
    }

    /**
     * Riwayat yang ditulis sebelum kegiatan bongkar punya operasi kapal tidak
     * akan pernah tersambung dengan sendirinya. Perintah perbaikan membentuk
     * induknya, dan memotongnya menjadi kunjungan terpisah bila jedanya jauh.
     */
    public function test_perintah_perbaikan_membentuk_operasi_untuk_data_bongkar_lama(): void
    {
        $spellings = [
            ['2026-05-19', 'MV. Sumber Rezeki'],
            ['2026-05-20', 'MV.SUMBER REZEKI'],
            // Jeda jauh melewati ambang pelayaran: ini kunjungan berikutnya.
            ['2026-06-25', 'mv sumber rezeki'],
        ];

        foreach ($spellings as $index => [$date, $spelling]) {
            $report = DB::table('daily_reports')->insertGetId([
                'report_date' => $date,
                'shift' => 'Pagi',
                'group_name' => 'A',
                'status' => 'approved',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            MaterialActivity::create([
                'daily_report_id' => $report,
                'sequence' => 1,
                'ship_name' => $spelling,
                'ship_name_key' => ShipNameNormalizer::key($spelling),
                'agent' => 'PT. Karya Samudera',
                'capacity' => 8000,
            ]);
        }

        $this->assertSame(0, ShipOperation::count());

        $this->artisan('ops:repair-ship-identity')->assertSuccessful();

        $operations = ShipOperation::where('type', ShipOperation::TYPE_MATERIAL_UNLOADING)->get();

        $this->assertCount(2, $operations, 'Dua kunjungan terpisah, bukan satu atau tiga.');
        $this->assertSame(0, MaterialActivity::whereNull('ship_operation_id')->count());

        // Kunjungan pertama memuat dua shift, kunjungan kedua satu shift.
        $this->assertEqualsCanonicalizing(
            [2, 1],
            $operations->map(fn (ShipOperation $o): int => MaterialActivity::where('ship_operation_id', $o->id)->count())->all(),
        );
    }

    /**
     * Form bongkar harus benar-benar mengirim kolom operasi kapalnya, kalau
     * tidak seluruh mekanisme di belakangnya tidak pernah terpanggil.
     */
    public function test_form_bongkar_merender_kolom_operasi_kapal(): void
    {
        $html = $this->actingAs($this->operator())
            ->get(route('report-ops.create'))
            ->assertOk()
            ->getContent();

        foreach ([
            'name="ship_operation_material_id_1"',
            'name="ship_operation_material_status_1"',
            'name="ship_operation_container_id_1"',
            'name="ship_operation_container_status_1"',
        ] as $field) {
            $this->assertStringContainsString($field, $html);
        }
    }

    public function test_saran_kapal_melayani_jenis_bongkar(): void
    {
        $this->submit('2026-05-19', 'Pagi', 'A', [
            'ship_name_material_1' => 'MV. Sumber Rezeki',
            'capacity_material_1' => '8000',
            'unloading_materials_1' => [['raw_material_type' => 'Clay JB', 'qty_current' => '500']],
        ]);

        // Kata kunci diketik dengan ejaan lain — pencarian kanonik harus tetap
        // menemukannya, supaya petugas memilih dari saran alih-alih mengetik
        // nama baru.
        $response = $this->actingAs($this->operator())
            ->getJson(route('report-ops.ship-operations.suggestions', [
                'type' => ShipOperation::TYPE_MATERIAL_UNLOADING,
                'q' => 'sumber-rezeki',
            ]))
            ->assertOk();

        $this->assertSame('MV. Sumber Rezeki', $response->json('items.0.ship_name'));
    }

    /**
     * @param  array<string, mixed>  $fields
     */
    private function submit(string $date, string $shift, string $group, array $fields): void
    {
        Carbon::setTestNow(Carbon::parse($date)->setTime(23, 30));

        foreach ([
            'ship_name_1' => 'ship_operation_status_1',
            'ship_name_urea_1' => 'ship_operation_urea_status_1',
            'ship_name_ammonia_1' => 'ship_operation_ammonia_status_1',
            'ship_name_material_1' => 'ship_operation_material_status_1',
            'ship_name_container_1' => 'ship_operation_container_status_1',
        ] as $shipField => $statusField) {
            if (filled($fields[$shipField] ?? null) && ! array_key_exists($statusField, $fields)) {
                $fields[$statusField] = ShipOperation::STATUS_ACTIVE;
            }
        }

        $this->actingAs($this->operator())
            ->post(route('report-ops.store'), array_merge([
                'status' => 'submitted',
                'report_date' => $date,
                'shift' => $shift,
                'group_name' => $group,
                'received_by_group' => $group === 'A' ? 'B' : 'A',
                'time_range' => '07.00 - 15.00',
                'confirm_duplicate' => '1',
            ], $fields))
            ->assertRedirect(route('report-ops.index'));

        Carbon::setTestNow();
    }

    private function operator(): User
    {
        return User::firstOrCreate(
            ['username' => 'petugas-bongkar'],
            [
                'name' => 'Petugas Bongkar',
                'email' => 'petugas-bongkar@example.com',
                'password' => 'password',
                'status' => 'aktif',
                'group' => 'A',
            ],
        );
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }
}
