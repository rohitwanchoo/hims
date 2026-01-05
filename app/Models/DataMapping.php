<?php

namespace App\Models;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Model;

class DataMapping extends Model
{
    use BelongsToHospital;

    protected $primaryKey = 'mapping_id';

    protected $fillable = [
        'hospital_id', 'mapping_type', 'context',
        'source_system', 'source_field', 'source_value',
        'target_system', 'target_field', 'target_value',
        'transformation', 'is_active', 'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function transform($value)
    {
        if (!$this->transformation) {
            return $value;
        }

        return match ($this->transformation) {
            'uppercase' => strtoupper($value),
            'lowercase' => strtolower($value),
            'trim' => trim($value),
            'date_iso' => date('Y-m-d', strtotime($value)),
            'datetime_iso' => date('c', strtotime($value)),
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $value,
            'float' => (float) $value,
            'gender_fhir' => $this->mapGenderToFhir($value),
            'gender_local' => $this->mapGenderFromFhir($value),
            default => $value,
        };
    }

    private function mapGenderToFhir($value): string
    {
        return match (strtolower($value)) {
            'male', 'm' => 'male',
            'female', 'f' => 'female',
            'other', 'o' => 'other',
            default => 'unknown',
        };
    }

    private function mapGenderFromFhir($value): string
    {
        return match (strtolower($value)) {
            'male' => 'Male',
            'female' => 'Female',
            'other' => 'Other',
            default => 'Unknown',
        };
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('mapping_type', $type);
    }

    public function scopeByContext($query, string $context)
    {
        return $query->where('context', $context);
    }

    public static function findMapping(
        string $type,
        string $sourceField,
        ?string $sourceValue = null,
        ?int $hospitalId = null
    ): ?self {
        $query = static::where('mapping_type', $type)
            ->where('source_field', $sourceField)
            ->where('is_active', true);

        if ($sourceValue !== null) {
            $query->where('source_value', $sourceValue);
        }

        if ($hospitalId !== null) {
            $query->where(function ($q) use ($hospitalId) {
                $q->where('hospital_id', $hospitalId)
                    ->orWhereNull('hospital_id');
            });
        }

        return $query->first();
    }
}
