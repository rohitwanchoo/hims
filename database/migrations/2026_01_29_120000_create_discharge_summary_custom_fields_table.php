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
        Schema::create('discharge_summary_custom_fields', function (Blueprint $table) {
            $table->id('field_id');
            $table->unsignedBigInteger('hospital_id');
            $table->string('field_name', 100);
            $table->string('field_label', 200);
            $table->enum('field_type', ['text', 'textarea', 'select', 'date', 'number']);
            $table->text('field_options')->nullable(); // JSON for dropdown options
            $table->string('section', 50)->default('custom'); // custom, history, diagnosis, treatment, medications, discharge
            $table->integer('display_order')->default(0);
            $table->boolean('is_required')->default(false);
            $table->boolean('is_active')->default(true);
            $table->string('placeholder', 200)->nullable();
            $table->text('help_text')->nullable();
            $table->timestamps();

            $table->foreign('hospital_id')->references('hospital_id')->on('hospitals')->cascadeOnDelete();
            $table->index(['hospital_id', 'is_active']);
        });

        Schema::create('discharge_summary_custom_field_values', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('discharge_summary_id');
            $table->unsignedBigInteger('field_id');
            $table->text('field_value')->nullable();
            $table->timestamps();

            $table->foreign('discharge_summary_id', 'ds_custom_field_values_ds_id_fk')
                  ->references('discharge_summary_id')->on('discharge_summaries')->cascadeOnDelete();
            $table->foreign('field_id', 'ds_custom_field_values_field_id_fk')
                  ->references('field_id')->on('discharge_summary_custom_fields')->cascadeOnDelete();
            $table->unique(['discharge_summary_id', 'field_id'], 'ds_custom_field_values_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discharge_summary_custom_field_values');
        Schema::dropIfExists('discharge_summary_custom_fields');
    }
};
