<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Daftar Hadir Karyawan pemeliharaan. employee_name & position adalah snapshot
 * agar laporan historis tetap akurat meski master roster berubah (MD §3.3).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('maintenance_report_id')->constrained()->cascadeOnDelete();
            $table->foreignId('maintenance_employee_id')->nullable()->constrained()->nullOnDelete();
            $table->string('employee_name');                 // snapshot
            $table->string('position')->nullable();          // snapshot
            $table->time('time_in')->nullable();
            $table->time('time_out')->nullable();
            $table->string('notes')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('maintenance_report_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_attendances');
    }
};
