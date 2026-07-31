<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dua tambahan yang membuat tonase muat curah bisa dihitung benar.
 *
 * 1. ship_name_key — bentuk kanonik nama kapal (lihat ShipNameNormalizer).
 *    Nama tampilan tetap disimpan apa adanya sesuai yang diketik petugas;
 *    kolom ini yang dipakai untuk mencocokkan satu pelayaran lintas shift.
 *
 * 2. cob_normalized & cob_delta pada bulk_loading_logs. Kolom cob berisi
 *    Cargo On Board, yaitu pembacaan KUMULATIF muatan di kapal, bukan tambahan
 *    per baris. Menjumlahkan cob sama saja menjumlahkan angka odometer.
 *    cob_delta menyimpan pertambahan nyata tiap baris sehingga SUM() kembali
 *    bermakna, dan cob_normalized menyimpan pembacaan setelah satuannya
 *    diseragamkan agar hasilnya bisa ditelusuri ulang.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ship_operations', function (Blueprint $table): void {
            if (! Schema::hasColumn('ship_operations', 'ship_name_key')) {
                $table->string('ship_name_key')->nullable()->after('ship_name');
                $table->index(['type', 'ship_name_key']);
            }
        });

        Schema::table('bulk_loading_activities', function (Blueprint $table): void {
            if (! Schema::hasColumn('bulk_loading_activities', 'ship_name_key')) {
                $table->string('ship_name_key')->nullable()->after('ship_name');
                $table->index('ship_name_key');
            }
        });

        Schema::table('loading_activities', function (Blueprint $table): void {
            if (! Schema::hasColumn('loading_activities', 'ship_name_key')) {
                $table->string('ship_name_key')->nullable()->after('ship_name');
                $table->index('ship_name_key');
            }
        });

        Schema::table('bulk_loading_logs', function (Blueprint $table): void {
            if (! Schema::hasColumn('bulk_loading_logs', 'cob_normalized')) {
                $table->decimal('cob_normalized', 15, 2)->nullable()->after('cob');
            }

            if (! Schema::hasColumn('bulk_loading_logs', 'cob_delta')) {
                $table->decimal('cob_delta', 15, 2)->nullable()->after('cob_normalized');
            }
        });

        // Muatan cair (amoniak) ditimbang sampai dua angka di belakang koma —
        // 4420.25 ton terpotong menjadi 4420 selama cob masih integer.
        Schema::table('bulk_loading_logs', function (Blueprint $table): void {
            $table->decimal('cob', 15, 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('bulk_loading_logs', function (Blueprint $table): void {
            $table->integer('cob')->nullable()->change();
        });

        Schema::table('bulk_loading_logs', function (Blueprint $table): void {
            foreach (['cob_delta', 'cob_normalized'] as $column) {
                if (Schema::hasColumn('bulk_loading_logs', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('loading_activities', function (Blueprint $table): void {
            if (Schema::hasColumn('loading_activities', 'ship_name_key')) {
                $table->dropIndex(['ship_name_key']);
                $table->dropColumn('ship_name_key');
            }
        });

        Schema::table('bulk_loading_activities', function (Blueprint $table): void {
            if (Schema::hasColumn('bulk_loading_activities', 'ship_name_key')) {
                $table->dropIndex(['ship_name_key']);
                $table->dropColumn('ship_name_key');
            }
        });

        Schema::table('ship_operations', function (Blueprint $table): void {
            if (Schema::hasColumn('ship_operations', 'ship_name_key')) {
                $table->dropIndex(['type', 'ship_name_key']);
                $table->dropColumn('ship_name_key');
            }
        });
    }
};
