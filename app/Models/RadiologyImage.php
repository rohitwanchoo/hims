<?php

namespace App\Models;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RadiologyImage extends Model
{
    use BelongsToHospital;

    protected $primaryKey = 'image_id';

    protected $fillable = [
        'hospital_id',
        'report_id',
        'detail_id',
        'image_type',
        'file_path',
        'thumbnail_path',
        'study_instance_uid',
        'series_instance_uid',
        'sop_instance_uid',
        'pacs_reference_id',
        'image_notes',
    ];

    public function report(): BelongsTo
    {
        return $this->belongsTo(RadiologyReport::class, 'report_id', 'report_id');
    }

    public function detail(): BelongsTo
    {
        return $this->belongsTo(RadiologyOrderDetail::class, 'detail_id', 'detail_id');
    }
}
