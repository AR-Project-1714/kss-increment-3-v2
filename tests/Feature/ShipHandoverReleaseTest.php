<?php

namespace Tests\Feature;

use App\Enums\ReportStatus;
use App\Models\DailyReport;
use App\Models\ShipOperation;
use App\Models\ShipOperationDecision;
use App\Models\User;
use App\Services\ShipHandoverService;
use App\Support\ShipNameNormalizer;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Kasus KM. Dolphin Nusantara 2 (laporan #349–#357, September 2026): kapal
 * yang sudah ditandai selesai terus muncul sebagai "Dibawa dari shift
 * sebelumnya" di setiap form Regu C.
 */
class ShipHandoverReleaseTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_koreksi_nama_kapal_serah_terima_tidak_membentuk_operasi_kembar(): void
    {
        $regD = $this->operator('D');
        $regC = $this->operator('C');
        $regB = $this->operator('B');

        // #349 — Regu D mengetik nama tanpa nomor lambung.
        $this->actingAs($regD)->post(route('report-ops.store'), $this->payload('D', 'C', '2026-09-21', 'Sore', [
            'ship_name_1' => 'KLM.Dolphin Nusantara',
            'wo_number_1' => '3460443009',
            'ship_operation_status_1' => ShipOperation::STATUS_ACTIVE,
        ]))->assertRedirect(route('report-ops.index'));

        $original = ShipOperation::sole();

        // #350 — Regu C mengoreksi namanya. Form membuang ID operasi begitu
        // nama diketik ulang, jadi yang terkirim hanya nama barunya.
        $this->actingAs($regC)->post(route('report-ops.store'), $this->payload('C', 'B', '2026-09-22', 'Pagi', [
            'ship_name_1' => 'KLM.Dolphin Nusantara.2',
            'wo_number_1' => '3460443009',
            'ship_operation_status_1' => ShipOperation::STATUS_ACTIVE,
        ]))->assertRedirect(route('report-ops.index'));

        $this->assertSame(1, ShipOperation::count(), 'Koreksi nama tidak boleh membelah operasi kapal.');
        $original->refresh();
        $this->assertSame('KLM.Dolphin Nusantara.2', $original->ship_name);
        $this->assertSame(ShipOperation::STATUS_ACTIVE, $original->status);

        // #357 — regu berikutnya menandai selesai.
        $this->actingAs($regB)->post(route('report-ops.store'), $this->payload('B', 'C', '2026-09-22', 'Sore', [
            'ship_name_1' => 'KM. Dolphin Nusantara 2',
            'ship_operation_id_1' => (string) $original->id,
            'wo_number_1' => '3460443009',
            'ship_operation_status_1' => ShipOperation::STATUS_COMPLETED,
        ]))->assertRedirect(route('report-ops.index'));

        $this->assertSame(ShipOperation::STATUS_COMPLETED, $original->fresh()->status);
        $this->assertSame([], $this->carryForwardFor($regC));
    }

    public function test_beda_nomor_wo_tetap_dianggap_pekerjaan_berbeda(): void
    {
        $regD = $this->operator('D');
        $regC = $this->operator('C');

        $this->actingAs($regD)->post(route('report-ops.store'), $this->payload('D', 'C', '2026-09-21', 'Sore', [
            'ship_name_1' => 'KM Sinar Kembar',
            'wo_number_1' => '5320070001',
            'ship_operation_status_1' => ShipOperation::STATUS_ACTIVE,
        ]))->assertRedirect(route('report-ops.index'));

        $this->actingAs($regC)->post(route('report-ops.store'), $this->payload('C', 'B', '2026-09-22', 'Pagi', [
            'ship_name_1' => 'KM Sinar Kembar 8',
            'wo_number_1' => '5320079999',
            'ship_operation_status_1' => ShipOperation::STATUS_ACTIVE,
        ]))->assertRedirect(route('report-ops.index'));

        $this->assertSame(2, ShipOperation::count());
    }

    public function test_kapal_yang_tidak_dicantumkan_regu_penerima_berhenti_dibawa(): void
    {
        $regA = $this->operator('A');
        $regD = $this->operator('D');

        // #415 — Regu A lupa menandai selesai.
        $this->actingAs($regA)->post(route('report-ops.store'), $this->payload('A', 'D', '2026-10-10', 'Pagi', [
            'ship_name_1' => 'KM. Karya Nusantara 88',
            'ship_operation_status_1' => ShipOperation::STATUS_ACTIVE,
        ]))->assertRedirect(route('report-ops.index'));

        $operation = ShipOperation::sole();
        $this->assertCount(1, $this->carryForwardFor($regD));

        // #416 — Regu D menghapus baris kapal itu dari laporannya.
        $this->actingAs($regD)->post(route('report-ops.store'), $this->payload('D', 'C', '2026-10-10', 'Sore'))
            ->assertRedirect(route('report-ops.index'));

        $releasing = DailyReport::where('group_name', 'D')->sole();

        $this->assertSame(ShipOperation::STATUS_INACTIVE, $operation->fresh()->status);
        $this->assertDatabaseHas('ship_operation_decisions', [
            'ship_operation_id' => $operation->id,
            'daily_report_id' => $releasing->id,
            'status' => ShipOperation::STATUS_INACTIVE,
            'decided_by' => $regD->id,
        ]);
        $this->assertSame([], $this->carryForwardFor($regD));

        // Baris yang terhapus tidak sengaja tersambung lagi lewat namanya.
        $this->actingAs($regD)->post(route('report-ops.store'), $this->payload('D', 'C', '2026-10-11', 'Pagi', [
            'ship_name_1' => 'KM Karya Nusantara 88',
            'ship_operation_status_1' => ShipOperation::STATUS_ACTIVE,
        ]))->assertRedirect(route('report-ops.index'));

        $this->assertSame(1, ShipOperation::count());
        $this->assertSame(ShipOperation::STATUS_ACTIVE, $operation->fresh()->status);
    }

    public function test_operasi_hantu_lama_dibersihkan_saat_form_dibuka(): void
    {
        $regC = $this->operator('C');

        // Keadaan produksi sebelum perbaikan: operasi aktif yang diserahkan ke
        // Regu C, sementara Regu C sudah berkali-kali melapor tanpanya.
        Carbon::setTestNow('2026-09-22 08:00:00');
        $handover = $this->sentReport('D', 'C', '2026-09-21', 'Malam');
        $ghost = $this->activeOperation('KLM.Dolphin Nusantara', $handover);

        Carbon::setTestNow('2026-09-23 09:00:00');
        $dropping = $this->sentReport('C', 'B', '2026-09-22', 'Pagi');
        $this->sentReport('C', 'A', '2026-09-23', 'Sore');

        Carbon::setTestNow('2026-10-22 08:00:00');

        $this->assertSame([], $this->carryForwardFor($regC));
        $this->assertSame(ShipOperation::STATUS_INACTIVE, $ghost->fresh()->status);
        $this->assertDatabaseHas('ship_operation_decisions', [
            'ship_operation_id' => $ghost->id,
            'daily_report_id' => $dropping->id,
            'status' => ShipOperation::STATUS_INACTIVE,
        ]);
    }

    public function test_laporan_penerima_yang_dibuat_sebelum_serah_terima_terkirim_tidak_melepas(): void
    {
        $regC = $this->operator('C');

        // Regu C sudah mengirim laporan shift berikutnya ketika laporan Regu D
        // (shift sebelumnya) baru terkirim belakangan.
        Carbon::setTestNow('2026-09-22 09:00:00');
        $this->sentReport('C', 'B', '2026-09-22', 'Pagi');

        Carbon::setTestNow('2026-09-22 22:46:00');
        $handover = $this->sentReport('D', 'C', '2026-09-21', 'Malam');
        $operation = $this->activeOperation('KM Laporan Susulan', $handover);

        $this->assertSame(0, app(ShipHandoverService::class)->releaseAbandoned());
        $this->assertSame(ShipOperation::STATUS_ACTIVE, $operation->fresh()->status);
        $this->assertCount(1, $this->carryForwardFor($regC));
    }

    public function test_draft_kosong_yang_dipakai_ulang_tetap_bisa_melepas_kapal(): void
    {
        $regA = $this->operator('A');
        $regD = $this->operator('D');

        // Pagi hari petugas Regu D membuka form lalu meninggalkannya kosong.
        Carbon::setTestNow('2026-10-10 09:00:00');
        $abandoned = $this->actingAs($regD)->get(route('report-ops.create'))->viewData('reservedReport');

        Carbon::setTestNow('2026-10-10 15:10:00');
        $this->actingAs($regA)->post(route('report-ops.store'), $this->payload('A', 'D', '2026-10-10', 'Pagi', [
            'ship_name_1' => 'KM Draft Tertinggal',
            'ship_operation_status_1' => ShipOperation::STATUS_ACTIVE,
        ]))->assertRedirect(route('report-ops.index'));
        $operation = ShipOperation::sole();

        // Malamnya form dibuka lagi: draft kosong tadi dipakai ulang, kapal
        // serah terima terlihat, lalu barisnya dihapus.
        Carbon::setTestNow('2026-10-10 22:30:00');
        $response = $this->actingAs($regD)->get(route('report-ops.create'));
        $reserved = $response->viewData('reservedReport');
        $this->assertSame($abandoned->id, $reserved->id);
        $this->assertSame('2026-10-10 22:30:00', $reserved->fresh()->created_at->toDateTimeString());
        $this->assertCount(1, $response->viewData('carryForwardOperations'));

        Carbon::setTestNow('2026-10-10 23:10:00');
        $this->actingAs($regD)->put(route('report-ops.update', $reserved), $this->payload('D', 'C', '2026-10-10', 'Sore'))
            ->assertRedirect(route('report-ops.index'));

        $this->assertSame(ShipOperation::STATUS_INACTIVE, $operation->fresh()->status);
        $this->assertSame([], $this->carryForwardFor($regD));
    }

    public function test_mengedit_laporan_serah_terima_lama_tidak_menghidupkan_operasi_yang_dilepas(): void
    {
        $regA = $this->operator('A');
        $regD = $this->operator('D');

        $payload = $this->payload('A', 'D', '2026-10-10', 'Pagi', [
            'ship_name_1' => 'KM Edit Ulang',
            'ship_operation_status_1' => ShipOperation::STATUS_ACTIVE,
        ]);

        $this->actingAs($regA)->post(route('report-ops.store'), $payload)
            ->assertRedirect(route('report-ops.index'));
        $handover = DailyReport::where('group_name', 'A')->sole();
        $operation = ShipOperation::sole();

        $this->actingAs($regD)->post(route('report-ops.store'), $this->payload('D', 'C', '2026-10-10', 'Sore'))
            ->assertRedirect(route('report-ops.index'));
        $this->assertSame(ShipOperation::STATUS_INACTIVE, $operation->fresh()->status);

        // Regu A memperbaiki salah ketik di laporannya; kapalnya tetap
        // berstatus berjalan di laporan lama itu.
        $this->actingAs($regA)->put(route('report-ops.update', $handover), $payload + [
            'ship_operation_id_1' => (string) $operation->id,
        ])->assertRedirect(route('report-ops.index'));

        $this->assertSame(ShipOperation::STATUS_INACTIVE, $operation->fresh()->status);
        $this->assertSame([], $this->carryForwardFor($regD));

        // Regu D memperbarui laporannya sendiri: keputusan melepasnya bertahan.
        $releasing = DailyReport::where('group_name', 'D')->sole();
        $this->actingAs($regD)->put(route('report-ops.update', $releasing), $this->payload('D', 'C', '2026-10-10', 'Sore'))
            ->assertRedirect(route('report-ops.index'));

        $this->assertDatabaseHas('ship_operation_decisions', [
            'ship_operation_id' => $operation->id,
            'daily_report_id' => $releasing->id,
            'status' => ShipOperation::STATUS_INACTIVE,
        ]);
    }

    public function test_repair_menyatukan_operasi_kembar_dengan_wo_yang_sama(): void
    {
        $first = $this->sentReport('D', 'C', '2026-09-21', 'Malam');
        $second = $this->sentReport('A', 'D', '2026-09-23', 'Malam');

        $ghost = $this->activeOperation('KLM.Dolphin Nusantara', $first, '3460443009');
        $first->loadingActivities()->create([
            'sequence' => 1,
            'ship_name' => 'KLM.Dolphin Nusantara',
            'wo_number' => '3460443009',
            'ship_operation_id' => $ghost->id,
        ]);

        $voyage = ShipOperation::create([
            'type' => ShipOperation::TYPE_BAG_LOADING,
            'ship_name' => 'KM. Dolphin Nusantara 2',
            'wo_number' => '3460443009',
            'status' => ShipOperation::STATUS_COMPLETED,
            'completed_at' => '2026-09-24 06:00:00',
            'last_report_id' => $second->id,
            'last_report_date' => $second->report_date,
        ]);
        $second->loadingActivities()->create([
            'sequence' => 1,
            'ship_name' => 'KM. Dolphin Nusantara 2',
            'wo_number' => '3460443009',
            'ship_operation_id' => $voyage->id,
        ]);
        ShipOperationDecision::create([
            'ship_operation_id' => $voyage->id,
            'daily_report_id' => $second->id,
            'status' => ShipOperation::STATUS_COMPLETED,
        ]);

        $this->artisan('ops:repair-ship-identity')->assertSuccessful();

        $merged = ShipOperation::sole();
        $this->assertSame(ShipOperation::STATUS_COMPLETED, $merged->status);
        $this->assertSame('KM. Dolphin Nusantara 2', $merged->ship_name);
        $this->assertSame(2, $merged->loadingActivities()->count());
        $this->assertDatabaseHas('ship_operation_decisions', [
            'ship_operation_id' => $merged->id,
            'daily_report_id' => $second->id,
            'status' => ShipOperation::STATUS_COMPLETED,
        ]);
    }

    public function test_nomor_lambung_hanya_diabaikan_oleh_kunci_dasar(): void
    {
        $this->assertSame('DOLPHIN NUSANTARA', ShipNameNormalizer::baseKey('KM. Dolphin Nusantara 2'));
        $this->assertSame('DOLPHIN NUSANTARA', ShipNameNormalizer::baseKey('KLM.Dolphin Nusantara.2'));
        $this->assertSame('ANUGRAH BUANA', ShipNameNormalizer::baseKey('KM.ANUGRAH BUANA.III'));
        $this->assertSame('88', ShipNameNormalizer::baseKey('KM 88'));

        // Di luar konteks serah terima, nomor lambung tetap membedakan kapal.
        $this->assertFalse(ShipNameNormalizer::isSameShip('KLM.Dolphin Nusantara', 'KM. Dolphin Nusantara 2'));
    }

    /** @return array<int, array<string, mixed>> */
    private function carryForwardFor(User $user): array
    {
        return $this->actingAs($user)
            ->get(route('report-ops.create'))
            ->assertOk()
            ->viewData('carryForwardOperations');
    }

    private function sentReport(string $group, string $receiver, string $date, string $shift): DailyReport
    {
        return DailyReport::create([
            'report_date' => $date,
            'shift' => $shift,
            'group_name' => $group,
            'received_by_group' => $receiver,
            'status' => ReportStatus::Approved->value,
        ]);
    }

    private function activeOperation(string $shipName, DailyReport $handover, ?string $workOrder = null): ShipOperation
    {
        $operation = ShipOperation::create([
            'type' => ShipOperation::TYPE_BAG_LOADING,
            'ship_name' => $shipName,
            'wo_number' => $workOrder,
            'status' => ShipOperation::STATUS_ACTIVE,
            'last_report_id' => $handover->id,
            'last_report_date' => $handover->report_date,
        ]);

        ShipOperationDecision::create([
            'ship_operation_id' => $operation->id,
            'daily_report_id' => $handover->id,
            'status' => ShipOperation::STATUS_ACTIVE,
            'decided_at' => now(),
        ]);

        return $operation;
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(string $group, string $receiver, string $date, string $shift, array $overrides = []): array
    {
        return array_merge([
            'status' => ReportStatus::Submitted->value,
            'report_date' => $date,
            'shift' => $shift,
            'group_name' => $group,
            'received_by_group' => $receiver,
            'time_range' => '07.00 - 15.00',
            'confirm_duplicate' => '1',
        ], $overrides);
    }

    private function operator(string $group): User
    {
        $username = 'operator-'.strtolower($group);

        return User::create([
            'name' => 'Operator '.$group,
            'username' => $username,
            'email' => $username.'@example.com',
            'password' => 'password',
            'status' => 'aktif',
            'group' => $group,
        ]);
    }
}
