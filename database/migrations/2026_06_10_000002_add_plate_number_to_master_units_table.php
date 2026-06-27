<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('master_units', function (Blueprint $table): void {
            if (! Schema::hasColumn('master_units', 'plate_number')) {
                $table->string('plate_number')->nullable()->after('unit_number');
            }
        });
    }

    public function down(): void
    {
        Schema::table('master_units', function (Blueprint $table): void {
            if (Schema::hasColumn('master_units', 'plate_number')) {
                $table->dropColumn('plate_number');
            }
        });
    }
};
