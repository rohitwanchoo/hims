<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doctor_patient_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('opd_id')->nullable();
            $table->date('assigned_date');
            $table->enum('status', ['active', 'completed', 'transferred'])->default('active');
            $table->timestamps();

            $table->foreign('doctor_id')->references('doctor_id')->on('doctors')->onDelete('cascade');
            $table->foreign('patient_id')->references('patient_id')->on('patients')->onDelete('cascade');
            $table->foreign('opd_id')->references('opd_id')->on('opd_visits')->onDelete('cascade');

            $table->index(['doctor_id', 'status']);
            $table->index(['patient_id', 'assigned_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctor_patient_assignments');
    }
};
