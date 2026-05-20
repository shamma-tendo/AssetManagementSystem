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
        Schema::create('sensor_alert_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('sensor_type_id');
            $table->enum('alert_type', ['threshold_high', 'threshold_low', 'anomaly', 'quality', 'offline', 'low_battery', 'poor_signal', 'calibration_due', 'maintenance_due', 'communication_error', 'sensor_error', 'data_gap', 'system_error']);
            $table->enum('severity', ['info', 'low', 'medium', 'high', 'critical']);
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->string('condition_template', 500)->nullable();
            $table->string('message_template', 500);
            $table->decimal('threshold_min', 10, 4)->nullable();
            $table->decimal('threshold_max', 10, 4)->nullable();
            $table->integer('duration_threshold')->nullable();
            $table->boolean('auto_escalate')->default(false);
            $table->json('escalation_rules')->nullable();
            $table->json('notification_channels')->nullable();
            $table->boolean('is_active')->default(true);
            $table->uuid('created_by');
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('sensor_type_id')->references('id')->on('sensor_types')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('restrict');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');

            // Indexes
            $table->index('sensor_type_id');
            $table->index('alert_type');
            $table->index('severity');
            $table->index('is_active');
            $table->index(['sensor_type_id', 'alert_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sensor_alert_templates');
    }
};
