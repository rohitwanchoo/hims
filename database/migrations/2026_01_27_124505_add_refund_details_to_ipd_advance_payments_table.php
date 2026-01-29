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
        Schema::table('ipd_advance_payments', function (Blueprint $table) {
            $table->string('refund_reason')->nullable()->after('refund_date');
            $table->string('refund_mode')->nullable()->after('refund_reason');
            $table->string('authorized_by')->nullable()->after('refund_mode');
            $table->unsignedBigInteger('refunded_by')->nullable()->after('authorized_by');

            $table->foreign('refunded_by')->references('user_id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ipd_advance_payments', function (Blueprint $table) {
            $table->dropForeign(['refunded_by']);
            $table->dropColumn(['refund_reason', 'refund_mode', 'authorized_by', 'refunded_by']);
        });
    }
};
