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
        Schema::table('consultation_form_fields', function (Blueprint $table) {
            // Add column width - controls how many columns field takes (1, 2, 3, or 4 per row)
            $table->string('column_width')->default('col-md-6')->after('section');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consultation_form_fields', function (Blueprint $table) {
            $table->dropColumn('column_width');
        });
    }
};
