<?php

use App\Support\MaterialPackaging;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Jenis kemasan bongkar bahan baku bertambah dari dua menjadi empat, dan masih
 * mungkin bertambah lagi. Selama tonase dihitung ulang dari *nama* kemasan,
 * menyunting katalog akan diam-diam menggeser angka laporan yang sudah
 * tersimpan. Karena itu faktor konversinya ikut disimpan pada barisnya.
 *
 * `packaging_type` tetap dipertahankan sebagai label tampilan sekaligus kolom
 * pencarian laporan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('material_items', function (Blueprint $table): void {
            $table->string('packaging_code', 40)->nullable()->after('packaging_type');
            $table->decimal('packaging_factor', 10, 4)->nullable()->after('packaging_code');
        });

        $this->backfill();
    }

    /**
     * Isi kode dan faktor baris yang baru punya label kemasan.
     *
     * Dipisah dari up() supaya pengujian dapat menjalankan pengisian ini apa
     * adanya tanpa perlu membongkar skema tabel lebih dulu.
     *
     * Label lama dipetakan lewat katalog, termasuk ejaan alias seperti
     * "Jumbo Bag" yang kini bernama "Jumbo Bag 1 Ton". Faktornya sama persis
     * (1 Ton per Bag), jadi tidak ada angka laporan yang bergeser.
     */
    public function backfill(): void
    {
        $labels = DB::table('material_items')
            ->select('packaging_type')
            ->whereNotNull('packaging_type')
            ->distinct()
            ->pluck('packaging_type');

        foreach ($labels as $label) {
            $package = MaterialPackaging::findByLabel($label);

            if ($package === null) {
                continue;
            }

            DB::table('material_items')
                ->where('packaging_type', $label)
                ->update([
                    'packaging_type' => $package['label'],
                    'packaging_code' => $package['code'],
                    'packaging_factor' => $package['tonPerBag'],
                ]);
        }
    }

    public function down(): void
    {
        // Baris berkemasan baru tidak punya padanan label lama; setelah kolom
        // faktor hilang, tonasenya dibaca sebagai Ton seperti data pra-kemasan.
        DB::table('material_items')
            ->where('packaging_code', MaterialPackaging::CODE_JUMBO_1000)
            ->update(['packaging_type' => 'Jumbo Bag']);

        Schema::table('material_items', function (Blueprint $table): void {
            $table->dropColumn(['packaging_code', 'packaging_factor']);
        });
    }
};
