<?php

namespace Tests\Feature\BlackBox;

use App\Enums\ReportStatus;

/**
 * Modul N — Fungsi Umum & Antarmuka (PENGUJIAN_BLACKBOX.md §4.N).
 *
 * Sebagian besar kasus pada modul ini bersifat antarmuka/JS. Pada lapisan
 * HTTP (black box) yang dapat diverifikasi adalah keberadaan markup/komponen
 * yang menjadi dasar perilaku tersebut.
 */
class CommonUiTest extends BlackBoxTestCase
{
    public function test_tc_ui_01_notifikasi_toast_seragam_di_semua_halaman(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->withSession(['success' => 'Aksi berhasil dijalankan.'])
            ->get(route('admin.index'))
            ->assertOk()
            ->assertSee('toast-message', false)
            ->assertSee('Aksi berhasil dijalankan.', false);
    }

    public function test_tc_ui_02_toast_memiliki_mekanisme_auto_hide(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->withSession(['error' => 'Terjadi kesalahan.'])
            ->get(route('admin.index'))
            ->assertOk()
            // Durasi auto-dismiss + tombol tutup manual.
            ->assertSee('data-duration', false)
            ->assertSee('toast-close', false);
    }

    public function test_tc_ui_03_mode_gelap_tersedia(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('admin.index'))
            ->assertOk()
            ->assertSee('btn-theme', false)
            ->assertSee('dark-mode', false);
    }

    public function test_tc_ui_04_layout_responsif_memiliki_sidebar_geser(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('admin.index'))
            ->assertOk()
            ->assertSee('btn-sidebar-toggle', false);
    }

    public function test_tc_ui_05_pusat_bantuan_memiliki_pencarian(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('admin.help'))
            ->assertOk()
            ->assertSee('help-search', false);
    }

    public function test_tc_ui_06_pusat_bantuan_memiliki_navigasi_tab(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('admin.help'))
            ->assertOk()
            ->assertSee('help-tab', false);
    }

    public function test_tc_ui_07_viewer_pdf_menampilkan_pratinjau_dan_unduhan(): void
    {
        $manager = $this->manager();
        $operator = $this->operator('A');
        $report = $this->approvedOpsReport($operator, $manager);

        $this->actingAs($manager)
            ->get(route('manajer.reports.show', $report))
            ->assertOk()
            ->assertSee('Unduh PDF', false)
            ->assertSee(route('manajer.reports.download', $report), false);
    }

    public function test_tc_ui_08_pdf_dibuat_dan_disimpan_saat_disetujui(): void
    {
        $manager = $this->manager();
        $operator = $this->operator('A');
        $report = $this->acknowledgedOpsReport($operator);

        $path = storage_path('app/public/reports/report-'.$report->id.'.pdf');
        @unlink($path);

        try {
            // Approve memicu pembuatan & penyimpanan PDF (cache) untuk akses berikutnya.
            $this->actingAs($manager)
                ->post(route('manajer.reports.approve', $report))
                ->assertRedirect(route('manajer.archive'));

            $this->assertSame(ReportStatus::Approved, $report->fresh()->status);
            $this->assertFileExists($path);
        } finally {
            @unlink($path);
        }
    }
}
