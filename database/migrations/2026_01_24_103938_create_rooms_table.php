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
        Schema::create('rooms', function (Blueprint $table) {
            $table->id('room_id');
            $table->foreignId('ward_id')->constrained('wards', 'ward_id')->onDelete('cascade');
            $table->string('room_name', 50);
            $table->integer('bed_capacity')->default(1);
            $table->enum('room_type', ['general', 'private', 'semi_private', 'icu', 'isolation'])->default('general');
            $table->integer('floor_number')->nullable();
            $table->string('room_description')->nullable();
            $table->timestamps();

            $table->index(['ward_id', 'room_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
