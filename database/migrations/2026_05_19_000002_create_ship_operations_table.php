<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ship_operations', function (Blueprint $table) {
            $table->id();
            $table->string('type', 30);
            $table->string('status', 20)->default('active');
            $table->string('ship_name');
            $table->string('agent')->nullable();
            $table->string('jetty')->nullable();
            $table->string('destination')->nullable();
            $table->decimal('capacity', 15, 2)->nullable()->default(0);
            $table->string('wo_number')->nullable();
            $table->string('cargo_type')->nullable();
            $table->string('marking')->nullable();
            $table->string('stevedoring')->nullable();
            $table->string('commodity')->nullable();
            $table->dateTime('arrival_time')->nullable();
            $table->dateTime('berthing_time')->nullable();
            $table->dateTime('start_loading_time')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('last_report_id')->nullable()->constrained('daily_reports')->nullOnDelete();
            $table->date('last_report_date')->nullable();
            $table->timestamps();

            $table->index(['type', 'status']);
            $table->index(['ship_name', 'type']);
        });

        Schema::table('loading_activities', function (Blueprint $table) {
            if (! Schema::hasColumn('loading_activities', 'ship_operation_id')) {
                $table->foreignId('ship_operation_id')
                    ->nullable()
                    ->after('daily_report_id')
                    ->constrained('ship_operations')
                    ->nullOnDelete();
            }
        });

        Schema::table('bulk_loading_activities', function (Blueprint $table) {
            if (! Schema::hasColumn('bulk_loading_activities', 'ship_operation_id')) {
                $table->foreignId('ship_operation_id')
                    ->nullable()
                    ->after('daily_report_id')
                    ->constrained('ship_operations')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('bulk_loading_activities', function (Blueprint $table) {
            if (Schema::hasColumn('bulk_loading_activities', 'ship_operation_id')) {
                $table->dropConstrainedForeignId('ship_operation_id');
            }
        });

        Schema::table('loading_activities', function (Blueprint $table) {
            if (Schema::hasColumn('loading_activities', 'ship_operation_id')) {
                $table->dropConstrainedForeignId('ship_operation_id');
            }
        });

        Schema::dropIfExists('ship_operations');
    }
};
