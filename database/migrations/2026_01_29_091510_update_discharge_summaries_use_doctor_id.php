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
        Schema::table('discharge_summaries', function (Blueprint $table) {
            // Drop old foreign keys
            $table->dropForeign(['treating_doctor_id']);
            $table->dropForeign(['consultant_doctor_id']);

            // Add new foreign keys to doctors table
            $table->foreign('treating_doctor_id')->references('doctor_id')->on('doctors')->nullOnDelete();
            $table->foreign('consultant_doctor_id')->references('doctor_id')->on('doctors')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('discharge_summaries', function (Blueprint $table) {
            // Drop new foreign keys
            $table->dropForeign(['treating_doctor_id']);
            $table->dropForeign(['consultant_doctor_id']);

            // Restore old foreign keys to users table
            $table->foreign('treating_doctor_id')->references('user_id')->on('users')->nullOnDelete();
            $table->foreign('consultant_doctor_id')->references('user_id')->on('users')->nullOnDelete();
        });
    }
};
