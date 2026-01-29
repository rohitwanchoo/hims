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
        Schema::table('ipd_admissions', function (Blueprint $table) {
            // ABDM Discharge Summary Fields
            $table->datetime('discharge_datetime')->nullable()->after('discharge_date');

            // Clinical Summary
            $table->text('chief_complaints')->nullable()->after('provisional_diagnosis');
            $table->text('history_present_illness')->nullable()->after('chief_complaints');
            $table->text('past_medical_history')->nullable()->after('history_present_illness');
            $table->text('examination_findings')->nullable()->after('past_medical_history');

            // Diagnosis
            $table->string('primary_diagnosis')->nullable()->after('final_diagnosis');
            $table->string('primary_diagnosis_icd')->nullable()->after('primary_diagnosis');
            $table->text('secondary_diagnosis')->nullable()->after('primary_diagnosis_icd');

            // Investigations & Treatment
            $table->text('investigations')->nullable()->after('secondary_diagnosis');
            $table->text('treatment_given')->nullable()->after('investigations');
            $table->text('procedures')->nullable()->after('treatment_given');
            $table->text('course_in_hospital')->nullable()->after('procedures');

            // Discharge Medications
            $table->text('discharge_medications')->nullable()->after('course_in_hospital');

            // Discharge Instructions
            $table->text('dietary_advice')->nullable()->after('discharge_medications');
            $table->text('activity_advice')->nullable()->after('dietary_advice');
            $table->text('discharge_instructions')->nullable()->after('activity_advice');
            $table->string('followup_doctor')->nullable()->after('followup_date');

            // Additional
            $table->text('referral_details')->nullable()->after('followup_doctor');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ipd_admissions', function (Blueprint $table) {
            $table->dropColumn([
                'discharge_datetime',
                'chief_complaints',
                'history_present_illness',
                'past_medical_history',
                'examination_findings',
                'primary_diagnosis',
                'primary_diagnosis_icd',
                'secondary_diagnosis',
                'investigations',
                'treatment_given',
                'procedures',
                'course_in_hospital',
                'discharge_medications',
                'dietary_advice',
                'activity_advice',
                'discharge_instructions',
                'followup_doctor',
                'referral_details',
            ]);
        });
    }
};
