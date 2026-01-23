<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Create Reference Doctors table
        if (!Schema::hasTable('reference_doctors')) {
            Schema::create('reference_doctors', function (Blueprint $table) {
                $table->id('reference_doctor_id');
                $table->unsignedBigInteger('hospital_id')->nullable();
                $table->string('doctor_name', 100);
                $table->string('clinic_name', 150)->nullable();
                $table->text('address')->nullable();
                $table->string('mobile', 15)->nullable();
                $table->string('email', 100)->nullable();
                $table->string('specialization', 100)->nullable();
                $table->string('registration_no', 50)->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->foreign('hospital_id')->references('hospital_id')->on('hospitals')->onDelete('cascade');
                $table->index(['hospital_id', 'is_active']);
            });
        }

        // Create Insurance Companies table
        if (!Schema::hasTable('insurance_companies')) {
            Schema::create('insurance_companies', function (Blueprint $table) {
                $table->id('insurance_company_id');
                $table->unsignedBigInteger('hospital_id')->nullable();
                $table->string('company_name', 150);
                $table->string('company_code', 50)->nullable();
                $table->text('address')->nullable();
                $table->string('contact_person', 100)->nullable();
                $table->string('mobile', 15)->nullable();
                $table->string('email', 100)->nullable();
                $table->string('website', 200)->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->foreign('hospital_id')->references('hospital_id')->on('hospitals')->onDelete('cascade');
                $table->index(['hospital_id', 'is_active']);
            });
        }

        // Create Countries table
        if (!Schema::hasTable('countries')) {
            Schema::create('countries', function (Blueprint $table) {
                $table->id('country_id');
                $table->unsignedBigInteger('hospital_id')->nullable();
                $table->string('country_name', 100);
                $table->string('country_code', 10)->nullable();
                $table->string('phone_code', 10)->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->foreign('hospital_id')->references('hospital_id')->on('hospitals')->onDelete('cascade');
                $table->index(['hospital_id', 'is_active']);
            });
        }

        // Create States table
        if (!Schema::hasTable('states')) {
            Schema::create('states', function (Blueprint $table) {
                $table->id('state_id');
                $table->unsignedBigInteger('hospital_id')->nullable();
                $table->unsignedBigInteger('country_id');
                $table->string('state_name', 100);
                $table->string('state_code', 10)->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->foreign('hospital_id')->references('hospital_id')->on('hospitals')->onDelete('cascade');
                $table->foreign('country_id')->references('country_id')->on('countries')->onDelete('cascade');
                $table->index(['hospital_id', 'is_active']);
                $table->index('country_id');
            });
        }

        // Create Districts table
        if (!Schema::hasTable('districts')) {
            Schema::create('districts', function (Blueprint $table) {
                $table->id('district_id');
                $table->unsignedBigInteger('hospital_id')->nullable();
                $table->unsignedBigInteger('state_id');
                $table->string('district_name', 100);
                $table->string('district_code', 10)->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->foreign('hospital_id')->references('hospital_id')->on('hospitals')->onDelete('cascade');
                $table->foreign('state_id')->references('state_id')->on('states')->onDelete('cascade');
                $table->index(['hospital_id', 'is_active']);
                $table->index('state_id');
            });
        }

        // Create Cities/Talukas table
        if (!Schema::hasTable('cities')) {
            Schema::create('cities', function (Blueprint $table) {
                $table->id('city_id');
                $table->unsignedBigInteger('hospital_id')->nullable();
                $table->unsignedBigInteger('district_id');
                $table->string('city_name', 100);
                $table->string('city_code', 10)->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->foreign('hospital_id')->references('hospital_id')->on('hospitals')->onDelete('cascade');
                $table->foreign('district_id')->references('district_id')->on('districts')->onDelete('cascade');
                $table->index(['hospital_id', 'is_active']);
                $table->index('district_id');
            });
        }

        // Create Areas/Villages table
        if (!Schema::hasTable('areas')) {
            Schema::create('areas', function (Blueprint $table) {
                $table->id('area_id');
                $table->unsignedBigInteger('hospital_id')->nullable();
                $table->unsignedBigInteger('city_id');
                $table->string('area_name', 100);
                $table->string('pincode', 10)->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->foreign('hospital_id')->references('hospital_id')->on('hospitals')->onDelete('cascade');
                $table->foreign('city_id')->references('city_id')->on('cities')->onDelete('cascade');
                $table->index(['hospital_id', 'is_active']);
                $table->index('city_id');
                $table->index('pincode');
            });
        }

        // Update patients table with new fields - conditionally add missing columns
        $columnsToAdd = [
            'reference_doctor_id' => ['type' => 'unsignedBigInteger', 'nullable' => true],
            'insurance_company_id' => ['type' => 'unsignedBigInteger', 'nullable' => true],
            'insurance_policy_no' => ['type' => 'string', 'length' => 50, 'nullable' => true],
            'subscribe_sms' => ['type' => 'boolean', 'default' => false],
            'subscribe_whatsapp' => ['type' => 'boolean', 'default' => false],
            'subscribe_email' => ['type' => 'boolean', 'default' => false],
            'permanent_address' => ['type' => 'text', 'nullable' => true],
            'permanent_country_id' => ['type' => 'unsignedBigInteger', 'nullable' => true],
            'permanent_state_id' => ['type' => 'unsignedBigInteger', 'nullable' => true],
            'permanent_district_id' => ['type' => 'unsignedBigInteger', 'nullable' => true],
            'permanent_city_id' => ['type' => 'unsignedBigInteger', 'nullable' => true],
            'permanent_area_id' => ['type' => 'unsignedBigInteger', 'nullable' => true],
            'permanent_pincode' => ['type' => 'string', 'length' => 10, 'nullable' => true],
            'permanent_mobile' => ['type' => 'string', 'length' => 15, 'nullable' => true],
            'permanent_email' => ['type' => 'string', 'length' => 100, 'nullable' => true],
            'same_as_permanent' => ['type' => 'boolean', 'default' => true],
            'current_address' => ['type' => 'text', 'nullable' => true],
            'current_country_id' => ['type' => 'unsignedBigInteger', 'nullable' => true],
            'current_state_id' => ['type' => 'unsignedBigInteger', 'nullable' => true],
            'current_district_id' => ['type' => 'unsignedBigInteger', 'nullable' => true],
            'current_city_id' => ['type' => 'unsignedBigInteger', 'nullable' => true],
            'current_area_id' => ['type' => 'unsignedBigInteger', 'nullable' => true],
            'current_pincode' => ['type' => 'string', 'length' => 10, 'nullable' => true],
            'current_mobile' => ['type' => 'string', 'length' => 15, 'nullable' => true],
            'current_email' => ['type' => 'string', 'length' => 100, 'nullable' => true],
        ];

        foreach ($columnsToAdd as $column => $config) {
            if (!Schema::hasColumn('patients', $column)) {
                Schema::table('patients', function (Blueprint $table) use ($column, $config) {
                    $col = null;
                    switch ($config['type']) {
                        case 'unsignedBigInteger':
                            $col = $table->unsignedBigInteger($column);
                            break;
                        case 'string':
                            $col = $table->string($column, $config['length'] ?? 255);
                            break;
                        case 'text':
                            $col = $table->text($column);
                            break;
                        case 'boolean':
                            $col = $table->boolean($column)->default($config['default'] ?? false);
                            break;
                    }
                    if ($col && isset($config['nullable']) && $config['nullable']) {
                        $col->nullable();
                    }
                });
            }
        }

        // Add foreign keys if not exist (check by trying to add)
        $foreignKeys = [
            ['column' => 'reference_doctor_id', 'references' => 'reference_doctor_id', 'on' => 'reference_doctors'],
            ['column' => 'insurance_company_id', 'references' => 'insurance_company_id', 'on' => 'insurance_companies'],
            ['column' => 'permanent_country_id', 'references' => 'country_id', 'on' => 'countries'],
            ['column' => 'permanent_state_id', 'references' => 'state_id', 'on' => 'states'],
            ['column' => 'permanent_district_id', 'references' => 'district_id', 'on' => 'districts'],
            ['column' => 'permanent_city_id', 'references' => 'city_id', 'on' => 'cities'],
            ['column' => 'permanent_area_id', 'references' => 'area_id', 'on' => 'areas'],
            ['column' => 'current_country_id', 'references' => 'country_id', 'on' => 'countries'],
            ['column' => 'current_state_id', 'references' => 'state_id', 'on' => 'states'],
            ['column' => 'current_district_id', 'references' => 'district_id', 'on' => 'districts'],
            ['column' => 'current_city_id', 'references' => 'city_id', 'on' => 'cities'],
            ['column' => 'current_area_id', 'references' => 'area_id', 'on' => 'areas'],
        ];

        foreach ($foreignKeys as $fk) {
            try {
                Schema::table('patients', function (Blueprint $table) use ($fk) {
                    $table->foreign($fk['column'])->references($fk['references'])->on($fk['on'])->onDelete('set null');
                });
            } catch (\Exception $e) {
                // Foreign key already exists, skip
            }
        }
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropForeign(['reference_doctor_id']);
            $table->dropForeign(['insurance_company_id']);
            $table->dropForeign(['permanent_country_id']);
            $table->dropForeign(['permanent_state_id']);
            $table->dropForeign(['permanent_district_id']);
            $table->dropForeign(['permanent_city_id']);
            $table->dropForeign(['permanent_area_id']);
            $table->dropForeign(['current_country_id']);
            $table->dropForeign(['current_state_id']);
            $table->dropForeign(['current_district_id']);
            $table->dropForeign(['current_city_id']);
            $table->dropForeign(['current_area_id']);
            
            $table->dropColumn([
                'reference_doctor_id', 'insurance_company_id', 'insurance_policy_no',
                'subscribe_sms', 'subscribe_whatsapp', 'subscribe_email',
                'permanent_address', 'permanent_country_id', 'permanent_state_id',
                'permanent_district_id', 'permanent_city_id', 'permanent_area_id',
                'permanent_pincode', 'permanent_mobile', 'permanent_email',
                'same_as_permanent', 'current_address', 'current_country_id',
                'current_state_id', 'current_district_id', 'current_city_id',
                'current_area_id', 'current_pincode', 'current_mobile', 'current_email'
            ]);
        });

        Schema::dropIfExists('areas');
        Schema::dropIfExists('cities');
        Schema::dropIfExists('districts');
        Schema::dropIfExists('states');
        Schema::dropIfExists('countries');
        Schema::dropIfExists('insurance_companies');
        Schema::dropIfExists('reference_doctors');
    }
};
