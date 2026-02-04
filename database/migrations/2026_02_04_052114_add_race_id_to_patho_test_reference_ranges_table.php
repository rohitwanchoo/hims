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
        Schema::table('patho_test_reference_ranges', function (Blueprint $table) {
            $table->unsignedBigInteger('race_id')->nullable()->after('age_group_id');
            $table->foreign('race_id')->references('race_id')->on('races')->onDelete('set null');
            $table->index('race_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patho_test_reference_ranges', function (Blueprint $table) {
            $table->dropForeign(['race_id']);
            $table->dropIndex(['race_id']);
            $table->dropColumn('race_id');
        });
    }
};
