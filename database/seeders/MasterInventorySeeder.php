<?php

namespace Database\Seeders;

use App\Models\MasterInventoryItem;
use Illuminate\Database\Seeder;

class MasterInventorySeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['name' => 'Mesin Jahit Portable', 'stock' => 1],
            ['name' => 'HT Mot. CP1660', 'stock' => 2],
            ['name' => 'HT Mot. P6620i', 'stock' => 2],
            ['name' => 'HT Mot. Xir C2620', 'stock' => 10],
            ['name' => 'Spare Battery', 'stock' => 7],
            ['name' => 'Charger', 'stock' => 7],
            ['name' => 'Computer + Printer', 'stock' => 1],
            ['name' => 'Kalkulator', 'stock' => 1],
            ['name' => 'Lemari Etalase', 'stock' => 1],
            ['name' => 'Gas Masker', 'stock' => 1],
            ['name' => 'Lemari Loker', 'stock' => 4],
            ['name' => 'Pemadam Api', 'stock' => 1],
            ['name' => 'AC', 'stock' => 2],
        ];

        foreach ($items as $item) {
            MasterInventoryItem::updateOrCreate(
                ['name' => $item['name']],
                ['stock' => $item['stock'], 'status' => 'active']
            );
        }
    }
}
