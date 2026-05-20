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
        Schema::create('work_orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title', 255);
            $table->text('description');
            $table->enum('priority', ['low', 'normal', 'high', 'urgent', 'emergency'])->default('normal');
            $table->enum('status', ['requested', 'approved', 'assigned', 'scheduled', 'in_progress', 'on_hold', 'completed', 'closed', 'cancelled'])->default('requested');
            $table->enum('type', ['preventive_maintenance', 'corrective_maintenance', 'emergency_maintenance', 'inspection', 'calibration', 'installation', 'removal', 'upgrade', 'repair', 'other'])->default('corrective_maintenance');
            
            // Relationships
            $table->uuid('asset_id');
            $table->uuid('assigned_to')->nullable();
            $table->uuid('created_by');
            $table->uuid('requested_by')->nullable();
            $table->uuid('location_id')->nullable();
            $table->uuid('department_id')->nullable();
            
            // Time and cost tracking
            $table->decimal('estimated_hours', 8, 2)->nullable();
            $table->decimal('actual_hours', 8, 2)->nullable();
            $table->decimal('estimated_cost', 15, 2)->nullable();
            $table->decimal('actual_cost', 15, 2)->nullable();
            
            // Dates
            $table->date('scheduled_date')->nullable();
            $table->datetime('started_at')->nullable();
            $table->datetime('completed_at')->nullable();
            $table->datetime('closed_at')->nullable();
            
            // Additional fields
            $table->text('notes')->nullable();
            $table->text('completion_notes')->nullable();
            $table->text('work_performed')->nullable();
            $table->json('parts_used')->nullable();
            $table->json('tools_used')->nullable();
            $table->text('safety_precautions')->nullable();
            
            // Follow-up
            $table->boolean('follow_up_required')->default(false);
            $table->date('follow_up_date')->nullable();
            $table->integer('customer_satisfaction')->nullable()->min(1)->max(5);
            
            // Internal tracking
            $table->text('internal_notes')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Foreign keys
            $table->foreign('asset_id')->references('id')->on('assets')->onDelete('restrict');
            $table->foreign('assigned_to')->references('id')->on('users')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('restrict');
            $table->foreign('requested_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('location_id')->references('id')->on('locations')->onDelete('set null');
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('set null');
            
            // Indexes
            $table->index('title');
            $table->index('status');
            $table->index('priority');
            $table->index('type');
            $table->index('scheduled_date');
            $table->index('assigned_to');
            $table->index('created_by');
            $table->index('asset_id');
            $table->index(['status', 'priority']);
            $table->index(['status', 'scheduled_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_orders');
    }
};
