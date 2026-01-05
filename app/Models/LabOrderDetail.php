<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LabOrderDetail extends Model
{
    protected $primaryKey = 'detail_id';

    protected $fillable = [
        'order_id',
        'test_id',
        'result',
        'result_date',
        'status',
        'price',
        'notes',
        'performed_by',
    ];

    protected $casts = [
        'result_date' => 'datetime',
        'price' => 'decimal:2',
    ];

    public function order()
    {
        return $this->belongsTo(LabOrder::class, 'order_id', 'order_id');
    }

    public function test()
    {
        return $this->belongsTo(LabTest::class, 'test_id', 'test_id');
    }

    public function performedBy()
    {
        return $this->belongsTo(User::class, 'performed_by', 'user_id');
    }
}
