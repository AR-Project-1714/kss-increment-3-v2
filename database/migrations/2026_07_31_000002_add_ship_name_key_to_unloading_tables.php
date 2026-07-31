<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Nama kanonik kapal untuk kegiatan bongkar.
 *
 * Migrasi sebelumnya (2026_07_31_000001) memasang ship_name_key pada kegiatan
 * yang punya operasi kapal: muat kantong, muat curah, dan muat amoniak. Padahal
 * bongkar bahan baku dan bongkar/muat container juga mencatat nama kapal yang
 * diketik bebas tiap shift, dengan akibat yang sama — satu kapal terhitung
 * sebagai beberapa kapal pada rekap dan panel rincian.
 *
 * Kedua tabel ini belum terhubung ke ship_operations (formnya memang tidak
 * punya tombol Simpan Operasi Kapal), sehingga nama kanoniklah yang menjadi
 * satu-satunya identitas kapal di sana.
 */
return new class extends Migration
{
    /** @var array<int, string> */
    private const TABLES = ['material_activities', 'container_activities'];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $blueprint) use ($table): void {
                if (! Schema::hasColumn($table, 'ship_name_key')) {
                    $blueprint->string('ship_name_key')->nullable()->after('ship_name');
                    $blueprint->index('ship_name_key');
                }
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $blueprint) use ($table): void {
                if (Schema::hasColumn($table, 'ship_name_key')) {
                    $blueprint->dropIndex(['ship_name_key']);
                    $blueprint->dropColumn('ship_name_key');
                }
            });
        }
    }
};
