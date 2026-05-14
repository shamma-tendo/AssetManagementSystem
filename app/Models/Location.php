<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['name', 'address', 'building', 'floor', 'room', 'latitude', 'longitude'];

    protected $keyType = 'string';

    public $incrementing = false;

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }

    public function spareParts(): HasMany
    {
        return $this->hasMany(SparePart::class);
    }
}
