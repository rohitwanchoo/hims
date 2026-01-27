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
        Schema::table('hospital_services', function (Blueprint $table) {
            $table->decimal('day_emergency_rate', 10, 2)->default(0)->after('base_price');
            $table->decimal('night_emergency_rate', 10, 2)->default(0)->after('day_emergency_rate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hospital_services', function (Blueprint $table) {
            $table->dropColumn(['day_emergency_rate', 'night_emergency_rate']);
        });
    }
};
