<?php

namespace App\Models\Pathology;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PathoContainer extends Model
{
    use HasFactory, BelongsToHospital;

    protected $table = 'patho_containers';
    protected $primaryKey = 'container_id';

    protected $fillable = [
        'hospital_id',
        'container_name',
        'is_active',
        'remarks',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the pathology tests using this container.
     */
    public function pathoTests()
    {
        return $this->hasMany(PathoTest::class, 'container_id', 'container_id');
    }
}
