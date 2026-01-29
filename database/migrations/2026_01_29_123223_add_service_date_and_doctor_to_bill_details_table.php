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
        Schema::table('bill_details', function (Blueprint $table) {
            $table->dateTime('service_date')->nullable()->after('item_name');
            $table->unsignedBigInteger('doctor_id')->nullable()->after('service_date');

            $table->foreign('doctor_id')->references('doctor_id')->on('doctors')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bill_details', function (Blueprint $table) {
            $table->dropForeign(['doctor_id']);
            $table->dropColumn(['service_date', 'doctor_id']);
        });
    }
};
