<?php

namespace App\Models\Pathology;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PathoTest extends Model
{
    use HasFactory, BelongsToHospital;

    protected $table = 'patho_test';
    protected $primaryKey = 'test_id';

    protected $fillable = [
        'hospital_id',
        'test_name',
        'test_code',
        'detail_type',
        'value_type',
        'method_id',
        'unit_id',
        'container_id',
        'sample_type_id',
        'category_id',
        'group_id',
        'analyzer_id',
        'external_lab_id',
        'tat_hours',
        'specimen_volume',
        'test_sequence',
        'min_value',
        'max_value',
        'normal_range',
        'critical_low',
        'critical_high',
        'test_instruction',
        'is_outsourced',
        'is_culture',
        'is_profile',
        'is_formula',
        'formula_expression',
        'is_active',
        'remarks',
    ];

    protected $casts = [
        'tat_hours' => 'integer',
        'test_sequence' => 'integer',
        'min_value' => 'decimal:2',
        'max_value' => 'decimal:2',
        'critical_low' => 'decimal:2',
        'critical_high' => 'decimal:2',
        'is_outsourced' => 'boolean',
        'is_culture' => 'boolean',
        'is_profile' => 'boolean',
        'is_formula' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Get the test method.
     */
    public function method()
    {
        return $this->belongsTo(PathoTestMethod::class, 'method_id', 'method_id');
    }

    /**
     * Get the test unit.
     */
    public function unit()
    {
        return $this->belongsTo(PathoTestUnit::class, 'unit_id', 'unit_id');
    }

    /**
     * Get the container.
     */
    public function container()
    {
        return $this->belongsTo(PathoContainer::class, 'container_id', 'container_id');
    }

    /**
     * Get the sample type.
     */
    public function sampleType()
    {
        return $this->belongsTo(PathoSampleType::class, 'sample_type_id', 'sample_type_id');
    }

    /**
     * Get the category.
     */
    public function category()
    {
        return $this->belongsTo(PathoTestCategory::class, 'category_id', 'category_id');
    }

    /**
     * Get the group.
     */
    public function group()
    {
        return $this->belongsTo(PathoTestGroup::class, 'group_id', 'group_id');
    }

    /**
     * Get the analyzer.
     */
    public function analyzer()
    {
        return $this->belongsTo(Analyzer::class, 'analyzer_id', 'analyzer_id');
    }

    /**
     * Get the external lab.
     */
    public function externalLab()
    {
        return $this->belongsTo(ExternalLabCenter::class, 'external_lab_id', 'lab_id');
    }

    /**
     * Get the test notes for this test.
     */
    public function testNotes()
    {
        return $this->hasMany(PathoTestNote::class, 'test_id', 'test_id');
    }
}
