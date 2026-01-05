<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Patient Documents
        Schema::create('patient_documents', function (Blueprint $table) {
            $table->id('document_id');
            $table->unsignedBigInteger('hospital_id');
            $table->unsignedBigInteger('patient_id');
            $table->enum('document_type', ['lab_report', 'radiology_report', 'discharge_summary', 'prescription', 'consent', 'id_proof', 'insurance', 'photo', 'operative_notes', 'progress_notes', 'other']);
            $table->string('document_category', 50)->nullable();
            $table->string('source_type', 50)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->date('document_date');
            $table->string('document_title', 200);
            $table->text('description')->nullable();
            $table->string('file_path', 255);
            $table->string('file_name', 200);
            $table->string('file_type', 50);
            $table->unsignedBigInteger('file_size');
            $table->boolean('is_confidential')->default(false);
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->unsignedBigInteger('verified_by')->nullable();
            $table->datetime('verified_at')->nullable();
            $table->boolean('is_archived')->default(false);
            $table->datetime('archived_at')->nullable();
            $table->unsignedBigInteger('archived_by')->nullable();
            $table->timestamps();

            $table->foreign('hospital_id')->references('hospital_id')->on('hospitals')->cascadeOnDelete();
            $table->foreign('patient_id')->references('patient_id')->on('patients')->cascadeOnDelete();
            $table->foreign('uploaded_by')->references('user_id')->on('users')->nullOnDelete();
            $table->foreign('verified_by')->references('user_id')->on('users')->nullOnDelete();
            $table->foreign('archived_by')->references('user_id')->on('users')->nullOnDelete();
            $table->index(['hospital_id', 'patient_id', 'document_type']);
        });

        // MRD File Movements
        Schema::create('mrd_file_movements', function (Blueprint $table) {
            $table->id('movement_id');
            $table->unsignedBigInteger('hospital_id');
            $table->unsignedBigInteger('patient_id');
            $table->string('file_number', 30);
            $table->enum('movement_type', ['issued', 'returned', 'transferred', 'archived', 'destroyed']);
            $table->string('from_location', 100)->nullable();
            $table->string('to_location', 100);
            $table->string('purpose', 200);
            $table->string('issued_to', 100);
            $table->unsignedBigInteger('issued_by');
            $table->datetime('issued_at');
            $table->date('expected_return_date')->nullable();
            $table->datetime('returned_at')->nullable();
            $table->unsignedBigInteger('returned_by')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->foreign('hospital_id')->references('hospital_id')->on('hospitals')->cascadeOnDelete();
            $table->foreign('patient_id')->references('patient_id')->on('patients')->cascadeOnDelete();
            $table->foreign('issued_by')->references('user_id')->on('users')->cascadeOnDelete();
            $table->foreign('returned_by')->references('user_id')->on('users')->nullOnDelete();
            $table->index(['hospital_id', 'file_number']);
        });

        // Medical Record Requests
        Schema::create('medical_record_requests', function (Blueprint $table) {
            $table->id('request_id');
            $table->unsignedBigInteger('hospital_id');
            $table->unsignedBigInteger('patient_id');
            $table->string('request_number', 20)->unique();
            $table->enum('requester_type', ['patient', 'doctor', 'insurance', 'legal', 'research', 'other']);
            $table->string('requester_name', 100);
            $table->string('requester_contact', 50);
            $table->string('requester_organization', 100)->nullable();
            $table->text('request_purpose');
            $table->json('records_requested');
            $table->date('date_range_from')->nullable();
            $table->date('date_range_to')->nullable();
            $table->date('request_date');
            $table->enum('priority', ['normal', 'urgent'])->default('normal');
            $table->enum('consent_type', ['patient', 'legal_guardian', 'court_order']);
            $table->string('consent_document_path', 255)->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'processing', 'completed', 'cancelled'])->default('pending');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->datetime('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->datetime('completed_at')->nullable();
            $table->enum('delivery_method', ['email', 'print', 'pickup', 'courier'])->default('pickup');
            $table->text('delivery_details')->nullable();
            $table->decimal('charges', 10, 2)->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('hospital_id')->references('hospital_id')->on('hospitals')->cascadeOnDelete();
            $table->foreign('patient_id')->references('patient_id')->on('patients')->cascadeOnDelete();
            $table->foreign('approved_by')->references('user_id')->on('users')->nullOnDelete();
            $table->foreign('created_by')->references('user_id')->on('users')->nullOnDelete();
        });

        // Patient Consents
        Schema::create('patient_consents', function (Blueprint $table) {
            $table->id('consent_id');
            $table->unsignedBigInteger('hospital_id');
            $table->unsignedBigInteger('patient_id');
            $table->enum('consent_type', ['treatment', 'surgery', 'anesthesia', 'blood_transfusion', 'research', 'data_sharing', 'photography', 'hiv_test', 'medico_legal', 'discharge_against_advice', 'high_risk_procedure', 'other']);
            $table->string('consent_for', 200);
            $table->string('reference_type', 50)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->date('consent_date');
            $table->time('consent_time');
            $table->boolean('is_given')->default(true);
            $table->string('given_by', 100);
            $table->string('relationship', 50)->nullable();
            $table->string('witness_name', 100)->nullable();
            $table->unsignedBigInteger('doctor_id')->nullable();
            $table->string('consent_form_path', 255)->nullable();
            $table->string('digital_signature_path', 255)->nullable();
            $table->text('notes')->nullable();
            $table->datetime('revoked_at')->nullable();
            $table->string('revoked_by', 100)->nullable();
            $table->text('revocation_reason')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('hospital_id')->references('hospital_id')->on('hospitals')->cascadeOnDelete();
            $table->foreign('patient_id')->references('patient_id')->on('patients')->cascadeOnDelete();
            $table->foreign('doctor_id')->references('doctor_id')->on('doctors')->nullOnDelete();
            $table->foreign('created_by')->references('user_id')->on('users')->nullOnDelete();
            $table->index(['hospital_id', 'patient_id', 'consent_type']);
        });

        // Coding Diagnoses (ICD)
        Schema::create('coding_diagnoses', function (Blueprint $table) {
            $table->id('coding_id');
            $table->unsignedBigInteger('hospital_id');
            $table->unsignedBigInteger('patient_id');
            $table->string('reference_type', 50);
            $table->unsignedBigInteger('reference_id');
            $table->enum('diagnosis_type', ['admission', 'discharge', 'principal', 'secondary', 'complication', 'comorbidity']);
            $table->text('diagnosis_text');
            $table->string('icd_code', 20)->nullable();
            $table->string('icd_description', 255)->nullable();
            $table->boolean('is_principal')->default(false);
            $table->unsignedBigInteger('coded_by')->nullable();
            $table->datetime('coded_at');
            $table->unsignedBigInteger('verified_by')->nullable();
            $table->datetime('verified_at')->nullable();
            $table->timestamps();

            $table->foreign('hospital_id')->references('hospital_id')->on('hospitals')->cascadeOnDelete();
            $table->foreign('patient_id')->references('patient_id')->on('patients')->cascadeOnDelete();
            $table->foreign('coded_by')->references('user_id')->on('users')->nullOnDelete();
            $table->foreign('verified_by')->references('user_id')->on('users')->nullOnDelete();
            $table->index(['hospital_id', 'reference_type', 'reference_id']);
            $table->index('icd_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coding_diagnoses');
        Schema::dropIfExists('patient_consents');
        Schema::dropIfExists('medical_record_requests');
        Schema::dropIfExists('mrd_file_movements');
        Schema::dropIfExists('patient_documents');
    }
};
