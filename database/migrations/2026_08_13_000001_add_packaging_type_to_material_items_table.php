<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('material_items', function (Blueprint $table): void {
            $table->string('packaging_type', 100)->nullable()->after('raw_material_type');
        });
    }

    public function down(): void
    {
        Schema::table('material_items', function (Blueprint $table): void {
            $table->dropColumn('packaging_type');
        });
    }
};
