<?php

namespace App\Models\Pathology;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Analyzer extends Model
{
    use HasFactory, BelongsToHospital;

    protected $table = 'analyzers';
    protected $primaryKey = 'analyzer_id';

    protected $fillable = [
        'hospital_id',
        'analyzer_name',
        'analyzer_code',
        'analyzer_type',
        'is_bidirectional',
        'analyzer_count',
        'is_active',
        'remarks',
    ];

    protected $casts = [
        'is_bidirectional' => 'boolean',
        'analyzer_count' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get the pathology tests using this analyzer.
     */
    public function pathoTests()
    {
        return $this->hasMany(PathoTest::class, 'analyzer_id', 'analyzer_id');
    }

    /**
     * Get mapped tests through analyzer_test_maps pivot table.
     */
    public function tests()
    {
        return $this->belongsToMany(
            PathoTest::class,
            'analyzer_test_maps',
            'analyzer_id',
            'test_id',
            'analyzer_id',
            'test_id'
        );
    }
}
