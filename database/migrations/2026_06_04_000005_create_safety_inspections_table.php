<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Baris inspeksi K3 (Lokasi -> Item -> QTY -> Kondisi -> Rekomendasi). Kolom
 * condition adalah enum tunggal 4-nilai (bukan dua sumbu — lihat MD §2.4).
 * *_name_snapshot menjaga integritas historis bila master diubah/dihapus.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('safety_inspections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('safety_report_id')->constrained('safety_reports')->cascadeOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('master_safety_locations')->nullOnDelete();
            $table->foreignId('item_id')->nullable()->constrained('master_safety_items')->nullOnDelete();
            $table->string('location_name_snapshot');   // integritas historis (lihat §2.4)
            $table->string('item_name_snapshot');
            $table->unsignedSmallInteger('qty')->nullable();
            $table->enum('condition', ['bagus', 'rusak', 'normal', 'tidak_normal'])->nullable();
            $table->string('recommendation')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['safety_report_id', 'location_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('safety_inspections');
    }
};
