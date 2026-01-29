<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DischargeSummaryCustomField extends Model
{
    protected $primaryKey = 'field_id';

    protected $fillable = [
        'hospital_id',
        'field_name',
        'field_label',
        'field_type',
        'field_options',
        'section',
        'display_order',
        'is_required',
        'is_active',
        'placeholder',
        'help_text',
    ];

    protected $casts = [
        'field_options' => 'array',
        'is_required' => 'boolean',
        'is_active' => 'boolean',
        'display_order' => 'integer',
    ];

    public function hospital()
    {
        return $this->belongsTo(Hospital::class, 'hospital_id', 'hospital_id');
    }

    public function values()
    {
        return $this->hasMany(DischargeSummaryCustomFieldValue::class, 'field_id', 'field_id');
    }
}
