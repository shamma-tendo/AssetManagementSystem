<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_order_parts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('work_order_id');
            $table->uuid('spare_part_id');
            $table->integer('quantity_used');
            $table->decimal('unit_cost', 15, 2);
            $table->decimal('total_cost', 15, 2);
            $table->timestamps();

            $table->foreign('work_order_id')->references('id')->on('work_orders')->cascadeOnDelete();
            $table->foreign('spare_part_id')->references('id')->on('spare_parts')->restrictOnDelete();

            $table->index(['work_order_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_order_parts');
    }
};
