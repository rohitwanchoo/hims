<?php

namespace App\Models\Pathology;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PathoSampleType extends Model
{
    use HasFactory, BelongsToHospital;

    protected $table = 'patho_sample_types';
    protected $primaryKey = 'sample_type_id';

    protected $fillable = [
        'hospital_id',
        'sample_type_name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the pathology tests using this sample type.
     */
    public function pathoTests()
    {
        return $this->hasMany(PathoTest::class, 'sample_type_id', 'sample_type_id');
    }
}
