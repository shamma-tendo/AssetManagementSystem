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
        Schema::create('parts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 255);
            $table->text('description');
            $table->string('part_number', 100)->unique();
            $table->string('manufacturer_part_number', 100)->nullable();
            $table->string('supplier_part_number', 100)->nullable();
            $table->uuid('category_id')->nullable();
            $table->uuid('manufacturer_id')->nullable();
            $table->uuid('supplier_id')->nullable();
            $table->string('unit_of_measure', 50);
            $table->decimal('current_stock', 10, 4)->default(0);
            $table->decimal('minimum_stock', 10, 4)->nullable();
            $table->decimal('maximum_stock', 10, 4)->nullable();
            $table->decimal('reorder_point', 10, 4)->nullable();
            $table->decimal('reorder_quantity', 10, 4)->nullable();
            $table->decimal('unit_cost', 10, 4)->nullable();
            $table->decimal('average_cost', 10, 4)->nullable();
            $table->decimal('selling_price', 10, 4)->nullable();
            $table->integer('lead_time_days')->nullable();
            $table->integer('shelf_life_days')->nullable();
            $table->string('storage_location', 255)->nullable();
            $table->string('bin_location', 100)->nullable();
            $table->string('warehouse_location', 255)->nullable();
            $table->string('barcode', 255)->nullable();
            $table->string('qr_code', 255)->nullable();
            $table->boolean('serial_number_required')->default(false);
            $table->boolean('batch_number_required')->default(false);
            $table->boolean('expiry_date_required')->default(false);
            $table->boolean('hazardous_material')->default(false);
            $table->string('safety_data_sheet_url', 500)->nullable();
            $table->json('specifications')->nullable();
            $table->json('dimensions')->nullable();
            $table->decimal('weight_kg', 8, 4)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->uuid('created_by');
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('category_id')->references('id')->on('part_categories')->onDelete('set null');
            $table->foreign('manufacturer_id')->references('id')->on('suppliers')->onDelete('set null');
            $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('restrict');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');

            // Indexes
            $table->index('name');
            $table->index('part_number');
            $table->index('manufacturer_part_number');
            $table->index('supplier_part_number');
            $table->index('category_id');
            $table->index('manufacturer_id');
            $table->index('supplier_id');
            $table->index('is_active');
            $table->index(['current_stock', 'minimum_stock']); // For low stock queries
            $table->index(['current_stock', 'reorder_point']); // For reorder queries
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parts');
    }
};
