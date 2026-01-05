<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientPortalSession extends Model
{
    protected $primaryKey = 'session_id';

    protected $fillable = [
        'patient_user_id', 'token', 'ip_address', 'user_agent',
        'device_type', 'login_at', 'logout_at', 'expires_at',
    ];

    protected $casts = [
        'login_at' => 'datetime',
        'logout_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function patientUser(): BelongsTo
    {
        return $this->belongsTo(PatientUser::class, 'patient_user_id', 'patient_user_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isActive(): bool
    {
        return !$this->isExpired() && !$this->logout_at;
    }
}
