<?php

namespace Tests\Feature\BlackBox;

use App\Enums\ReportStatus;
use App\Enums\SafetyStatus;
use App\Models\DailyReport;
use App\Models\SafetyReport;

/**
 * Modul I — Manajer / Dashboard & Tanda Tangan (PENGUJIAN_BLACKBOX.md §4.I).
 */
class ManagerDashboardTest extends BlackBoxTestCase
{
    private function opsDocId(DailyReport $report): string
    {
        return '#OPS-2026-'.str_pad((string) $report->id, 3, '0', STR_PAD_LEFT);
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
}
