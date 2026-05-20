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
        Schema::create('inspections', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('asset_id');
            $table->enum('inspection_type', ['routine', 'periodic', 'special', 'emergency', 'preventive', 'compliance', 'safety', 'environmental', 'quality', 'operational', 'acceptance'])->default('routine');
            $table->string('title', 255);
            $table->text('description');
            $table->date('scheduled_date');
            $table->date('performed_date')->nullable();
            $table->uuid('inspector_id');
            $table->uuid('supervisor_id')->nullable();
            $table->enum('status', ['scheduled', 'in_progress', 'completed', 'cancelled', 'postponed', 'failed', 'passed'])->default('scheduled');
            $table->enum('priority', ['low', 'normal', 'high', 'urgent', 'critical'])->default('normal');
            $table->integer('duration_minutes')->nullable();
            $table->uuid('checklist_template_id')->nullable();
            $table->json('checklist_items')->nullable();
            $table->json('checklist_results')->nullable();
            $table->decimal('overall_score', 8, 4)->nullable();
            $table->decimal('max_score', 8, 4)->nullable();
            $table->decimal('passing_score', 8, 4)->nullable();
            $table->json('findings')->nullable();
            $table->json('recommendations')->nullable();
            $table->json('deficiencies')->nullable();
            $table->boolean('corrective_actions_required')->default(false);
            $table->date('next_inspection_date')->nullable();
            $table->boolean('follow_up_required')->default(false);
            $table->date('follow_up_date')->nullable();
            $table->json('compliance_status')->nullable();
            $table->json('risk_assessment')->nullable();
            $table->json('safety_concerns')->nullable();
            $table->json('environmental_concerns')->nullable();
            $table->text('notes')->nullable();
            $table->text('internal_notes')->nullable();
            $table->uuid('created_by');
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('asset_id')->references('id')->on('assets')->onDelete('cascade');
            $table->foreign('inspector_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('supervisor_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('checklist_template_id')->references('id')->on('checklist_templates')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('restrict');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');

            // Indexes
            $table->index('asset_id');
            $table->index('inspector_id');
            $table->index('supervisor_id');
            $table->index('scheduled_date');
            $table->index('performed_date');
            $table->index('status');
            $table->index('priority');
            $table->index('inspection_type');
            $table->index(['status', 'scheduled_date']);
            $table->index(['asset_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inspections');
    }
};
