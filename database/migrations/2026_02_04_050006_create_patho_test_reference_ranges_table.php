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
        Schema::create('patho_test_reference_ranges', function (Blueprint $table) {
            $table->id('reference_id');
            $table->unsignedBigInteger('test_id');
            $table->unsignedBigInteger('gender_id')->nullable();
            $table->unsignedBigInteger('age_group_id')->nullable();
            $table->decimal('min_value', 10, 2)->nullable();
            $table->decimal('max_value', 10, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('test_id')->references('test_id')->on('patho_tests')->onDelete('cascade');
            $table->foreign('gender_id')->references('gender_id')->on('genders')->onDelete('set null');
            $table->foreign('age_group_id')->references('age_group_id')->on('age_groups')->onDelete('set null');

            $table->index('test_id');
            $table->index('gender_id');
            $table->index('age_group_id');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patho_test_reference_ranges');
    }
};
