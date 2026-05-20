<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sensor_alerts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('sensor_id');
            $table->enum('alert_type', ['threshold_high', 'threshold_low', 'anomaly', 'quality', 'offline', 'low_battery', 'poor_signal', 'calibration_due', 'maintenance_due', 'communication_error', 'sensor_error', 'data_gap', 'system_error']);
            $table->enum('severity', ['info', 'low', 'medium', 'high', 'critical']);
            $table->string('message', 500);
            $table->text('description')->nullable();
            $table->decimal('trigger_value', 15, 6)->nullable();
            $table->decimal('threshold_value', 15, 6)->nullable();
            $table->timestamp('triggered_at');
            $table->timestamp('acknowledged_at')->nullable();
            $table->uuid('acknowledged_by')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->uuid('resolved_by')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('auto_resolved')->default(false);
            $table->integer('escalation_level')->default(0);
            $table->boolean('notification_sent')->default(false);

            // Foreign keys
            $table->foreign('sensor_id')->references('id')->on('sensors')->onDelete('cascade');
            $table->foreign('acknowledged_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('resolved_by')->references('id')->on('users')->onDelete('set null');

            // Indexes
            $table->index('sensor_id');
            $table->index('alert_type');
            $table->index('severity');
            $table->index('triggered_at');
            $table->index('acknowledged_at');
            $table->index('resolved_at');
            $table->index(['sensor_id', 'alert_type']);
            $table->index(['sensor_id', 'severity']);
            $table->index(['sensor_id', 'triggered_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sensor_alerts');
    }
};
