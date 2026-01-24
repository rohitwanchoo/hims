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
        Schema::table('prescriptions', function (Blueprint $table) {
            if (!Schema::hasColumn('prescriptions', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('qty_display_on_print');
                $table->foreign('created_by')->references('user_id')->on('users')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prescriptions', function (Blueprint $table) {
            if (Schema::hasColumn('prescriptions', 'created_by')) {
                $table->dropForeign(['created_by']);
                $table->dropColumn('created_by');
            }
        });
    }
};
