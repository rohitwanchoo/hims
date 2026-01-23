<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LabOrderDetail extends Model
{
    protected $primaryKey = 'detail_id';

    protected $fillable = [
        'order_id',
        'test_id',
        'rate',
        'result_value',
        'result_status',
        'remarks',
        'verified_by',
        'verified_at',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
        'rate' => 'decimal:2',
    ];

    public function order()
    {
        return $this->belongsTo(LabOrder::class, 'order_id', 'order_id');
    }

    public function test()
    {
        return $this->belongsTo(LabTest::class, 'test_id', 'test_id');
    }

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by', 'user_id');
    }
}
