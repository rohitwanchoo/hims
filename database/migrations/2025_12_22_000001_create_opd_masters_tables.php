<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Reference Doctors (External referring doctors)
        Schema::create('reference_doctors', function (Blueprint $table) {
            $table->id('reference_doctor_id');
            $table->string('doctor_code', 20)->unique();
            $table->string('full_name', 100);
            $table->string('qualification', 200)->nullable();
            $table->string('skill_set', 200)->nullable(); // specialty
            $table->string('registration_no', 50)->nullable();
            $table->string('hospital_name', 200)->nullable();
            $table->string('group_name', 100)->nullable(); // Reference Doctor Group
            $table->text('address')->nullable();
            $table->string('city', 50)->nullable();
            $table->string('state', 50)->nullable();
            $table->string('pincode', 10)->nullable();
            $table->string('mobile', 15)->nullable();
            $table->string('phone', 15)->nullable();
            $table->string('email', 100)->nullable();
            $table->decimal('commission_percent', 5, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Clients (TPA, Insurance Companies, Corporate tie-ups)
        Schema::create('clients', function (Blueprint $table) {
            $table->id('client_id');
            $table->string('client_code', 20)->unique();
            $table->string('client_name', 150);
            $table->string('contact_person', 100)->nullable();
            $table->string('ledger_name', 100)->nullable(); // For accounting
            $table->text('address')->nullable();
            $table->string('city', 50)->nullable();
            $table->string('state', 50)->nullable();
            $table->string('pincode', 10)->nullable();
            $table->string('mobile', 15)->nullable();
            $table->string('phone', 15)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('website', 150)->nullable();
            $table->enum('category', ['insurance', 'corporate', 'trust', 'charity', 'government', 'other'])->default('insurance');
            $table->enum('rate_based_on', ['standard', 'normal', 'increase', 'decrease', 'cashless_price_list'])->default('standard');
            $table->decimal('rate_adjustment_percent', 5, 2)->default(0); // For increase/decrease
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->decimal('credit_limit', 12, 2)->default(0);
            $table->integer('credit_days')->default(30);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Classes (Patient classification - General, Cashless, Co-Pay, etc.)
        Schema::create('classes', function (Blueprint $table) {
            $table->id('class_id');
            $table->string('class_code', 20)->unique();
            $table->string('class_name', 100);
            $table->text('description')->nullable();
            $table->unsignedBigInteger('client_id')->nullable(); // Associated client/TPA
            $table->string('discount_ledger', 100)->nullable(); // For accounting
            $table->boolean('is_cashless')->default(false);
            $table->boolean('apply_service_charges_on_cashless')->default(false);
            $table->boolean('is_cashless_reimbursement')->default(false);
            $table->boolean('is_copay')->default(false);
            $table->decimal('copay_patient_percent', 5, 2)->default(0); // Patient pays this %
            $table->decimal('copay_tpa_percent', 5, 2)->default(0); // TPA pays this %
            $table->enum('cashless_applicable_on', ['opd', 'ipd', 'both'])->default('both');
            $table->boolean('pharmacy_cash')->default(false); // Pharmacy on cash for cashless
            $table->boolean('apply_token')->default(false);
            $table->boolean('ipd_for_new')->default(false); // Enable IPD cashless rate list
            $table->boolean('service_tax_applicable')->default(false);
            $table->enum('service_tax_on', ['opd', 'ipd', 'health_checkup'])->nullable();
            $table->enum('service_tax_bill_type', ['cash', 'credit', 'both'])->nullable();
            $table->integer('grace_period_days')->default(0);
            $table->boolean('prior_approval_required')->default(false);
            $table->decimal('prior_approval_limit', 12, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('client_id')->references('client_id')->on('clients')->nullOnDelete();
        });

        // Cashless Price List (Service rates for different classes)
        Schema::create('cashless_price_lists', function (Blueprint $table) {
            $table->id('price_list_id');
            $table->unsignedBigInteger('class_id');
            $table->unsignedBigInteger('service_id');
            $table->decimal('opd_rate', 12, 2)->default(0);
            $table->decimal('ipd_rate', 12, 2)->default(0);
            $table->decimal('day_emergency_rate', 12, 2)->default(0);
            $table->decimal('night_emergency_rate', 12, 2)->default(0);
            $table->boolean('is_approval_required')->default(false);
            $table->boolean('is_not_applicable')->default(false); // Service not provided for this class
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('class_id')->references('class_id')->on('classes')->cascadeOnDelete();
            $table->foreign('service_id')->references('service_id')->on('services')->cascadeOnDelete();
            $table->unique(['class_id', 'service_id', 'effective_from'], 'unique_class_service_effective');
        });

        // Ward Wise Cost Addition (WWCA) for IPD
        Schema::create('ward_wise_cost_additions', function (Blueprint $table) {
            $table->id('wwca_id');
            $table->unsignedBigInteger('class_id');
            $table->unsignedBigInteger('service_id');
            $table->unsignedBigInteger('ward_id');
            $table->decimal('rate', 12, 2)->default(0);
            $table->enum('rate_type', ['fixed', 'percentage'])->default('fixed');
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('class_id')->references('class_id')->on('classes')->cascadeOnDelete();
            $table->foreign('service_id')->references('service_id')->on('services')->cascadeOnDelete();
            $table->foreign('ward_id')->references('ward_id')->on('wards')->cascadeOnDelete();
            $table->unique(['class_id', 'service_id', 'ward_id', 'effective_from'], 'unique_wwca');
        });

        // Mode of Payment Master
        Schema::create('payment_modes', function (Blueprint $table) {
            $table->id('payment_mode_id');
            $table->string('mode_code', 20)->unique();
            $table->string('mode_name', 100);
            $table->text('description')->nullable();
            $table->boolean('has_financial_details')->default(false);
            $table->boolean('use_for_refund')->default(false);
            $table->boolean('is_title_mandatory')->default(false);
            $table->enum('value_type', ['text', 'number', 'date'])->default('text');
            $table->integer('value_max_length')->nullable();
            $table->boolean('post_charges_applicable')->default(false);
            $table->decimal('post_charge_percent', 5, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Payment Mode Title Details (e.g., Cheque No, Card No, etc.)
        Schema::create('payment_mode_details', function (Blueprint $table) {
            $table->id('detail_id');
            $table->unsignedBigInteger('payment_mode_id');
            $table->string('field_name', 50);
            $table->string('field_label', 100);
            $table->enum('field_type', ['text', 'number', 'date'])->default('text');
            $table->boolean('is_required')->default(false);
            $table->integer('max_length')->nullable();
            $table->integer('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('payment_mode_id')->references('payment_mode_id')->on('payment_modes')->cascadeOnDelete();
        });

        // Cancel Reason Master
        Schema::create('cancel_reasons', function (Blueprint $table) {
            $table->id('cancel_reason_id');
            $table->string('reason_code', 20)->unique();
            $table->string('reason_name', 150);
            $table->text('description')->nullable();
            $table->boolean('is_auto_process')->default(false);
            $table->enum('applicable_for', ['opd', 'ipd', 'both'])->default('both');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Vaccination Master
        Schema::create('vaccinations', function (Blueprint $table) {
            $table->id('vaccination_id');
            $table->string('vaccine_code', 20)->unique();
            $table->string('vaccine_name', 150);
            $table->string('manufacturer', 100)->nullable();
            $table->integer('schedule_value')->default(0); // 0 = at birth
            $table->enum('schedule_type', ['days', 'months', 'years'])->default('months');
            $table->string('schedule_text', 100)->nullable(); // e.g., "At Birth", "6 Weeks"
            $table->integer('dose_number')->default(1);
            $table->integer('total_doses')->default(1);
            $table->text('description')->nullable();
            $table->text('instructions')->nullable();
            $table->decimal('rate', 10, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Patient Vaccination Records
        Schema::create('patient_vaccinations', function (Blueprint $table) {
            $table->id('patient_vaccination_id');
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('vaccination_id');
            $table->date('scheduled_date')->nullable();
            $table->date('administered_date')->nullable();
            $table->string('batch_number', 50)->nullable();
            $table->unsignedBigInteger('administered_by')->nullable();
            $table->enum('status', ['scheduled', 'administered', 'missed', 'cancelled'])->default('scheduled');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('patient_id')->references('patient_id')->on('patients')->cascadeOnDelete();
            $table->foreign('vaccination_id')->references('vaccination_id')->on('vaccinations')->cascadeOnDelete();
        });

        // Add missing fields to patients table based on requirements
        Schema::table('patients', function (Blueprint $table) {
            $table->unsignedBigInteger('class_id')->nullable()->after('insurance_id');
            $table->unsignedBigInteger('reference_doctor_id')->nullable()->after('class_id');
            $table->enum('category', ['general', 'vip'])->default('general')->after('religion');
            $table->enum('charges_type', ['normal', 'day_emergency', 'night_emergency'])->default('normal')->after('category');
            $table->string('pan_number', 10)->nullable()->after('aadhaar_number');
            $table->string('relation_with_ip', 50)->nullable()->after('pan_number'); // Relation with Insurance Policyholder
            $table->string('ip_name', 100)->nullable()->after('relation_with_ip'); // Insurance Policyholder Name
            $table->string('cashless_referral_no', 50)->nullable()->after('insurance_policy_number');
            $table->string('tpa_id', 50)->nullable()->after('cashless_referral_no');
            $table->boolean('subscribe_sms')->default(true)->after('mobile');
            $table->boolean('is_bpl')->default(false)->after('nationality');
            $table->boolean('is_foreigner')->default(false)->after('is_bpl');
            $table->boolean('is_urgent')->default(false)->after('is_foreigner');
            $table->string('area', 100)->nullable()->after('address');
            $table->string('district', 50)->nullable()->after('city');
            $table->string('country', 50)->default('India')->after('state');
            $table->text('permanent_address')->nullable()->after('country');
            $table->string('permanent_city', 50)->nullable()->after('permanent_address');
            $table->string('permanent_district', 50)->nullable()->after('permanent_city');
            $table->string('permanent_state', 50)->nullable()->after('permanent_district');
            $table->string('permanent_country', 50)->default('India')->after('permanent_state');
            $table->string('permanent_pincode', 10)->nullable()->after('permanent_country');
            $table->text('documents')->nullable()->after('photo'); // JSON for multiple documents

            $table->foreign('class_id')->references('class_id')->on('classes')->nullOnDelete();
            $table->foreign('reference_doctor_id')->references('reference_doctor_id')->on('reference_doctors')->nullOnDelete();
        });

        // Add reference_doctor_id to opd_visits
        Schema::table('opd_visits', function (Blueprint $table) {
            $table->unsignedBigInteger('reference_doctor_id')->nullable()->after('doctor_id');
            $table->unsignedBigInteger('class_id')->nullable()->after('reference_doctor_id');
            $table->unsignedBigInteger('cancel_reason_id')->nullable()->after('status');

            $table->foreign('reference_doctor_id')->references('reference_doctor_id')->on('reference_doctors')->nullOnDelete();
            $table->foreign('class_id')->references('class_id')->on('classes')->nullOnDelete();
            $table->foreign('cancel_reason_id')->references('cancel_reason_id')->on('cancel_reasons')->nullOnDelete();
        });

        // Add class_id and cancel_reason_id to ipd_admissions
        Schema::table('ipd_admissions', function (Blueprint $table) {
            $table->unsignedBigInteger('class_id')->nullable()->after('insurance_id');
            $table->unsignedBigInteger('reference_doctor_id')->nullable()->after('class_id');
            $table->unsignedBigInteger('cancel_reason_id')->nullable()->after('status');

            $table->foreign('class_id')->references('class_id')->on('classes')->nullOnDelete();
            $table->foreign('reference_doctor_id')->references('reference_doctor_id')->on('reference_doctors')->nullOnDelete();
            $table->foreign('cancel_reason_id')->references('cancel_reason_id')->on('cancel_reasons')->nullOnDelete();
        });

        // Update Services table with additional fields from requirements
        Schema::table('services', function (Blueprint $table) {
            $table->boolean('is_health_checkup')->default(false)->after('service_type');
            $table->boolean('apply_service_charges')->default(false)->after('is_health_checkup');
            $table->boolean('use_for_service_bill')->default(true)->after('apply_service_charges');
            $table->boolean('is_special_service')->default(false)->after('use_for_service_bill');
            $table->string('ledger_name', 100)->nullable()->after('service_name');
            $table->string('sub_service_type', 100)->nullable()->after('service_type');
            $table->enum('service_used_for', ['opd', 'ipd', 'direct', 'all'])->default('all')->after('sub_service_type');
            $table->boolean('is_followup_service')->default(false)->after('service_used_for');
            $table->boolean('is_free_followup')->default(false)->after('is_followup_service');
            $table->boolean('service_tax_applicable')->default(false)->after('is_free_followup');
            $table->string('service_tax_plan', 50)->nullable()->after('service_tax_applicable');
            $table->decimal('night_emergency_rate', 10, 2)->default(0)->after('rate');
            $table->decimal('day_emergency_rate', 10, 2)->default(0)->after('night_emergency_rate');
            $table->boolean('is_premium_service')->default(false)->after('day_emergency_rate');
            $table->boolean('use_for_srn')->default(false)->after('is_premium_service'); // Service Receipt Note
            $table->date('effective_from')->nullable()->after('use_for_srn');
            $table->date('effective_to')->nullable()->after('effective_from');
        });

        // Update Departments table with additional fields
        Schema::table('departments', function (Blueprint $table) {
            $table->enum('service_type', ['normal', 'direct', 'both'])->default('both')->after('description');
            $table->boolean('imparted_service')->default(false)->after('service_type'); // Can provide own services
        });
    }

    public function down(): void
    {
        // Remove foreign keys and columns from ipd_admissions
        Schema::table('ipd_admissions', function (Blueprint $table) {
            $table->dropForeign(['class_id']);
            $table->dropForeign(['reference_doctor_id']);
            $table->dropForeign(['cancel_reason_id']);
            $table->dropColumn(['class_id', 'reference_doctor_id', 'cancel_reason_id']);
        });

        // Remove foreign keys and columns from opd_visits
        Schema::table('opd_visits', function (Blueprint $table) {
            $table->dropForeign(['reference_doctor_id']);
            $table->dropForeign(['class_id']);
            $table->dropForeign(['cancel_reason_id']);
            $table->dropColumn(['reference_doctor_id', 'class_id', 'cancel_reason_id']);
        });

        // Remove columns from patients
        Schema::table('patients', function (Blueprint $table) {
            $table->dropForeign(['class_id']);
            $table->dropForeign(['reference_doctor_id']);
            $table->dropColumn([
                'class_id', 'reference_doctor_id', 'category', 'charges_type',
                'pan_number', 'relation_with_ip', 'ip_name', 'cashless_referral_no',
                'tpa_id', 'subscribe_sms', 'is_bpl', 'is_foreigner', 'is_urgent',
                'area', 'district', 'country', 'permanent_address', 'permanent_city',
                'permanent_district', 'permanent_state', 'permanent_country',
                'permanent_pincode', 'documents'
            ]);
        });

        // Remove columns from services
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn([
                'is_health_checkup', 'apply_service_charges', 'use_for_service_bill',
                'is_special_service', 'ledger_name', 'sub_service_type', 'service_used_for',
                'is_followup_service', 'is_free_followup', 'service_tax_applicable',
                'service_tax_plan', 'night_emergency_rate', 'day_emergency_rate',
                'is_premium_service', 'use_for_srn', 'effective_from', 'effective_to'
            ]);
        });

        // Remove columns from departments
        Schema::table('departments', function (Blueprint $table) {
            $table->dropColumn(['service_type', 'imparted_service']);
        });

        Schema::dropIfExists('patient_vaccinations');
        Schema::dropIfExists('vaccinations');
        Schema::dropIfExists('cancel_reasons');
        Schema::dropIfExists('payment_mode_details');
        Schema::dropIfExists('payment_modes');
        Schema::dropIfExists('ward_wise_cost_additions');
        Schema::dropIfExists('cashless_price_lists');
        Schema::dropIfExists('classes');
        Schema::dropIfExists('clients');
        Schema::dropIfExists('reference_doctors');
    }
};
