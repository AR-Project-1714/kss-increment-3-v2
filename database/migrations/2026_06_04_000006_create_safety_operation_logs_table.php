<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Section 8 form: Kegiatan Operasi & Pemeliharaan. KONDISI di sini berupa teks
 * bebas ("Aman"), berbeda dari enum inspeksi (lihat MD §1 & §2.2).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('safety_operation_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('safety_report_id')->constrained('safety_reports')->cascadeOnDelete();
            $table->string('activity_name');             // GRESIK NIAGA, PENGIRIMAN KE GD TURBA, RENTAL ...
            $table->string('condition')->nullable();     // teks: "Aman" (BUKAN enum inspeksi)
            $table->string('action')->nullable();        // Tindakan
            $table->string('notes')->nullable();         // Keterangan: "In Bags" / "Curah"
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('safety_report_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('safety_operation_logs');
    }
};
