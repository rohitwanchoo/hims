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
        Schema::create('patho_tests', function (Blueprint $table) {
            $table->id('test_id');
            $table->foreignId('hospital_id')->constrained('hospitals', 'hospital_id')->onDelete('cascade');
            $table->string('test_name');
            $table->enum('detail_type', ['range', 'reference'])->default('range');
            $table->boolean('use_for_ipd_summary')->default(false);
            $table->enum('value_type', ['numeric', 'alphanumeric'])->default('numeric');
            $table->integer('max_length')->nullable();
            $table->string('normal_range')->nullable();
            $table->unsignedBigInteger('method_id')->nullable();
            $table->unsignedBigInteger('unit_id')->nullable();
            $table->unsignedBigInteger('container_id')->nullable();
            $table->boolean('is_multicolumn')->default(false);
            $table->boolean('is_calculated')->default(false);
            $table->boolean('is_subtest_calculated')->default(false);
            $table->decimal('range_from', 10, 2)->nullable();
            $table->decimal('range_to', 10, 2)->nullable();
            $table->decimal('deviation_value', 10, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('method_id')->references('method_id')->on('patho_test_methods')->onDelete('set null');
            $table->foreign('unit_id')->references('unit_id')->on('patho_test_units')->onDelete('set null');
            $table->foreign('container_id')->references('container_id')->on('patho_containers')->onDelete('set null');
            $table->index('hospital_id');
            $table->index('method_id');
            $table->index('unit_id');
            $table->index('container_id');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patho_tests');
    }
};
