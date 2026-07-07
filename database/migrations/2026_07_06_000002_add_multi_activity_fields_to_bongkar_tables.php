<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bongkar Bahan Baku & Bongkar/Muat Container kini bisa punya lebih dari satu
 * kegiatan per laporan (mengikuti pola Muat Kantong/Curah), jadi kedua tabel
 * butuh kolom `sequence` untuk urutan kegiatan. Sekalian tambah `truck_number`
 * agar No Truck bisa dicatat terpisah dari nama driver (pola sama seperti
 * loading_activities.truck_number).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('material_activities', function (Blueprint $table): void {
            if (! Schema::hasColumn('material_activities', 'sequence')) {
                $table->integer('sequence')->default(1)->after('id');
            }
            if (! Schema::hasColumn('material_activities', 'truck_number')) {
                $table->string('truck_number')->nullable()->after('driver_names');
            }
        });

        Schema::table('container_activities', function (Blueprint $table): void {
            if (! Schema::hasColumn('container_activities', 'sequence')) {
                $table->integer('sequence')->default(1)->after('id');
            }
            if (! Schema::hasColumn('container_activities', 'truck_number')) {
                $table->string('truck_number')->nullable()->after('driver_names');
            }
        });
    }

    public function down(): void
    {
        Schema::table('material_activities', function (Blueprint $table): void {
            foreach (['sequence', 'truck_number'] as $column) {
                if (Schema::hasColumn('material_activities', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('container_activities', function (Blueprint $table): void {
            foreach (['sequence', 'truck_number'] as $column) {
                if (Schema::hasColumn('container_activities', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
