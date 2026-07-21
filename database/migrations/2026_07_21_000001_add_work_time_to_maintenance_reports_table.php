<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tambah jam kerja pada laporan pemeliharaan. Sebelumnya field "Rentang Jam
 * Kerja" di form hanya alat bantu isi cepat Daftar Hadir dan tidak tersimpan.
 * Sekarang disimpan sebagai jam laporan agar proteksi laporan ganda pada
 * tanggal yang sama bisa dibedakan per jam mulai kerja (selaras dengan
 * work_time_start pada safety_reports).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maintenance_reports', function (Blueprint $table) {
            $table->string('work_time_start')->nullable()->after('day_name');
            $table->string('work_time_end')->nullable()->after('work_time_start');
        });
    }

    public function down(): void
    {
        Schema::table('maintenance_reports', function (Blueprint $table) {
            $table->dropColumn(['work_time_start', 'work_time_end']);
        });
    }
};
