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
        Schema::create('depreciation_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('asset_depreciation_id');
            $table->date('period_date');
            $table->decimal('depreciation_amount', 15, 2);
            $table->decimal('book_value_before', 15, 2);
            $table->decimal('book_value_after', 15, 2);
            $table->decimal('accumulated_depreciation_before', 15, 2);
            $table->decimal('accumulated_depreciation_after', 15, 2);
            $table->text('description')->nullable();
            $table->uuid('created_by');
            $table->timestamps();

            // Foreign keys
            $table->foreign('asset_depreciation_id')->references('id')->on('asset_depreciations')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('restrict');

            // Indexes
            $table->index('asset_depreciation_id');
            $table->index('period_date');
            $table->index(['asset_depreciation_id', 'period_date']);
            $table->unique(['asset_depreciation_id', 'period_date'], 'unique_depreciation_period');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('depreciation_entries');
    }
};
