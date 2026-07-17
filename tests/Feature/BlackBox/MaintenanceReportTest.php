<?php

namespace Tests\Feature\BlackBox;

use App\Enums\MaintenanceStatus;
use App\Models\MaintenanceReport;
use App\Models\MasterUnit;

/**
 * Modul L — Pemeliharaan / Laporan Pemeliharaan (PENGUJIAN_BLACKBOX.md §4.L).
 */
class MaintenanceReportTest extends BlackBoxTestCase
{
    private function unit(): MasterUnit
    {
        return MasterUnit::create([
            'name' => 'Forklift KSS-01',
            'type' => 'Forklift',
            'unit_code' => 'FL',
            'brand' => 'YALE',
            'unit_number' => 'KSS-01',
            'macro_category' => MasterUnit::MACRO_HEAVY,
            'status' => 'active',
        ]);
    }

    public function test_tc_pml_01_mengisi_lima_langkah_dan_serahkan(): void
    {
        $user = $this->maintenance();
        $unit = $this->unit();

        $this->actingAs($user)
            ->post(route('pemeliharaan.store'), [
                'status' => MaintenanceStatus::Submitted->value,
                'report_date' => '2026-05-31',
                'main_items' => [
                    ['work_group' => 'I', 'description' => 'Servis rutin', 'assignee' => 'Mekanik A'],
                ],
                'priority_items' => [
                    ['description' => 'Ganti oli prioritas', 'assignee' => 'Mekanik B'],
                ],
                'conditions' => [
                    $unit->id => ['condition' => 'ready'],
                ],
                'attendances' => [
                    ['employee_name' => 'Hadir Satu', 'position' => 'Mekanik', 'time_in' => '07:00', 'time_out' => '16:00'],
                ],
            ])
            ->assertRedirect(route('pemeliharaan.index'))
            ->assertSessionHas('success', 'Laporan pemeliharaan berhasil dikirim.');

        $report = MaintenanceReport::where('created_by', $user->id)->firstOrFail();
        $this->assertSame(MaintenanceStatus::Submitted, $report->status);
        $this->assertDatabaseHas('maintenance_work_items', ['maintenance_report_id' => $report->id, 'description' => 'Servis rutin']);
        $this->assertDatabaseHas('maintenance_unit_conditions', ['maintenance_report_id' => $report->id, 'condition' => 'ready']);
        $this->assertDatabaseHas('maintenance_attendances', ['maintenance_report_id' => $report->id, 'employee_name' => 'Hadir Satu']);
    }

    public function test_tc_pml_02_data_wajib_kosong_ditolak(): void
    {
        $user = $this->maintenance();

        $this->actingAs($user)
            ->from(route('pemeliharaan.create'))
            ->post(route('pemeliharaan.store'), [
                'status' => MaintenanceStatus::Submitted->value,
                // report_date sengaja dikosongkan.
            ])
            ->assertSessionHasErrors('report_date');
    }

    public function test_tc_pml_03_simpan_sebagai_draft(): void
    {
        $user = $this->maintenance();

        $this->actingAs($user)
            ->post(route('pemeliharaan.store'), [
                'status' => MaintenanceStatus::Draft->value,
                'report_date' => '2026-05-31',
            ])
            ->assertRedirect(route('pemeliharaan.index'))
            ->assertSessionHas('success', 'Draft laporan pemeliharaan berhasil disimpan.');

        $this->assertDatabaseHas('maintenance_reports', [
            'created_by' => $user->id,
            'status' => MaintenanceStatus::Draft->value,
        ]);
    }

    public function test_tc_pml_04_serahkan_langsung_muncul_di_dashboard_manajer(): void
    {
        $user = $this->maintenance();
        $manager = $this->manager();

        $this->actingAs($user)
            ->post(route('pemeliharaan.store'), [
                'status' => MaintenanceStatus::Submitted->value,
                'report_date' => '2026-05-31',
            ])
            ->assertRedirect(route('pemeliharaan.index'));

        $report = MaintenanceReport::where('created_by', $user->id)->firstOrFail();
        $this->assertSame(MaintenanceStatus::Submitted, $report->status);
        $this->assertNotNull($report->submitted_at);

        $mntId = '#MNT-2026-'.str_pad((string) $report->id, 3, '0', STR_PAD_LEFT);
        $this->actingAs($manager)
            ->get(route('manajer.index'))
            ->assertOk()
            ->assertSee($mntId, false);
    }

    public function test_tc_pml_05_lanjutkan_dan_hapus_draft(): void
    {
        $user = $this->maintenance();
        $draft = $this->submittedMaintenanceReport($user, [
            'status' => MaintenanceStatus::Draft,
            'submitted_at' => null,
        ]);

        // Lanjutkan draft.
        $this->actingAs($user)->get(route('pemeliharaan.edit', $draft))->assertOk();

        // Hapus draft.
        $this->actingAs($user)
            ->delete(route('pemeliharaan.destroy', $draft))
            ->assertRedirect(route('pemeliharaan.index'))
            ->assertSessionHas('success', 'Draft laporan pemeliharaan berhasil dihapus.');

        $this->assertDatabaseMissing('maintenance_reports', ['id' => $draft->id]);
    }

    public function test_tc_pml_06_unduh_pdf(): void
    {
        $user = $this->maintenance();
        $report = $this->submittedMaintenanceReport($user);

        $response = $this->actingAs($user)->get(route('pemeliharaan.pdf', $report));
        $response->assertOk();
        $this->assertSame('application/pdf', strtolower((string) $response->headers->get('content-type')));
    }

    public function test_tc_pml_07_sesi_terputus_menyimpan_otomatis_sebagai_draft(): void
    {
        $user = $this->maintenance();

        $response = $this->actingAs($user)->post(route('pemeliharaan.store'), [
            'status' => MaintenanceStatus::Submitted->value,
            'autosave' => 1,
            'report_date' => '2026-05-31',
        ]);

        $response->assertOk();
        $response->assertJson(['ok' => true]);

        $report = MaintenanceReport::where('created_by', $user->id)->firstOrFail();
        $this->assertSame(MaintenanceStatus::Draft, $report->status);
    }

    public function test_tc_pml_08_pekerjaan_belum_selesai_dimuat_di_laporan_baru(): void
    {
        $user = $this->maintenance();

        // Laporan kemarin: satu pekerjaan prioritas selesai, satu belum.
        $this->actingAs($user)
            ->post(route('pemeliharaan.store'), [
                'status' => MaintenanceStatus::Submitted->value,
                'report_date' => '2026-05-31',
                'priority_items' => [
                    ['description' => 'Ganti oli sudah beres', 'assignee' => 'Mekanik A', 'is_completed' => 1],
                    ['description' => 'Perbaikan rem belum tuntas', 'assignee' => 'Mekanik B', 'is_completed' => 0],
                ],
            ])
            ->assertRedirect(route('pemeliharaan.index'));

        // Form laporan baru memuat pekerjaan yang belum selesai sebagai lanjutan.
        $this->actingAs($user)
            ->get(route('pemeliharaan.create'))
            ->assertOk()
            ->assertSee('Perbaikan rem belum tuntas', false)
            ->assertSee('Lanjutan dari', false)
            ->assertDontSee('Ganti oli sudah beres', false);
    }

    public function test_tc_pml_09_laporan_ganda_tanggal_sama_ditolak(): void
    {
        $user = $this->maintenance();

        $this->actingAs($user)
            ->post(route('pemeliharaan.store'), [
                'status' => MaintenanceStatus::Submitted->value,
                'report_date' => '2026-05-31',
            ])
            ->assertRedirect(route('pemeliharaan.index'));

        $this->actingAs($user)
            ->from(route('pemeliharaan.create'))
            ->post(route('pemeliharaan.store'), [
                'status' => MaintenanceStatus::Submitted->value,
                'report_date' => '2026-05-31',
            ])
            ->assertRedirect(route('pemeliharaan.create'))
            ->assertSessionHasErrors('report_date');

        $this->assertSame(1, MaintenanceReport::where('status', MaintenanceStatus::Submitted->value)->count());
    }
}
