<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Enhance ipd_admissions table - add missing columns
        Schema::table('ipd_admissions', function (Blueprint $table) {
            if (!Schema::hasColumn('ipd_admissions', 'admission_time')) {
                $table->time('admission_time')->nullable()->after('admission_date');
            }
            if (!Schema::hasColumn('ipd_admissions', 'admission_source')) {
                $table->enum('admission_source', ['opd', 'emergency', 'direct', 'transfer'])->default('direct')->after('admission_type');
            }
            if (!Schema::hasColumn('ipd_admissions', 'opd_id')) {
                $table->unsignedBigInteger('opd_id')->nullable()->after('admission_source');
            }
            if (!Schema::hasColumn('ipd_admissions', 'provisional_diagnosis')) {
                $table->text('provisional_diagnosis')->nullable()->after('diagnosis_at_admission');
            }
            if (!Schema::hasColumn('ipd_admissions', 'icd_code')) {
                $table->string('icd_code', 20)->nullable()->after('provisional_diagnosis');
            }
            if (!Schema::hasColumn('ipd_admissions', 'admission_notes')) {
                $table->text('admission_notes')->nullable()->after('icd_code');
            }
            if (!Schema::hasColumn('ipd_admissions', 'treatment_plan')) {
                $table->text('treatment_plan')->nullable()->after('admission_notes');
            }
            // Consultant doctor
            if (!Schema::hasColumn('ipd_admissions', 'consultant_doctor_id')) {
                $table->unsignedBigInteger('consultant_doctor_id')->nullable()->after('treating_doctor_id');
            }
            // Emergency/MLC enhancements
            if (!Schema::hasColumn('ipd_admissions', 'is_emergency')) {
                $table->boolean('is_emergency')->default(false)->after('mlc_number');
            }
            if (!Schema::hasColumn('ipd_admissions', 'police_station')) {
                $table->string('police_station', 100)->nullable()->after('is_emergency');
            }
            if (!Schema::hasColumn('ipd_admissions', 'brought_by')) {
                $table->string('brought_by', 200)->nullable()->after('police_station');
            }
            // Insurance enhancements
            if (!Schema::hasColumn('ipd_admissions', 'tpa_name')) {
                $table->string('tpa_name', 150)->nullable()->after('insurance_approval_number');
            }
            if (!Schema::hasColumn('ipd_admissions', 'pre_auth_amount')) {
                $table->decimal('pre_auth_amount', 12, 2)->default(0)->after('approved_amount');
            }
            if (!Schema::hasColumn('ipd_admissions', 'scheme_type')) {
                $table->enum('scheme_type', ['none', 'ayushman', 'cghs', 'esi', 'state_scheme', 'corporate'])->default('none')->after('pre_auth_amount');
            }
            // Billing fields
            if (!Schema::hasColumn('ipd_admissions', 'total_charges')) {
                $table->decimal('total_charges', 12, 2)->default(0)->after('scheme_type');
            }
            if (!Schema::hasColumn('ipd_admissions', 'discount_amount')) {
                $table->decimal('discount_amount', 12, 2)->default(0)->after('total_charges');
            }
            if (!Schema::hasColumn('ipd_admissions', 'tax_amount')) {
                $table->decimal('tax_amount', 12, 2)->default(0)->after('discount_amount');
            }
            if (!Schema::hasColumn('ipd_admissions', 'net_amount')) {
                $table->decimal('net_amount', 12, 2)->default(0)->after('tax_amount');
            }
            if (!Schema::hasColumn('ipd_admissions', 'advance_amount')) {
                $table->decimal('advance_amount', 12, 2)->default(0)->after('net_amount');
            }
            if (!Schema::hasColumn('ipd_admissions', 'paid_amount')) {
                $table->decimal('paid_amount', 12, 2)->default(0)->after('advance_amount');
            }
            if (!Schema::hasColumn('ipd_admissions', 'due_amount')) {
                $table->decimal('due_amount', 12, 2)->default(0)->after('paid_amount');
            }
            if (!Schema::hasColumn('ipd_admissions', 'credit_limit')) {
                $table->decimal('credit_limit', 12, 2)->default(0)->after('due_amount');
            }
            if (!Schema::hasColumn('ipd_admissions', 'bill_id')) {
                $table->unsignedBigInteger('bill_id')->nullable()->after('credit_limit');
            }
            // Discharge enhancements
            if (!Schema::hasColumn('ipd_admissions', 'discharge_time')) {
                $table->time('discharge_time')->nullable()->after('discharge_date');
            }
            if (!Schema::hasColumn('ipd_admissions', 'discharged_by')) {
                $table->unsignedBigInteger('discharged_by')->nullable()->after('discharge_type');
            }
            if (!Schema::hasColumn('ipd_admissions', 'followup_date')) {
                $table->date('followup_date')->nullable()->after('followup_advice');
            }
            // Death tracking
            if (!Schema::hasColumn('ipd_admissions', 'death_date')) {
                $table->date('death_date')->nullable()->after('condition_at_discharge');
            }
            if (!Schema::hasColumn('ipd_admissions', 'death_time')) {
                $table->time('death_time')->nullable()->after('death_date');
            }
            if (!Schema::hasColumn('ipd_admissions', 'cause_of_death')) {
                $table->text('cause_of_death')->nullable()->after('death_time');
            }
        });

        // Enhance beds table
        Schema::table('beds', function (Blueprint $table) {
            if (!Schema::hasColumn('beds', 'room_number')) {
                $table->string('room_number', 20)->nullable()->after('bed_number');
            }
            if (!Schema::hasColumn('beds', 'floor')) {
                $table->string('floor', 20)->nullable()->after('room_number');
            }
            if (!Schema::hasColumn('beds', 'charges_per_day')) {
                $table->decimal('charges_per_day', 10, 2)->default(0)->after('bed_type');
            }
            if (!Schema::hasColumn('beds', 'is_isolation')) {
                $table->boolean('is_isolation')->default(false)->after('is_available');
            }
            if (!Schema::hasColumn('beds', 'is_ventilator')) {
                $table->boolean('is_ventilator')->default(false)->after('is_isolation');
            }
            if (!Schema::hasColumn('beds', 'current_patient_id')) {
                $table->unsignedBigInteger('current_patient_id')->nullable()->after('is_ventilator');
            }
            if (!Schema::hasColumn('beds', 'current_ipd_id')) {
                $table->unsignedBigInteger('current_ipd_id')->nullable()->after('current_patient_id');
            }
        });

        // Create bed_transfers table
        if (!Schema::hasTable('bed_transfers')) {
            Schema::create('bed_transfers', function (Blueprint $table) {
                $table->id('transfer_id');
                $table->unsignedBigInteger('hospital_id');
                $table->unsignedBigInteger('ipd_id');
                $table->unsignedBigInteger('from_bed_id');
                $table->unsignedBigInteger('to_bed_id');
                $table->unsignedBigInteger('from_ward_id');
                $table->unsignedBigInteger('to_ward_id');
                $table->dateTime('transfer_datetime');
                $table->string('transfer_reason', 255)->nullable();
                $table->text('remarks')->nullable();
                $table->unsignedBigInteger('transferred_by')->nullable();
                $table->timestamps();

                $table->foreign('hospital_id')->references('hospital_id')->on('hospitals')->onDelete('cascade');
                $table->foreign('ipd_id')->references('ipd_id')->on('ipd_admissions')->onDelete('cascade');
            });
        }

        // Create ipd_progress_notes table
        if (!Schema::hasTable('ipd_progress_notes')) {
            Schema::create('ipd_progress_notes', function (Blueprint $table) {
                $table->id('note_id');
                $table->unsignedBigInteger('hospital_id');
                $table->unsignedBigInteger('ipd_id');
                $table->unsignedBigInteger('doctor_id');
                $table->date('note_date');
                $table->time('note_time');
                $table->text('subjective')->nullable();
                $table->text('objective')->nullable();
                $table->text('assessment')->nullable();
                $table->text('plan')->nullable();
                $table->text('general_notes')->nullable();
                $table->text('instructions')->nullable();
                $table->enum('note_type', ['round', 'consultation', 'procedure', 'handover', 'other'])->default('round');
                $table->timestamps();

                $table->foreign('hospital_id')->references('hospital_id')->on('hospitals')->onDelete('cascade');
                $table->foreign('ipd_id')->references('ipd_id')->on('ipd_admissions')->onDelete('cascade');
                $table->foreign('doctor_id')->references('doctor_id')->on('doctors');
            });
        }

        // Create ipd_nursing_charts table
        if (!Schema::hasTable('ipd_nursing_charts')) {
            Schema::create('ipd_nursing_charts', function (Blueprint $table) {
                $table->id('chart_id');
                $table->unsignedBigInteger('hospital_id');
                $table->unsignedBigInteger('ipd_id');
                $table->unsignedBigInteger('nurse_id')->nullable();
                $table->date('chart_date');
                $table->enum('shift', ['morning', 'evening', 'night'])->default('morning');
                $table->integer('bp_systolic')->nullable();
                $table->integer('bp_diastolic')->nullable();
                $table->integer('pulse')->nullable();
                $table->decimal('temperature', 4, 1)->nullable();
                $table->integer('spo2')->nullable();
                $table->integer('respiratory_rate')->nullable();
                $table->decimal('blood_sugar', 5, 1)->nullable();
                $table->integer('oral_intake_ml')->nullable();
                $table->integer('iv_intake_ml')->nullable();
                $table->integer('urine_output_ml')->nullable();
                $table->integer('drain_output_ml')->nullable();
                $table->integer('vomit_ml')->nullable();
                $table->text('general_condition')->nullable();
                $table->text('pain_assessment')->nullable();
                $table->text('wound_assessment')->nullable();
                $table->text('iv_site_assessment')->nullable();
                $table->text('nursing_notes')->nullable();
                $table->text('medications_given')->nullable();
                $table->text('patient_response')->nullable();
                $table->timestamps();

                $table->foreign('hospital_id')->references('hospital_id')->on('hospitals')->onDelete('cascade');
                $table->foreign('ipd_id')->references('ipd_id')->on('ipd_admissions')->onDelete('cascade');
            });
        }

        // Create ipd_services table
        if (!Schema::hasTable('ipd_services')) {
            Schema::create('ipd_services', function (Blueprint $table) {
                $table->id('ipd_service_id');
                $table->unsignedBigInteger('hospital_id');
                $table->unsignedBigInteger('ipd_id');
                $table->date('service_date');
                $table->enum('service_type', ['bed', 'doctor_visit', 'nursing', 'procedure', 'lab', 'radiology', 'pharmacy', 'ot', 'icu', 'consumable', 'other'])->default('other');
                $table->unsignedBigInteger('service_id')->nullable();
                $table->string('service_name', 200);
                $table->integer('quantity')->default(1);
                $table->decimal('rate', 10, 2)->default(0);
                $table->decimal('amount', 12, 2)->default(0);
                $table->decimal('discount', 10, 2)->default(0);
                $table->decimal('net_amount', 12, 2)->default(0);
                $table->boolean('is_package')->default(false);
                $table->boolean('is_billed')->default(false);
                $table->unsignedBigInteger('bill_id')->nullable();
                $table->text('remarks')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->foreign('hospital_id')->references('hospital_id')->on('hospitals')->onDelete('cascade');
                $table->foreign('ipd_id')->references('ipd_id')->on('ipd_admissions')->onDelete('cascade');
            });
        }

        // Create ipd_investigations table
        if (!Schema::hasTable('ipd_investigations')) {
            Schema::create('ipd_investigations', function (Blueprint $table) {
                $table->id('investigation_id');
                $table->unsignedBigInteger('hospital_id');
                $table->unsignedBigInteger('ipd_id');
                $table->date('order_date');
                $table->time('order_time')->nullable();
                $table->enum('investigation_type', ['pathology', 'radiology', 'procedure'])->default('pathology');
                $table->unsignedBigInteger('test_id')->nullable();
                $table->string('investigation_name', 200);
                $table->enum('priority', ['routine', 'urgent', 'stat'])->default('routine');
                $table->text('clinical_notes')->nullable();
                $table->decimal('rate', 10, 2)->default(0);
                $table->enum('status', ['ordered', 'sample_collected', 'processing', 'completed', 'cancelled'])->default('ordered');
                $table->text('result')->nullable();
                $table->dateTime('result_datetime')->nullable();
                $table->unsignedBigInteger('ordered_by')->nullable();
                $table->unsignedBigInteger('lab_order_id')->nullable();
                $table->timestamps();

                $table->foreign('hospital_id')->references('hospital_id')->on('hospitals')->onDelete('cascade');
                $table->foreign('ipd_id')->references('ipd_id')->on('ipd_admissions')->onDelete('cascade');
            });
        }

        // Create ipd_medications table if not exists, or enhance it
        if (!Schema::hasTable('ipd_medications')) {
            Schema::create('ipd_medications', function (Blueprint $table) {
                $table->id('medication_id');
                $table->unsignedBigInteger('hospital_id');
                $table->unsignedBigInteger('ipd_id');
                $table->date('order_date');
                $table->time('order_time')->nullable();
                $table->unsignedBigInteger('drug_id')->nullable();
                $table->string('drug_name', 200);
                $table->string('dosage', 50)->nullable();
                $table->enum('route', ['oral', 'iv', 'im', 'sc', 'topical', 'inhalation', 'pr', 'sl'])->default('oral');
                $table->string('frequency', 50)->nullable();
                $table->integer('duration_days')->nullable();
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->integer('quantity_ordered')->default(0);
                $table->integer('quantity_issued')->default(0);
                $table->integer('quantity_returned')->default(0);
                $table->text('instructions')->nullable();
                $table->enum('status', ['ordered', 'issued', 'administered', 'stopped', 'completed'])->default('ordered');
                $table->unsignedBigInteger('ordered_by')->nullable();
                $table->timestamps();

                $table->foreign('hospital_id')->references('hospital_id')->on('hospitals')->onDelete('cascade');
                $table->foreign('ipd_id')->references('ipd_id')->on('ipd_admissions')->onDelete('cascade');
            });
        }

        // Create ipd_advance_payments table
        if (!Schema::hasTable('ipd_advance_payments')) {
            Schema::create('ipd_advance_payments', function (Blueprint $table) {
                $table->id('advance_id');
                $table->unsignedBigInteger('hospital_id');
                $table->unsignedBigInteger('ipd_id');
                $table->string('receipt_number', 50)->unique();
                $table->date('payment_date');
                $table->decimal('amount', 12, 2);
                $table->enum('payment_mode', ['cash', 'card', 'upi', 'neft', 'cheque', 'dd'])->default('cash');
                $table->string('reference_number', 100)->nullable();
                $table->text('remarks')->nullable();
                $table->boolean('is_refunded')->default(false);
                $table->decimal('refund_amount', 12, 2)->default(0);
                $table->date('refund_date')->nullable();
                $table->unsignedBigInteger('received_by')->nullable();
                $table->timestamps();

                $table->foreign('hospital_id')->references('hospital_id')->on('hospitals')->onDelete('cascade');
                $table->foreign('ipd_id')->references('ipd_id')->on('ipd_admissions')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ipd_advance_payments');
        Schema::dropIfExists('ipd_medications');
        Schema::dropIfExists('ipd_investigations');
        Schema::dropIfExists('ipd_services');
        Schema::dropIfExists('ipd_nursing_charts');
        Schema::dropIfExists('ipd_progress_notes');
        Schema::dropIfExists('bed_transfers');
    }
};
