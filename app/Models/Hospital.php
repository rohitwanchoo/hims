<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Hospital extends Model
{
    use HasFactory;

    protected $primaryKey = 'hospital_id';

    protected $fillable = [
        'code',
        'name',
        'type',
        'address',
        'city',
        'state',
        'country',
        'pincode',
        'phone',
        'email',
        'website',
        'logo',
        'license_number',
        'license_expiry',
        'tax_id',
        'settings',
        'is_active',
        'subscription_start',
        'subscription_end',
        'subscription_plan',
    ];

    protected $casts = [
        'settings' => 'array',
        'is_active' => 'boolean',
        'license_expiry' => 'date',
        'subscription_start' => 'date',
        'subscription_end' => 'date',
    ];

    // Relationships
    public function users()
    {
        return $this->hasMany(User::class, 'hospital_id', 'hospital_id');
    }

    public function patients()
    {
        return $this->hasMany(Patient::class, 'hospital_id', 'hospital_id');
    }

    public function doctors()
    {
        return $this->hasMany(Doctor::class, 'hospital_id', 'hospital_id');
    }

    public function departments()
    {
        return $this->hasMany(Department::class, 'hospital_id', 'hospital_id');
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class, 'hospital_id', 'hospital_id');
    }

    public function opdVisits()
    {
        return $this->hasMany(OpdVisit::class, 'hospital_id', 'hospital_id');
    }

    public function ipdAdmissions()
    {
        return $this->hasMany(IpdAdmission::class, 'hospital_id', 'hospital_id');
    }

    public function bills()
    {
        return $this->hasMany(Bill::class, 'hospital_id', 'hospital_id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'hospital_id', 'hospital_id');
    }

    // Helpers
    public function isSubscriptionActive(): bool
    {
        if (!$this->subscription_end) {
            return true; // No end date means unlimited
        }
        return $this->subscription_end->isFuture();
    }

    public function getSetting(string $key, $default = null)
    {
        return data_get($this->settings, $key, $default);
    }
}
