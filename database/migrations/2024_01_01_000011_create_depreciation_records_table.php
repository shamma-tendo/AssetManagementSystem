<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('depreciation_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('asset_id');
            $table->integer('year');
            $table->enum('method', ['straight_line', 'declining_balance'])->default('straight_line');
            $table->decimal('beginning_book_value', 15, 2);
            $table->decimal('depreciation_expense', 15, 2);
            $table->decimal('book_value', 15, 2);
            $table->decimal('accumulated_depreciation', 15, 2);
            $table->timestamps();

            $table->foreign('asset_id')->references('id')->on('assets')->restrictOnDelete();

            $table->index(['asset_id', 'year']);
            $table->unique(['asset_id', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('depreciation_records');
    }
};
