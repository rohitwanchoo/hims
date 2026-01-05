<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Table name => primary key column
    protected $tables = [
        'users' => 'user_id',
        'patients' => 'patient_id',
        'doctors' => 'doctor_id',
        'departments' => 'department_id',
        'appointments' => 'appointment_id',
        'opd_visits' => 'opd_id',
        'ipd_admissions' => 'ipd_id',
        'wards' => 'ward_id',
        'beds' => 'bed_id',
        'lab_categories' => 'category_id',
        'lab_tests' => 'test_id',
        'lab_orders' => 'order_id',
        'drug_categories' => 'category_id',
        'drugs' => 'drug_id',
        'drug_batches' => 'batch_id',
        'pharmacy_sales' => 'sale_id',
        'services' => 'service_id',
        'bills' => 'bill_id',
        'payments' => 'payment_id',
    ];

    public function up(): void
    {
        foreach ($this->tables as $tableName => $primaryKey) {
            if (Schema::hasTable($tableName) && !Schema::hasColumn($tableName, 'hospital_id')) {
                Schema::table($tableName, function (Blueprint $table) use ($primaryKey) {
                    $table->unsignedBigInteger('hospital_id')->nullable()->after($primaryKey);
                    $table->foreign('hospital_id')->references('hospital_id')->on('hospitals')->onDelete('cascade');
                    $table->index('hospital_id');
                });
            }
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $tableName => $primaryKey) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'hospital_id')) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    $table->dropForeign([$tableName . '_hospital_id_foreign']);
                    $table->dropColumn('hospital_id');
                });
            }
        }
    }
};
