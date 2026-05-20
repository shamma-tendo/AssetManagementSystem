<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, HasUuids;

    protected $keyType = 'string';

    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'organization_id',
        'status',
        'is_approved',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function assignedWorkOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class, 'assigned_to');
    }

    public function createdAssets(): HasMany
    {
        return $this->hasMany(Asset::class, 'created_by');
    }

    public function updatedAssets(): HasMany
    {
        return $this->hasMany(Asset::class, 'updated_by');
    }

    public function maintenanceRecords(): HasMany
    {
        return $this->hasMany(MaintenanceRecord::class, 'technician_id');
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function hasRole(string $roleName): bool
    {
        return $this->role && $this->role->name === $roleName;
    }

    public function hasPermission(string $permission): bool
    {
        return $this->role && $this->role->hasPermission($permission);
    }

    public function hasAnyRole(array $roles): bool
    {
        return $this->role && in_array($this->role->name, $roles);
    }

    /**
     * Role-specific checkers for company accounts
     */
    public function isExecutive(): bool
    {
        return $this->hasAnyRole(['CEO', 'CFO', 'Executive', 'Admin']);
    }

    public function isAssetManager(): bool
    {
        return $this->hasRole('Asset Manager');
    }

    public function isStaff(): bool
    {
        return $this->hasRole('Staff');
    }

    public function isEmployee(): bool
    {
        return $this->hasAnyRole(['Staff', 'Employee', 'Team Member']);
    }

    /**
     * For household/individual accounts
     */
    public function isHouseholdOwner(): bool
    {
        return $this->organization && $this->organization->isHousehold();
    }

    /**
     * Get user's dashboard route based on role and organization type
     */
    public function getDashboardRoute(): string
    {
        if (!$this->organization) {
            return 'dashboard';
        }

        if ($this->organization->isHousehold()) {
            return 'household.dashboard';
        }

        // Company-based dashboards
        if ($this->isExecutive()) {
            return 'executive.dashboard';
        }

        if ($this->isStaff()) {
            return 'staff.dashboard';
        }

        return 'dashboard';
    }
}

