<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Master armada unit pemeliharaan. Sengaja dibuat terpisah dari master
 * operasional (master_units) sesuai keputusan perancangan MD §1.3 poin 7.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_units', function (Blueprint $table) {
            $table->id();
            $table->string('unit_code');                 // Jenis: TRL, TRT, DT, FL, EXC, WL
            $table->string('brand')->nullable();         // Merek: UD, YALE, TOYOTA, HINO
            $table->string('unit_number');               // Nomor unit
            $table->string('name')->nullable();          // Nama tampil opsional
            $table->enum('macro_category', ['truck', 'heavy']); // kelompok Kondisi Unit
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['unit_code', 'unit_number']);
            $table->index(['macro_category', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_units');
    }
};
