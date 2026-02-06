<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PathoTestReport extends Model
{
    use HasFactory;

    protected $table = 'patho_test_reports';
    protected $primaryKey = 'report_id';

    protected $fillable = [
        'hospital_id',
        'report_name',
        'report_code',
        'service_id',
        'faculty_id',
        'report_type',
        'is_multi_value',
        'is_active',
        'report_in_new_page',
        'is_non_routine',
        'is_confidential',
        'is_premium',
        'notes',
        'interpretation',
        'remarks',
        'tat_hours',
        'tat_days',
        'show_previous_result',
        'base_price',
        'day_emergency_rate',
        'night_emergency_rate',
        'price_from_date',
        'price_to_date',
        'lab_type',
        'external_lab_id',
    ];

    protected $casts = [
        'is_multi_value' => 'boolean',
        'is_active' => 'boolean',
        'report_in_new_page' => 'boolean',
        'is_non_routine' => 'boolean',
        'is_confidential' => 'boolean',
        'is_premium' => 'boolean',
        'show_previous_result' => 'boolean',
        'tat_hours' => 'integer',
        'tat_days' => 'integer',
        'base_price' => 'decimal:2',
        'day_emergency_rate' => 'decimal:2',
        'night_emergency_rate' => 'decimal:2',
        'price_from_date' => 'date',
        'price_to_date' => 'date',
    ];

    // Relationships
    public function hospital()
    {
        return $this->belongsTo(Hospital::class, 'hospital_id', 'hospital_id');
    }

    public function faculty()
    {
        return $this->belongsTo(PathoFaculty::class, 'faculty_id', 'faculty_id');
    }

    public function externalLab()
    {
        return $this->belongsTo(ExternalLabCenter::class, 'external_lab_id', 'lab_id');
    }
}
