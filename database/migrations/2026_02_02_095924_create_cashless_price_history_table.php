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
        Schema::create('cashless_price_history', function (Blueprint $table) {
            $table->id('history_id');
            $table->unsignedBigInteger('hospital_service_id');
            $table->unsignedBigInteger('hospital_id');
            $table->string('service_name', 200);
            $table->string('insurance_company_name', 255)->nullable();

            // Previous values
            $table->decimal('old_cl_rate', 10, 2)->default(0.00);
            $table->decimal('old_cl_day_emergency_rate', 10, 2)->default(0.00);
            $table->decimal('old_cl_night_emergency_rate', 10, 2)->default(0.00);
            $table->date('old_from_date')->nullable();
            $table->date('old_to_date')->nullable();

            // New values
            $table->decimal('new_cl_rate', 10, 2)->default(0.00);
            $table->decimal('new_cl_day_emergency_rate', 10, 2)->default(0.00);
            $table->decimal('new_cl_night_emergency_rate', 10, 2)->default(0.00);
            $table->date('new_from_date')->nullable();
            $table->date('new_to_date')->nullable();

            // Tracking info
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->string('updated_by_name', 100)->nullable();
            $table->timestamp('updated_at');

            // Foreign keys
            $table->foreign('hospital_service_id')->references('hospital_service_id')->on('hospital_services')->onDelete('cascade');

            $table->index(['hospital_service_id', 'updated_at']);
            $table->index('hospital_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cashless_price_history');
    }
};
