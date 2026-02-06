<?php

namespace App\Models\Pathology;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SkillPathoTestMap extends Model
{
    use HasFactory, BelongsToHospital;

    protected $table = 'skill_patho_test_maps';
    protected $primaryKey = 'map_id';

    protected $fillable = [
        'hospital_id',
        'department_id',
        'test_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the department this mapping belongs to.
     */
    public function department()
    {
        return $this->belongsTo(\App\Models\Department::class, 'department_id', 'department_id');
    }

    /**
     * Get the pathology test this mapping belongs to.
     */
    public function test()
    {
        return $this->belongsTo(\App\Models\Pathology\PathoTest::class, 'test_id', 'test_id');
    }
}
