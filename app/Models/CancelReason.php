<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CancelReason extends Model
{
    protected $primaryKey = 'cancel_reason_id';

    protected $fillable = [
        'reason_code',
        'reason_name',
        'description',
        'is_auto_process',
        'applicable_for',
        'is_active',
    ];

    protected $casts = [
        'is_auto_process' => 'boolean',
        'is_active' => 'boolean',
    ];
}
