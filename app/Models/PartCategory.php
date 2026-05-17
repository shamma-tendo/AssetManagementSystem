<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class PartCategory extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $keyType = 'string';
    protected $primaryKey = 'id';
    public $incrementing = false;

    protected $fillable = [
        'name',
        'description',
        'parent_category_id',
        'code',
        'icon',
        'color',
        'notes',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the parent category.
     */
    public function parentCategory()
    {
        return $this->belongsTo(PartCategory::class, 'parent_category_id');
    }

    /**
     * Get the child categories.
     */
    public function childCategories()
    {
        return $this->hasMany(PartCategory::class, 'parent_category_id');
    }

    /**
     * Get the user who created the category.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the category.
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the parts in this category.
     */
    public function parts()
    {
        return $this->hasMany(Part::class);
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

    /**
     * Scope a query to only include child categories.
     */
    public function scopeChild($query)
    {
        return $query->whereNotNull('parent_category_id');
    }

    /**
     * Get the full category path.
     */
    public function getFullPathAttribute(): string
    {
        $path = [];
        $current = $this;

        while ($current) {
            array_unshift($path, $current->name);
            $current = $current->parentCategory;
        }

        return implode(' > ', $path);
    }

    /**
     * Get the category depth level.
     */
    public function getDepthAttribute(): int
    {
        $depth = 0;
        $current = $this;

        while ($current->parentCategory) {
            $depth++;
            $current = $current->parentCategory;
        }

        return $depth;
    }

    /**
     * Get all descendant categories recursively.
     */
    public function getAllDescendants()
    {
        $descendants = collect([$this]);

        foreach ($this->childCategories as $child) {
            $descendants = $descendants->merge($child->getAllDescendants());
        }

        return $descendants;
    }

    /**
     * Get the total number of parts in this category and all subcategories.
     */
    public function getTotalPartsCountAttribute(): int
    {
        return $this->getAllDescendants()->sum(function ($category) {
            return $category->parts()->count();
        });
    }

    /**
     * Check if this category has child categories.
     */
    public function hasChildren(): bool
    {
        return $this->childCategories()->exists();
    }

    /**
     * Check if this is a root category.
     */
    public function isRoot(): bool
    {
        return is_null($this->parent_category_id);
    }

    /**
     * Check if this is a leaf category (no children).
     */
    public function isLeaf(): bool
    {
        return !$this->hasChildren();
    }
}
