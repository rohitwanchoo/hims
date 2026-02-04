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
        Schema::create('patho_test_report_test_maps', function (Blueprint $table) {
            $table->id('map_id');
            $table->unsignedBigInteger('report_id');
            $table->unsignedBigInteger('test_id');
            $table->integer('test_sequence')->nullable();
            $table->boolean('is_mandatory')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Foreign keys
            $table->foreign('report_id')->references('report_id')->on('patho_test_reports')->onDelete('cascade');
            $table->foreign('test_id')->references('test_id')->on('patho_tests')->onDelete('cascade');

            // Indexes
            $table->index('report_id');
            $table->index('test_id');
            $table->unique(['report_id', 'test_id'], 'unique_report_test');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patho_test_report_test_maps');
    }
};
