<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kondisi Unit dicatat per unit. Total ready/rusak dihitung
 * otomatis oleh sistem dari baris tabel ini. restrictOnDelete: unit master yang
 * sudah dipakai pada laporan tidak boleh terhapus begitu saja.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_unit_conditions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('maintenance_report_id')->constrained()->cascadeOnDelete();
            $table->foreignId('maintenance_unit_id')->constrained()->restrictOnDelete();
            $table->enum('condition', ['ready', 'rusak'])->default('ready');
            $table->string('notes')->nullable();
            $table->timestamps();

            // Satu status per unit per laporan.
            $table->unique(['maintenance_report_id', 'maintenance_unit_id'], 'maintenance_unit_condition_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_unit_conditions');
    }
};
