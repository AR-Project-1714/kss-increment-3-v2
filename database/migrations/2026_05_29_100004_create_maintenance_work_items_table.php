<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pekerjaan Utama (terikat Group I-IV) + Pekerjaan Prioritas (dinamis) digabung
 * dalam satu tabel lewat kolom work_type. Pola snapshot
 * unit_label berdampingan dengan FK ke master agar laporan historis tetap akurat.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_work_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('maintenance_report_id')->constrained()->cascadeOnDelete();
            $table->enum('work_type', ['utama', 'prioritas']);
            $table->string('work_group')->nullable();        // I/II/III/IV (utama saja), varchar agar bisa gabung
            $table->foreignId('maintenance_unit_id')->nullable()->constrained()->nullOnDelete();
            $table->string('unit_label')->nullable();        // fallback teks unit (mis. BENGKEL)
            $table->text('description')->nullable();          // uraian pekerjaan
            $table->string('assignee')->nullable();          // petugas (teks bebas, multi-nama)
            $table->boolean('is_completed')->default(false);
            $table->string('notes')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['maintenance_report_id', 'work_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_work_items');
    }
};
