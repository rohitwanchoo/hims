<?php

namespace App\Models;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientDocument extends Model
{
    use BelongsToHospital;

    protected $primaryKey = 'document_id';

    protected $fillable = [
        'hospital_id', 'patient_id', 'document_type', 'document_category',
        'source_type', 'source_id', 'document_date', 'document_title', 'description',
        'file_path', 'file_name', 'file_type', 'file_size',
        'is_confidential', 'uploaded_by', 'verified_by', 'verified_at',
        'is_archived', 'archived_at', 'archived_by',
    ];

    protected $casts = [
        'document_date' => 'date',
        'is_confidential' => 'boolean',
        'is_archived' => 'boolean',
        'verified_at' => 'datetime',
        'archived_at' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id', 'patient_id');
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by', 'user_id');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by', 'user_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_archived', false);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('document_type', $type);
    }
}
