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
        Schema::table('bill_details', function (Blueprint $table) {
            $table->unsignedBigInteger('cost_head_id')->nullable()->after('item_type');

            // Add foreign key if cost_heads table exists
            if (Schema::hasTable('cost_heads')) {
                $table->foreign('cost_head_id')->references('cost_head_id')->on('cost_heads')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bill_details', function (Blueprint $table) {
            // Drop foreign key if it exists
            if (Schema::hasTable('cost_heads')) {
                $table->dropForeign(['cost_head_id']);
            }
            $table->dropColumn('cost_head_id');
        });
    }
};
