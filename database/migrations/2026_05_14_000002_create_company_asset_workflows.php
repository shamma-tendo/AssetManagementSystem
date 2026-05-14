<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Asset Requests - Manager requests assets from CEO/CFO
        Schema::create('asset_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id')->index();
            $table->uuid('requested_by')->index(); // Asset Manager
            $table->uuid('approved_by')->nullable()->index(); // CEO/CFO
            $table->string('title');
            $table->text('description');
            $table->unsignedInteger('quantity');
            $table->string('asset_type'); // laptop, printer, etc
            $table->decimal('estimated_cost', 12, 2)->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'fulfilled'])->default('pending')->index();
            $table->text('approval_notes')->nullable();
            $table->timestamp('requested_at')->useCurrent();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('fulfilled_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->foreign('requested_by')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
        });

        // Asset Assignments - Track distribution of assets to staff
        Schema::create('asset_assignments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('asset_id')->index();
            $table->uuid('organization_id')->index();
            $table->uuid('assigned_to')->nullable()->index(); // Staff member
            $table->uuid('assigned_by')->index(); // Asset Manager
            $table->unsignedInteger('quantity')->default(1);
            $table->enum('status', ['assigned', 'in_use', 'returned', 'lost', 'damaged'])->default('assigned')->index();
            $table->text('assignment_notes')->nullable();
            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamp('received_at')->nullable(); // When staff confirmed receipt
            $table->timestamp('returned_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('asset_id')->references('id')->on('assets')->cascadeOnDelete();
            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->foreign('assigned_to')->references('id')->on('users')->nullOnDelete();
            $table->foreign('assigned_by')->references('id')->on('users')->restrictOnDelete();
        });

        // Asset Condition Reports - Staff report status of their assets
        Schema::create('asset_condition_reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('asset_assignment_id')->index();
            $table->uuid('organization_id')->index();
            $table->uuid('reported_by')->index(); // Staff member
            $table->enum('condition', ['in_use', 'broken', 'needs_repair', 'stolen', 'lost', 'not_effective', 'ready_for_return'])->index();
            $table->text('description')->nullable();
            $table->text('action_required')->nullable();
            $table->boolean('requires_urgent_attention')->default(false);
            $table->timestamp('reported_at')->useCurrent();
            $table->uuid('reviewed_by')->nullable()->index(); // Asset Manager or CEO/CFO
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamps();

            $table->foreign('asset_assignment_id')->references('id')->on('asset_assignments')->cascadeOnDelete();
            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->foreign('reported_by')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('reviewed_by')->references('id')->on('users')->nullOnDelete();
        });

        // Asset Inventory Snapshots - Track what manager has distributed
        Schema::create('inventory_snapshots', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id')->index();
            $table->uuid('asset_id')->index();
            $table->unsignedInteger('total_received')->default(0);
            $table->unsignedInteger('in_use')->default(0);
            $table->unsignedInteger('unused')->default(0);
            $table->unsignedInteger('damaged')->default(0);
            $table->unsignedInteger('stolen')->default(0);
            $table->unsignedInteger('pending_return')->default(0);
            $table->timestamp('snapshot_date')->useCurrent();
            $table->timestamps();

            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->foreign('asset_id')->references('id')->on('assets')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_snapshots');
        Schema::dropIfExists('asset_condition_reports');
        Schema::dropIfExists('asset_assignments');
        Schema::dropIfExists('asset_requests');
    }
};
