<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('master_inventory_items', function (Blueprint $table) {
            if (! Schema::hasColumn('master_inventory_items', 'category')) {
                $table->string('category')->nullable()->after('name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('master_inventory_items', function (Blueprint $table) {
            if (Schema::hasColumn('master_inventory_items', 'category')) {
                $table->dropColumn('category');
            }
        });
    }
};
