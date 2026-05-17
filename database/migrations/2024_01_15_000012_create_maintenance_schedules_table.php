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
        Schema::create('maintenance_schedules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('asset_id');
            $table->string('title', 255);
            $table->text('description');
            $table->enum('maintenance_type', ['preventive', 'predictive', 'corrective', 'condition_based', 'emergency', 'routine', 'inspection', 'calibration', 'lubrication', 'cleaning', 'testing'])->default('preventive');
            $table->enum('frequency_type', ['daily', 'weekly', 'monthly', 'yearly', 'hourly', 'custom'])->default('monthly');
            $table->integer('frequency_interval')->default(1);
            $table->integer('frequency_months')->nullable();
            $table->integer('frequency_days')->nullable();
            $table->integer('frequency_hours')->nullable();
            
            // Date tracking
            $table->date('last_performed_date')->nullable();
            $table->date('next_due_date');
            $table->date('due_date_based_on')->nullable();
            
            // Work order generation
            $table->boolean('auto_create_work_order')->default(false);
            $table->enum('work_order_priority', ['low', 'normal', 'high', 'urgent', 'emergency'])->default('normal');
            $table->uuid('assigned_technician_id')->nullable();
            
            // Estimations
            $table->decimal('estimated_duration_hours', 8, 4)->nullable();
            $table->decimal('estimated_cost', 15, 2)->nullable();
            
            // Requirements
            $table->json('required_parts')->nullable();
            $table->json('required_tools')->nullable();
            $table->json('safety_requirements')->nullable();
            $table->json('checklist_items')->nullable();
            
            // Status
            $table->boolean('is_active')->default(true);
            $table->uuid('created_by');
            $table->uuid('updated_by')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Foreign keys
            $table->foreign('asset_id')->references('id')->on('assets')->onDelete('cascade');
            $table->foreign('assigned_technician_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('restrict');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            
            // Indexes
            $table->index('asset_id');
            $table->index('next_due_date');
            $table->index('last_performed_date');
            $table->index('is_active');
            $table->index('auto_create_work_order');
            $table->index(['asset_id', 'is_active']);
            $table->index(['next_due_date', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_schedules');
    }
};
