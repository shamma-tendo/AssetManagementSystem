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
        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('purchase_order_id');
            $table->uuid('part_id');
            $table->decimal('quantity', 10, 4);
            $table->decimal('unit_cost', 10, 4);
            $table->decimal('total_cost', 15, 2);
            $table->decimal('received_quantity', 10, 4)->default(0);
            $table->text('notes')->nullable();
            $table->json('specifications')->nullable();
            $table->date('expected_delivery_date')->nullable();
            $table->date('actual_delivery_date')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('purchase_order_id')->references('id')->on('purchase_orders')->onDelete('cascade');
            $table->foreign('part_id')->references('id')->on('parts')->onDelete('restrict');

            // Indexes
            $table->index('purchase_order_id');
            $table->index('part_id');
            $table->index(['purchase_order_id', 'part_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_order_items');
    }
};
