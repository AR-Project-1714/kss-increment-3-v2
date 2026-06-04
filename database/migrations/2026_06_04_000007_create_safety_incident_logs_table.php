<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Section 9 form: Laporan Kejadian & Lain-lain. Kosong pada sampel form -> semua
 * kolom nullable dan boleh nol baris (lihat MD §1 & §2.2).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('safety_incident_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('safety_report_id')->constrained('safety_reports')->cascadeOnDelete();
            $table->string('description')->nullable();   // Uraian kejadian
            $table->string('condition')->nullable();
            $table->string('action')->nullable();
            $table->string('notes')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('safety_report_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('safety_incident_logs');
    }
};
