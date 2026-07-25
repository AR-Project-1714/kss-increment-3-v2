<?php

namespace Tests\Feature\BlackBox;

use App\Enums\ReportStatus;
use App\Models\DailyReport;
use Carbon\Carbon;

/**
 * Modul K — Operasional / Laporan Operasi Harian (PENGUJIAN_BLACKBOX.md §4.K).
 */
class OperationalReportTest extends BlackBoxTestCase
{
    private function opsDocId(DailyReport $report): string
    {
        return '#OPS-2026-'.str_pad((string) $report->id, 3, '0', STR_PAD_LEFT);
    }

    /** @return array<string, mixed> */
    private function validSubmitPayload(array $overrides = []): array
    {
        return array_merge([
            'status' => 'submitted',
            'report_date' => '2026-05-19',
            'shift' => 'Pagi',
            'group_name' => 'A',
            'received_by_group' => 'B',
            'time_range' => '07.00 - 15.00',
        ], $overrides);
    }

    public function test_tc_ops_01_halaman_menampilkan_tiga_tab(): void
    {
        $this->actingAs($this->operator('A'))
            ->get(route('report-ops.index'))
            ->assertOk()
            ->assertSee('Laporan Masuk', false)
            ->assertSee('Draft', false)
            ->assertSee('Riwayat Laporan', false)
            ->assertSee('Laporan Diterima', false);
    }

    public function test_tc_ops_02_step1_info_umum_valid_dapat_disimpan(): void
    {
        $operator = $this->operator('A');

        $this->actingAs($operator)
            ->post(route('report-ops.store'), $this->validSubmitPayload([
                'ship_name_1' => 'KM Info Umum',
            ]))
            ->assertRedirect(route('report-ops.index'));

        $this->assertDatabaseHas('daily_reports', [
            'created_by' => $operator->id,
            'status' => ReportStatus::Submitted->value,
            'group_name' => 'A',
            'received_by_group' => 'B',
        ]);
    }

    public function test_tc_ops_03_field_wajib_kosong_ditolak(): void
    {
        $operator = $this->operator('A');

        $this->actingAs($operator)
            ->from(route('report-ops.create'))
            ->post(route('report-ops.store'), ['status' => 'submitted'])
            ->assertRedirect(route('report-ops.create'))
            ->assertSessionHasErrors(['report_date', 'shift', 'group_name', 'received_by_group', 'time_range']);
    }

    public function test_tc_ops_04_regu_tujuan_sama_dengan_regu_sendiri_ditolak(): void
    {
        $operator = $this->operator('B');

        $this->actingAs($operator)
            ->from(route('report-ops.create'))
            ->post(route('report-ops.store'), $this->validSubmitPayload([
                'group_name' => 'B',
                'received_by_group' => 'B',
            ]))
            ->assertRedirect(route('report-ops.create'))
            ->assertSessionHasErrors('received_by_group');
    }

    public function test_tc_ops_05_semua_langkah_dapat_diisi(): void
    {
        $operator = $this->operator('A');

        $this->actingAs($operator)
            ->post(route('report-ops.store'), $this->validSubmitPayload([
                'ship_name_1' => 'KM Lengkap',
                'capacity_1' => '1000',
                'unit_logs' => [
                    [
                        'item_name' => 'Forklift Cek',
                        'condition_received' => 'Baik',
                        'condition_handed_over' => 'Baik',
                    ],
                ],
            ]))
            ->assertRedirect(route('report-ops.index'));

        $report = DailyReport::where('created_by', $operator->id)->firstOrFail();
        $this->assertSame(ReportStatus::Submitted, $report->status);
        $this->assertDatabaseHas('unit_check_logs', ['item_name' => 'Forklift Cek']);
    }

    public function test_tc_ops_06_simpan_sebagai_draft(): void
    {
        $operator = $this->operator('A');

        $this->actingAs($operator)
            ->post(route('report-ops.store'), [
                'status' => 'draft',
                'ship_name_1' => 'KM Draft',
            ])
            ->assertRedirect(route('report-ops.index'));

        $this->assertDatabaseHas('daily_reports', [
            'created_by' => $operator->id,
            'status' => ReportStatus::Draft->value,
        ]);
    }

    public function test_tc_ops_07_serahkan_laporan_berstatus_submitted(): void
    {
        $operator = $this->operator('A');

        $this->actingAs($operator)
            ->post(route('report-ops.store'), $this->validSubmitPayload())
            ->assertRedirect(route('report-ops.index'));

        $this->assertDatabaseHas('daily_reports', [
            'created_by' => $operator->id,
            'status' => ReportStatus::Submitted->value,
        ]);
    }

    public function test_tc_ops_08_lanjutkan_draft_membuka_kembali_data(): void
    {
        $operator = $this->operator('A');
        $draft = DailyReport::create([
            'user_id' => $operator->id,
            'created_by' => $operator->id,
            'report_date' => now()->toDateString(),
            'shift' => 'Pagi',
            'group_name' => 'A',
            'received_by_group' => 'B',
            'time_range' => '07:00 - 15:00',
            'status' => ReportStatus::Draft,
        ]);

        $this->actingAs($operator)
            ->get(route('report-ops.edit', $draft))
            ->assertOk();
    }

    public function test_tc_ops_09_hapus_draft(): void
    {
        $operator = $this->operator('A');
        $draft = DailyReport::create([
            'user_id' => $operator->id,
            'created_by' => $operator->id,
            'report_date' => now()->toDateString(),
            'shift' => 'Pagi',
            'group_name' => 'A',
            'received_by_group' => 'B',
            'time_range' => '07:00 - 15:00',
            'status' => ReportStatus::Draft,
        ]);

        $this->actingAs($operator)
            ->delete(route('report-ops.destroy', $draft))
            ->assertRedirect(route('report-ops.index'))
            ->assertSessionHas('success', 'Draft laporan berhasil dihapus.');

        $this->assertDatabaseMissing('daily_reports', ['id' => $draft->id]);
    }

    public function test_tc_ops_10_tab_laporan_masuk_menampilkan_laporan_regu_lain(): void
    {
        $sender = $this->operator('B');
        $receiver = $this->operator('A');

        $incoming = DailyReport::create([
            'user_id' => $sender->id,
            'created_by' => $sender->id,
            'report_date' => '2026-05-20',
            'shift' => 'Pagi',
            'group_name' => 'B',
            'received_by_group' => 'A',
            'time_range' => '07:00 - 15:00',
            'status' => ReportStatus::Submitted,
        ]);

        $this->actingAs($receiver)
            ->get(route('report-ops.index'))
            ->assertOk()
            ->assertSee($this->opsDocId($incoming), false);
    }

    public function test_tc_ops_11_menandatangani_laporan_masuk(): void
    {
        $sender = $this->operator('B');
        $receiver = $this->operator('A');

        $incoming = DailyReport::create([
            'user_id' => $sender->id,
            'created_by' => $sender->id,
            'report_date' => '2026-05-20',
            'shift' => 'Pagi',
            'group_name' => 'B',
            'received_by_group' => 'A',
            'time_range' => '07:00 - 15:00',
            'status' => ReportStatus::Submitted,
        ]);

        $this->actingAs($receiver)
            ->post(route('report-ops.sign', $incoming))
            ->assertRedirect()
            ->assertSessionHas('success', 'Laporan berhasil diterima dan ditanda tangani.');

        $this->assertDatabaseHas('daily_reports', [
            'id' => $incoming->id,
            'status' => ReportStatus::Acknowledged->value,
            'received_by_user_id' => $receiver->id,
        ]);
    }

    public function test_tc_ops_12_riwayat_unduh_pdf(): void
    {
        $operator = $this->operator('A');
        $report = DailyReport::create([
            'user_id' => $operator->id,
            'created_by' => $operator->id,
            'report_date' => '2026-05-20',
            'shift' => 'Pagi',
            'group_name' => 'A',
            'received_by_group' => 'B',
            'time_range' => '07:00 - 15:00',
            'status' => ReportStatus::Submitted,
        ]);

        $response = $this->actingAs($operator)->get(route('report-ops.pdf', $report));
        $response->assertOk();
        $this->assertSame('application/pdf', strtolower((string) $response->headers->get('content-type')));
    }

    public function test_tc_ops_13_riwayat_unduh_excel(): void
    {
        $operator = $this->operator('A');
        $report = DailyReport::create([
            'user_id' => $operator->id,
            'created_by' => $operator->id,
            'report_date' => '2026-05-20',
            'shift' => 'Pagi',
            'group_name' => 'A',
            'received_by_group' => 'B',
            'time_range' => '07:00 - 15:00',
            'status' => ReportStatus::Submitted,
        ]);

        $response = $this->actingAs($operator)->get(route('report-ops.excel', $report));
        $response->assertOk();
        $this->assertStringContainsString(
            'spreadsheetml.sheet',
            strtolower((string) $response->headers->get('content-type'))
        );
    }

    public function test_tc_ops_14_sesi_terputus_menyimpan_otomatis_sebagai_draft(): void
    {
        $operator = $this->operator('A');

        // Autosave mengirim status=submitted tapi server memaksa draft + JSON update_url.
        $response = $this->actingAs($operator)->post(route('report-ops.store'), [
            'status' => 'submitted',
            'autosave' => 1,
            'report_date' => '2026-06-10',
            'ship_name_1' => 'KM Autosave',
        ]);

        $response->assertOk();
        $response->assertJson(['ok' => true]);

        $report = DailyReport::where('created_by', $operator->id)->firstOrFail();
        $this->assertSame(ReportStatus::Draft, $report->status);
    }

    public function test_tc_ops_15_laporan_kombinasi_sama_persis_ditolak_tanpa_konfirmasi(): void
    {
        $operator = $this->operator('A');

        $this->actingAs($operator)
            ->post(route('report-ops.store'), $this->validSubmitPayload())
            ->assertRedirect(route('report-ops.index'));

        // Proteksi ketat: submit kedua dengan tanggal dinas + shift + regu sama
        // persis DITOLAK, kecuali petugas mencentang konfirmasi laporan ganda.
        $this->actingAs($operator)
            ->from(route('report-ops.create'))
            ->post(route('report-ops.store'), $this->validSubmitPayload())
            ->assertRedirect(route('report-ops.create'))
            ->assertSessionHasErrors('report_date')
            ->assertSessionHas('duplicate_report_shift');

        $this->assertSame(1, DailyReport::where('status', ReportStatus::Submitted->value)->count());
    }

    public function test_tc_ops_16_masa_simpan_draft_dapat_diperpanjang(): void
    {
        $operator = $this->operator('A');

        $draft = DailyReport::create([
            'user_id' => $operator->id,
            'created_by' => $operator->id,
            'report_date' => '2026-05-19',
            'status' => ReportStatus::Draft,
        ]);
        DailyReport::whereKey($draft->id)->update(['updated_at' => now()->subDays(2)]);

        $this->actingAs($operator)
            ->post(route('report-ops.extend-draft', $draft))
            ->assertRedirect(route('report-ops.index', ['tab' => 'draft']));

        // updated_at tersegarkan sehingga hitungan masa simpan (3 hari) dimulai ulang.
        $this->assertTrue($draft->fresh()->updated_at->gt(now()->subMinute()));

        // Bukan pembuat draft: ditolak.
        $this->actingAs($this->operator('B'))
            ->post(route('report-ops.extend-draft', $draft))
            ->assertForbidden();
    }

    private function submittedReport(string $group, string $date, string $shift, string $timeRange): DailyReport
    {
        $operator = $this->operator($group);

        return DailyReport::create([
            'user_id' => $operator->id,
            'created_by' => $operator->id,
            'report_date' => $date,
            'shift' => $shift,
            'group_name' => $group,
            'received_by_group' => $group === 'A' ? 'B' : 'A',
            'time_range' => $timeRange,
            'status' => ReportStatus::Submitted,
        ]);
    }

    public function test_tc_ops_17_laporan_kombinasi_sama_bisa_lanjut_setelah_konfirmasi(): void
    {
        // Laporan dengan kombinasi tanggal dinas + shift + regu yang sama boleh
        // dikirim (mis. koreksi) HANYA setelah petugas mencentang konfirmasi.
        $this->submittedReport('A', '2026-05-19', 'Pagi', '07.00 - 15.00');

        $this->actingAs($this->operator('A'))
            ->post(route('report-ops.store'), $this->validSubmitPayload([
                'report_date' => '2026-05-19',
                'shift' => 'Pagi',
                'group_name' => 'A',
                'confirm_duplicate' => '1',
            ]))
            ->assertRedirect(route('report-ops.index'))
            ->assertSessionHasNoErrors();

        $this->assertSame(2, DailyReport::where('status', ReportStatus::Submitted->value)->count());
    }

    public function test_tc_ops_17b_shift_berbeda_tanggal_sama_diterima_tanpa_konfirmasi(): void
    {
        // Shift yang berbeda pada tanggal yang sama BUKAN laporan ganda, jadi lolos
        // tanpa perlu konfirmasi.
        $this->submittedReport('A', '2026-05-19', 'Pagi', '07.00 - 15.00');

        $this->actingAs($this->operator('A'))
            ->post(route('report-ops.store'), $this->validSubmitPayload([
                'report_date' => '2026-05-19',
                'shift' => 'Sore',
                'group_name' => 'A',
                'time_range' => '15.00 - 23.00',
            ]))
            ->assertRedirect(route('report-ops.index'))
            ->assertSessionHasNoErrors();

        $this->assertSame(2, DailyReport::where('status', ReportStatus::Submitted->value)->count());
    }

    public function test_tc_ops_18_shift_malam_lintas_tengah_malam_bukan_laporan_ganda(): void
    {
        // Shift malam yang MULAI 19 Mei sudah tersimpan (diisi malam harinya).
        $this->submittedReport('A', '2026-05-19', 'Malam', '23.00 - 07.00');

        // Shift malam BERIKUTNYA diisi di awal jam kerja: 20 Mei pukul 23.15. Karena
        // jam >= 12, tanggal dinas = 20 Mei -> shift yang berbeda -> diterima tanpa
        // konfirmasi (tidak dianggap ganda meski keduanya shift Malam regu A).
        Carbon::setTestNow(Carbon::create(2026, 5, 20, 23, 15, 0, 'Asia/Makassar'));

        $this->actingAs($this->operator('A'))
            ->post(route('report-ops.store'), $this->validSubmitPayload([
                'report_date' => '2026-05-20',
                'shift' => 'Malam',
                'group_name' => 'A',
                'time_range' => '23.00 - 07.00',
            ]))
            ->assertRedirect(route('report-ops.index'))
            ->assertSessionHasNoErrors();

        Carbon::setTestNow();

        $this->assertSame(2, DailyReport::where('status', ReportStatus::Submitted->value)->count());
        $this->assertSame(1, DailyReport::whereDate('report_date', '2026-05-20')->where('shift', 'Malam')->count());
    }

    public function test_tc_ops_19_laporan_malam_dini_hari_dinormalisasi_ke_tanggal_mulai(): void
    {
        // Shift malam yang MULAI 19 Mei sudah tersimpan.
        $this->submittedReport('A', '2026-05-19', 'Malam', '23.00 - 07.00');

        // Petugas shift yang SAMA mengisi ulang dini hari 20 Mei pukul 03.00 dengan
        // tanggal default "hari ini" (20 Mei). Sistem menormalkan ke tanggal shift
        // mulai (19 Mei) -> terdeteksi sebagai laporan untuk shift yang sama ->
        // ditolak dan meminta konfirmasi.
        Carbon::setTestNow(Carbon::create(2026, 5, 20, 3, 0, 0, 'Asia/Makassar'));

        $this->actingAs($this->operator('A'))
            ->from(route('report-ops.create'))
            ->post(route('report-ops.store'), $this->validSubmitPayload([
                'report_date' => '2026-05-20',
                'shift' => 'Malam',
                'group_name' => 'A',
                'time_range' => '23.00 - 07.00',
            ]))
            ->assertRedirect(route('report-ops.create'))
            ->assertSessionHasErrors('report_date')
            ->assertSessionHas('duplicate_report_shift');

        // Dengan konfirmasi, laporan koreksi tetap bisa dikirim dan tercatat di 19 Mei.
        $this->actingAs($this->operator('A'))
            ->post(route('report-ops.store'), $this->validSubmitPayload([
                'report_date' => '2026-05-20',
                'shift' => 'Malam',
                'group_name' => 'A',
                'time_range' => '23.00 - 07.00',
                'confirm_duplicate' => '1',
            ]))
            ->assertRedirect(route('report-ops.index'));

        Carbon::setTestNow();

        $this->assertSame(2, DailyReport::whereDate('report_date', '2026-05-19')->where('shift', 'Malam')->count());
    }

    public function test_tc_ops_20_shift_bukan_malam_tanggal_berdekatan_tidak_diperingatkan(): void
    {
        // Kelonggaran hanya untuk shift malam yang melewati tengah malam. Shift Pagi
        // di tanggal berdekatan adalah shift yang jelas berbeda, jadi lolos tanpa peringatan.
        $this->submittedReport('A', '2026-05-19', 'Pagi', '07.00 - 15.00');

        $this->actingAs($this->operator('A'))
            ->post(route('report-ops.store'), $this->validSubmitPayload([
                'report_date' => '2026-05-20',
                'shift' => 'Pagi',
                'group_name' => 'A',
                'time_range' => '07.00 - 15.00',
            ]))
            ->assertRedirect(route('report-ops.index'));

        $this->assertSame(2, DailyReport::where('status', ReportStatus::Submitted->value)->count());
    }

    public function test_tc_ops_21_regu_boleh_kirim_tiga_laporan_pada_tanggal_sama(): void
    {
        $operator = $this->operator('A');

        $shifts = [
            ['Pagi', '07.00 - 15.00'],
            ['Sore', '15.00 - 23.00'],
            ['Malam', '23.00 - 07.00'],
        ];

        foreach ($shifts as [$shift, $range]) {
            $this->actingAs($operator)
                ->post(route('report-ops.store'), $this->validSubmitPayload([
                    'report_date' => '2026-06-01',
                    'shift' => $shift,
                    'group_name' => 'A',
                    'time_range' => $range,
                ]))
                ->assertRedirect(route('report-ops.index'));
        }

        $this->assertSame(3, DailyReport::where('group_name', 'A')
            ->whereDate('report_date', '2026-06-01')
            ->where('status', ReportStatus::Submitted->value)
            ->count());
    }

    public function test_tc_ops_22_endpoint_deteksi_potensi_laporan_ganda(): void
    {
        $this->submittedReport('A', '2026-06-02', 'Pagi', '07.00 - 15.00');

        // Kombinasi tanggal dinas + shift + regu yang sama -> potensi laporan ganda.
        $this->actingAs($this->operator('A'))
            ->getJson(route('report-ops.day-report-count', [
                'report_date' => '2026-06-02',
                'group_name' => 'A',
                'shift' => 'Pagi',
            ]))
            ->assertOk()
            ->assertJson(['duplicate' => true]);

        // Shift berbeda pada tanggal sama -> bukan laporan ganda.
        $this->actingAs($this->operator('A'))
            ->getJson(route('report-ops.day-report-count', [
                'report_date' => '2026-06-02',
                'group_name' => 'A',
                'shift' => 'Sore',
            ]))
            ->assertOk()
            ->assertJson(['duplicate' => false]);
    }
}
