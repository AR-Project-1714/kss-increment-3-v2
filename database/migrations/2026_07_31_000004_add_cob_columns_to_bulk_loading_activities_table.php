<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * COB Diterima & COB Diserahkan per kapal (bukan per baris log jam-jaman),
 * dipakai untuk menghitung Jumlah Pemuatan = COB Diserahkan - COB Diterima.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bulk_loading_activities', function (Blueprint $table): void {
            if (! Schema::hasColumn('bulk_loading_activities', 'cob_received')) {
                $table->decimal('cob_received', 15, 2)->nullable()->after('capacity');
            }

            if (! Schema::hasColumn('bulk_loading_activities', 'cob_delivered')) {
                $table->decimal('cob_delivered', 15, 2)->nullable()->after('cob_received');
            }

            if (! Schema::hasColumn('bulk_loading_activities', 'loading_qty')) {
                $table->decimal('loading_qty', 15, 2)->nullable()->after('cob_delivered');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bulk_loading_activities', function (Blueprint $table): void {
            foreach (['cob_received', 'cob_delivered', 'loading_qty'] as $column) {
                if (Schema::hasColumn('bulk_loading_activities', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
