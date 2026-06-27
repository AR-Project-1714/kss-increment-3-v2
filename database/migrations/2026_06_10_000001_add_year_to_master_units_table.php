<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('master_units', function (Blueprint $table): void {
            if (! Schema::hasColumn('master_units', 'year')) {
                $table->unsignedSmallInteger('year')->nullable()->after('macro_category');
            }
        });
    }

    public function down(): void
    {
        Schema::table('master_units', function (Blueprint $table): void {
            if (Schema::hasColumn('master_units', 'year')) {
                $table->dropColumn('year');
            }
        });
    }
};
