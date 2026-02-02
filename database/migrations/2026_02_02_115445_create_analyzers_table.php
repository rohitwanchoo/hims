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
        Schema::create('analyzers', function (Blueprint $table) {
            $table->id('analyzer_id');
            $table->foreignId('hospital_id')->constrained('hospitals', 'hospital_id')->onDelete('cascade');
            $table->string('analyzer_name');
            $table->string('analyzer_code')->nullable();
            $table->enum('analyzer_type', ['on_demand', 'pre_database'])->default('on_demand');
            $table->boolean('is_bidirectional')->default(false);
            $table->integer('analyzer_count')->default(1);
            $table->boolean('is_active')->default(true);
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index('hospital_id');
            $table->index('analyzer_code');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analyzers');
    }
};
