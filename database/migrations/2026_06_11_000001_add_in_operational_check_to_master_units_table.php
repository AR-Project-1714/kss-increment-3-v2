<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Kategori baru: penanda apakah sebuah unit ikut tampil pada seksi "Cek Unit"
 * laporan operasional. Memisahkan logika "masuk laporan" dari kolom `type`
 * (sebelumnya cek unit memakai `type != Minibus`), sehingga unit seperti Avanza
 * (sarana jemputan) bisa dikecualikan tanpa mengubah tipenya.
 *
 * Masuk cek unit: Trailer, Tronton, Dump Truck, Wheel Loader, Pickup, Bus,
 * Excavator, Forklift. Dikecualikan: Minibus (Toyota Avanza).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('master_units', function (Blueprint $table): void {
            if (! Schema::hasColumn('master_units', 'in_operational_check')) {
                $table->boolean('in_operational_check')->default(false)->after('macro_category');
            }
        });

        // Backfill data lama berdasarkan tipe, agar DB yang sudah ada langsung
        // benar tanpa harus menjalankan ulang seeder.
        DB::table('master_units')
            ->whereIn('type', [
                'Trailer', 'Tronton', 'Dump Truck', 'Wheel Loader',
                'Pickup', 'Pick Up', 'Bus', 'Excavator', 'Forklift',
            ])
            ->update(['in_operational_check' => true]);
    }

    public function down(): void
    {
        Schema::table('master_units', function (Blueprint $table): void {
            if (Schema::hasColumn('master_units', 'in_operational_check')) {
                $table->dropColumn('in_operational_check');
            }
        });
    }
};
