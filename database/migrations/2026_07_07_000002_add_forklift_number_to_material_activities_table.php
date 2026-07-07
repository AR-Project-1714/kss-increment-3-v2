<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bongkar Bahan Baku kini punya kolom Nomor Forklift terpisah dari nama
 * operatornya, mengikuti pola turba_activities.fl_no.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('material_activities', function (Blueprint $table): void {
            if (! Schema::hasColumn('material_activities', 'forklift_number')) {
                $table->string('forklift_number')->nullable()->after('forklift_operator_names');
            }
        });
    }

    public function down(): void
    {
        Schema::table('material_activities', function (Blueprint $table): void {
            if (Schema::hasColumn('material_activities', 'forklift_number')) {
                $table->dropColumn('forklift_number');
            }
        });
    }
};
