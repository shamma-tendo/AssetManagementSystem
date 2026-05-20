<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Organization extends Model
{
    protected $fillable = ['name', 'industry_type', 'company_code', 'created_by', 'type']; // Added 'type'

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($organization) {
            // Generate unique Company Code: INDUSTRY-RANDOM
            $prefix = strtoupper(substr($organization->industry_type ?? 'ORG', 0, 4));
            $organization->company_code = $prefix . '-' . rand(1000, 9999);
        });
    }

    public function users() {
        return $this->hasMany(User::class);
    }

    public function assets() {
        return $this->hasMany(Asset::class);
    }

    public function pendingUsers() {
        return $this->users()->where('is_approved', false);
    }
}