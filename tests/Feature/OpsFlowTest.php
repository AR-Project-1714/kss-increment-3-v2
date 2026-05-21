<?php

namespace Tests\Feature;

use App\Models\DailyReport;
use App\Models\LoadingActivity;
use App\Models\MasterUnit;
use App\Models\Role;
use App\Models\ShipOperation;
use App\Models\UnitCheckLog;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class OpsFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_petugas_can_login_with_username(): void
    {
        $role = Role::firstOrCreate(['name' => Role::OPERATIONAL]);

        User::create([
            'name' => 'Petugas Test',
            'username' => 'petugas-test',
            'email' => 'petugas-test@example.com',
            'password' => 'password',
            'role_id' => $role->id,
            'status' => 'aktif',
            'group' => 'A',
        ]);

        $response = $this->post(route('login.authenticate'), [
            'username' => 'petugas-test',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('report-ops.index'));
        $this->assertAuthenticated();
    }

    public function test_admin_can_login_and_is_redirected_to_admin_index(): void
    {
        $role = Role::firstOrCreate(['name' => Role::ADMIN]);

        User::create([
            'name' => 'Admin Test',
            'username' => 'admin-test',
            'email' => 'admin-test@example.com',
            'password' => 'password',
            'role_id' => $role->id,
            'status' => 'aktif',
        ]);

        $response = $this->post(route('login.authenticate'), [
            'username' => 'admin-test',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('admin.index'));
        $this->assertAuthenticated();
    }

    public function test_admin_views_are_registered_and_render_successfully(): void
    {
        foreach ([
            'admin.index' => 'Dashboard Sistem',
            'admin.archive' => 'Riwayat Laporan',
            'admin.log' => 'Riwayat Aktivitas Sistem',
            'admin.user-manage' => 'Daftar Pengguna',
            'admin.datamaster' => 'Data Karyawan',
            'admin.backup' => 'Manajemen Backup',
            'admin.help' => 'Pusat Bantuan',
        ] as $routeName => $expectedText) {
            $this->get(route($routeName))
                ->assertOk()
                ->assertSee($expectedText, false);
        }
    }

    public function test_manager_is_redirected_away_from_operational_pages(): void
    {
        $role = Role::firstOrCreate(['name' => Role::MANAGER]);
        $manager = User::create([
            'name' => 'Manajer Test',
            'username' => 'manajer-test',
            'email' => 'manajer-test@example.com',
            'password' => 'password',
            'role_id' => $role->id,
            'status' => 'aktif',
        ]);

        $this->actingAs($manager)
            ->get(route('report-ops.index'))
            ->assertRedirect(route('manajer.index'))
            ->assertSessionHas('error', 'Akun manajer hanya dapat mengakses halaman manajer.');

        $this->actingAs($manager)
            ->getJson(route('report-ops.history.suggestions', ['q' => 'OPS']))
            ->assertForbidden()
            ->assertJsonPath('message', 'Akun manajer hanya dapat mengakses halaman manajer.');
    }

    public function test_manager_can_review_reports_from_manager_route_only(): void
    {
        $managerRole = Role::firstOrCreate(['name' => Role::MANAGER]);
        $manager = User::create([
            'name' => 'Manajer Review',
            'username' => 'manajer-review',
            'email' => 'manajer-review@example.com',
            'password' => 'password',
            'role_id' => $managerRole->id,
            'status' => 'aktif',
        ]);

        $report = DailyReport::create([
            'report_date' => '2026-05-20',
            'shift' => 'Pagi',
            'group_name' => 'A',
            'received_by_group' => 'B',
            'time_range' => '07:00 - 15:00',
            'status' => 'acknowledged',
        ]);

        $this->actingAs($manager)
            ->get(route('report-ops.show', $report))
            ->assertRedirect(route('manajer.index'));

        $this->actingAs($manager)
            ->get(route('manajer.reports.show', $report))
            ->assertOk()
            ->assertSee('Laporan Harian Shift', false);
    }

    public function test_login_errors_are_rendered_as_attention_toast(): void
    {
        $response = $this->followingRedirects()
            ->from(route('login.index'))
            ->post(route('login.authenticate'), [
                'username' => 'akun-tidak-ada',
                'password' => 'password-salah',
            ])
            ->assertOk();

        $html = $response->getContent();

        $this->assertStringContainsString('auth-toast error', $html);
        $this->assertStringContainsString('Login belum berhasil', $html);
        $this->assertStringContainsString('has-auth-error', $html);
        $this->assertStringNotContainsString('alert alert-danger', $html);
    }

    public function test_role_seeder_prepares_five_development_roles_and_migrates_legacy_petugas(): void
    {
        $legacyRole = Role::create(['name' => 'petugas']);
        $user = User::create([
            'name' => 'Legacy Petugas',
            'username' => 'legacy-petugas',
            'email' => 'legacy-petugas@example.com',
            'password' => 'password',
            'role_id' => $legacyRole->id,
            'status' => 'aktif',
            'group' => 'A',
        ]);

        $this->seed(RoleSeeder::class);

        foreach (Role::NAMES as $roleName) {
            $this->assertDatabaseHas('roles', ['name' => $roleName]);
        }

        $this->assertDatabaseMissing('roles', ['name' => 'petugas']);
        $this->assertSame(
            Role::where('name', Role::OPERATIONAL)->value('id'),
            $user->fresh()->role_id
        );
    }

    public function test_petugas_can_save_report_as_draft(): void
    {
        $user = User::create([
            'name' => 'Petugas Draft',
            'username' => 'petugas-draft',
            'email' => 'petugas-draft@example.com',
            'password' => 'password',
            'status' => 'aktif',
            'group' => 'A',
        ]);

        $response = $this->actingAs($user)->post(route('report-ops.store'), [
            'status' => 'draft',
            'form_payload' => json_encode([
                'fields' => [
                    ['key' => 'ship_name_1', 'value' => 'KM Draft'],
                ],
            ]),
            'ship_name_1' => 'KM Draft',
        ]);

        $response->assertRedirect(route('report-ops.index'));
        $this->assertDatabaseHas('daily_reports', [
            'created_by' => $user->id,
            'status' => 'draft',
        ]);
        $this->assertDatabaseHas('loading_activities', [
            'ship_name' => 'KM Draft',
        ]);
    }

    public function test_submitted_report_rejects_same_sender_and_receiver_group(): void
    {
        $user = User::create([
            'name' => 'Petugas Same Group',
            'username' => 'petugas-same-group',
            'email' => 'petugas-same-group@example.com',
            'password' => 'password',
            'status' => 'aktif',
            'group' => 'B',
        ]);

        $response = $this->actingAs($user)
            ->from(route('report-ops.create'))
            ->post(route('report-ops.store'), [
                'status' => 'submitted',
                'report_date' => '2026-05-19',
                'shift' => 'Pagi',
                'group_name' => 'B',
                'received_by_group' => 'B',
                'time_range' => '07.00 - 15.00',
            ]);

        $response->assertRedirect(route('report-ops.create'));
        $response->assertSessionHasErrors(['received_by_group']);
        $this->assertDatabaseMissing('daily_reports', [
            'created_by' => $user->id,
            'group_name' => 'B',
            'received_by_group' => 'B',
            'status' => 'submitted',
        ]);
    }

    public function test_negative_numeric_values_are_clamped_when_saving_report(): void
    {
        $user = User::create([
            'name' => 'Petugas Angka',
            'username' => 'petugas-angka',
            'email' => 'petugas-angka@example.com',
            'password' => 'password',
            'status' => 'aktif',
            'group' => 'A',
        ]);

        $this->actingAs($user)->post(route('report-ops.store'), [
            'status' => 'submitted',
            'report_date' => '2026-05-19',
            'shift' => 'Pagi',
            'group_name' => 'A',
            'received_by_group' => 'B',
            'time_range' => '07.00 - 15.00',
            'ship_name_1' => 'KM Non Negatif',
            'capacity_1' => '-100',
            'qty_delivery_current_1' => '-11',
            'qty_loading_current_1' => '-12',
            'qty_damage_current_1' => '-1',
            'unit_logs' => [
                [
                    'item_name' => 'Forklift Negatif',
                    'fuel_level' => '-5',
                    'condition_received' => 'Baik',
                    'condition_handed_over' => 'Baik',
                ],
            ],
        ])->assertRedirect(route('report-ops.index'));

        $activity = LoadingActivity::where('ship_name', 'KM Non Negatif')->firstOrFail();
        $this->assertSame(0.0, (float) $activity->capacity);
        $this->assertSame(0.0, (float) $activity->qty_delivery_current);
        $this->assertSame(0.0, (float) $activity->qty_loading_current);
        $this->assertSame(0.0, (float) $activity->qty_damage_current);

        $unitLog = UnitCheckLog::where('item_name', 'Forklift Negatif')->firstOrFail();
        $this->assertSame('0', $unitLog->fuel_level);
    }

    public function test_create_form_uses_previous_handed_over_condition_as_check_unit_defaults(): void
    {
        $unit = MasterUnit::create([
            'name' => 'Forklift Test',
            'type' => 'forklift',
            'status' => 'active',
        ]);

        $user = User::create([
            'name' => 'Petugas Unit',
            'username' => 'petugas-unit',
            'email' => 'petugas-unit@example.com',
            'password' => 'password',
            'status' => 'aktif',
            'group' => 'A',
        ]);

        $report = DailyReport::create([
            'user_id' => $user->id,
            'created_by' => $user->id,
            'report_date' => '2026-05-18',
            'shift' => 'Malam',
            'group_name' => 'A',
            'received_by_group' => 'B',
            'time_range' => '23.00 - 07.00',
            'status' => 'submitted',
        ]);

        $report->unitCheckLogs()->create([
            'category' => 'vehicle',
            'item_name' => $unit->name,
            'master_id' => (string) $unit->id,
            'condition_received' => 'Baik',
            'condition_handed_over' => 'Rusak',
        ]);

        $response = $this->actingAs($user)->get(route('report-ops.create'));

        $response->assertOk();
        $this->assertStringContainsString('"vehicle":{"master":{"'.$unit->id.'":"Rusak"', $response->getContent());
        $this->assertStringContainsString(
            'makeRadioCell(`unit_logs[${index}][condition_handed_over]`, `unit_serah_${index}`, previousHandoverCondition(\'vehicle\', item))',
            $response->getContent()
        );
    }

    public function test_active_ship_operation_can_be_reused_and_completed(): void
    {
        $user = User::create([
            'name' => 'Operasional Kapal',
            'username' => 'operasional-kapal',
            'email' => 'operasional-kapal@example.com',
            'password' => 'password',
            'status' => 'aktif',
            'group' => 'A',
        ]);

        $this->actingAs($user)->post(route('report-ops.store'), [
            'status' => 'submitted',
            'report_date' => '2026-05-19',
            'shift' => 'Pagi',
            'group_name' => 'A',
            'received_by_group' => 'B',
            'time_range' => '07.00 - 15.00',
            'ship_name_1' => 'KM Berlanjut',
            'agent_1' => 'Agen Aktif',
            'jetty_1' => 'Dermaga 1',
            'destination_1' => 'Tursina',
            'capacity_1' => '1000',
            'wo_number_1' => 'WO-001',
            'qty_delivery_current_1' => '120',
            'qty_loading_current_1' => '90',
            'qty_damage_current_1' => '2',
            'ship_operation_status_1' => ShipOperation::STATUS_ACTIVE,
        ])->assertRedirect(route('report-ops.index'));

        $operation = ShipOperation::where('ship_name', 'KM Berlanjut')->firstOrFail();
        $this->assertSame(ShipOperation::TYPE_BAG_LOADING, $operation->type);
        $this->assertSame(ShipOperation::STATUS_ACTIVE, $operation->status);
        $this->assertDatabaseHas('loading_activities', [
            'ship_name' => 'KM Berlanjut',
            'ship_operation_id' => $operation->id,
        ]);

        $this->actingAs($user)
            ->getJson(route('report-ops.ship-operations.suggestions', [
                'type' => ShipOperation::TYPE_BAG_LOADING,
                'q' => 'Berlanjut',
            ]))
            ->assertOk()
            ->assertJsonPath('items.0.id', $operation->id)
            ->assertJsonPath('items.0.accumulation.qty_delivery_prev', 120)
            ->assertJsonPath('items.0.accumulation.qty_loading_prev', 90)
            ->assertJsonPath('items.0.accumulation.qty_damage_prev', 2);

        $this->actingAs($user)->post(route('report-ops.store'), [
            'status' => 'submitted',
            'report_date' => '2026-05-20',
            'shift' => 'Sore',
            'group_name' => 'B',
            'received_by_group' => 'A',
            'time_range' => '15.00 - 23.00',
            'ship_operation_id_1' => $operation->id,
            'ship_operation_status_1' => ShipOperation::STATUS_COMPLETED,
            'ship_name_1' => 'KM Berlanjut',
            'agent_1' => 'Agen Aktif',
            'jetty_1' => 'Dermaga 1',
            'destination_1' => 'Tursina',
            'capacity_1' => '1000',
            'wo_number_1' => 'WO-001',
            'qty_delivery_current_1' => '30',
            'qty_delivery_prev_1' => '120',
            'qty_loading_current_1' => '40',
            'qty_loading_prev_1' => '90',
        ])->assertRedirect(route('report-ops.index'));

        $this->assertSame(ShipOperation::STATUS_COMPLETED, $operation->fresh()->status);

        $this->actingAs($user)
            ->getJson(route('report-ops.ship-operations.suggestions', [
                'type' => ShipOperation::TYPE_BAG_LOADING,
                'q' => 'Berlanjut',
            ]))
            ->assertOk()
            ->assertJsonCount(0, 'items');
    }

    public function test_stale_ship_operation_suggestions_are_pruned_after_three_days_for_bag_and_bulk_loading(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-19 08:00:00'));

        try {
            $user = User::create([
                'name' => 'Petugas Suggestion',
                'username' => 'petugas-suggestion',
                'email' => 'petugas-suggestion@example.com',
                'password' => 'password',
                'status' => 'aktif',
                'group' => 'A',
            ]);

            $freshBagOperation = ShipOperation::create([
                'type' => ShipOperation::TYPE_BAG_LOADING,
                'status' => ShipOperation::STATUS_ACTIVE,
                'ship_name' => 'KM Masih Aktif',
                'created_at' => now()->subDays(3),
                'updated_at' => now()->subDays(3),
            ]);

            $staleBagOperation = ShipOperation::create([
                'type' => ShipOperation::TYPE_BAG_LOADING,
                'status' => ShipOperation::STATUS_ACTIVE,
                'ship_name' => 'KM Kantong Kadaluarsa',
                'created_at' => now()->subDays(3)->subSecond(),
                'updated_at' => now()->subDays(3)->subSecond(),
            ]);

            $this->actingAs($user)
                ->getJson(route('report-ops.ship-operations.suggestions', [
                    'type' => ShipOperation::TYPE_BAG_LOADING,
                    'q' => 'Kadaluarsa',
                ]))
                ->assertOk()
                ->assertJsonCount(0, 'items');

            $this->assertDatabaseMissing('ship_operations', ['id' => $staleBagOperation->id]);

            $this->actingAs($user)
                ->getJson(route('report-ops.ship-operations.suggestions', [
                    'type' => ShipOperation::TYPE_BAG_LOADING,
                    'q' => 'Masih Aktif',
                ]))
                ->assertOk()
                ->assertJsonPath('items.0.id', $freshBagOperation->id);

            $freshBulkOperation = ShipOperation::create([
                'type' => ShipOperation::TYPE_BULK_LOADING,
                'status' => ShipOperation::STATUS_ACTIVE,
                'ship_name' => 'KM Curah Aktif',
                'created_at' => now()->subDays(3),
                'updated_at' => now()->subDays(3),
            ]);

            $staleBulkOperation = ShipOperation::create([
                'type' => ShipOperation::TYPE_BULK_LOADING,
                'status' => ShipOperation::STATUS_ACTIVE,
                'ship_name' => 'KM Curah Kadaluarsa',
                'created_at' => now()->subDays(3)->subSecond(),
                'updated_at' => now()->subDays(3)->subSecond(),
            ]);

            $this->actingAs($user)
                ->getJson(route('report-ops.ship-operations.suggestions', [
                    'type' => ShipOperation::TYPE_BULK_LOADING,
                    'q' => 'Curah Kadaluarsa',
                ]))
                ->assertOk()
                ->assertJsonCount(0, 'items');

            $this->assertDatabaseMissing('ship_operations', ['id' => $staleBulkOperation->id]);

            $this->actingAs($user)
                ->getJson(route('report-ops.ship-operations.suggestions', [
                    'type' => ShipOperation::TYPE_BULK_LOADING,
                    'q' => 'Curah Aktif',
                ]))
                ->assertOk()
                ->assertJsonPath('items.0.id', $freshBulkOperation->id);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_stale_draft_reports_are_pruned_after_three_days_without_continuation(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-20 08:00:00'));

        try {
            $user = User::create([
                'name' => 'Petugas Draft Expiry',
                'username' => 'petugas-draft-expiry',
                'email' => 'petugas-draft-expiry@example.com',
                'password' => 'password',
                'status' => 'aktif',
                'group' => 'A',
            ]);

            $freshDraft = DailyReport::create([
                'user_id' => $user->id,
                'created_by' => $user->id,
                'report_date' => '2026-05-17',
                'shift' => 'Pagi',
                'group_name' => 'A',
                'received_by_group' => 'B',
                'time_range' => '07.00 - 15.00',
                'status' => 'draft',
                'created_at' => now()->subDays(DailyReport::DRAFT_TTL_DAYS),
                'updated_at' => now()->subDays(DailyReport::DRAFT_TTL_DAYS),
            ]);

            $staleDraft = DailyReport::create([
                'user_id' => $user->id,
                'created_by' => $user->id,
                'report_date' => '2026-05-16',
                'shift' => 'Pagi',
                'group_name' => 'A',
                'received_by_group' => 'B',
                'time_range' => '07.00 - 15.00',
                'status' => 'draft',
                'payload' => ['fields' => [['key' => 'ship_name_1', 'value' => 'KM Draft Kadaluarsa']]],
                'created_at' => now()->subDays(DailyReport::DRAFT_TTL_DAYS)->subSecond(),
                'updated_at' => now()->subDays(DailyReport::DRAFT_TTL_DAYS)->subSecond(),
            ]);

            $staleActivity = $staleDraft->loadingActivities()->create([
                'sequence' => 1,
                'ship_name' => 'KM Draft Kadaluarsa',
            ]);

            $response = $this->actingAs($user)->get(route('report-ops.index', ['tab' => 'draft']));

            $response->assertOk();
            $this->assertDatabaseHas('daily_reports', ['id' => $freshDraft->id, 'status' => 'draft']);
            $this->assertDatabaseMissing('daily_reports', ['id' => $staleDraft->id]);
            $this->assertDatabaseMissing('loading_activities', ['id' => $staleActivity->id]);

            $draftHtml = Str::between($response->getContent(), 'id="content-draft"', 'id="content-riwayat"');
            $this->assertStringContainsString('#OPS-2026-'.str_pad((string) $freshDraft->id, 3, '0', STR_PAD_LEFT), $draftHtml);
            $this->assertStringNotContainsString('KM Draft Kadaluarsa', $draftHtml);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_signed_report_leaves_incoming_reports_but_stays_in_history(): void
    {
        $sender = User::create([
            'name' => 'Petugas Pengirim',
            'username' => 'petugas-pengirim',
            'email' => 'petugas-pengirim@example.com',
            'password' => 'password',
            'status' => 'aktif',
            'group' => 'B',
        ]);

        $receiver = User::create([
            'name' => 'Petugas Penerima',
            'username' => 'petugas-penerima',
            'email' => 'petugas-penerima@example.com',
            'password' => 'password',
            'status' => 'aktif',
            'group' => 'A',
        ]);

        $report = DailyReport::create([
            'user_id' => $sender->id,
            'created_by' => $sender->id,
            'report_date' => '2026-05-18',
            'shift' => 'Pagi',
            'group_name' => 'B',
            'received_by_group' => 'A',
            'time_range' => '07:00 - 15:00',
            'status' => 'submitted',
            'payload' => [
                'fields' => [
                    ['key' => 'ship_name_1', 'value' => 'KM Metadata Test'],
                    ['key' => 'timesheets[1][delivery][0][activity]', 'value' => 'Aktivitas Bongkar Khusus'],
                ],
            ],
        ]);

        $this->actingAs($receiver)
            ->post(route('report-ops.sign', $report))
            ->assertRedirect();

        $this->assertDatabaseHas('daily_reports', [
            'id' => $report->id,
            'status' => 'acknowledged',
            'received_by_user_id' => $receiver->id,
        ]);

        $response = $this->actingAs($receiver)->get(route('report-ops.index'));
        $response->assertOk();

        $documentId = '#OPS-2026-'.str_pad((string) $report->id, 3, '0', STR_PAD_LEFT);
        $html = $response->getContent();
        $incomingHtml = Str::between($html, 'id="content-laporan"', 'id="content-draft"');
        $historyHtml = Str::after($html, 'id="content-riwayat"');

        $this->assertStringNotContainsString($documentId, $incomingHtml);
        $this->assertStringContainsString($documentId, $historyHtml);
        $this->assertStringContainsString('id="history-search-input"', $historyHtml);
        $this->assertStringContainsString('data-history-row', $historyHtml);
        $this->assertStringContainsString('km metadata test', $historyHtml);
        $this->assertStringContainsString('aktivitas bongkar khusus', $historyHtml);
        $this->assertStringContainsString('Group Penerima', $historyHtml);
        $this->assertStringContainsString('Regu A', $historyHtml);
        $this->assertStringContainsString('Ditanda Tangani', $historyHtml);

        $searchResponse = $this->actingAs($receiver)->get(route('report-ops.index', [
            'tab' => 'riwayat',
            'history_search' => 'Metadata Test',
        ]));
        $searchResponse->assertOk();
        $this->assertStringContainsString($documentId, Str::after($searchResponse->getContent(), 'id="content-riwayat"'));
    }

    public function test_history_reports_are_paginated_per_ten(): void
    {
        $user = User::create([
            'name' => 'Petugas Riwayat',
            'username' => 'petugas-riwayat',
            'email' => 'petugas-riwayat@example.com',
            'password' => 'password',
            'status' => 'aktif',
            'group' => 'A',
        ]);

        for ($i = 0; $i < 12; $i++) {
            DailyReport::create([
                'user_id' => $user->id,
                'created_by' => $user->id,
                'report_date' => now()->subDays($i)->toDateString(),
                'shift' => 'Pagi',
                'group_name' => 'A',
                'received_by_group' => 'B',
                'time_range' => '07:00 - 15:00',
                'status' => 'acknowledged',
            ]);
        }

        $pageOne = $this->actingAs($user)->get(route('report-ops.index', ['tab' => 'riwayat']));
        $pageOne->assertOk();
        $this->assertSame(10, substr_count($pageOne->getContent(), 'data-history-search="'));
        $this->assertStringContainsString('history-pagination', $pageOne->getContent());

        $pageTwo = $this->actingAs($user)->get(route('report-ops.index', ['tab' => 'riwayat', 'history_page' => 2]));
        $pageTwo->assertOk();
        $this->assertSame(2, substr_count($pageTwo->getContent(), 'data-history-search="'));
    }

    public function test_history_search_finds_date_from_later_pagination_page(): void
    {
        $user = User::create([
            'name' => 'Petugas Cari Tanggal',
            'username' => 'petugas-cari-tanggal',
            'email' => 'petugas-cari-tanggal@example.com',
            'password' => 'password',
            'status' => 'aktif',
            'group' => 'A',
        ]);

        $targetReport = null;

        for ($i = 0; $i < 26; $i++) {
            $reportDate = Carbon::create(2026, 5, 19)->subDays($i);

            $report = DailyReport::create([
                'user_id' => $user->id,
                'created_by' => $user->id,
                'report_date' => $reportDate->toDateString(),
                'shift' => 'Pagi',
                'group_name' => 'A',
                'received_by_group' => 'B',
                'time_range' => '07:00 - 15:00',
                'status' => 'acknowledged',
            ]);

            if ($reportDate->toDateString() === '2026-04-24') {
                $targetReport = $report;
            }
        }

        $this->assertNotNull($targetReport);

        $pageOne = $this->actingAs($user)->get(route('report-ops.index', ['tab' => 'riwayat']));
        $pageOne->assertOk();
        $this->assertStringNotContainsString('24 April 2026', Str::after($pageOne->getContent(), 'id="content-riwayat"'));

        $pageThree = $this->actingAs($user)->get(route('report-ops.index', ['tab' => 'riwayat', 'history_page' => 3]));
        $pageThree->assertOk();
        $this->assertStringContainsString('24 April 2026', Str::after($pageThree->getContent(), 'id="content-riwayat"'));

        $searchResponse = $this->actingAs($user)->get(route('report-ops.index', [
            'tab' => 'riwayat',
            'history_search' => '24 april',
        ]));

        $searchResponse->assertOk();
        $searchHtml = Str::after($searchResponse->getContent(), 'id="content-riwayat"');
        $documentId = '#OPS-2026-'.str_pad((string) $targetReport->id, 3, '0', STR_PAD_LEFT);

        $this->assertSame(1, substr_count($searchHtml, 'data-history-search="'));
        $this->assertStringContainsString($documentId, $searchHtml);
        $this->assertStringContainsString('24 April 2026', $searchHtml);
        $this->assertStringContainsString('1 hasil', $searchHtml);

        $this->actingAs($user)
            ->getJson(route('report-ops.history.suggestions', ['q' => '24 april']))
            ->assertOk()
            ->assertJsonFragment([
                'document_id' => $documentId,
                'report_date' => '24 April 2026',
            ]);

        $this->actingAs($user)
            ->getJson(route('report-ops.history.suggestions', ['q' => '24 apri']))
            ->assertOk()
            ->assertJsonFragment([
                'document_id' => $documentId,
                'report_date' => '24 April 2026',
            ]);

        $this->actingAs($user)
            ->get(route('report-ops.index', [
                'tab' => 'riwayat',
                'history_search' => 'apri',
            ]))
            ->assertOk()
            ->assertSee('24 April 2026');
    }

    public function test_report_search_suggestions_accept_clear_partial_month_names(): void
    {
        $managerRole = Role::firstOrCreate(['name' => Role::MANAGER]);
        $user = User::create([
            'name' => 'Petugas Bulan Parsial',
            'username' => 'petugas-bulan-parsial',
            'email' => 'petugas-bulan-parsial@example.com',
            'password' => 'password',
            'status' => 'aktif',
            'group' => 'A',
        ]);
        $manager = User::create([
            'name' => 'Manajer Bulan Parsial',
            'username' => 'manajer-bulan-parsial',
            'email' => 'manajer-bulan-parsial@example.com',
            'password' => 'password',
            'role_id' => $managerRole->id,
            'status' => 'aktif',
        ]);

        $reports = collect([
            'janu' => '2026-01-12',
            'apri' => '2026-04-24',
            'me' => '2026-05-21',
            'jul' => '2026-07-07',
        ])->map(function (string $date) use ($user): DailyReport {
            return DailyReport::create([
                'user_id' => $user->id,
                'created_by' => $user->id,
                'report_date' => $date,
                'shift' => 'Pagi',
                'group_name' => 'A',
                'received_by_group' => 'B',
                'time_range' => '07:00 - 15:00',
                'status' => 'acknowledged',
            ]);
        });

        $expectedDates = [
            'janu' => '12 Januari 2026',
            'apri' => '24 April 2026',
            'me' => '21 Mei 2026',
            'jul' => '07 Juli 2026',
        ];

        foreach ($expectedDates as $keyword => $dateLabel) {
            $report = $reports->get($keyword);
            $documentId = '#OPS-2026-'.str_pad((string) $report->id, 3, '0', STR_PAD_LEFT);

            $this->actingAs($user)
                ->getJson(route('report-ops.history.suggestions', ['q' => $keyword]))
                ->assertOk()
                ->assertJsonFragment([
                    'document_id' => $documentId,
                    'report_date' => $dateLabel,
                ]);

            $this->actingAs($manager)
                ->getJson(route('manajer.archive.suggestions', ['q' => $keyword]))
                ->assertOk()
                ->assertJsonFragment([
                    'document_id' => $documentId,
                    'report_date' => $dateLabel,
                ]);
        }
    }
}
