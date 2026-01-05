<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SkillSet extends Model
{
    protected $primaryKey = 'skill_set_id';

    protected $fillable = [
        'skill_code',
        'skill_name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function doctors()
    {
        return $this->hasMany(Doctor::class, 'skill_set_id', 'skill_set_id');
    }

    public function visitValidity()
    {
        return $this->hasOne(SkillSetVisitValidity::class, 'skill_set_id', 'skill_set_id');
    }
}
