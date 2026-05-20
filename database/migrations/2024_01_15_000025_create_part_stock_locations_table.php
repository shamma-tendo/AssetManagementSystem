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
        Schema::create('part_stock_locations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('part_id');
            $table->uuid('location_id');
            $table->string('bin_location', 100)->nullable();
            $table->decimal('quantity', 10, 4)->default(0);
            $table->decimal('minimum_quantity', 10, 4)->nullable();
            $table->decimal('maximum_quantity', 10, 4)->nullable();
            $table->timestamp('last_counted_at')->nullable();
            $table->uuid('last_counted_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('part_id')->references('id')->on('parts')->onDelete('cascade');
            $table->foreign('location_id')->references('id')->on('locations')->onDelete('cascade');
            $table->foreign('last_counted_by')->references('id')->on('users')->onDelete('set null');

            // Indexes
            $table->index('part_id');
            $table->index('location_id');
            $table->index(['part_id', 'location_id']);
            $table->index(['location_id', 'quantity']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('part_stock_locations');
    }
};
