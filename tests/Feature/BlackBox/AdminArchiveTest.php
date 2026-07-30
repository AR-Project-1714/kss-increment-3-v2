<?php

namespace Tests\Feature\BlackBox;

use App\Enums\ReportStatus;
use App\Jobs\BuildArchiveBundle;
use App\Models\AdminActivityLog;
use App\Models\ArchiveBundle;
use App\Models\DailyReport;
use App\Services\ArchiveBundleService;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

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

    /**
     * Siapkan cache PDF laporan agar bundel ZIP deterministik (tanpa render dompdf).
     */
    private function stubReportPdf(DailyReport $report): string
    {
        $dir = storage_path('app/public/reports');
        @mkdir($dir, 0755, true);
        $path = $dir.'/report-'.$report->id.'.pdf';
        file_put_contents($path, '%PDF-1.4 test');

        return $path;
    }

    public function test_unduh_massal_membundel_laporan_terpilih_jadi_zip(): void
    {
        $admin = $this->admin();
        $manager = $this->manager();
        $operator = $this->operator('A');

        $first = $this->approvedOpsReport($operator, $manager, ['report_date' => '2026-05-02']);
        $second = $this->approvedOpsReport($operator, $manager, ['report_date' => '2026-05-03']);
        $excluded = $this->approvedOpsReport($operator, $manager, ['report_date' => '2026-05-04']);

        $paths = [
            $this->stubReportPdf($first),
            $this->stubReportPdf($second),
            $this->stubReportPdf($excluded),
        ];

        try {
            $response = $this->actingAs($admin)->post(route('admin.archive.bulk-download'), [
                'keys' => ['ops-'.$first->id, 'ops-'.$second->id],
            ]);

            $response->assertOk();
            $this->assertSame('application/zip', strtolower((string) $response->headers->get('content-type')));

            $zipPath = tempnam(sys_get_temp_dir(), 'kss-test-');
            file_put_contents($zipPath, $response->streamedContent());

            $zip = new \ZipArchive;
            $this->assertTrue($zip->open($zipPath) === true);

            $names = [];
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $names[] = $zip->getNameIndex($i);
            }
            $zip->close();
            @unlink($zipPath);

            $this->assertCount(2, $names, 'ZIP hanya memuat laporan yang dicentang.');
            $this->assertContains('Laporan_Ops_'.str_pad((string) $first->id, 3, '0', STR_PAD_LEFT).'_2026_A.pdf', $names);
            $this->assertNotContains('Laporan_Ops_'.str_pad((string) $excluded->id, 3, '0', STR_PAD_LEFT).'_2026_A.pdf', $names);

            $this->assertTrue(
                AdminActivityLog::where('type', 'export')
                    ->where('description', 'like', 'Mengunduh massal 2 laporan arsip%')
                    ->exists()
            );
        } finally {
            foreach ($paths as $path) {
                @unlink($path);
            }
        }
    }

    public function test_unduh_massal_mode_pilih_semua_mengikuti_filter_aktif(): void
    {
        $admin = $this->admin();
        $manager = $this->manager();
        $operator = $this->operator('A');

        $pagi = $this->approvedOpsReport($operator, $manager, ['shift' => 'Pagi']);
        $sore = $this->approvedOpsReport($operator, $manager, ['shift' => 'Sore']);

        $paths = [$this->stubReportPdf($pagi), $this->stubReportPdf($sore)];

        try {
            $response = $this->actingAs($admin)->post(route('admin.archive.bulk-download'), [
                'all' => '1',
                'shift' => 'pagi',
            ]);

            $response->assertOk();

            $zipPath = tempnam(sys_get_temp_dir(), 'kss-test-');
            file_put_contents($zipPath, $response->streamedContent());

            $zip = new \ZipArchive;
            $this->assertTrue($zip->open($zipPath) === true);
            $count = $zip->numFiles;
            $firstName = $zip->getNameIndex(0);
            $zip->close();
            @unlink($zipPath);

            $this->assertSame(1, $count, 'Mode pilih semua hanya membundel laporan yang lolos filter shift.');
            $this->assertSame('Laporan_Ops_'.str_pad((string) $pagi->id, 3, '0', STR_PAD_LEFT).'_2026_A.pdf', $firstName);
        } finally {
            foreach ($paths as $path) {
                @unlink($path);
            }
        }
    }

    public function test_unduh_massal_tanpa_pilihan_ditolak(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->postJson(route('admin.archive.bulk-download'), ['keys' => []])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Belum ada laporan yang dipilih untuk diunduh.');
    }

    public function test_unduh_massal_menolak_laporan_di_luar_arsip(): void
    {
        $admin = $this->admin();
        $operator = $this->operator('A');

        // Draft belum masuk arsip, jadi kuncinya tidak boleh bisa dibundel.
        $draft = DailyReport::create([
            'user_id' => $operator->id,
            'created_by' => $operator->id,
            'report_date' => '2026-05-21',
            'shift' => 'Pagi',
            'group_name' => 'A',
            'received_by_group' => 'B',
            'time_range' => '07:00 - 15:00',
            'status' => ReportStatus::Draft,
        ]);

        $this->actingAs($admin)
            ->postJson(route('admin.archive.bulk-download'), ['keys' => ['ops-'.$draft->id]])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Laporan yang dipilih tidak ditemukan lagi di arsip.');
    }

    // ============================================================
    // Bundel ZIP latar (permintaan di atas batas unduhan instan)
    // ============================================================

    public function test_unduh_instan_menolak_pilihan_di_atas_batas_dan_mengarahkan_ke_latar(): void
    {
        $admin = $this->admin();
        $manager = $this->manager();
        $operator = $this->operator('A');

        $keys = [];
        foreach (range(1, ArchiveBundleService::INSTANT_LIMIT + 1) as $index) {
            $keys[] = 'ops-'.$this->approvedOpsReport($operator, $manager)->id;
        }

        $this->actingAs($admin)
            ->postJson(route('admin.archive.bulk-download'), ['keys' => $keys])
            ->assertStatus(422)
            ->assertJsonPath('needs_background', true)
            ->assertJsonPath('total', ArchiveBundleService::INSTANT_LIMIT + 1);
    }

    public function test_bundel_latar_dijadwalkan_dan_membekukan_daftar_laporan(): void
    {
        Queue::fake();

        $admin = $this->admin();
        $manager = $this->manager();
        $operator = $this->operator('A');

        $pagi = $this->approvedOpsReport($operator, $manager, ['shift' => 'Pagi']);
        $this->approvedOpsReport($operator, $manager, ['shift' => 'Sore']);

        // Mode "pilih semua" harus mengikuti filter aktif, bukan seluruh arsip.
        $response = $this->actingAs($admin)->postJson(route('admin.archive.bundles.store'), [
            'all' => '1',
            'shift' => 'pagi',
        ]);

        $response->assertStatus(202)
            ->assertJsonPath('bundle.status', ArchiveBundle::STATUS_QUEUED)
            ->assertJsonPath('bundle.total', 1)
            ->assertJsonPath('bundle.percent', 0)
            ->assertJsonPath('bundle.download_url', null);

        $bundle = ArchiveBundle::firstOrFail();
        $this->assertSame($admin->id, $bundle->user_id);
        $this->assertSame([['kind' => 'operasional', 'id' => $pagi->id]], $bundle->refs);

        Queue::assertPushed(BuildArchiveBundle::class, fn (BuildArchiveBundle $job): bool => $job->bundleId === $bundle->id);
    }

    public function test_bundel_latar_kedua_ditolak_selagi_yang_pertama_berjalan(): void
    {
        Queue::fake();

        $admin = $this->admin();
        $manager = $this->manager();
        $operator = $this->operator('A');
        $this->approvedOpsReport($operator, $manager);

        $this->actingAs($admin)
            ->postJson(route('admin.archive.bundles.store'), ['all' => '1'])
            ->assertStatus(202);

        $this->actingAs($admin)
            ->postJson(route('admin.archive.bundles.store'), ['all' => '1'])
            ->assertStatus(409)
            ->assertJsonPath('bundle.status', ArchiveBundle::STATUS_QUEUED);

        $this->assertSame(1, ArchiveBundle::count());
    }

    public function test_job_latar_merakit_bundel_lalu_bisa_diunduh(): void
    {
        $admin = $this->admin();
        $manager = $this->manager();
        $operator = $this->operator('A');

        $first = $this->approvedOpsReport($operator, $manager, ['report_date' => '2026-05-02']);
        $second = $this->approvedOpsReport($operator, $manager, ['report_date' => '2026-05-03']);
        $paths = [$this->stubReportPdf($first), $this->stubReportPdf($second)];

        try {
            $bundle = ArchiveBundle::create([
                'token' => (string) Str::uuid(),
                'user_id' => $admin->id,
                'context' => 'admin',
                'status' => ArchiveBundle::STATUS_QUEUED,
                'total_reports' => 2,
                'processed_reports' => 0,
                'skipped_reports' => 0,
                'refs' => [
                    ['kind' => 'operasional', 'id' => $first->id],
                    ['kind' => 'operasional', 'id' => $second->id],
                ],
                'expires_at' => now()->addHours(24),
            ]);

            (new BuildArchiveBundle($bundle->id))->handle(app(ArchiveBundleService::class));

            $bundle->refresh();
            $this->assertSame(ArchiveBundle::STATUS_READY, $bundle->status);
            $this->assertSame(2, $bundle->processed_reports);
            $this->assertSame(0, $bundle->skipped_reports);
            $this->assertSame(100, $bundle->progressPercent());
            $this->assertTrue(is_file($bundle->absolutePath()));

            $response = $this->actingAs($admin)->get(route('admin.archive.bundles.download', $bundle->token));
            $response->assertOk();
            $this->assertSame('application/zip', strtolower((string) $response->headers->get('content-type')));

            $zipPath = tempnam(sys_get_temp_dir(), 'kss-test-');
            file_put_contents($zipPath, $response->streamedContent());

            $zip = new \ZipArchive;
            $this->assertTrue($zip->open($zipPath) === true);
            $this->assertSame(2, $zip->numFiles);
            $zip->close();
            @unlink($zipPath);

            // Berkas tetap ada setelah diunduh supaya bisa diambil ulang dalam 24 jam.
            $this->assertTrue(is_file($bundle->absolutePath()));

            $bundle->purge();
        } finally {
            foreach ($paths as $path) {
                @unlink($path);
            }
        }
    }

    public function test_bundel_belum_selesai_tidak_bisa_diunduh(): void
    {
        Queue::fake();

        $admin = $this->admin();
        $manager = $this->manager();
        $operator = $this->operator('A');
        $this->approvedOpsReport($operator, $manager);

        $token = $this->actingAs($admin)
            ->postJson(route('admin.archive.bundles.store'), ['all' => '1'])
            ->json('bundle.token');

        $this->actingAs($admin)
            ->get(route('admin.archive.bundles.download', $token))
            ->assertStatus(409);
    }

    public function test_bundel_milik_pengguna_lain_tidak_bisa_diakses(): void
    {
        Queue::fake();

        $admin = $this->admin();
        $otherAdmin = $this->admin();
        $manager = $this->manager();
        $operator = $this->operator('A');
        $this->approvedOpsReport($operator, $manager);

        $token = $this->actingAs($admin)
            ->postJson(route('admin.archive.bundles.store'), ['all' => '1'])
            ->json('bundle.token');

        $this->actingAs($otherAdmin)
            ->getJson(route('admin.archive.bundles.show', $token))
            ->assertStatus(403);

        $this->actingAs($otherAdmin)
            ->deleteJson(route('admin.archive.bundles.destroy', $token))
            ->assertStatus(403);
    }

    public function test_bundel_bisa_dibatalkan_pemiliknya(): void
    {
        Queue::fake();

        $admin = $this->admin();
        $manager = $this->manager();
        $operator = $this->operator('A');
        $this->approvedOpsReport($operator, $manager);

        $token = $this->actingAs($admin)
            ->postJson(route('admin.archive.bundles.store'), ['all' => '1'])
            ->json('bundle.token');

        $this->actingAs($admin)
            ->deleteJson(route('admin.archive.bundles.destroy', $token))
            ->assertOk();

        $this->assertSame(0, ArchiveBundle::count());
    }

    public function test_prune_menghapus_bundel_kedaluwarsa_dan_menandai_yang_macet(): void
    {
        $admin = $this->admin();

        $expired = ArchiveBundle::create([
            'token' => (string) Str::uuid(),
            'user_id' => $admin->id,
            'context' => 'admin',
            'status' => ArchiveBundle::STATUS_READY,
            'total_reports' => 1,
            'refs' => [['kind' => 'operasional', 'id' => 1]],
            'expires_at' => now()->subMinute(),
        ]);

        $stuck = ArchiveBundle::create([
            'token' => (string) Str::uuid(),
            'user_id' => $admin->id,
            'context' => 'admin',
            'status' => ArchiveBundle::STATUS_PROCESSING,
            'total_reports' => 1,
            'refs' => [['kind' => 'operasional', 'id' => 1]],
            'created_at' => now()->subHours(5),
            'expires_at' => now()->addHours(19),
        ]);

        $this->artisan('archive:prune-bundles')->assertSuccessful();

        $this->assertDatabaseMissing('archive_bundles', ['id' => $expired->id]);
        $this->assertSame(ArchiveBundle::STATUS_FAILED, $stuck->refresh()->status);
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
