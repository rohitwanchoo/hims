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
        // Add hospital_id to reference_doctors
        Schema::table('reference_doctors', function (Blueprint $table) {
            $table->unsignedBigInteger('hospital_id')->nullable()->after('reference_doctor_id');
            $table->foreign('hospital_id')->references('hospital_id')->on('hospitals')->onDelete('cascade');
        });
        DB::table('reference_doctors')->whereNull('hospital_id')->update(['hospital_id' => 1]);

        // Add hospital_id to skill_sets
        Schema::table('skill_sets', function (Blueprint $table) {
            $table->unsignedBigInteger('hospital_id')->nullable()->after('skill_set_id');
            $table->foreign('hospital_id')->references('hospital_id')->on('hospitals')->onDelete('cascade');
        });
        DB::table('skill_sets')->whereNull('hospital_id')->update(['hospital_id' => 1]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reference_doctors', function (Blueprint $table) {
            $table->dropForeign(['hospital_id']);
            $table->dropColumn('hospital_id');
        });

        Schema::table('skill_sets', function (Blueprint $table) {
            $table->dropForeign(['hospital_id']);
            $table->dropColumn('hospital_id');
        });
    }
};
