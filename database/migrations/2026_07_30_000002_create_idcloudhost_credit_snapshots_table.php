<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL tidak membungkus DDL dalam transaksi. Bersihkan artefak kosong
        // bila percobaan migration sebelumnya berhenti saat membuat indeks.
        if (Schema::hasTable('idcloudhost_credit_snapshots')) {
            Schema::drop('idcloudhost_credit_snapshots');
        }

        Schema::create('idcloudhost_credit_snapshots', function (Blueprint $table) {
            $table->id();
            $table->string('billing_account_id', 64)->index();
            $table->decimal('credit_available', 20, 4);
            $table->decimal('estimated_daily_cost', 20, 4)->nullable();
            $table->string('estimate_source', 32)->nullable();
            $table->string('account_title')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('captured_at')->index();
            $table->timestamps();

            $table->index(['billing_account_id', 'captured_at'], 'idch_credit_account_captured_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('idcloudhost_credit_snapshots');
    }
};
