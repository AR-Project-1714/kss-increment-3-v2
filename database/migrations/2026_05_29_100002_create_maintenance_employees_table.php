<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Master roster personel pemeliharaan (Kasi/Karu/Mekanik/Helper/Driver/Checker).
 * Terpisah dari master_employees operasional (MD §1.3 poin 7).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_employees', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('position')->nullable();          // Kasi/Karu/Mekanik/Helper/...
            $table->string('work_time')->default('Non Shift');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_employees');
    }
};
