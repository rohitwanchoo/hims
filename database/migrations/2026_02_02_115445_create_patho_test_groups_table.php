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
        Schema::create('patho_test_groups', function (Blueprint $table) {
            $table->id('group_id');
            $table->foreignId('hospital_id')->constrained('hospitals', 'hospital_id')->onDelete('cascade');
            $table->string('group_name');
            $table->string('group_code')->nullable();
            $table->boolean('is_default_group')->default(false);
            $table->boolean('is_active')->default(true);
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index('hospital_id');
            $table->index('group_code');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patho_test_groups');
    }
};
