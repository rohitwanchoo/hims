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
        Schema::create('cost_heads', function (Blueprint $table) {
            $table->id('cost_head_id');
            $table->unsignedBigInteger('hospital_id');
            $table->string('cost_head_code', 50)->unique();
            $table->string('cost_head_name', 200);
            $table->enum('cost_head_type', ['ipd_services', 'opd_services', 'lab_services', 'pharmacy', 'radiology', 'procedure', 'others'])->default('opd_services');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('hospital_id')->references('hospital_id')->on('hospitals')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cost_heads');
    }
};
