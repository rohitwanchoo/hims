<?php

namespace App\Models;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MrdFileMovement extends Model
{
    use BelongsToHospital;

    protected $primaryKey = 'movement_id';

    protected $fillable = [
        'hospital_id', 'patient_id', 'file_number', 'movement_type',
        'from_location', 'to_location', 'purpose', 'issued_to',
        'issued_by', 'issued_at', 'expected_return_date',
        'returned_at', 'returned_by', 'remarks',
    ];

    protected $casts = [
        'issued_at' => 'datetime',
        'expected_return_date' => 'date',
        'returned_at' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id', 'patient_id');
    }

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by', 'user_id');
    }

    public function returnedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'returned_by', 'user_id');
    }

    public function isOverdue(): bool
    {
        return $this->expected_return_date
            && $this->expected_return_date->isPast()
            && !$this->returned_at;
    }

    public function scopeOutstanding($query)
    {
        return $query->whereNull('returned_at')
            ->where('movement_type', 'issued');
    }

    public function scopeOverdue($query)
    {
        return $query->whereNull('returned_at')
            ->where('expected_return_date', '<', now());
    }
}
