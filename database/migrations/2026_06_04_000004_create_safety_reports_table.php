<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel induk laporan harian K3. FSM lebih pendek dari operasional (tanpa
 * acknowledged) karena tidak ada serah-terima antar regu: draft -> submitted ->
 * approved. Pengesahan dua pihak: created_by (Karu Safety) & approved_by (Manajer).
 * Lihat MD §1, §2.2, §3.1.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('safety_reports', function (Blueprint $table) {
            $table->id();
            $table->string('document_number')->nullable()->unique();   // DOC-2026-00X
            $table->date('report_date')->nullable();
            $table->string('time_range')->nullable();                   // "19:00-03:00" (teks bebas, sesuai form)
            $table->string('shift')->nullable();                        // OPSIONAL — hanya bila manajer butuh filter shift
            $table->enum('status', ['draft', 'submitted', 'approved'])->default('draft');

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();  // role: safety
            $table->timestamp('submitted_at')->nullable();
            $table->string('reporter_signature_path')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete(); // role: manajer
            $table->timestamp('approved_at')->nullable();
            $table->string('approver_signature_path')->nullable();

            $table->timestamps();

            $table->index(['status', 'report_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('safety_reports');
    }
};
