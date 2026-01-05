<?php

namespace App\Models;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Model;

class EntryCard extends Model
{
    use BelongsToHospital;

    protected $primaryKey = 'entry_card_id';

    protected $fillable = [
        'hospital_id',
        'card_number',
        'patient_id',
        'amount',
        'issue_date',
        'valid_from',
        'valid_to',
        'validity_type',
        'status',
        'bill_id',
        'issued_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'issue_date' => 'date',
        'valid_from' => 'date',
        'valid_to' => 'date',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id', 'patient_id');
    }

    public function bill()
    {
        return $this->belongsTo(Bill::class, 'bill_id', 'bill_id');
    }

    public function issuedBy()
    {
        return $this->belongsTo(User::class, 'issued_by', 'id');
    }

    /**
     * Check if entry card is valid
     */
    public function isValid()
    {
        if ($this->status !== 'active') return false;
        if ($this->valid_to && $this->valid_to < now()->toDateString()) return false;
        return true;
    }

    /**
     * Get valid entry card for a patient
     */
    public static function getValidCardForPatient($patientId)
    {
        return self::where('patient_id', $patientId)
            ->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('valid_to')
                  ->orWhere('valid_to', '>=', now()->toDateString());
            })
            ->first();
    }

    /**
     * Generate unique card number
     */
    public static function generateCardNumber($hospitalId)
    {
        $count = self::where('hospital_id', $hospitalId)
            ->whereYear('created_at', now()->year)
            ->count();

        return 'EC' . now()->format('Y') . str_pad($count + 1, 6, '0', STR_PAD_LEFT);
    }
}
