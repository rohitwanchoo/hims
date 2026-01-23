<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Fix reference_doctors table
        Schema::table('reference_doctors', function (Blueprint $table) {
            // Add missing columns if they don't exist
            if (!Schema::hasColumn('reference_doctors', 'first_name')) {
                $table->string('first_name', 50)->nullable()->after('hospital_id');
            }
            if (!Schema::hasColumn('reference_doctors', 'middle_name')) {
                $table->string('middle_name', 50)->nullable()->after('first_name');
            }
            if (!Schema::hasColumn('reference_doctors', 'last_name')) {
                $table->string('last_name', 50)->nullable()->after('middle_name');
            }
            if (!Schema::hasColumn('reference_doctors', 'gender_id')) {
                $table->unsignedBigInteger('gender_id')->nullable()->after('full_name');
            }
            if (!Schema::hasColumn('reference_doctors', 'blood_group_id')) {
                $table->unsignedBigInteger('blood_group_id')->nullable()->after('gender_id');
            }
            if (!Schema::hasColumn('reference_doctors', 'qualification_id')) {
                $table->unsignedBigInteger('qualification_id')->nullable()->after('blood_group_id');
            }
            if (!Schema::hasColumn('reference_doctors', 'department_id')) {
                $table->unsignedBigInteger('department_id')->nullable()->after('qualification_id');
            }
            if (!Schema::hasColumn('reference_doctors', 'practice_no')) {
                $table->string('practice_no', 50)->nullable()->after('registration_no');
            }
            if (!Schema::hasColumn('reference_doctors', 'dob')) {
                $table->date('dob')->nullable()->after('practice_no');
            }
            if (!Schema::hasColumn('reference_doctors', 'clinic_name')) {
                $table->string('clinic_name', 150)->nullable()->after('hospital_name');
            }
            if (!Schema::hasColumn('reference_doctors', 'specialization')) {
                $table->string('specialization', 100)->nullable()->after('skill_set');
            }
            
            // Residence Address fields
            if (!Schema::hasColumn('reference_doctors', 'res_address_line1')) {
                $table->string('res_address_line1', 200)->nullable();
            }
            if (!Schema::hasColumn('reference_doctors', 'res_address_line2')) {
                $table->string('res_address_line2', 200)->nullable();
            }
            if (!Schema::hasColumn('reference_doctors', 'res_country_id')) {
                $table->unsignedBigInteger('res_country_id')->nullable();
            }
            if (!Schema::hasColumn('reference_doctors', 'res_state_id')) {
                $table->unsignedBigInteger('res_state_id')->nullable();
            }
            if (!Schema::hasColumn('reference_doctors', 'res_district_id')) {
                $table->unsignedBigInteger('res_district_id')->nullable();
            }
            if (!Schema::hasColumn('reference_doctors', 'res_city_id')) {
                $table->unsignedBigInteger('res_city_id')->nullable();
            }
            if (!Schema::hasColumn('reference_doctors', 'res_pincode')) {
                $table->string('res_pincode', 10)->nullable();
            }
            if (!Schema::hasColumn('reference_doctors', 'res_fax')) {
                $table->string('res_fax', 20)->nullable();
            }
            if (!Schema::hasColumn('reference_doctors', 'res_tel1')) {
                $table->string('res_tel1', 20)->nullable();
            }
            if (!Schema::hasColumn('reference_doctors', 'res_tel2')) {
                $table->string('res_tel2', 20)->nullable();
            }
            if (!Schema::hasColumn('reference_doctors', 'res_mobile')) {
                $table->string('res_mobile', 15)->nullable();
            }
            if (!Schema::hasColumn('reference_doctors', 'res_email')) {
                $table->string('res_email', 100)->nullable();
            }
            if (!Schema::hasColumn('reference_doctors', 'res_website')) {
                $table->string('res_website', 200)->nullable();
            }
            
            // Clinic Address fields
            if (!Schema::hasColumn('reference_doctors', 'clinic_address_line1')) {
                $table->string('clinic_address_line1', 200)->nullable();
            }
            if (!Schema::hasColumn('reference_doctors', 'clinic_address_line2')) {
                $table->string('clinic_address_line2', 200)->nullable();
            }
            if (!Schema::hasColumn('reference_doctors', 'clinic_country_id')) {
                $table->unsignedBigInteger('clinic_country_id')->nullable();
            }
            if (!Schema::hasColumn('reference_doctors', 'clinic_state_id')) {
                $table->unsignedBigInteger('clinic_state_id')->nullable();
            }
            if (!Schema::hasColumn('reference_doctors', 'clinic_district_id')) {
                $table->unsignedBigInteger('clinic_district_id')->nullable();
            }
            if (!Schema::hasColumn('reference_doctors', 'clinic_city_id')) {
                $table->unsignedBigInteger('clinic_city_id')->nullable();
            }
            if (!Schema::hasColumn('reference_doctors', 'clinic_pincode')) {
                $table->string('clinic_pincode', 10)->nullable();
            }
            if (!Schema::hasColumn('reference_doctors', 'clinic_fax')) {
                $table->string('clinic_fax', 20)->nullable();
            }
            if (!Schema::hasColumn('reference_doctors', 'clinic_tel1')) {
                $table->string('clinic_tel1', 20)->nullable();
            }
            if (!Schema::hasColumn('reference_doctors', 'clinic_tel2')) {
                $table->string('clinic_tel2', 20)->nullable();
            }
            if (!Schema::hasColumn('reference_doctors', 'clinic_mobile')) {
                $table->string('clinic_mobile', 15)->nullable();
            }
            if (!Schema::hasColumn('reference_doctors', 'clinic_email')) {
                $table->string('clinic_email', 100)->nullable();
            }
            if (!Schema::hasColumn('reference_doctors', 'clinic_website')) {
                $table->string('clinic_website', 200)->nullable();
            }
        });

        // Migrate data from full_name to split fields if full_name exists and first_name is empty
        DB::statement("UPDATE reference_doctors SET first_name = full_name WHERE first_name IS NULL AND full_name IS NOT NULL");

        // Fix insurance_companies table - add missing columns
        Schema::table('insurance_companies', function (Blueprint $table) {
            if (!Schema::hasColumn('insurance_companies', 'hospital_id')) {
                $table->unsignedBigInteger('hospital_id')->nullable()->after('insurance_id');
            }
            if (!Schema::hasColumn('insurance_companies', 'mobile')) {
                $table->string('mobile', 15)->nullable()->after('phone');
            }
            if (!Schema::hasColumn('insurance_companies', 'website')) {
                $table->string('website', 200)->nullable()->after('email');
            }
        });
    }

    public function down(): void
    {
        // Reverse the changes if needed
        Schema::table('reference_doctors', function (Blueprint $table) {
            $columns = [
                'first_name', 'middle_name', 'last_name', 'gender_id', 'blood_group_id',
                'qualification_id', 'department_id', 'practice_no', 'dob', 'clinic_name', 'specialization',
                'res_address_line1', 'res_address_line2', 'res_country_id', 'res_state_id',
                'res_district_id', 'res_city_id', 'res_pincode', 'res_fax', 'res_tel1', 'res_tel2',
                'res_mobile', 'res_email', 'res_website',
                'clinic_address_line1', 'clinic_address_line2', 'clinic_country_id', 'clinic_state_id',
                'clinic_district_id', 'clinic_city_id', 'clinic_pincode', 'clinic_fax', 'clinic_tel1',
                'clinic_tel2', 'clinic_mobile', 'clinic_email', 'clinic_website'
            ];
            foreach ($columns as $col) {
                if (Schema::hasColumn('reference_doctors', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
