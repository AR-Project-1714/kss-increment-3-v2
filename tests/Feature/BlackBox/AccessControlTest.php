<?php

namespace Tests\Feature\BlackBox;

/**
 * Modul B — Kontrol Akses / Hak Akses Antar Peran (PENGUJIAN_BLACKBOX.md §4.B).
 */
class AccessControlTest extends BlackBoxTestCase
{
    private const DENIED_MESSAGE = 'Anda tidak memiliki akses ke halaman tersebut.';

    public function test_tc_rbac_01_operasional_dilarang_membuka_halaman_admin(): void
    {
        $operator = $this->operator('A');

        $this->actingAs($operator)
            ->get(route('admin.index'))
            ->assertRedirect(route('report-ops.index'))
            ->assertSessionHas('error', self::DENIED_MESSAGE);
    }

    public function test_tc_rbac_02_manajer_dilarang_membuka_halaman_petugas(): void
    {
        $manager = $this->manager();

        foreach (['report-ops.index', 'pemeliharaan.index', 'safety.index'] as $route) {
            $this->actingAs($manager)
                ->get(route($route))
                ->assertRedirect(route('manajer.index'))
                ->assertSessionHas('error', self::DENIED_MESSAGE);
        }
    }

    public function test_tc_rbac_03_pemeliharaan_dan_safety_saling_terisolasi(): void
    {
        $maintenance = $this->maintenance();
        $safety = $this->safety();

        $this->actingAs($maintenance)
            ->get(route('safety.index'))
            ->assertRedirect(route('pemeliharaan.index'))
            ->assertSessionHas('error', self::DENIED_MESSAGE);

        $this->actingAs($safety)
            ->get(route('pemeliharaan.index'))
            ->assertRedirect(route('safety.index'))
            ->assertSessionHas('error', self::DENIED_MESSAGE);
    }

    public function test_tc_rbac_04_admin_dilarang_membuka_halaman_manajer_dan_petugas(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('manajer.index'))
            ->assertRedirect(route('admin.index'))
            ->assertSessionHas('error', self::DENIED_MESSAGE);

        $this->actingAs($admin)
            ->get(route('report-ops.index'))
            ->assertRedirect(route('admin.index'))
            ->assertSessionHas('error', self::DENIED_MESSAGE);
    }

    public function test_tc_rbac_04_json_request_mendapatkan_403(): void
    {
        $operator = $this->operator('A');

        $this->actingAs($operator)
            ->getJson(route('manajer.archive.suggestions', ['q' => 'x']))
            ->assertForbidden()
            ->assertJsonPath('message', self::DENIED_MESSAGE);
    }

    public function test_tc_rbac_05_tiap_peran_mengakses_dashboard_sendiri(): void
    {
        $this->actingAs($this->admin())->get(route('admin.index'))->assertOk();
        $this->actingAs($this->manager())->get(route('manajer.index'))->assertOk();
        $this->actingAs($this->operator('A'))->get(route('report-ops.index'))->assertOk();
        $this->actingAs($this->maintenance())->get(route('pemeliharaan.index'))->assertOk();
        $this->actingAs($this->safety())->get(route('safety.index'))->assertOk();
    }

    public function test_tc_rbac_06_label_jabatan_di_header_sesuai_akun(): void
    {
        $karu = $this->operator('A', false);
        $this->actingAs($karu)
            ->get(route('report-ops.index'))
            ->assertOk()
            ->assertSee('Kepala Regu A', false);

        $wakaru = $this->operator('B', true);
        $this->actingAs($wakaru)
            ->get(route('report-ops.index'))
            ->assertOk()
            ->assertSee('Wakil Kepala Regu B', false);

        $this->actingAs($this->maintenance())
            ->get(route('pemeliharaan.index'))
            ->assertOk()
            ->assertSee('Kasi Pemeliharaan', false);

        $this->actingAs($this->safety())
            ->get(route('safety.index'))
            ->assertOk()
            ->assertSee('Karu Safety', false);
    }
}
