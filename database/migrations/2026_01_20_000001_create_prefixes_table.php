<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prefixes', function (Blueprint $table) {
            $table->id('prefix_id');
            $table->unsignedBigInteger('hospital_id')->nullable();
            $table->string('prefix_name', 50);
            $table->string('description', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('hospital_id')->references('hospital_id')->on('hospitals')->onDelete('cascade');
            $table->index(['hospital_id', 'is_active']);
        });

        // Add prefix_id to patients table
        Schema::table('patients', function (Blueprint $table) {
            $table->unsignedBigInteger('prefix_id')->nullable()->after('hospital_id');
            $table->foreign('prefix_id')->references('prefix_id')->on('prefixes')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropForeign(['prefix_id']);
            $table->dropColumn('prefix_id');
        });

        Schema::dropIfExists('prefixes');
    }
};
