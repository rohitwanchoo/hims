<?php

namespace App\Models\Pathology;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PathoFaculty extends Model
{
    use HasFactory, BelongsToHospital;

    protected $table = 'patho_faculty';
    protected $primaryKey = 'faculty_id';

    protected $fillable = [
        'hospital_id',
        'faculty_name',
        'faculty_code',
        'is_active',
        'remarks',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the pathology test reports belonging to this faculty.
     */
    public function pathoTestReports()
    {
        return $this->hasMany(PathoTestReport::class, 'faculty_id', 'faculty_id');
    }

    /**
     * Get the pathologist-doctor mappings for this faculty.
     */
    public function pathologistDoctorMaps()
    {
        return $this->hasMany(PathologistDoctorMap::class, 'faculty_id', 'faculty_id');
    }
}
