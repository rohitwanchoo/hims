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
        Schema::create('skill_patho_test_maps', function (Blueprint $table) {
            $table->id('map_id');
            $table->foreignId('hospital_id')->constrained('hospitals', 'hospital_id')->onDelete('cascade');
            $table->unsignedBigInteger('skill_id');
            $table->unsignedBigInteger('test_report_id');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('test_report_id')->references('report_id')->on('patho_test_reports')->onDelete('cascade');
            $table->index('hospital_id');
            $table->index('skill_id');
            $table->index('test_report_id');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('skill_patho_test_maps');
    }
};
