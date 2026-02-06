<?php

namespace App\Models\Pathology;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PathoTestUnit extends Model
{
    use HasFactory, BelongsToHospital;

    protected $table = 'patho_test_units';
    protected $primaryKey = 'unit_id';

    protected $fillable = [
        'hospital_id',
        'unit_name',
        'decimal_places',
        'is_active',
        'remarks',
    ];

    protected $casts = [
        'decimal_places' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get the pathology tests using this unit.
     */
    public function pathoTests()
    {
        return $this->hasMany(PathoTest::class, 'unit_id', 'unit_id');
    }
}
