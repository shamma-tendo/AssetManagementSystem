<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inspections', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('asset_id');
            $table->string('inspection_type');
            $table->string('compliance_standard')->nullable();
            $table->enum('status', ['Scheduled', 'In Progress', 'Completed', 'Failed', 'Passed'])->default('Scheduled');
            $table->dateTime('scheduled_date');
            $table->dateTime('completed_date')->nullable();
            $table->dateTime('next_due_date')->nullable();
            $table->uuid('inspector_id');
            $table->text('findings')->nullable();
            $table->text('corrective_actions')->nullable();
            $table->boolean('compliance_met')->nullable();
            $table->string('certification_number')->nullable();
            $table->date('certification_expiry')->nullable();
            $table->timestamps();

            $table->foreign('asset_id')->references('id')->on('assets')->restrictOnDelete();
            $table->foreign('inspector_id')->references('id')->on('users')->restrictOnDelete();

            $table->index(['asset_id']);
            $table->index(['status']);
            $table->index(['scheduled_date']);
            $table->index(['next_due_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inspections');
    }
};
