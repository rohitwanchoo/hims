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
        Schema::create('discharge_summaries', function (Blueprint $table) {
            $table->id('discharge_summary_id');
            $table->unsignedBigInteger('hospital_id');
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('ipd_id');
            $table->string('discharge_summary_number', 50)->unique();

            // Admission Details
            $table->dateTime('admission_date');
            $table->dateTime('discharge_date');
            $table->string('admission_type', 50)->default('emergency'); // emergency, planned
            $table->text('chief_complaints')->nullable();
            $table->text('history_of_present_illness')->nullable();
            $table->text('past_medical_history')->nullable();
            $table->text('family_history')->nullable();

            // Physical Examination
            $table->text('physical_examination')->nullable();
            $table->string('vital_signs', 500)->nullable(); // JSON

            // Diagnosis
            $table->text('provisional_diagnosis')->nullable();
            $table->text('final_diagnosis');
            $table->text('secondary_diagnosis')->nullable();
            $table->string('icd_codes', 500)->nullable(); // ICD-10 codes

            // Treatment
            $table->text('course_in_hospital')->nullable();
            $table->text('procedures_performed')->nullable();
            $table->text('operation_notes')->nullable();
            $table->text('investigations')->nullable();
            $table->text('treatment_given')->nullable();

            // Medications
            $table->text('medications_on_admission')->nullable();
            $table->text('medications_on_discharge')->nullable();

            // Discharge Details
            $table->string('condition_at_discharge', 100); // improved, same, deteriorated, expired
            $table->text('discharge_advice')->nullable();
            $table->text('follow_up_instructions')->nullable();
            $table->date('follow_up_date')->nullable();
            $table->text('dietary_instructions')->nullable();
            $table->text('activity_restrictions')->nullable();

            // Doctor Details
            $table->unsignedBigInteger('treating_doctor_id')->nullable();
            $table->unsignedBigInteger('consultant_doctor_id')->nullable();
            $table->unsignedBigInteger('created_by');

            // ABDM Related
            $table->string('abha_address', 100)->nullable();
            $table->boolean('shared_with_abdm')->default(false);
            $table->dateTime('abdm_shared_at')->nullable();
            $table->string('abdm_document_id', 100)->nullable();

            $table->text('notes')->nullable();
            $table->enum('status', ['draft', 'completed', 'signed'])->default('draft');

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('hospital_id')->references('hospital_id')->on('hospitals')->cascadeOnDelete();
            $table->foreign('patient_id')->references('patient_id')->on('patients')->cascadeOnDelete();
            $table->foreign('ipd_id')->references('ipd_id')->on('ipd_admissions')->cascadeOnDelete();
            $table->foreign('treating_doctor_id')->references('user_id')->on('users')->nullOnDelete();
            $table->foreign('consultant_doctor_id')->references('user_id')->on('users')->nullOnDelete();
            $table->foreign('created_by')->references('user_id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discharge_summaries');
    }
};
