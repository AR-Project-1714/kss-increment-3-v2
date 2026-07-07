<?php

namespace Database\Seeders;

use App\Models\MasterEnvironmentItem;
use Illuminate\Database\Seeder;

class MasterEnvironmentSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['name' => 'Ruangan Shelter', 'category' => 'Kebersihan'],
            ['name' => 'Halaman Shelter', 'category' => 'Kebersihan'],
            ['name' => 'Selokan/Parit', 'category' => 'Kebersihan'],
            ['name' => 'Jala-Jala Angkat', 'category' => 'Kerapian'],
            ['name' => 'Jala-Jala Lambung', 'category' => 'Kerapian'],
            ['name' => 'Terpal', 'category' => 'Kerapian'],
            ['name' => 'Chain Sling', 'category' => 'Kerapian'],
        ];

        foreach ($items as $index => $item) {
            MasterEnvironmentItem::updateOrCreate(
                ['name' => $item['name']],
                ['category' => $item['category'], 'sort_order' => $index + 1, 'is_active' => true]
            );
        }
    }
}
