<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class WorkOrderHistory extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    protected $primaryKey = 'id';
    public $incrementing = false;

    protected $fillable = [
        'work_order_id',
        'user_id',
        'action',
        'old_value',
        'new_value',
        'field_name',
        'description',
        'ip_address',
        'user_agent',
    ];

    /**
     * Get the work order that owns the history entry.
     */
    public function workOrder()
    {
        return $this->belongsTo(WorkOrder::class);
    }

    /**
     * Get the user who performed the action.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Create a history entry for a status change.
     */
    public static function logStatusChange(WorkOrder $workOrder, WorkOrderStatus $oldStatus, WorkOrderStatus $newStatus, User $user): self
    {
        return static::create([
            'work_order_id' => $workOrder->id,
            'user_id' => $user->id,
            'action' => 'status_change',
            'old_value' => $oldStatus->value,
            'new_value' => $newStatus->value,
            'field_name' => 'status',
            'description' => "Status changed from {$oldStatus->getDisplayName()} to {$newStatus->getDisplayName()}",
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Create a history entry for an assignment change.
     */
    public static function logAssignmentChange(WorkOrder $workOrder, ?User $oldAssignee, User $newAssignee, User $user): self
    {
        return static::create([
            'work_order_id' => $workOrder->id,
            'user_id' => $user->id,
            'action' => 'assignment_change',
            'old_value' => $oldAssignee?->id,
            'new_value' => $newAssignee->id,
            'field_name' => 'assigned_to',
            'description' => "Work order assigned from " . ($oldAssignee?->full_name ?? 'Unassigned') . " to {$newAssignee->full_name}",
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Create a history entry for a general field change.
     */
    public static function logFieldChange(WorkOrder $workOrder, string $field, $oldValue, $newValue, User $user): self
    {
        return static::create([
            'work_order_id' => $workOrder->id,
            'user_id' => $user->id,
            'action' => 'field_change',
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'field_name' => $field,
            'description' => ucfirst($field) . " changed from '{$oldValue}' to '{$newValue}'",
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Create a history entry for a custom action.
     */
    public static function logCustomAction(WorkOrder $workOrder, string $action, string $description, User $user): self
    {
        return static::create([
            'work_order_id' => $workOrder->id,
            'user_id' => $user->id,
            'action' => $action,
            'description' => $description,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
