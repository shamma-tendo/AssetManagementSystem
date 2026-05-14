<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Alerts & Notifications System
        Schema::create('alerts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id')->index();
            $table->uuid('asset_id')->nullable()->index();
            $table->enum('alert_type', ['maintenance_due', 'asset_overdue', 'high_value_item_moved', 'asset_damaged', 'asset_stolen', 'warranty_expiring', 'insurance_expiring', 'depreciation_threshold', 'loan_overdue'])->index();
            $table->string('title');
            $table->text('message');
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('medium')->index();
            $table->boolean('is_resolved')->default(false)->index();
            $table->text('resolution_notes')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->foreign('asset_id')->references('id')->on('assets')->nullOnDelete();
        });

        // User Alert Preferences
        Schema::create('alert_preferences', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->unique()->index();
            $table->uuid('organization_id')->index();
            $table->boolean('email_alerts')->default(true);
            $table->boolean('push_notifications')->default(true);
            $table->boolean('maintenance_alerts')->default(true);
            $table->boolean('asset_overdue_alerts')->default(true);
            $table->boolean('high_value_alerts')->default(true);
            $table->boolean('damage_alerts')->default(true);
            $table->boolean('daily_digest')->default(false);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
        });

        // Health Metrics & KPIs Dashboard
        Schema::create('asset_metrics', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id')->index();
            $table->date('metric_date')->index();
            
            // Utilization Metrics
            $table->unsignedInteger('total_assets')->default(0);
            $table->unsignedInteger('assets_in_use')->default(0);
            $table->decimal('utilization_rate', 5, 2)->nullable(); // Percentage
            $table->unsignedInteger('unused_assets')->default(0);
            
            // Loss & Damage Metrics
            $table->unsignedInteger('damaged_assets')->default(0);
            $table->unsignedInteger('stolen_assets')->default(0);
            $table->decimal('loss_rate', 5, 2)->nullable(); // Percentage
            $table->decimal('total_loss_value', 12, 2)->nullable();
            
            // Financial Metrics
            $table->decimal('total_asset_value', 15, 2)->nullable();
            $table->decimal('total_depreciation_value', 15, 2)->nullable();
            $table->decimal('net_asset_value', 15, 2)->nullable();
            $table->decimal('replacement_cost', 15, 2)->nullable();
            
            // ROI & Cost Metrics
            $table->decimal('maintenance_cost_ytd', 12, 2)->nullable();
            $table->decimal('depreciation_cost_ytd', 12, 2)->nullable();
            $table->decimal('cost_per_asset', 10, 2)->nullable();
            
            // Condition Metrics
            $table->unsignedInteger('assets_needing_repair')->default(0);
            $table->unsignedInteger('overdue_maintenance')->default(0);
            
            $table->timestamps();
            $table->unique(['organization_id', 'metric_date']);

            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
        });

        // Barcode/QR Code Scanning Log
        Schema::create('asset_scans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id')->index();
            $table->uuid('asset_id')->index();
            $table->uuid('scanned_by')->nullable()->index();
            $table->string('barcode_value')->index();
            $table->enum('scan_type', ['checkin', 'checkout', 'verification', 'inventory_count', 'assignment'])->index();
            $table->string('location')->nullable();
            $table->string('device_info')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->foreign('asset_id')->references('id')->on('assets')->cascadeOnDelete();
            $table->foreign('scanned_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_scans');
        Schema::dropIfExists('asset_metrics');
        Schema::dropIfExists('alert_preferences');
        Schema::dropIfExists('alerts');
    }
};
