<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('container_activities', function (Blueprint $table): void {
            if (! Schema::hasColumn('container_activities', 'capacity_empty')) {
                $table->decimal('capacity_empty', 15, 2)->nullable()->after('capacity');
            }

            if (! Schema::hasColumn('container_activities', 'capacity_full')) {
                $table->decimal('capacity_full', 15, 2)->nullable()->after('capacity_empty');
            }
        });
    }

    public function down(): void
    {
        Schema::table('container_activities', function (Blueprint $table): void {
            foreach (['capacity_full', 'capacity_empty'] as $column) {
                if (Schema::hasColumn('container_activities', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
