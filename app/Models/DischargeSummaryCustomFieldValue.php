<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DischargeSummaryCustomFieldValue extends Model
{
    protected $fillable = [
        'discharge_summary_id',
        'field_id',
        'field_value',
    ];

    public function dischargeSummary()
    {
        return $this->belongsTo(DischargeSummary::class, 'discharge_summary_id', 'discharge_summary_id');
    }

    public function customField()
    {
        return $this->belongsTo(DischargeSummaryCustomField::class, 'field_id', 'field_id');
    }
}
