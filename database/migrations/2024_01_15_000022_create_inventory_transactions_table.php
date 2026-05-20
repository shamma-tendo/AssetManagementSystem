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
        Schema::create('inventory_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('part_id');
            $table->decimal('quantity', 10, 4);
            $table->enum('transaction_type', ['purchase', 'receive', 'issue', 'return', 'transfer', 'adjustment', 'reservation', 'release_reservation', 'damage', 'loss', 'expired', 'recall']);
            $table->string('reference', 255)->nullable();
            $table->string('reference_type', 50)->nullable();
            $table->enum('status', ['pending', 'completed', 'cancelled', 'failed', 'active', 'expired'])->default('completed');
            $table->decimal('unit_cost', 10, 4)->nullable();
            $table->decimal('total_cost', 15, 2)->nullable();
            $table->string('batch_number', 100)->nullable();
            $table->date('expiry_date')->nullable();
            $table->json('serial_numbers')->nullable();
            $table->string('location_from', 255)->nullable();
            $table->string('location_to', 255)->nullable();
            $table->uuid('performed_by');
            $table->timestamp('performed_at');
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('part_id')->references('id')->on('parts')->onDelete('cascade');
            $table->foreign('performed_by')->references('id')->on('users')->onDelete('set null');

            // Indexes
            $table->index('part_id');
            $table->index('transaction_type');
            $table->index('status');
            $table->index('reference');
            $table->index('performed_at');
            $table->index(['part_id', 'transaction_type']);
            $table->index(['part_id', 'performed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_transactions');
    }
};
