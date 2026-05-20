<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asset_condition_reports', function (Blueprint $table) {
            $table->string('status')->default('pending')->after('review_notes');
            $table->string('asset_id')->nullable()->after('asset_assignment_id');
        });
    }

    public function down(): void
    {
        Schema::table('asset_condition_reports', function (Blueprint $table) {
            $table->dropColumn(['status', 'asset_id']);
        });
    }
};
