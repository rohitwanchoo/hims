<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Skill Sets (Specialties)
        Schema::create('skill_sets', function (Blueprint $table) {
            $table->id('skill_set_id');
            $table->string('skill_code', 20)->unique();
            $table->string('skill_name', 100);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Skill Set Wise OPD Visit Validity
        Schema::create('skill_set_visit_validities', function (Blueprint $table) {
            $table->id('validity_id');
            $table->unsignedBigInteger('skill_set_id');
            $table->integer('followup_validity_days')->default(7);
            $table->integer('free_followup_validity_days')->default(3);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('skill_set_id')->references('skill_set_id')->on('skill_sets')->cascadeOnDelete();
        });

        // Health Check Packages
        Schema::create('health_packages', function (Blueprint $table) {
            $table->id('package_id');
            $table->string('package_code', 20)->unique();
            $table->string('package_name', 150);
            $table->text('description')->nullable();
            $table->enum('package_type', ['hospital', 'tpa', 'corporate'])->default('hospital');
            $table->unsignedBigInteger('client_id')->nullable(); // For TPA/Corporate packages
            $table->decimal('package_rate', 12, 2)->default(0);
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('client_id')->references('client_id')->on('clients')->nullOnDelete();
        });

        // Health Package Services (items included in package)
        Schema::create('health_package_services', function (Blueprint $table) {
            $table->id('package_service_id');
            $table->unsignedBigInteger('package_id');
            $table->unsignedBigInteger('service_id');
            $table->integer('quantity')->default(1);
            $table->boolean('is_mandatory')->default(true);
            $table->timestamps();

            $table->foreign('package_id')->references('package_id')->on('health_packages')->cascadeOnDelete();
            $table->foreign('service_id')->references('service_id')->on('services')->cascadeOnDelete();
        });

        // OPD Visit Services (services added to OPD visit)
        Schema::create('opd_visit_services', function (Blueprint $table) {
            $table->id('visit_service_id');
            $table->unsignedBigInteger('opd_id');
            $table->unsignedBigInteger('service_id');
            $table->integer('quantity')->default(1);
            $table->decimal('rate', 12, 2)->default(0);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('tax', 10, 2)->default(0);
            $table->decimal('amount', 12, 2)->default(0);
            $table->boolean('is_free_followup')->default(false);
            $table->enum('status', ['pending', 'completed', 'cancelled'])->default('pending');
            $table->timestamps();

            $table->foreign('opd_id')->references('opd_id')->on('opd_visits')->cascadeOnDelete();
            $table->foreign('service_id')->references('service_id')->on('services')->cascadeOnDelete();
        });

        // OPD Investigations (Lab/Radiology ordered from OPD)
        Schema::create('opd_investigations', function (Blueprint $table) {
            $table->id('investigation_id');
            $table->unsignedBigInteger('opd_id');
            $table->enum('investigation_type', ['pathology', 'radiology', 'procedure'])->default('pathology');
            $table->unsignedBigInteger('test_id')->nullable(); // lab_tests.test_id
            $table->unsignedBigInteger('service_id')->nullable(); // For procedures
            $table->string('investigation_name', 200);
            $table->decimal('rate', 12, 2)->default(0);
            $table->text('clinical_notes')->nullable();
            $table->enum('priority', ['routine', 'urgent', 'stat'])->default('routine');
            $table->enum('status', ['ordered', 'sample_collected', 'processing', 'completed', 'cancelled'])->default('ordered');
            $table->unsignedBigInteger('ordered_by')->nullable();
            $table->timestamp('ordered_at')->nullable();
            $table->timestamps();

            $table->foreign('opd_id')->references('opd_id')->on('opd_visits')->cascadeOnDelete();
            $table->foreign('test_id')->references('test_id')->on('lab_tests')->nullOnDelete();
            $table->foreign('service_id')->references('service_id')->on('services')->nullOnDelete();
        });

        // Add skill_set_id to doctors table
        Schema::table('doctors', function (Blueprint $table) {
            $table->unsignedBigInteger('skill_set_id')->nullable()->after('department_id');
            $table->foreign('skill_set_id')->references('skill_set_id')->on('skill_sets')->nullOnDelete();
        });

        // Add additional fields to opd_visits
        Schema::table('opd_visits', function (Blueprint $table) {
            $table->enum('registration_purpose', ['normal', 'direct', 'health_checkup', 'emergency'])->default('normal')->after('opd_number');
            $table->unsignedBigInteger('health_package_id')->nullable()->after('class_id');
            $table->boolean('is_mlc')->default(false)->after('consultation_fee');
            $table->string('mlc_number', 50)->nullable()->after('is_mlc');
            $table->string('police_station', 100)->nullable()->after('mlc_number');
            $table->boolean('is_insurance')->default(false)->after('police_station');
            $table->string('insurance_company_name', 150)->nullable()->after('is_insurance');
            $table->date('registration_expiry_date')->nullable()->after('visit_date');
            $table->boolean('is_free_followup')->default(false)->after('followup_instructions');
            $table->unsignedBigInteger('previous_visit_id')->nullable()->after('is_free_followup');
            $table->decimal('total_amount', 12, 2)->default(0)->after('consultation_fee');
            $table->decimal('discount_amount', 10, 2)->default(0)->after('total_amount');
            $table->decimal('tax_amount', 10, 2)->default(0)->after('discount_amount');
            $table->decimal('net_amount', 12, 2)->default(0)->after('tax_amount');
            $table->decimal('paid_amount', 12, 2)->default(0)->after('net_amount');
            $table->decimal('due_amount', 12, 2)->default(0)->after('paid_amount');
            $table->enum('payment_status', ['pending', 'partial', 'paid', 'credit'])->default('pending')->after('due_amount');
            $table->unsignedBigInteger('bill_id')->nullable()->after('payment_status');

            $table->foreign('health_package_id')->references('package_id')->on('health_packages')->nullOnDelete();
            $table->foreign('previous_visit_id')->references('opd_id')->on('opd_visits')->nullOnDelete();
        });

        // Add additional fields to appointments
        Schema::table('appointments', function (Blueprint $table) {
            $table->unsignedBigInteger('skill_set_id')->nullable()->after('department_id');
            $table->unsignedBigInteger('class_id')->nullable()->after('skill_set_id');
            $table->unsignedBigInteger('reference_doctor_id')->nullable()->after('class_id');
            $table->enum('appointment_type', ['consultation', 'followup', 'health_checkup', 'procedure'])->default('consultation')->after('appointment_time');
            $table->integer('slot_number')->nullable()->after('appointment_type');
            $table->time('slot_start_time')->nullable()->after('slot_number');
            $table->time('slot_end_time')->nullable()->after('slot_start_time');
            $table->unsignedBigInteger('opd_id')->nullable()->after('notes');
            $table->timestamp('checked_in_at')->nullable()->after('opd_id');
            $table->timestamp('consultation_started_at')->nullable()->after('checked_in_at');
            $table->timestamp('consultation_ended_at')->nullable()->after('consultation_started_at');

            $table->foreign('skill_set_id')->references('skill_set_id')->on('skill_sets')->nullOnDelete();
            $table->foreign('class_id')->references('class_id')->on('classes')->nullOnDelete();
            $table->foreign('reference_doctor_id')->references('reference_doctor_id')->on('reference_doctors')->nullOnDelete();
            $table->foreign('opd_id')->references('opd_id')->on('opd_visits')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropForeign(['skill_set_id']);
            $table->dropForeign(['class_id']);
            $table->dropForeign(['reference_doctor_id']);
            $table->dropForeign(['opd_id']);
            $table->dropColumn([
                'skill_set_id', 'class_id', 'reference_doctor_id', 'appointment_type',
                'slot_number', 'slot_start_time', 'slot_end_time', 'opd_id',
                'checked_in_at', 'consultation_started_at', 'consultation_ended_at'
            ]);
        });

        Schema::table('opd_visits', function (Blueprint $table) {
            $table->dropForeign(['health_package_id']);
            $table->dropForeign(['previous_visit_id']);
            $table->dropColumn([
                'registration_purpose', 'health_package_id', 'is_mlc', 'mlc_number',
                'police_station', 'is_insurance', 'insurance_company_name',
                'registration_expiry_date', 'is_free_followup', 'previous_visit_id',
                'total_amount', 'discount_amount', 'tax_amount', 'net_amount',
                'paid_amount', 'due_amount', 'payment_status', 'bill_id'
            ]);
        });

        Schema::table('doctors', function (Blueprint $table) {
            $table->dropForeign(['skill_set_id']);
            $table->dropColumn('skill_set_id');
        });

        Schema::dropIfExists('opd_investigations');
        Schema::dropIfExists('opd_visit_services');
        Schema::dropIfExists('health_package_services');
        Schema::dropIfExists('health_packages');
        Schema::dropIfExists('skill_set_visit_validities');
        Schema::dropIfExists('skill_sets');
    }
};
