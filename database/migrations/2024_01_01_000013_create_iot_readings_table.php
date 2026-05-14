<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('iot_readings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('asset_id');
            $table->string('sensor_id');
            $table->string('sensor_type');
            $table->string('metric_name');
            $table->decimal('metric_value', 15, 4);
            $table->string('unit')->nullable();
            $table->dateTime('reading_timestamp');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('asset_id')->references('id')->on('assets')->cascadeOnDelete();

            $table->index(['asset_id', 'reading_timestamp']);
            $table->index(['sensor_id', 'reading_timestamp']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('iot_readings');
    }
};
