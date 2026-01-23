<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('qualifications')) {
            Schema::create('qualifications', function (Blueprint $table) {
                $table->id('qualification_id');
                $table->unsignedBigInteger('hospital_id')->nullable();
                $table->string('qualification_name', 100);
                $table->string('qualification_code', 20)->nullable();
                $table->string('description', 255)->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                
                $table->foreign('hospital_id')->references('hospital_id')->on('hospitals')->onDelete('cascade');
                $table->index(['hospital_id', 'is_active']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('qualifications');
    }
};
