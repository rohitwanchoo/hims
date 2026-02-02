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
        Schema::create('pathologist_doctor_maps', function (Blueprint $table) {
            $table->id('map_id');
            $table->foreignId('hospital_id')->constrained('hospitals', 'hospital_id')->onDelete('cascade');
            $table->unsignedBigInteger('faculty_id');
            $table->foreignId('doctor_id')->constrained('doctors', 'doctor_id')->onDelete('cascade');
            $table->unsignedBigInteger('skill_set_id')->nullable();
            $table->string('signature_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('faculty_id')->references('faculty_id')->on('patho_faculties')->onDelete('cascade');
            $table->index('hospital_id');
            $table->index('faculty_id');
            $table->index('doctor_id');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pathologist_doctor_maps');
    }
};
