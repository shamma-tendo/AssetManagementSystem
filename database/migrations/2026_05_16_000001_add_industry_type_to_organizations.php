<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds industry_type field to support different company types:
     * - hospital: Medical facilities
     * - school: Educational institutions
     * - retail: Retail stores
     * - manufacturing: Manufacturing facilities
     * - corporate: Corporate offices
     * - household: Personal/individual accounts
     */
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            // Add industry_type column for company-specific features
            $table->enum('industry_type', [
                'generic',           // Default for companies without specific industry
                'hospital',          // Medical facilities
                'school',            // Educational institutions
                'retail',            // Retail stores
                'manufacturing',     // Manufacturing facilities
                'corporate',         // Corporate offices
                'household'          // Personal/individual accounts (from type='household')
            ])->default('generic')->after('type')->index();

            // Add next_of_kin fields for household accounts
            $table->string('next_of_kin_name')->nullable()->after('industry_type');
            $table->string('next_of_kin_phone')->nullable()->after('next_of_kin_name');
            $table->string('next_of_kin_email')->nullable()->after('next_of_kin_phone');
            $table->text('next_of_kin_relationship')->nullable()->after('next_of_kin_email');

            // Add industry-specific metadata
            $table->json('industry_metadata')->nullable()->after('next_of_kin_relationship');
            // This can store:
            // - For hospital: { bed_count, departments: [...] }
            // - For school: { student_count, departments: [...] }
            // - For retail: { store_count, locations: [...] }
            // - For manufacturing: { production_lines, facilities: [...] }
            // - For corporate: { employee_count, departments: [...] }
            // - For household: { property_type, location_details: {...} }
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn([
                'industry_type',
                'next_of_kin_name',
                'next_of_kin_phone',
                'next_of_kin_email',
                'next_of_kin_relationship',
                'industry_metadata'
            ]);
        });
    }
};
