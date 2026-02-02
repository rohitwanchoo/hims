<?php

namespace App\Models\Pathology;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PathoTestReport extends Model
{
    use HasFactory, BelongsToHospital;

    protected $table = 'patho_test_report';
    protected $primaryKey = 'report_id';

    protected $fillable = [
        'hospital_id',
        'report_name',
        'report_code',
        'service_id',
        'faculty_id',
        'category_id',
        'group_id',
        'report_type',
        'base_price',
        'discount_percentage',
        'final_price',
        'tat_hours',
        'is_package',
        'is_outsourced',
        'external_lab_id',
        'report_template',
        'header_text',
        'footer_text',
        'is_active',
        'remarks',
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'final_price' => 'decimal:2',
        'tat_hours' => 'integer',
        'is_package' => 'boolean',
        'is_outsourced' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Get the faculty this report belongs to.
     */
    public function faculty()
    {
        return $this->belongsTo(PathoFaculty::class, 'faculty_id', 'faculty_id');
    }

    /**
     * Get the category this report belongs to.
     */
    public function category()
    {
        return $this->belongsTo(PathoTestCategory::class, 'category_id', 'category_id');
    }

    /**
     * Get the group this report belongs to.
     */
    public function group()
    {
        return $this->belongsTo(PathoTestGroup::class, 'group_id', 'group_id');
    }

    /**
     * Get the external lab if outsourced.
     */
    public function externalLab()
    {
        return $this->belongsTo(ExternalLabCenter::class, 'external_lab_id', 'lab_id');
    }

    /**
     * Get the test notes for this report.
     */
    public function testNotes()
    {
        return $this->hasMany(PathoTestNote::class, 'report_id', 'report_id');
    }

    /**
     * Get the skill mappings for this report.
     */
    public function skillMappings()
    {
        return $this->hasMany(SkillPathoTestMap::class, 'test_report_id', 'report_id');
    }
}
