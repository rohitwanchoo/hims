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
            $table->unsignedBigInteger('prefix_id')->nullable()->after('hospital_id');
            $table->foreign('prefix_id')->references('prefix_id')->on('prefixes')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reference_doctors', function (Blueprint $table) {
            $table->dropForeign(['prefix_id']);
            $table->dropColumn('prefix_id');
        });
    }
};
