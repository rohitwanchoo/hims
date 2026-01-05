<?php

namespace App\Models;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Model;

class DrugCategory extends Model
{
    use BelongsToHospital;

    protected $primaryKey = 'category_id';

    protected $fillable = [
        'hospital_id',
        'category_name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function drugs()
    {
        return $this->hasMany(Drug::class, 'category_id', 'category_id');
    }
}
