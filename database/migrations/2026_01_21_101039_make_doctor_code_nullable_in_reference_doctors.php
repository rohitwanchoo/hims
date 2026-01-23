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
        Schema::table('reference_doctors', function (Blueprint $table) {
            // Make doctor_code nullable since it's no longer required
            $table->string('doctor_code', 20)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reference_doctors', function (Blueprint $table) {
            // Revert doctor_code to NOT NULL (if needed)
            $table->string('doctor_code', 20)->nullable(false)->change();
        });
    }
};
