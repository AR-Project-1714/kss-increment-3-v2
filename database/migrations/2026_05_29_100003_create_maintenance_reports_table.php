<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel induk laporan harian pemeliharaan. Tanpa kolom shift/group/jam kerja/
 * group penerima (tidak relevan, lihat MD §1.2). Dua pengesahan diwakili
 * created_by/submitted_at (Kasi) dan approved_by/approved_at (Manajer).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_reports', function (Blueprint $table) {
            $table->id();
            $table->date('report_date')->nullable();
            $table->string('day_name')->nullable();          // diturunkan dari tanggal
            $table->enum('status', ['draft', 'submitted', 'approved'])->default('draft');

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();

            // Field informatif untuk kesetiaan cetak PDF (bukan bagian workflow).
            $table->string('karu_pemeliharaan_name')->nullable();
            $table->string('karu_peralatan_name')->nullable();

            $table->timestamps();

            $table->index(['status', 'report_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_reports');
    }
};
