<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Patient Users (Portal Login)
        Schema::create('patient_users', function (Blueprint $table) {
            $table->id('patient_user_id');
            $table->unsignedBigInteger('hospital_id');
            $table->unsignedBigInteger('patient_id');
            $table->string('username', 100)->unique();
            $table->string('email', 100);
            $table->string('mobile', 15);
            $table->string('password', 255);
            $table->datetime('email_verified_at')->nullable();
            $table->datetime('mobile_verified_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->datetime('last_login_at')->nullable();
            $table->string('remember_token', 100)->nullable();
            $table->string('otp', 10)->nullable();
            $table->datetime('otp_expires_at')->nullable();
            $table->timestamps();

            $table->foreign('hospital_id')->references('hospital_id')->on('hospitals')->cascadeOnDelete();
            $table->foreign('patient_id')->references('patient_id')->on('patients')->cascadeOnDelete();
            $table->unique(['hospital_id', 'patient_id']);
            $table->index('email');
            $table->index('mobile');
        });

        // Patient Portal Sessions
        Schema::create('patient_portal_sessions', function (Blueprint $table) {
            $table->id('session_id');
            $table->unsignedBigInteger('patient_user_id');
            $table->string('token', 255);
            $table->string('ip_address', 45);
            $table->string('user_agent', 255)->nullable();
            $table->string('device_type', 20)->nullable();
            $table->datetime('login_at');
            $table->datetime('logout_at')->nullable();
            $table->datetime('expires_at');
            $table->timestamps();

            $table->foreign('patient_user_id')->references('patient_user_id')->on('patient_users')->cascadeOnDelete();
            $table->index('token');
        });

        // Patient Appointment Requests
        Schema::create('patient_appointment_requests', function (Blueprint $table) {
            $table->id('request_id');
            $table->unsignedBigInteger('hospital_id');
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('patient_user_id');
            $table->unsignedBigInteger('department_id')->nullable();
            $table->unsignedBigInteger('doctor_id')->nullable();
            $table->date('preferred_date');
            $table->string('preferred_time_slot', 50)->nullable();
            $table->date('alternate_date')->nullable();
            $table->text('reason');
            $table->enum('status', ['pending', 'approved', 'rejected', 'converted'])->default('pending');
            $table->unsignedBigInteger('appointment_id')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->datetime('reviewed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->foreign('hospital_id')->references('hospital_id')->on('hospitals')->cascadeOnDelete();
            $table->foreign('patient_id')->references('patient_id')->on('patients')->cascadeOnDelete();
            $table->foreign('patient_user_id')->references('patient_user_id')->on('patient_users')->cascadeOnDelete();
            $table->foreign('department_id')->references('department_id')->on('departments')->nullOnDelete();
            $table->foreign('doctor_id')->references('doctor_id')->on('doctors')->nullOnDelete();
            $table->foreign('appointment_id')->references('appointment_id')->on('appointments')->nullOnDelete();
            $table->foreign('reviewed_by')->references('user_id')->on('users')->nullOnDelete();
        });

        // Patient Feedback
        Schema::create('patient_feedback', function (Blueprint $table) {
            $table->id('feedback_id');
            $table->unsignedBigInteger('hospital_id');
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('patient_user_id')->nullable();
            $table->enum('feedback_type', ['visit', 'service', 'doctor', 'staff', 'facility', 'general']);
            $table->string('reference_type', 50)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->unsignedBigInteger('doctor_id')->nullable();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->integer('overall_rating');
            $table->integer('cleanliness_rating')->nullable();
            $table->integer('staff_rating')->nullable();
            $table->integer('wait_time_rating')->nullable();
            $table->integer('doctor_rating')->nullable();
            $table->text('comments')->nullable();
            $table->text('suggestions')->nullable();
            $table->boolean('is_anonymous')->default(false);
            $table->boolean('is_addressed')->default(false);
            $table->text('response')->nullable();
            $table->unsignedBigInteger('responded_by')->nullable();
            $table->datetime('responded_at')->nullable();
            $table->timestamps();

            $table->foreign('hospital_id')->references('hospital_id')->on('hospitals')->cascadeOnDelete();
            $table->foreign('patient_id')->references('patient_id')->on('patients')->cascadeOnDelete();
            $table->foreign('patient_user_id')->references('patient_user_id')->on('patient_users')->nullOnDelete();
            $table->foreign('doctor_id')->references('doctor_id')->on('doctors')->nullOnDelete();
            $table->foreign('department_id')->references('department_id')->on('departments')->nullOnDelete();
            $table->foreign('responded_by')->references('user_id')->on('users')->nullOnDelete();
        });

        // Patient Document Access Logs
        Schema::create('patient_document_access_logs', function (Blueprint $table) {
            $table->id('log_id');
            $table->unsignedBigInteger('hospital_id');
            $table->unsignedBigInteger('patient_user_id');
            $table->string('document_type', 50);
            $table->unsignedBigInteger('document_id');
            $table->enum('action', ['view', 'download', 'print']);
            $table->string('ip_address', 45);
            $table->datetime('accessed_at');

            $table->foreign('hospital_id')->references('hospital_id')->on('hospitals')->cascadeOnDelete();
            $table->foreign('patient_user_id')->references('patient_user_id')->on('patient_users')->cascadeOnDelete();
            $table->index(['patient_user_id', 'accessed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_document_access_logs');
        Schema::dropIfExists('patient_feedback');
        Schema::dropIfExists('patient_appointment_requests');
        Schema::dropIfExists('patient_portal_sessions');
        Schema::dropIfExists('patient_users');
    }
};
