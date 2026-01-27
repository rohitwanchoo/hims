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
            $table->date('from_date')->nullable()->after('description');
            $table->date('to_date')->nullable()->after('from_date');
            $table->boolean('is_free_followup')->default(false)->after('is_active');
            $table->boolean('qty_rate_not_required')->default(false)->after('is_free_followup');
            $table->unsignedBigInteger('gst_plan_id')->nullable()->after('qty_rate_not_required');
            $table->decimal('gst_above_amount', 10, 2)->nullable()->after('gst_plan_id');

            $table->foreign('gst_plan_id')->references('gst_plan_id')->on('gst_plans')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hospital_services', function (Blueprint $table) {
            $table->dropForeign(['gst_plan_id']);
            $table->dropColumn([
                'from_date',
                'to_date',
                'is_free_followup',
                'qty_rate_not_required',
                'gst_plan_id',
                'gst_above_amount'
            ]);
        });
    }
};
