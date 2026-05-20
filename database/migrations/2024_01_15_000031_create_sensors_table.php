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
        Schema::create('sensors', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('asset_id');
            $table->uuid('sensor_type_id');
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->string('manufacturer', 255)->nullable();
            $table->string('model', 255)->nullable();
            $table->string('serial_number', 255)->nullable();
            $table->string('firmware_version', 50)->nullable();
            $table->string('hardware_version', 50)->nullable();
            $table->string('mac_address', 17)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('location_description', 500)->nullable();
            $table->date('installation_date')->nullable();
            $table->date('calibration_date')->nullable();
            $table->date('next_calibration_date')->nullable();
            $table->integer('battery_level')->nullable();
            $table->integer('signal_strength')->nullable();
            $table->enum('status', ['active', 'inactive', 'maintenance', 'error', 'calibrating', 'offline'])->default('active');
            $table->json('configuration')->nullable();
            $table->decimal('threshold_min', 10, 4)->nullable();
            $table->decimal('threshold_max', 10, 4)->nullable();
            $table->boolean('alert_enabled')->default(true);
            $table->integer('data_retention_days')->default(90);
            $table->integer('sampling_interval')->nullable();
            $table->timestamp('last_data_received')->nullable();
            $table->timestamp('last_heartbeat')->nullable();
            $table->text('notes')->nullable();
            $table->uuid('created_by');
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('asset_id')->references('id')->on('assets')->onDelete('cascade');
            $table->foreign('sensor_type_id')->references('id')->on('sensor_types')->onDelete('restrict');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('restrict');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');

            // Indexes
            $table->index('asset_id');
            $table->index('sensor_type_id');
            $table->index('name');
            $table->index('status');
            $table->index('mac_address');
            $table->index('ip_address');
            $table->index('last_heartbeat');
            $table->index('next_calibration_date');
            $table->index(['asset_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sensors');
    }
};
