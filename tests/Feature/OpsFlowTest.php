<?php

namespace Tests\Feature;

use App\Enums\ReportStatus;
use App\Enums\MaintenanceStatus;
use App\Enums\SafetyStatus;
use App\Http\Controllers\Concerns\ResolvesReportMeta;
use App\Models\AdminActivityLog;
use App\Models\DailyReport;
use App\Models\LoadingActivity;
use App\Models\MaintenanceReport;
use App\Models\MasterEmployee;
use App\Models\MasterUnit;
use App\Models\Role;
use App\Models\SafetyReport;
use App\Models\ShipOperation;
use App\Models\UnitCheckLog;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
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
        $role = Role::firstOrCreate(['name' => Role::ADMIN]);
        $admin = User::create([
            'name' => 'Admin Views',
            'username' => 'admin-views',
            'email' => 'admin-views@example.com',
            'password' => 'password',
            'role_id' => $role->id,
            'status' => 'aktif',
        ]);

        $this->get(route('admin.index'))->assertRedirect(route('login'));

        foreach ([
            'admin.index' => 'Dashboard Sistem',
            'admin.archive' => 'Riwayat Laporan',
            'admin.log' => 'Riwayat Aktivitas Sistem',
            'admin.user-manage' => 'Daftar Pengguna',
            'admin.datamaster' => 'Data Karyawan',
            'admin.backup' => 'Manajemen Backup',
            'admin.help' => 'Pusat Bantuan',
        ] as $routeName => $expectedText) {
            $this->actingAs($admin)
                ->get(route($routeName))
                ->assertOk()
                ->assertSee($expectedText, false);
        }
    }

    public function test_archive_filters_include_division_status_and_show_manager_signed_reports_as_archived(): void
    {
        $adminRole = Role::firstOrCreate(['name' => Role::ADMIN]);
        $managerRole = Role::firstOrCreate(['name' => Role::MANAGER]);
        $operatorRole = Role::firstOrCreate(['name' => Role::OPERATIONAL]);

        $admin = User::create([
            'name' => 'Admin Arsip',
            'username' => 'admin-arsip',
            'email' => 'admin-arsip@example.com',
            'password' => 'password',
            'role_id' => $adminRole->id,
            'status' => 'aktif',
        ]);

        $manager = User::create([
            'name' => 'Manajer Arsip',
            'username' => 'manajer-arsip',
            'email' => 'manajer-arsip@example.com',
            'password' => 'password',
            'role_id' => $managerRole->id,
            'status' => 'aktif',
        ]);

        $operator = User::create([
            'name' => 'Operator Arsip',
            'username' => 'operator-arsip',
            'email' => 'operator-arsip@example.com',
            'password' => 'password',
            'role_id' => $operatorRole->id,
            'status' => 'aktif',
            'group' => 'A',
        ]);

        $archivedReport = DailyReport::create([
            'user_id' => $operator->id,
            'created_by' => $operator->id,
            'report_date' => '2026-05-21',
            'shift' => 'Pagi',
            'group_name' => 'A',
            'received_by_group' => 'B',
            'time_range' => '07:00 - 15:00',
            'status' => 'approved',
            'approved_by' => $manager->id,
            'approved_at' => '2026-05-21 16:00:00',
        ]);

        $pendingReport = DailyReport::create([
            'user_id' => $operator->id,
            'created_by' => $operator->id,
            'report_date' => '2026-05-21',
            'shift' => 'Sore',
            'group_name' => 'C',
            'received_by_group' => 'D',
            'time_range' => '15:00 - 23:00',
            'status' => 'acknowledged',
        ]);

        $safetyReport = SafetyReport::create([
            'report_date' => '2026-05-21',
            'time_range' => '07:00 - 16:00',
            'status' => SafetyStatus::Approved,
            'created_by' => $operator->id,
            'approved_by' => $manager->id,
            'approved_at' => '2026-05-21 17:00:00',
        ]);

        $archivedDocumentId = '#OPS-2026-'.str_pad((string) $archivedReport->id, 3, '0', STR_PAD_LEFT);
        $pendingDocumentId = '#OPS-2026-'.str_pad((string) $pendingReport->id, 3, '0', STR_PAD_LEFT);
        $safetyDocumentId = '#K3-2026-'.str_pad((string) $safetyReport->id, 3, '0', STR_PAD_LEFT);

        $this->actingAs($admin)
            ->get(route('admin.archive', ['divisi' => 'operasional', 'status' => 'approved']))
            ->assertOk()
            ->assertSee('name="divisi"', false)
            ->assertSee('name="status"', false)
            ->assertSee('Diarsipkan', false)
            ->assertSee($archivedDocumentId, false)
            ->assertDontSee($pendingDocumentId, false);

        $this->actingAs($manager)
            ->get(route('manajer.archive', ['tanggal' => '2026-05-21', 'status' => 'approved']))
            ->assertOk()
            ->assertSee('name="divisi"', false)
            ->assertSee('name="status"', false)
            ->assertSee('Diarsipkan', false)
            ->assertSee($archivedDocumentId, false)
            ->assertDontSee($pendingDocumentId, false);

        $this->actingAs($manager)
            ->get(route('manajer.archive', ['divisi' => 'safety']))
            ->assertOk()
            ->assertSee('Safety', false)
            ->assertSee($safetyDocumentId, false)
            ->assertDontSee($archivedDocumentId, false)
            ->assertDontSee($pendingDocumentId, false);

        $this->actingAs($admin)
            ->get(route('admin.archive', ['divisi' => 'safety']))
            ->assertOk()
            ->assertSee('Safety', false)
            ->assertSee($safetyDocumentId, false)
            ->assertDontSee($archivedDocumentId, false)
            ->assertDontSee($pendingDocumentId, false);
    }

    public function test_admin_flash_error_is_rendered_as_toast_message(): void
    {
        $role = Role::firstOrCreate(['name' => Role::ADMIN]);
        $admin = User::create([
            'name' => 'Admin Toast',
            'username' => 'admin-toast',
            'email' => 'admin-toast@example.com',
            'password' => 'password',
            'role_id' => $role->id,
            'status' => 'aktif',
        ]);

        $this->actingAs($admin)
            ->withSession(['error' => 'Status pengguna gagal diperbarui.'])
            ->get(route('admin.user-manage'))
            ->assertOk()
            ->assertSee('class="toast-message error"', false)
            ->assertSee('Status pengguna gagal diperbarui.', false)
            ->assertDontSee('kss-alert', false);
    }

    public function test_admin_datamaster_search_uses_debounced_auto_submit(): void
    {
        $role = Role::firstOrCreate(['name' => Role::ADMIN]);
        $admin = User::create([
            'name' => 'Admin Master Search',
            'username' => 'admin-master-search',
            'email' => 'admin-master-search@example.com',
            'password' => 'password',
            'role_id' => $role->id,
            'status' => 'aktif',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.datamaster'))
            ->assertOk()
            ->assertSee('id="masterSearchForm"', false)
            ->assertSee('data-search-debounce="650"', false)
            ->assertSee('scheduleMasterSearchSubmit', false)
            ->assertDontSee('<i class="fi fi-rr-search"></i> Cari</button>', false);
    }

    public function test_admin_datamaster_unit_pagination_preserves_active_pane(): void
    {
        $role = Role::firstOrCreate(['name' => Role::ADMIN]);
        $admin = User::create([
            'name' => 'Admin Master Pagination',
            'username' => 'admin-master-pagination',
            'email' => 'admin-master-pagination@example.com',
            'password' => 'password',
            'role_id' => $role->id,
            'status' => 'aktif',
        ]);

        foreach (range(1, 11) as $number) {
            MasterUnit::create([
                'name' => sprintf('Unit Pagination %02d', $number),
                'type' => 'Support',
                'status' => 'active',
            ]);
        }

        $this->actingAs($admin)
            ->get(route('admin.datamaster', ['pane' => 'unit']))
            ->assertOk()
            ->assertSee('<span class="page-breadcrumb__current" id="masterCrumb">Data Unit</span>', false)
            ->assertSee('pane=unit&amp;units_page=2', false)
            ->assertDontSee('employees_page=2', false);

        $this->actingAs($admin)
            ->get(route('admin.datamaster', ['pane' => 'unit', 'units_page' => 2]))
            ->assertOk()
            ->assertSee('<div class="master-pane active" data-pane="unit">', false)
            ->assertSee('Unit Pagination 11')
            ->assertDontSee('Unit Pagination 01');
    }

    public function test_maintenance_report_uses_master_units_as_single_unit_source(): void
    {
        $role = Role::firstOrCreate(['name' => Role::MAINTENANCE]);
        $user = User::create([
            'name' => 'Petugas Pemeliharaan Master',
            'username' => 'petugas-pml-master',
            'email' => 'petugas-pml-master@example.com',
            'password' => 'password',
            'role_id' => $role->id,
            'status' => 'aktif',
        ]);

        $unit = MasterUnit::create([
            'name' => 'Forklift KSS-01',
            'type' => 'Forklift',
            'unit_code' => 'FL',
            'brand' => 'YALE',
            'unit_number' => 'KSS-01',
            'macro_category' => MasterUnit::MACRO_HEAVY,
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->post(route('pemeliharaan.store'), [
                'status' => MaintenanceStatus::Draft->value,
                'report_date' => '2026-05-31',
                'conditions' => [
                    $unit->id => [
                        'unit_label' => 'FL YALE KSS-01',
                        'condition' => 'ready',
                    ],
                ],
            ])
            ->assertRedirect(route('pemeliharaan.index'));

        $this->assertDatabaseHas('maintenance_unit_conditions', [
            'master_unit_id' => $unit->id,
            'unit_label' => 'Forklift KSS-01',
            'condition' => 'ready',
        ]);
    }

    public function test_maintenance_create_form_uses_latest_unit_condition_as_default(): void
    {
        $role = Role::firstOrCreate(['name' => Role::MAINTENANCE]);
        $user = User::create([
            'name' => 'Petugas Pemeliharaan Carry',
            'username' => 'petugas-pml-carry',
            'email' => 'petugas-pml-carry@example.com',
            'password' => 'password',
            'role_id' => $role->id,
            'status' => 'aktif',
        ]);

        $unit = MasterUnit::create([
            'name' => 'Trailer TRL-01',
            'type' => 'Trailer',
            'unit_code' => 'TRL',
            'brand' => 'UD',
            'unit_number' => 'TRL-01',
            'macro_category' => MasterUnit::MACRO_TRUCK,
            'status' => 'active',
        ]);

        $olderReport = MaintenanceReport::create([
            'report_date' => '2026-05-31',
            'day_name' => 'Minggu',
            'status' => MaintenanceStatus::Submitted->value,
            'created_by' => $user->id,
            'submitted_at' => now()->subDay(),
        ]);
        $olderReport->unitConditions()->create([
            'master_unit_id' => $unit->id,
            'unit_label' => 'TRL UD KSS-01',
            'condition' => 'ready',
        ]);

        $latestReport = MaintenanceReport::create([
            'report_date' => '2026-06-01',
            'day_name' => 'Senin',
            'status' => MaintenanceStatus::Submitted->value,
            'created_by' => $user->id,
            'submitted_at' => now(),
        ]);
        $latestReport->unitConditions()->create([
            'master_unit_id' => $unit->id,
            'unit_label' => 'TRL UD KSS-01',
            'condition' => 'rusak',
        ]);

        $response = $this->actingAs($user)->get(route('pemeliharaan.create'));

        $response->assertOk();
        $response->assertSee('value="Trailer TRL-01"', false);
        $response->assertSee('id="ct_rusak_'.$unit->id.'" value="rusak" data-cond-group="truck" checked', false);
    }

    public function test_maintenance_report_condition_pdf_uses_unit_code_only(): void
    {
        $role = Role::firstOrCreate(['name' => Role::MAINTENANCE]);
        $user = User::create([
            'name' => 'Petugas Pemeliharaan PDF',
            'username' => 'petugas-pml-pdf',
            'email' => 'petugas-pml-pdf@example.com',
            'password' => 'password',
            'role_id' => $role->id,
            'status' => 'aktif',
        ]);

        $unit = MasterUnit::create([
            'name' => 'Trailer TRL-01',
            'type' => 'Trailer',
            'unit_code' => 'TRL',
            'brand' => 'UD',
            'unit_number' => 'TRL-01',
            'macro_category' => MasterUnit::MACRO_TRUCK,
            'status' => 'active',
        ]);

        $report = MaintenanceReport::create([
            'report_date' => '2026-06-10',
            'day_name' => 'Rabu',
            'status' => MaintenanceStatus::Draft->value,
            'created_by' => $user->id,
        ]);
        $report->unitConditions()->create([
            'master_unit_id' => $unit->id,
            'unit_label' => 'Trailer TRL-01',
            'condition' => 'ready',
        ]);

        $html = view('pemeliharaan.partials.report-paper', [
            'report' => $report->load(['creator', 'approver', 'workItems.unit', 'unitConditions.unit', 'attendances']),
            'isPdf' => true,
        ])->render();

        $this->assertStringContainsString('>TRL-01<', $html);
        $this->assertStringNotContainsString('Trailer TRL-01', $html);
    }

    public function test_maintenance_report_uses_master_employees_as_single_employee_source(): void
    {
        $role = Role::firstOrCreate(['name' => Role::MAINTENANCE]);
        $user = User::create([
            'name' => 'Petugas Pemeliharaan Employee',
            'username' => 'petugas-pml-employee',
            'email' => 'petugas-pml-employee@example.com',
            'password' => 'password',
            'role_id' => $role->id,
            'status' => 'aktif',
        ]);

        $employee = MasterEmployee::create([
            'npk' => 'MNT-TST-001',
            'name' => 'Mekanik Master',
            'position' => 'Mekanik',
            'division' => MasterEmployee::DIVISION_MAINTENANCE,
            'work_time' => 'Non Shift',
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->post(route('pemeliharaan.store'), [
                'status' => MaintenanceStatus::Draft->value,
                'report_date' => '2026-05-31',
                'attendances' => [
                    [
                        'master_employee_id' => $employee->id,
                        'employee_name' => 'Mekanik Master',
                        'position' => 'Mekanik',
                        'time_in' => '07:00',
                        'time_out' => '16:00',
                    ],
                ],
            ])
            ->assertRedirect(route('pemeliharaan.index'));

        $this->assertDatabaseHas('maintenance_attendances', [
            'master_employee_id' => $employee->id,
            'employee_name' => 'Mekanik Master',
            'position' => 'Mekanik',
        ]);
    }

    public function test_maintenance_master_tables_are_merged_into_general_master_tables(): void
    {
        $this->assertFalse(Schema::hasTable('maintenance_units'));
        $this->assertFalse(Schema::hasTable('maintenance_employees'));
        $this->assertFalse(Schema::hasColumn('maintenance_work_items', 'maintenance_unit_id'));
        $this->assertFalse(Schema::hasColumn('maintenance_unit_conditions', 'maintenance_unit_id'));
        $this->assertFalse(Schema::hasColumn('maintenance_attendances', 'maintenance_employee_id'));
        $this->assertTrue(Schema::hasColumn('maintenance_work_items', 'master_unit_id'));
        $this->assertTrue(Schema::hasColumn('maintenance_unit_conditions', 'master_unit_id'));
        $this->assertTrue(Schema::hasColumn('maintenance_attendances', 'master_employee_id'));

        $employee = MasterEmployee::create([
            'name' => 'Karyawan Tanpa NPK',
            'division' => MasterEmployee::DIVISION_MAINTENANCE,
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('master_employees', [
            'id' => $employee->id,
            'npk' => null,
        ]);
    }

    public function test_master_unit_bus_and_minibus_active_numbers_follow_latest_list(): void
    {
        $this->seed(\Database\Seeders\MasterUnitSeeder::class);

        $activeBusUnits = MasterUnit::where('unit_code', 'BUS')
            ->where('status', 'active')
            ->orderBy('unit_number')
            ->get(['type', 'unit_number'])
            ->groupBy('type')
            ->map(fn ($units) => $units->pluck('unit_number')->values()->all());

        $this->assertSame(['KSS-06', 'KSS-07', 'KSS-10'], $activeBusUnits->get('Bus'));
        $this->assertSame(['KSS-02', 'KSS-03', 'KSS-04', 'KSS-09'], $activeBusUnits->get('Minibus'));

        $this->assertDatabaseMissing('master_units', [
            'unit_code' => 'BUS',
            'unit_number' => 'KSS-01',
            'status' => 'active',
        ]);
    }

    public function test_maintenance_employee_roster_uses_latest_positions_and_npks(): void
    {
        $this->seed(\Database\Seeders\MasterEmployeeSeeder::class);

        $expected = [
            'Sungkono' => ['2000.1.007', 'Kasi Pemeliharaan & Peralatan'],
            'Achmad Saiful Anwari' => ['2008.1.058', 'Karu Pemeliharaan'],
            'Usman' => ['2023.K.017', 'Mekanik'],
            'Arman' => ['2023.K.035', 'Mekanik'],
            'Muhammad Suaiban' => ['2024.K.058', 'Helper'],
            'Rahul' => ['2024.K.059', 'Helper'],
            'Usriadi' => [null, 'Helper'],
            'Fakhruddin' => [null, 'Helper'],
            'Akhmad Yani Siregar' => ['2003.1.031', 'Karu Peralatan'],
            'Rizal Paselleri' => ['2003.1.038', 'Driver'],
            'Irfan Teguh Andriyanto' => ['2023.K.019', 'Operator Exca/ WL'],
            'Amiruddin' => ['2006.1.049', 'Checker'],
        ];

        foreach ($expected as $name => [$npk, $position]) {
            $this->assertDatabaseHas('master_employees', [
                'name' => $name,
                'npk' => $npk,
                'position' => $position,
                'division' => 'pemeliharaan',
                'work_time' => 'Non Shift',
            ]);
        }
    }

    public function test_maintenance_create_form_renders_without_existing_report_rows(): void
    {
        $role = Role::firstOrCreate(['name' => Role::MAINTENANCE]);
        $user = User::create([
            'name' => 'Petugas Pemeliharaan Create',
            'username' => 'petugas-pml-create',
            'email' => 'petugas-pml-create@example.com',
            'password' => 'password',
            'role_id' => $role->id,
            'status' => 'aktif',
        ]);

        MasterUnit::create([
            'name' => 'Forklift KSS-01',
            'type' => 'Forklift',
            'unit_code' => 'FL',
            'brand' => 'YALE',
            'unit_number' => 'KSS-01',
            'macro_category' => MasterUnit::MACRO_HEAVY,
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->get(route('pemeliharaan.create'))
            ->assertOk()
            ->assertSee('Form Laporan Harian Pemeliharaan')
            ->assertSee('Forklift KSS-01');
    }

    public function test_master_unit_short_display_name_uses_abbreviated_pdf_label(): void
    {
        $forklift = MasterUnit::create([
            'name' => 'Forklift KSS-72',
            'type' => 'Forklift',
            'status' => 'active',
        ]);

        $trailer = MasterUnit::create([
            'name' => 'Trailler KSS-01',
            'type' => 'Trailler',
            'status' => 'active',
        ]);

        $wheelLoader = MasterUnit::create([
            'name' => 'WL.KSS-02',
            'type' => 'Unit',
            'status' => 'active',
        ]);

        $this->assertSame('FL KSS-72', $forklift->short_display_name);
        $this->assertSame('TRL KSS-01', $trailer->short_display_name);
        $this->assertSame('WL KSS-02', $wheelLoader->short_display_name);
    }

    public function test_admin_can_run_core_admin_actions(): void
    {
        Storage::fake('local');

        $adminRole = Role::firstOrCreate(['name' => Role::ADMIN]);
        $operationalRole = Role::firstOrCreate(['name' => Role::OPERATIONAL]);
        $admin = User::create([
            'name' => 'Admin Action',
            'username' => 'admin-action',
            'email' => 'admin-action@example.com',
            'password' => 'password',
            'role_id' => $adminRole->id,
            'status' => 'aktif',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Operator Baru',
                'username' => 'operator-baru',
                'password' => 'password',
                'role_id' => $operationalRole->id,
                'group' => 'A',
                'signature' => UploadedFile::fake()->create('signature.png', 8, 'image/png'),
            ])
            ->assertRedirect();

        $createdUser = User::where('username', 'operator-baru')->firstOrFail();
        $signaturePath = $createdUser->signature_path;

        $this->assertDatabaseHas('users', [
            'id' => $createdUser->id,
            'status' => 'aktif',
            'group' => 'A',
        ]);
        $this->assertNotNull($signaturePath);
        $this->assertStringStartsWith('signatures/signature-operator-baru-', $signaturePath);
        $this->assertFileExists(public_path($signaturePath));
        @unlink(public_path($signaturePath));

        $this->actingAs($admin)
            ->patch(route('admin.users.status', $createdUser))
            ->assertRedirect();

        $this->assertSame('nonaktif', $createdUser->fresh()->status);

        $this->actingAs($admin)
            ->post(route('admin.master.units.store'), [
                'type' => 'Minibus',
                'unit_number' => 'KSS-99',
            ])
            ->assertRedirect(route('admin.datamaster', ['pane' => 'unit']));

        // Nama unit otomatis = gabungan Tipe + Kode unit.
        $this->assertDatabaseHas('master_units', [
            'name' => 'Minibus KSS-99',
            'type' => 'Minibus',
            'unit_number' => 'KSS-99',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.backup.generate'))
            ->assertRedirect();

        $this->assertNotEmpty(Storage::disk('local')->files('admin-backups'));

        $this->assertDatabaseHas('admin_activity_logs', [
            'type' => 'backup',
        ]);
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
            ->assertSessionHas('error', 'Anda tidak memiliki akses ke halaman tersebut.');

        $this->actingAs($manager)
            ->getJson(route('report-ops.history.suggestions', ['q' => 'OPS']))
            ->assertForbidden()
            ->assertJsonPath('message', 'Anda tidak memiliki akses ke halaman tersebut.');
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
            ->assertSee('Laporan Operasi Harian', false);
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

        // Error login dirender lewat komponen toast bersama (partials.toast).
        $this->assertStringContainsString('toast-message error', $html);
        $this->assertStringContainsString('Username/email atau password salah.', $html);
        $this->assertStringContainsString('has-auth-error', $html);
        $this->assertStringNotContainsString('alert alert-danger', $html);
    }

    public function test_failed_login_and_brute_force_attempts_are_logged_for_admin(): void
    {
        $adminRole = Role::firstOrCreate(['name' => Role::ADMIN]);
        $operationalRole = Role::firstOrCreate(['name' => Role::OPERATIONAL]);

        $admin = User::create([
            'name' => 'Admin Security',
            'username' => 'admin-security',
            'email' => 'admin-security@example.com',
            'password' => 'password',
            'role_id' => $adminRole->id,
            'status' => 'aktif',
        ]);

        User::create([
            'name' => 'Target Login',
            'username' => 'target-login',
            'email' => 'target-login@example.com',
            'password' => 'password-benar',
            'role_id' => $operationalRole->id,
            'status' => 'aktif',
            'group' => 'A',
        ]);

        $ip = '10.55.0.77';

        for ($i = 0; $i < 5; $i++) {
            $this
                ->withServerVariables(['REMOTE_ADDR' => $ip])
                ->from(route('login.index'))
                ->post(route('login.authenticate'), [
                    'username' => 'target-login',
                    'password' => 'password-salah',
                ])
                ->assertRedirect(route('login.index'))
                ->assertSessionHasErrors('username');
        }

        $this
            ->withServerVariables(['REMOTE_ADDR' => $ip])
            ->from(route('login.index'))
            ->post(route('login.authenticate'), [
                'username' => 'target-login',
                'password' => 'password-salah',
            ])
            ->assertRedirect(route('login.index'))
            ->assertSessionHasErrors('username');

        $this->assertTrue(AdminActivityLog::where('type', 'security')
            ->where('description', 'like', 'Login gagal untuk username/email "target-login"%')
            ->exists());

        $this->assertTrue(AdminActivityLog::where('type', 'security')
            ->where('description', 'like', 'Brute force login diblokir untuk username/email "target-login"%')
            ->exists());

        $this->actingAs($admin)
            ->get(route('admin.log', ['type' => 'security']))
            ->assertOk()
            ->assertSee('Keamanan', false)
            ->assertSee('Login gagal untuk username/email &quot;target-login&quot;', false)
            ->assertSee('Brute force login diblokir untuk username/email &quot;target-login&quot;', false);
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
            'status' => ReportStatus::Draft->value,
        ]);
        $this->assertDatabaseHas('loading_activities', [
            'ship_name' => 'KM Draft',
            'ship_operation_id' => null,
        ]);
        $this->assertDatabaseMissing('ship_operations', [
            'ship_name' => 'KM Draft',
        ]);
    }

    public function test_autosave_request_forces_draft_and_returns_update_url(): void
    {
        $role = Role::firstOrCreate(['name' => Role::OPERATIONAL]);
        $user = User::create([
            'name' => 'Petugas Autosave',
            'username' => 'petugas-autosave',
            'email' => 'petugas-autosave@example.com',
            'password' => 'password',
            'status' => 'aktif',
            'group' => 'A',
            'role_id' => $role->id,
        ]);

        // Autosave mengirim status=submitted, tapi server WAJIB memaksanya jadi draft
        // dan menjawab JSON berisi update_url (bukan redirect) agar tidak duplikat.
        $response = $this->actingAs($user)->post(route('report-ops.store'), [
            'status' => 'submitted',
            'autosave' => 1,
            'report_date' => '2026-06-10',
            'ship_name_1' => 'KM Autosave',
        ]);

        $response->assertOk();
        $response->assertJson(['ok' => true]);

        $report = DailyReport::where('created_by', $user->id)->firstOrFail();
        $this->assertSame(ReportStatus::Draft->value, $report->status->value);
        $this->assertSame(route('report-ops.update', $report), $response->json('update_url'));
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
            'status' => ReportStatus::Submitted->value,
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
        $response->assertSee('<input type="date" id="tanggal" name="report_date" value="'.now()->toDateString().'"', false);
        $response->assertSee('name="arrival_time_1"', false);
        $response->assertSee('data-kss-picker="datetime"', false);
        $response->assertSee('name="tally_warehouse_1"', false);
        $response->assertSee('name="driver_name_1"', false);
        $response->assertSee('name="truck_number_1"', false);
        $response->assertSee('name="tally_ship_1"', false);
        $response->assertSee('name="operator_ship_1"', false);
        $response->assertSee('name="forklift_ship_1"', false);
        $response->assertSee('name="operator_warehouse_1"', false);
        $response->assertSee('name="forklift_warehouse_1"', false);
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
            'tally_warehouse_1' => 'Tally Gudang A',
            'driver_name_1' => 'Driver A',
            'truck_number_1' => 'KT 1234 XX',
            'tally_ship_1' => 'Tally Kapal A',
            'operator_ship_1' => 'Operator Kapal A',
            'forklift_ship_1' => 'FL-01',
            'operator_warehouse_1' => 'Operator Gudang A',
            'forklift_warehouse_1' => 'FL-02',
            'ship_operation_status_1' => ShipOperation::STATUS_ACTIVE,
        ])->assertRedirect(route('report-ops.index'));

        $operation = ShipOperation::where('ship_name', 'KM Berlanjut')->firstOrFail();
        $this->assertSame(ShipOperation::TYPE_BAG_LOADING, $operation->type);
        $this->assertSame(ShipOperation::STATUS_ACTIVE, $operation->status);
        $this->assertDatabaseHas('loading_activities', [
            'ship_name' => 'KM Berlanjut',
            'ship_operation_id' => $operation->id,
            'tally_warehouse' => 'Tally Gudang A',
            'driver_name' => 'Driver A',
            'truck_number' => 'KT 1234 XX',
            'tally_ship' => 'Tally Kapal A',
            'operator_ship' => 'Operator Kapal A',
            'forklift_ship' => 'FL-01',
            'operator_warehouse' => 'Operator Gudang A',
            'forklift_warehouse' => 'FL-02',
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

    public function test_container_capacity_empty_and_full_are_persisted_and_rendered(): void
    {
        $user = User::create([
            'name' => 'Operasional Container',
            'username' => 'operasional-container',
            'email' => 'operasional-container@example.com',
            'password' => 'password',
            'status' => 'aktif',
            'group' => 'A',
        ]);

        $this->actingAs($user)->post(route('report-ops.store'), [
            'status' => 'submitted',
            'report_date' => '2026-05-21',
            'shift' => 'Malam',
            'group_name' => 'A',
            'received_by_group' => 'B',
            'time_range' => '23.00 - 07.00',
            'ship_name_container_1' => 'KM Container Jaya',
            'agent_container_1' => 'Agen Container',
            'jetty_container_1' => 'Tursina',
            'capacity_container_1' => '12',
            'capacity_full_container_1' => '24',
            'tally_muat_1' => 'Tally Muat A',
            'tally_gudang_1' => 'Tally Gudang A',
            'driver_petugas_cont_1' => 'Driver A',
            'truck_petugas_cont_1' => 'TRL-01',
            'unloading_containers_1' => [
                ['time_text' => '23:00 - 04:00', 'status' => 'Empty', 'qty_current' => '1', 'qty_prev' => '2', 'qty_total' => '3'],
            ],
        ])->assertRedirect(route('report-ops.index'));

        $report = DailyReport::where('created_by', $user->id)->latest('id')->firstOrFail();
        $container = $report->containerActivity()->firstOrFail();

        $this->assertEquals(12.0, (float) $container->capacity_empty);
        $this->assertEquals(24.0, (float) $container->capacity_full);

        $loadedReport = $report->fresh()->load([
            'creator',
            'receiver',
            'approver',
            'loadingActivities.timesheets',
            'bulkLoadingActivities.logs',
            'materialActivity.items',
            'containerActivity.items',
            'turbaActivity.deliveries',
            'unitCheckLogs',
            'employeeLogs',
        ]);

        $this->assertCount(1, $loadedReport->containerActivity);

        $html = view('report-ops.partials.report-paper', [
            'report' => $loadedReport,
            'isPdf' => false,
        ])->render();

        $this->assertStringContainsString('Empty =', $html);
        $this->assertStringContainsString('Full =', $html);
        $this->assertStringContainsString('Teus', $html);
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

            // Kapal kadaluarsa diarsipkan (bukan dihapus): tetap bisa ditemukan
            // lewat pencarian berkata-kunci dengan status "Diarsipkan"...
            $this->actingAs($user)
                ->getJson(route('report-ops.ship-operations.suggestions', [
                    'type' => ShipOperation::TYPE_BAG_LOADING,
                    'q' => 'Kadaluarsa',
                ]))
                ->assertOk()
                ->assertJsonCount(1, 'items')
                ->assertJsonPath('items.0.id', $staleBagOperation->id)
                ->assertJsonPath('items.0.status', ShipOperation::STATUS_INACTIVE)
                ->assertJsonPath('items.0.status_label', 'Diarsipkan');

            $this->assertDatabaseHas('ship_operations', [
                'id' => $staleBagOperation->id,
                'status' => ShipOperation::STATUS_INACTIVE,
            ]);

            // ...tetapi tidak muncul di saran default (tanpa kata kunci).
            $this->actingAs($user)
                ->getJson(route('report-ops.ship-operations.suggestions', [
                    'type' => ShipOperation::TYPE_BAG_LOADING,
                ]))
                ->assertOk()
                ->assertJsonCount(1, 'items')
                ->assertJsonPath('items.0.id', $freshBagOperation->id);

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
                ->assertJsonCount(1, 'items')
                ->assertJsonPath('items.0.id', $staleBulkOperation->id)
                ->assertJsonPath('items.0.status', ShipOperation::STATUS_INACTIVE);

            $this->assertDatabaseHas('ship_operations', [
                'id' => $staleBulkOperation->id,
                'status' => ShipOperation::STATUS_INACTIVE,
            ]);

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
            $this->assertDatabaseHas('daily_reports', ['id' => $freshDraft->id, 'status' => ReportStatus::Draft->value]);
            $this->assertDatabaseMissing('daily_reports', ['id' => $staleDraft->id]);
            $this->assertDatabaseMissing('loading_activities', ['id' => $staleActivity->id]);

            $draftHtml = Str::between($response->getContent(), 'id="content-draft"', 'id="content-riwayat"');
            $this->assertStringContainsString('#OPS-2026-'.str_pad((string) $freshDraft->id, 3, '0', STR_PAD_LEFT), $draftHtml);
            $this->assertStringNotContainsString('KM Draft Kadaluarsa', $draftHtml);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_signed_report_moves_to_received_tab_for_receiver_not_own_history(): void
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
            'status' => ReportStatus::Acknowledged->value,
            'received_by_user_id' => $receiver->id,
        ]);

        $response = $this->actingAs($receiver)->get(route('report-ops.index'));
        $response->assertOk();

        $documentId = '#OPS-2026-'.str_pad((string) $report->id, 3, '0', STR_PAD_LEFT);
        $html = $response->getContent();
        $incomingHtml = Str::between($html, 'id="content-laporan"', 'id="content-draft"');
        $ownHistoryHtml = Str::between($html, 'id="content-riwayat"', 'id="content-diterima"');
        $receivedHtml = Str::after($html, 'id="content-diterima"');

        // Sudah ditandatangani: tidak lagi di kotak masuk yang perlu aksi.
        $this->assertStringNotContainsString($documentId, $incomingHtml);

        // Bukan buatan regu penerima (A) -> tidak tampil di Riwayat (laporan regu sendiri).
        $this->assertStringNotContainsString($documentId, $ownHistoryHtml);

        // Tampil di tab "Laporan Diterima" lengkap dengan group pengirim & isi laporan.
        $this->assertStringContainsString($documentId, $receivedHtml);
        $this->assertStringContainsString('id="received-search-input"', $receivedHtml);
        $this->assertStringContainsString('data-received-row', $receivedHtml);
        $this->assertStringContainsString('km metadata test', $receivedHtml);
        $this->assertStringContainsString('aktivitas bongkar khusus', $receivedHtml);
        $this->assertStringContainsString('Group Pengirim', $receivedHtml);
        $this->assertStringContainsString('Regu B', $receivedHtml);
        $this->assertStringContainsString('Diterima', $receivedHtml);

        $searchResponse = $this->actingAs($receiver)->get(route('report-ops.index', [
            'tab' => 'diterima',
            'received_search' => 'Metadata Test',
        ]));
        $searchResponse->assertOk();
        $this->assertStringContainsString($documentId, Str::after($searchResponse->getContent(), 'id="content-diterima"'));
    }

    public function test_petugas_history_and_received_tabs_are_scoped_by_group(): void
    {
        $sender = User::create([
            'name' => 'Karu B',
            'username' => 'karu-b',
            'email' => 'karu-b@example.com',
            'password' => 'password',
            'status' => 'aktif',
            'group' => 'B',
        ]);

        // Laporan yang DIBUAT regu B (group pengirim B, dikirim ke A).
        $ownReport = DailyReport::create([
            'user_id' => $sender->id,
            'created_by' => $sender->id,
            'report_date' => '2026-05-20',
            'shift' => 'Pagi',
            'group_name' => 'B',
            'received_by_group' => 'A',
            'time_range' => '07:00 - 15:00',
            'status' => 'acknowledged',
        ]);

        // Laporan dari regu A yang DITERIMA regu B.
        $incomingReport = DailyReport::create([
            'user_id' => $sender->id,
            'created_by' => $sender->id,
            'report_date' => '2026-05-21',
            'shift' => 'Sore',
            'group_name' => 'A',
            'received_by_group' => 'B',
            'time_range' => '15:00 - 23:00',
            'status' => 'acknowledged',
        ]);

        $ownId = '#OPS-2026-'.str_pad((string) $ownReport->id, 3, '0', STR_PAD_LEFT);
        $incomingId = '#OPS-2026-'.str_pad((string) $incomingReport->id, 3, '0', STR_PAD_LEFT);

        $html = $this->actingAs($sender)->get(route('report-ops.index'))->assertOk()->getContent();

        $ownHistoryHtml = Str::between($html, 'id="content-riwayat"', 'id="content-diterima"');
        $receivedHtml = Str::after($html, 'id="content-diterima"');

        // Riwayat regu B: hanya laporan buatan regu B.
        $this->assertStringContainsString($ownId, $ownHistoryHtml);
        $this->assertStringNotContainsString($incomingId, $ownHistoryHtml);

        // Tab Diterima regu B: hanya laporan yang masuk dari regu lain.
        $this->assertStringContainsString($incomingId, $receivedHtml);
        $this->assertStringNotContainsString($ownId, $receivedHtml);
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

    public function test_edit_page_renders_shared_report_form_with_update_method(): void
    {
        $user = User::create([
            'name' => 'Petugas Edit',
            'username' => 'petugas-edit',
            'email' => 'petugas-edit@example.com',
            'password' => 'password',
            'status' => 'aktif',
            'group' => 'A',
        ]);

        $report = DailyReport::create([
            'user_id' => $user->id,
            'created_by' => $user->id,
            'report_date' => '2026-05-21',
            'shift' => 'Pagi',
            'group_name' => 'A',
            'received_by_group' => 'B',
            'time_range' => '07:00 - 15:00',
            'status' => 'submitted',
        ]);

        $documentId = '#OPS-2026-'.str_pad((string) $report->id, 3, '0', STR_PAD_LEFT);

        // Halaman create & edit kini berbagi satu partial (report-ops.partials.report-form);
        // pastikan mode edit memakai action update + method PUT dan menampilkan ID dokumen.
        $this->actingAs($user)
            ->get(route('report-ops.edit', $report))
            ->assertOk()
            ->assertSee('Edit Laporan Operasi Harian', false)
            ->assertSee('name="_method" value="PUT"', false)
            ->assertSee($documentId, false)
            ->assertSee('name="report_date"', false);
    }

    public function test_admin_archive_search_matches_deep_report_content_and_suggestions(): void
    {
        $adminRole = Role::firstOrCreate(['name' => Role::ADMIN]);
        $operatorRole = Role::firstOrCreate(['name' => Role::OPERATIONAL]);

        $admin = User::create([
            'name' => 'Admin Cari',
            'username' => 'admin-cari',
            'email' => 'admin-cari@example.com',
            'password' => 'password',
            'role_id' => $adminRole->id,
            'status' => 'aktif',
        ]);

        $operator = User::create([
            'name' => 'Operator Cari',
            'username' => 'operator-cari',
            'email' => 'operator-cari@example.com',
            'password' => 'password',
            'role_id' => $operatorRole->id,
            'status' => 'aktif',
            'group' => 'A',
        ]);

        $report = DailyReport::create([
            'user_id' => $operator->id,
            'created_by' => $operator->id,
            'report_date' => '2026-05-21',
            'shift' => 'Pagi',
            'group_name' => 'A',
            'received_by_group' => 'B',
            'time_range' => '07:00 - 15:00',
            'status' => 'approved',
        ]);

        $report->loadingActivities()->create([
            'sequence' => 1,
            'ship_name' => 'KM Pencarian Admin',
        ]);

        $documentId = '#OPS-2026-'.str_pad((string) $report->id, 3, '0', STR_PAD_LEFT);

        // Pencarian relasi dalam pada halaman arsip (server-side), seperti manajer.
        $this->actingAs($admin)
            ->get(route('admin.archive', ['q' => 'KM Pencarian Admin']))
            ->assertOk()
            ->assertSee($documentId, false);

        // Endpoint saran mengembalikan laporan via nama kapal.
        $this->actingAs($admin)
            ->getJson(route('admin.archive.suggestions', ['q' => 'Pencarian Admin']))
            ->assertOk()
            ->assertJsonFragment(['document_id' => $documentId]);

        // Parser tanggal parsial (mei) juga berlaku di saran admin.
        $this->actingAs($admin)
            ->getJson(route('admin.archive.suggestions', ['q' => 'me']))
            ->assertOk()
            ->assertJsonFragment(['document_id' => $documentId, 'report_date' => '21 Mei 2026']);
    }

    public function test_report_export_file_name_uses_laporan_ops_format(): void
    {
        $report = DailyReport::create([
            'report_date' => '2026-05-21',
            'shift' => 'Pagi',
            'group_name' => 'A',
            'received_by_group' => 'B',
            'time_range' => '07:00 - 15:00',
            'status' => 'approved',
        ]);

        // Akses langsung helper penamaan dari trait (protected) lewat kelas anonim.
        $namer = new class
        {
            use ResolvesReportMeta;

            public function name(DailyReport $report, string $extension): string
            {
                return $this->reportFileName($report, $extension);
            }
        };

        $paddedId = str_pad((string) $report->id, 3, '0', STR_PAD_LEFT);

        $this->assertSame("Laporan_Ops_{$paddedId}_2026_A.pdf", $namer->name($report, 'pdf'));
        $this->assertSame("Laporan_Ops_{$paddedId}_2026_A.xlsx", $namer->name($report, 'xlsx'));
    }

    public function test_received_suggestions_are_scoped_to_incoming_reports(): void
    {
        $receiver = User::create([
            'name' => 'Karu B Saran',
            'username' => 'karu-b-saran',
            'email' => 'karu-b-saran@example.com',
            'password' => 'password',
            'status' => 'aktif',
            'group' => 'B',
        ]);

        // Laporan dari regu A yang ditujukan ke regu B (masuk ke tab Diterima regu B).
        $incoming = DailyReport::create([
            'user_id' => $receiver->id,
            'created_by' => $receiver->id,
            'report_date' => '2026-05-21',
            'shift' => 'Pagi',
            'group_name' => 'A',
            'received_by_group' => 'B',
            'time_range' => '07:00 - 15:00',
            'status' => 'acknowledged',
        ]);
        $incoming->loadingActivities()->create([
            'sequence' => 1,
            'ship_name' => 'KM Saran Diterima',
        ]);

        $documentId = '#OPS-2026-'.str_pad((string) $incoming->id, 3, '0', STR_PAD_LEFT);

        // Saran tab "Laporan Diterima" (scope received_by_group = B) menemukannya via nama kapal.
        $this->actingAs($receiver)
            ->getJson(route('report-ops.received.suggestions', ['q' => 'Saran Diterima']))
            ->assertOk()
            ->assertJsonFragment(['document_id' => $documentId]);

        // Saran Riwayat (laporan buatan regu B) TIDAK memuatnya, karena ini buatan regu A.
        $this->actingAs($receiver)
            ->getJson(route('report-ops.history.suggestions', ['q' => 'Saran Diterima']))
            ->assertOk()
            ->assertJsonMissing(['document_id' => $documentId]);
    }
}
