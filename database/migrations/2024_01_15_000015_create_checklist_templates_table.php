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
        Schema::create('checklist_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->uuid('category_id')->nullable();
            $table->enum('inspection_type', ['routine', 'periodic', 'special', 'emergency', 'preventive', 'compliance', 'safety', 'environmental', 'quality', 'operational', 'acceptance'])->default('routine');
            $table->integer('version')->default(1);
            $table->json('checklist_items');
            $table->decimal('passing_score_percentage', 5, 2)->default(70.00);
            $table->integer('estimated_duration_minutes')->nullable();
            $table->json('required_certifications')->nullable();
            $table->json('safety_requirements')->nullable();
            $table->json('equipment_required')->nullable();
            $table->boolean('is_active')->default(true);
            $table->uuid('created_by');
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('restrict');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');

            // Indexes
            $table->index('name');
            $table->index('category_id');
            $table->index('inspection_type');
            $table->index('is_active');
            $table->index(['category_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('checklist_templates');
    }
};
