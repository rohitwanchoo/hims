<?php

namespace App\Models\Pathology;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PathoTestGroup extends Model
{
    use HasFactory, BelongsToHospital;

    protected $table = 'patho_test_groups';
    protected $primaryKey = 'group_id';

    protected $fillable = [
        'hospital_id',
        'group_name',
        'group_code',
        'is_default_group',
        'is_active',
        'remarks',
    ];

    protected $casts = [
        'is_default_group' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Get the pathology tests in this group.
     */
    public function pathoTests()
    {
        return $this->hasMany(PathoTest::class, 'group_id', 'group_id');
    }
}
