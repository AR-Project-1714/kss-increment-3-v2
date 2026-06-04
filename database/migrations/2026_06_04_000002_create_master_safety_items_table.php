<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Master item yang diinspeksi (Bangunan, Lampu, AC, APAR, dst). is_countable
 * menandai apakah QTY relevan untuk item tersebut (lihat MD §2.1 & §6).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_safety_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_countable')->default(false); // true = QTY relevan
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_safety_items');
    }
};
