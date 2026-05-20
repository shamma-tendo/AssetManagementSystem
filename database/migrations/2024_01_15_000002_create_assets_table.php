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
        Schema::create('assets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 255);
            $table->string('serial_number', 100)->unique();
            $table->uuid('category_id');
            $table->uuid('location_id')->nullable();
            $table->uuid('department_id')->nullable();
            $table->date('purchase_date');
            $table->decimal('purchase_cost', 15, 2);
            $table->decimal('current_value', 15, 2)->nullable();
            $table->decimal('salvage_value', 15, 2)->nullable()->default(0);
            $table->integer('useful_life_years')->default(5);
            $table->enum('depreciation_method', ['straight_line', 'declining_balance'])->default('straight_line');
            $table->enum('status', ['ordered', 'received', 'active', 'under_maintenance', 'retired', 'disposed'])->default('ordered');
            $table->text('description')->nullable();
            $table->string('manufacturer', 255)->nullable();
            $table->string('model', 255)->nullable();
            $table->date('warranty_expiry')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();

            // Foreign keys will be added later after referenced tables are created
            // $table->foreign('category_id')->references('id')->on('categories')->onDelete('restrict');
            // $table->foreign('location_id')->references('id')->on('locations')->onDelete('set null');
            // $table->foreign('department_id')->references('id')->on('departments')->onDelete('set null');
            // $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            // $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');

            // Indexes
            $table->index('name');
            $table->index('serial_number');
            $table->index('status');
            $table->index('category_id');
            $table->index('location_id');
            $table->index('purchase_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
