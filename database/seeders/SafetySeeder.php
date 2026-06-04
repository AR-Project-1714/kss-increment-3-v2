<?php

namespace Database\Seeders;

use App\Models\MasterSafetyItem;
use App\Models\MasterSafetyLocation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeder master data K3 sesuai form fisik (PERANCANGAN_MODUL_SAFETY.md §6):
 * katalog item beserta is_countable, 7 lokasi tetap, dan template lokasi -> item.
 * Idempotent: aman dijalankan ulang.
 */
class SafetySeeder extends Seeder
{
    public function run(): void
    {
        // ── Katalog item (name => is_countable) ──────────────────────────────
        $items = [
            'Bangunan'             => true,
            'Kebersihan'           => false,
            'Kerapian'             => false,
            'Instalasi Listrik'    => false,
            'Lampu/Pencahayaan'    => true,
            'Instalasi Air'        => false,
            'AC'                   => true,
            'APAR'                 => true,
            'Kotak P3K'            => true,
            'Papan Informasi'      => true,
            'Mesin Check O\'clock' => true,
            'Exaus Fan'            => true,
        ];

        $itemIds = [];
        foreach ($items as $name => $countable) {
            $item = MasterSafetyItem::firstOrNew(['name' => $name]);
            $item->is_countable = $countable;
            $item->is_active = true;
            $item->save();
            $itemIds[$name] = $item->id;
        }

        // ── Lokasi + template item (urut sesuai form) ────────────────────────
        $template = [
            'Shelter Shift Operasi' => [
                'Bangunan', 'Kebersihan', 'Kerapian', 'Instalasi Listrik', 'Lampu/Pencahayaan',
                'Instalasi Air', 'AC', 'APAR', 'Kotak P3K', 'Papan Informasi', 'Mesin Check O\'clock',
            ],
            'Work Shop dan Sekitarnya' => [
                'Bangunan', 'Kebersihan', 'Kerapian', 'Instalasi Listrik', 'Lampu/Pencahayaan',
                'Instalasi Air', 'APAR', 'Kotak P3K',
            ],
            'Shelter Mekanik dan Sekitarnya' => [
                'Bangunan', 'Kebersihan', 'Kerapian', 'Instalasi Listrik', 'Lampu/Pencahayaan',
                'Instalasi Air', 'AC', 'APAR',
            ],
            'Shelter Karu Peralatan' => [
                'Bangunan', 'Kebersihan', 'Kerapian', 'Instalasi Listrik', 'Lampu/Pencahayaan', 'APAR',
            ],
            'Kontainer Peralatan Bongkar Muat' => [
                'Bangunan', 'Kebersihan', 'Kerapian', 'Instalasi Listrik', 'Lampu/Pencahayaan', 'APAR',
            ],
            'Gudang Spare Part' => [
                'Bangunan', 'Kebersihan', 'Kerapian', 'Instalasi Listrik', 'Lampu/Pencahayaan', 'APAR', 'Exaus Fan',
            ],
            'Shelter Operasi di Tursina dan Sekitarnya' => [
                'Bangunan', 'Kebersihan', 'Kerapian', 'Instalasi Listrik', 'Lampu/Pencahayaan',
                'AC', 'APAR', 'Kotak P3K', 'Mesin Check O\'clock',
            ],
        ];

        $locationSort = 0;
        foreach ($template as $locationName => $itemNames) {
            $location = MasterSafetyLocation::firstOrNew(['name' => $locationName]);
            $location->sort_order = $locationSort++;
            $location->is_active = true;
            $location->save();

            $sync = [];
            foreach ($itemNames as $sort => $itemName) {
                $itemId = $itemIds[$itemName] ?? null;
                if ($itemId === null) {
                    continue;
                }

                $isCountable = $items[$itemName] ?? false;

                $sync[$itemId] = [
                    'default_qty' => $isCountable ? 1 : null,
                    'sort_order'  => $sort,
                    'updated_at'  => now(),
                    'created_at'  => now(),
                ];
            }

            // Sinkronkan template tanpa menghapus baris yang sudah ada di luar set.
            $existing = DB::table('master_safety_location_items')
                ->where('location_id', $location->id)
                ->pluck('item_id')
                ->all();

            foreach ($sync as $itemId => $pivot) {
                if (in_array($itemId, $existing, true)) {
                    DB::table('master_safety_location_items')
                        ->where('location_id', $location->id)
                        ->where('item_id', $itemId)
                        ->update([
                            'default_qty' => $pivot['default_qty'],
                            'sort_order'  => $pivot['sort_order'],
                            'updated_at'  => now(),
                        ]);
                } else {
                    DB::table('master_safety_location_items')->insert([
                        'location_id' => $location->id,
                        'item_id'     => $itemId,
                        'default_qty' => $pivot['default_qty'],
                        'sort_order'  => $pivot['sort_order'],
                        'created_at'  => now(),
                        'updated_at'  => now(),
                    ]);
                }
            }
        }
    }
}
