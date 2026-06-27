<?php

namespace Tests\Feature\BlackBox;

use App\Models\MasterEmployee;
use App\Models\MasterSafetyItem;
use App\Models\MasterSafetyLocation;
use App\Models\MasterUnit;

/**
 * Modul G — Admin / Data Master (PENGUJIAN_BLACKBOX.md §4.G).
 */
class AdminDataMasterTest extends BlackBoxTestCase
{
    public function test_tc_amst_01_berpindah_antar_tab(): void
    {
        $admin = $this->admin();

        foreach (['karyawan', 'unit', 'truck', 'inventaris', 'safety_lokasi', 'safety_item'] as $pane) {
            $this->actingAs($admin)
                ->get(route('admin.datamaster', ['pane' => $pane]))
                ->assertOk();
        }
    }

    public function test_tc_amst_02_tambah_karyawan_valid(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.master.employees.store'), [
                'npk' => 'NPK-UJI-01',
                'name' => 'Karyawan Master Baru',
                'division' => 'Pemeliharaan',
                'work_time' => 'Non Shift',
            ])
            ->assertRedirect(route('admin.datamaster', ['pane' => 'karyawan']))
            ->assertSessionHas('success', 'Data karyawan berhasil ditambahkan.');

        $this->assertDatabaseHas('master_employees', [
            'npk' => 'NPK-UJI-01',
            'name' => 'Karyawan Master Baru',
        ]);
    }

    public function test_tc_amst_03_field_wajib_kosong_ditolak(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->from(route('admin.datamaster', ['pane' => 'karyawan']))
            ->post(route('admin.master.employees.store'), [
                'name' => '',
            ])
            ->assertSessionHasErrors('name');
    }

    public function test_tc_amst_04_nama_unit_terbentuk_otomatis_dari_tipe_dan_nomor(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.master.units.store'), [
                'type' => 'Minibus',
                'unit_number' => 'KSS-77',
            ])
            ->assertRedirect(route('admin.datamaster', ['pane' => 'unit']));

        $this->assertDatabaseHas('master_units', [
            'name' => 'Minibus KSS-77',
            'type' => 'Minibus',
            'unit_number' => 'KSS-77',
        ]);
    }

    public function test_tc_amst_05_edit_data_master(): void
    {
        $admin = $this->admin();
        $employee = MasterEmployee::create([
            'name' => 'Nama Lama',
            'division' => MasterEmployee::DIVISION_OPERATIONAL,
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->put(route('admin.master.employees.update', $employee), [
                'name' => 'Nama Baru',
                'division' => 'Operasional',
            ])
            ->assertRedirect(route('admin.datamaster', ['pane' => 'karyawan']));

        $this->assertSame('Nama Baru', $employee->fresh()->name);
    }

    public function test_tc_amst_06_hapus_master_tidak_merusak_laporan_lama(): void
    {
        $admin = $this->admin();
        $maintenanceUser = $this->maintenance();

        $employee = MasterEmployee::create([
            'npk' => 'NPK-LAMA-01',
            'name' => 'Mekanik Snapshot',
            'division' => MasterEmployee::DIVISION_MAINTENANCE,
            'status' => 'active',
        ]);

        $report = $this->submittedMaintenanceReport($maintenanceUser);
        $report->attendances()->create([
            'master_employee_id' => $employee->id,
            'employee_name' => 'Mekanik Snapshot',
            'position' => 'Mekanik',
            'sort_order' => 0,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.master.employees.destroy', $employee))
            ->assertRedirect(route('admin.datamaster', ['pane' => 'karyawan']));

        $this->assertDatabaseMissing('master_employees', ['id' => $employee->id]);
        // Laporan lama tetap utuh: snapshot nama karyawan tidak ikut hilang.
        $this->assertDatabaseHas('maintenance_attendances', [
            'maintenance_report_id' => $report->id,
            'employee_name' => 'Mekanik Snapshot',
        ]);
    }

    public function test_tc_amst_07_pencarian_dan_filter_master(): void
    {
        $admin = $this->admin();
        MasterEmployee::create(['name' => 'Budi Pencarian', 'division' => MasterEmployee::DIVISION_OPERATIONAL, 'status' => 'active']);
        MasterEmployee::create(['name' => 'Tidak Relevan', 'division' => MasterEmployee::DIVISION_OPERATIONAL, 'status' => 'active']);

        $this->actingAs($admin)
            ->get(route('admin.datamaster', ['pane' => 'karyawan', 'q' => 'Budi Pencarian']))
            ->assertOk()
            ->assertSee('Budi Pencarian', false)
            ->assertDontSee('Tidak Relevan', false);
    }

    public function test_tc_amst_08_status_aktif_master_k3_mempengaruhi_form_safety(): void
    {
        $safetyUser = $this->safety();

        $location = MasterSafetyLocation::create([
            'name' => 'Lokasi Uji Aktif',
            'sort_order' => 0,
            'is_active' => true,
        ]);

        $activeItem = MasterSafetyItem::create([
            'name' => 'ItemAktifUji',
            'is_countable' => true,
            'is_active' => true,
        ]);

        $inactiveItem = MasterSafetyItem::create([
            'name' => 'ItemNonaktifUji',
            'is_countable' => true,
            'is_active' => false,
        ]);

        $location->items()->attach($activeItem->id, ['default_qty' => 1, 'sort_order' => 0]);

        $this->actingAs($safetyUser)
            ->get(route('safety.create'))
            ->assertOk()
            ->assertSee('ItemAktifUji', false)
            ->assertDontSee('ItemNonaktifUji', false);
    }
}
