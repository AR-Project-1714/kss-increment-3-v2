<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bulk_loading_activities', function (Blueprint $table) {
            $table->string('activity_type', 30)
                ->default('muat_curah')
                ->after('daily_report_id')
                ->index();
        });
    }

    public function down(): void
    {
        Schema::table('bulk_loading_activities', function (Blueprint $table) {
            $table->dropIndex(['activity_type']);
            $table->dropColumn('activity_type');
        });
    }
};
