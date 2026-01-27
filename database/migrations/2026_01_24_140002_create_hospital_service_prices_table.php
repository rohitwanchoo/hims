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
        Schema::create('hospital_service_prices', function (Blueprint $table) {
            $table->id('price_id');
            $table->unsignedBigInteger('hospital_service_id');
            $table->unsignedBigInteger('room_id')->nullable();
            $table->unsignedBigInteger('bed_id')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->timestamps();

            $table->foreign('hospital_service_id')->references('hospital_service_id')->on('hospital_services')->onDelete('cascade');
            $table->foreign('room_id')->references('room_id')->on('rooms')->onDelete('cascade');
            $table->foreign('bed_id')->references('bed_id')->on('beds')->onDelete('cascade');

            // Unique constraint: one price per service per room/bed combination
            $table->unique(['hospital_service_id', 'room_id', 'bed_id'], 'unique_service_room_bed_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hospital_service_prices');
    }
};
