<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Master "Data Lingkungan Operasi": daftar item pemeriksaan Lingkungan Shelter
 * (mis. Ruangan Shelter, Jala-Jala Angkat) yang dikelola admin dan dipakai
 * sebagai acuan pada form Cek Unit tab Lingkungan Shelter serta PDF laporan.
 * `category` mengelompokkan item (mis. Kebersihan / Kerapian).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_environment_items', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('category')->default('Umum');
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_environment_items');
    }
};
