<?php

namespace App\Models;

use App\Traits\BelongsToHospital;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PatientUser extends Authenticatable
{
    use HasApiTokens, Notifiable, BelongsToHospital;

    protected $primaryKey = 'patient_user_id';

    protected $fillable = [
        'hospital_id', 'patient_id', 'username', 'email', 'mobile', 'password',
        'email_verified_at', 'mobile_verified_at', 'is_active', 'last_login_at',
        'otp', 'otp_expires_at',
    ];

    protected $hidden = [
        'password', 'remember_token', 'otp',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'mobile_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'otp_expires_at' => 'datetime',
        'is_active' => 'boolean',
        'password' => 'hashed',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id', 'patient_id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(PatientPortalSession::class, 'patient_user_id', 'patient_user_id');
    }

    public function appointmentRequests(): HasMany
    {
        return $this->hasMany(PatientAppointmentRequest::class, 'patient_user_id', 'patient_user_id');
    }

    public function feedbacks(): HasMany
    {
        return $this->hasMany(PatientFeedback::class, 'patient_user_id', 'patient_user_id');
    }

    public function generateOtp(): string
    {
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $this->update([
            'otp' => $otp,
            'otp_expires_at' => now()->addMinutes(10),
        ]);
        return $otp;
    }

    public function verifyOtp(string $otp): bool
    {
        if ($this->otp === $otp && $this->otp_expires_at && $this->otp_expires_at->isFuture()) {
            $this->update([
                'otp' => null,
                'otp_expires_at' => null,
                'mobile_verified_at' => now(),
            ]);
            return true;
        }
        return false;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
