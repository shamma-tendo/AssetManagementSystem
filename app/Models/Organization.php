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
        'slug', // URL-friendly identifier
        'code', // unique company code for joining (e.g., HOSP-4821)
        'email',
        'type', // company or household
        'industry_type', // generic, hospital, school, retail, manufacturing, corporate, household
        'size',
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
        'status',
        'next_of_kin_name',
        'next_of_kin_phone',
        'next_of_kin_email',
        'next_of_kin_relationship',
        'industry_metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'industry_metadata' => 'array',
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

    /**
     * Industry Type Checkers
     */
    public function isHospital(): bool
    {
        return $this->industry_type === 'hospital';
    }

    public function isSchool(): bool
    {
        return $this->industry_type === 'school';
    }

    public function isRetail(): bool
    {
        return $this->industry_type === 'retail';
    }

    public function isManufacturing(): bool
    {
        return $this->industry_type === 'manufacturing';
    }

    public function isCorporate(): bool
    {
        return $this->industry_type === 'corporate';
    }

    /**
     * Get a user-friendly industry type name
     */
    public function getIndustryTypeLabel(): string
    {
        return match ($this->industry_type) {
            'hospital' => 'Hospital / Medical Facility',
            'school' => 'School / Educational Institution',
            'retail' => 'Retail Store',
            'manufacturing' => 'Manufacturing Facility',
            'corporate' => 'Corporate Office',
            'household' => 'Personal / Household',
            default => 'General Company',
        };
    }

    /**
     * Get industry-specific icon
     */
    public function getIndustryIcon(): string
    {
        return match ($this->industry_type) {
            'hospital' => '🏥',
            'school' => '🎓',
            'retail' => '🏪',
            'manufacturing' => '🏭',
            'corporate' => '🏢',
            'household' => '🏠',
            default => '📦',
        };
    }
}
