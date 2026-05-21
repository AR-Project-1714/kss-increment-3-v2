<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('report_date')->nullable();
            $table->string('shift')->nullable();
            $table->string('group_name')->nullable();
            $table->string('received_by_group')->nullable();
            $table->foreignId('received_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('received_at')->nullable();
            $table->string('time_range')->nullable();
            $table->string('status')->default('draft');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['status', 'report_date']);
            $table->index(['group_name', 'received_by_group']);
        });

        Schema::create('loading_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daily_report_id')->constrained()->cascadeOnDelete();
            $table->integer('sequence')->default(1);
            $table->string('ship_name')->nullable();
            $table->string('agent')->nullable();
            $table->string('jetty')->nullable();
            $table->string('destination')->nullable();
            $table->decimal('capacity', 15, 2)->nullable()->default(0);
            $table->string('wo_number')->nullable();
            $table->string('cargo_type')->nullable();
            $table->string('marking')->nullable();
            $table->dateTime('arrival_time')->nullable();
            $table->string('operating_gang')->nullable();
            $table->integer('tkbm_count')->default(0);
            $table->string('foreman')->nullable();
            $table->decimal('qty_delivery_current', 15, 2)->default(0);
            $table->decimal('qty_delivery_prev', 15, 2)->default(0);
            $table->decimal('qty_loading_current', 15, 2)->default(0);
            $table->decimal('qty_loading_prev', 15, 2)->default(0);
            $table->decimal('qty_damage_current', 15, 2)->default(0);
            $table->decimal('qty_damage_prev', 15, 2)->default(0);
            $table->string('tally_warehouse')->nullable();
            $table->string('driver_name')->nullable();
            $table->string('truck_number')->nullable();
            $table->string('tally_ship')->nullable();
            $table->string('operator_ship')->nullable();
            $table->string('forklift_ship')->nullable();
            $table->string('operator_warehouse')->nullable();
            $table->string('forklift_warehouse')->nullable();
            $table->timestamps();
        });

        Schema::create('loading_timesheets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loading_activity_id')->constrained()->cascadeOnDelete();
            $table->string('category');
            $table->time('time')->nullable();
            $table->string('activity')->nullable();
            $table->timestamps();
        });

        Schema::create('bulk_loading_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daily_report_id')->constrained()->cascadeOnDelete();
            $table->integer('sequence')->default(1);
            $table->string('ship_name')->nullable();
            $table->string('jetty')->nullable();
            $table->string('destination')->nullable();
            $table->string('agent')->nullable();
            $table->string('stevedoring')->nullable();
            $table->string('commodity')->nullable();
            $table->decimal('capacity', 15, 2)->nullable()->default(0);
            $table->dateTime('berthing_time')->nullable();
            $table->dateTime('start_loading_time')->nullable();
            $table->timestamps();
        });

        Schema::create('bulk_loading_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bulk_loading_activity_id')->constrained()->cascadeOnDelete();
            $table->dateTime('datetime')->nullable();
            $table->string('activity')->nullable();
            $table->integer('cob')->nullable();
            $table->timestamps();
        });

        Schema::create('material_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daily_report_id')->constrained()->cascadeOnDelete();
            $table->string('ship_name')->nullable();
            $table->string('agent')->nullable();
            $table->decimal('capacity', 15, 2)->nullable()->default(0);
            $table->string('ship_tally_names')->nullable();
            $table->string('forklift_operator_names')->nullable();
            $table->string('delivery_tally_names')->nullable();
            $table->string('driver_names')->nullable();
            $table->string('working_hours')->nullable();
            $table->timestamps();
        });

        Schema::create('material_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_activity_id')->constrained('material_activities')->cascadeOnDelete();
            $table->string('raw_material_type')->nullable();
            $table->decimal('qty_current', 15, 2)->default(0);
            $table->decimal('qty_prev', 15, 2)->default(0);
            $table->decimal('qty_total', 15, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('container_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daily_report_id')->constrained()->cascadeOnDelete();
            $table->string('ship_name')->nullable();
            $table->string('agent')->nullable();
            $table->decimal('capacity', 15, 2)->nullable()->default(0);
            $table->string('ship_tally_names')->nullable();
            $table->string('gudang_tally_names')->nullable();
            $table->string('driver_names')->nullable();
            $table->timestamps();
        });

        Schema::create('container_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('container_activity_id')->constrained('container_activities')->cascadeOnDelete();
            $table->time('time')->nullable();
            $table->decimal('qty_current', 15, 2)->default(0);
            $table->decimal('qty_prev', 15, 2)->default(0);
            $table->decimal('qty_total', 15, 2)->default(0);
            $table->string('status')->nullable();
            $table->timestamps();
        });

        Schema::create('turba_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daily_report_id')->constrained()->cascadeOnDelete();
            $table->string('tally_gudang_names')->nullable();
            $table->string('forklift_operator_names')->nullable();
            $table->string('driver_names')->nullable();
            $table->string('working_hours')->nullable();
            $table->timestamps();
        });

        Schema::create('turba_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('turba_activity_id')->constrained()->cascadeOnDelete();
            $table->string('truck_name')->nullable();
            $table->string('do_so_number')->nullable();
            $table->decimal('capacity', 15, 2)->default(0);
            $table->string('marking_type')->nullable();
            $table->decimal('qty_current', 15, 2)->default(0);
            $table->decimal('qty_prev', 15, 2)->default(0);
            $table->decimal('qty_accumulated', 15, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('unit_check_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daily_report_id')->constrained()->cascadeOnDelete();
            $table->string('category');
            $table->string('item_name')->nullable();
            $table->string('master_id')->nullable();
            $table->string('fuel_level')->nullable();
            $table->string('condition_received')->nullable();
            $table->string('condition_handed_over')->nullable();
            $table->integer('quantity')->default(1);
            $table->timestamps();
        });

        Schema::create('employee_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daily_report_id')->constrained()->cascadeOnDelete();
            $table->string('category');
            $table->string('name')->nullable();
            $table->string('no_forklift_')->nullable();
            $table->string('work_area')->nullable();
            $table->string('personil_count')->nullable();
            $table->time('time_in')->nullable();
            $table->time('time_out')->nullable();
            $table->string('work_time')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('master_units', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('master_inventory_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('stock')->default(0);
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('master_trucks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('plate_number')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('master_employees', function (Blueprint $table) {
            $table->id();
            $table->string('npk')->unique();
            $table->string('name');
            $table->string('group_name')->nullable();
            $table->string('position')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_employees');
        Schema::dropIfExists('master_trucks');
        Schema::dropIfExists('master_inventory_items');
        Schema::dropIfExists('master_units');
        Schema::dropIfExists('employee_logs');
        Schema::dropIfExists('unit_check_logs');
        Schema::dropIfExists('turba_deliveries');
        Schema::dropIfExists('turba_activities');
        Schema::dropIfExists('container_items');
        Schema::dropIfExists('container_activities');
        Schema::dropIfExists('material_items');
        Schema::dropIfExists('material_activities');
        Schema::dropIfExists('bulk_loading_logs');
        Schema::dropIfExists('bulk_loading_activities');
        Schema::dropIfExists('loading_timesheets');
        Schema::dropIfExists('loading_activities');
        Schema::dropIfExists('daily_reports');
    }
};
