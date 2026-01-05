<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Rate Change Requests (For doctor approval workflow)
        if (!Schema::hasTable('rate_change_requests')) {
            Schema::create('rate_change_requests', function (Blueprint $table) {
                $table->id('request_id');
                $table->unsignedBigInteger('hospital_id');
                $table->unsignedBigInteger('opd_id')->nullable();
                $table->unsignedBigInteger('service_id')->nullable();
                $table->decimal('original_rate', 10, 2);
                $table->decimal('requested_rate', 10, 2);
                $table->string('reason', 255)->nullable();
                $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
                $table->unsignedBigInteger('requested_by');
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->text('remarks')->nullable();
                $table->timestamps();

                $table->foreign('hospital_id')->references('hospital_id')->on('hospitals')->cascadeOnDelete();
                $table->foreign('opd_id')->references('opd_id')->on('opd_visits')->nullOnDelete();
                $table->foreign('service_id')->references('service_id')->on('services')->nullOnDelete();
                $table->foreign('requested_by')->references('user_id')->on('users');
                $table->foreign('approved_by')->references('user_id')->on('users')->nullOnDelete();
            });
        }

        // OPD Time Slots (For slot-based appointments)
        if (!Schema::hasTable('opd_time_slots')) {
            Schema::create('opd_time_slots', function (Blueprint $table) {
                $table->id('slot_id');
                $table->unsignedBigInteger('hospital_id');
                $table->unsignedBigInteger('doctor_id')->nullable();
                $table->unsignedBigInteger('department_id')->nullable();
                $table->enum('day_of_week', ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday']);
                $table->time('start_time');
                $table->time('end_time');
                $table->integer('slot_duration_minutes')->default(15);
                $table->integer('max_patients_per_slot')->default(1);
                $table->integer('max_patients_per_session')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->foreign('hospital_id')->references('hospital_id')->on('hospitals')->cascadeOnDelete();
                $table->foreign('doctor_id')->references('doctor_id')->on('doctors')->nullOnDelete();
                $table->foreign('department_id')->references('department_id')->on('departments')->nullOnDelete();
            });
        }

        // Add group_id, entry_card_id, charge_type to opd_visits
        Schema::table('opd_visits', function (Blueprint $table) {
            if (!Schema::hasColumn('opd_visits', 'group_id')) {
                $table->unsignedBigInteger('group_id')->nullable()->after('doctor_id');
                $table->foreign('group_id')->references('group_id')->on('doctor_groups')->nullOnDelete();
            }
            if (!Schema::hasColumn('opd_visits', 'entry_card_id')) {
                $table->unsignedBigInteger('entry_card_id')->nullable()->after('bill_id');
                $table->foreign('entry_card_id')->references('entry_card_id')->on('entry_cards')->nullOnDelete();
            }
            if (!Schema::hasColumn('opd_visits', 'charge_type')) {
                $table->enum('charge_type', ['normal', 'day_emergency', 'night_emergency'])->default('normal')->after('visit_type');
            }
        });

        // Add group_id to appointments
        Schema::table('appointments', function (Blueprint $table) {
            if (!Schema::hasColumn('appointments', 'group_id')) {
                $table->unsignedBigInteger('group_id')->nullable()->after('doctor_id');
                $table->foreign('group_id')->references('group_id')->on('doctor_groups')->nullOnDelete();
            }
        });

        // Mark the original migration as run to prevent future conflicts
        DB::table('migrations')->updateOrInsert(
            ['migration' => '2025_12_22_000003_create_opd_configuration_tables'],
            ['batch' => DB::table('migrations')->max('batch') + 1]
        );
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            if (Schema::hasColumn('appointments', 'group_id')) {
                $table->dropForeign(['group_id']);
                $table->dropColumn('group_id');
            }
        });

        Schema::table('opd_visits', function (Blueprint $table) {
            if (Schema::hasColumn('opd_visits', 'group_id')) {
                $table->dropForeign(['group_id']);
                $table->dropColumn('group_id');
            }
            if (Schema::hasColumn('opd_visits', 'entry_card_id')) {
                $table->dropForeign(['entry_card_id']);
                $table->dropColumn('entry_card_id');
            }
            if (Schema::hasColumn('opd_visits', 'charge_type')) {
                $table->dropColumn('charge_type');
            }
        });

        Schema::dropIfExists('opd_time_slots');
        Schema::dropIfExists('rate_change_requests');
    }
};
