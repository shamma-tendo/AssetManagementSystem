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
        Schema::create('sensor_calibrations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('sensor_id');
            $table->date('calibration_date');
            $table->uuid('performed_by');
            $table->enum('calibration_type', ['routine', 'initial', 'repair', 'verification', 'field', 'laboratory', 'certification']);
            $table->decimal('reference_value', 15, 6)->nullable();
            $table->decimal('measured_value', 15, 6)->nullable();
            $table->decimal('correction_factor', 10, 6)->nullable();
            $table->decimal('offset', 15, 6)->nullable();
            $table->decimal('linearity_error', 8, 4)->nullable();
            $table->decimal('hysteresis_error', 8, 4)->nullable();
            $table->decimal('repeatability_error', 8, 4)->nullable();
            $table->decimal('temperature_coefficient', 10, 8)->nullable();
            $table->decimal('humidity_coefficient', 10, 8)->nullable();
            $table->string('calibration_certificate', 500)->nullable();
            $table->string('equipment_used', 500)->nullable();
            $table->json('environment_conditions')->nullable();
            $table->text('notes')->nullable();
            $table->date('next_calibration_date')->nullable();
            $table->enum('calibration_status', ['pending', 'approved', 'failed', 'expired'])->default('pending');
            $table->uuid('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();

            // Foreign keys
            $table->foreign('sensor_id')->references('id')->on('sensors')->onDelete('cascade');
            $table->foreign('performed_by')->references('id')->on('users')->onDelete('restrict');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');

            // Indexes
            $table->index('sensor_id');
            $table->index('calibration_date');
            $table->index('calibration_type');
            $table->index('calibration_status');
            $table->index('next_calibration_date');
            $table->index(['sensor_id', 'calibration_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sensor_calibrations');
    }
};
