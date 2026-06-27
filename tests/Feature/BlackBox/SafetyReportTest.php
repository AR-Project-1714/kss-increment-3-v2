<?php

namespace Tests\Feature\BlackBox;

use App\Enums\SafetyStatus;
use App\Models\MasterSafetyItem;
use App\Models\MasterSafetyLocation;
use App\Models\SafetyReport;

/**
 * Modul M — Safety (K3) / Laporan K3 (PENGUJIAN_BLACKBOX.md §4.M).
 */
class SafetyReportTest extends BlackBoxTestCase
{
    public function test_tc_sft_01_mengisi_empat_langkah_dan_serahkan(): void
    {
        $user = $this->safety();

        $this->actingAs($user)
            ->post(route('safety.store'), [
                'status' => SafetyStatus::Submitted->value,
                'report_date' => '2026-05-31',
                'work_time_start' => '07:00',
                'work_time_end' => '16:00',
                'locations' => [
                    ['location_name' => 'Shelter Operasi', 'items' => [
                        ['item_name' => 'APAR', 'qty' => 2, 'condition' => 'bagus', 'recommendation' => '-'],
                    ]],
                ],
                'operations' => [
                    ['activity_name' => 'GRESIK NIAGA', 'condition' => 'Aman'],
                ],
                'incidents' => [
                    ['description' => 'Tidak ada kejadian', 'condition' => 'Aman'],
                ],
            ])
            ->assertRedirect(route('safety.index'))
            ->assertSessionHas('success', 'Laporan K3 berhasil dikirim.');

        $report = SafetyReport::where('created_by', $user->id)->firstOrFail();
        $this->assertSame(SafetyStatus::Submitted, $report->status);
    }

    public function test_tc_sft_02_inspeksi_k3_tersimpan_per_lokasi(): void
    {
        $user = $this->safety();

        $this->actingAs($user)
            ->post(route('safety.store'), [
                'status' => SafetyStatus::Submitted->value,
                'report_date' => '2026-05-31',
                'locations' => [
                    ['location_name' => 'Work Shop', 'items' => [
                        ['item_name' => 'Kebersihan', 'condition' => 'normal', 'recommendation' => 'Bersihkan rutin'],
                        ['item_name' => 'APAR', 'qty' => 3, 'condition' => 'bagus'],
                    ]],
                ],
            ])
            ->assertRedirect(route('safety.index'));

        $report = SafetyReport::where('created_by', $user->id)->firstOrFail();
        $this->assertDatabaseHas('safety_inspections', [
            'safety_report_id' => $report->id,
            'location_name_snapshot' => 'Work Shop',
            'item_name_snapshot' => 'APAR',
            'condition' => 'bagus',
            'qty' => 3,
        ]);
    }

    public function test_tc_sft_03_tombol_set_semua_bagus_tersedia_di_form(): void
    {
        $user = $this->safety();

        $this->actingAs($user)
            ->get(route('safety.create'))
            ->assertOk()
            ->assertSee('set-all-good', false);
    }

    public function test_tc_sft_04_data_wajib_kosong_ditolak(): void
    {
        $user = $this->safety();

        $this->actingAs($user)
            ->from(route('safety.create'))
            ->post(route('safety.store'), [
                'status' => SafetyStatus::Submitted->value,
                // report_date dikosongkan.
            ])
            ->assertSessionHasErrors('report_date');
    }

    public function test_tc_sft_05_simpan_sebagai_draft(): void
    {
        $user = $this->safety();

        $this->actingAs($user)
            ->post(route('safety.store'), [
                'status' => SafetyStatus::Draft->value,
                'report_date' => '2026-05-31',
            ])
            ->assertRedirect(route('safety.index'))
            ->assertSessionHas('success', 'Draft laporan K3 berhasil disimpan.');

        $this->assertDatabaseHas('safety_reports', [
            'created_by' => $user->id,
            'status' => SafetyStatus::Draft->value,
        ]);
    }

    public function test_tc_sft_06_serahkan_langsung_muncul_di_dashboard_manajer(): void
    {
        $user = $this->safety();
        $manager = $this->manager();

        $this->actingAs($user)
            ->post(route('safety.store'), [
                'status' => SafetyStatus::Submitted->value,
                'report_date' => '2026-05-31',
            ])
            ->assertRedirect(route('safety.index'));

        $report = SafetyReport::where('created_by', $user->id)->firstOrFail();
        $this->assertSame(SafetyStatus::Submitted, $report->status);

        $k3Id = '#K3-2026-'.str_pad((string) $report->id, 3, '0', STR_PAD_LEFT);
        $this->actingAs($manager)
            ->get(route('manajer.index'))
            ->assertOk()
            ->assertSee($k3Id, false);
    }

    public function test_tc_sft_07_unduh_pdf(): void
    {
        $user = $this->safety();
        $report = $this->submittedSafetyReport($user);

        $response = $this->actingAs($user)->get(route('safety.pdf', $report));
        $response->assertOk();
        $this->assertSame('application/pdf', strtolower((string) $response->headers->get('content-type')));
    }

    public function test_tc_sft_08_form_hanya_menampilkan_item_lokasi_aktif(): void
    {
        $user = $this->safety();

        $location = MasterSafetyLocation::create([
            'name' => 'Lokasi K3 Aktif',
            'sort_order' => 0,
            'is_active' => true,
        ]);
        $activeItem = MasterSafetyItem::create(['name' => 'ItemK3Aktif', 'is_countable' => true, 'is_active' => true]);
        MasterSafetyItem::create(['name' => 'ItemK3Nonaktif', 'is_countable' => true, 'is_active' => false]);
        $location->items()->attach($activeItem->id, ['default_qty' => 1, 'sort_order' => 0]);

        $this->actingAs($user)
            ->get(route('safety.create'))
            ->assertOk()
            ->assertSee('ItemK3Aktif', false)
            ->assertDontSee('ItemK3Nonaktif', false);
    }
}
