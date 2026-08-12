<?php

namespace Tests\Feature;

use App\Enums\ReportStatus;
use App\Models\DailyReport;
use App\Models\ShipOperation;
use App\Models\ShipOperationDecision;
use App\Models\User;
use App\Services\OperationalPerformanceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShipOperationContinuityTest extends TestCase
{
    use RefreshDatabase;

    public function test_laporan_terkirim_mewajibkan_keputusan_status_setiap_kapal(): void
    {
        $operator = $this->operator('A', 'operator-a');

        $this->actingAs($operator)
            ->from(route('report-ops.create'))
            ->post(route('report-ops.store'), $this->payload([
                'ship_name_1' => 'KM Wajib Status',
            ]))
            ->assertRedirect(route('report-ops.create'))
            ->assertSessionHasErrors('ship_operation_status_1');

        $this->assertDatabaseCount('daily_reports', 0);
        $this->assertDatabaseCount('ship_operations', 0);
    }

    public function test_operasi_aktif_dibawa_ke_regu_penerima_dan_keputusan_disimpan(): void
    {
        $sender = $this->operator('A', 'operator-pengirim');
        $receiver = $this->operator('B', 'operator-penerima');

        $this->actingAs($sender)
            ->post(route('report-ops.store'), $this->payload([
                'ship_name_1' => 'KM Sambung Shift',
                'ship_operation_status_1' => ShipOperation::STATUS_ACTIVE,
                'qty_loading_current_1' => '125',
            ]))
            ->assertRedirect(route('report-ops.index'));

        $report = DailyReport::firstOrFail();
        $operation = ShipOperation::firstOrFail();

        $this->assertDatabaseHas('ship_operation_decisions', [
            'ship_operation_id' => $operation->id,
            'daily_report_id' => $report->id,
            'status' => ShipOperation::STATUS_ACTIVE,
            'decided_by' => $sender->id,
        ]);

        $html = $this->actingAs($receiver)
            ->get(route('report-ops.create'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('KM Sambung Shift', $html);
        $this->assertStringContainsString('Dibawa dari shift sebelumnya', $html);
        $this->assertStringContainsString('data-operation-review-list', $html);
        $this->assertStringContainsString('Konfirmasi status operasi kapal', $html);
    }

    public function test_semua_kegiatan_muat_kantong_aktif_dibawa_ke_form_shift_berikutnya(): void
    {
        $sender = $this->operator('A', 'operator-dua-kapal-pengirim');
        $receiver = $this->operator('B', 'operator-dua-kapal-penerima');

        $this->actingAs($sender)
            ->post(route('report-ops.store'), $this->payload([
                'ship_name_1' => 'KM Handover Satu',
                'ship_operation_status_1' => ShipOperation::STATUS_ACTIVE,
                'qty_loading_current_1' => '125',
                'ship_name_2' => 'KM Handover Dua',
                'ship_operation_status_2' => ShipOperation::STATUS_ACTIVE,
                'qty_loading_current_2' => '175',
            ]))
            ->assertRedirect(route('report-ops.index'));

        $this->assertSame(2, ShipOperation::query()
            ->where('type', ShipOperation::TYPE_BAG_LOADING)
            ->where('status', ShipOperation::STATUS_ACTIVE)
            ->count());

        $response = $this->actingAs($receiver)
            ->get(route('report-ops.create'))
            ->assertOk();

        $carryForward = collect($response->viewData('carryForwardOperations'))
            ->where('type', ShipOperation::TYPE_BAG_LOADING)
            ->values();

        $this->assertCount(2, $carryForward);
        $this->assertEqualsCanonicalizing(
            ['KM Handover Satu', 'KM Handover Dua'],
            $carryForward->pluck('ship_name')->all(),
        );

        $indexHtml = $this->actingAs($receiver)
            ->get(route('report-ops.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString(
            '<strong>2 operasi kapal</strong> akan dilanjutkan ke laporan shift berikutnya.',
            $indexHtml,
        );
        $this->assertStringNotContainsString('Status operasi kapal yang Anda terima', $indexHtml);
        $this->assertStringNotContainsString('sign-operation-summary__item', $indexHtml);

        $html = $response->getContent();
        $this->assertStringContainsString('positions[item.type]', $html);
        $this->assertStringContainsString("while (section.querySelectorAll('.activity-pane').length <= position)", $html);
        $this->assertStringContainsString('showFirstActivityOnInitialLoad();', $html);
        $this->assertGreaterThan(
            strpos($html, 'hydrateSavedCarryForwardNotices();'),
            strpos($html, 'showFirstActivityOnInitialLoad();'),
        );
    }

    public function test_draft_yang_dilanjutkan_tetap_menerima_konteks_handover_kapal(): void
    {
        $sender = $this->operator('A', 'operator-draft-pengirim');
        $receiver = $this->operator('B', 'operator-draft-penerima');

        $this->actingAs($sender)->post(route('report-ops.store'), $this->payload([
            'ship_name_1' => 'KM Draft Kesinambungan',
            'ship_operation_status_1' => ShipOperation::STATUS_ACTIVE,
        ]))->assertRedirect(route('report-ops.index'));

        $sourceReport = DailyReport::where('status', ReportStatus::Submitted)->firstOrFail();
        $operation = ShipOperation::firstOrFail();
        $draft = DailyReport::create([
            'user_id' => $receiver->id,
            'created_by' => $receiver->id,
            'report_date' => '2026-05-20',
            'shift' => 'Sore',
            'group_name' => 'B',
            'received_by_group' => 'C',
            'time_range' => '15.00 - 23.00',
            'status' => ReportStatus::Draft,
            'payload' => [
                'fields' => [
                    ['key' => 'ship_name_1', 'value' => $operation->ship_name],
                    ['key' => 'ship_operation_id_1', 'value' => (string) $operation->id],
                    ['key' => 'ship_operation_status_1', 'value' => ShipOperation::STATUS_ACTIVE],
                    ['key' => 'qty_loading_current_1', 'value' => '1000.50'],
                ],
            ],
        ]);

        $html = $this->actingAs($receiver)
            ->get(route('report-ops.edit', $draft))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('KM Draft Kesinambungan', $html);
        $this->assertStringContainsString('"handover":', $html);
        $this->assertStringContainsString('#OPS-2026-'.str_pad((string) $sourceReport->id, 3, '0', STR_PAD_LEFT), $html);
        $this->assertStringContainsString('1000.50', $html);
    }

    public function test_draft_lama_tidak_dapat_membuka_kembali_operasi_yang_sudah_selesai(): void
    {
        $sender = $this->operator('A', 'operator-konflik-pengirim');
        $receiver = $this->operator('B', 'operator-konflik-penerima');

        $this->actingAs($sender)->post(route('report-ops.store'), $this->payload([
            'ship_name_1' => 'KM Konflik Draft',
            'ship_operation_status_1' => ShipOperation::STATUS_ACTIVE,
        ]))->assertRedirect(route('report-ops.index'));

        $operation = ShipOperation::firstOrFail();
        $operation->update([
            'status' => ShipOperation::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);

        $this->actingAs($receiver)
            ->from(route('report-ops.create'))
            ->post(route('report-ops.store'), $this->payload([
                'report_date' => '2026-05-20',
                'group_name' => 'B',
                'received_by_group' => 'C',
                'ship_name_1' => 'KM Konflik Draft',
                'ship_operation_id_1' => $operation->id,
                'ship_operation_status_1' => ShipOperation::STATUS_ACTIVE,
            ]))
            ->assertRedirect(route('report-ops.create'))
            ->assertSessionHasErrors('ship_operation_status_1');

        $this->assertSame(ShipOperation::STATUS_COMPLETED, $operation->fresh()->status);
    }

    public function test_keputusan_selesai_menutup_operasi_tanpa_menghapus_riwayat(): void
    {
        $sender = $this->operator('A', 'operator-status-a');
        $receiver = $this->operator('B', 'operator-status-b');

        $this->actingAs($sender)->post(route('report-ops.store'), $this->payload([
            'ship_name_1' => 'KM Riwayat Utuh',
            'ship_operation_status_1' => ShipOperation::STATUS_ACTIVE,
        ]))->assertRedirect(route('report-ops.index'));

        $operation = ShipOperation::firstOrFail();
        $firstReport = DailyReport::firstOrFail();

        $this->actingAs($receiver)->post(route('report-ops.store'), $this->payload([
            'report_date' => '2026-05-20',
            'group_name' => 'B',
            'received_by_group' => 'C',
            'ship_name_1' => 'KM Riwayat Utuh',
            'ship_operation_id_1' => $operation->id,
            'ship_operation_status_1' => ShipOperation::STATUS_COMPLETED,
        ]))->assertRedirect(route('report-ops.index'));

        $operation->refresh();

        $this->assertSame(ShipOperation::STATUS_COMPLETED, $operation->status);
        $this->assertNotNull($operation->completed_at);
        $this->assertSame(2, ShipOperationDecision::where('ship_operation_id', $operation->id)->count());
        $this->assertDatabaseHas('ship_operation_decisions', [
            'ship_operation_id' => $operation->id,
            'daily_report_id' => $firstReport->id,
            'status' => ShipOperation::STATUS_ACTIVE,
        ]);
    }

    public function test_operasi_terkonfirmasi_tetap_berjalan_meski_melewati_batas_saran_lama(): void
    {
        $operator = $this->operator('A', 'operator-jeda');
        $report = DailyReport::create([
            'report_date' => '2026-05-19',
            'shift' => 'Pagi',
            'group_name' => 'A',
            'received_by_group' => 'B',
            'status' => ReportStatus::Submitted->value,
        ]);
        $operation = ShipOperation::create([
            'type' => ShipOperation::TYPE_BAG_LOADING,
            'ship_name' => 'KM Tertunda Cuaca',
            'status' => ShipOperation::STATUS_ACTIVE,
            'created_by' => $operator->id,
            'last_report_id' => $report->id,
            'last_report_date' => $report->report_date,
        ]);

        ShipOperationDecision::create([
            'ship_operation_id' => $operation->id,
            'daily_report_id' => $report->id,
            'status' => ShipOperation::STATUS_ACTIVE,
            'decided_by' => $operator->id,
            'decided_at' => '2026-05-19 15:00:00',
        ]);
        $operation->timestamps = false;
        $operation->update(['updated_at' => '2026-05-19 15:00:00']);

        Carbon::setTestNow('2026-05-30 08:00:00');

        $this->assertSame(0, ShipOperation::pruneStaleActiveSuggestions());
        $this->assertSame(ShipOperation::STATUS_ACTIVE, $operation->fresh()->status);

        Carbon::setTestNow();
    }

    public function test_kpi_manajer_menandai_laporan_menunggu_serah_terima_sebagai_sementara(): void
    {
        $report = DailyReport::create([
            'report_date' => '2026-05-19',
            'shift' => 'Pagi',
            'group_name' => 'A',
            'received_by_group' => 'B',
            'status' => ReportStatus::Submitted->value,
        ]);

        $report->loadingActivities()->create([
            'sequence' => 1,
            'ship_name' => 'KM KPI Valid',
            'qty_loading_current' => 100,
        ]);

        $service = app(OperationalPerformanceService::class);
        $filters = [
            'start' => Carbon::parse('2026-05-01')->startOfDay(),
            'end' => Carbon::parse('2026-05-31')->startOfDay(),
            'group' => null,
            'shift' => null,
        ];

        $before = collect($service->activityRecap($filters)['rows'])->keyBy('key');
        $this->assertSame(100.0, $before['muat_kantong']['total']['value']);
        $this->assertSame(1, $service->performanceReport($filters)['provisionalReportCount']);

        $report->update(['status' => ReportStatus::Acknowledged->value]);

        $after = collect($service->activityRecap($filters)['rows'])->keyBy('key');
        $this->assertSame(100.0, $after['muat_kantong']['total']['value']);
        $this->assertSame(0, $service->performanceReport($filters)['provisionalReportCount']);
    }

    /** @param array<string, mixed> $overrides */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'status' => ReportStatus::Submitted->value,
            'report_date' => '2026-05-19',
            'shift' => 'Pagi',
            'group_name' => 'A',
            'received_by_group' => 'B',
            'time_range' => '07.00 - 15.00',
            'confirm_duplicate' => '1',
        ], $overrides);
    }

    private function operator(string $group, string $username): User
    {
        return User::create([
            'name' => 'Operator '.strtoupper($group),
            'username' => $username,
            'email' => $username.'@example.com',
            'password' => 'password',
            'status' => 'aktif',
            'group' => $group,
        ]);
    }
}
