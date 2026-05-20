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
        Schema::create('sensor_types', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 255);
            $table->text('description');
            $table->string('category', 100);
            $table->string('unit_of_measure', 50);
            $table->enum('data_type', ['temperature', 'humidity', 'pressure', 'voltage', 'current', 'power', 'energy', 'vibration', 'motion', 'light', 'sound', 'flow', 'level', 'position', 'speed', 'acceleration', 'rotation', 'torque', 'force', 'strain', 'ph', 'conductivity', 'turbidity', 'gas', 'radiation', 'magnetic', 'proximity', 'distance', 'angle', 'weight', 'mass', 'volume', 'density', 'concentration', 'boolean', 'counter', 'enum', 'text', 'binary', 'json']);
            $table->decimal('min_value', 10, 4)->nullable();
            $table->decimal('max_value', 10, 4)->nullable();
            $table->decimal('default_threshold_min', 10, 4)->nullable();
            $table->decimal('default_threshold_max', 10, 4)->nullable();
            $table->integer('sampling_frequency')->nullable();
            $table->string('communication_protocol', 50)->nullable();
            $table->string('power_requirements', 255)->nullable();
            $table->json('environmental_specs')->nullable();
            $table->decimal('accuracy', 5, 4)->nullable();
            $table->decimal('resolution', 10, 6)->nullable();
            $table->integer('response_time')->nullable();
            $table->boolean('is_active')->default(true);
            $table->uuid('created_by');
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('created_by')->references('id')->on('users')->onDelete('restrict');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');

            // Indexes
            $table->index('name');
            $table->index('category');
            $table->index('data_type');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sensor_types');
    }
};
