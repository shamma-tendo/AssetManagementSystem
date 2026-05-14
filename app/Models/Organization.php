<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Organization extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'name',
        'email',
        'type', // company or household
        'phone',
        'address',
        'city',
        'state',
        'postal_code',
        'country',
        'description',
        'logo_path',
        'subscription_plan',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'organization_id');
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class, 'organization_id');
    }

    public function assetRequests(): HasMany
    {
        return $this->hasMany(AssetRequest::class, 'organization_id');
    }

    public function assetAssignments(): HasMany
    {
        return $this->hasMany(AssetAssignment::class, 'organization_id');
    }

    public function conditionReports(): HasMany
    {
        return $this->hasMany(AssetConditionReport::class, 'organization_id');
    }

    public function insurancePolicies(): HasMany
    {
        return $this->hasMany(InsurancePolicy::class, 'organization_id');
    }

    public function assetLoans(): HasMany
    {
        return $this->hasMany(AssetLoan::class, 'organization_id');
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class, 'organization_id');
    }

    public function metrics(): HasMany
    {
        return $this->hasMany(AssetMetrics::class, 'organization_id');
    }

    public function isCompany(): bool
    {
        return $this->type === 'company';
    }

    public function isHousehold(): bool
    {
        return $this->type === 'household';
    }
}
