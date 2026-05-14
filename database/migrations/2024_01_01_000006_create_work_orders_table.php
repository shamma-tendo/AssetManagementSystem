<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('work_order_number')->unique();
            $table->uuid('asset_id');
            $table->enum('type', ['Preventive', 'Corrective', 'Predictive'])->default('Corrective');
            $table->enum('status', ['Open', 'In Progress', 'On Hold', 'Completed', 'Cancelled'])->default('Open');
            $table->uuid('assigned_to')->nullable();
            $table->text('description')->nullable();
            $table->dateTime('scheduled_date')->nullable();
            $table->dateTime('started_date')->nullable();
            $table->dateTime('completed_date')->nullable();
            $table->decimal('estimated_labor_hours', 8, 2)->nullable();
            $table->decimal('actual_labor_hours', 8, 2)->nullable();
            $table->decimal('estimated_cost', 15, 2)->nullable();
            $table->decimal('actual_cost', 15, 2)->nullable();
            $table->uuid('created_by')->nullable();
            $table->timestamps();

            $table->foreign('asset_id')->references('id')->on('assets')->restrictOnDelete();
            $table->foreign('assigned_to')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();

            $table->index(['asset_id', 'status']);
            $table->index(['assigned_to']);
            $table->index(['status']);
            $table->index(['scheduled_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_orders');
    }
};
