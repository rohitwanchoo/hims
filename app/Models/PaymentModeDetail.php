<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentModeDetail extends Model
{
    protected $primaryKey = 'detail_id';

    protected $fillable = [
        'payment_mode_id',
        'field_name',
        'field_label',
        'field_type',
        'is_required',
        'max_length',
        'display_order',
        'is_active',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function paymentMode()
    {
        return $this->belongsTo(PaymentMode::class, 'payment_mode_id', 'payment_mode_id');
    }
}
