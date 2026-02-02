<?php

namespace App\Models\Pathology;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExternalLabCenter extends Model
{
    use HasFactory, BelongsToHospital;

    protected $table = 'external_lab_center';
    protected $primaryKey = 'lab_id';

    protected $fillable = [
        'hospital_id',
        'lab_name',
        'has_patho_test',
        'has_radio_test',
        'has_procedure_test',
        'contact_person',
        'address',
        'city',
        'state',
        'country',
        'pincode',
        'phone',
        'mobile',
        'email',
        'website',
        'is_active',
        'remarks',
    ];

    protected $casts = [
        'has_patho_test' => 'boolean',
        'has_radio_test' => 'boolean',
        'has_procedure_test' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Get the pathology tests outsourced to this lab.
     */
    public function pathoTests()
    {
        return $this->hasMany(PathoTest::class, 'external_lab_id', 'lab_id');
    }
}
