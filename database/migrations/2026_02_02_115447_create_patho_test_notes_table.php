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
        Schema::create('patho_test_notes', function (Blueprint $table) {
            $table->id('note_id');
            $table->foreignId('hospital_id')->constrained('hospitals', 'hospital_id')->onDelete('cascade');
            $table->enum('note_for', ['test_master', 'test_report'])->default('test_master');
            $table->unsignedBigInteger('test_id')->nullable();
            $table->unsignedBigInteger('report_id')->nullable();
            $table->string('age_group')->nullable();
            $table->text('note_text');
            $table->text('test_remark')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_abnormal')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('test_id')->references('test_id')->on('patho_tests')->onDelete('cascade');
            $table->index('hospital_id');
            $table->index('note_for');
            $table->index('test_id');
            $table->index('report_id');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patho_test_notes');
    }
};
