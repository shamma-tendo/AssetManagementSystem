<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asset_requests', function (Blueprint $table) {
            $table->text('purpose')->nullable()->after('description');
            $table->string('use_location')->nullable()->after('purpose');
        });

        Schema::table('asset_assignments', function (Blueprint $table) {
            $table->uuid('asset_request_id')->nullable()->after('organization_id')->index();
            $table->foreign('asset_request_id')->references('id')->on('asset_requests')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('asset_assignments', function (Blueprint $table) {
            $table->dropForeign(['asset_request_id']);
            $table->dropColumn('asset_request_id');
        });

        Schema::table('asset_requests', function (Blueprint $table) {
            $table->dropColumn(['purpose', 'use_location']);
        });
    }
};
