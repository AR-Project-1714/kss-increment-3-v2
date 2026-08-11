<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ship_operation_decisions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ship_operation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('daily_report_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20);
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();

            $table->unique(['ship_operation_id', 'daily_report_id'], 'ship_operation_report_decision_unique');
            $table->index(['daily_report_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ship_operation_decisions');
    }
};
