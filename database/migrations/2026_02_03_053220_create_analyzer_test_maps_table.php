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
        Schema::create('analyzer_test_maps', function (Blueprint $table) {
            $table->id('map_id');
            $table->foreignId('analyzer_id')->constrained('analyzers', 'analyzer_id')->onDelete('cascade');
            $table->foreignId('test_id')->constrained('patho_tests', 'test_id')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['analyzer_id', 'test_id']);
            $table->index('analyzer_id');
            $table->index('test_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analyzer_test_maps');
    }
};
