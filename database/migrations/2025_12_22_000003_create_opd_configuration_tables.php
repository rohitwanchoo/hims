<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // OPD Configuration Settings (Hospital-level OPD settings)
        Schema::create('opd_configurations', function (Blueprint $table) {
            $table->id('config_id');
            $table->unsignedBigInteger('hospital_id');

            // Entry Card / Registration Charges
            $table->boolean('charge_entry_card')->default(true);
            $table->decimal('entry_card_amount', 10, 2)->default(0);
            $table->enum('entry_card_validity_type', ['one_time', 'daily', 'monthly', 'half_yearly', 'yearly', 'lifetime'])->default('yearly');
            $table->integer('entry_card_validity_days')->nullable(); // Custom validity in days

            // Consultation Charge Collection Mode
            $table->enum('consultation_charge_mode', ['at_registration', 'after_consultation', 'both'])->default('at_registration');

            // Rate Configuration
            $table->enum('rate_type', ['fixed', 'editable', 'doctor_approval'])->default('fixed');
            $table->boolean('allow_rate_override')->default(false);
            $table->boolean('require_doctor_approval_for_rate_change')->default(false);

            // Doctor Wise Rates
            $table->boolean('use_doctor_wise_rates')->default(true);
            $table->boolean('use_class_wise_rates')->default(true);

            // Registration Mode
            $table->enum('registration_mode', ['doctor', 'department', 'unit', 'group'])->default('doctor');

            // Appointment Settings
            $table->integer('appointment_expiry_days')->default(1);
            $table->boolean('auto_cancel_expired_appointments')->default(true);
            $table->time('appointment_cancel_time')->nullable(); // Time when auto-cancel runs

            // Follow-up Settings
            $table->boolean('enable_free_followup')->default(true);
            $table->integer('default_followup_validity_days')->default(7);
            $table->integer('default_free_followup_days')->default(3);

            // Token Generation
            $table->enum('token_generation', ['auto', 'manual', 'slot_based'])->default('auto');
            $table->boolean('token_per_doctor')->default(true);
            $table->boolean('token_per_department')->default(false);

            // Other Settings
            $table->boolean('mandatory_vitals')->default(false);
            $table->boolean('mandatory_chief_complaint')->default(true);
            $table->boolean('allow_multiple_visits_per_day')->default(false);
            $table->boolean('require_payment_before_consultation')->default(false);

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('hospital_id')->references('hospital_id')->on('hospitals')->cascadeOnDelete();
        });

        // Doctor OPD Rates (Doctor-specific consultation rates)
        Schema::create('doctor_opd_rates', function (Blueprint $table) {
            $table->id('rate_id');
            $table->unsignedBigInteger('hospital_id');
            $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('class_id')->nullable(); // Patient class specific rate
            $table->enum('visit_type', ['new', 'followup', 'emergency', 'referral'])->default('new');
            $table->enum('charge_type', ['normal', 'day_emergency', 'night_emergency'])->default('normal');
            $table->decimal('rate', 10, 2)->default(0);
            $table->decimal('free_followup_rate', 10, 2)->default(0);
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('hospital_id')->references('hospital_id')->on('hospitals')->cascadeOnDelete();
            $table->foreign('doctor_id')->references('doctor_id')->on('doctors')->cascadeOnDelete();
            $table->foreign('class_id')->references('class_id')->on('classes')->nullOnDelete();

            $table->unique(['hospital_id', 'doctor_id', 'class_id', 'visit_type', 'charge_type'], 'doctor_rate_unique');
        });

        // Doctor Groups/Units (For group/department/unit wise registration)
        Schema::create('doctor_groups', function (Blueprint $table) {
            $table->id('group_id');
            $table->unsignedBigInteger('hospital_id');
            $table->string('group_code', 20)->nullable();
            $table->string('group_name', 100);
            $table->enum('group_type', ['unit', 'department', 'specialty', 'team'])->default('unit');
            $table->unsignedBigInteger('department_id')->nullable();
            $table->unsignedBigInteger('head_doctor_id')->nullable(); // Group/Unit head
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('hospital_id')->references('hospital_id')->on('hospitals')->cascadeOnDelete();
            $table->foreign('department_id')->references('department_id')->on('departments')->nullOnDelete();
            $table->foreign('head_doctor_id')->references('doctor_id')->on('doctors')->nullOnDelete();
        });

        // Doctor Group Members
        Schema::create('doctor_group_members', function (Blueprint $table) {
            $table->id('member_id');
            $table->unsignedBigInteger('group_id');
            $table->unsignedBigInteger('doctor_id');
            $table->enum('role', ['head', 'senior', 'member', 'consultant'])->default('member');
            $table->boolean('can_consult')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('group_id')->references('group_id')->on('doctor_groups')->cascadeOnDelete();
            $table->foreign('doctor_id')->references('doctor_id')->on('doctors')->cascadeOnDelete();

            $table->unique(['group_id', 'doctor_id']);
        });

        // Entry Card Records (Track patient entry card purchases)
        Schema::create('entry_cards', function (Blueprint $table) {
            $table->id('entry_card_id');
            $table->unsignedBigInteger('hospital_id');
            $table->string('card_number', 30)->unique();
            $table->unsignedBigInteger('patient_id');
            $table->decimal('amount', 10, 2)->default(0);
            $table->date('issue_date');
            $table->date('valid_from');
            $table->date('valid_to');
            $table->enum('validity_type', ['one_time', 'daily', 'monthly', 'half_yearly', 'yearly', 'lifetime'])->default('yearly');
            $table->enum('status', ['active', 'expired', 'cancelled'])->default('active');
            $table->unsignedBigInteger('bill_id')->nullable();
            $table->unsignedBigInteger('issued_by')->nullable();
            $table->timestamps();

            $table->foreign('hospital_id')->references('hospital_id')->on('hospitals')->cascadeOnDelete();
            $table->foreign('patient_id')->references('patient_id')->on('patients')->cascadeOnDelete();
            $table->foreign('bill_id')->references('bill_id')->on('bills')->nullOnDelete();
            $table->foreign('issued_by')->references('user_id')->on('users')->nullOnDelete();
        });

        // Rate Change Requests (For doctor approval workflow)
        Schema::create('rate_change_requests', function (Blueprint $table) {
            $table->id('request_id');
            $table->unsignedBigInteger('hospital_id');
            $table->unsignedBigInteger('opd_id')->nullable();
            $table->unsignedBigInteger('service_id')->nullable();
            $table->decimal('original_rate', 10, 2);
            $table->decimal('requested_rate', 10, 2);
            $table->string('reason', 255)->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->unsignedBigInteger('requested_by');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->foreign('hospital_id')->references('hospital_id')->on('hospitals')->cascadeOnDelete();
            $table->foreign('opd_id')->references('opd_id')->on('opd_visits')->nullOnDelete();
            $table->foreign('service_id')->references('service_id')->on('services')->nullOnDelete();
            $table->foreign('requested_by')->references('user_id')->on('users');
            $table->foreign('approved_by')->references('user_id')->on('users')->nullOnDelete();
        });

        // OPD Time Slots (For slot-based appointments)
        Schema::create('opd_time_slots', function (Blueprint $table) {
            $table->id('slot_id');
            $table->unsignedBigInteger('hospital_id');
            $table->unsignedBigInteger('doctor_id')->nullable();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->enum('day_of_week', ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday']);
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('slot_duration_minutes')->default(15);
            $table->integer('max_patients_per_slot')->default(1);
            $table->integer('max_patients_per_session')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('hospital_id')->references('hospital_id')->on('hospitals')->cascadeOnDelete();
            $table->foreign('doctor_id')->references('doctor_id')->on('doctors')->nullOnDelete();
            $table->foreign('department_id')->references('department_id')->on('departments')->nullOnDelete();
        });

        // Add group_id to opd_visits for unit/group registration
        Schema::table('opd_visits', function (Blueprint $table) {
            $table->unsignedBigInteger('group_id')->nullable()->after('doctor_id');
            $table->unsignedBigInteger('entry_card_id')->nullable()->after('bill_id');
            $table->enum('charge_type', ['normal', 'day_emergency', 'night_emergency'])->default('normal')->after('visit_type');

            $table->foreign('group_id')->references('group_id')->on('doctor_groups')->nullOnDelete();
            $table->foreign('entry_card_id')->references('entry_card_id')->on('entry_cards')->nullOnDelete();
        });

        // Add group_id to appointments
        Schema::table('appointments', function (Blueprint $table) {
            $table->unsignedBigInteger('group_id')->nullable()->after('doctor_id');

            $table->foreign('group_id')->references('group_id')->on('doctor_groups')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropForeign(['group_id']);
            $table->dropColumn('group_id');
        });

        Schema::table('opd_visits', function (Blueprint $table) {
            $table->dropForeign(['group_id']);
            $table->dropForeign(['entry_card_id']);
            $table->dropColumn(['group_id', 'entry_card_id', 'charge_type']);
        });

        Schema::dropIfExists('opd_time_slots');
        Schema::dropIfExists('rate_change_requests');
        Schema::dropIfExists('entry_cards');
        Schema::dropIfExists('doctor_group_members');
        Schema::dropIfExists('doctor_groups');
        Schema::dropIfExists('doctor_opd_rates');
        Schema::dropIfExists('opd_configurations');
    }
};
