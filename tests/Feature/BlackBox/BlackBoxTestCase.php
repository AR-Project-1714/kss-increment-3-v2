<?php

namespace Tests\Feature\BlackBox;

use App\Enums\MaintenanceStatus;
use App\Enums\ReportStatus;
use App\Enums\SafetyStatus;
use App\Models\DailyReport;
use App\Models\MaintenanceReport;
use App\Models\Role;
use App\Models\SafetyReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Basis pengujian black box untuk Sistem Laporan KSS.
 *
 * Setiap kelas turunan memetakan satu modul pada PENGUJIAN_BLACKBOX.md
 * (A–N) dan setiap metode `test_tc_*` memetakan satu ID kasus uji (TC-…).
 *
 * Kelas ini menyediakan pembantu pembuatan akun untuk kelima peran agar
 * tiap kasus uji bisa fokus pada skenario, bukan boilerplate.
 */
abstract class BlackBoxTestCase extends TestCase
{
    use RefreshDatabase;

    protected function role(string $name): Role
    {
        return Role::firstOrCreate(['name' => $name]);
    }

    /**
     * Buat satu pengguna dengan peran tertentu.
     *
     * @param  array<string, mixed>  $overrides
     */
    protected function makeUser(string $roleName, array $overrides = []): User
    {
        $defaults = [
            'name' => ucfirst($roleName).' '.fake()->unique()->numberBetween(1000, 999999),
            'username' => $roleName.'-'.fake()->unique()->numberBetween(1000, 999999),
            'email' => null,
            'password' => 'password',
            'role_id' => $this->role($roleName)->id,
            'status' => 'aktif',
            'group' => null,
        ];

        $attributes = array_merge($defaults, $overrides);
        $attributes['email'] ??= $attributes['username'].'@example.com';

        return User::create($attributes);
    }

    protected function admin(array $overrides = []): User
    {
        return $this->makeUser(Role::ADMIN, $overrides);
    }

    protected function manager(array $overrides = []): User
    {
        return $this->makeUser(Role::MANAGER, $overrides);
    }

    /**
     * Akun operasional (Kepala/Wakil Kepala Regu).
     */
    protected function operator(string $group = 'A', bool $wakil = false, array $overrides = []): User
    {
        $prefix = $wakil ? 'wakaru' : 'karu';

        return $this->makeUser(Role::OPERATIONAL, array_merge([
            'username' => $prefix.'.'.strtolower($group).'-'.fake()->unique()->numberBetween(1000, 999999),
            'group' => $group,
        ], $overrides));
    }

    protected function maintenance(array $overrides = []): User
    {
        return $this->makeUser(Role::MAINTENANCE, $overrides);
    }

    protected function safety(array $overrides = []): User
    {
        return $this->makeUser(Role::SAFETY, $overrides);
    }

    // ============================================================
    // Pembantu pembuatan laporan (untuk arsip & dashboard manajer)
    // ============================================================

    /**
     * Laporan operasional yang sudah disetujui manajer (status arsip).
     *
     * @param  array<string, mixed>  $overrides
     */
    protected function approvedOpsReport(User $creator, User $manager, array $overrides = []): DailyReport
    {
        return DailyReport::create(array_merge([
            'user_id' => $creator->id,
            'created_by' => $creator->id,
            'report_date' => '2026-05-21',
            'shift' => 'Pagi',
            'group_name' => 'A',
            'received_by_group' => 'B',
            'time_range' => '07:00 - 15:00',
            'status' => ReportStatus::Approved,
            'approved_by' => $manager->id,
            'approved_at' => '2026-05-21 16:00:00',
        ], $overrides));
    }

    /**
     * Laporan operasional yang sudah ditandatangani regu penerima dan menunggu
     * persetujuan manajer (muncul di dashboard manajer).
     *
     * @param  array<string, mixed>  $overrides
     */
    protected function acknowledgedOpsReport(User $creator, array $overrides = []): DailyReport
    {
        return DailyReport::create(array_merge([
            'user_id' => $creator->id,
            'created_by' => $creator->id,
            'report_date' => '2026-05-21',
            'shift' => 'Pagi',
            'group_name' => 'A',
            'received_by_group' => 'B',
            'time_range' => '07:00 - 15:00',
            'status' => ReportStatus::Acknowledged,
            'received_at' => '2026-05-21 15:30:00',
        ], $overrides));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    protected function submittedMaintenanceReport(User $creator, array $overrides = []): MaintenanceReport
    {
        return MaintenanceReport::create(array_merge([
            'report_date' => '2026-05-21',
            'day_name' => 'Kamis',
            'status' => MaintenanceStatus::Submitted,
            'created_by' => $creator->id,
            'submitted_at' => now(),
        ], $overrides));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    protected function submittedSafetyReport(User $creator, array $overrides = []): SafetyReport
    {
        return SafetyReport::create(array_merge([
            'report_date' => '2026-05-21',
            'time_range' => '07:00 - 16:00',
            'status' => SafetyStatus::Submitted,
            'created_by' => $creator->id,
            'submitted_at' => now(),
        ], $overrides));
    }
}
