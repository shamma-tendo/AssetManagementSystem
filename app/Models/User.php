<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes, HasUuids;

    protected $keyType = 'string';
    protected $primaryKey = 'id';
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'username',
        'password',
        'phone',
        'role',
        'department_id',
        'location_id',
        'is_active',
        'email_verified_at',
        'two_factor_secret',
        'two_factor_enabled',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
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
            'is_active' => 'boolean',
            'two_factor_enabled' => 'boolean',
            'role' => UserRole::class,
            'last_login_at' => 'datetime',
        ];
    }

    /**
     * Get the department that owns the user.
     */
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the location that owns the user.
     */
    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Get the assets created by the user.
     */
    public function createdAssets()
    {
        return $this->hasMany(Asset::class, 'created_by');
    }

    /**
     * Get the assets updated by the user.
     */
    public function updatedAssets()
    {
        return $this->hasMany(Asset::class, 'updated_by');
    }

    /**
     * Get the departments managed by the user.
     */
    public function managedDepartments()
    {
        return $this->hasMany(Department::class, 'manager_id');
    }

    /**
     * Get the user's full name.
     */
    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * Get the user's role display name.
     */
    public function getRoleDisplayNameAttribute()
    {
        return $this->role->getDisplayName();
    }

    /**
     * Check if user has specific role.
     */
    public function hasRole(UserRole $role): bool
    {
        return $this->role === $role;
    }

    /**
     * Check if user has any of the given roles.
     */
    public function hasAnyRole(array $roles): bool
    {
        return in_array($this->role, $roles);
    }

    /**
     * Check if user can perform action based on role.
     */
    public function canPerform(string $action): bool
    {
        return $this->role->canPerform($action);
    }

    /**
     * Scope a query to only include active users.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include users with specific role.
     */
    public function scopeByRole($query, UserRole $role)
    {
        return $query->where('role', $role);
    }

    /**
     * Update last login timestamp.
     */
    public function updateLastLogin()
    {
        $this->update(['last_login_at' => now()]);
    }
}

/**
 * User Role Enum
 */
enum UserRole: string
{
    case ADMIN = 'admin';
    case MANAGER = 'manager';
    case TECHNICIAN = 'technician';
    case AUDITOR = 'auditor';
    case VIEWER = 'viewer';

    public function getDisplayName(): string
    {
        return match($this) {
            self::ADMIN => 'Administrator',
            self::MANAGER => 'Manager',
            self::TECHNICIAN => 'Technician',
            self::AUDITOR => 'Auditor',
            self::VIEWER => 'Viewer',
        };
    }

    public function getLevel(): int
    {
        return match($this) {
            self::ADMIN => 5,
            self::MANAGER => 4,
            self::TECHNICIAN => 3,
            self::AUDITOR => 2,
            self::VIEWER => 1,
        };
    }

    public function canPerform(string $action): bool
    {
        $permissions = [
            self::ADMIN => ['*'], // All permissions
            self::MANAGER => [
                'create_asset', 'edit_asset', 'delete_asset',
                'create_work_order', 'assign_work_order', 'approve_work_order',
                'view_reports', 'manage_users', 'manage_categories'
            ],
            self::TECHNICIAN => [
                'view_asset', 'create_work_order', 'update_work_order',
                'view_assigned_work_orders', 'update_work_order_status'
            ],
            self::AUDITOR => [
                'view_asset', 'view_reports', 'view_audit_logs',
                'export_data', 'view_compliance'
            ],
            self::VIEWER => [
                'view_asset', 'view_reports', 'export_data'
            ],
        ];

        return in_array('*', $permissions[$this]) || 
               in_array($action, $permissions[$this]);
    }
}
