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
        Schema::table('patho_test_notes', function (Blueprint $table) {
            $table->foreign('report_id')->references('report_id')->on('patho_test_reports')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patho_test_notes', function (Blueprint $table) {
            $table->dropForeign(['report_id']);
        });
    }
};
