<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Operasi kapal untuk kegiatan bongkar.
 *
 * Sampai sekarang hanya pemuatan (kantong, curah, amoniak) yang punya operasi
 * kapal. Bongkar bahan baku dan bongkar/muat container mencatat nama kapalnya
 * sebagai teks bebas tanpa induk apa pun, sehingga:
 *
 *   - kunjungan kapal yang sama pada bulan berbeda tidak bisa dibedakan dari
 *     satu kunjungan panjang, dan
 *   - keterangan kapal (agen, dermaga, kapasitas) harus diketik ulang tiap
 *     shift, yang justru menjadi sumber ejaan yang berbeda-beda.
 *
 * Dengan kolom ini keduanya memakai mekanisme yang sama persis dengan
 * pemuatan: saran kapal berjalan, penyambungan lintas shift, dan penandaan
 * "Selesai" saat kapal berangkat.
 */
return new class extends Migration
{
    /** @var array<int, string> */
    private const TABLES = ['material_activities', 'container_activities'];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $blueprint) use ($table): void {
                if (! Schema::hasColumn($table, 'ship_operation_id')) {
                    $blueprint->foreignId('ship_operation_id')
                        ->nullable()
                        ->after('daily_report_id')
                        ->constrained('ship_operations')
                        ->nullOnDelete();
                }
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $blueprint) use ($table): void {
                if (Schema::hasColumn($table, 'ship_operation_id')) {
                    $blueprint->dropConstrainedForeignId('ship_operation_id');
                }
            });
        }
    }
};
