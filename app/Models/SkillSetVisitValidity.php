<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SkillSetVisitValidity extends Model
{
    protected $primaryKey = 'validity_id';

    protected $fillable = [
        'skill_set_id',
        'followup_validity_days',
        'free_followup_validity_days',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function skillSet()
    {
        return $this->belongsTo(SkillSet::class, 'skill_set_id', 'skill_set_id');
    }
}
