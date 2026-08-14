<?php

namespace Tests\Feature;

use App\Models\ContainerActivity;
use App\Models\ContainerItem;
use App\Models\DailyReport;
use App\Models\MaterialActivity;
use App\Models\MaterialItem;
use App\Models\ShipOperation;
use App\Models\TurbaDelivery;
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
     *
     * Jedanya sengaja melewati REOPEN_COMPLETED_WITHIN_DAYS. Di dalam ambang itu
     * tanda selesai dianggap kelewat cepat dan operasinya justru disambung
     * kembali — lihat test_selesai_yang_kelewat_cepat_tidak_memecah_satu_kunjungan.
     * Tes ini menjaga sisi seberangnya: sehari lewat ambang, kapal yang sudah
     * berangkat tidak boleh lagi menarik kunjungan berikutnya.
     */
    public function test_kapal_yang_sudah_selesai_tidak_menarik_kunjungan_berikutnya(): void
    {
        $this->submit('2026-05-19', 'Pagi', 'A', [
            'ship_name_container_1' => 'KM. Ayer Mas',
            'capacity_container_1' => '120',
            'ship_operation_container_status_1' => ShipOperation::STATUS_COMPLETED,
            'unloading_containers_1' => [['status' => 'Empty', 'qty_current' => '20']],
        ]);

        $this->submit('2026-05-22', 'Pagi', 'C', [
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
     * Bagian yang tidak pernah disentuh petugas tidak boleh ikut tersimpan.
     *
     * Form menyediakan satu baris rincian siap-isi yang kolom angkanya
     * terkirim sebagai "0". Selama nol dianggap isian, bagian Bahan Baku yang
     * kosong tetap tersimpan sebagai kegiatan tanpa nama kapal — dan pada
     * rekap bulanan kegiatan tanpa nama itu ikut terhitung sebagai satu kapal
     * bertonase nol. Persis yang terjadi pada laporan 4, 5, dan 6 Agustus 2026,
     * yang membuat Bongkar Bahan Baku melaporkan dua kapal padahal yang
     * bersandar hanya satu.
     */
    public function test_bagian_kosong_tidak_tersimpan_sebagai_kegiatan(): void
    {
        $this->submit('2026-05-19', 'Pagi', 'A', [
            // Bahan Baku: tidak disentuh sama sekali, hanya baris siap-isi.
            'ship_name_material_1' => '',
            'agent_material_1' => '',
            'unloading_materials_1' => [
                ['raw_material_type' => '', 'qty_current' => '0', 'qty_prev' => '0', 'qty_total' => ''],
            ],

            // Trucking: juga tidak disentuh.
            'turba_deliveries' => [
                ['truck_name' => '', 'do_so_number' => '', 'qty_current' => '0', 'qty_prev' => '0'],
            ],

            // Container: benar-benar dikerjakan, harus tetap tersimpan utuh.
            'ship_name_container_1' => 'KM. Ayer Mas',
            'capacity_container_1' => '120',
            'unloading_containers_1' => [
                ['status' => 'Empty', 'qty_current' => '20'],
                ['status' => '', 'qty_current' => '0', 'qty_prev' => '0', 'qty_total' => ''],
            ],
        ]);

        $this->assertSame(0, MaterialActivity::count(), 'Bagian Bahan Baku yang kosong ikut tersimpan.');
        $this->assertSame(0, MaterialItem::count());
        $this->assertSame(0, TurbaDelivery::count(), 'Rit hampa ikut tersimpan dan menggelembungkan jumlah Rit.');

        $this->assertSame(1, ContainerActivity::count(), 'Bagian yang benar-benar dikerjakan justru ikut terbuang.');
        $this->assertSame(1, ContainerItem::count(), 'Hanya baris yang berisi yang boleh tersimpan.');
        $this->assertSame(20.0, (float) ContainerItem::firstOrFail()->qty_current);
    }

    /**
     * Nol yang memang dicatat petugas tetap tersimpan. Pembedanya adalah
     * keterangan: baris yang menyebut APA yang nol jelas sebuah catatan,
     * sedangkan baris tanpa keterangan dan tanpa angka bukan apa-apa.
     */
    public function test_nol_yang_diberi_keterangan_tetap_tersimpan(): void
    {
        $this->submit('2026-05-19', 'Pagi', 'A', [
            'ship_name_material_1' => 'MV. Sumber Rezeki',
            'capacity_material_1' => '8000',
            'unloading_materials_1' => [
                ['raw_material_type' => 'Limestone', 'qty_current' => '0', 'qty_prev' => '439', 'qty_total' => '439'],
                ['raw_material_type' => 'Clay JB', 'qty_current' => '0', 'qty_prev' => '0', 'qty_total' => '0'],
            ],
        ]);

        $this->assertSame(1, MaterialActivity::count());
        $this->assertSame(2, MaterialItem::count(), 'Catatan nol yang menyebut jenis bahannya ikut terbuang.');
    }

    /**
     * Tanda "Selesai" yang kelewat cepat tidak boleh memecah satu kunjungan.
     *
     * Shift Sore menutup pekerjaan, lalu shift Malam hari yang sama ternyata
     * masih membongkar. Selama operasi selesai dikeluarkan dari kandidat
     * pencocokan, laporan lanjutan itu membentuk operasi BARU untuk kapal yang
     * sama — dan rekap melaporkan dua kapal padahal yang bersandar satu.
     * Persis yang terjadi pada KM. Hasil Bahari 8, 12-13 Agustus 2026.
     */
    public function test_selesai_yang_kelewat_cepat_tidak_memecah_satu_kunjungan(): void
    {
        $this->submit('2026-05-19', 'Pagi', 'A', [
            'ship_name_material_1' => 'KM. Hasil Bahari 8',
            'capacity_material_1' => '4750',
            'unloading_materials_1' => [['raw_material_type' => 'Clay', 'qty_current' => '110']],
        ]);

        // Shift Sore menandai selesai — padahal bongkarnya belum rampung.
        $this->submit('2026-05-19', 'Sore', 'B', [
            'ship_name_material_1' => 'KM. Hasil Bahari 8',
            'capacity_material_1' => '4750',
            'ship_operation_material_status_1' => ShipOperation::STATUS_COMPLETED,
            'unloading_materials_1' => [['raw_material_type' => 'Clay', 'qty_current' => '130']],
        ]);

        $this->assertSame(1, ShipOperation::where('type', ShipOperation::TYPE_MATERIAL_UNLOADING)->count());

        // Shift Malam meneruskan, mengetik namanya dengan ejaan lain dan tanpa
        // memilih dari saran — persis seperti di lapangan.
        $this->submit('2026-05-19', 'Malam', 'C', [
            'ship_name_material_1' => 'KM.HASIL BAHARI.8',
            'capacity_material_1' => '4750',
            'unloading_materials_1' => [['raw_material_type' => 'Mgo', 'qty_current' => '4700']],
        ]);

        $operations = ShipOperation::where('type', ShipOperation::TYPE_MATERIAL_UNLOADING)->get();

        $this->assertCount(1, $operations, 'Satu kunjungan kapal terpecah menjadi dua operasi.');
        $this->assertSame(ShipOperation::STATUS_ACTIVE, $operations->first()->status, 'Operasi harus kembali berjalan.');
        $this->assertNull($operations->first()->completed_at);
        $this->assertSame(3, MaterialActivity::where('ship_operation_id', $operations->first()->id)->count());
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
            'data-material-package-code="jumbo_1000"',
            'data-material-package-code="bag_50"',
            'data-material-package-family="jumbo"',
            'data-material-package-family="bag"',
            'data-package-family="jumbo"',
            'data-package-family="bag"',
            'name="unloading_materials_1[0][packaging_code]"',
            'name="unloading_materials_1[1][packaging_code]"',
            'name="unloading_materials_1[0][packaging_type]"',
            'name="unloading_materials_1[1][packaging_type]"',
            'value="jumbo_1000"',
            'value="bag_50"',
            // Kemasan yang jarang dipakai tetap tersedia sebagai pilihan,
            // bukan sebagai kelompok yang selalu tampil.
            'value="jumbo_1500"',
            'value="bag_25"',
            'Kemasan Jumbo Bag 1 Ton',
            'Kemasan Jumbo Bag 1,5 Ton',
            'Kemasan Bag 50 Kg',
            'Kemasan Bag 25 Kg',
            'data-package-factor="1"',
            'data-package-factor="1.5"',
            'data-package-factor="0.05"',
            'data-package-factor="0.025"',
            'data-material-package-add',
            'data-material-package-remove',
            'data-material-package-defaults="jumbo_1000,bag_50"',
            // Pilihan terakhir dropdown beserta pop-up pendaftarannya.
            'data-material-package-new',
            // Kelompok kemasan dapat diciutkan, mengikuti akordeon lokasi K3.
            'data-material-package-toggle',
            'data-material-package-body',
            'data-material-package-summary',
            'data-material-package-rowcount',
            'Tambah Kemasan Baru',
            'id="materialPackageModal"',
            'id="materialPackageBags"',
            'id="materialPackageTons"',
            // Dropdown kemasan memakai kontrol select yang sama dengan
            // isian lain pada form ini.
            'class="custom-input material-package-native-select" data-material-package-select',
            'quantity-input__unit">Bag',
            'quantity-input__unit">Teus',
            'material-package-subtotal__value',
        ] as $field) {
            $this->assertStringContainsString($field, $html);
        }

        // Keterangan konversi cukup satu kali, pada pil di samping nama
        // kemasannya; kalimat pengulangnya sudah dihapus.
        $this->assertStringNotContainsString('1 Jumbo Bag setara 1 Ton.', $html);
        $this->assertStringNotContainsString('20 Bag 50 Kg setara 1 Ton.', $html);
        $this->assertStringNotContainsString('data-material-package-input', $html);
        $this->assertStringNotContainsString('Contoh: Jumbo Bag', $html);
        $this->assertStringNotContainsString('Sekarang <small>Ton</small>', $html);
        $this->assertStringNotContainsString('Bag → Ton</small>', $html);
        $this->assertStringNotContainsString('data-material-tonnage=', $html);

        $materialCatalog = app(\App\Services\OperationalPerformanceService::class)
            ->activityCatalog()['bongkar_bahan_baku'];
        $this->assertSame('Ton', $materialCatalog['unit']);
        $this->assertTrue($materialCatalog['countsToTonnage']);
    }

    public function test_dua_kemasan_bahan_baku_disimpan_terpisah(): void
    {
        $this->submit('2026-05-19', 'Pagi', 'A', [
            'ship_name_material_1' => 'KM. Hasil Bahari 8',
            'capacity_material_1' => '4750',
            'unloading_materials_1' => [
                [
                    'packaging_code' => 'jumbo_1000',
                    'raw_material_type' => 'Clay',
                    'qty_current' => '200',
                    'qty_prev' => '300',
                    'qty_total' => '500',
                ],
                [
                    'packaging_code' => 'bag_50',
                    'raw_material_type' => 'MgO 18%',
                    'qty_current' => '1500',
                    'qty_prev' => '2000',
                    'qty_total' => '3500',
                ],
            ],
        ]);

        $this->assertEqualsCanonicalizing(
            ['Jumbo Bag 1 Ton', 'Bag 50 Kg'],
            MaterialItem::pluck('packaging_type')->all(),
        );
        $this->assertSame(200.0, (float) MaterialItem::where('packaging_code', 'jumbo_1000')->firstOrFail()->qty_current);
        $this->assertSame(1500.0, (float) MaterialItem::where('packaging_code', 'bag_50')->firstOrFail()->qty_current);

        $detail = app(\App\Services\OperationalPerformanceService::class)->activityDetail('bongkar_bahan_baku', [
            'start' => Carbon::parse('2026-05-19')->startOfDay(),
            'end' => Carbon::parse('2026-05-19')->endOfDay(),
            'group' => null,
            'shift' => null,
        ]);
        $this->assertSame(275.0, (float) $detail['value']);
    }

    /**
     * Kemasan yang jarang dipakai harus terhitung sama benarnya dengan dua
     * kemasan utama: 200 Jumbo Bag 1,5 Ton = 300 Ton dan 800 Bag 25 Kg = 20 Ton.
     */
    public function test_kemasan_tambahan_dikonversi_sesuai_katalog(): void
    {
        $this->submit('2026-05-19', 'Pagi', 'A', [
            'ship_name_material_1' => 'KM. Hasil Bahari 8',
            'capacity_material_1' => '4750',
            'unloading_materials_1' => [
                ['packaging_code' => 'jumbo_1500', 'raw_material_type' => 'Clay', 'qty_current' => '200'],
                ['packaging_code' => 'bag_25', 'raw_material_type' => 'MgO 18%', 'qty_current' => '800'],
            ],
        ]);

        $this->assertEqualsCanonicalizing(
            ['Jumbo Bag 1,5 Ton', 'Bag 25 Kg'],
            MaterialItem::pluck('packaging_type')->all(),
        );

        $detail = app(\App\Services\OperationalPerformanceService::class)->activityDetail('bongkar_bahan_baku', [
            'start' => Carbon::parse('2026-05-19')->startOfDay(),
            'end' => Carbon::parse('2026-05-19')->endOfDay(),
            'group' => null,
            'shift' => null,
        ]);
        $this->assertSame(320.0, (float) $detail['value']);
    }

    /**
     * Faktor konversi dibekukan pada barisnya. Inilah yang membuat tonase
     * laporan lama tidak ikut bergeser bila katalog kemasan disunting.
     */
    public function test_faktor_konversi_ikut_tersimpan_pada_baris(): void
    {
        $this->submit('2026-05-19', 'Pagi', 'A', [
            'ship_name_material_1' => 'KM. Hasil Bahari 8',
            'capacity_material_1' => '4750',
            'unloading_materials_1' => [
                ['packaging_code' => 'jumbo_1500', 'raw_material_type' => 'Clay', 'qty_current' => '200'],
                ['packaging_code' => 'bag_25', 'raw_material_type' => 'MgO 18%', 'qty_current' => '800'],
            ],
        ]);

        $this->assertSame(1.5, (float) MaterialItem::where('packaging_code', 'jumbo_1500')->firstOrFail()->packaging_factor);
        $this->assertSame(0.025, (float) MaterialItem::where('packaging_code', 'bag_25')->firstOrFail()->packaging_factor);
    }

    /**
     * Faktor tidak boleh datang dari kiriman form. Kalau bisa, tonase laporan
     * bisa dikarang tanpa mengubah satu pun angka yang tampil.
     */
    public function test_faktor_kiriman_form_diabaikan(): void
    {
        $this->submit('2026-05-19', 'Pagi', 'A', [
            'ship_name_material_1' => 'KM. Hasil Bahari 8',
            'capacity_material_1' => '4750',
            'unloading_materials_1' => [
                [
                    'packaging_code' => 'bag_50',
                    'packaging_type' => 'Jumbo Bag 1,5 Ton',
                    'packaging_factor' => '99',
                    'raw_material_type' => 'MgO 18%',
                    'qty_current' => '1500',
                ],
            ],
        ]);

        $item = MaterialItem::firstOrFail();
        $this->assertSame('bag_50', $item->packaging_code);
        $this->assertSame('Bag 50 Kg', $item->packaging_type);
        $this->assertSame(0.05, (float) $item->packaging_factor);
    }

    public function test_laporan_bahan_baku_menampilkan_kolom_bag_dan_ton_seperti_format_operasional(): void
    {
        $this->submit('2026-05-19', 'Pagi', 'A', [
            'ship_name_material_1' => 'KM. Hasil Bahari 8',
            'agent_material_1' => 'PT. NDSB',
            'jetty_material_1' => 'IV Tursina',
            'capacity_material_1' => '4700',
            'unloading_materials_1' => [
                ['packaging_code' => 'jumbo_1000', 'raw_material_type' => 'Clay', 'qty_current' => '200', 'qty_prev' => '300', 'qty_total' => '500'],
                ['packaging_code' => 'jumbo_1000', 'raw_material_type' => 'Limestone', 'qty_current' => '150', 'qty_prev' => '25', 'qty_total' => '175'],
                ['packaging_code' => 'bag_50', 'raw_material_type' => 'MgO 18%', 'qty_current' => '1500', 'qty_prev' => '2000', 'qty_total' => '3500'],
                ['packaging_code' => 'bag_50', 'raw_material_type' => 'MgO 10%', 'qty_current' => '1000', 'qty_prev' => '500', 'qty_total' => '1500'],
            ],
        ]);

        $report = DailyReport::latest('id')->firstOrFail();
        $html = view('report-ops.partials.report-paper', [
            'report' => $report,
            'isPdf' => false,
        ])->render();

        $this->assertStringContainsString('material-report-grid', $html);
        $this->assertStringContainsString('colspan="2" rowspan="2">JENIS', $html);
        $this->assertStringContainsString('material-report-col--number" style="width:4%"', $html);
        $this->assertStringContainsString('material-report-col--name" style="width:27%"', $html);
        $this->assertStringContainsString('<th colspan="2">SEKARANG</th>', $html);
        $this->assertStringContainsString('<th>BAG</th><th>TON</th>', $html);
        $this->assertStringContainsString('Jumbo Bag 1 Ton', $html);
        $this->assertStringContainsString('Bag 50 Kg', $html);
        $this->assertStringContainsString('2,500', $html);
        $this->assertStringContainsString('125', $html);
        $this->assertSame(2, substr_count($html, 'class="material-report-total"'));
        $this->assertStringNotContainsString('(BAG / TON)', $html);
        $this->assertStringNotContainsString('dual-qty', $html);
    }

    public function test_jumlah_bahan_baku_harus_berupa_bilangan_bulat_bag(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-19')->setTime(23, 30));

        $this->actingAs($this->operator())
            ->post(route('report-ops.store'), [
                'status' => 'submitted',
                'report_date' => '2026-05-19',
                'shift' => 'Pagi',
                'group_name' => 'A',
                'received_by_group' => 'B',
                'time_range' => '07.00 - 15.00',
                'confirm_duplicate' => '1',
                'ship_name_material_1' => 'KM. Hasil Bahari 8',
                'ship_operation_material_status_1' => ShipOperation::STATUS_ACTIVE,
                'unloading_materials_1' => [
                    ['packaging_code' => 'jumbo_1000', 'raw_material_type' => 'Clay', 'qty_current' => '10.5'],
                    ['packaging_code' => 'bag_50', 'raw_material_type' => 'MgO 18%', 'qty_current' => '20'],
                ],
            ])
            ->assertSessionHasErrors('unloading_materials_1.0.qty_current');

        $this->assertSame(0, MaterialItem::count());
    }

    /**
     * Satu shift boleh saja hanya membongkar satu kemasan. Aturan lama yang
     * mewajibkan dua kategori terisi akan menolak laporan yang sah ini.
     */
    public function test_laporan_baru_dengan_satu_kemasan_diterima(): void
    {
        $this->submit('2026-05-19', 'Pagi', 'A', [
            'ship_name_material_1' => 'KM. Hasil Bahari 8',
            'capacity_material_1' => '4750',
            'unloading_materials_1' => [
                ['packaging_code' => 'bag_25', 'raw_material_type' => 'MgO 18%', 'qty_current' => '800'],
                ['packaging_code' => 'bag_50', 'raw_material_type' => '', 'qty_current' => '0'],
            ],
        ]);

        $this->assertSame(1, MaterialItem::count());
        $this->assertSame('bag_25', MaterialItem::firstOrFail()->packaging_code);
    }

    /**
     * Dua kelompok berkemasan sama membuat subtotal laporan terbaca ganda.
     */
    public function test_kelompok_kemasan_kembar_ditolak(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-19')->setTime(23, 30));

        $this->actingAs($this->operator())
            ->post(route('report-ops.store'), [
                'status' => 'submitted',
                'report_date' => '2026-05-19',
                'shift' => 'Pagi',
                'group_name' => 'A',
                'received_by_group' => 'B',
                'time_range' => '07.00 - 15.00',
                'confirm_duplicate' => '1',
                'ship_name_material_1' => 'KM. Hasil Bahari 8',
                'ship_operation_material_status_1' => ShipOperation::STATUS_ACTIVE,
                'unloading_materials_1' => [
                    ['packaging_code' => 'jumbo_1000', 'raw_material_type' => 'Clay', 'qty_current' => '200'],
                    ['packaging_code' => 'bag_50', 'raw_material_type' => 'MgO 18%', 'qty_current' => '1500'],
                    ['packaging_code' => 'jumbo_1000', 'raw_material_type' => 'Limestone', 'qty_current' => '150'],
                ],
            ])
            ->assertSessionHasErrors('unloading_materials_1');

        Carbon::setTestNow();
        $this->assertSame(0, MaterialActivity::count());
        $this->assertSame(0, MaterialItem::count());
    }

    public function test_kemasan_di_luar_katalog_ditolak(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-19')->setTime(23, 30));

        $this->actingAs($this->operator())
            ->post(route('report-ops.store'), [
                'status' => 'submitted',
                'report_date' => '2026-05-19',
                'shift' => 'Pagi',
                'group_name' => 'A',
                'received_by_group' => 'B',
                'time_range' => '07.00 - 15.00',
                'confirm_duplicate' => '1',
                'ship_name_material_1' => 'KM. Hasil Bahari 8',
                'ship_operation_material_status_1' => ShipOperation::STATUS_ACTIVE,
                'unloading_materials_1' => [
                    ['packaging_code' => 'bag_10', 'raw_material_type' => 'Clay', 'qty_current' => '200'],
                ],
            ])
            ->assertSessionHasErrors('unloading_materials_1.0.packaging_code');

        Carbon::setTestNow();
        $this->assertSame(0, MaterialItem::count());
    }

    /**
     * Kemasan di luar katalog didaftarkan petugas sendiri lewat pop-up pada
     * dropdown, dan hanya berlaku untuk laporan yang sedang diisi.
     */
    public function test_kemasan_tambahan_dari_petugas_tersimpan_beserta_faktornya(): void
    {
        $this->submit('2026-05-19', 'Pagi', 'A', [
            'ship_name_material_1' => 'KM. Hasil Bahari 8',
            'capacity_material_1' => '4750',
            'unloading_materials_1' => [
                [
                    'packaging_code' => 'custom',
                    'packaging_type' => 'Bag 40 Kg',
                    'packaging_factor' => '0.04',
                    'raw_material_type' => 'MgO 18%',
                    'qty_current' => '500',
                ],
                [
                    'packaging_code' => 'jumbo_1000',
                    'raw_material_type' => 'Clay',
                    'qty_current' => '100',
                ],
            ],
        ]);

        $item = MaterialItem::where('packaging_code', 'custom')->firstOrFail();
        $this->assertSame('Bag 40 Kg', $item->packaging_type);
        $this->assertSame(0.04, (float) $item->packaging_factor);

        // 500 × 0,04 = 20 Ton, ditambah 100 Ton dari Jumbo Bag.
        $detail = app(\App\Services\OperationalPerformanceService::class)->activityDetail('bongkar_bahan_baku', [
            'start' => Carbon::parse('2026-05-19')->startOfDay(),
            'end' => Carbon::parse('2026-05-19')->endOfDay(),
            'group' => null,
            'shift' => null,
        ]);
        $this->assertSame(120.0, (float) $detail['value']);
    }

    public function test_dua_kemasan_tambahan_berbeda_nama_diterima(): void
    {
        $this->submit('2026-05-19', 'Pagi', 'A', [
            'ship_name_material_1' => 'KM. Hasil Bahari 8',
            'capacity_material_1' => '4750',
            'unloading_materials_1' => [
                ['packaging_code' => 'custom', 'packaging_type' => 'Karung Goni', 'packaging_factor' => '0.06', 'raw_material_type' => 'Clay', 'qty_current' => '100'],
                ['packaging_code' => 'custom', 'packaging_type' => 'Karung Plastik', 'packaging_factor' => '0.08', 'raw_material_type' => 'MgO 18%', 'qty_current' => '200'],
            ],
        ]);

        $this->assertEqualsCanonicalizing(
            ['Karung Goni', 'Karung Plastik'],
            MaterialItem::pluck('packaging_type')->all(),
        );
    }

    public function test_kelompok_kemasan_tambahan_bernama_sama_ditolak(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-19')->setTime(23, 30));

        $this->actingAs($this->operator())
            ->post(route('report-ops.store'), [
                'status' => 'submitted',
                'report_date' => '2026-05-19',
                'shift' => 'Pagi',
                'group_name' => 'A',
                'received_by_group' => 'B',
                'time_range' => '07.00 - 15.00',
                'confirm_duplicate' => '1',
                'ship_name_material_1' => 'KM. Hasil Bahari 8',
                'ship_operation_material_status_1' => ShipOperation::STATUS_ACTIVE,
                'unloading_materials_1' => [
                    ['packaging_code' => 'custom', 'packaging_type' => 'Karung Goni', 'packaging_factor' => '0.06', 'raw_material_type' => 'Clay', 'qty_current' => '100'],
                    ['packaging_code' => 'jumbo_1000', 'raw_material_type' => 'Limestone', 'qty_current' => '50'],
                    ['packaging_code' => 'custom', 'packaging_type' => 'Karung Goni', 'packaging_factor' => '0.06', 'raw_material_type' => 'MgO 18%', 'qty_current' => '200'],
                ],
            ])
            ->assertSessionHasErrors('unloading_materials_1');

        Carbon::setTestNow();
        $this->assertSame(0, MaterialItem::count());
    }

    public function test_kemasan_tambahan_tanpa_faktor_wajar_ditolak(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-19')->setTime(23, 30));

        $this->actingAs($this->operator())
            ->post(route('report-ops.store'), [
                'status' => 'submitted',
                'report_date' => '2026-05-19',
                'shift' => 'Pagi',
                'group_name' => 'A',
                'received_by_group' => 'B',
                'time_range' => '07.00 - 15.00',
                'confirm_duplicate' => '1',
                'ship_name_material_1' => 'KM. Hasil Bahari 8',
                'ship_operation_material_status_1' => ShipOperation::STATUS_ACTIVE,
                'unloading_materials_1' => [
                    ['packaging_code' => 'custom', 'packaging_type' => 'Karung Goni', 'packaging_factor' => '0', 'raw_material_type' => 'Clay', 'qty_current' => '100'],
                ],
            ])
            ->assertSessionHasErrors('unloading_materials_1.0.packaging_factor');

        Carbon::setTestNow();
        $this->assertSame(0, MaterialItem::count());
    }

    /**
     * Kemasan tambahan yang memakai nama kemasan katalog harus jatuh ke
     * katalog, bukan memakai faktor yang diketik ulang petugas.
     */
    public function test_kemasan_tambahan_bernama_katalog_memakai_faktor_katalog(): void
    {
        $this->submit('2026-05-19', 'Pagi', 'A', [
            'ship_name_material_1' => 'KM. Hasil Bahari 8',
            'capacity_material_1' => '4750',
            'unloading_materials_1' => [
                ['packaging_code' => 'custom', 'packaging_type' => 'Bag 50 Kg', 'packaging_factor' => '9', 'raw_material_type' => 'MgO 18%', 'qty_current' => '1500'],
            ],
        ]);

        $item = MaterialItem::firstOrFail();
        $this->assertSame('bag_50', $item->packaging_code);
        $this->assertSame(0.05, (float) $item->packaging_factor);
    }

    /**
     * Kiriman lama hanya membawa label kemasan, dan label "Jumbo Bag" dari
     * sebelum ukuran 1,5 Ton ada tetap harus jatuh ke Jumbo Bag 1 Ton.
     */
    public function test_kiriman_label_kemasan_lama_tetap_dikenali(): void
    {
        $this->submit('2026-05-19', 'Pagi', 'A', [
            'ship_name_material_1' => 'KM. Hasil Bahari 8',
            'capacity_material_1' => '4750',
            'unloading_materials_1' => [
                ['packaging_type' => 'Jumbo Bag', 'raw_material_type' => 'Clay', 'qty_current' => '200'],
                ['packaging_type' => 'Bag 50 Kg', 'raw_material_type' => 'MgO 18%', 'qty_current' => '1500'],
            ],
        ]);

        $item = MaterialItem::where('raw_material_type', 'Clay')->firstOrFail();
        $this->assertSame('jumbo_1000', $item->packaging_code);
        $this->assertSame('Jumbo Bag 1 Ton', $item->packaging_type);
        $this->assertSame(1.0, (float) $item->packaging_factor);
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
     * Satu kapal dibongkar lintas shift. Regu berikutnya harus menerima jenis
     * bahan, kemasan, dan akumulasi terakhirnya — bukan form kosong yang harus
     * diketik ulang.
     */
    public function test_saran_kapal_meneruskan_rincian_bahan_baku_ke_regu_berikutnya(): void
    {
        $this->submit('2026-05-19', 'Pagi', 'A', [
            'ship_name_material_1' => 'MV. Sumber Rezeki',
            'capacity_material_1' => '8000',
            'ship_operation_material_status_1' => ShipOperation::STATUS_ACTIVE,
            'unloading_materials_1' => [
                ['packaging_code' => 'jumbo_1000', 'raw_material_type' => 'Clay JB', 'qty_current' => '200', 'qty_prev' => '300'],
                ['packaging_code' => 'bag_50', 'raw_material_type' => 'MgO 18%', 'qty_current' => '1500', 'qty_prev' => '2000'],
                ['packaging_code' => 'bag_50', 'raw_material_type' => 'MgO 10%', 'qty_current' => '500', 'qty_prev' => '0'],
            ],
        ]);

        $materials = $this->actingAs($this->operator())
            ->getJson(route('report-ops.ship-operations.suggestions', [
                'type' => ShipOperation::TYPE_MATERIAL_UNLOADING,
                'q' => 'sumber rezeki',
            ]))
            ->assertOk()
            ->json('items.0.accumulation.materials');

        $this->assertCount(3, $materials);

        // Jenis bahan dan kemasannya diteruskan apa adanya.
        $this->assertSame('Clay JB', $materials[0]['raw_material_type']);
        $this->assertSame('jumbo_1000', $materials[0]['packaging_code']);
        $this->assertSame('Jumbo Bag 1 Ton', $materials[0]['packaging_type']);
        $this->assertSame(1.0, (float) $materials[0]['packaging_factor']);
        $this->assertSame('bag_50', $materials[2]['packaging_code']);

        // "Lalu" regu berikutnya = akumulasi terakhir (Sekarang + Lalu).
        $this->assertSame(500.0, (float) $materials[0]['qty_prev']);
        $this->assertSame(3500.0, (float) $materials[1]['qty_prev']);
        $this->assertSame(500.0, (float) $materials[2]['qty_prev']);
    }

    public function test_penerusan_bahan_baku_membawa_kemasan_tambahan_beserta_faktornya(): void
    {
        $this->submit('2026-05-19', 'Pagi', 'A', [
            'ship_name_material_1' => 'MV. Sumber Rezeki',
            'capacity_material_1' => '8000',
            'ship_operation_material_status_1' => ShipOperation::STATUS_ACTIVE,
            'unloading_materials_1' => [
                ['packaging_code' => 'custom', 'packaging_type' => 'Bag 40 Kg', 'packaging_factor' => '0.04', 'raw_material_type' => 'MgO 18%', 'qty_current' => '500'],
            ],
        ]);

        $materials = $this->actingAs($this->operator())
            ->getJson(route('report-ops.ship-operations.suggestions', [
                'type' => ShipOperation::TYPE_MATERIAL_UNLOADING,
                'q' => 'sumber rezeki',
            ]))
            ->assertOk()
            ->json('items.0.accumulation.materials');

        $this->assertSame('custom', $materials[0]['packaging_code']);
        $this->assertSame('Bag 40 Kg', $materials[0]['packaging_type']);
        $this->assertSame(0.04, (float) $materials[0]['packaging_factor']);
    }

    /**
     * Baris kosong yang ikut terkirim form tidak boleh menjadi baris warisan
     * pada laporan regu berikutnya.
     */
    public function test_penerusan_bahan_baku_melewati_baris_tanpa_isi(): void
    {
        $this->submit('2026-05-19', 'Pagi', 'A', [
            'ship_name_material_1' => 'MV. Sumber Rezeki',
            'capacity_material_1' => '8000',
            'ship_operation_material_status_1' => ShipOperation::STATUS_ACTIVE,
            'unloading_materials_1' => [
                ['packaging_code' => 'jumbo_1000', 'raw_material_type' => 'Clay JB', 'qty_current' => '200'],
                ['packaging_code' => 'bag_50', 'raw_material_type' => '', 'qty_current' => '0'],
            ],
        ]);

        $materials = $this->actingAs($this->operator())
            ->getJson(route('report-ops.ship-operations.suggestions', [
                'type' => ShipOperation::TYPE_MATERIAL_UNLOADING,
                'q' => 'sumber rezeki',
            ]))
            ->assertOk()
            ->json('items.0.accumulation.materials');

        $this->assertCount(1, $materials);
        $this->assertSame('Clay JB', $materials[0]['raw_material_type']);
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
