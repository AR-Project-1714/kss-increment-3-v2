<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rekaman harian metrik sistem untuk dashboard admin.
 *
 * Storage terpakai dan jumlah pengguna hanya bisa dibaca sebagai keadaan saat
 * ini — tidak ada jejak historisnya di tabel mana pun. Tanpa rekaman ini,
 * kartu dashboard tidak punya dasar untuk menampilkan perubahan terhadap
 * periode sebelumnya. Satu baris per tanggal sudah cukup: dashboard hanya
 * membandingkan hari ini dengan kemarin dan dengan awal bulan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_metric_snapshots', function (Blueprint $table) {
            $table->id();
            $table->date('captured_on')->unique();

            $table->unsignedBigInteger('storage_used_bytes')->default(0);
            $table->unsignedInteger('active_users')->default(0);
            $table->unsignedInteger('total_users')->default(0);
            $table->unsignedInteger('security_events')->default(0);
            $table->unsignedInteger('activity_events')->default(0);
            $table->unsignedInteger('reports_created')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_metric_snapshots');
    }
};
