<?php

namespace Tests\Feature;

use App\Enums\MaintenanceStatus;
use App\Enums\ReportStatus;
use App\Enums\SafetyStatus;
use App\Models\DailyReport;
use App\Models\MaintenanceReport;
use App\Models\SafetyReport;
use App\Models\User;
use Tests\Feature\BlackBox\BlackBoxTestCase;

/**
 * Hapus cepat seluruh draft pada tab Draft halaman petugas.
 *
 * Aksi ini merusak dan berlaku untuk banyak baris sekaligus, jadi batasnya yang
 * diuji: hanya draft, hanya milik petugas yang sedang masuk, dan laporan yang
 * sudah dikirim atau disetujui tidak boleh ikut terhapus.
 */
class BulkDraftDeletionTest extends BlackBoxTestCase
{
    private function opsDraft(User $creator, string $date): DailyReport
    {
        return DailyReport::create([
            'user_id' => $creator->id,
            'created_by' => $creator->id,
            'report_date' => $date,
            'shift' => 'Pagi',
            'group_name' => 'A',
            'received_by_group' => 'B',
            'time_range' => '07:00 - 15:00',
            'status' => ReportStatus::Draft,
        ]);
    }

    public function test_operasional_menghapus_semua_draft_miliknya_sendiri(): void
    {
        $user = $this->operator('A');

        $this->opsDraft($user, '2026-05-18');
        $this->opsDraft($user, '2026-05-19');
        $this->opsDraft($user, '2026-05-20');

        $this->assertSame(3, DailyReport::where('created_by', $user->id)->count());

        $this->actingAs($user)
            ->delete(route('report-ops.drafts.destroy-all'))
            ->assertRedirect(route('report-ops.index', ['tab' => 'draft']))
            ->assertSessionHas('success');

        $this->assertSame(0, DailyReport::where('created_by', $user->id)->count());
    }

    public function test_operasional_tidak_menyentuh_laporan_yang_sudah_dikirim_atau_disetujui(): void
    {
        $user = $this->operator('A');
        $manager = $this->manager();

        $this->opsDraft($user, '2026-05-18');
        $approved = $this->approvedOpsReport($user, $manager, ['report_date' => '2026-05-19']);
        $acknowledged = $this->acknowledgedOpsReport($user, ['report_date' => '2026-05-20']);

        $this->actingAs($user)->delete(route('report-ops.drafts.destroy-all'));

        $this->assertDatabaseHas('daily_reports', ['id' => $approved->id]);
        $this->assertDatabaseHas('daily_reports', ['id' => $acknowledged->id]);
        $this->assertSame(0, DailyReport::where('created_by', $user->id)
            ->where('status', ReportStatus::Draft)
            ->count());
    }

    public function test_operasional_tidak_menghapus_draft_milik_petugas_lain(): void
    {
        $user = $this->operator('A');
        $lain = $this->operator('B');

        $this->opsDraft($user, '2026-05-18');
        $draftLain = $this->opsDraft($lain, '2026-05-19');

        $this->actingAs($user)->delete(route('report-ops.drafts.destroy-all'));

        $this->assertDatabaseHas('daily_reports', ['id' => $draftLain->id]);
        $this->assertSame(1, DailyReport::where('created_by', $lain->id)->count());
    }

    public function test_operasional_tanpa_draft_mendapat_pesan_galat_bukan_sukses(): void
    {
        $user = $this->operator('A');

        $this->actingAs($user)
            ->delete(route('report-ops.drafts.destroy-all'))
            ->assertRedirect(route('report-ops.index', ['tab' => 'draft']))
            ->assertSessionHas('error');
    }

    public function test_pemeliharaan_menghapus_semua_draft_tanpa_menyentuh_laporan_terkirim(): void
    {
        $user = $this->maintenance();

        MaintenanceReport::create([
            'report_date' => '2026-05-18',
            'day_name' => 'Senin',
            'status' => MaintenanceStatus::Draft,
            'created_by' => $user->id,
        ]);
        MaintenanceReport::create([
            'report_date' => '2026-05-19',
            'day_name' => 'Selasa',
            'status' => MaintenanceStatus::Draft,
            'created_by' => $user->id,
        ]);
        $terkirim = $this->submittedMaintenanceReport($user, ['report_date' => '2026-05-20']);

        $this->actingAs($user)
            ->delete(route('pemeliharaan.drafts.destroy-all'))
            ->assertRedirect(route('pemeliharaan.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('maintenance_reports', ['id' => $terkirim->id]);
        $this->assertSame(0, MaintenanceReport::where('created_by', $user->id)
            ->where('status', MaintenanceStatus::Draft)
            ->count());
    }

    public function test_k3_menghapus_semua_draft_tanpa_menyentuh_laporan_terkirim(): void
    {
        $user = $this->safety();

        SafetyReport::create([
            'report_date' => '2026-05-18',
            'time_range' => '07:00 - 16:00',
            'status' => SafetyStatus::Draft,
            'created_by' => $user->id,
        ]);
        SafetyReport::create([
            'report_date' => '2026-05-19',
            'time_range' => '07:00 - 16:00',
            'status' => SafetyStatus::Draft,
            'created_by' => $user->id,
        ]);
        $terkirim = $this->submittedSafetyReport($user, ['report_date' => '2026-05-20']);

        $this->actingAs($user)
            ->delete(route('safety.drafts.destroy-all'))
            ->assertRedirect(route('safety.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('safety_reports', ['id' => $terkirim->id]);
        $this->assertSame(0, SafetyReport::where('created_by', $user->id)
            ->where('status', SafetyStatus::Draft)
            ->count());
    }

    /**
     * EnsureRole memantulkan peran yang ditolak ke dashboard-nya sendiri (403
     * hanya untuk permintaan JSON), jadi yang diperiksa adalah pantulan itu dan
     * — yang lebih penting — draft modul lain tidak ikut terhapus.
     */
    public function test_petugas_lain_tidak_bisa_mengakses_rute_hapus_massal_modul_bukan_miliknya(): void
    {
        $operator = $this->operator('A');
        $draftOperator = $this->opsDraft($operator, '2026-05-18');

        $user = $this->safety();

        $this->actingAs($user)
            ->delete(route('report-ops.drafts.destroy-all'))
            ->assertRedirect(route('safety.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('daily_reports', ['id' => $draftOperator->id]);
    }
}
