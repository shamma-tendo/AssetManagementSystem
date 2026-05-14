<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DepreciationRecord extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'asset_id', 'year', 'method', 'beginning_book_value',
        'depreciation_expense', 'book_value', 'accumulated_depreciation'
    ];

    protected $keyType = 'string';

    public $incrementing = false;

    protected $casts = [
        'beginning_book_value' => 'decimal:2',
        'depreciation_expense' => 'decimal:2',
        'book_value' => 'decimal:2',
        'accumulated_depreciation' => 'decimal:2',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }
}
