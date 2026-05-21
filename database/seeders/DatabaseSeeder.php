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
        ]);

        $adminRole = Role::firstOrCreate(['name' => Role::ADMIN]);
        $managerRole = Role::firstOrCreate(['name' => Role::MANAGER]);
        $operationalRole = Role::firstOrCreate(['name' => Role::OPERATIONAL]);

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
            'name' => 'Manajer Operasional',
            'email' => 'manager@example.com',
            'password' => Hash::make('password'),
            'role_id' => $managerRole->id,
            'status' => 'aktif',
            'group' => null,
            'signature_path' => 'signatures/admin.png',
        ]);

        foreach (['a', 'b', 'c', 'd'] as $group) {
            $upperGroup = strtoupper($group);
            $password = Hash::make('password');

            User::updateOrCreate([
                'username' => "karu.{$group}",
            ], [
                'name' => "Kepala Regu {$upperGroup}",
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
                'name' => "Wakil Regu {$upperGroup}",
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

        $this->call([
            DailyReportSeeder::class,
            HistoryPaginationReportSeeder::class,
        ]);
    }
}
