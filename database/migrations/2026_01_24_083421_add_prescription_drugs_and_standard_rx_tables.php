<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add missing columns to prescriptions table if they don't exist
        if (Schema::hasTable('prescriptions')) {
            Schema::table('prescriptions', function (Blueprint $table) {
                if (!Schema::hasColumn('prescriptions', 'hospital_id')) {
                    $table->unsignedBigInteger('hospital_id')->after('prescription_id')->nullable();
                    $table->foreign('hospital_id')->references('hospital_id')->on('hospitals')->onDelete('cascade');
                }
                if (!Schema::hasColumn('prescriptions', 'appointment_id')) {
                    $table->unsignedBigInteger('appointment_id')->after('patient_id')->nullable();
                }
                if (!Schema::hasColumn('prescriptions', 'advice')) {
                    $table->text('advice')->nullable();
                }
                if (!Schema::hasColumn('prescriptions', 'investigations')) {
                    $table->text('investigations')->nullable();
                }
                if (!Schema::hasColumn('prescriptions', 'qty_display_on_print')) {
                    $table->boolean('qty_display_on_print')->default(true);
                }
            });
        }

        // Create prescription_drugs table if it doesn't exist
        if (!Schema::hasTable('prescription_drugs')) {
            Schema::create('prescription_drugs', function (Blueprint $table) {
                $table->id('prescription_drug_id');
                $table->unsignedBigInteger('prescription_id');
                $table->unsignedBigInteger('drug_master_id')->nullable();
                $table->string('drug_name');
                $table->string('drug_type')->nullable();
                $table->enum('language', ['english', 'marathi', 'hindi'])->default('english');
                $table->string('dose_advice')->nullable();
                $table->integer('days')->nullable();
                $table->integer('qty')->nullable();
                $table->integer('display_order')->default(0);
                $table->timestamps();

                $table->foreign('prescription_id')->references('prescription_id')->on('prescriptions')->onDelete('cascade');
                $table->foreign('drug_master_id')->references('drug_master_id')->on('drug_masters')->onDelete('set null');
            });
        }

        // Create standard_rx table if it doesn't exist
        if (!Schema::hasTable('standard_rx')) {
            Schema::create('standard_rx', function (Blueprint $table) {
                $table->id('standard_rx_id');
                $table->unsignedBigInteger('hospital_id');
                $table->string('disease_name');
                $table->text('advice')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->foreign('hospital_id')->references('hospital_id')->on('hospitals')->onDelete('cascade');
            });
        }

        // Create standard_rx_drugs table if it doesn't exist
        if (!Schema::hasTable('standard_rx_drugs')) {
            Schema::create('standard_rx_drugs', function (Blueprint $table) {
                $table->id('standard_rx_drug_id');
                $table->unsignedBigInteger('standard_rx_id');
                $table->unsignedBigInteger('drug_master_id')->nullable();
                $table->string('drug_name');
                $table->string('drug_type')->nullable();
                $table->enum('language', ['english', 'marathi', 'hindi'])->default('english');
                $table->string('dose_advice')->nullable();
                $table->integer('days')->nullable();
                $table->integer('qty')->nullable();
                $table->integer('display_order')->default(0);
                $table->timestamps();

                $table->foreign('standard_rx_id')->references('standard_rx_id')->on('standard_rx')->onDelete('cascade');
                $table->foreign('drug_master_id')->references('drug_master_id')->on('drug_masters')->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('standard_rx_drugs');
        Schema::dropIfExists('standard_rx');
        Schema::dropIfExists('prescription_drugs');

        // Remove added columns from prescriptions table
        if (Schema::hasTable('prescriptions')) {
            Schema::table('prescriptions', function (Blueprint $table) {
                if (Schema::hasColumn('prescriptions', 'hospital_id')) {
                    $table->dropForeign(['hospital_id']);
                    $table->dropColumn('hospital_id');
                }
                if (Schema::hasColumn('prescriptions', 'appointment_id')) {
                    $table->dropColumn('appointment_id');
                }
                if (Schema::hasColumn('prescriptions', 'advice')) {
                    $table->dropColumn('advice');
                }
                if (Schema::hasColumn('prescriptions', 'investigations')) {
                    $table->dropColumn('investigations');
                }
                if (Schema::hasColumn('prescriptions', 'qty_display_on_print')) {
                    $table->dropColumn('qty_display_on_print');
                }
            });
        }
    }
};
