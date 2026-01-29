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
        'transfer_type',
        'is_move_completed',
        'from_bed_id',
        'to_bed_id',
        'from_ward_id',
        'to_ward_id',
        'swap_ipd_id',
        'parent_move_transfer_id',
        'move_completion_type',
        'transfer_datetime',
        'reason',
        'remarks',
        'transferred_by',
    ];

    protected $casts = [
        'transfer_datetime' => 'datetime',
        'is_move_completed' => 'boolean',
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

    public function swapIpdAdmission()
    {
        return $this->belongsTo(IpdAdmission::class, 'swap_ipd_id', 'ipd_id');
    }

    public function parentMoveTransfer()
    {
        return $this->belongsTo(BedTransfer::class, 'parent_move_transfer_id', 'transfer_id');
    }

    public function completionTransfer()
    {
        return $this->hasOne(BedTransfer::class, 'parent_move_transfer_id', 'transfer_id');
    }

    // Scope to find active moves (not completed)
    public function scopeActiveMoves($query, $ipdId)
    {
        return $query->where('ipd_id', $ipdId)
            ->where('transfer_type', 'move')
            ->where('is_move_completed', false);
    }
}
