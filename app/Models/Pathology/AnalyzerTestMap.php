<?php

namespace App\Models\Pathology;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnalyzerTestMap extends Model
{
    use HasFactory, BelongsToHospital;

    protected $table = 'analyzer_test_maps';
    protected $primaryKey = 'map_id';

    protected $fillable = [
        'analyzer_id',
        'test_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the analyzer.
     */
    public function analyzer()
    {
        return $this->belongsTo(Analyzer::class, 'analyzer_id', 'analyzer_id');
    }

    /**
     * Get the test.
     */
    public function test()
    {
        return $this->belongsTo(PathoTest::class, 'test_id', 'test_id');
    }
}
