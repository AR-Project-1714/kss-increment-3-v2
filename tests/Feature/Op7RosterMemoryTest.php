<?php

namespace Tests\Feature;

use App\Enums\ReportStatus;
use App\Models\DailyReport;
use App\Models\MasterEmployee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Memori susunan karyawan hanya berlaku untuk OP.7, dan hanya menyimpan nama
 * yang memang terdaftar sebagai anggota OP.7 regu tersebut.
 */
class Op7RosterMemoryTest extends TestCase
{
    use RefreshDatabase;

    private function petugas(): User
    {
        $role = Role::firstOrCreate(['name' => Role::OPERATIONAL]);

        return User::create([
            'name' => 'Petugas Roster',
            'username' => 'petugas-roster',
            'email' => 'petugas-roster@example.com',
            'password' => 'password',
            'role_id' => $role->id,
            'status' => 'aktif',
            'group' => 'C',
        ]);
    }

    private function employee(string $name, string $group): void
    {
        MasterEmployee::create([
            'name' => $name,
            'group_name' => $group,
            'position' => 'Operator FL',
            'division' => MasterEmployee::DIVISION_OPERATIONAL,
            'work_time' => 'Shift',
            'status' => 'active',
        ]);
    }

    /** @param list<array{0: string, 1: string}> $rows nama => kategori */
    private function submittedReport(User $user, array $rows): DailyReport
    {
        $report = DailyReport::create([
            'user_id' => $user->id,
            'created_by' => $user->id,
            'report_date' => '2026-07-20',
            'shift' => 'Pagi',
            'group_name' => 'C',
            'time_range' => '07:00 - 15:00',
            'status' => ReportStatus::Submitted->value,
        ]);

        foreach ($rows as $index => [$name, $category]) {
            $report->employeeLogs()->create([
                'category' => $category,
                'name' => $name,
                'no_forklift_' => 'FL.KSS-1'.str_pad((string) $index, 2, '0', STR_PAD_LEFT),
                'work_area' => 'Blending',
            ]);
        }

        return $report;
    }

    public function test_op7_memory_keeps_only_members_of_that_op7_group(): void
    {
        $user = $this->petugas();

        $this->employee('Anggota OP7 C', 'OP.7 Group C');
        $this->employee('Anggota Shift C', 'Group C');
        $this->employee('Anggota OP7 D', 'OP.7 Group D');

        $this->submittedReport($user, [
            ['Operator P.6', 'op7'],
            ['Anggota OP7 C', 'op7'],
            ['Anggota OP7 D', 'op7'],
            ['Anggota Shift C', 'op7'],
            ['Orang Tak Dikenal', 'op7'],
        ]);

        $response = $this->actingAs($user)->get(route('report-ops.create'));

        $response->assertOk();

        $rosters = $this->rostersFrom($response->getContent());

        $this->assertSame(['C'], array_keys($rosters));
        $this->assertSame(['Anggota OP7 C'], array_column($rosters['C'], 'name'));
    }

    public function test_shift_roster_has_no_memory(): void
    {
        $user = $this->petugas();

        $this->employee('Anggota Shift C', 'Group C');

        $this->submittedReport($user, [['Anggota Shift C', 'shift']]);

        $response = $this->actingAs($user)->get(route('report-ops.create'));

        $response->assertOk();
        $this->assertSame([], $this->rostersFrom($response->getContent()));
        $response->assertDontSee('lastEmployeeRosters', false);
    }

    /** @return array<string, list<array<string, string|null>>> */
    private function rostersFrom(string $html): array
    {
        $this->assertMatchesRegularExpression('/const lastOp7Rosters = (.*);/', $html);

        preg_match('/const lastOp7Rosters = (.*);/', $html, $matches);

        return json_decode($matches[1], true);
    }
}
