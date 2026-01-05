<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Departments
        Schema::create('departments', function (Blueprint $table) {
            $table->id('department_id');
            $table->string('department_code', 20)->unique();
            $table->string('department_name', 100);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Doctors
        Schema::create('doctors', function (Blueprint $table) {
            $table->id('doctor_id');
            $table->string('doctor_code', 20)->unique();
            $table->string('full_name', 100);
            $table->string('qualification', 200)->nullable();
            $table->string('specialization', 100)->nullable();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->string('mobile', 15)->nullable();
            $table->string('email', 100)->nullable();
            $table->decimal('consultation_fee', 10, 2)->default(0);
            $table->boolean('opd_available')->default(true);
            $table->boolean('ipd_available')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('department_id')->references('department_id')->on('departments')->nullOnDelete();
        });

        // Insurance Companies
        Schema::create('insurance_companies', function (Blueprint $table) {
            $table->id('insurance_id');
            $table->string('company_name', 100);
            $table->string('company_code', 20)->unique();
            $table->text('address')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('contact_person', 100)->nullable();
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Patients
        Schema::create('patients', function (Blueprint $table) {
            $table->id('patient_id');
            $table->string('pcd', 20)->unique(); // Patient Code
            $table->string('patient_name', 100);
            $table->string('guardian_name', 100)->nullable();
            $table->string('relation', 50)->nullable();
            $table->date('dob')->nullable();
            $table->integer('age')->nullable();
            $table->enum('age_unit', ['days', 'months', 'years'])->default('years');
            $table->enum('gender', ['male', 'female', 'other']);
            $table->enum('marital_status', ['single', 'married', 'divorced', 'widowed'])->nullable();
            $table->string('blood_group', 5)->nullable();
            $table->string('mobile', 15)->nullable();
            $table->string('phone', 15)->nullable();
            $table->string('email', 100)->nullable();
            $table->text('address')->nullable();
            $table->string('city', 50)->nullable();
            $table->string('state', 50)->nullable();
            $table->string('pincode', 10)->nullable();
            $table->string('aadhaar_number', 12)->nullable();
            $table->string('occupation', 50)->nullable();
            $table->string('nationality', 50)->default('Indian');
            $table->string('religion', 50)->nullable();
            $table->text('allergies')->nullable();
            $table->text('medical_history')->nullable();
            $table->string('emergency_contact_name', 100)->nullable();
            $table->string('emergency_contact_phone', 15)->nullable();
            $table->string('emergency_contact_relation', 50)->nullable();
            $table->unsignedBigInteger('insurance_id')->nullable();
            $table->string('insurance_policy_number', 50)->nullable();
            $table->date('registration_date')->nullable();
            $table->string('photo', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('insurance_id')->references('insurance_id')->on('insurance_companies')->nullOnDelete();
        });

        // Appointments
        Schema::create('appointments', function (Blueprint $table) {
            $table->id('appointment_id');
            $table->string('appointment_number', 20)->unique();
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('doctor_id')->nullable();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->date('appointment_date');
            $table->time('appointment_time');
            $table->enum('status', ['scheduled', 'confirmed', 'checked_in', 'completed', 'cancelled', 'no_show'])->default('scheduled');
            $table->text('reason')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('patient_id')->references('patient_id')->on('patients')->cascadeOnDelete();
            $table->foreign('doctor_id')->references('doctor_id')->on('doctors')->nullOnDelete();
            $table->foreign('department_id')->references('department_id')->on('departments')->nullOnDelete();
        });

        // OPD Visits
        Schema::create('opd_visits', function (Blueprint $table) {
            $table->id('opd_id');
            $table->string('opd_number', 20)->unique();
            $table->unsignedBigInteger('patient_id');
            $table->date('visit_date');
            $table->time('visit_time');
            $table->integer('token_number')->nullable();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->unsignedBigInteger('doctor_id')->nullable();
            $table->enum('visit_type', ['new', 'followup', 'referral', 'emergency'])->default('new');
            $table->string('referral_doctor', 100)->nullable();
            $table->text('chief_complaints')->nullable();
            $table->text('history_of_illness')->nullable();
            $table->text('examination_notes')->nullable();
            $table->text('diagnosis')->nullable();
            $table->text('advice')->nullable();
            $table->date('followup_date')->nullable();
            $table->text('followup_instructions')->nullable();
            $table->integer('vitals_bp_systolic')->nullable();
            $table->integer('vitals_bp_diastolic')->nullable();
            $table->integer('vitals_pulse')->nullable();
            $table->decimal('vitals_temperature', 4, 1)->nullable();
            $table->integer('vitals_spo2')->nullable();
            $table->decimal('vitals_weight', 5, 2)->nullable();
            $table->decimal('vitals_height', 5, 2)->nullable();
            $table->decimal('consultation_fee', 10, 2)->default(0);
            $table->enum('status', ['waiting', 'in_consultation', 'completed', 'cancelled'])->default('waiting');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('patient_id')->references('patient_id')->on('patients')->cascadeOnDelete();
            $table->foreign('department_id')->references('department_id')->on('departments')->nullOnDelete();
            $table->foreign('doctor_id')->references('doctor_id')->on('doctors')->nullOnDelete();
        });

        // Wards
        Schema::create('wards', function (Blueprint $table) {
            $table->id('ward_id');
            $table->string('ward_code', 20)->unique();
            $table->string('ward_name', 100);
            $table->enum('ward_type', ['general', 'semi_private', 'private', 'icu', 'nicu', 'picu', 'emergency'])->default('general');
            $table->integer('total_beds')->default(0);
            $table->decimal('charges_per_day', 10, 2)->default(0);
            $table->unsignedBigInteger('department_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('department_id')->references('department_id')->on('departments')->nullOnDelete();
        });

        // Beds
        Schema::create('beds', function (Blueprint $table) {
            $table->id('bed_id');
            $table->string('bed_number', 20);
            $table->unsignedBigInteger('ward_id');
            $table->enum('bed_type', ['standard', 'electric', 'icu', 'pediatric', 'maternity'])->default('standard');
            $table->enum('status', ['available', 'occupied', 'maintenance', 'reserved'])->default('available');
            $table->boolean('is_available')->default(true);
            $table->timestamps();

            $table->foreign('ward_id')->references('ward_id')->on('wards')->cascadeOnDelete();
            $table->unique(['bed_number', 'ward_id']);
        });

        // IPD Admissions
        Schema::create('ipd_admissions', function (Blueprint $table) {
            $table->id('ipd_id');
            $table->string('ipd_number', 20)->unique();
            $table->unsignedBigInteger('patient_id');
            $table->dateTime('admission_date');
            $table->enum('admission_type', ['emergency', 'elective', 'transfer', 'referral'])->default('elective');
            $table->unsignedBigInteger('department_id')->nullable();
            $table->unsignedBigInteger('admitting_doctor_id')->nullable();
            $table->unsignedBigInteger('treating_doctor_id')->nullable();
            $table->unsignedBigInteger('ward_id')->nullable();
            $table->unsignedBigInteger('bed_id')->nullable();
            $table->text('diagnosis_at_admission')->nullable();
            $table->boolean('mlc_case')->default(false);
            $table->string('mlc_number', 50)->nullable();
            $table->string('referral_from', 200)->nullable();
            $table->integer('expected_los')->nullable(); // Length of stay
            $table->boolean('insurance_applicable')->default(false);
            $table->unsignedBigInteger('insurance_id')->nullable();
            $table->string('insurance_approval_number', 50)->nullable();
            $table->decimal('approved_amount', 12, 2)->nullable();
            $table->string('attendant_name', 100)->nullable();
            $table->string('attendant_relation', 50)->nullable();
            $table->string('attendant_mobile', 15)->nullable();
            $table->dateTime('discharge_date')->nullable();
            $table->enum('discharge_type', ['normal', 'lama', 'absconded', 'referred', 'expired', 'dor'])->default('normal');
            $table->text('discharge_summary')->nullable();
            $table->text('final_diagnosis')->nullable();
            $table->string('condition_at_discharge', 100)->nullable();
            $table->text('followup_advice')->nullable();
            $table->enum('status', ['admitted', 'discharged', 'transferred'])->default('admitted');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('patient_id')->references('patient_id')->on('patients')->cascadeOnDelete();
            $table->foreign('department_id')->references('department_id')->on('departments')->nullOnDelete();
            $table->foreign('admitting_doctor_id')->references('doctor_id')->on('doctors')->nullOnDelete();
            $table->foreign('treating_doctor_id')->references('doctor_id')->on('doctors')->nullOnDelete();
            $table->foreign('ward_id')->references('ward_id')->on('wards')->nullOnDelete();
            $table->foreign('bed_id')->references('bed_id')->on('beds')->nullOnDelete();
            $table->foreign('insurance_id')->references('insurance_id')->on('insurance_companies')->nullOnDelete();
        });

        // Patient Vitals
        Schema::create('patient_vitals', function (Blueprint $table) {
            $table->id('vital_id');
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('ipd_id')->nullable();
            $table->unsignedBigInteger('opd_id')->nullable();
            $table->integer('bp_systolic')->nullable();
            $table->integer('bp_diastolic')->nullable();
            $table->integer('pulse')->nullable();
            $table->decimal('temperature', 4, 1)->nullable();
            $table->integer('respiratory_rate')->nullable();
            $table->integer('spo2')->nullable();
            $table->decimal('weight', 5, 2)->nullable();
            $table->decimal('height', 5, 2)->nullable();
            $table->decimal('bmi', 5, 2)->nullable();
            $table->integer('blood_sugar')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('recorded_by')->nullable();
            $table->timestamp('recorded_at')->useCurrent();
            $table->timestamps();

            $table->foreign('patient_id')->references('patient_id')->on('patients')->cascadeOnDelete();
            $table->foreign('ipd_id')->references('ipd_id')->on('ipd_admissions')->nullOnDelete();
            $table->foreign('opd_id')->references('opd_id')->on('opd_visits')->nullOnDelete();
        });

        // Lab Test Categories
        Schema::create('lab_categories', function (Blueprint $table) {
            $table->id('category_id');
            $table->string('category_name', 100);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Lab Tests
        Schema::create('lab_tests', function (Blueprint $table) {
            $table->id('test_id');
            $table->string('test_code', 20)->unique();
            $table->string('test_name', 100);
            $table->unsignedBigInteger('category_id')->nullable();
            $table->string('test_category', 50)->nullable();
            $table->decimal('rate', 10, 2)->default(0);
            $table->string('sample_type', 50)->nullable();
            $table->string('normal_range', 100)->nullable();
            $table->string('unit', 20)->nullable();
            $table->integer('tat_hours')->nullable(); // Turn around time
            $table->text('method')->nullable();
            $table->text('instructions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('category_id')->references('category_id')->on('lab_categories')->nullOnDelete();
        });

        // Lab Orders
        Schema::create('lab_orders', function (Blueprint $table) {
            $table->id('order_id');
            $table->string('order_number', 20)->unique();
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('opd_id')->nullable();
            $table->unsignedBigInteger('ipd_id')->nullable();
            $table->date('order_date');
            $table->unsignedBigInteger('referred_by')->nullable();
            $table->enum('priority', ['routine', 'urgent', 'stat'])->default('routine');
            $table->text('clinical_notes')->nullable();
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->enum('status', ['pending', 'sample_collected', 'processing', 'completed', 'cancelled'])->default('pending');
            $table->timestamp('completed_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('patient_id')->references('patient_id')->on('patients')->cascadeOnDelete();
            $table->foreign('opd_id')->references('opd_id')->on('opd_visits')->nullOnDelete();
            $table->foreign('ipd_id')->references('ipd_id')->on('ipd_admissions')->nullOnDelete();
            $table->foreign('referred_by')->references('doctor_id')->on('doctors')->nullOnDelete();
        });

        // Lab Order Details
        Schema::create('lab_order_details', function (Blueprint $table) {
            $table->id('detail_id');
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('test_id');
            $table->decimal('rate', 10, 2)->default(0);
            $table->string('result_value', 255)->nullable();
            $table->enum('result_status', ['normal', 'abnormal', 'critical'])->nullable();
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('verified_by')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->foreign('order_id')->references('order_id')->on('lab_orders')->cascadeOnDelete();
            $table->foreign('test_id')->references('test_id')->on('lab_tests')->cascadeOnDelete();
        });

        // Drug Categories
        Schema::create('drug_categories', function (Blueprint $table) {
            $table->id('category_id');
            $table->string('category_name', 100);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Drug Schedules
        Schema::create('drug_schedules', function (Blueprint $table) {
            $table->id('schedule_id');
            $table->string('schedule_name', 50);
            $table->text('description')->nullable();
            $table->boolean('is_controlled')->default(false);
            $table->timestamps();
        });

        // Drugs
        Schema::create('drugs', function (Blueprint $table) {
            $table->id('drug_id');
            $table->string('drug_code', 20)->unique();
            $table->string('drug_name', 200);
            $table->string('generic_name', 200)->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('schedule_id')->nullable();
            $table->string('manufacturer', 100)->nullable();
            $table->enum('drug_form', ['tablet', 'capsule', 'syrup', 'injection', 'cream', 'ointment', 'drops', 'inhaler', 'powder', 'other'])->default('tablet');
            $table->string('strength', 50)->nullable();
            $table->string('unit', 20)->nullable();
            $table->integer('pack_size')->default(1);
            $table->string('hsn_code', 20)->nullable();
            $table->decimal('gst_percent', 5, 2)->default(0);
            $table->decimal('mrp', 10, 2)->default(0);
            $table->decimal('purchase_rate', 10, 2)->default(0);
            $table->decimal('sale_rate', 10, 2)->default(0);
            $table->integer('reorder_level')->default(10);
            $table->string('storage_conditions', 100)->nullable();
            $table->boolean('is_narcotic')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('category_id')->references('category_id')->on('drug_categories')->nullOnDelete();
            $table->foreign('schedule_id')->references('schedule_id')->on('drug_schedules')->nullOnDelete();
        });

        // Drug Batches
        Schema::create('drug_batches', function (Blueprint $table) {
            $table->id('batch_id');
            $table->unsignedBigInteger('drug_id');
            $table->string('batch_number', 50);
            $table->date('expiry_date');
            $table->integer('quantity')->default(0);
            $table->decimal('purchase_rate', 10, 2)->default(0);
            $table->decimal('mrp', 10, 2)->default(0);
            $table->date('received_date')->nullable();
            $table->string('supplier', 100)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('drug_id')->references('drug_id')->on('drugs')->cascadeOnDelete();
        });

        // Pharmacy Sales
        Schema::create('pharmacy_sales', function (Blueprint $table) {
            $table->id('sale_id');
            $table->string('sale_number', 20)->unique();
            $table->unsignedBigInteger('patient_id')->nullable();
            $table->string('patient_name', 100)->nullable();
            $table->unsignedBigInteger('opd_id')->nullable();
            $table->unsignedBigInteger('ipd_id')->nullable();
            $table->unsignedBigInteger('prescription_id')->nullable();
            $table->date('sale_date');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->enum('payment_mode', ['cash', 'card', 'upi', 'credit', 'insurance'])->default('cash');
            $table->enum('payment_status', ['pending', 'paid', 'partial'])->default('paid');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('patient_id')->references('patient_id')->on('patients')->nullOnDelete();
            $table->foreign('opd_id')->references('opd_id')->on('opd_visits')->nullOnDelete();
            $table->foreign('ipd_id')->references('ipd_id')->on('ipd_admissions')->nullOnDelete();
        });

        // Pharmacy Sale Details
        Schema::create('pharmacy_sale_details', function (Blueprint $table) {
            $table->id('detail_id');
            $table->unsignedBigInteger('sale_id');
            $table->unsignedBigInteger('drug_id');
            $table->unsignedBigInteger('batch_id')->nullable();
            $table->integer('quantity');
            $table->decimal('rate', 10, 2);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('tax', 10, 2)->default(0);
            $table->decimal('amount', 10, 2);
            $table->timestamps();

            $table->foreign('sale_id')->references('sale_id')->on('pharmacy_sales')->cascadeOnDelete();
            $table->foreign('drug_id')->references('drug_id')->on('drugs')->cascadeOnDelete();
            $table->foreign('batch_id')->references('batch_id')->on('drug_batches')->nullOnDelete();
        });

        // Services
        Schema::create('services', function (Blueprint $table) {
            $table->id('service_id');
            $table->string('service_code', 20)->unique();
            $table->string('service_name', 100);
            $table->unsignedBigInteger('department_id')->nullable();
            $table->enum('service_type', ['opd', 'ipd', 'lab', 'radiology', 'procedure', 'other'])->default('other');
            $table->decimal('rate', 10, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('department_id')->references('department_id')->on('departments')->nullOnDelete();
        });

        // Bills
        Schema::create('bills', function (Blueprint $table) {
            $table->id('bill_id');
            $table->string('bill_number', 20)->unique();
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('opd_id')->nullable();
            $table->unsignedBigInteger('ipd_id')->nullable();
            $table->date('bill_date');
            $table->enum('bill_type', ['opd', 'ipd', 'pharmacy', 'lab', 'general'])->default('general');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('adjustment', 10, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->decimal('due_amount', 12, 2)->default(0);
            $table->enum('payment_status', ['pending', 'partial', 'paid', 'cancelled', 'refunded'])->default('pending');
            $table->unsignedBigInteger('insurance_claim_id')->nullable();
            $table->decimal('approved_amount', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('patient_id')->references('patient_id')->on('patients')->cascadeOnDelete();
            $table->foreign('opd_id')->references('opd_id')->on('opd_visits')->nullOnDelete();
            $table->foreign('ipd_id')->references('ipd_id')->on('ipd_admissions')->nullOnDelete();
        });

        // Bill Details
        Schema::create('bill_details', function (Blueprint $table) {
            $table->id('detail_id');
            $table->unsignedBigInteger('bill_id');
            $table->enum('item_type', ['service', 'procedure', 'consumable', 'room', 'medicine', 'lab', 'other'])->default('service');
            $table->unsignedBigInteger('item_id')->nullable();
            $table->string('item_name', 200);
            $table->integer('quantity')->default(1);
            $table->decimal('rate', 10, 2)->default(0);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('tax', 10, 2)->default(0);
            $table->decimal('amount', 10, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('bill_id')->references('bill_id')->on('bills')->cascadeOnDelete();
        });

        // Payments
        Schema::create('payments', function (Blueprint $table) {
            $table->id('payment_id');
            $table->string('payment_number', 20)->unique();
            $table->unsignedBigInteger('bill_id')->nullable();
            $table->unsignedBigInteger('patient_id');
            $table->decimal('amount', 12, 2);
            $table->date('payment_date');
            $table->enum('payment_mode', ['cash', 'card', 'upi', 'cheque', 'bank_transfer', 'insurance'])->default('cash');
            $table->string('reference_number', 50)->nullable();
            $table->string('transaction_id', 100)->nullable();
            $table->enum('status', ['pending', 'success', 'failed', 'refunded'])->default('success');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('received_by')->nullable();
            $table->timestamps();

            $table->foreign('bill_id')->references('bill_id')->on('bills')->nullOnDelete();
            $table->foreign('patient_id')->references('patient_id')->on('patients')->cascadeOnDelete();
        });

        // Insurance Claims
        Schema::create('insurance_claims', function (Blueprint $table) {
            $table->id('claim_id');
            $table->string('claim_number', 20)->unique();
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('bill_id');
            $table->unsignedBigInteger('insurance_id');
            $table->date('claim_date');
            $table->decimal('claimed_amount', 12, 2);
            $table->decimal('approved_amount', 12, 2)->nullable();
            $table->decimal('settled_amount', 12, 2)->nullable();
            $table->enum('status', ['submitted', 'under_review', 'approved', 'partially_approved', 'rejected', 'settled'])->default('submitted');
            $table->text('rejection_reason')->nullable();
            $table->date('settlement_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('patient_id')->references('patient_id')->on('patients')->cascadeOnDelete();
            $table->foreign('bill_id')->references('bill_id')->on('bills')->cascadeOnDelete();
            $table->foreign('insurance_id')->references('insurance_id')->on('insurance_companies')->cascadeOnDelete();
        });

        // Prescriptions
        Schema::create('prescriptions', function (Blueprint $table) {
            $table->id('prescription_id');
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('opd_id')->nullable();
            $table->unsignedBigInteger('ipd_id')->nullable();
            $table->unsignedBigInteger('doctor_id');
            $table->date('prescription_date');
            $table->text('diagnosis')->nullable();
            $table->text('instructions')->nullable();
            $table->date('valid_until')->nullable();
            $table->boolean('is_dispensed')->default(false);
            $table->timestamps();

            $table->foreign('patient_id')->references('patient_id')->on('patients')->cascadeOnDelete();
            $table->foreign('opd_id')->references('opd_id')->on('opd_visits')->nullOnDelete();
            $table->foreign('ipd_id')->references('ipd_id')->on('ipd_admissions')->nullOnDelete();
            $table->foreign('doctor_id')->references('doctor_id')->on('doctors')->cascadeOnDelete();
        });

        // Prescription Items
        Schema::create('prescription_items', function (Blueprint $table) {
            $table->id('item_id');
            $table->unsignedBigInteger('prescription_id');
            $table->unsignedBigInteger('drug_id')->nullable();
            $table->string('medicine_name', 200);
            $table->string('dosage', 100)->nullable();
            $table->string('frequency', 100)->nullable();
            $table->string('duration', 50)->nullable();
            $table->integer('quantity')->nullable();
            $table->text('instructions')->nullable();
            $table->timestamps();

            $table->foreign('prescription_id')->references('prescription_id')->on('prescriptions')->cascadeOnDelete();
            $table->foreign('drug_id')->references('drug_id')->on('drugs')->nullOnDelete();
        });

        // IPD Medications
        Schema::create('ipd_medications', function (Blueprint $table) {
            $table->id('medication_id');
            $table->unsignedBigInteger('ipd_id');
            $table->unsignedBigInteger('drug_id')->nullable();
            $table->string('medicine_name', 200);
            $table->string('dosage', 100)->nullable();
            $table->string('route', 50)->nullable();
            $table->string('frequency', 100)->nullable();
            $table->dateTime('start_date');
            $table->dateTime('end_date')->nullable();
            $table->text('instructions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('prescribed_by')->nullable();
            $table->timestamps();

            $table->foreign('ipd_id')->references('ipd_id')->on('ipd_admissions')->cascadeOnDelete();
            $table->foreign('drug_id')->references('drug_id')->on('drugs')->nullOnDelete();
            $table->foreign('prescribed_by')->references('doctor_id')->on('doctors')->nullOnDelete();
        });

        // Nursing Notes
        Schema::create('nursing_notes', function (Blueprint $table) {
            $table->id('note_id');
            $table->unsignedBigInteger('ipd_id');
            $table->dateTime('note_date');
            $table->enum('shift', ['morning', 'afternoon', 'night']);
            $table->text('notes');
            $table->text('observations')->nullable();
            $table->text('interventions')->nullable();
            $table->unsignedBigInteger('nurse_id')->nullable();
            $table->timestamps();

            $table->foreign('ipd_id')->references('ipd_id')->on('ipd_admissions')->cascadeOnDelete();
        });

        // Settings
        Schema::create('settings', function (Blueprint $table) {
            $table->id('setting_id');
            $table->string('setting_key', 50)->unique();
            $table->text('setting_value')->nullable();
            $table->string('setting_group', 50)->default('general');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Audit Logs
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id('log_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('action', 50);
            $table->string('table_name', 50)->nullable();
            $table->unsignedBigInteger('record_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamps();
        });

        // Add foreign key for users.department_id
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('department_id')->references('department_id')->on('departments')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
        });

        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('nursing_notes');
        Schema::dropIfExists('ipd_medications');
        Schema::dropIfExists('prescription_items');
        Schema::dropIfExists('prescriptions');
        Schema::dropIfExists('insurance_claims');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('bill_details');
        Schema::dropIfExists('bills');
        Schema::dropIfExists('services');
        Schema::dropIfExists('pharmacy_sale_details');
        Schema::dropIfExists('pharmacy_sales');
        Schema::dropIfExists('drug_batches');
        Schema::dropIfExists('drugs');
        Schema::dropIfExists('drug_schedules');
        Schema::dropIfExists('drug_categories');
        Schema::dropIfExists('lab_order_details');
        Schema::dropIfExists('lab_orders');
        Schema::dropIfExists('lab_tests');
        Schema::dropIfExists('lab_categories');
        Schema::dropIfExists('patient_vitals');
        Schema::dropIfExists('ipd_admissions');
        Schema::dropIfExists('beds');
        Schema::dropIfExists('wards');
        Schema::dropIfExists('opd_visits');
        Schema::dropIfExists('appointments');
        Schema::dropIfExists('patients');
        Schema::dropIfExists('insurance_companies');
        Schema::dropIfExists('doctors');
        Schema::dropIfExists('departments');
    }
};
