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
        Schema::table('drug_masters', function (Blueprint $table) {
            $table->enum('language', ['english', 'marathi', 'hindi'])->default('english')->after('drug_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('drug_masters', function (Blueprint $table) {
            $table->dropColumn('language');
        });
    }
};
