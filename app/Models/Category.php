<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use HasFactory, SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'name',
        'description',
        'parent_category_id',
        'pm_frequency_months',
        'useful_life_years',
        'depreciation_method',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'pm_frequency_months' => 'integer',
        'useful_life_years' => 'integer',
    ];

    /**
     * Get the assets for this category.
     */
    public function assets()
    {
        return $this->hasMany(Asset::class);
    }

    /**
     * Get the parent category.
     */
    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_category_id');
    }

    /**
     * Get the child categories.
     */
    public function children()
    {
        return $this->hasMany(Category::class, 'parent_category_id');
    }

    /**
     * Scope a query to only include active categories.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include root categories.
     */
    public function scopeRoot($query)
    {
        return $query->whereNull('parent_category_id');
    }
}
