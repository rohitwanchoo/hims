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
        Schema::create('patho_test_group_tests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('group_id');
            $table->unsignedBigInteger('test_id');
            $table->integer('display_order')->default(0);
            $table->timestamps();

            // Foreign keys
            $table->foreign('group_id')->references('group_id')->on('patho_test_groups')->onDelete('cascade');
            $table->foreign('test_id')->references('test_id')->on('patho_tests')->onDelete('cascade');

            // Indexes
            $table->index('group_id');
            $table->index('test_id');

            // Unique constraint to prevent duplicate mappings
            $table->unique(['group_id', 'test_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patho_test_group_tests');
    }
};
