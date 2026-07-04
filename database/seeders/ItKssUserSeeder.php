<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ItKssUserSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::firstOrCreate(['name' => Role::ADMIN]);

        User::updateOrCreate([
            'username' => 'it-kss',
        ], [
            'name' => 'IT KSS',
            'email' => 'it-kss@example.com',
            'password' => Hash::make('password'),
            'role_id' => $adminRole->id,
            'status' => 'aktif',
            'group' => null,
            'signature_path' => 'signatures/it-kss.png',
        ]);
    }
}
