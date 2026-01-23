<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Create Genders table
        Schema::create('genders', function (Blueprint $table) {
            $table->id('gender_id');
            $table->unsignedBigInteger('hospital_id')->nullable();
            $table->string('gender_name', 50);
            $table->string('description', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->foreign('hospital_id')->references('hospital_id')->on('hospitals')->onDelete('cascade');
            $table->index(['hospital_id', 'is_active']);
        });

        // Create Blood Groups table
        Schema::create('blood_groups', function (Blueprint $table) {
            $table->id('blood_group_id');
            $table->unsignedBigInteger('hospital_id')->nullable();
            $table->string('blood_group_name', 10);
            $table->string('description', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->foreign('hospital_id')->references('hospital_id')->on('hospitals')->onDelete('cascade');
            $table->index(['hospital_id', 'is_active']);
        });

        // Create Patient Types table
        Schema::create('patient_types', function (Blueprint $table) {
            $table->id('patient_type_id');
            $table->unsignedBigInteger('hospital_id')->nullable();
            $table->string('patient_type_name', 100);
            $table->string('description', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->foreign('hospital_id')->references('hospital_id')->on('hospitals')->onDelete('cascade');
            $table->index(['hospital_id', 'is_active']);
        });

        // Create Marital Statuses table
        Schema::create('marital_statuses', function (Blueprint $table) {
            $table->id('marital_status_id');
            $table->unsignedBigInteger('hospital_id')->nullable();
            $table->string('marital_status_name', 50);
            $table->string('description', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->foreign('hospital_id')->references('hospital_id')->on('hospitals')->onDelete('cascade');
            $table->index(['hospital_id', 'is_active']);
        });

        // Update patients table
        Schema::table('patients', function (Blueprint $table) {
            // Add new name fields after prefix_id
            $table->string('first_name', 100)->nullable()->after('prefix_id');
            $table->string('middle_name', 100)->nullable()->after('first_name');
            $table->string('last_name', 100)->nullable()->after('middle_name');
            
            // Add age breakdown fields
            $table->integer('age_years')->nullable()->after('dob');
            $table->integer('age_months')->nullable()->after('age_years');
            $table->integer('age_days')->nullable()->after('age_months');
            
            // Add foreign key fields for masters
            $table->unsignedBigInteger('gender_id')->nullable()->after('age_days');
            $table->unsignedBigInteger('blood_group_id')->nullable()->after('gender_id');
            $table->unsignedBigInteger('patient_type_id')->nullable()->after('blood_group_id');
            $table->unsignedBigInteger('marital_status_id')->nullable()->after('patient_type_id');
            
            // Add foreign key constraints
            $table->foreign('gender_id')->references('gender_id')->on('genders')->onDelete('set null');
            $table->foreign('blood_group_id')->references('blood_group_id')->on('blood_groups')->onDelete('set null');
            $table->foreign('patient_type_id')->references('patient_type_id')->on('patient_types')->onDelete('set null');
            $table->foreign('marital_status_id')->references('marital_status_id')->on('marital_statuses')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropForeign(['gender_id']);
            $table->dropForeign(['blood_group_id']);
            $table->dropForeign(['patient_type_id']);
            $table->dropForeign(['marital_status_id']);
            
            $table->dropColumn([
                'first_name', 'middle_name', 'last_name',
                'age_years', 'age_months', 'age_days',
                'gender_id', 'blood_group_id', 'patient_type_id', 'marital_status_id'
            ]);
        });

        Schema::dropIfExists('marital_statuses');
        Schema::dropIfExists('patient_types');
        Schema::dropIfExists('blood_groups');
        Schema::dropIfExists('genders');
    }
};
