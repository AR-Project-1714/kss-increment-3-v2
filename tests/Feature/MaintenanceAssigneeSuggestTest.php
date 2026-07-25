<?php

namespace Tests\Feature;

use App\Models\MaintenanceReport;
use Tests\Feature\BlackBox\BlackBoxTestCase;

/**
 * Field "Petugas" pada laporan pemeliharaan memakai autocomplete multi-nilai
 * yang sama dengan form laporan operasi: saran nama muncul saat mengetik, dan
 * muncul lagi setelah koma untuk nama berikutnya.
 */
class MaintenanceAssigneeSuggestTest extends BlackBoxTestCase
{
    public function test_field_petugas_memakai_autocomplete_kustom_bukan_datalist_bawaan(): void
    {
        $user = $this->maintenance();

        $html = $this->actingAs($user)->get(route('pemeliharaan.create'))->assertOk()->getContent();

        // Dropdown bawaan browser dimatikan agar tidak menimpa dropdown kustom.
        $this->assertStringNotContainsString(
            'name="main_items[0][assignee]" value="" list=',
            $html
        );

        foreach (['main_items[0][assignee]', 'priority_items[0][assignee]'] as $field) {
            $this->assertMatchesRegularExpression(
                '/name="'.preg_quote($field, '/').'"[^>]*data-suggest="maintenance-employee-datalist"/',
                $html
            );
            $this->assertMatchesRegularExpression(
                '/name="'.preg_quote($field, '/').'"[^>]*data-multi="true"/',
                $html
            );
        }

        // Skrip & sumber opsi ikut termuat.
        $this->assertStringContainsString('kss-suggest-dropdown', $html);
        $this->assertStringContainsString('<datalist id="maintenance-employee-datalist">', $html);
    }

    public function test_daftar_hadir_tetap_satu_nama_per_baris(): void
    {
        $user = $this->maintenance();

        $html = $this->actingAs($user)->get(route('pemeliharaan.create'))->assertOk()->getContent();

        // Baris Daftar Hadir mewakili satu karyawan, jadi tetap datalist biasa.
        $this->assertMatchesRegularExpression(
            '/name="attendances\[0\]\[employee_name\]"[^>]*list="maintenance-employee-datalist"/',
            $html
        );
    }

    public function test_petugas_lebih_dari_satu_tersimpan_dan_tampil_lagi_saat_edit(): void
    {
        $user = $this->maintenance();
        $petugas = 'Usman, Arman, Rahul';

        $this->actingAs($user)->post(route('pemeliharaan.store'), [
            'status' => 'draft',
            'report_date' => '2026-05-31',
            'main_items' => [
                ['work_group' => 'I', 'description' => 'Servis rutin', 'assignee' => $petugas],
            ],
        ])->assertRedirect();

        $report = MaintenanceReport::where('created_by', $user->id)->firstOrFail();
        $item = $report->workItems()->where('work_type', 'utama')->firstOrFail();

        $this->assertSame($petugas, $item->assignee);

        $html = $this->actingAs($user)->get(route('pemeliharaan.edit', $report))->assertOk()->getContent();
        $this->assertStringContainsString('value="'.$petugas.'"', $html);
    }
}
