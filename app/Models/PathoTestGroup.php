<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PathoTestGroup extends Model
{
    use HasFactory;

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

    protected $appends = ['tests_count'];

    // Relationships
    public function hospital()
    {
        return $this->belongsTo(Hospital::class, 'hospital_id', 'hospital_id');
    }

    // Many-to-many relationship with tests (requires pivot table)
    public function tests()
    {
        return $this->belongsToMany(
            PathoTest::class,
            'patho_test_group_tests', // pivot table name
            'group_id',
            'test_id',
            'group_id',
            'test_id'
        );
    }

    // Accessor for tests count
    public function getTestsCountAttribute()
    {
        // If tests relationship is loaded, use it
        if ($this->relationLoaded('tests')) {
            return $this->tests->count();
        }

        // If tests_count was loaded via withCount, use that
        if (isset($this->attributes['tests_count'])) {
            return $this->attributes['tests_count'];
        }

        return 0;
    }
}
