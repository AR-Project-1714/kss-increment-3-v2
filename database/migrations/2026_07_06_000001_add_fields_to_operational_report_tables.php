<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Penyesuaian form Laporan Operasi sesuai catatan revisi:
 * - Dermaga (jetty) pada Bongkar Bahan Baku & Bongkar/Muat Container.
 * - Jam Container disimpan sebagai rentang teks bebas (time_text), mis. "23:00 - 04:00".
 * - Kelengkapan tracking pupuk kantong (Turba): Tally Gudang Terima, FL No, TRL No.
 *
 * Semua kolom nullable/aditif sehingga data lama tetap aman.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('material_activities', function (Blueprint $table): void {
            if (! Schema::hasColumn('material_activities', 'jetty')) {
                $table->string('jetty')->nullable()->after('agent');
            }
        });

        Schema::table('container_activities', function (Blueprint $table): void {
            if (! Schema::hasColumn('container_activities', 'jetty')) {
                $table->string('jetty')->nullable()->after('agent');
            }
        });

        Schema::table('container_items', function (Blueprint $table): void {
            if (! Schema::hasColumn('container_items', 'time_text')) {
                $table->string('time_text')->nullable()->after('time');
            }
        });

        Schema::table('turba_activities', function (Blueprint $table): void {
            if (! Schema::hasColumn('turba_activities', 'tally_gudang_terima')) {
                $table->string('tally_gudang_terima')->nullable()->after('tally_gudang_names');
            }
            if (! Schema::hasColumn('turba_activities', 'fl_no')) {
                $table->string('fl_no')->nullable()->after('forklift_operator_names');
            }
            if (! Schema::hasColumn('turba_activities', 'trl_no')) {
                $table->string('trl_no')->nullable()->after('driver_names');
            }
        });
    }

    public function down(): void
    {
        Schema::table('material_activities', function (Blueprint $table): void {
            if (Schema::hasColumn('material_activities', 'jetty')) {
                $table->dropColumn('jetty');
            }
        });

        Schema::table('container_activities', function (Blueprint $table): void {
            if (Schema::hasColumn('container_activities', 'jetty')) {
                $table->dropColumn('jetty');
            }
        });

        Schema::table('container_items', function (Blueprint $table): void {
            if (Schema::hasColumn('container_items', 'time_text')) {
                $table->dropColumn('time_text');
            }
        });

        Schema::table('turba_activities', function (Blueprint $table): void {
            foreach (['tally_gudang_terima', 'fl_no', 'trl_no'] as $column) {
                if (Schema::hasColumn('turba_activities', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
