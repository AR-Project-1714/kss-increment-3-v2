<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $legacyPetugas = Role::where('name', 'petugas')->first();

        foreach (Role::NAMES as $roleName) {
            Role::firstOrCreate(['name' => $roleName]);
        }

        if ($legacyPetugas) {
            $operationalRole = Role::where('name', Role::OPERATIONAL)->first();

            User::where('role_id', $legacyPetugas->id)->update([
                'role_id' => $operationalRole?->id,
            ]);

            $legacyPetugas->delete();
        }
    }
}
