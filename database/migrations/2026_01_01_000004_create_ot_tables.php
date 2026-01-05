<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Operation Theaters
        Schema::create('operation_theaters', function (Blueprint $table) {
            $table->id('ot_id');
            $table->unsignedBigInteger('hospital_id');
            $table->string('ot_code', 20);
            $table->string('ot_name', 100);
            $table->enum('ot_type', ['major', 'minor', 'daycare', 'emergency'])->default('major');
            $table->string('floor', 20)->nullable();
            $table->integer('capacity')->default(1);
            $table->boolean('has_laminar_flow')->default(false);
            $table->boolean('has_c_arm')->default(false);
            $table->boolean('has_laparoscopy')->default(false);
            $table->decimal('charges_per_hour', 10, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('hospital_id')->references('hospital_id')->on('hospitals')->cascadeOnDelete();
            $table->unique(['hospital_id', 'ot_code']);
        });

        // Surgery Types
        Schema::create('surgery_types', function (Blueprint $table) {
            $table->id('surgery_type_id');
            $table->unsignedBigInteger('hospital_id');
            $table->string('surgery_code', 20);
            $table->string('surgery_name', 200);
            $table->unsignedBigInteger('department_id')->nullable();
            $table->string('specialty', 100)->nullable();
            $table->enum('category', ['elective', 'emergency', 'day_care'])->default('elective');
            $table->enum('complexity', ['minor', 'moderate', 'major', 'super_specialty'])->default('moderate');
            $table->integer('estimated_duration_mins')->default(60);
            $table->decimal('base_charges', 12, 2)->default(0);
            $table->decimal('surgeon_fee', 10, 2)->default(0);
            $table->enum('anesthesia_type', ['local', 'regional', 'general', 'sedation'])->default('general');
            $table->boolean('requires_icu')->default(false);
            $table->boolean('requires_blood')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('hospital_id')->references('hospital_id')->on('hospitals')->cascadeOnDelete();
            $table->foreign('department_id')->references('department_id')->on('departments')->nullOnDelete();
            $table->unique(['hospital_id', 'surgery_code']);
        });

        // OT Schedules
        Schema::create('ot_schedules', function (Blueprint $table) {
            $table->id('schedule_id');
            $table->unsignedBigInteger('hospital_id');
            $table->unsignedBigInteger('ot_id');
            $table->unsignedBigInteger('ipd_id')->nullable();
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('surgery_type_id')->nullable();
            $table->string('surgery_name', 200);
            $table->date('scheduled_date');
            $table->time('scheduled_start_time');
            $table->time('scheduled_end_time');
            $table->integer('estimated_duration_mins');
            $table->enum('priority', ['routine', 'urgent', 'emergency'])->default('routine');
            $table->text('pre_op_diagnosis');
            $table->text('planned_procedure');
            $table->unsignedBigInteger('surgeon_id');
            $table->unsignedBigInteger('assistant_surgeon_id')->nullable();
            $table->unsignedBigInteger('anesthetist_id');
            $table->enum('anesthesia_type', ['local', 'regional', 'spinal', 'epidural', 'general', 'combined'])->default('general');
            $table->text('special_equipment')->nullable();
            $table->string('blood_requirement', 100)->nullable();
            $table->boolean('pre_op_checklist_complete')->default(false);
            $table->boolean('consent_obtained')->default(false);
            $table->enum('status', ['scheduled', 'confirmed', 'in_progress', 'completed', 'postponed', 'cancelled'])->default('scheduled');
            $table->text('postpone_reason')->nullable();
            $table->text('cancel_reason')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('hospital_id')->references('hospital_id')->on('hospitals')->cascadeOnDelete();
            $table->foreign('ot_id')->references('ot_id')->on('operation_theaters')->cascadeOnDelete();
            $table->foreign('ipd_id')->references('ipd_id')->on('ipd_admissions')->nullOnDelete();
            $table->foreign('patient_id')->references('patient_id')->on('patients')->cascadeOnDelete();
            $table->foreign('surgery_type_id')->references('surgery_type_id')->on('surgery_types')->nullOnDelete();
            $table->foreign('surgeon_id')->references('doctor_id')->on('doctors')->cascadeOnDelete();
            $table->foreign('assistant_surgeon_id')->references('doctor_id')->on('doctors')->nullOnDelete();
            $table->foreign('anesthetist_id')->references('doctor_id')->on('doctors')->cascadeOnDelete();
            $table->foreign('created_by')->references('user_id')->on('users')->nullOnDelete();
            $table->index(['hospital_id', 'scheduled_date', 'status']);
        });

        // OT Procedures
        Schema::create('ot_procedures', function (Blueprint $table) {
            $table->id('procedure_id');
            $table->unsignedBigInteger('hospital_id');
            $table->unsignedBigInteger('schedule_id');
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('ipd_id')->nullable();
            $table->unsignedBigInteger('ot_id');
            $table->string('procedure_number', 20)->unique();
            $table->date('procedure_date');
            $table->time('actual_start_time');
            $table->time('actual_end_time')->nullable();
            $table->unsignedBigInteger('surgeon_id');
            $table->unsignedBigInteger('assistant_surgeon_id')->nullable();
            $table->unsignedBigInteger('anesthetist_id');
            $table->unsignedBigInteger('scrub_nurse_id')->nullable();
            $table->unsignedBigInteger('circulating_nurse_id')->nullable();
            $table->time('anesthesia_start_time')->nullable();
            $table->time('anesthesia_end_time')->nullable();
            $table->time('incision_time')->nullable();
            $table->time('closure_time')->nullable();
            $table->text('pre_op_diagnosis');
            $table->text('post_op_diagnosis')->nullable();
            $table->text('procedure_performed');
            $table->text('procedure_notes')->nullable();
            $table->text('complications')->nullable();
            $table->integer('blood_loss_ml')->nullable();
            $table->string('blood_transfused', 100)->nullable();
            $table->text('specimens_collected')->nullable();
            $table->text('implants_used')->nullable();
            $table->boolean('drain_placed')->default(false);
            $table->string('drain_details', 255)->nullable();
            $table->enum('wound_classification', ['clean', 'clean_contaminated', 'contaminated', 'dirty'])->default('clean');
            $table->text('patient_condition_post_op')->nullable();
            $table->text('post_op_instructions')->nullable();
            $table->boolean('icu_required')->default(false);
            $table->enum('status', ['in_progress', 'completed', 'cancelled'])->default('in_progress');
            $table->timestamps();

            $table->foreign('hospital_id')->references('hospital_id')->on('hospitals')->cascadeOnDelete();
            $table->foreign('schedule_id')->references('schedule_id')->on('ot_schedules')->cascadeOnDelete();
            $table->foreign('patient_id')->references('patient_id')->on('patients')->cascadeOnDelete();
            $table->foreign('ipd_id')->references('ipd_id')->on('ipd_admissions')->nullOnDelete();
            $table->foreign('ot_id')->references('ot_id')->on('operation_theaters')->cascadeOnDelete();
            $table->foreign('surgeon_id')->references('doctor_id')->on('doctors')->cascadeOnDelete();
            $table->foreign('assistant_surgeon_id')->references('doctor_id')->on('doctors')->nullOnDelete();
            $table->foreign('anesthetist_id')->references('doctor_id')->on('doctors')->cascadeOnDelete();
            $table->foreign('scrub_nurse_id')->references('user_id')->on('users')->nullOnDelete();
            $table->foreign('circulating_nurse_id')->references('user_id')->on('users')->nullOnDelete();
        });

        // OT Consumables
        Schema::create('ot_consumables', function (Blueprint $table) {
            $table->id('consumable_id');
            $table->unsignedBigInteger('hospital_id');
            $table->unsignedBigInteger('procedure_id');
            $table->unsignedBigInteger('item_id')->nullable();
            $table->string('item_name', 200);
            $table->string('batch_number', 50)->nullable();
            $table->decimal('quantity', 10, 2);
            $table->string('unit', 20);
            $table->decimal('rate', 10, 2);
            $table->decimal('amount', 12, 2);
            $table->boolean('is_implant')->default(false);
            $table->string('implant_serial_number', 100)->nullable();
            $table->timestamps();

            $table->foreign('hospital_id')->references('hospital_id')->on('hospitals')->cascadeOnDelete();
            $table->foreign('procedure_id')->references('procedure_id')->on('ot_procedures')->cascadeOnDelete();
        });

        // OT Anesthesia Records
        Schema::create('ot_anesthesia_records', function (Blueprint $table) {
            $table->id('anesthesia_record_id');
            $table->unsignedBigInteger('hospital_id');
            $table->unsignedBigInteger('procedure_id');
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('anesthetist_id');
            $table->text('pre_op_assessment');
            $table->enum('asa_grade', ['I', 'II', 'III', 'IV', 'V', 'VI'])->default('I');
            $table->text('airway_assessment')->nullable();
            $table->enum('mallampati_score', ['I', 'II', 'III', 'IV'])->nullable();
            $table->enum('anesthesia_type', ['local', 'regional', 'spinal', 'epidural', 'general', 'combined'])->default('general');
            $table->text('anesthesia_technique');
            $table->json('agents_used')->nullable();
            $table->json('monitoring_data')->nullable();
            $table->text('intubation_details')->nullable();
            $table->text('complications')->nullable();
            $table->text('post_op_orders')->nullable();
            $table->integer('recovery_score')->nullable();
            $table->timestamps();

            $table->foreign('hospital_id')->references('hospital_id')->on('hospitals')->cascadeOnDelete();
            $table->foreign('procedure_id')->references('procedure_id')->on('ot_procedures')->cascadeOnDelete();
            $table->foreign('patient_id')->references('patient_id')->on('patients')->cascadeOnDelete();
            $table->foreign('anesthetist_id')->references('doctor_id')->on('doctors')->cascadeOnDelete();
        });

        // OT Pre-Op Checklists
        Schema::create('ot_pre_op_checklists', function (Blueprint $table) {
            $table->id('checklist_id');
            $table->unsignedBigInteger('hospital_id');
            $table->unsignedBigInteger('schedule_id');
            $table->unsignedBigInteger('patient_id');
            $table->boolean('consent_surgical')->default(false);
            $table->boolean('consent_anesthesia')->default(false);
            $table->boolean('consent_blood_transfusion')->default(false);
            $table->boolean('site_marked')->default(false);
            $table->boolean('patient_identity_confirmed')->default(false);
            $table->boolean('allergies_noted')->default(false);
            $table->text('allergies_details')->nullable();
            $table->boolean('nil_by_mouth_confirmed')->default(false);
            $table->integer('npo_hours')->nullable();
            $table->boolean('investigations_reviewed')->default(false);
            $table->boolean('blood_available')->default(false);
            $table->string('blood_type_crossmatched', 50)->nullable();
            $table->boolean('pre_medications_given')->default(false);
            $table->text('pre_medication_details')->nullable();
            $table->boolean('jewelry_removed')->default(false);
            $table->boolean('dentures_removed')->default(false);
            $table->text('prosthesis_noted')->nullable();
            $table->boolean('iv_line_secured')->default(false);
            $table->boolean('foley_catheter')->default(false);
            $table->boolean('skin_prep_done')->default(false);
            $table->boolean('special_equipment_verified')->default(false);
            $table->unsignedBigInteger('checked_by')->nullable();
            $table->datetime('checked_at')->nullable();
            $table->timestamps();

            $table->foreign('hospital_id')->references('hospital_id')->on('hospitals')->cascadeOnDelete();
            $table->foreign('schedule_id')->references('schedule_id')->on('ot_schedules')->cascadeOnDelete();
            $table->foreign('patient_id')->references('patient_id')->on('patients')->cascadeOnDelete();
            $table->foreign('checked_by')->references('user_id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ot_pre_op_checklists');
        Schema::dropIfExists('ot_anesthesia_records');
        Schema::dropIfExists('ot_consumables');
        Schema::dropIfExists('ot_procedures');
        Schema::dropIfExists('ot_schedules');
        Schema::dropIfExists('surgery_types');
        Schema::dropIfExists('operation_theaters');
    }
};
