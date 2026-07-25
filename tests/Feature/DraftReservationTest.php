<?php

namespace Tests\Feature;

use App\Models\DailyReport;
use App\Models\MaintenanceReport;
use App\Models\SafetyReport;
use Tests\Feature\BlackBox\BlackBoxTestCase;

/**
 * Reservasi draft saat form laporan baru dibuka.
 *
 * Dulu form baru menembak endpoint store, sementara penyimpanan lewat
 * navigator.sendBeacon (tab disembunyikan/ditutup) tidak bisa membaca response
 * sehingga tidak pernah tahu ID draft yang baru dibuat — tiap kali tab
 * disembunyikan lahir draft baru. Sekarang barisnya direservasi lebih dulu dan
 * form selalu menimpa baris yang sama.
 */
class DraftReservationTest extends BlackBoxTestCase
{
    public function test_membuka_form_pemeliharaan_berkali_kali_hanya_menyisakan_satu_draft(): void
    {
        $user = $this->maintenance();

        for ($i = 0; $i < 3; $i++) {
            $this->actingAs($user)->get(route('pemeliharaan.create'))->assertOk();
        }

        $this->assertSame(1, MaintenanceReport::where('created_by', $user->id)->count());
    }

    public function test_membuka_form_operasional_berkali_kali_hanya_menyisakan_satu_draft(): void
    {
        $user = $this->operator('A');

        for ($i = 0; $i < 3; $i++) {
            $this->actingAs($user)->get(route('report-ops.create'))->assertOk();
        }

        $this->assertSame(1, DailyReport::where('created_by', $user->id)->count());
    }

    public function test_membuka_form_k3_berkali_kali_hanya_menyisakan_satu_draft(): void
    {
        $user = $this->safety();

        for ($i = 0; $i < 3; $i++) {
            $this->actingAs($user)->get(route('safety.create'))->assertOk();
        }

        $this->assertSame(1, SafetyReport::where('created_by', $user->id)->count());
    }

    public function test_id_dokumen_langsung_tampil_di_form_baru(): void
    {
        $user = $this->maintenance();

        $html = $this->actingAs($user)->get(route('pemeliharaan.create'))->assertOk()->getContent();
        $report = MaintenanceReport::where('created_by', $user->id)->firstOrFail();

        $this->assertStringNotContainsString('Draft Baru', $html);
        $this->assertStringContainsString(
            '#MNT-'.now()->format('Y').'-'.str_pad((string) $report->id, 3, '0', STR_PAD_LEFT),
            $html
        );
    }

    public function test_form_baru_mengarah_ke_endpoint_update_bukan_store(): void
    {
        $user = $this->maintenance();

        $html = $this->actingAs($user)->get(route('pemeliharaan.create'))->assertOk()->getContent();
        $report = MaintenanceReport::where('created_by', $user->id)->firstOrFail();

        $this->assertStringContainsString('action="'.route('pemeliharaan.update', $report).'"', $html);
        $this->assertStringContainsString('name="_method" value="PUT"', $html);
    }

    public function test_autosave_berulang_tidak_menambah_draft(): void
    {
        $user = $this->maintenance();

        $this->actingAs($user)->get(route('pemeliharaan.create'))->assertOk();
        $report = MaintenanceReport::where('created_by', $user->id)->firstOrFail();

        // Meniru beacon/autosave yang menembak endpoint update berkali-kali.
        foreach (['2026-05-30', '2026-05-31', '2026-06-01'] as $date) {
            $this->actingAs($user)->put(route('pemeliharaan.update', $report), [
                'autosave' => 1,
                'report_date' => $date,
            ])->assertOk()->assertJson(['ok' => true]);
        }

        $this->assertSame(1, MaintenanceReport::where('created_by', $user->id)->count());
        $this->assertSame('2026-06-01', $report->fresh()->report_date->toDateString());
    }

    public function test_draft_kosong_dibuang_saat_form_ditinggal(): void
    {
        $user = $this->maintenance();

        $this->actingAs($user)->get(route('pemeliharaan.create'))->assertOk();
        $report = MaintenanceReport::where('created_by', $user->id)->firstOrFail();

        $this->actingAs($user)
            ->post(route('pemeliharaan.discard-blank', $report))
            ->assertOk()
            ->assertJson(['ok' => true, 'discarded' => true]);

        $this->assertDatabaseMissing('maintenance_reports', ['id' => $report->id]);
    }

    public function test_draft_yang_sudah_diisi_tidak_ikut_terbuang(): void
    {
        $user = $this->maintenance();

        $this->actingAs($user)->get(route('pemeliharaan.create'))->assertOk();
        $report = MaintenanceReport::where('created_by', $user->id)->firstOrFail();

        $this->actingAs($user)->put(route('pemeliharaan.update', $report), [
            'autosave' => 1,
            'report_date' => '2026-05-31',
        ])->assertOk();

        // Beacon "buang draft kosong" tidak boleh menghapus pekerjaan nyata.
        $this->actingAs($user)
            ->post(route('pemeliharaan.discard-blank', $report))
            ->assertOk()
            ->assertJson(['ok' => true, 'discarded' => false]);

        $this->assertDatabaseHas('maintenance_reports', ['id' => $report->id]);
    }

    public function test_draft_orang_lain_tidak_bisa_dibuang(): void
    {
        $owner = $this->maintenance();
        $penyusup = $this->maintenance();

        $this->actingAs($owner)->get(route('pemeliharaan.create'))->assertOk();
        $report = MaintenanceReport::where('created_by', $owner->id)->firstOrFail();

        $this->actingAs($penyusup)
            ->post(route('pemeliharaan.discard-blank', $report))
            ->assertForbidden();

        $this->assertDatabaseHas('maintenance_reports', ['id' => $report->id]);
    }

    public function test_draft_berisi_tidak_dipakai_ulang_oleh_form_baru(): void
    {
        $user = $this->maintenance();

        // Draft lama yang sudah dikerjakan.
        $existing = $this->submittedMaintenanceReport($user, [
            'status' => \App\Enums\MaintenanceStatus::Draft,
            'submitted_at' => null,
        ]);

        $this->actingAs($user)->get(route('pemeliharaan.create'))->assertOk();

        $reserved = MaintenanceReport::where('created_by', $user->id)
            ->where('id', '!=', $existing->id)
            ->firstOrFail();

        $this->assertNotSame($existing->id, $reserved->id);
        $this->assertSame('2026-05-21', $existing->fresh()->report_date->toDateString());
        $this->assertNull($reserved->report_date);
    }

    /**
     * Draft reservasi hanya boleh dibuang saat halaman benar-benar ditinggalkan.
     * Sempat keliru dipasang di visibilitychange juga — pindah tab sebentar
     * menghapus draft yang formnya masih terbuka.
     */
    public function test_pembuangan_draft_kosong_tidak_dipicu_saat_pindah_tab(): void
    {
        $autosave = file_get_contents(resource_path('views/partials/report-autosave.blade.php'));

        $visibilityHandler = substr($autosave, strpos($autosave, "addEventListener('visibilitychange'"));
        $visibilityHandler = substr($visibilityHandler, 0, strpos($visibilityHandler, '});'));

        $this->assertStringNotContainsString('discardBlankBeacon', $visibilityHandler);
        $this->assertStringContainsString('if (! event.persisted) discardBlankBeacon();', $autosave);
    }

    /**
     * Bila baris draft yang dituju sudah tiada, isian di layar tidak boleh ikut
     * hilang — form jatuh kembali ke endpoint store sebagai laporan baru.
     */
    public function test_menyimpan_ke_draft_yang_sudah_terhapus_masih_bisa_jadi_laporan_baru(): void
    {
        $user = $this->maintenance();

        $this->actingAs($user)->get(route('pemeliharaan.create'))->assertOk();
        $report = MaintenanceReport::where('created_by', $user->id)->firstOrFail();
        $hilang = route('pemeliharaan.update', $report);
        $report->delete();

        $this->actingAs($user)->put($hilang, ['autosave' => 1, 'report_date' => '2026-05-31'])
            ->assertNotFound();

        // Yang dilakukan report-autosave.blade.php lewat recoverToStoreEndpoint().
        $this->actingAs($user)->post(route('pemeliharaan.store'), [
            'autosave' => 1,
            'report_date' => '2026-05-31',
        ])->assertOk()->assertJson(['ok' => true]);

        $this->assertSame(1, MaintenanceReport::where('created_by', $user->id)->count());
    }

    public function test_simpan_pertama_dari_form_baru_berpesan_disimpan(): void
    {
        $user = $this->maintenance();

        $this->actingAs($user)->get(route('pemeliharaan.create'))->assertOk();
        $report = MaintenanceReport::where('created_by', $user->id)->firstOrFail();

        $this->actingAs($user)->put(route('pemeliharaan.update', $report), [
            'status' => 'draft',
            'report_date' => '2026-05-31',
        ])->assertSessionHas('success', 'Draft laporan pemeliharaan berhasil disimpan.');

        // Penyimpanan berikutnya barulah "diperbarui".
        $this->actingAs($user)->put(route('pemeliharaan.update', $report), [
            'status' => 'draft',
            'report_date' => '2026-06-01',
        ])->assertSessionHas('success', 'Draft laporan pemeliharaan berhasil diperbarui.');
    }
}
