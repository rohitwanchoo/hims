<?php

namespace App\Models;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Model;

class FhirResource extends Model
{
    use BelongsToHospital;

    protected $primaryKey = 'resource_id';

    protected $fillable = [
        'hospital_id', 'resource_type', 'resource_fhir_id',
        'local_reference_type', 'local_reference_id',
        'resource_json', 'version', 'last_updated',
        'is_deleted', 'meta_version_id',
    ];

    protected $casts = [
        'resource_json' => 'array',
        'last_updated' => 'datetime',
        'is_deleted' => 'boolean',
    ];

    public function getLocalReference()
    {
        if (!$this->local_reference_type || !$this->local_reference_id) {
            return null;
        }

        return match ($this->local_reference_type) {
            'patient' => Patient::find($this->local_reference_id),
            'doctor' => Doctor::find($this->local_reference_id),
            'appointment' => Appointment::find($this->local_reference_id),
            'lab_result' => LabResult::find($this->local_reference_id),
            'prescription' => Prescription::find($this->local_reference_id),
            'opd_visit' => OpdVisit::find($this->local_reference_id),
            'ipd_admission' => IpdAdmission::find($this->local_reference_id),
            default => null,
        };
    }

    public function scopeByResourceType($query, string $type)
    {
        return $query->where('resource_type', $type);
    }

    public function scopeActive($query)
    {
        return $query->where('is_deleted', false);
    }

    public function scopeForLocalReference($query, string $type, int $id)
    {
        return $query->where('local_reference_type', $type)
            ->where('local_reference_id', $id);
    }

    public function incrementVersion(): void
    {
        $this->version++;
        $this->meta_version_id = $this->version;
        $this->last_updated = now();
        $this->save();
    }
}
