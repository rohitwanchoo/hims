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
        Schema::table('bills', function (Blueprint $table) {
            $table->enum('payment_mode', ['cash', 'cashless', 'insurance'])->default('cash')->after('bill_type');
            $table->string('insurance_company')->nullable()->after('payment_mode');
            $table->string('policy_number')->nullable()->after('insurance_company');
            $table->decimal('copay_amount', 10, 2)->nullable()->after('approved_amount');
            $table->decimal('insurance_amount', 10, 2)->nullable()->after('copay_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bills', function (Blueprint $table) {
            $table->dropColumn(['payment_mode', 'insurance_company', 'policy_number', 'copay_amount', 'insurance_amount']);
        });
    }
};
