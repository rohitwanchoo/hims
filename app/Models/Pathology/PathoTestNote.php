<?php

namespace App\Models\Pathology;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PathoTestNote extends Model
{
    use HasFactory, BelongsToHospital;

    protected $table = 'patho_test_note';
    protected $primaryKey = 'note_id';

    protected $fillable = [
        'hospital_id',
        'note_for',
        'test_id',
        'report_id',
        'age_group',
        'note_text',
        'test_remark',
        'is_default',
        'is_abnormal',
        'is_active',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_abnormal' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Get the pathology test this note belongs to.
     */
    public function pathoTest()
    {
        return $this->belongsTo(PathoTest::class, 'test_id', 'test_id');
    }

    /**
     * Get the pathology test report this note belongs to.
     */
    public function pathoTestReport()
    {
        return $this->belongsTo(PathoTestReport::class, 'report_id', 'report_id');
    }
}
