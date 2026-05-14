<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('serial_number')->unique();
            $table->string('model')->nullable();
            $table->string('manufacturer')->nullable();
            $table->uuid('category_id');
            $table->uuid('location_id')->nullable();
            $table->uuid('department_id')->nullable();
            $table->date('purchase_date');
            $table->decimal('purchase_cost', 15, 2);
            $table->decimal('current_value', 15, 2);
            $table->decimal('salvage_value', 15, 2)->default(0);
            $table->integer('useful_life_years')->default(5);
            $table->enum('status', ['Ordered', 'Received', 'Active', 'Under Maintenance', 'Retired', 'Disposed'])->default('Ordered');
            $table->text('description')->nullable();
            $table->string('barcode')->unique()->nullable();
            $table->string('qr_code')->unique()->nullable();
            $table->string('rfid_tag')->unique()->nullable();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('category_id')->references('id')->on('categories')->restrictOnDelete();
            $table->foreign('location_id')->references('id')->on('locations')->nullOnDelete();
            $table->foreign('department_id')->references('id')->on('departments')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();

            $table->index(['category_id', 'status']);
            $table->index(['location_id']);
            $table->index(['department_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
