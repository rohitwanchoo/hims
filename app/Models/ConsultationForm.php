<?php

namespace App\Models;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Model;

class ConsultationForm extends Model
{
    use BelongsToHospital;

    protected $primaryKey = 'form_id';

    protected $fillable = [
        'hospital_id',
        'form_name',
        'description',
        'form_type',
        'department_id',
        'is_active',
        'is_default',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id', 'department_id');
    }

    public function fields()
    {
        return $this->hasMany(ConsultationFormField::class, 'form_id', 'form_id')
            ->orderBy('field_order');
    }

    public function visibleFields()
    {
        return $this->hasMany(ConsultationFormField::class, 'form_id', 'form_id')
            ->where('is_visible', true)
            ->orderBy('field_order');
    }

    public function records()
    {
        return $this->hasMany(ConsultationRecord::class, 'form_id', 'form_id');
    }

    /**
     * Get fields grouped by section
     */
    public function getFieldsBySection()
    {
        return $this->visibleFields()
            ->get()
            ->groupBy('section');
    }
}
