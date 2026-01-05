<?php

namespace App\Models;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Model;

class Ward extends Model
{
    use BelongsToHospital;

    protected $primaryKey = 'ward_id';

    protected $fillable = [
        'hospital_id',
        'ward_code',
        'ward_name',
        'ward_type',
        'total_beds',
        'charges_per_day',
        'department_id',
        'is_active',
    ];

    protected $casts = [
        'total_beds' => 'integer',
        'charges_per_day' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id', 'department_id');
    }

    public function beds()
    {
        return $this->hasMany(Bed::class, 'ward_id', 'ward_id');
    }

    public function availableBeds()
    {
        return $this->beds()->where('status', 'available');
    }
}
