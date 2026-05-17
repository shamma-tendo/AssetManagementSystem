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
        Schema::create('maintenance_history', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('maintenance_schedule_id');
            $table->uuid('work_order_id')->nullable();
            $table->uuid('asset_id');
            $table->uuid('performed_by');
            $table->date('performed_date');
            $table->decimal('actual_duration_hours', 8, 4)->nullable();
            $table->decimal('estimated_duration_hours', 8, 4)->nullable();
            $table->decimal('actual_cost', 15, 2)->nullable();
            $table->decimal('estimated_cost', 15, 2)->nullable();
            $table->enum('completion_status', ['completed', 'partially_completed', 'cancelled', 'rescheduled'])->default('completed');
            $table->text('notes')->nullable();
            $table->text('findings')->nullable();
            $table->text('recommendations')->nullable();
            $table->json('parts_used')->nullable();
            $table->json('tools_used')->nullable();
            $table->boolean('checklist_completed')->default(false);
            $table->json('checklist_items')->nullable();
            $table->date('next_due_date')->nullable();
            $table->boolean('completed_on_time')->default(true);
            $table->integer('performance_rating')->nullable()->min(1)->max(5);
            $table->json('issues_found')->nullable();
            $table->boolean('follow_up_required')->default(false);
            $table->date('follow_up_date')->nullable();
            $table->uuid('created_by');
            $table->timestamps();

            // Foreign keys
            $table->foreign('maintenance_schedule_id')->references('id')->on('maintenance_schedules')->onDelete('cascade');
            $table->foreign('work_order_id')->references('id')->on('work_orders')->onDelete('set null');
            $table->foreign('asset_id')->references('id')->on('assets')->onDelete('cascade');
            $table->foreign('performed_by')->references('id')->on('users')->onDelete('restrict');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('restrict');

            // Indexes
            $table->index('maintenance_schedule_id');
            $table->index('work_order_id');
            $table->index('asset_id');
            $table->index('performed_by');
            $table->index('performed_date');
            $table->index('completion_status');
            $table->index('completed_on_time');
            $table->index(['asset_id', 'performed_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_history');
    }
};
