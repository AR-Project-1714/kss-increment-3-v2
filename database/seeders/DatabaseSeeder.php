<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            MasterUnitSeeder::class,
            MasterInventorySeeder::class,
            MasterTruckSeeder::class,
            MasterEmployeeSeeder::class,
            OP7Seeder::class,
            MaintenanceUnitSeeder::class,
            MaintenanceEmployeeSeeder::class,
            ShiftRegularRosterSeeder::class,
            SafetySeeder::class,
        ]);

        $adminRole = Role::firstOrCreate(['name' => Role::ADMIN]);
        $managerRole = Role::firstOrCreate(['name' => Role::MANAGER]);
        $operationalRole = Role::firstOrCreate(['name' => Role::OPERATIONAL]);
        $maintenanceRole = Role::firstOrCreate(['name' => Role::MAINTENANCE]);
        $safetyRole = Role::firstOrCreate(['name' => Role::SAFETY]);

        User::updateOrCreate([
            'username' => 'admin',
        ], [
            'name' => 'Administrator Sistem',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role_id' => $adminRole->id,
            'status' => 'aktif',
            'group' => null,
            'signature_path' => 'signatures/admin.png',
        ]);

        User::updateOrCreate([
            'username' => 'manajer',
        ], [
            'name' => 'Mustari',
            'email' => 'manager@example.com',
            'password' => Hash::make('password'),
            'role_id' => $managerRole->id,
            'status' => 'aktif',
            'group' => null,
            'signature_path' => 'signatures/manajer.png',
        ]);

        // Nama akun Karu/Wakaru diselaraskan dengan roster Shift Regular
        // (lihat ShiftRegularRosterSeeder).
        $groupLeaders = [
            'a' => ['karu' => 'Jhon Maradona Mailoor', 'wakaru' => 'Zainuddin'],
            'b' => ['karu' => 'Nurul Huda', 'wakaru' => 'Ryman Oloan Manurung'],
            'c' => ['karu' => 'Jawawi', 'wakaru' => 'Ahmad Bisri'],
            'd' => ['karu' => 'Sugianto', 'wakaru' => 'Syamsuddin R'],
        ];

        foreach (['a', 'b', 'c', 'd'] as $group) {
            $password = Hash::make('password');

            User::updateOrCreate([
                'username' => "karu.{$group}",
            ], [
                'name' => $groupLeaders[$group]['karu'],
                'email' => "karu.{$group}@example.com",
                'password' => $password,
                'role_id' => $operationalRole->id,
                'status' => 'aktif',
                'group' => $group,
                'signature_path' => "signatures/karu.{$group}.png",
            ]);

            User::updateOrCreate([
                'username' => "wakaru.{$group}",
            ], [
                'name' => $groupLeaders[$group]['wakaru'],
                'email' => "wakil.{$group}@example.com",
                'password' => $password,
                'role_id' => $operationalRole->id,
                'status' => 'aktif',
                'group' => $group,
                'signature_path' => "signatures/wakaru.{$group}.png",
            ]);
        }

        // Akun singkat untuk smoke test lokal.
        User::updateOrCreate([
            'username' => 'petugas',
        ], [
            'name' => 'Operasional Regu A',
            'email' => 'petugas@example.com',
            'password' => Hash::make('password'),
            'role_id' => $operationalRole->id,
            'status' => 'aktif',
            'group' => 'A',
            'signature_path' => 'signatures/petugas.png',
        ]);

        // Satu akun pembuat untuk modul Pemeliharaan: Kasi Pemeliharaan
        // (MD §1.3 poin 6 — tidak diperlukan RBAC bertingkat). Nama selaras
        // dengan roster di MaintenanceEmployeeSeeder.
        User::updateOrCreate([
            'username' => 'kasi.pemeliharaan',
        ], [
            'name' => 'Sungkono',
            'email' => 'kasi.pemeliharaan@example.com',
            'password' => Hash::make('password'),
            'role_id' => $maintenanceRole->id,
            'status' => 'aktif',
            'group' => null,
            'signature_path' => 'signatures/kasi.pemeliharaan.png',
        ]);

        // Satu akun pembuat untuk modul Safety/K3: Karu Safety. Nama selaras
        // dengan blok tanda tangan "Dilaporkan, Usman Ali — Karu Safety"
        // pada form fisik (PERANCANGAN_MODUL_SAFETY.md §1).
        User::updateOrCreate([
            'username' => 'karu.safety',
        ], [
            'name' => 'Usman Ali',
            'email' => 'karu.safety@example.com',
            'password' => Hash::make('password'),
            'role_id' => $safetyRole->id,
            'status' => 'aktif',
            'group' => null,
            'signature_path' => 'signatures/karu.safety.png',
        ]);

        $this->call([
            DailyReportSeeder::class,
            HistoryPaginationReportSeeder::class,
        ]);
    }
}
