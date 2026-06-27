<?php

namespace Tests\Feature\BlackBox;

use App\Models\AdminActivityLog;
use App\Models\DailyReport;

/**
 * Modul D — Admin / Arsip Laporan (PENGUJIAN_BLACKBOX.md §4.D).
 */
class AdminArchiveTest extends BlackBoxTestCase
{
    private function docId(DailyReport $report): string
    {
        return '#OPS-2026-'.str_pad((string) $report->id, 3, '0', STR_PAD_LEFT);
    }

    public function test_tc_aars_01_arsip_menampilkan_laporan(): void
    {
        $admin = $this->admin();
        $manager = $this->manager();
        $operator = $this->operator('A');

        $report = $this->approvedOpsReport($operator, $manager);

        $this->actingAs($admin)
            ->get(route('admin.archive'))
            ->assertOk()
            ->assertSee($this->docId($report), false);
    }

    public function test_tc_aars_02_pencarian_kata_kunci_menyaring_dan_memberi_saran(): void
    {
        $admin = $this->admin();
        $manager = $this->manager();
        $operator = $this->operator('A');

        $report = $this->approvedOpsReport($operator, $manager, [
            'payload' => ['fields' => [['key' => 'ship_name_1', 'value' => 'KM Pencarian Unik']]],
        ]);

        // Daftar tersaring sesuai kata kunci.
        $this->actingAs($admin)
            ->get(route('admin.archive', ['q' => 'Pencarian Unik']))
            ->assertOk()
            ->assertSee($this->docId($report), false);

        // Endpoint saran pencarian (autocomplete) mengembalikan item yang cocok.
        $this->actingAs($admin)
            ->getJson(route('admin.archive.suggestions', ['q' => 'Pencarian Unik']))
            ->assertOk()
            ->assertJsonPath('keyword', 'Pencarian Unik')
            ->assertJsonStructure(['keyword', 'total', 'items']);
    }

    public function test_tc_aars_03_kata_kunci_tidak_ada_menampilkan_status_kosong(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->getJson(route('admin.archive.suggestions', ['q' => 'tidak-akan-pernah-cocok-xyz']))
            ->assertOk()
            ->assertJsonPath('total', 0)
            ->assertJsonCount(0, 'items');
    }

    public function test_tc_aars_04_filter_divisi_dan_status(): void
    {
        $admin = $this->admin();
        $manager = $this->manager();
        $operator = $this->operator('A');

        $approved = $this->approvedOpsReport($operator, $manager);
        $pending = $this->acknowledgedOpsReport($operator, ['shift' => 'Sore']);

        $this->actingAs($admin)
            ->get(route('admin.archive', ['divisi' => 'operasional', 'status' => 'approved']))
            ->assertOk()
            ->assertSee($this->docId($approved), false)
            ->assertDontSee($this->docId($pending), false);
    }

    public function test_tc_aars_05_urutan_terbaru_dan_terlama(): void
    {
        $admin = $this->admin();
        $manager = $this->manager();
        $operator = $this->operator('A');

        $older = $this->approvedOpsReport($operator, $manager, ['report_date' => '2026-04-01']);
        $newer = $this->approvedOpsReport($operator, $manager, ['report_date' => '2026-05-01']);

        $newestFirst = $this->actingAs($admin)
            ->get(route('admin.archive', ['sort' => 'newest']))
            ->assertOk()
            ->getContent();
        $this->assertLessThan(
            strpos($newestFirst, $this->docId($older)),
            strpos($newestFirst, $this->docId($newer)),
            'Urutan terbaru: laporan paling baru harus muncul lebih dulu.'
        );

        $oldestFirst = $this->actingAs($admin)
            ->get(route('admin.archive', ['sort' => 'oldest']))
            ->assertOk()
            ->getContent();
        $this->assertLessThan(
            strpos($oldestFirst, $this->docId($newer)),
            strpos($oldestFirst, $this->docId($older)),
            'Urutan terlama: laporan paling lama harus muncul lebih dulu.'
        );
    }

    public function test_tc_aars_06_reset_filter_mengembalikan_kondisi_awal(): void
    {
        $admin = $this->admin();
        $manager = $this->manager();
        $operator = $this->operator('A');
        $report = $this->approvedOpsReport($operator, $manager);

        $this->actingAs($admin)
            ->get(route('admin.archive'))
            ->assertOk()
            ->assertSee($this->docId($report), false);
    }

    public function test_tc_aars_07_tombol_lihat_membuka_pratinjau(): void
    {
        $admin = $this->admin();
        $manager = $this->manager();
        $operator = $this->operator('A');
        $report = $this->approvedOpsReport($operator, $manager);

        $this->actingAs($admin)
            ->get(route('admin.reports.show', $report))
            ->assertOk()
            ->assertSee('Laporan Operasi Harian', false);
    }

    public function test_tc_aars_08_tombol_unduh_mengembalikan_berkas_pdf(): void
    {
        $admin = $this->admin();
        $manager = $this->manager();
        $operator = $this->operator('A');
        $report = $this->approvedOpsReport($operator, $manager);

        // Siapkan berkas PDF tersimpan agar unduhan deterministik (tanpa render dompdf).
        $dir = storage_path('app/public/reports');
        @mkdir($dir, 0755, true);
        $path = $dir.'/report-'.$report->id.'.pdf';
        file_put_contents($path, '%PDF-1.4 test');

        try {
            $response = $this->actingAs($admin)->get(route('admin.reports.download', $report));
            $response->assertOk();
            $this->assertSame('application/pdf', strtolower((string) $response->headers->get('content-type')));
        } finally {
            @unlink($path);
        }
    }

    public function test_tc_aars_09_tombol_hapus_menghapus_permanen_dan_mencatat_log(): void
    {
        $admin = $this->admin();
        $manager = $this->manager();
        $operator = $this->operator('A');
        $report = $this->approvedOpsReport($operator, $manager);

        $this->actingAs($admin)
            ->delete(route('admin.reports.destroy', $report))
            ->assertRedirect()
            ->assertSessionHas('success', 'Arsip laporan berhasil dihapus.');

        $this->assertDatabaseMissing('daily_reports', ['id' => $report->id]);
        $this->assertTrue(
            AdminActivityLog::where('type', 'delete')
                ->where('description', 'like', 'Menghapus arsip laporan%')
                ->exists()
        );
    }

    public function test_tc_aars_10_pagination_arsip_berfungsi(): void
    {
        $admin = $this->admin();
        $manager = $this->manager();
        $operator = $this->operator('A');

        foreach (range(1, 12) as $i) {
            $this->approvedOpsReport($operator, $manager, [
                'report_date' => '2026-05-'.str_pad((string) $i, 2, '0', STR_PAD_LEFT),
            ]);
        }

        $this->actingAs($admin)
            ->get(route('admin.archive', ['page' => 2]))
            ->assertOk();
    }
}
