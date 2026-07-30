<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bundel ZIP arsip yang disiapkan di latar (background) oleh queue worker.
 *
 * Unduh massal di bawah batas instan tetap dikerjakan langsung dalam satu
 * request. Tabel ini hanya untuk permintaan besar: barisnya menyimpan daftar
 * laporan yang harus dibundel, progres pengerjaan, dan lokasi berkas hasilnya
 * sampai kedaluwarsa.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('archive_bundles', function (Blueprint $table) {
            $table->id();
            // Token dipakai di URL (bukan id berurut) supaya bundel milik orang
            // lain tidak bisa ditebak.
            $table->uuid('token')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('context', 20)->default('admin');
            $table->string('status', 20)->default('queued')->index();
            $table->unsignedInteger('total_reports')->default(0);
            $table->unsignedInteger('processed_reports')->default(0);
            $table->unsignedInteger('skipped_reports')->default(0);
            // Refs (kind + id) dibekukan saat permintaan dibuat, jadi isi bundel
            // sama dengan yang dilihat pengguna walau arsip berubah setelahnya.
            $table->json('refs');
            $table->string('filter_summary')->nullable();
            $table->string('file_name')->nullable();
            $table->string('file_path')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamp('downloaded_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('archive_bundles');
    }
};
