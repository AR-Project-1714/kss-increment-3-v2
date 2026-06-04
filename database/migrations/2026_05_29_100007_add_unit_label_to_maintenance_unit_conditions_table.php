<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Snapshot nama unit pada Kondisi Unit. Kolom Unit di form bersifat editable
 * namun terisi otomatis dari master; nilai yang ditampilkan/diedit disimpan di
 * sini agar laporan historis tetap akurat meski master berubah.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maintenance_unit_conditions', function (Blueprint $table) {
            if (! Schema::hasColumn('maintenance_unit_conditions', 'unit_label')) {
                $table->string('unit_label')->nullable()->after('maintenance_unit_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('maintenance_unit_conditions', function (Blueprint $table) {
            if (Schema::hasColumn('maintenance_unit_conditions', 'unit_label')) {
                $table->dropColumn('unit_label');
            }
        });
    }
};
