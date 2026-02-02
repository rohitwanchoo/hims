<?php

namespace App\Models\Pathology;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SkillPathoTestMap extends Model
{
    use HasFactory, BelongsToHospital;

    protected $table = 'skill_patho_test_map';
    protected $primaryKey = 'map_id';

    protected $fillable = [
        'hospital_id',
        'skill_id',
        'test_report_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the pathology test report this mapping belongs to.
     */
    public function pathoTestReport()
    {
        return $this->belongsTo(PathoTestReport::class, 'test_report_id', 'report_id');
    }
}
