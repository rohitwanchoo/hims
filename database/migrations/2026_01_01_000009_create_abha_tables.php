<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ABHA Registrations
        Schema::create('abha_registrations', function (Blueprint $table) {
            $table->id('abha_registration_id');
            $table->unsignedBigInteger('hospital_id');
            $table->unsignedBigInteger('patient_id');
            $table->string('abha_number', 17)->nullable();
            $table->string('abha_address', 255)->nullable();
            $table->string('health_id', 50)->nullable();
            $table->string('name', 100);
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', ['M', 'F', 'O'])->nullable();
            $table->string('mobile', 15)->nullable();
            $table->string('email', 100)->nullable();
            $table->enum('kyc_status', ['pending', 'verified', 'rejected'])->default('pending');
            $table->enum('kyc_type', ['aadhaar', 'driving_license', 'pan'])->nullable();
            $table->string('kyc_document_number', 50)->nullable();
            $table->datetime('linked_at')->nullable();
            $table->boolean('consent_given')->default(false);
            $table->datetime('consent_datetime')->nullable();
            $table->string('hip_id', 50)->nullable();
            $table->string('phr_address', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('hospital_id')->references('hospital_id')->on('hospitals')->cascadeOnDelete();
            $table->foreign('patient_id')->references('patient_id')->on('patients')->cascadeOnDelete();
            $table->unique(['hospital_id', 'patient_id']);
            $table->index('abha_number');
            $table->index('abha_address');
        });

        // ABHA Auth Tokens
        Schema::create('abha_auth_tokens', function (Blueprint $table) {
            $table->id('token_id');
            $table->unsignedBigInteger('hospital_id');
            $table->unsignedBigInteger('abha_registration_id')->nullable();
            $table->string('token_type', 50)->default('gateway');
            $table->text('access_token');
            $table->text('refresh_token')->nullable();
            $table->datetime('expires_at');
            $table->string('scope', 255)->nullable();
            $table->timestamps();

            $table->foreign('hospital_id')->references('hospital_id')->on('hospitals')->cascadeOnDelete();
            $table->foreign('abha_registration_id')->references('abha_registration_id')->on('abha_registrations')->cascadeOnDelete();
            $table->index(['hospital_id', 'token_type']);
        });

        // ABDM Health Records
        Schema::create('abdm_health_records', function (Blueprint $table) {
            $table->id('record_id');
            $table->unsignedBigInteger('hospital_id');
            $table->unsignedBigInteger('abha_registration_id');
            $table->unsignedBigInteger('patient_id');
            $table->string('care_context_reference', 100);
            $table->enum('care_context_type', ['opd', 'ipd', 'lab', 'radiology', 'prescription', 'discharge_summary']);
            $table->unsignedBigInteger('care_context_id');
            $table->json('fhir_bundle')->nullable();
            $table->enum('status', ['pending', 'sent', 'acknowledged', 'error'])->default('pending');
            $table->string('consent_id', 100)->nullable();
            $table->string('hip_request_id', 100)->nullable();
            $table->text('error_message')->nullable();
            $table->datetime('sent_at')->nullable();
            $table->datetime('acknowledged_at')->nullable();
            $table->timestamps();

            $table->foreign('hospital_id')->references('hospital_id')->on('hospitals')->cascadeOnDelete();
            $table->foreign('abha_registration_id')->references('abha_registration_id')->on('abha_registrations')->cascadeOnDelete();
            $table->foreign('patient_id')->references('patient_id')->on('patients')->cascadeOnDelete();
            $table->index(['hospital_id', 'status']);
            $table->index('care_context_reference');
        });

        // ABDM Consent Requests
        Schema::create('abdm_consent_requests', function (Blueprint $table) {
            $table->id('consent_request_id');
            $table->unsignedBigInteger('hospital_id');
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('abha_registration_id');
            $table->string('consent_request_id_abdm', 100)->nullable();
            $table->string('purpose', 100);
            $table->json('hi_types');
            $table->date('date_range_from');
            $table->date('date_range_to');
            $table->datetime('expiry_date');
            $table->enum('status', ['requested', 'granted', 'denied', 'expired', 'revoked'])->default('requested');
            $table->string('consent_artefact_id', 100)->nullable();
            $table->text('hiu_id')->nullable();
            $table->text('requester_name')->nullable();
            $table->timestamps();

            $table->foreign('hospital_id')->references('hospital_id')->on('hospitals')->cascadeOnDelete();
            $table->foreign('patient_id')->references('patient_id')->on('patients')->cascadeOnDelete();
            $table->foreign('abha_registration_id')->references('abha_registration_id')->on('abha_registrations')->cascadeOnDelete();
            $table->index(['hospital_id', 'status']);
        });

        // ABDM Transaction Logs
        Schema::create('abdm_transaction_logs', function (Blueprint $table) {
            $table->id('log_id');
            $table->unsignedBigInteger('hospital_id');
            $table->string('transaction_id', 100);
            $table->string('request_type', 50);
            $table->string('endpoint', 255);
            $table->json('request_body')->nullable();
            $table->json('response_body')->nullable();
            $table->integer('response_code')->nullable();
            $table->enum('status', ['success', 'failed', 'pending'])->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->foreign('hospital_id')->references('hospital_id')->on('hospitals')->cascadeOnDelete();
            $table->index('transaction_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('abdm_transaction_logs');
        Schema::dropIfExists('abdm_consent_requests');
        Schema::dropIfExists('abdm_health_records');
        Schema::dropIfExists('abha_auth_tokens');
        Schema::dropIfExists('abha_registrations');
    }
};
