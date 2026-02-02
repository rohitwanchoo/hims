<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BillHistory extends Model
{
    protected $table = 'bill_history';
    protected $primaryKey = 'history_id';

    protected $fillable = [
        'bill_id',
        'action',
        'subtotal',
        'discount_amount',
        'discount_percent',
        'tax_amount',
        'adjustment',
        'total_amount',
        'paid_amount',
        'due_amount',
        'payment_mode',
        'payment_status',
        'changed_by',
        'changes',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'adjustment' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'due_amount' => 'decimal:2',
        'changes' => 'array',
    ];

    public function bill()
    {
        return $this->belongsTo(Bill::class, 'bill_id', 'bill_id');
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by', 'user_id');
    }
}
