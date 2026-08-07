<?php

namespace Tests\Feature;

use App\Models\ContainerItem;
use App\Models\DailyReport;
use App\Models\User;
use App\Services\OperationalPerformanceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Bongkar Container pernah tercatat 112 Teus padahal 275 Teus.
 *
 * Penyebabnya kolom penanda Empty/Full berupa teks bebas, sementara laporan
 * manajer memilah kegiatan dengan pencocokan kata persis. Baris yang ditulis
 * "Container empty" atau "Empty Container" tidak masuk kegiatan mana pun.
 *
 * Angka pada berkas ini disalin apa adanya dari laporan MV. Curug Mas,
 * 3-5 Agustus 2026, supaya keluhan yang sesungguhnya ikut terkunci di sini.
 */
class ContainerStatusIntegrityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Sembilan baris container sebagaimana benar-benar diketik petugas.
     *
     * @var array<int, array{0: string, 1: string, 2: string, 3: string, 4: string}>
     */
    private const LAPORAN_AGUSTUS = [
        // tanggal, shift, regu, penanda yang diketik, jumlah
        ['2026-08-03', 'Sore', 'D', 'Empty', '15'],
        ['2026-08-03', 'Malam', 'C', 'Empty', '97'],
        ['2026-08-04', 'Pagi', 'B', 'Empty Container', '70'],
        ['2026-08-04', 'Sore', 'A', 'Container empty', '93'],
        ['2026-08-04', 'Malam', 'D', 'Contaner Isi', '30'],
        ['2026-08-05', 'Pagi', 'B', 'Full', '48'],
        ['2026-08-05', 'Sore', 'A', 'Muat container isi', '46'],
        ['2026-08-05', 'Malam', 'D', 'Coutener isi', '71'],
    ];

    public function test_penanda_bebas_diseragamkan_saat_laporan_disimpan(): void
    {
        $this->submit('2026-08-04', 'Pagi', 'B', [
            'ship_name_container_1' => 'Mv. Curug Mas',
            'capacity_container_1' => '275',
            'capacity_full_container_1' => '220',
            'unloading_containers_1' => [
                ['status' => 'Empty Container', 'qty_current' => '70'],
                ['status' => 'Coutener isi', 'qty_current' => '30'],
            ],
        ]);

        $this->assertSame(
            ['Empty', 'Full'],
            ContainerItem::orderBy('id')->pluck('status')->all(),
            'Penanda bebas harus tersimpan sebagai Empty/Full, bukan apa adanya.',
        );
    }

    /**
     * Penanda yang memang tidak dapat dipastikan tidak boleh ditebak menjadi
     * salah satu kategori — barisnya disimpan tanpa penanda, lalu dilaporkan.
     */
    public function test_penanda_yang_tidak_jelas_disimpan_kosong_bukan_ditebak(): void
    {
        $this->submit('2026-08-04', 'Pagi', 'B', [
            'ship_name_container_1' => 'Mv. Curug Mas',
            'capacity_container_1' => '275',
            'unloading_containers_1' => [
                ['status' => 'Hujan deras', 'qty_current' => '40'],
            ],
        ]);

        $this->assertNull(ContainerItem::firstOrFail()->status);
    }

    /**
     * Inti keluhan Pak Mustari: bulan berjalan harus berbunyi 275 Teus, bukan
     * 112 Teus. Muat Container ikut diperiksa karena rusak oleh sebab yang sama.
     */
    public function test_rekap_bulan_berjalan_memakai_seluruh_baris_container(): void
    {
        foreach (self::LAPORAN_AGUSTUS as [$date, $shift, $group, $status, $qty]) {
            $this->submit($date, $shift, $group, [
                'ship_name_container_1' => 'MV. Curug Mas',
                'capacity_container_1' => '275',
                'capacity_full_container_1' => '220',
                'unloading_containers_1' => [['status' => $status, 'qty_current' => $qty]],
            ]);
        }

        $recap = collect(app(OperationalPerformanceService::class)->activityRecap([
            'start' => Carbon::parse('2026-08-01')->startOfDay(),
            'end' => Carbon::parse('2026-08-07')->startOfDay(),
        ])['rows'])->keyBy('key');

        $this->assertSame(275.0, $recap['bongkar_container']['total']['value']);
        $this->assertSame(195.0, $recap['muat_container']['total']['value']);
    }

    /**
     * Data yang terlanjur tersimpan sebelum perbaikan tidak ikut membaik dengan
     * sendirinya. Perintah perapian yang mengurusnya — termasuk payload yang
     * diputar ulang ke form, supaya membuka laporan lama tidak menampilkan
     * kolom Empty / Full dalam keadaan kosong.
     */
    public function test_perintah_perapian_menyeragamkan_data_lama(): void
    {
        $report = DailyReport::create([
            'report_date' => '2026-07-21',
            'shift' => 'Pagi',
            'group_name' => 'D',
            'status' => 'approved',
            'payload' => ['fields' => [
                ['key' => 'unloading_containers_1[0][status]', 'value' => 'EMPYTY'],
                ['key' => 'shift', 'value' => 'Pagi'],
            ]],
        ]);

        $activity = $report->containerActivity()->create([
            'sequence' => 1,
            'ship_name' => 'MV.AYER MAS',
            'capacity' => 200,
        ]);

        $activity->items()->create(['status' => 'EMPYTY', 'qty_current' => 108]);
        // Penanda kosong: sengaja dibiarkan agar bisa dicocokkan dengan arsip.
        $activity->items()->create(['status' => null, 'qty_current' => 86]);

        $this->artisan('container:repair-status', ['--dry-run' => true])->assertSuccessful();

        $this->assertSame(
            'EMPYTY',
            ContainerItem::orderBy('id')->first()->status,
            'Mode pratinjau tidak boleh menulis apa pun.',
        );

        $this->artisan('container:repair-status')->assertSuccessful();

        $statuses = ContainerItem::orderBy('id')->pluck('status')->all();
        $this->assertSame(['Empty', null], $statuses);

        $this->assertSame(
            'Empty',
            $report->fresh()->payload['fields'][0]['value'],
            'Payload ikut diseragamkan agar dropdown terpilih saat laporan dibuka.',
        );
    }

    /**
     * Kalau nanti ada penanda yang lolos lagi, panel harus menyebutkannya
     * sendiri. Selisih tidak boleh lagi bergantung pada ada tidaknya orang
     * yang kebetulan hafal angkanya.
     */
    public function test_panel_menyebut_baris_yang_belum_ditandai(): void
    {
        $report = DailyReport::create([
            'report_date' => '2026-08-04',
            'shift' => 'Pagi',
            'group_name' => 'B',
            'status' => 'approved',
        ]);

        $activity = $report->containerActivity()->create([
            'sequence' => 1,
            'ship_name' => 'MV. Curug Mas',
            'capacity' => 275,
            'capacity_empty' => 275,
        ]);

        $activity->items()->create(['status' => 'Empty', 'qty_current' => 70]);
        $activity->items()->create(['status' => null, 'qty_current' => 86]);
        // Baris tanpa jumlah tidak menggeser angka apa pun, jadi tidak diadukan.
        $activity->items()->create(['status' => null, 'qty_current' => 0]);

        $detail = app(OperationalPerformanceService::class)->activityDetail('bongkar_container', [
            'start' => Carbon::parse('2026-08-01')->startOfDay(),
            'end' => Carbon::parse('2026-08-07')->startOfDay(),
        ]);

        $this->assertNotEmpty($detail['warning'] ?? null);
        $this->assertStringContainsString('1 baris container', $detail['warning']);
        $this->assertStringContainsString('86 Teus', $detail['warning']);
    }

    /**
     * Kolomnya tetap teks bebas — penyeragaman dikerjakan di server — tetapi
     * harus membawa daftar saran Empty/Full supaya petugas tidak perlu menebak
     * kata yang dipakai sistem.
     */
    public function test_form_merender_saran_empty_full_pada_kolom_penanda(): void
    {
        $html = $this->actingAs($this->operator())
            ->get(route('report-ops.create'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString(
            'name="unloading_containers_1[0][status]" class="form-control-custom" list="container-status-options"',
            $html,
        );
        $this->assertStringContainsString('<datalist id="container-status-options">', $html);
        $this->assertStringContainsString('<option value="Empty">', $html);
        $this->assertStringContainsString('<option value="Full">', $html);
    }

    /**
     * @param  array<string, mixed>  $fields
     */
    private function submit(string $date, string $shift, string $group, array $fields): void
    {
        // Jam 23.30 menahan pergeseran "tanggal dinas" shift Malam, supaya
        // tanggal laporan pada uji ini persis seperti yang dituliskan.
        Carbon::setTestNow(Carbon::parse($date)->setTime(23, 30));

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
            ['username' => 'petugas-container'],
            [
                'name' => 'Petugas Container',
                'email' => 'petugas-container@example.com',
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
