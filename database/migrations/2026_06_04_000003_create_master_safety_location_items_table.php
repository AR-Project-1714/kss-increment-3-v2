<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Template inspeksi: mendefinisikan item apa yang muncul di lokasi mana. Set item
 * berbeda tiap lokasi sehingga form baru di-seed dari pivot ini (lihat MD §3.3).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_safety_location_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained('master_safety_locations')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('master_safety_items')->cascadeOnDelete();
            $table->unsignedSmallInteger('default_qty')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['location_id', 'item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_safety_location_items');
    }
};
