<?php

namespace App\Models\Pathology;

use App\Models\Doctor;
use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PathologistDoctorMap extends Model
{
    use HasFactory, BelongsToHospital;

    protected $table = 'pathologist_doctor_maps';
    protected $primaryKey = 'map_id';

    protected $fillable = [
        'hospital_id',
        'faculty_id',
        'doctor_id',
        'skill_set_id',
        'signature_path',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the faculty this mapping belongs to.
     */
    public function faculty()
    {
        return $this->belongsTo(PathoFaculty::class, 'faculty_id', 'faculty_id');
    }

    /**
     * Get the doctor this mapping belongs to.
     */
    public function doctor()
    {
        return $this->belongsTo(Doctor::class, 'doctor_id', 'doctor_id');
    }
}
