<?php

namespace App\Models;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Model;

class BedTransfer extends Model
{
    use BelongsToHospital;

    protected $primaryKey = 'transfer_id';

    protected $fillable = [
        'hospital_id',
        'ipd_id',
        'from_bed_id',
        'to_bed_id',
        'from_ward_id',
        'to_ward_id',
        'transfer_datetime',
        'transfer_reason',
        'remarks',
        'transferred_by',
    ];

    protected $casts = [
        'transfer_datetime' => 'datetime',
    ];

    public function ipdAdmission()
    {
        return $this->belongsTo(IpdAdmission::class, 'ipd_id', 'ipd_id');
    }

    public function fromBed()
    {
        return $this->belongsTo(Bed::class, 'from_bed_id', 'bed_id');
    }

    public function toBed()
    {
        return $this->belongsTo(Bed::class, 'to_bed_id', 'bed_id');
    }

    public function fromWard()
    {
        return $this->belongsTo(Ward::class, 'from_ward_id', 'ward_id');
    }

    public function toWard()
    {
        return $this->belongsTo(Ward::class, 'to_ward_id', 'ward_id');
    }

    public function transferredByUser()
    {
        return $this->belongsTo(User::class, 'transferred_by', 'user_id');
    }
}
