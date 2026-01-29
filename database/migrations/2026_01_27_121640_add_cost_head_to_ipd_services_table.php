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
        Schema::table('ipd_services', function (Blueprint $table) {
            $table->unsignedBigInteger('cost_head_id')->nullable()->after('service_id');
            $table->string('cost_head_name')->nullable()->after('cost_head_id');

            $table->foreign('cost_head_id')->references('cost_head_id')->on('cost_heads')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ipd_services', function (Blueprint $table) {
            $table->dropForeign(['cost_head_id']);
            $table->dropColumn(['cost_head_id', 'cost_head_name']);
        });
    }
};
