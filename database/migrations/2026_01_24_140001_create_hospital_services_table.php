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
        Schema::create('hospital_services', function (Blueprint $table) {
            $table->id('hospital_service_id');
            $table->unsignedBigInteger('hospital_id');
            $table->unsignedBigInteger('insurance_id')->nullable();
            $table->unsignedBigInteger('cost_head_id');
            $table->string('service_name', 200);
            $table->text('description')->nullable();
            $table->decimal('base_price', 10, 2)->default(0);
            $table->enum('price_unit', ['per_day', 'per_service', 'per_hour'])->default('per_service');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('hospital_id')->references('hospital_id')->on('hospitals')->onDelete('cascade');
            $table->foreign('insurance_id')->references('insurance_id')->on('insurance_companies')->onDelete('set null');
            $table->foreign('cost_head_id')->references('cost_head_id')->on('cost_heads')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hospital_services');
    }
};
