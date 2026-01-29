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
        Schema::create('bed_transfers', function (Blueprint $table) {
            $table->id('transfer_id');
            $table->unsignedBigInteger('hospital_id');
            $table->unsignedBigInteger('ipd_id');
            $table->enum('transfer_type', ['transfer', 'swap', 'move'])->default('transfer');
            $table->unsignedBigInteger('from_bed_id');
            $table->unsignedBigInteger('to_bed_id')->nullable();
            $table->unsignedBigInteger('from_ward_id');
            $table->unsignedBigInteger('to_ward_id')->nullable();
            $table->unsignedBigInteger('swap_ipd_id')->nullable()->comment('For swap type - the other patient being swapped');
            $table->dateTime('transfer_datetime');
            $table->text('reason');
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('transferred_by');
            $table->timestamps();

            $table->foreign('hospital_id')->references('hospital_id')->on('hospitals')->onDelete('cascade');
            $table->foreign('ipd_id')->references('ipd_id')->on('ipd_admissions')->onDelete('cascade');
            $table->foreign('from_bed_id')->references('bed_id')->on('beds')->onDelete('cascade');
            $table->foreign('to_bed_id')->references('bed_id')->on('beds')->onDelete('cascade');
            $table->foreign('from_ward_id')->references('ward_id')->on('wards')->onDelete('cascade');
            $table->foreign('to_ward_id')->references('ward_id')->on('wards')->onDelete('cascade');
            $table->foreign('swap_ipd_id')->references('ipd_id')->on('ipd_admissions')->onDelete('cascade');
            $table->foreign('transferred_by')->references('user_id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bed_transfers');
    }
};
