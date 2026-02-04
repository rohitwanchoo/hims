<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add report_date field only if it doesn't exist
        if (!Schema::hasColumn('patho_test_reports', 'report_date')) {
            Schema::table('patho_test_reports', function (Blueprint $table) {
                $table->date('report_date')->nullable()->after('report_code');
            });
        }

        // Step 1: Temporarily expand enum to include both old and new values
        DB::statement("ALTER TABLE patho_test_reports MODIFY COLUMN report_type ENUM('normal', 'multicolumn', 'biopsy', 'culture', 'histo_patho') DEFAULT 'normal'");

        // Step 2: Update existing data: map old values to new values
        DB::table('patho_test_reports')
            ->where('report_type', 'multicolumn')
            ->update(['report_type' => 'normal']); // Map multicolumn to normal

        DB::table('patho_test_reports')
            ->where('report_type', 'biopsy')
            ->update(['report_type' => 'histo_patho']); // Map biopsy to histo_patho

        // Step 3: Remove old enum values, keeping only new ones
        DB::statement("ALTER TABLE patho_test_reports MODIFY COLUMN report_type ENUM('normal', 'culture', 'histo_patho') DEFAULT 'normal'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patho_test_reports', function (Blueprint $table) {
            $table->dropColumn('report_date');
        });

        // Revert report_type enum to original values
        DB::statement("ALTER TABLE patho_test_reports MODIFY COLUMN report_type ENUM('normal', 'multicolumn', 'biopsy') DEFAULT 'normal'");
    }
};
