<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // SMS Gateways
        Schema::create('sms_gateways', function (Blueprint $table) {
            $table->id('gateway_id');
            $table->unsignedBigInteger('hospital_id');
            $table->string('gateway_name', 50);
            $table->enum('provider', ['twilio', 'msg91', 'textlocal', 'custom'])->default('msg91');
            $table->string('api_url', 255)->nullable();
            $table->text('api_key');
            $table->text('api_secret')->nullable();
            $table->string('sender_id', 20);
            $table->json('settings')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('hospital_id')->references('hospital_id')->on('hospitals')->cascadeOnDelete();
            $table->index('hospital_id');
        });

        // Notification Templates
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id('template_id');
            $table->unsignedBigInteger('hospital_id');
            $table->string('template_code', 50);
            $table->string('template_name', 100);
            $table->enum('notification_type', [
                'appointment_reminder', 'appointment_confirmation', 'lab_result',
                'prescription', 'discharge', 'bill', 'payment', 'opd_visit',
                'admission', 'general'
            ]);
            $table->enum('channel', ['sms', 'email', 'both'])->default('both');
            $table->text('sms_template')->nullable();
            $table->string('sms_dlt_template_id', 50)->nullable();
            $table->string('email_subject', 255)->nullable();
            $table->text('email_template')->nullable();
            $table->json('variables')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('hospital_id')->references('hospital_id')->on('hospitals')->cascadeOnDelete();
            $table->unique(['hospital_id', 'template_code']);
        });

        // Notification Logs
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id('log_id');
            $table->unsignedBigInteger('hospital_id');
            $table->unsignedBigInteger('template_id')->nullable();
            $table->string('notification_type', 50);
            $table->enum('channel', ['sms', 'email']);
            $table->enum('recipient_type', ['patient', 'doctor', 'staff', 'other'])->default('patient');
            $table->unsignedBigInteger('recipient_id')->nullable();
            $table->string('recipient_name', 100);
            $table->string('recipient_contact', 100);
            $table->string('subject', 255)->nullable();
            $table->text('message');
            $table->string('reference_type', 50)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->enum('status', ['queued', 'sent', 'delivered', 'failed'])->default('queued');
            $table->unsignedBigInteger('gateway_id')->nullable();
            $table->json('gateway_response')->nullable();
            $table->string('message_id', 100)->nullable();
            $table->datetime('sent_at')->nullable();
            $table->datetime('delivered_at')->nullable();
            $table->text('error_message')->nullable();
            $table->integer('retry_count')->default(0);
            $table->timestamps();

            $table->foreign('hospital_id')->references('hospital_id')->on('hospitals')->cascadeOnDelete();
            $table->foreign('template_id')->references('template_id')->on('notification_templates')->nullOnDelete();
            $table->foreign('gateway_id')->references('gateway_id')->on('sms_gateways')->nullOnDelete();
            $table->index(['hospital_id', 'status']);
            $table->index(['recipient_type', 'recipient_id']);
        });

        // Notification Preferences
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id('preference_id');
            $table->unsignedBigInteger('hospital_id');
            $table->unsignedBigInteger('patient_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('notification_type', 50);
            $table->boolean('sms_enabled')->default(true);
            $table->boolean('email_enabled')->default(true);
            $table->boolean('push_enabled')->default(true);
            $table->timestamps();

            $table->foreign('hospital_id')->references('hospital_id')->on('hospitals')->cascadeOnDelete();
            $table->foreign('patient_id')->references('patient_id')->on('patients')->cascadeOnDelete();
            $table->foreign('user_id')->references('user_id')->on('users')->cascadeOnDelete();
            $table->unique(['hospital_id', 'patient_id', 'notification_type'], 'notif_pref_patient_unique');
            $table->unique(['hospital_id', 'user_id', 'notification_type'], 'notif_pref_user_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
        Schema::dropIfExists('notification_logs');
        Schema::dropIfExists('notification_templates');
        Schema::dropIfExists('sms_gateways');
    }
};
