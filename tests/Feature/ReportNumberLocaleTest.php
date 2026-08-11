<?php

namespace Tests\Feature;

use App\Enums\ReportStatus;
use App\Models\ShipOperation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportNumberLocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_laporan_menyimpan_angka_format_indonesia_dengan_nilai_yang_tepat(): void
    {
        $operator = User::create([
            'name' => 'Operator Angka Lokal',
            'username' => 'operator-angka-lokal',
            'email' => 'operator-angka-lokal@example.com',
            'password' => 'password',
            'status' => 'aktif',
            'group' => 'A',
        ]);

        $this->actingAs($operator)
            ->post(route('report-ops.store'), [
                'status' => ReportStatus::Submitted->value,
                'report_date' => '2026-08-11',
                'shift' => 'Pagi',
                'group_name' => 'A',
                'received_by_group' => 'B',
                'time_range' => '07.00 - 15.00',
                'confirm_duplicate' => '1',
                'ship_name_1' => 'KM Angka Indonesia',
                'ship_operation_status_1' => ShipOperation::STATUS_ACTIVE,
                'capacity_1' => '1.234.567,50',
                'qty_loading_current_1' => '100.000.000,75',
                'qty_loading_prev_1' => '1.000.000',
                'unit_logs' => [[
                    'item_name' => 'Unit Angka Lokal',
                    'fuel_level' => '1.234,5',
                ]],
                'inventory_logs' => [[
                    'item_name' => 'Inventaris Angka Lokal',
                    'quantity' => '1.000',
                ]],
            ])
            ->assertRedirect(route('report-ops.index'));

        $this->assertDatabaseHas('loading_activities', [
            'ship_name' => 'KM Angka Indonesia',
            'capacity' => 1234567.50,
            'qty_loading_current' => 100000000.75,
            'qty_loading_prev' => 1000000.00,
        ]);
        $this->assertDatabaseHas('unit_check_logs', [
            'category' => 'vehicle',
            'item_name' => 'Unit Angka Lokal',
            'fuel_level' => '1234.5',
        ]);
        $this->assertDatabaseHas('unit_check_logs', [
            'category' => 'inventory',
            'item_name' => 'Inventaris Angka Lokal',
            'quantity' => 1000,
        ]);
    }

    public function test_angka_di_luar_kapasitas_kolom_dibatasi_tanpa_merusak_laporan(): void
    {
        $operator = User::create([
            'name' => 'Operator Angka Besar',
            'username' => 'operator-angka-besar',
            'email' => 'operator-angka-besar@example.com',
            'password' => 'password',
            'status' => 'aktif',
            'group' => 'A',
        ]);

        $this->actingAs($operator)
            ->post(route('report-ops.store'), [
                'status' => ReportStatus::Submitted->value,
                'report_date' => '2026-08-11',
                'shift' => 'Pagi',
                'group_name' => 'A',
                'received_by_group' => 'B',
                'time_range' => '07.00 - 15.00',
                'confirm_duplicate' => '1',
                'ship_name_1' => 'KM Angka Maksimal',
                'ship_operation_status_1' => ShipOperation::STATUS_ACTIVE,
                'capacity_1' => '99.999.999.999.999,99',
                'inventory_logs' => [[
                    'item_name' => 'Inventaris Maksimal',
                    'quantity' => '9.999.999.999',
                ]],
            ])
            ->assertRedirect(route('report-ops.index'));

        $this->assertDatabaseHas('loading_activities', [
            'ship_name' => 'KM Angka Maksimal',
            'capacity' => 9999999999999.99,
        ]);
        $this->assertDatabaseHas('unit_check_logs', [
            'category' => 'inventory',
            'item_name' => 'Inventaris Maksimal',
            'quantity' => 2147483647,
        ]);
    }
}
