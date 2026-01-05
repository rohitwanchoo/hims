<?php

namespace App\Models;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationPreference extends Model
{
    use BelongsToHospital;

    protected $primaryKey = 'preference_id';

    protected $fillable = [
        'hospital_id',
        'patient_id',
        'user_id',
        'notification_type',
        'sms_enabled',
        'email_enabled',
        'push_enabled',
    ];

    protected $casts = [
        'sms_enabled' => 'boolean',
        'email_enabled' => 'boolean',
        'push_enabled' => 'boolean',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id', 'patient_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public static function getForPatient(int $hospitalId, int $patientId, string $type): ?self
    {
        return static::where('hospital_id', $hospitalId)
            ->where('patient_id', $patientId)
            ->where('notification_type', $type)
            ->first();
    }

    public static function getForUser(int $hospitalId, int $userId, string $type): ?self
    {
        return static::where('hospital_id', $hospitalId)
            ->where('user_id', $userId)
            ->where('notification_type', $type)
            ->first();
    }

    public static function isSmsEnabled(int $hospitalId, int $patientId, string $type): bool
    {
        $pref = static::getForPatient($hospitalId, $patientId, $type);
        return $pref ? $pref->sms_enabled : true; // Default to enabled
    }

    public static function isEmailEnabled(int $hospitalId, int $patientId, string $type): bool
    {
        $pref = static::getForPatient($hospitalId, $patientId, $type);
        return $pref ? $pref->email_enabled : true; // Default to enabled
    }
}
