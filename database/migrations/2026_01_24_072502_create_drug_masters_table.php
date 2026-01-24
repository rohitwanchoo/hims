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
        Schema::create('drug_masters', function (Blueprint $table) {
            $table->id('drug_master_id');
            $table->unsignedBigInteger('hospital_id');
            $table->unsignedBigInteger('drug_type_id')->nullable();
            $table->string('drug_name', 255);
            $table->string('dose_time', 100)->nullable();
            $table->integer('days')->nullable();
            $table->integer('quantity')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('hospital_id')->references('hospital_id')->on('hospitals')->onDelete('cascade');
            $table->foreign('drug_type_id')->references('drug_type_id')->on('drug_types')->onDelete('set null');
            $table->index(['hospital_id', 'drug_type_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('drug_masters');
    }
};
