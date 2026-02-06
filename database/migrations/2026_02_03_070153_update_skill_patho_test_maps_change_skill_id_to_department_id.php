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
            // Rename column from skill_id to department_id
            $table->renameColumn('skill_id', 'department_id');
        });

        Schema::table('skill_patho_test_maps', function (Blueprint $table) {
            // Add foreign key to departments table
            $table->foreign('department_id')->references('department_id')->on('departments')->onDelete('cascade');
            $table->index('department_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('skill_patho_test_maps', function (Blueprint $table) {
            // Drop the foreign key
            $table->dropForeign(['department_id']);
            $table->dropIndex(['department_id']);
        });

        Schema::table('skill_patho_test_maps', function (Blueprint $table) {
            // Rename column back to skill_id
            $table->renameColumn('department_id', 'skill_id');
        });
    }
};
