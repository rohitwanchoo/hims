<?php

namespace App\Models;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Model;

class GstPlan extends Model
{
    use BelongsToHospital;

    protected $primaryKey = 'gst_plan_id';

    protected $fillable = [
        'hospital_id',
        'plan_name',
        'gst_percentage',
        'description',
        'is_active',
    ];

    protected $casts = [
        'gst_percentage' => 'decimal:2',
        'is_active' => 'boolean',
    ];
}
