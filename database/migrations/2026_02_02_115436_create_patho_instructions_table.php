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
        Schema::create('patho_instructions', function (Blueprint $table) {
            $table->id('instruction_id');
            $table->foreignId('hospital_id')->constrained('hospitals', 'hospital_id')->onDelete('cascade');
            $table->enum('instruction_type', ['pathology', 'procedure']);
            $table->text('instruction_text');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('hospital_id');
            $table->index('instruction_type');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patho_instructions');
    }
};
