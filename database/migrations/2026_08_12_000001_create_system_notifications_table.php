<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('event_key');
            $table->string('category', 40)->default('system');
            $table->string('severity', 20)->default('info');
            $table->string('title');
            $table->text('message');
            $table->text('action_url')->nullable();
            $table->string('action_label', 80)->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->unique(['user_id', 'event_key']);
            $table->index(['user_id', 'resolved_at', 'expires_at'], 'system_notifications_active_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_notifications');
    }
};
