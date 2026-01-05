<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            // Booking Mode (how appointment was made)
            if (!Schema::hasColumn('appointments', 'booking_mode')) {
                $table->enum('booking_mode', ['walk_in', 'telephonic', 'online'])->default('walk_in')->after('appointment_type');
            }

            // Service type (consultation/followup - replaces appointment_type for OPD context)
            if (!Schema::hasColumn('appointments', 'service_type')) {
                $table->enum('service_type', ['first', 'followup'])->default('first')->after('booking_mode');
            }

            // Online appointment flag
            if (!Schema::hasColumn('appointments', 'is_online')) {
                $table->boolean('is_online')->default(false)->after('service_type');
            }

            // Priority/Urgency
            if (!Schema::hasColumn('appointments', 'priority')) {
                $table->enum('priority', ['normal', 'urgent', 'emergency'])->default('normal')->after('is_online');
            }

            // Cancellation fields
            if (!Schema::hasColumn('appointments', 'cancel_reason_id')) {
                $table->unsignedBigInteger('cancel_reason_id')->nullable()->after('notes');
                $table->foreign('cancel_reason_id')->references('cancel_reason_id')->on('cancel_reasons')->nullOnDelete();
            }

            if (!Schema::hasColumn('appointments', 'cancel_remarks')) {
                $table->text('cancel_remarks')->nullable()->after('cancel_reason_id');
            }

            if (!Schema::hasColumn('appointments', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable()->after('cancel_remarks');
            }

            if (!Schema::hasColumn('appointments', 'cancelled_by')) {
                $table->unsignedBigInteger('cancelled_by')->nullable()->after('cancelled_at');
                $table->foreign('cancelled_by')->references('user_id')->on('users')->nullOnDelete();
            }

            // Transfer tracking
            if (!Schema::hasColumn('appointments', 'transferred_from_id')) {
                $table->unsignedBigInteger('transferred_from_id')->nullable()->after('cancelled_by');
            }

            if (!Schema::hasColumn('appointments', 'original_doctor_id')) {
                $table->unsignedBigInteger('original_doctor_id')->nullable()->after('transferred_from_id');
                $table->foreign('original_doctor_id')->references('doctor_id')->on('doctors')->nullOnDelete();
            }

            // Arrived/Confirmation fields
            if (!Schema::hasColumn('appointments', 'arrived_at')) {
                $table->timestamp('arrived_at')->nullable()->after('checked_in_at');
            }

            if (!Schema::hasColumn('appointments', 'confirmed_at')) {
                $table->timestamp('confirmed_at')->nullable()->after('arrived_at');
            }

            if (!Schema::hasColumn('appointments', 'confirmed_by')) {
                $table->unsignedBigInteger('confirmed_by')->nullable()->after('confirmed_at');
                $table->foreign('confirmed_by')->references('user_id')->on('users')->nullOnDelete();
            }

            // Duration (expected consultation time in minutes)
            if (!Schema::hasColumn('appointments', 'duration_minutes')) {
                $table->integer('duration_minutes')->default(15)->after('slot_end_time');
            }

            // Reminder sent flag
            if (!Schema::hasColumn('appointments', 'reminder_sent')) {
                $table->boolean('reminder_sent')->default(false)->after('duration_minutes');
            }

            // Source of booking
            if (!Schema::hasColumn('appointments', 'booking_source')) {
                $table->enum('booking_source', ['reception', 'portal', 'mobile_app', 'call_center'])->default('reception')->after('reminder_sent');
            }
        });

        // Update status enum to include 'in_consultation'
        DB::statement("ALTER TABLE appointments MODIFY COLUMN status ENUM('scheduled', 'confirmed', 'checked_in', 'in_consultation', 'completed', 'cancelled', 'no_show', 'transferred') DEFAULT 'scheduled'");
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            // Drop foreign keys first
            $table->dropForeign(['cancel_reason_id']);
            $table->dropForeign(['cancelled_by']);
            $table->dropForeign(['original_doctor_id']);
            $table->dropForeign(['confirmed_by']);

            // Drop columns
            $columns = [
                'booking_mode', 'service_type', 'is_online', 'priority',
                'cancel_reason_id', 'cancel_remarks', 'cancelled_at', 'cancelled_by',
                'transferred_from_id', 'original_doctor_id',
                'arrived_at', 'confirmed_at', 'confirmed_by',
                'duration_minutes', 'reminder_sent', 'booking_source'
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('appointments', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        // Revert status enum
        DB::statement("ALTER TABLE appointments MODIFY COLUMN status ENUM('scheduled', 'confirmed', 'checked_in', 'completed', 'cancelled', 'no_show') DEFAULT 'scheduled'");
    }
};
