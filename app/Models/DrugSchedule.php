<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DrugSchedule extends Model
{
    protected $primaryKey = 'schedule_id';

    protected $fillable = [
        'schedule_name',
        'description',
    ];

    public function drugs()
    {
        return $this->hasMany(Drug::class, 'schedule_id', 'schedule_id');
    }
}
