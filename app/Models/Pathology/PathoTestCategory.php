<?php

namespace App\Models\Pathology;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PathoTestCategory extends Model
{
    use HasFactory, BelongsToHospital;

    protected $table = 'patho_test_category';
    protected $primaryKey = 'category_id';

    protected $fillable = [
        'hospital_id',
        'category_name',
        'category_code',
        'fit_100',
        'has_sub_category',
        'parent_category_id',
        'is_active',
        'remarks',
    ];

    protected $casts = [
        'fit_100' => 'boolean',
        'has_sub_category' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Get the parent category (self-referencing relationship).
     */
    public function parentCategory()
    {
        return $this->belongsTo(PathoTestCategory::class, 'parent_category_id', 'category_id');
    }

    /**
     * Get the child categories (self-referencing relationship).
     */
    public function subCategories()
    {
        return $this->hasMany(PathoTestCategory::class, 'parent_category_id', 'category_id');
    }

    /**
     * Get the pathology tests in this category.
     */
    public function pathoTests()
    {
        return $this->hasMany(PathoTest::class, 'category_id', 'category_id');
    }
}
