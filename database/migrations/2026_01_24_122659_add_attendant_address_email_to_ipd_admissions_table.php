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
        Schema::table('ipd_admissions', function (Blueprint $table) {
            $table->text('attendant_address')->nullable()->after('attendant_mobile');
            $table->string('attendant_email', 100)->nullable()->after('attendant_address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ipd_admissions', function (Blueprint $table) {
            $table->dropColumn(['attendant_address', 'attendant_email']);
        });
    }
};
