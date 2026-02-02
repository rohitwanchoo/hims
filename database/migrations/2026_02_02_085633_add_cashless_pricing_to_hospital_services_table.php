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
            $table->string('cashless_pricelist', 255)->nullable()->after('night_emergency_rate');
            $table->decimal('cl_rate', 10, 2)->default(0.00)->after('cashless_pricelist');
            $table->decimal('cl_day_emergency_rate', 10, 2)->default(0.00)->after('cl_rate');
            $table->decimal('cl_night_emergency_rate', 10, 2)->default(0.00)->after('cl_day_emergency_rate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hospital_services', function (Blueprint $table) {
            $table->dropColumn([
                'cashless_pricelist',
                'cl_rate',
                'cl_day_emergency_rate',
                'cl_night_emergency_rate'
            ]);
        });
    }
};
