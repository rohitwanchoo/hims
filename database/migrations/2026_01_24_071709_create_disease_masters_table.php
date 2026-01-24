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
        Schema::create('disease_masters', function (Blueprint $table) {
            $table->id('disease_id');
            $table->unsignedBigInteger('hospital_id');
            $table->string('disease_name', 255);
            $table->enum('language', ['english', 'marathi', 'hindi'])->default('english');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('hospital_id')->references('hospital_id')->on('hospitals')->onDelete('cascade');
            $table->index(['hospital_id', 'language']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('disease_masters');
    }
};
