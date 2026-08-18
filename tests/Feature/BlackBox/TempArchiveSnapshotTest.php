<?php

namespace Tests\Feature\BlackBox;

/** Sementara — memotret HTML halaman arsip untuk verifikasi UI. */
class TempArchiveSnapshotTest extends BlackBoxTestCase
{
    public function test_snapshot(): void
    {
        $admin = $this->admin();
        $manager = $this->manager();
        $operator = $this->operator('A');

        foreach (range(1, 10) as $i) {
            $this->approvedOpsReport($operator, $manager, [
                'report_date' => '2026-08-'.str_pad((string) $i, 2, '0', STR_PAD_LEFT),
                'shift' => ['Pagi', 'Sore', 'Malam'][$i % 3],
            ]);
        }

        file_put_contents(
            public_path('__arc-admin.html'),
            $this->actingAs($admin)->get(route('admin.archive'))->assertOk()->getContent()
        );

        file_put_contents(
            public_path('__arc-manajer.html'),
            $this->actingAs($manager)->get(route('manajer.archive'))->assertOk()->getContent()
        );

        $this->assertTrue(true);
    }
}
