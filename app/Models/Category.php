<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['name', 'description', 'code'];

    protected $keyType = 'string';

    public $incrementing = false;

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }

    public function spareParts(): HasMany
    {
        return $this->hasMany(SparePart::class);
    }
}
