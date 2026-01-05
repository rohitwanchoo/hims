<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Birth Registrations
        Schema::create('birth_registrations', function (Blueprint $table) {
            $table->id('birth_id');
            $table->unsignedBigInteger('hospital_id');
            $table->string('registration_number', 30)->unique();
            $table->unsignedBigInteger('ipd_id')->nullable();
            $table->unsignedBigInteger('mother_patient_id');
            $table->string('father_name', 100);
            $table->string('father_aadhar', 12)->nullable();
            $table->string('father_occupation', 50)->nullable();
            $table->string('father_education', 50)->nullable();
            $table->string('father_nationality', 50)->default('Indian');
            $table->string('mother_name', 100);
            $table->string('mother_aadhar', 12)->nullable();
            $table->string('mother_occupation', 50)->nullable();
            $table->string('mother_education', 50)->nullable();
            $table->integer('mother_age_at_delivery');
            $table->string('mother_nationality', 50)->default('Indian');
            $table->text('permanent_address');
            $table->string('permanent_city', 50);
            $table->string('permanent_district', 50);
            $table->string('permanent_state', 50);
            $table->string('permanent_pincode', 10);
            $table->date('date_of_birth');
            $table->time('time_of_birth');
            $table->string('place_of_birth', 100);
            $table->enum('birth_type', ['live', 'stillbirth'])->default('live');
            $table->enum('gender', ['male', 'female', 'other']);
            $table->decimal('weight_kg', 4, 2);
            $table->integer('birth_order')->default(1);
            $table->enum('delivery_type', ['normal', 'cesarean', 'assisted'])->default('normal');
            $table->integer('pregnancy_duration_weeks');
            $table->unsignedBigInteger('attending_doctor_id');
            $table->text('complications')->nullable();
            $table->integer('apgar_score_1min')->nullable();
            $table->integer('apgar_score_5min')->nullable();
            $table->boolean('is_multiple_birth')->default(false);
            $table->integer('multiple_birth_order')->nullable();
            $table->string('child_name', 100)->nullable();
            $table->string('informant_name', 100);
            $table->string('informant_relation', 50);
            $table->text('informant_address')->nullable();
            $table->string('certificate_number', 30)->nullable();
            $table->date('certificate_issue_date')->nullable();
            $table->boolean('is_govt_registered')->default(false);
            $table->string('govt_registration_number', 50)->nullable();
            $table->date('govt_registration_date')->nullable();
            $table->enum('status', ['draft', 'registered', 'certificate_issued'])->default('draft');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('hospital_id')->references('hospital_id')->on('hospitals')->cascadeOnDelete();
            $table->foreign('ipd_id')->references('ipd_id')->on('ipd_admissions')->nullOnDelete();
            $table->foreign('mother_patient_id')->references('patient_id')->on('patients')->cascadeOnDelete();
            $table->foreign('attending_doctor_id')->references('doctor_id')->on('doctors')->cascadeOnDelete();
            $table->foreign('created_by')->references('user_id')->on('users')->nullOnDelete();
            $table->index(['hospital_id', 'date_of_birth']);
        });

        // Death Registrations
        Schema::create('death_registrations', function (Blueprint $table) {
            $table->id('death_id');
            $table->unsignedBigInteger('hospital_id');
            $table->string('registration_number', 30)->unique();
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('ipd_id')->nullable();
            $table->string('deceased_name', 100);
            $table->string('deceased_aadhar', 12)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->integer('age_years');
            $table->integer('age_months')->nullable();
            $table->integer('age_days')->nullable();
            $table->enum('gender', ['male', 'female', 'other']);
            $table->string('marital_status', 20)->nullable();
            $table->string('occupation', 50)->nullable();
            $table->string('nationality', 50)->default('Indian');
            $table->string('religion', 50)->nullable();
            $table->text('permanent_address');
            $table->string('permanent_city', 50);
            $table->string('permanent_district', 50);
            $table->string('permanent_state', 50);
            $table->string('permanent_pincode', 10);
            $table->date('date_of_death');
            $table->time('time_of_death');
            $table->enum('place_of_death', ['hospital', 'home', 'transit', 'other'])->default('hospital');
            $table->string('place_details', 200)->nullable();
            $table->boolean('is_medically_certified')->default(true);
            $table->string('cause_of_death_immediate', 255);
            $table->string('cause_of_death_antecedent', 255)->nullable();
            $table->string('cause_of_death_underlying', 255)->nullable();
            $table->text('other_conditions')->nullable();
            $table->enum('manner_of_death', ['natural', 'accident', 'suicide', 'homicide', 'pending_investigation', 'unknown'])->default('natural');
            $table->enum('was_pregnant', ['yes', 'no', 'na'])->default('na');
            $table->boolean('pregnancy_contribution')->default(false);
            $table->boolean('is_autopsy_performed')->default(false);
            $table->text('autopsy_findings')->nullable();
            $table->boolean('is_mlc_case')->default(false);
            $table->string('mlc_number', 50)->nullable();
            $table->string('police_station', 100)->nullable();
            $table->unsignedBigInteger('certifying_doctor_id');
            $table->string('father_name', 100)->nullable();
            $table->string('mother_name', 100)->nullable();
            $table->string('spouse_name', 100)->nullable();
            $table->string('informant_name', 100);
            $table->string('informant_relation', 50);
            $table->text('informant_address')->nullable();
            $table->string('certificate_number', 30)->nullable();
            $table->date('certificate_issue_date')->nullable();
            $table->boolean('is_govt_registered')->default(false);
            $table->string('govt_registration_number', 50)->nullable();
            $table->date('govt_registration_date')->nullable();
            $table->string('cremation_burial_place', 200)->nullable();
            $table->string('body_handed_to', 100)->nullable();
            $table->string('body_handed_relation', 50)->nullable();
            $table->datetime('body_handed_at')->nullable();
            $table->enum('status', ['draft', 'registered', 'certificate_issued'])->default('draft');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('hospital_id')->references('hospital_id')->on('hospitals')->cascadeOnDelete();
            $table->foreign('patient_id')->references('patient_id')->on('patients')->cascadeOnDelete();
            $table->foreign('ipd_id')->references('ipd_id')->on('ipd_admissions')->nullOnDelete();
            $table->foreign('certifying_doctor_id')->references('doctor_id')->on('doctors')->cascadeOnDelete();
            $table->foreign('created_by')->references('user_id')->on('users')->nullOnDelete();
            $table->index(['hospital_id', 'date_of_death']);
        });

        // Stillbirth Registrations
        Schema::create('stillbirth_registrations', function (Blueprint $table) {
            $table->id('stillbirth_id');
            $table->unsignedBigInteger('hospital_id');
            $table->string('registration_number', 30)->unique();
            $table->unsignedBigInteger('ipd_id')->nullable();
            $table->unsignedBigInteger('mother_patient_id');
            $table->string('father_name', 100);
            $table->string('father_aadhar', 12)->nullable();
            $table->string('mother_name', 100);
            $table->string('mother_aadhar', 12)->nullable();
            $table->integer('mother_age_at_delivery');
            $table->text('permanent_address');
            $table->string('permanent_city', 50);
            $table->string('permanent_district', 50);
            $table->string('permanent_state', 50);
            $table->string('permanent_pincode', 10);
            $table->date('date_of_delivery');
            $table->time('time_of_delivery');
            $table->string('place_of_delivery', 100);
            $table->enum('gender', ['male', 'female', 'undetermined'])->default('undetermined');
            $table->decimal('weight_kg', 4, 2);
            $table->integer('gestational_age_weeks');
            $table->enum('delivery_type', ['normal', 'cesarean', 'assisted'])->default('normal');
            $table->text('cause_of_fetal_death');
            $table->boolean('was_alive_at_labor_start')->nullable();
            $table->unsignedBigInteger('attending_doctor_id');
            $table->text('complications')->nullable();
            $table->string('informant_name', 100);
            $table->string('informant_relation', 50);
            $table->string('certificate_number', 30)->nullable();
            $table->date('certificate_issue_date')->nullable();
            $table->boolean('is_govt_registered')->default(false);
            $table->string('govt_registration_number', 50)->nullable();
            $table->enum('status', ['draft', 'registered', 'certificate_issued'])->default('draft');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('hospital_id')->references('hospital_id')->on('hospitals')->cascadeOnDelete();
            $table->foreign('ipd_id')->references('ipd_id')->on('ipd_admissions')->nullOnDelete();
            $table->foreign('mother_patient_id')->references('patient_id')->on('patients')->cascadeOnDelete();
            $table->foreign('attending_doctor_id')->references('doctor_id')->on('doctors')->cascadeOnDelete();
            $table->foreign('created_by')->references('user_id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stillbirth_registrations');
        Schema::dropIfExists('death_registrations');
        Schema::dropIfExists('birth_registrations');
    }
};
