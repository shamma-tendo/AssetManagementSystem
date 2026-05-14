<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spare_parts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('part_number')->unique();
            $table->string('part_name');
            $table->text('description')->nullable();
            $table->string('supplier')->nullable();
            $table->decimal('unit_cost', 15, 2);
            $table->integer('stock_quantity')->default(0);
            $table->integer('reorder_point')->default(10);
            $table->integer('reorder_quantity')->default(20);
            $table->string('unit_of_measure')->default('pcs');
            $table->uuid('category_id')->nullable();
            $table->uuid('location_id')->nullable();
            $table->timestamps();

            $table->foreign('category_id')->references('id')->on('categories')->nullOnDelete();
            $table->foreign('location_id')->references('id')->on('locations')->nullOnDelete();

            $table->index(['stock_quantity']);
            $table->index(['reorder_point']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spare_parts');
    }
};
