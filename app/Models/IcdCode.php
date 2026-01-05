<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IcdCode extends Model
{
    protected $primaryKey = 'icd_id';

    protected $fillable = [
        'icd_code',
        'short_description',
        'long_description',
        'icd_version',
        'category',
        'chapter',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Accessor to provide 'code' attribute for compatibility
    public function getCodeAttribute()
    {
        return $this->icd_code;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByVersion($query, string $version)
    {
        return $query->where('icd_version', $version);
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('icd_code', 'like', "%{$term}%")
                ->orWhere('short_description', 'like', "%{$term}%")
                ->orWhere('long_description', 'like', "%{$term}%");
        });
    }

    public function getFullDescription(): string
    {
        return "{$this->icd_code} - {$this->short_description}";
    }
}
