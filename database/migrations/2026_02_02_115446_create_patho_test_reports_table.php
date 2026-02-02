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
        Schema::create('patho_test_reports', function (Blueprint $table) {
            $table->id('report_id');
            $table->foreignId('hospital_id')->constrained('hospitals', 'hospital_id')->onDelete('cascade');
            $table->string('report_name');
            $table->string('report_code')->nullable();
            $table->unsignedBigInteger('service_id')->nullable();
            $table->unsignedBigInteger('faculty_id');
            $table->enum('report_type', ['normal', 'multicolumn', 'biopsy'])->default('normal');
            $table->boolean('is_multi_value')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('report_in_new_page')->default(false);
            $table->boolean('is_non_routine')->default(false);
            $table->boolean('is_confidential')->default(false);
            $table->boolean('is_premium')->default(false);
            $table->text('notes')->nullable();
            $table->text('interpretation')->nullable();
            $table->text('remarks')->nullable();
            $table->integer('tat_hours')->nullable();
            $table->integer('tat_days')->nullable();
            $table->boolean('show_previous_result')->default(false);
            $table->decimal('base_price', 10, 2)->default(0);
            $table->decimal('day_emergency_rate', 10, 2)->nullable();
            $table->decimal('night_emergency_rate', 10, 2)->nullable();
            $table->date('price_from_date')->nullable();
            $table->date('price_to_date')->nullable();
            $table->enum('lab_type', ['internal', 'external'])->default('internal');
            $table->unsignedBigInteger('external_lab_id')->nullable();
            $table->timestamps();

            $table->foreign('faculty_id')->references('faculty_id')->on('patho_faculties')->onDelete('cascade');
            $table->foreign('external_lab_id')->references('lab_id')->on('external_lab_centers')->onDelete('set null');
            $table->index('hospital_id');
            $table->index('report_code');
            $table->index('service_id');
            $table->index('faculty_id');
            $table->index('lab_type');
            $table->index('external_lab_id');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patho_test_reports');
    }
};
