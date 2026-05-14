<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Insurance Policies - For household mode
        Schema::create('insurance_policies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id')->index();
            $table->uuid('asset_id')->nullable()->index(); // Can be linked to specific asset
            $table->string('policy_number')->unique();
            $table->string('provider');
            $table->decimal('coverage_amount', 12, 2);
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('premium_amount', 10, 2);
            $table->enum('premium_frequency', ['monthly', 'quarterly', 'annual'])->default('annual');
            $table->text('coverage_details')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('email')->nullable();
            $table->text('claims_procedure')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->foreign('asset_id')->references('id')->on('assets')->nullOnDelete();
        });

        // Asset Loans - For household mode (loan to friends/family)
        Schema::create('asset_loans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id')->index();
            $table->uuid('asset_id')->index();
            $table->string('borrowed_by'); // Name of person borrowing
            $table->string('borrowed_by_contact')->nullable(); // Phone/Email
            $table->text('relationship')->nullable(); // Friend, Family, Colleague
            $table->date('loaned_at');
            $table->date('due_back_at')->nullable();
            $table->date('returned_at')->nullable();
            $table->text('condition_at_loan')->nullable();
            $table->text('condition_at_return')->nullable();
            $table->enum('status', ['active', 'returned', 'lost', 'damaged'])->default('active')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->foreign('asset_id')->references('id')->on('assets')->cascadeOnDelete();
        });

        // Maintenance Reminders & Service Records
        Schema::create('maintenance_schedules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id')->index();
            $table->uuid('asset_id')->index();
            $table->string('service_type'); // Oil change, Servicing, Inspection, etc
            $table->date('last_service_date')->nullable();
            $table->date('next_service_date');
            $table->unsignedInteger('service_interval_days')->nullable(); // Repeat every X days
            $table->string('service_provider')->nullable();
            $table->decimal('estimated_cost', 10, 2)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_reminder_sent')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->foreign('asset_id')->references('id')->on('assets')->cascadeOnDelete();
        });

        // Asset Photos & Receipts
        Schema::create('asset_documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id')->index();
            $table->uuid('asset_id')->index();
            $table->enum('document_type', ['receipt', 'warranty', 'photo', 'certificate', 'manual', 'other'])->index();
            $table->string('file_path');
            $table->string('file_name');
            $table->string('file_size');
            $table->string('mime_type')->nullable();
            $table->date('document_date')->nullable(); // Date on receipt/warranty
            $table->text('notes')->nullable();
            $table->uuid('uploaded_by')->index();
            $table->timestamps();

            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->foreign('asset_id')->references('id')->on('assets')->cascadeOnDelete();
            $table->foreign('uploaded_by')->references('id')->on('users')->restrictOnDelete();
        });

        // Warranty Tracking
        Schema::create('asset_warranties', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id')->index();
            $table->uuid('asset_id')->index();
            $table->string('warranty_type'); // Manufacturer, Extended, Accidental Damage
            $table->date('warranty_start_date');
            $table->date('warranty_end_date');
            $table->text('coverage_details')->nullable();
            $table->string('provider_name')->nullable();
            $table->string('provider_contact')->nullable();
            $table->string('claim_process_url')->nullable();
            $table->boolean('has_been_claimed')->default(false);
            $table->text('claim_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->foreign('asset_id')->references('id')->on('assets')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_warranties');
        Schema::dropIfExists('asset_documents');
        Schema::dropIfExists('maintenance_schedules');
        Schema::dropIfExists('asset_loans');
        Schema::dropIfExists('insurance_policies');
    }
};
