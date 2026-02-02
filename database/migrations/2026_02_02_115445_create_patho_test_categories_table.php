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
        Schema::create('patho_test_categories', function (Blueprint $table) {
            $table->id('category_id');
            $table->foreignId('hospital_id')->constrained('hospitals', 'hospital_id')->onDelete('cascade');
            $table->string('category_name');
            $table->string('category_code')->nullable();
            $table->boolean('fit_100')->default(false);
            $table->boolean('has_sub_category')->default(false);
            $table->unsignedBigInteger('parent_category_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->foreign('parent_category_id')->references('category_id')->on('patho_test_categories')->onDelete('set null');
            $table->index('hospital_id');
            $table->index('category_code');
            $table->index('parent_category_id');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patho_test_categories');
    }
};
