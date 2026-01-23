<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Consultation Forms - Templates
        Schema::create('consultation_forms', function (Blueprint $table) {
            $table->id('form_id');
            $table->unsignedBigInteger('hospital_id')->nullable();
            $table->string('form_name');
            $table->text('description')->nullable();
            $table->enum('form_type', ['general', 'opd', 'ipd', 'specialty'])->default('general');
            $table->unsignedBigInteger('department_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->foreign('hospital_id')->references('hospital_id')->on('hospitals')->onDelete('cascade');
            $table->foreign('department_id')->references('department_id')->on('departments')->onDelete('set null');

            $table->index(['hospital_id', 'is_active']);
        });

        // Consultation Form Fields - Field Definitions
        Schema::create('consultation_form_fields', function (Blueprint $table) {
            $table->id('field_id');
            $table->unsignedBigInteger('form_id');
            $table->string('field_label');
            $table->string('field_key'); // Unique key for the field (e.g., 'chief_complaint', 'blood_pressure')
            $table->enum('field_type', [
                'text',
                'textarea',
                'number',
                'dropdown',
                'radio',
                'checkbox',
                'date',
                'time',
                'datetime',
                'file',
                'email',
                'phone'
            ]);
            $table->json('field_options')->nullable(); // For dropdown, radio, checkbox options
            $table->json('field_config')->nullable(); // Additional config (placeholder, min, max, etc.)
            $table->string('default_value')->nullable();
            $table->integer('field_order')->default(0);
            $table->boolean('is_required')->default(false);
            $table->boolean('is_visible')->default(true);
            $table->string('validation_rules')->nullable(); // Laravel validation rules
            $table->string('section')->nullable(); // Group fields by section (e.g., 'vitals', 'examination')
            $table->string('placeholder')->nullable(); // Placeholder text for input fields
            $table->text('help_text')->nullable();
            $table->timestamps();

            $table->foreign('form_id')->references('form_id')->on('consultation_forms')->onDelete('cascade');

            $table->index(['form_id', 'is_visible', 'field_order']);
            $table->unique(['form_id', 'field_key']);
        });

        // Consultation Records - Actual Consultation Data
        Schema::create('consultation_records', function (Blueprint $table) {
            $table->id('record_id');
            $table->unsignedBigInteger('opd_id')->nullable();
            $table->unsignedBigInteger('ipd_id')->nullable();
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('form_id');
            $table->dateTime('consultation_date');
            $table->json('form_data'); // Actual field values
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('opd_id')->references('opd_id')->on('opd_visits')->onDelete('cascade');
            $table->foreign('patient_id')->references('patient_id')->on('patients')->onDelete('cascade');
            $table->foreign('doctor_id')->references('doctor_id')->on('doctors')->onDelete('cascade');
            $table->foreign('form_id')->references('form_id')->on('consultation_forms')->onDelete('restrict');
            $table->foreign('created_by')->references('user_id')->on('users')->onDelete('restrict');

            $table->index(['patient_id', 'consultation_date']);
            $table->index(['doctor_id', 'consultation_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultation_records');
        Schema::dropIfExists('consultation_form_fields');
        Schema::dropIfExists('consultation_forms');
    }
};
