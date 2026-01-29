<?php

namespace App\Models;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Model;

class IpdService extends Model
{
    use BelongsToHospital;

    protected $primaryKey = 'ipd_service_id';

    protected $fillable = [
        'hospital_id',
        'ipd_id',
        'service_date',
        'service_time',
        'doctor_id',
        'service_type',
        'service_id',
        'cost_head_id',
        'cost_head_name',
        'service_name',
        'quantity',
        'rate',
        'amount',
        'discount',
        'net_amount',
        'is_package',
        'is_billed',
        'bill_id',
        'remarks',
        'created_by',
    ];

    protected $casts = [
        'service_date' => 'date',
        'quantity' => 'integer',
        'rate' => 'decimal:2',
        'amount' => 'decimal:2',
        'discount' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'is_package' => 'boolean',
        'is_billed' => 'boolean',
    ];

    public function ipdAdmission()
    {
        return $this->belongsTo(IpdAdmission::class, 'ipd_id', 'ipd_id');
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class, 'doctor_id', 'doctor_id');
    }

    public function bill()
    {
        return $this->belongsTo(Bill::class, 'bill_id', 'bill_id');
    }

    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    public function costHead()
    {
        return $this->belongsTo(CostHead::class, 'cost_head_id', 'cost_head_id');
    }

    // Scopes
    public function scopeUnbilled($query)
    {
        return $query->where('is_billed', false);
    }

    public function scopeBilled($query)
    {
        return $query->where('is_billed', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('service_type', $type);
    }

    public function scopeByDate($query, $date)
    {
        return $query->whereDate('service_date', $date);
    }

    // Calculate amount
    public static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            $model->amount = $model->quantity * $model->rate;
            $model->net_amount = $model->amount - ($model->discount ?? 0);
        });
    }
}
