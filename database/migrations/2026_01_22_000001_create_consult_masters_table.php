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
        Schema::create('consult_masters', function (Blueprint $table) {
            $table->id('consult_master_id');
            $table->unsignedBigInteger('hospital_id')->nullable();
            $table->unsignedBigInteger('department_id');
            $table->unsignedBigInteger('doctor_id');

            // Day of week (1=Monday, 7=Sunday) or null for all days
            $table->tinyInteger('day_of_week')->nullable()->comment('1=Monday, 7=Sunday, null=All days');

            // Date-specific schedule (overrides day_of_week if set)
            $table->date('specific_date')->nullable()->comment('For specific date scheduling');

            // Time period: morning, afternoon, evening
            $table->enum('time_period', ['morning', 'afternoon', 'evening']);

            // Actual time range
            $table->time('start_time');
            $table->time('end_time');

            // Slot duration in minutes (5, 10, 15, 20, 30, etc.)
            $table->integer('slot_duration')->comment('Duration in minutes');

            // Auto-generated slots stored as JSON
            $table->json('time_slots')->nullable()->comment('Generated time slots');

            // Maximum patients per slot
            $table->integer('max_patients_per_slot')->default(1);

            // Status
            $table->boolean('is_active')->default(true);

            // Additional settings
            $table->text('notes')->nullable();

            $table->timestamps();

            // Foreign keys
            $table->foreign('hospital_id')
                ->references('hospital_id')
                ->on('hospitals')
                ->onDelete('cascade');

            $table->foreign('department_id')
                ->references('department_id')
                ->on('departments')
                ->onDelete('cascade');

            $table->foreign('doctor_id')
                ->references('doctor_id')
                ->on('doctors')
                ->onDelete('cascade');

            // Indexes
            $table->index(['hospital_id', 'doctor_id', 'is_active']);
            $table->index(['hospital_id', 'department_id', 'is_active']);
            $table->index(['specific_date', 'doctor_id']);
            $table->index(['day_of_week', 'doctor_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consult_masters');
    }
};
