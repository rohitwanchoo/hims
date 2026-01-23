<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Table name => primary key column
    protected $tables = [
        'clients' => 'client_id',
        'classes' => 'class_id',
        'cashless_price_lists' => 'price_list_id',
        'ward_wise_cost_additions' => 'wwca_id',
        'payment_modes' => 'payment_mode_id',
        'vaccinations' => 'vaccination_id',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach ($this->tables as $tableName => $primaryKey) {
            if (Schema::hasTable($tableName) && !Schema::hasColumn($tableName, 'hospital_id')) {
                Schema::table($tableName, function (Blueprint $table) use ($primaryKey) {
                    $table->unsignedBigInteger('hospital_id')->nullable()->after($primaryKey);
                    $table->foreign('hospital_id')->references('hospital_id')->on('hospitals')->onDelete('cascade');
                    $table->index('hospital_id');
                });

                // Set default hospital_id to 1 for existing records
                DB::table($tableName)->whereNull('hospital_id')->update(['hospital_id' => 1]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach ($this->tables as $tableName => $primaryKey) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'hospital_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropForeign(['hospital_id']);
                    $table->dropColumn('hospital_id');
                });
            }
        }
    }
};
