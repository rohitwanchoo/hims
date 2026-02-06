<?php

namespace App\Models\Pathology;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PathoTestMethod extends Model
{
    use HasFactory, BelongsToHospital;

    protected $table = 'patho_test_methods';
    protected $primaryKey = 'method_id';

    protected $fillable = [
        'hospital_id',
        'method_name',
        'method_code',
        'use_for_blood_bank',
        'is_active',
        'remarks',
    ];

    protected $casts = [
        'use_for_blood_bank' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Get the pathology tests using this method.
     */
    public function pathoTests()
    {
        return $this->hasMany(PathoTest::class, 'method_id', 'method_id');
    }
}
