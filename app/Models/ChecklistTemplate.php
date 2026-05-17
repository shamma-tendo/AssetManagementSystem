<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ChecklistTemplate extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $keyType = 'string';
    protected $primaryKey = 'id';
    public $incrementing = false;

    protected $fillable = [
        'name',
        'description',
        'category_id',
        'inspection_type',
        'version',
        'checklist_items',
        'passing_score_percentage',
        'estimated_duration_minutes',
        'required_certifications',
        'safety_requirements',
        'equipment_required',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'checklist_items' => 'array',
        'passing_score_percentage' => 'decimal:5,2',
        'estimated_duration_minutes' => 'integer',
        'required_certifications' => 'array',
        'safety_requirements' => 'array',
        'equipment_required' => 'array',
        'is_active' => 'boolean',
        'inspection_type' => InspectionType::class,
    ];

    /**
     * Get the category for this checklist template.
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the user who created the checklist template.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the checklist template.
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the inspections that use this template.
     */
    public function inspections()
    {
        return $this->hasMany(Inspection::class);
    }

    /**
     * Scope a query to only include active templates.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include templates for specific category.
     */
    public function scopeForCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Scope a query to only include templates for specific inspection type.
     */
    public function scopeForInspectionType($query, $inspectionType)
    {
        return $query->where('inspection_type', $inspectionType);
    }

    /**
     * Get the maximum possible score for this template.
     */
    public function getMaxScoreAttribute(): float
    {
        $maxScore = 0;
        
        foreach ($this->checklist_items as $item) {
            $maxScore += $item['max_points'] ?? 10;
        }
        
        return $maxScore;
    }

    /**
     * Get the passing score for this template.
     */
    public function getPassingScoreAttribute(): float
    {
        $maxScore = $this->max_score;
        $passingPercentage = $this->passing_score_percentage ?? 70;
        
        return ($maxScore * $passingPercentage) / 100;
    }

    /**
     * Get the number of checklist items.
     */
    public function getItemCountAttribute(): int
    {
        return count($this->checklist_items ?? []);
    }

    /**
     * Get the inspection type display name.
     */
    public function getInspectionTypeDisplayNameAttribute(): string
    {
        return $this->inspection_type->getDisplayName();
    }

    /**
     * Get the inspection type color.
     */
    public function getInspectionTypeColorAttribute(): string
    {
        return $this->inspection_type->getColor();
    }

    /**
     * Create a new checklist item.
     */
    public function addChecklistItem(array $item): void
    {
        $items = $this->checklist_items ?? [];
        $items[] = [
            'id' => uniqid(),
            'title' => $item['title'],
            'description' => $item['description'] ?? '',
            'type' => $item['type'] ?? 'checkbox', // checkbox, rating, text, number, photo
            'required' => $item['required'] ?? false,
            'max_points' => $item['max_points'] ?? 10,
            'options' => $item['options'] ?? null,
            'validation_rules' => $item['validation_rules'] ?? null,
            'help_text' => $item['help_text'] ?? '',
            'category' => $item['category'] ?? 'general',
            'order' => count($items),
        ];
        
        $this->checklist_items = $items;
        $this->save();
    }

    /**
     * Update a checklist item.
     */
    public function updateChecklistItem(string $itemId, array $updates): void
    {
        $items = $this->checklist_items ?? [];
        
        foreach ($items as &$item) {
            if ($item['id'] === $itemId) {
                $item = array_merge($item, $updates);
                break;
            }
        }
        
        $this->checklist_items = $items;
        $this->save();
    }

    /**
     * Remove a checklist item.
     */
    public function removeChecklistItem(string $itemId): void
    {
        $items = $this->checklist_items ?? [];
        $items = array_filter($items, fn($item) => $item['id'] !== $itemId);
        $items = array_values($items); // Re-index array
        
        $this->checklist_items = $items;
        $this->save();
    }

    /**
     * Reorder checklist items.
     */
    public function reorderChecklistItems(array $itemIds): void
    {
        $items = $this->checklist_items ?? [];
        $reorderedItems = [];
        
        foreach ($itemIds as $index => $itemId) {
            foreach ($items as $item) {
                if ($item['id'] === $itemId) {
                    $item['order'] = $index;
                    $reorderedItems[] = $item;
                    break;
                }
            }
        }
        
        $this->checklist_items = $reorderedItems;
        $this->save();
    }

    /**
     * Duplicate the checklist template.
     */
    public function duplicate(string $newName, string $newVersion = null): self
    {
        $newTemplate = $this->replicate();
        $newTemplate->name = $newName;
        $newTemplate->version = $newVersion ?? ($this->version + 1);
        $newTemplate->created_by = auth()->id();
        $newTemplate->updated_by = null;
        $newTemplate->save();
        
        return $newTemplate;
    }

    /**
     * Validate checklist structure.
     */
    public function validateChecklist(): array
    {
        $errors = [];
        $items = $this->checklist_items ?? [];
        
        if (empty($items)) {
            $errors[] = 'Checklist must have at least one item';
            return $errors;
        }
        
        foreach ($items as $index => $item) {
            if (!isset($item['title']) || empty($item['title'])) {
                $errors[] = "Item " . ($index + 1) . ": Title is required";
            }
            
            if (!isset($item['type']) || !in_array($item['type'], ['checkbox', 'rating', 'text', 'number', 'photo'])) {
                $errors[] = "Item " . ($index + 1) . ": Invalid type";
            }
            
            if (isset($item['max_points']) && (!is_numeric($item['max_points']) || $item['max_points'] <= 0)) {
                $errors[] = "Item " . ($index + 1) . ": Max points must be a positive number";
            }
            
            if ($item['type'] === 'rating' && (!isset($item['options']) || !is_array($item['options']))) {
                $errors[] = "Item " . ($index + 1) . ": Rating items must have options";
            }
        }
        
        return $errors;
    }

    /**
     * Get checklist items grouped by category.
     */
    public function getChecklistItemsByCategory(): array
    {
        $items = $this->checklist_items ?? [];
        $grouped = [];
        
        foreach ($items as $item) {
            $category = $item['category'] ?? 'general';
            if (!isset($grouped[$category])) {
                $grouped[$category] = [];
            }
            $grouped[$category][] = $item;
        }
        
        // Sort items within each category by order
        foreach ($grouped as $category => &$categoryItems) {
            usort($categoryItems, fn($a, $b) => ($a['order'] ?? 0) - ($b['order'] ?? 0));
        }
        
        return $grouped;
    }
}
