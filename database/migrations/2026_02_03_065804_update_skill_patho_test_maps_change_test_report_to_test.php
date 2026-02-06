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
        Schema::table('skill_patho_test_maps', function (Blueprint $table) {
            // Drop the old foreign key
            $table->dropForeign(['test_report_id']);

            // Rename column from test_report_id to test_id
            $table->renameColumn('test_report_id', 'test_id');
        });

        Schema::table('skill_patho_test_maps', function (Blueprint $table) {
            // Add new foreign key to patho_tests
            $table->foreign('test_id')->references('test_id')->on('patho_tests')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('skill_patho_test_maps', function (Blueprint $table) {
            // Drop the new foreign key
            $table->dropForeign(['test_id']);

            // Rename column back to test_report_id
            $table->renameColumn('test_id', 'test_report_id');
        });

        Schema::table('skill_patho_test_maps', function (Blueprint $table) {
            // Add back old foreign key to patho_test_reports
            $table->foreign('test_report_id')->references('report_id')->on('patho_test_reports')->onDelete('cascade');
        });
    }
};
