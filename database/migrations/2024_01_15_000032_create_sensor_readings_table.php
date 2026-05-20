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
        Schema::create('sensor_readings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('sensor_id');
            $table->timestamp('timestamp');
            $table->decimal('value', 15, 6);
            $table->string('unit', 50)->nullable();
            $table->decimal('quality', 5, 4)->default(1.0000);
            $table->json('raw_data')->nullable();
            $table->json('processed_data')->nullable();
            $table->json('metadata')->nullable();
            $table->integer('battery_level')->nullable();
            $table->integer('signal_strength')->nullable();
            $table->decimal('temperature', 5, 2)->nullable();
            $table->decimal('humidity', 5, 2)->nullable();
            $table->integer('error_code')->nullable();
            $table->json('status_flags')->nullable();

            // Foreign key
            $table->foreign('sensor_id')->references('id')->on('sensors')->onDelete('cascade');

            // Indexes
            $table->index('sensor_id');
            $table->index('timestamp');
            $table->index(['sensor_id', 'timestamp']);
            $table->index('quality');
            $table->index('error_code');
            
            // Composite indexes for common queries
            $table->index(['sensor_id', 'timestamp', 'quality']);
            $table->index(['sensor_id', 'timestamp', 'error_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sensor_readings');
    }
};
