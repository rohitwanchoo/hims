<?php

namespace App\Models\Pathology;

use App\Models\AgeGroup;
use App\Models\Gender;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PathoTestReferenceRange extends Model
{
    use HasFactory;

    protected $primaryKey = 'reference_id';

    protected $fillable = [
        'test_id',
        'gender_id',
        'age_group_id',
        'race_id',
        'min_value',
        'max_value',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'min_value' => 'decimal:2',
        'max_value' => 'decimal:2',
    ];

    public function test()
    {
        return $this->belongsTo(PathoTest::class, 'test_id', 'test_id');
    }

    public function gender()
    {
        return $this->belongsTo(Gender::class, 'gender_id', 'gender_id');
    }

    public function ageGroup()
    {
        return $this->belongsTo(AgeGroup::class, 'age_group_id', 'age_group_id');
    }

    public function race()
    {
        return $this->belongsTo(Race::class, 'race_id', 'race_id');
    }
}
