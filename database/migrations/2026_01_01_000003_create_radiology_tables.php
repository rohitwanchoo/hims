<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Radiology Modalities
        Schema::create('radiology_modalities', function (Blueprint $table) {
            $table->id('modality_id');
            $table->unsignedBigInteger('hospital_id');
            $table->string('modality_code', 20);
            $table->string('modality_name', 100);
            $table->text('description')->nullable();
            $table->string('room_number', 20)->nullable();
            $table->boolean('is_contrast_available')->default(false);
            $table->integer('default_tat_hours')->default(24);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('hospital_id')->references('hospital_id')->on('hospitals')->cascadeOnDelete();
            $table->unique(['hospital_id', 'modality_code']);
        });

        // Radiology Tests
        Schema::create('radiology_tests', function (Blueprint $table) {
            $table->id('radiology_test_id');
            $table->unsignedBigInteger('hospital_id');
            $table->unsignedBigInteger('modality_id');
            $table->string('test_code', 20);
            $table->string('test_name', 200);
            $table->string('body_part', 100)->nullable();
            $table->enum('laterality', ['left', 'right', 'bilateral', 'na'])->default('na');
            $table->string('cpt_code', 20)->nullable();
            $table->decimal('rate', 10, 2)->default(0);
            $table->boolean('contrast_required')->default(false);
            $table->decimal('contrast_rate', 10, 2)->default(0);
            $table->text('preparation_instructions')->nullable();
            $table->boolean('consent_required')->default(false);
            $table->integer('tat_hours')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('hospital_id')->references('hospital_id')->on('hospitals')->cascadeOnDelete();
            $table->foreign('modality_id')->references('modality_id')->on('radiology_modalities')->cascadeOnDelete();
            $table->unique(['hospital_id', 'test_code']);
        });

        // Radiology Orders
        Schema::create('radiology_orders', function (Blueprint $table) {
            $table->id('radiology_order_id');
            $table->unsignedBigInteger('hospital_id');
            $table->string('order_number', 20)->unique();
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('opd_id')->nullable();
            $table->unsignedBigInteger('ipd_id')->nullable();
            $table->date('order_date');
            $table->time('order_time');
            $table->unsignedBigInteger('referring_doctor_id')->nullable();
            $table->enum('priority', ['routine', 'urgent', 'stat'])->default('routine');
            $table->text('clinical_indication');
            $table->text('clinical_history')->nullable();
            $table->enum('pregnancy_status', ['yes', 'no', 'unknown', 'na'])->default('na');
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('net_amount', 12, 2)->default(0);
            $table->enum('status', ['ordered', 'scheduled', 'in_progress', 'completed', 'cancelled'])->default('ordered');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('hospital_id')->references('hospital_id')->on('hospitals')->cascadeOnDelete();
            $table->foreign('patient_id')->references('patient_id')->on('patients')->cascadeOnDelete();
            $table->foreign('opd_id')->references('opd_id')->on('opd_visits')->nullOnDelete();
            $table->foreign('ipd_id')->references('ipd_id')->on('ipd_admissions')->nullOnDelete();
            $table->foreign('referring_doctor_id')->references('doctor_id')->on('doctors')->nullOnDelete();
            $table->foreign('created_by')->references('user_id')->on('users')->nullOnDelete();
            $table->index(['hospital_id', 'status']);
            $table->index(['patient_id', 'order_date']);
        });

        // Radiology Order Details
        Schema::create('radiology_order_details', function (Blueprint $table) {
            $table->id('detail_id');
            $table->unsignedBigInteger('radiology_order_id');
            $table->unsignedBigInteger('radiology_test_id');
            $table->unsignedBigInteger('modality_id');
            $table->decimal('rate', 10, 2)->default(0);
            $table->boolean('with_contrast')->default(false);
            $table->decimal('contrast_rate', 10, 2)->default(0);
            $table->decimal('amount', 10, 2)->default(0);
            $table->datetime('scheduled_datetime')->nullable();
            $table->enum('status', ['ordered', 'scheduled', 'in_progress', 'completed', 'cancelled'])->default('ordered');
            $table->timestamps();

            $table->foreign('radiology_order_id')->references('radiology_order_id')->on('radiology_orders')->cascadeOnDelete();
            $table->foreign('radiology_test_id')->references('radiology_test_id')->on('radiology_tests')->cascadeOnDelete();
            $table->foreign('modality_id')->references('modality_id')->on('radiology_modalities')->cascadeOnDelete();
        });

        // Radiology Reports
        Schema::create('radiology_reports', function (Blueprint $table) {
            $table->id('report_id');
            $table->unsignedBigInteger('hospital_id');
            $table->unsignedBigInteger('radiology_order_id');
            $table->unsignedBigInteger('detail_id');
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('reporting_radiologist_id')->nullable();
            $table->date('report_date');
            $table->time('report_time');
            $table->text('technique')->nullable();
            $table->text('findings');
            $table->text('impression');
            $table->text('recommendations')->nullable();
            $table->boolean('critical_finding')->default(false);
            $table->boolean('critical_finding_communicated')->default(false);
            $table->string('communicated_to', 100)->nullable();
            $table->datetime('communicated_at')->nullable();
            $table->enum('report_status', ['draft', 'preliminary', 'final', 'addendum'])->default('draft');
            $table->unsignedBigInteger('verified_by')->nullable();
            $table->datetime('verified_at')->nullable();
            $table->string('report_pdf_path', 255)->nullable();
            $table->timestamps();

            $table->foreign('hospital_id')->references('hospital_id')->on('hospitals')->cascadeOnDelete();
            $table->foreign('radiology_order_id')->references('radiology_order_id')->on('radiology_orders')->cascadeOnDelete();
            $table->foreign('detail_id')->references('detail_id')->on('radiology_order_details')->cascadeOnDelete();
            $table->foreign('patient_id')->references('patient_id')->on('patients')->cascadeOnDelete();
            $table->foreign('reporting_radiologist_id')->references('doctor_id')->on('doctors')->nullOnDelete();
            $table->foreign('verified_by')->references('doctor_id')->on('doctors')->nullOnDelete();
        });

        // Radiology Images
        Schema::create('radiology_images', function (Blueprint $table) {
            $table->id('image_id');
            $table->unsignedBigInteger('hospital_id');
            $table->unsignedBigInteger('report_id');
            $table->unsignedBigInteger('detail_id');
            $table->string('image_type', 50);
            $table->string('file_path', 255);
            $table->string('thumbnail_path', 255)->nullable();
            $table->string('study_instance_uid', 100)->nullable();
            $table->string('series_instance_uid', 100)->nullable();
            $table->string('sop_instance_uid', 100)->nullable();
            $table->string('pacs_reference_id', 100)->nullable();
            $table->text('image_notes')->nullable();
            $table->timestamps();

            $table->foreign('hospital_id')->references('hospital_id')->on('hospitals')->cascadeOnDelete();
            $table->foreign('report_id')->references('report_id')->on('radiology_reports')->cascadeOnDelete();
            $table->foreign('detail_id')->references('detail_id')->on('radiology_order_details')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('radiology_images');
        Schema::dropIfExists('radiology_reports');
        Schema::dropIfExists('radiology_order_details');
        Schema::dropIfExists('radiology_orders');
        Schema::dropIfExists('radiology_tests');
        Schema::dropIfExists('radiology_modalities');
    }
};
