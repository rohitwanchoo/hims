<?php

namespace App\Models;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class OtProcedure extends Model
{
    use BelongsToHospital;

    protected $primaryKey = 'procedure_id';

    protected $fillable = [
        'hospital_id',
        'schedule_id',
        'patient_id',
        'ipd_id',
        'ot_id',
        'procedure_number',
        'procedure_date',
        'actual_start_time',
        'actual_end_time',
        'surgeon_id',
        'assistant_surgeon_id',
        'anesthetist_id',
        'scrub_nurse_id',
        'circulating_nurse_id',
        'anesthesia_start_time',
        'anesthesia_end_time',
        'incision_time',
        'closure_time',
        'pre_op_diagnosis',
        'post_op_diagnosis',
        'procedure_performed',
        'procedure_notes',
        'complications',
        'blood_loss_ml',
        'blood_transfused',
        'specimens_collected',
        'implants_used',
        'drain_placed',
        'drain_details',
        'wound_classification',
        'patient_condition_post_op',
        'post_op_instructions',
        'icu_required',
        'status',
    ];

    protected $casts = [
        'procedure_date' => 'date',
        'drain_placed' => 'boolean',
        'icu_required' => 'boolean',
    ];

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(OtSchedule::class, 'schedule_id', 'schedule_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id', 'patient_id');
    }

    public function operationTheater(): BelongsTo
    {
        return $this->belongsTo(OperationTheater::class, 'ot_id', 'ot_id');
    }

    public function surgeon(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'surgeon_id', 'doctor_id');
    }

    public function anesthetist(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'anesthetist_id', 'doctor_id');
    }

    public function consumables(): HasMany
    {
        return $this->hasMany(OtConsumable::class, 'procedure_id', 'procedure_id');
    }

    public function anesthesiaRecord(): HasOne
    {
        return $this->hasOne(OtAnesthesiaRecord::class, 'procedure_id', 'procedure_id');
    }

    public static function generateProcedureNumber(int $hospitalId): string
    {
        $prefix = 'OTP';
        $date = now()->format('Ymd');
        $count = static::where('hospital_id', $hospitalId)
            ->whereDate('created_at', today())
            ->count();
        return $prefix . $date . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
    }
}
