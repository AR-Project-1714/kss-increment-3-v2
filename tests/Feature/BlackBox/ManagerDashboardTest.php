<?php

namespace Tests\Feature\BlackBox;

use App\Enums\ReportStatus;
use App\Enums\SafetyStatus;
use App\Models\BulkLoadingActivity;
use App\Models\ContainerActivity;
use App\Models\ContainerItem;
use App\Models\DailyReport;
use App\Models\EmployeeLog;
use App\Models\LoadingActivity;
use App\Models\TurbaActivity;
use App\Models\TurbaDelivery;
use App\Models\User;
use App\Services\BulkTonnageService;
use App\Services\OperationalPerformanceService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Modul I — Manajer / Dashboard & Tanda Tangan (PENGUJIAN_BLACKBOX.md §4.I).
 */
class ManagerDashboardTest extends BlackBoxTestCase
{
    /**
     * Tanggal acuan pengujian periode. Dibekukan supaya "Januari sampai hari
     * ini" dan "bulan berjalan" punya batas yang pasti, bukan ikut kalender
     * mesin yang menjalankan pengujian.
     */
    private const TODAY = '2026-07-15';

    private function opsDocId(DailyReport $report): string
    {
        return '#OPS-2026-'.str_pad((string) $report->id, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Bekukan tanggal lalu kosongkan cache performa, karena kunci cache-nya
     * memuat rentang tanggal yang baru saja digeser.
     */
    private function freezeToday(string $date = self::TODAY): void
    {
        $this->travelTo($date.' 09:00:00');
        Cache::flush();
    }

    /**
     * Satu laporan operasi lengkap dengan kegiatan muat kantong, container,
     * trucking, dan catatan lembur — cukup untuk menguji seluruh blok analisis.
     */
    private function opsReportWithActivities(User $creator, array $overrides = [], array $values = []): DailyReport
    {
        $report = $this->acknowledgedOpsReport($creator, $overrides);

        LoadingActivity::create([
            'daily_report_id' => $report->id,
            'ship_name' => $values['ship'] ?? 'MV Uji',
            'arrival_time' => ($overrides['report_date'] ?? '2026-05-21').' 06:00:00',
            'capacity' => 5000,
            'tkbm_count' => 12,
            'qty_loading_current' => $values['kantong'] ?? 100,
            'qty_damage_current' => $values['kerusakan'] ?? 2,
        ]);

        foreach ([
            'curah' => BulkLoadingActivity::TYPE_BULK_LOADING,
            'amoniak' => BulkLoadingActivity::TYPE_AMMONIA_LOADING,
        ] as $valueKey => $activityType) {
            if (! array_key_exists($valueKey, $values)) {
                continue;
            }

            $bulk = BulkLoadingActivity::create([
                'daily_report_id' => $report->id,
                'activity_type' => $activityType,
                'ship_name' => $activityType === BulkLoadingActivity::TYPE_AMMONIA_LOADING ? 'MT Amoniak Uji' : 'MV Curah Uji',
                'commodity' => $activityType === BulkLoadingActivity::TYPE_AMMONIA_LOADING ? 'Amoniak Cair' : 'Urea Curah',
                'berthing_time' => ($overrides['report_date'] ?? '2026-05-21').' 06:00:00',
                'start_loading_time' => ($overrides['report_date'] ?? '2026-05-21').' 08:00:00',
                'capacity' => 5000,
            ]);

            $bulk->logs()->create([
                'datetime' => ($overrides['report_date'] ?? '2026-05-21').' 09:00:00',
                'activity' => 'Pemuatan pengujian',
                'cob' => $values[$valueKey],
            ]);
        }

        // COB adalah pembacaan kumulatif; yang dijumlahkan pada statistik
        // adalah pertambahannya. Sama seperti di controller, tonase dihitung
        // ulang setelah baris log tersimpan.
        app(BulkTonnageService::class)->recalculateForReport($report->id);

        $container = ContainerActivity::create([
            'daily_report_id' => $report->id,
            'ship_name' => 'MV Box',
            'capacity' => 200,
        ]);

        // Penanda Empty/Full inilah yang memecah kontainer menjadi dua baris
        // pada rekap, tanpa menyentuh form laporan.
        ContainerItem::create([
            'container_activity_id' => $container->id,
            'qty_current' => $values['container'] ?? 50,
            'status' => 'Empty',
        ]);

        ContainerItem::create([
            'container_activity_id' => $container->id,
            'qty_current' => $values['containerFull'] ?? 10,
            'status' => 'Full',
        ]);

        $turba = TurbaActivity::create([
            'daily_report_id' => $report->id,
            'working_hours' => '08:00 - 16:00',
        ]);

        TurbaDelivery::create([
            'turba_activity_id' => $turba->id,
            'truck_name' => 'Buffer Stock',
            'capacity' => 30,
            'qty_current' => $values['trucking'] ?? 20,
        ]);

        EmployeeLog::create([
            'daily_report_id' => $report->id,
            'category' => 'shift',
            'name' => 'Personil Uji',
        ]);

        EmployeeLog::create([
            'daily_report_id' => $report->id,
            'category' => 'operasi',
            'description' => 'Lembur',
            'name' => $values['lembur'] ?? 'Zein',
            'time_in' => '16:00:00',
            'time_out' => '20:00:00',
        ]);

        return $report;
    }

    /**
     * Jumlah query yang dijalankan satu permintaan halaman.
     */
    private function queryCountFor(User $user, string $url): int
    {
        Cache::flush();
        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->actingAs($user)->get($url)->assertOk();

        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    }

    public function test_tc_mgr_01_dashboard_menampilkan_laporan_masuk_tiga_divisi(): void
    {
        $manager = $this->manager();
        $operator = $this->operator('A');
        $maintenanceUser = $this->maintenance();
        $safetyUser = $this->safety();

        $ops = $this->acknowledgedOpsReport($operator);
        $maintenance = $this->submittedMaintenanceReport($maintenanceUser);
        $safety = $this->submittedSafetyReport($safetyUser);

        $opsId = $this->opsDocId($ops);
        $mntId = '#MNT-2026-'.str_pad((string) $maintenance->id, 3, '0', STR_PAD_LEFT);
        $k3Id = '#K3-2026-'.str_pad((string) $safety->id, 3, '0', STR_PAD_LEFT);

        $this->actingAs($manager)
            ->get(route('manajer.index'))
            ->assertOk()
            ->assertSee($opsId, false)
            ->assertSee($mntId, false)
            ->assertSee($k3Id, false);
    }

    public function test_tc_mgr_01b_sidebar_memisahkan_menu_analitik_dari_menu_utama(): void
    {
        $response = $this->actingAs($this->manager())
            ->get(route('manajer.index'))
            ->assertOk()
            ->assertSeeInOrder([
                'Menu Utama',
                'Dashboard',
                'Arsip Laporan',
                'Analitik Operasi',
                'Kinerja Operasi',
                'Rincian Kegiatan',
                'Sistem',
            ], false);

        $html = $response->getContent();

        preg_match(
            '/data-sidebar-section="main">(.*?)<\/div>\s*<!-- ANALITIK OPERASI -->/s',
            $html,
            $mainSection
        );
        preg_match(
            '/data-sidebar-section="operational-analytics">(.*?)<\/div>\s*<!-- SISTEM -->/s',
            $html,
            $analyticsSection
        );

        $this->assertStringNotContainsString('Kinerja Operasi', $mainSection[1] ?? '');
        $this->assertStringNotContainsString('Rincian Kegiatan', $mainSection[1] ?? '');
        $this->assertStringContainsString('Kinerja Operasi', $analyticsSection[1] ?? '');
        $this->assertStringContainsString('Rincian Kegiatan', $analyticsSection[1] ?? '');
    }

    public function test_tc_mgr_01c_pusat_bantuan_menjelaskan_analitik_dan_pencarian_berada_di_header(): void
    {
        $this->actingAs($this->manager())
            ->get(route('manajer.bantuan'))
            ->assertOk()
            ->assertSee('class="page-header help-page-header"', false)
            ->assertSeeInOrder([
                'help-page-header__heading',
                'data-help-search-toolbar',
                'id="helpSearch"',
            ], false)
            ->assertSee('data-tab="analitik"', false)
            ->assertSee('id="analitik"', false)
            ->assertSee('Kinerja Operasi', false)
            ->assertSee('Rincian Kegiatan', false)
            ->assertSee('Filter Data Bersama', false)
            ->assertSee('Satuan dan Ekspor', false);
    }

    public function test_tc_mgr_02_tab_divisi_dan_jumlahnya(): void
    {
        $manager = $this->manager();
        $operator = $this->operator('A');
        $this->acknowledgedOpsReport($operator);
        $this->acknowledgedOpsReport($operator, ['shift' => 'Sore']);

        $this->actingAs($manager)
            ->get(route('manajer.index'))
            ->assertOk()
            ->assertSee('Operasional', false)
            ->assertSee('Pemeliharaan', false)
            ->assertSee('Safety', false);
    }

    public function test_tc_mgr_03_lihat_laporan_masuk_membuka_pratinjau(): void
    {
        $manager = $this->manager();
        $operator = $this->operator('A');
        $report = $this->acknowledgedOpsReport($operator);

        $this->actingAs($manager)
            ->get(route('manajer.reports.show', $report))
            ->assertOk()
            ->assertSee('Laporan Operasi Harian', false);
    }

    public function test_tc_mgr_04_menandatangani_laporan_masuk_mengarsipkan(): void
    {
        $manager = $this->manager();
        $operator = $this->operator('A');
        $report = $this->acknowledgedOpsReport($operator);

        $this->actingAs($manager)
            ->post(route('manajer.reports.approve', $report))
            ->assertRedirect(route('manajer.archive'))
            ->assertSessionHas('success', 'Laporan berhasil ditanda tangani dan masuk ke arsip.');

        $fresh = $report->fresh();
        $this->assertSame(ReportStatus::Approved, $fresh->status);
        $this->assertSame($manager->id, $fresh->approved_by);
        $this->assertNotNull($fresh->approved_at);
    }

    public function test_tc_mgr_05_laporan_ops_belum_diterima_tidak_muncul(): void
    {
        $manager = $this->manager();
        $operator = $this->operator('A');

        // Status submitted (belum ditandatangani regu penerima).
        $submitted = DailyReport::create([
            'user_id' => $operator->id,
            'created_by' => $operator->id,
            'report_date' => '2026-05-21',
            'shift' => 'Pagi',
            'group_name' => 'A',
            'received_by_group' => 'B',
            'time_range' => '07:00 - 15:00',
            'status' => ReportStatus::Submitted,
        ]);

        $this->actingAs($manager)
            ->get(route('manajer.index'))
            ->assertOk()
            ->assertDontSee($this->opsDocId($submitted), false);
    }

    public function test_tc_mgr_06_laporan_pemeliharaan_safety_diserahkan_langsung_muncul(): void
    {
        $manager = $this->manager();
        $maintenance = $this->submittedMaintenanceReport($this->maintenance());
        $safety = $this->submittedSafetyReport($this->safety());

        $mntId = '#MNT-2026-'.str_pad((string) $maintenance->id, 3, '0', STR_PAD_LEFT);
        $k3Id = '#K3-2026-'.str_pad((string) $safety->id, 3, '0', STR_PAD_LEFT);

        $this->actingAs($manager)
            ->get(route('manajer.index'))
            ->assertOk()
            ->assertSee($mntId, false)
            ->assertSee($k3Id, false);
    }

    public function test_tc_mgr_07_approve_safety_dengan_tanda_tangan_kosong(): void
    {
        // Catatan: pada implementasi saat ini, penandatanganan tetap diproses
        // meski signature_path manajer kosong (penanganan "hubungi admin" hanya
        // di lapisan UI). Pengujian ini memverifikasi sistem tidak error.
        $manager = $this->manager(['signature_path' => null]);
        $safety = $this->submittedSafetyReport($this->safety());

        $this->actingAs($manager)
            ->post(route('manajer.safety.approve', $safety))
            ->assertRedirect(route('manajer.index'));

        $this->assertSame(SafetyStatus::Approved, $safety->fresh()->status);
    }

    public function test_approve_lewat_fetch_membalas_url_tujuan_dan_flash_tetap_tersimpan(): void
    {
        // Overlay progres PDF mengirim form lewat fetch supaya bar baru lompat ke
        // 100% setelah proses server selesai; balasannya harus berupa URL tujuan,
        // bukan redirect (fetch akan mengikutinya dan menghabiskan flash message).
        $manager = $this->manager();
        $safety = $this->submittedSafetyReport($this->safety());

        $this->actingAs($manager)
            ->postJson(route('manajer.safety.approve', $safety))
            ->assertOk()
            ->assertExactJson(['redirect' => route('manajer.index')]);

        $this->assertSame(SafetyStatus::Approved, $safety->fresh()->status);
        $this->assertSame('Laporan K3 berhasil diarsipkan.', session('success'));
    }

    public function test_tc_mgr_08_ekspor_performa_menghasilkan_excel_lima_sheet(): void
    {
        $manager = $this->manager();
        $operator = $this->operator('A');
        $this->acknowledgedOpsReport($operator);

        $response = $this->actingAs($manager)->get(route('manajer.performa.export'));

        $response->assertOk();
        $response->assertHeader(
            'Content-Type',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );

        // Isi berkas dibongkar sungguhan, bukan hanya dicek header-nya:
        // regresi pada penulisan sheet harus ketahuan di sini.
        $path = tempnam(sys_get_temp_dir(), 'perf-export');
        file_put_contents($path, $response->streamedContent());

        try {
            $spreadsheet = IOFactory::load($path);

            // Sheet Kapal Dilayani ikut dihapus bersama bloknya di halaman;
            // sheet rekap kegiatan jadi sheet pertama.
            $this->assertSame(
                ['Kinerja Operasional', 'Ringkasan', 'Per Kegiatan', 'Tren Bulanan', 'Regu & Kegiatan', 'Peringkat Lembur'],
                $spreadsheet->getSheetNames()
            );

            $summary = $spreadsheet->getSheetByName('Ringkasan');
            $this->assertSame('Performa Operasional — Ringkasan', $summary->getCell('A1')->getValue());

            $found = false;
            foreach ($summary->getRowIterator() as $row) {
                if ($summary->getCell('A'.$row->getRowIndex())->getValue() === 'Tonase Ditangani') {
                    $found = true;
                    break;
                }
            }
            $this->assertTrue($found, 'Baris KPI "Tonase Ditangani" tidak ditemukan pada sheet Ringkasan.');

            $spreadsheet->disconnectWorksheets();
        } finally {
            @unlink($path);
        }
    }

    public function test_tc_mgr_08b_sheet_rekap_mengikuti_format_laporan_manajemen(): void
    {
        $this->freezeToday();

        $manager = $this->manager();
        $operator = $this->operator('A');

        // Satu laporan Februari (kolom "sebelumnya") dan satu Juli (kolom
        // "bulan sekarang"), supaya ketiga kelompok kolom terisi.
        $this->opsReportWithActivities($operator, ['report_date' => '2026-02-10'], ['kantong' => 100, 'container' => 20]);
        $this->opsReportWithActivities($operator, ['report_date' => '2026-07-10'], ['kantong' => 60, 'container' => 30]);

        $response = $this->actingAs($manager)->get(route('manajer.performa.export', ['periode' => 'tahun-berjalan']));
        $response->assertOk();

        $path = tempnam(sys_get_temp_dir(), 'perf-rekap');
        file_put_contents($path, $response->streamedContent());

        try {
            $sheet = IOFactory::load($path)->getSheetByName('Kinerja Operasional');

            $this->assertSame('KINERJA OPERASIONAL TAHUN 2026', $sheet->getCell('A1')->getValue());

            $rows = $sheet->toArray(null, true, false, true);

            $rowFor = static function (string $label) use ($rows): ?array {
                foreach ($rows as $row) {
                    if (($row['A'] ?? null) === $label) {
                        return $row;
                    }
                }

                return null;
            };

            // Container berdiri sebagai dua kegiatan, dipisah penanda barisnya.
            $labels = array_filter(array_column($rows, 'A'));
            $this->assertContains('Pemuatan Pupuk Kantong', $labels);
            $this->assertContains('Bongkar Container (Empty)', $labels);
            $this->assertContains('Muat Container (Full)', $labels);

            // Tiga kelompok kolom: bulan sekarang, sebelumnya, akumulasi.
            $header = implode(' | ', array_filter($rowFor('KEGIATAN') ?? []));
            $this->assertStringContainsString('BULAN SEKARANG', $header);
            $this->assertStringContainsString('SEBELUMNYA', $header);
            $this->assertStringContainsString('AKUMULASI', $header);

            // Baris muat kantong: 60 Ton bulan ini + 100 Ton sebelumnya = 160.
            // Kolom B–E bulan sekarang, F–I sebelumnya, J–M akumulasi.
            $kantong = $rowFor('Pemuatan Pupuk Kantong');
            $this->assertSame(60.0, (float) $kantong['D']);
            $this->assertSame(100.0, (float) $kantong['H']);
            $this->assertSame(160.0, (float) $kantong['L']);
        } finally {
            @unlink($path);
        }
    }

    public function test_tc_mgr_08c_ekspor_rincian_membuat_sheet_per_kegiatan_dan_chart(): void
    {
        $this->freezeToday();

        $manager = $this->manager();
        $operator = $this->operator('A');

        $this->opsReportWithActivities(
            $operator,
            ['report_date' => '2026-02-10', 'group_name' => 'A', 'shift' => 'Pagi'],
            ['kantong' => 100, 'container' => 20]
        );

        // Priming cache halaman dengan data lama memastikan workbook tidak
        // mencampur overview dari cache dan detail yang dihitung aktual.
        $this->actingAs($manager)->get(route('manajer.kegiatan', [
            'periode' => 'tahun-berjalan',
            'regu' => 'A',
        ]))->assertOk();

        $this->opsReportWithActivities(
            $operator,
            ['report_date' => '2026-07-10', 'group_name' => 'A', 'shift' => 'Malam'],
            ['kantong' => 60, 'container' => 30]
        );

        $response = $this->actingAs($manager)->get(route('manajer.kegiatan.export', [
            'periode' => 'tahun-berjalan',
            'regu' => 'A',
        ]));

        $response->assertOk();
        $response->assertHeader(
            'Content-Type',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );
        $this->assertStringContainsString(
            'Rincian-Kegiatan-Operasional_',
            (string) $response->headers->get('Content-Disposition')
        );

        $path = tempnam(sys_get_temp_dir(), 'activity-export');
        file_put_contents($path, $response->streamedContent());

        try {
            $reader = IOFactory::createReader('Xlsx');
            $reader->setIncludeCharts(true);
            $spreadsheet = $reader->load($path);

            $this->assertSame([
                'Gambaran Besar',
                'Muat Kantong',
                'Muat Curah',
                'Muat Amoniak',
                'Bongkar Bahan Baku',
                'Bongkar Container',
                'Muat Container',
                'Trucking Pupuk',
            ], $spreadsheet->getSheetNames());

            $overview = $spreadsheet->getSheetByName('Gambaran Besar');
            $this->assertSame('KINERJA OPERASIONAL TAHUN 2026', $overview->getCell('A1')->getValue());
            $this->assertSame('B12', $overview->getFreezePane());
            $this->assertArrayHasKey('A8:M8', $overview->getMergeCells());

            $overviewKantongRow = null;
            foreach ($overview->getRowIterator() as $sheetRow) {
                $index = $sheetRow->getRowIndex();
                if ($overview->getCell('A'.$index)->getValue() === 'Pemuatan Pupuk Kantong') {
                    $overviewKantongRow = $index;
                    break;
                }
            }
            $this->assertNotNull($overviewKantongRow);
            $this->assertSame(
                160.0,
                (float) $overview->getCell('L'.$overviewKantongRow)->getCalculatedValue(),
                'Overview dan sheet detail harus memakai pembacaan data aktual yang sama.'
            );

            foreach (array_slice($spreadsheet->getSheetNames(), 1) as $sheetName) {
                $this->assertSame(
                    2,
                    $spreadsheet->getSheetByName($sheetName)->getChartCount(),
                    "Sheet {$sheetName} harus memuat dua chart."
                );
            }

            $kantong = $spreadsheet->getSheetByName('Muat Kantong');
            $this->assertSame('PEMUATAN PUPUK KANTONG', $kantong->getCell('A1')->getValue());
            $this->assertSame('Bulan', $kantong->getCell('M38')->getValue());

            $trendSeries = $kantong->getChartCollection()[0]
                ->getPlotAreaOrThrow()
                ->getPlotGroupByIndex(0)
                ->getPlotValuesByIndex(0);
            $this->assertNotFalse($trendSeries);
            $this->assertSame("'Muat Kantong'!\$N\$39:\$N\$44", $trendSeries->getDataSource());

            $recapFormulaRow = null;
            foreach ($kantong->getRowIterator() as $sheetRow) {
                $index = $sheetRow->getRowIndex();
                if ($kantong->getCell('A'.$index)->getValue() === 'Muat') {
                    $recapFormulaRow = $index;
                    $this->assertTrue(str_starts_with(
                        (string) $kantong->getCell('D'.$index)->getValue(),
                        '=SUM(B'
                    ));
                    break;
                }
            }
            $this->assertNotNull($recapFormulaRow, 'Baris rekap kegiatan tidak ditemukan.');
            $this->assertSame(
                (float) $kantong->getCell('B'.$recapFormulaRow)->getCalculatedValue()
                    + (float) $kantong->getCell('C'.$recapFormulaRow)->getCalculatedValue(),
                (float) $kantong->getCell('D'.$recapFormulaRow)->getCalculatedValue(),
                'Akumulasi rekap kegiatan harus menghitung dua periode sumber.'
            );

            $spreadsheet->disconnectWorksheets();
        } finally {
            @unlink($path);
        }
    }

    public function test_tc_mgr_08d_ekspor_rincian_tidak_memakai_batas_baris_panel(): void
    {
        $this->freezeToday();

        $report = $this->opsReportWithActivities(
            $this->operator('A'),
            ['report_date' => '2026-07-10']
        );
        $turba = TurbaActivity::query()->where('daily_report_id', $report->id)->firstOrFail();

        foreach (range(1, 14) as $index) {
            TurbaDelivery::create([
                'turba_activity_id' => $turba->id,
                'truck_name' => 'Tujuan '.$index,
                'capacity' => 30,
                'qty_current' => 20 + $index,
            ]);
        }

        $filters = [
            'start' => Carbon::parse('2026-01-01'),
            'end' => Carbon::parse(self::TODAY),
            'group' => null,
            'shift' => null,
        ];
        $service = app(OperationalPerformanceService::class);

        $panel = $service->activityDetail('trucking_turba', $filters);
        $export = $service->activityDetailForExport('trucking_turba', $filters);

        $this->assertCount(10, $panel['table']['rows']);
        $this->assertTrue($panel['table']['limited']);
        $this->assertCount(15, $export['table']['rows']);
        $this->assertFalse($export['table']['limited']);
    }

    public function test_tc_mgr_09_ekspor_performa_ditolak_untuk_selain_manajer(): void
    {
        // Middleware role mengarahkan pengguna kembali ke berandanya sendiri,
        // bukan menampilkan 403 — mengikuti konvensi pada AccessControlTest.
        $operator = $this->operator('A');

        $this->actingAs($operator)
            ->get(route('manajer.performa.export'))
            ->assertRedirect(route('report-ops.index'));

        $this->actingAs($operator)
            ->get(route('manajer.kegiatan.export'))
            ->assertRedirect(route('report-ops.index'));
    }

    public function test_tc_mgr_10_menu_kegiatan_memuat_tujuh_tab_kegiatan(): void
    {
        $manager = $this->manager();

        // Daftar tab diturunkan dari katalog lewat penanda showOnActivityDetail,
        // urut dari muat kantong sampai trucking. Container berdiri dua kegiatan.
        $this->actingAs($manager)
            ->get(route('manajer.kegiatan'))
            ->assertOk()
            ->assertSee('data-activity-tab="muat_kantong"', false)
            ->assertSee('data-activity-tab="muat_curah"', false)
            ->assertSee('data-activity-tab="muat_amoniak"', false)
            ->assertSee('data-activity-tab="bongkar_bahan_baku"', false)
            ->assertSee('data-activity-tab="bongkar_container"', false)
            ->assertSee('data-activity-tab="muat_container"', false)
            ->assertSee('data-activity-tab="trucking_turba"', false)
            ->assertSee('id="activityTabIndicator"', false)
            ->assertSee(route('manajer.kegiatan.panel', ['key' => 'muat_kantong']), false)
            ->assertDontSee('performance-island', false)
            // Strip "Capaian per Kegiatan" sudah tidak ada; halaman langsung
            // menampilkan tab beserta panelnya.
            ->assertDontSee('Capaian per Kegiatan', false)
            ->assertDontSee('act-grid', false);
    }

    public function test_tc_mgr_11_halaman_performa_tidak_memuat_tab_kegiatan(): void
    {
        // Bedah dan rekap per kegiatan ada di menu Rincian Kegiatan. Halaman
        // Kinerja Operasi hanya menampilkan ringkasan seluruh divisi.
        $manager = $this->manager();

        $this->actingAs($manager)
            ->get(route('manajer.performa'))
            ->assertOk()
            ->assertSee('Tren Tonase', false)
            ->assertSee('Ringkasan Kinerja Operasi', false)
            ->assertSee('data-performance-filter-trigger', false)
            ->assertSee('data-performance-filter-popover', false)
            ->assertDontSee('performance-island', false)
            ->assertDontSee('Rekap per Jenis Kegiatan', false)
            ->assertDontSee('Ringkasan Kegiatan', false)
            ->assertDontSee('data-activity-tab', false)
            ->assertDontSee('data-activity-switch', false)
            ->assertDontSee('id="activity-panel"', false);
    }

    public function test_tc_mgr_12_filter_aktif_ikut_terbawa_ke_panel_kegiatan(): void
    {
        $manager = $this->manager();
        $operator = $this->operator('A');
        $this->acknowledgedOpsReport($operator);

        // Filter yang sedang aktif menempel pada URL panel dan pada tautan
        // sidebar ke Performa, sehingga berpindah menu tidak menyaring ulang.
        $this->actingAs($manager)
            ->get(route('manajer.kegiatan', ['periode' => '3-bulan', 'regu' => 'A']))
            ->assertOk()
            ->assertSee(route('manajer.kegiatan.panel', ['key' => 'muat_curah', 'periode' => '3-bulan', 'regu' => 'A']))
            ->assertSee(route('manajer.performa', ['periode' => '3-bulan', 'regu' => 'A']))
            ->assertSee(route('manajer.kegiatan.export', ['periode' => '3-bulan', 'regu' => 'A']));

        // Kepala panel menyebut kegiatan yang sedang dibaca.
        $this->actingAs($manager)
            ->get(route('manajer.kegiatan.panel', ['key' => 'muat_curah', 'periode' => '3-bulan']))
            ->assertOk()
            ->assertSee('act-panel__title', false)
            ->assertSee('act-block', false)
            ->assertSee('Pemuatan Urea Curah', false);
    }

    public function test_tc_mgr_13_menu_kegiatan_ditolak_untuk_selain_manajer(): void
    {
        $operator = $this->operator('A');

        $this->actingAs($operator)
            ->get(route('manajer.kegiatan'))
            ->assertRedirect(route('report-ops.index'));
    }

    public function test_tc_mgr_14_kinerja_operasi_bawaan_januari_sampai_hari_ini(): void
    {
        $this->freezeToday();

        $manager = $this->manager();
        $operator = $this->operator('A');

        // Laporan Februari berada di luar bulan berjalan tetapi masih di dalam
        // rentang Januari–sekarang, jadi angkanya wajib ikut terhitung.
        $this->opsReportWithActivities($operator, ['report_date' => '2026-02-10'], ['kantong' => 250]);

        $this->actingAs($manager)
            ->get(route('manajer.performa'))
            ->assertOk()
            ->assertSee('value="2026-01-01"', false)
            ->assertSee('value="2026-07-15"', false)
            ->assertSee('Januari–Sekarang', false)
            ->assertSee('1 Jan - 15 Jul 2026', false);
    }

    public function test_tc_mgr_14b_kartu_ytd_memakai_delta_bulan_berjalan_vs_bulan_lalu(): void
    {
        $this->freezeToday();

        $manager = $this->manager();
        $operator = $this->operator('A');

        // Juni: 150 Ton dari dua laporan. Juli: 200 Ton dari satu laporan.
        // Angka utama tetap menjumlahkan 350 Ton, tetapi indikator membandingkan
        // Juli dengan Juni: tonase naik (hijau), laporan turun (merah).
        $this->opsReportWithActivities(
            $operator,
            ['report_date' => '2026-06-10'],
            ['kantong' => 100, 'kerusakan' => 3, 'trucking' => 0]
        );
        $this->opsReportWithActivities(
            $operator,
            ['report_date' => '2026-06-11'],
            ['kantong' => 50, 'kerusakan' => 3, 'trucking' => 0]
        );
        $this->opsReportWithActivities(
            $operator,
            ['report_date' => '2026-07-10'],
            ['kantong' => 200, 'kerusakan' => 2, 'trucking' => 0]
        );

        $report = app(OperationalPerformanceService::class)->performanceReport([
            'start' => Carbon::parse('2026-01-01'),
            'end' => Carbon::parse(self::TODAY),
            'group' => null,
            'shift' => null,
        ]);

        $this->assertSame(350.0, (float) $report['summary']['tonnage']['value']);
        $this->assertSame('vs Jun 2026', $report['kpiComparisonLabel']);

        $this->assertTrue($report['summary']['tonnage']['delta']['available']);
        $this->assertSame('up', $report['summary']['tonnage']['delta']['tone']);
        $this->assertSame('33,3%', $report['summary']['tonnage']['delta']['text']);

        $this->assertTrue($report['summary']['reports']['delta']['available']);
        $this->assertSame('down', $report['summary']['reports']['delta']['tone']);
        $this->assertSame('50,0%', $report['summary']['reports']['delta']['text']);

        // Rasio kerusakan turun dari 4% menjadi 1%, sehingga tetap hijau.
        $this->assertSame('down', $report['summary']['damageRatio']['delta']['direction']);
        $this->assertSame('up', $report['summary']['damageRatio']['delta']['tone']);

        $this->actingAs($manager)
            ->get(route('manajer.performa'))
            ->assertOk()
            ->assertSee('vs Jun 2026', false)
            ->assertSee('spark spark--up kpi-card__spark', false)
            ->assertSee('spark spark--down kpi-card__spark', false);
    }

    public function test_tc_mgr_14c_analitik_ytd_memakai_periode_setara_tahun_lalu(): void
    {
        $this->freezeToday();

        $operator = $this->operator('A');

        $this->opsReportWithActivities(
            $operator,
            ['report_date' => '2025-07-10'],
            ['kantong' => 100, 'trucking' => 0]
        );
        $this->opsReportWithActivities(
            $operator,
            ['report_date' => '2026-07-10'],
            ['kantong' => 200, 'trucking' => 0]
        );

        $report = app(OperationalPerformanceService::class)->performanceReport([
            'start' => Carbon::parse('2026-01-01'),
            'end' => Carbon::parse(self::TODAY),
            'group' => null,
            'shift' => null,
        ]);

        $this->assertSame('vs 1 Jan - 15 Jul 2025', $report['comparisonLabel']);

        $panels = collect($report['activityPanels'])->keyBy('key');
        $this->assertTrue($panels['muat_kantong']['delta']['available']);
        $this->assertSame('up', $panels['muat_kantong']['delta']['tone']);
        $this->assertSame('100,0%', $panels['muat_kantong']['delta']['text']);
    }

    public function test_tc_mgr_15_rincian_kegiatan_bawaan_januari_sampai_hari_ini(): void
    {
        $this->freezeToday();

        $manager = $this->manager();

        $this->actingAs($manager)
            ->get(route('manajer.kegiatan'))
            ->assertOk()
            ->assertSee('value="2026-01-01"', false)
            ->assertSee('value="2026-07-15"', false)
            ->assertSee('Januari–Sekarang', false)
            ->assertSee('1 Jan - 15 Jul 2026', false);
    }

    public function test_tc_mgr_16_kapal_dilayani_tidak_tampil_pada_kinerja_operasi(): void
    {
        $this->freezeToday();

        $manager = $this->manager();
        $operator = $this->operator('A');
        $this->opsReportWithActivities($operator, ['report_date' => '2026-07-10']);

        $this->actingAs($manager)
            ->get(route('manajer.performa'))
            ->assertOk()
            ->assertDontSee('Kapal Dilayani', false)
            // Tempat kartunya diisi jumlah laporan yang jadi dasar hitungan.
            ->assertSee('Laporan Masuk', false);
    }

    public function test_tc_mgr_17_endpoint_panel_hanya_melayani_kegiatan_katalog(): void
    {
        $manager = $this->manager();

        // Kegiatan yang ditandai tampil pada menu ini bisa dibuka…
        foreach (array_keys(app(OperationalPerformanceService::class)->activitiesFor('activityDetail')) as $key) {
            $this->actingAs($manager)
                ->get(route('manajer.kegiatan.panel', ['key' => $key]))
                ->assertOk();
        }

        // …selain itu ditolak, termasuk saat endpoint-nya dibuka langsung.
        $this->actingAs($manager)
            ->get(route('manajer.kegiatan.panel', ['key' => 'kegiatan_karangan']))
            ->assertNotFound();
    }

    public function test_tc_mgr_18_pupuk_kantong_tetap_masuk_kinerja_operasi(): void
    {
        $this->freezeToday();

        $manager = $this->manager();
        $operator = $this->operator('A');
        $this->opsReportWithActivities($operator, ['report_date' => '2026-07-10'], [
            'kantong' => 100,
            'container' => 50,
            'trucking' => 20,
        ]);

        $report = app(OperationalPerformanceService::class)->performanceReport([
            'start' => Carbon::parse('2026-01-01'),
            'end' => Carbon::parse(self::TODAY),
            'group' => null,
            'shift' => null,
        ]);

        $cards = collect($report['activityCards'])->keyBy('key');

        $this->assertSame(100.0, (float) $cards['muat_kantong']['value']);
        $this->assertSame('Ton', $cards['muat_kantong']['unit']);

        // Kantong + trucking = 120 Ton; container 50 Teus tidak ikut.
        $this->assertSame(120.0, (float) $report['summary']['tonnage']['value']);

        $panels = collect($report['activityPanels'])->keyBy('key');
        $this->assertTrue($panels->has('muat_kantong'));
    }

    public function test_tc_mgr_18b_curah_dan_amoniak_terpisah_pada_komposisi_dan_rincian(): void
    {
        $this->freezeToday();

        $manager = $this->manager();
        $this->opsReportWithActivities(
            $this->operator('A'),
            ['report_date' => '2026-07-10'],
            ['kantong' => 100, 'trucking' => 0, 'curah' => 40, 'amoniak' => 25]
        );

        $response = $this->actingAs($manager)
            ->get(route('manajer.performa'))
            ->assertOk()
            ->assertSee('Muat Curah', false)
            ->assertSee('Muat Amoniak', false);

        $this->assertStringNotContainsString('Muat Curah / Amoniak', $response->getContent());

        $service = app(OperationalPerformanceService::class);
        $report = $service->performanceReport([
            'start' => Carbon::parse('2026-01-01'),
            'end' => Carbon::parse(self::TODAY),
            'group' => null,
            'shift' => null,
        ]);
        $cards = collect($report['activityCards'])->keyBy('key');

        $this->assertSame(40.0, (float) $cards['muat_curah']['value']);
        $this->assertSame(25.0, (float) $cards['muat_amoniak']['value']);

        $this->actingAs($manager)
            ->get(route('manajer.kegiatan.panel', ['key' => 'muat_amoniak']))
            ->assertOk()
            ->assertSee('Pemuatan Amoniak', false)
            ->assertSee('25,00', false);
    }

    public function test_tc_mgr_18c_tren_amoniak_tidak_menghitung_laporan_setelah_hari_ini(): void
    {
        $this->freezeToday('2026-07-29');

        $this->opsReportWithActivities(
            $this->operator('A'),
            ['report_date' => '2026-07-30'],
            ['amoniak' => 600]
        );

        $detail = app(OperationalPerformanceService::class)->activityDetail('muat_amoniak', [
            'start' => Carbon::parse('2026-01-01'),
            'end' => Carbon::parse('2026-07-29'),
            'group' => null,
            'shift' => null,
        ]);

        $this->assertSame(0.0, (float) $detail['value']);
        $this->assertSame(0.0, (float) collect($detail['trend'])->last()['value']);
        $this->assertSame(0.0, (float) $detail['recap']['row']['total']['value']);
    }

    public function test_tc_mgr_19_container_memakai_teus_dan_di_luar_total_tonase(): void
    {
        $this->freezeToday();

        $manager = $this->manager();
        $operator = $this->operator('A');
        $this->opsReportWithActivities($operator, ['report_date' => '2026-07-10'], [
            'kantong' => 100,
            'container' => 50,
            'trucking' => 0,
        ]);

        $this->actingAs($manager)
            ->get(route('manajer.performa'))
            ->assertOk()
            ->assertSee('Teus', false)
            // Donut komposisi hanya memuat kegiatan bersatuan Ton.
            ->assertSee('Container dihitung terpisah karena bersatuan Teus.', false);

        // Bongkar memakai baris Empty, muat memakai baris Full; keduanya Teus.
        $this->actingAs($manager)
            ->get(route('manajer.kegiatan.panel', ['key' => 'bongkar_container']))
            ->assertOk()
            ->assertSee('Bongkar Container (Empty)', false)
            ->assertSee('Teus', false);

        $this->actingAs($manager)
            ->get(route('manajer.kegiatan.panel', ['key' => 'muat_container']))
            ->assertOk()
            ->assertSee('Muat Container (Full)', false)
            ->assertSee('Teus', false);
    }

    public function test_tc_mgr_20_filter_regu_dan_shift_bekerja_dan_peringkat_regu_tetap_utuh(): void
    {
        $this->freezeToday();

        $manager = $this->manager();

        $this->opsReportWithActivities($this->operator('A'), [
            'report_date' => '2026-07-10',
            'group_name' => 'A',
            'shift' => 'Pagi',
        ], ['kantong' => 100]);

        $this->opsReportWithActivities($this->operator('B'), [
            'report_date' => '2026-07-11',
            'group_name' => 'B',
            'shift' => 'Pagi',
        ], ['kantong' => 40]);

        $this->opsReportWithActivities($this->operator('B'), [
            'report_date' => '2026-07-12',
            'group_name' => 'B',
            'shift' => 'Malam',
        ], ['kantong' => 60]);

        $service = app(OperationalPerformanceService::class);

        $filters = [
            'start' => Carbon::parse('2026-07-01'),
            'end' => Carbon::parse(self::TODAY),
            'group' => 'A',
            'shift' => 'Pagi',
        ];

        $report = $service->performanceReport($filters);
        $panels = collect($report['activityPanels'])->keyBy('key');

        // Filter regu + shift menyaring nilai kegiatan…
        $this->assertSame(100.0, (float) $panels['muat_kantong']['value']);

        // …tetapi peringkat regu tetap membandingkan seluruh regu pada shift
        // yang sedang disaring: regu B shift Pagi ikut, shift Malam-nya tidak.
        $groups = collect($panels['muat_kantong']['groups'])->keyBy('name');
        $this->assertEqualsCanonicalizing(['A', 'B'], $groups->keys()->all());
        $this->assertSame(40.0, (float) $groups['B']['value']);

        // Tanpa filter, seluruh 200 Ton kantong + 60 Ton trucking terhitung.
        $unfiltered = $service->performanceReport(array_merge($filters, ['group' => null, 'shift' => null]));
        $this->assertSame(260.0, (float) $unfiltered['summary']['tonnage']['value']);
    }

    public function test_tc_mgr_21_blok_tanpa_data_tidak_dirender(): void
    {
        $this->freezeToday();

        $manager = $this->manager();

        // Periode tanpa laporan sama sekali: tidak ada batang tren, tidak ada
        // batang peringkat, hanya keterangan kosong.
        $this->actingAs($manager)
            ->get(route('manajer.performa', ['dari' => '2026-03-01', 'sampai' => '2026-03-31']))
            ->assertOk()
            ->assertDontSee('act-trend__bar', false)
            ->assertDontSee('rank-bar__fill', false)
            ->assertSee('Belum ada', false);
    }

    public function test_tc_mgr_22_ekspor_mengikuti_periode_halaman(): void
    {
        $this->freezeToday();

        $manager = $this->manager();

        // Kedua halaman dibuka pada Januari sampai hari ini, sehingga tautan
        // ekspornya juga memakai periode tahun berjalan.
        $this->actingAs($manager)
            ->get(route('manajer.kegiatan'))
            ->assertOk()
            ->assertSee(route('manajer.kegiatan.export', ['periode' => 'tahun-berjalan']), false);

        $this->actingAs($manager)
            ->get(route('manajer.performa'))
            ->assertOk()
            ->assertSee(route('manajer.performa.export', ['periode' => 'tahun-berjalan']), false);
    }

    public function test_tc_mgr_24_ringkasan_kegiatan_memisah_bulan_berjalan_dan_sebelumnya(): void
    {
        $this->freezeToday();

        $manager = $this->manager();
        $operator = $this->operator('A');

        $this->opsReportWithActivities($operator, ['report_date' => '2026-02-10'], ['kantong' => 100, 'container' => 20]);
        $this->opsReportWithActivities($operator, ['report_date' => '2026-07-10'], ['kantong' => 60, 'container' => 30]);

        $this->actingAs($manager)
            ->get(route('manajer.performa'))
            ->assertOk()
            ->assertDontSee('Ringkasan Kegiatan', false)
            ->assertDontSee('Rekap Kegiatan', false);

        // Rekap sekarang dibaca di dalam tab kegiatan, bukan pada ringkasan
        // seluruh divisi. Rentang tahun berjalan menampilkan tiga segmen.
        $this->actingAs($manager)
            ->get(route('manajer.kegiatan.panel', [
                'key' => 'muat_kantong',
                'periode' => 'tahun-berjalan',
            ]))
            ->assertOk()
            ->assertSee('Rekap Kegiatan', false)
            ->assertSee('Bulan Berjalan', false)
            ->assertSee('Sebelumnya', false)
            ->assertSee('Akumulasi', false);

        $recap = app(OperationalPerformanceService::class)->activityRecap([
            'start' => Carbon::parse('2026-01-01'),
            'end' => Carbon::parse(self::TODAY),
            'group' => null,
            'shift' => null,
        ]);

        $rows = collect($recap['rows'])->keyBy('key');

        $this->assertSame('1-15 Jul 2026', $recap['labels']['month']);
        $this->assertSame('1 Jan - 30 Jun 2026', $recap['labels']['previous']);

        $this->assertSame(60.0, $rows['muat_kantong']['month']['value']);
        $this->assertSame(100.0, $rows['muat_kantong']['previous']['value']);
        $this->assertSame(160.0, $rows['muat_kantong']['total']['value']);
        $this->assertSame(2, $rows['muat_kantong']['total']['count']);

        // Container berdiri sebagai dua kegiatan bersatuan Teus: bongkar
        // menghitung baris Empty, muat menghitung baris Full.
        $this->assertSame('Teus', $rows['bongkar_container']['unit']);
        $this->assertSame(50.0, $rows['bongkar_container']['total']['value']);
        $this->assertSame(20.0, $rows['muat_container']['total']['value']);
    }

    public function test_tc_mgr_25_peringkat_lembur_memuat_regu_rata_rata_dan_perubahan_posisi(): void
    {
        $this->freezeToday();

        $manager = $this->manager();
        $operatorA = $this->operator('A');
        $operatorB = $this->operator('B');

        // Juni adalah periode pembanding 1-15 Juni. Petugas A awalnya posisi
        // pertama dengan dua entri, Petugas B posisi kedua dengan satu entri.
        foreach (['2026-06-02', '2026-06-03'] as $date) {
            $this->opsReportWithActivities(
                $operatorA,
                ['report_date' => $date, 'group_name' => 'A'],
                ['lembur' => 'Petugas A']
            );
        }

        $this->opsReportWithActivities(
            $operatorB,
            ['report_date' => '2026-06-04', 'group_name' => 'B'],
            ['lembur' => 'Petugas B']
        );

        // Pada Juli, tambahan tiga entri Petugas B membuatnya naik satu
        // posisi. Setiap entri berdurasi empat jam dari helper di atas.
        $this->opsReportWithActivities(
            $operatorA,
            ['report_date' => '2026-07-02', 'group_name' => 'A'],
            ['lembur' => 'Petugas A']
        );

        foreach (['2026-07-03', '2026-07-04', '2026-07-05'] as $date) {
            $this->opsReportWithActivities(
                $operatorB,
                ['report_date' => $date, 'group_name' => 'B'],
                ['lembur' => 'Petugas B']
            );
        }

        $report = app(OperationalPerformanceService::class)->performanceReport([
            'start' => Carbon::parse('2026-07-01'),
            'end' => Carbon::parse(self::TODAY),
            'group' => null,
            'shift' => null,
        ]);

        $ranking = collect($report['overtimeLeaders']['ranking'])->keyBy('name');

        $this->assertSame(1, $ranking['Petugas B']['position']);
        $this->assertSame(2, $ranking['Petugas B']['previousPosition']);
        $this->assertSame('up', $ranking['Petugas B']['movement']);
        $this->assertSame(1, $ranking['Petugas B']['movementValue']);
        $this->assertSame('B', $ranking['Petugas B']['group']);
        $this->assertSame(3, $ranking['Petugas B']['count']);
        $this->assertSame(12.0, $ranking['Petugas B']['hours']);
        $this->assertSame(4.0, $ranking['Petugas B']['averageHours']);

        $this->assertSame(2, $ranking['Petugas A']['position']);
        $this->assertSame(1, $ranking['Petugas A']['previousPosition']);
        $this->assertSame('down', $ranking['Petugas A']['movement']);
        $this->assertSame(1, $ranking['Petugas A']['movementValue']);
        $this->assertSame('A', $ranking['Petugas A']['group']);

        Cache::flush();

        $this->actingAs($manager)
            ->get(route('manajer.performa', ['periode' => 'bulan-ini']))
            ->assertOk()
            ->assertSee('overtime-ranking__table', false)
            ->assertSee('data-overtime-sort', false)
            ->assertSee('fi fi-rr-caret-up', false)
            ->assertSee('fi fi-rr-caret-down', false)
            ->assertSee('aria-sort="ascending"', false)
            ->assertDontSee('overtime-ranking__team-label', false)
            ->assertSee('Jumlah Lembur', false)
            ->assertSee('Total Jam Lembur', false)
            ->assertSee('Rata-rata Jam Lembur', false)
            ->assertSee('overtime-ranking__group--a', false)
            ->assertSee('overtime-ranking__group--b', false)
            ->assertSee('Naik 1 posisi', false)
            ->assertSee('Turun 1 posisi', false);
    }

    public function test_tc_mgr_23_jumlah_query_tidak_tumbuh_mengikuti_regu_dan_shift(): void
    {
        $this->freezeToday();

        $manager = $this->manager();
        $url = route('manajer.performa');

        $this->opsReportWithActivities($this->operator('A'), [
            'report_date' => '2026-07-02',
            'group_name' => 'A',
            'shift' => 'Pagi',
        ]);

        // Permintaan pemanasan: relasi peran pengguna baru diambil sekali,
        // dan tanpa ini pengukuran pertama membawa satu query tambahan.
        $this->queryCountFor($manager, $url);

        $baseline = $this->queryCountFor($manager, $url);

        foreach ([['B', 'Sore'], ['C', 'Malam'], ['D', 'Pagi'], ['A', 'Malam']] as $index => [$group, $shift]) {
            $this->opsReportWithActivities($this->operator($group), [
                'report_date' => '2026-07-0'.($index + 3),
                'group_name' => $group,
                'shift' => $shift,
            ]);
        }

        $grown = $this->queryCountFor($manager, $url);

        // Empat regu dan tiga shift lebih banyak, jumlah query tetap sama:
        // seluruh potongan diambil dari satu matriks agregat.
        $this->assertSame($baseline, $grown);
        $this->assertLessThan(35, $grown, 'Halaman Kinerja Operasi menjalankan terlalu banyak query.');
    }
}
