<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('asset_id');
            $table->uuid('work_order_id')->nullable();
            $table->uuid('technician_id');
            $table->enum('type', ['Preventive', 'Corrective', 'Predictive', 'Emergency'])->default('Corrective');
            $table->text('description')->nullable();
            $table->text('findings')->nullable();
            $table->text('actions_taken')->nullable();
            $table->dateTime('maintenance_date');
            $table->decimal('labor_hours', 8, 2);
            $table->decimal('labor_cost', 15, 2)->nullable();
            $table->decimal('parts_cost', 15, 2)->default(0);
            $table->decimal('total_cost', 15, 2);
            $table->boolean('asset_operational')->default(true);
            $table->timestamps();

            $table->foreign('asset_id')->references('id')->on('assets')->restrictOnDelete();
            $table->foreign('work_order_id')->references('id')->on('work_orders')->nullOnDelete();
            $table->foreign('technician_id')->references('id')->on('users')->restrictOnDelete();

            $table->index(['asset_id', 'maintenance_date']);
            $table->index(['technician_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_records');
    }
};
