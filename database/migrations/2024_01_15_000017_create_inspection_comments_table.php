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
        Schema::create('inspection_comments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('inspection_id');
            $table->uuid('user_id');
            $table->text('comment');
            $table->enum('comment_type', ['general', 'finding', 'recommendation', 'correction', 'question', 'approval', 'rejection', 'note'])->default('general');
            $table->boolean('is_internal')->default(false);
            $table->boolean('is_private')->default(false);
            $table->json('attachment_references')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('inspection_id')->references('id')->on('inspections')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            // Indexes
            $table->index('inspection_id');
            $table->index('user_id');
            $table->index('comment_type');
            $table->index(['inspection_id', 'is_internal']);
            $table->index(['inspection_id', 'is_private']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inspection_comments');
    }
};
