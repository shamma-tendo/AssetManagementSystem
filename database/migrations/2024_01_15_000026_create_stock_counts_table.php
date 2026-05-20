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
        Schema::create('stock_counts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('part_stock_location_id');
            $table->decimal('old_quantity', 10, 4);
            $table->decimal('new_quantity', 10, 4);
            $table->decimal('variance', 10, 4);
            $table->decimal('variance_percentage', 8, 4)->nullable();
            $table->string('reason', 255);
            $table->uuid('counted_by');
            $table->timestamp('counted_at');
            $table->text('notes')->nullable();
            $table->string('batch_number', 100)->nullable();
            $table->date('expiry_date')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('part_stock_location_id')->references('id')->on('part_stock_locations')->onDelete('cascade');
            $table->foreign('counted_by')->references('id')->on('users')->onDelete('restrict');

            // Indexes
            $table->index('part_stock_location_id');
            $table->index('counted_by');
            $table->index('counted_at');
            $table->index(['part_stock_location_id', 'counted_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_counts');
    }
};
