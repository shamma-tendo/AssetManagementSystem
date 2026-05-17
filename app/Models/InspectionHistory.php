<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class InspectionHistory extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    protected $primaryKey = 'id';
    public $incrementing = false;

    protected $fillable = [
        'inspection_id',
        'user_id',
        'action',
        'old_value',
        'new_value',
        'field_name',
        'description',
        'ip_address',
        'user_agent',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    /**
     * Get the inspection that owns the history entry.
     */
    public function inspection()
    {
        return $this->belongsTo(Inspection::class);
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
    public static function logStatusChange(Inspection $inspection, InspectionStatus $oldStatus, InspectionStatus $newStatus, User $user): self
    {
        return static::create([
            'inspection_id' => $inspection->id,
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
     * Create a history entry for a score change.
     */
    public static function logScoreChange(Inspection $inspection, float $oldScore, float $newScore, User $user): self
    {
        return static::create([
            'inspection_id' => $inspection->id,
            'user_id' => $user->id,
            'action' => 'score_change',
            'old_value' => $oldScore,
            'new_value' => $newScore,
            'field_name' => 'overall_score',
            'description' => "Score changed from {$oldScore} to {$newScore}",
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Create a history entry for a checklist update.
     */
    public static function logChecklistUpdate(Inspection $inspection, array $changes, User $user): self
    {
        return static::create([
            'inspection_id' => $inspection->id,
            'user_id' => $user->id,
            'action' => 'checklist_update',
            'old_value' => $changes['old'] ?? null,
            'new_value' => $changes['new'] ?? null,
            'field_name' => 'checklist_results',
            'description' => "Checklist updated: " . ($changes['description'] ?? 'Items modified'),
            'metadata' => $changes['metadata'] ?? [],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Create a history entry for an assignment change.
     */
    public static function logAssignmentChange(Inspection $inspection, ?User $oldInspector, User $newInspector, User $user): self
    {
        return static::create([
            'inspection_id' => $inspection->id,
            'user_id' => $user->id,
            'action' => 'assignment_change',
            'old_value' => $oldInspector?->id,
            'new_value' => $newInspector->id,
            'field_name' => 'inspector_id',
            'description' => "Inspector changed from " . ($oldInspector?->full_name ?? 'Unassigned') . " to {$newInspector->full_name}",
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Create a history entry for a general field change.
     */
    public static function logFieldChange(Inspection $inspection, string $field, $oldValue, $newValue, User $user): self
    {
        return static::create([
            'inspection_id' => $inspection->id,
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
    public static function logCustomAction(Inspection $inspection, string $action, string $description, User $user, array $metadata = []): self
    {
        return static::create([
            'inspection_id' => $inspection->id,
            'user_id' => $user->id,
            'action' => $action,
            'description' => $description,
            'metadata' => $metadata,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Create a history entry for file upload.
     */
    public static function logFileUpload(Inspection $inspection, InspectionAttachment $attachment, User $user): self
    {
        return static::create([
            'inspection_id' => $inspection->id,
            'user_id' => $user->id,
            'action' => 'file_upload',
            'field_name' => 'attachments',
            'description' => "File uploaded: {$attachment->original_name}",
            'metadata' => [
                'attachment_id' => $attachment->id,
                'file_size' => $attachment->file_size,
                'mime_type' => $attachment->mime_type,
            ],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Create a history entry for comment addition.
     */
    public static function logCommentAdded(Inspection $inspection, InspectionComment $comment, User $user): self
    {
        return static::create([
            'inspection_id' => $inspection->id,
            'user_id' => $user->id,
            'action' => 'comment_added',
            'field_name' => 'comments',
            'description' => "Comment added: " . substr($comment->comment, 0, 50) . (strlen($comment->comment) > 50 ? '...' : ''),
            'metadata' => [
                'comment_id' => $comment->id,
                'comment_type' => $comment->comment_type->value,
                'is_internal' => $comment->is_internal,
                'is_private' => $comment->is_private,
            ],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Create a history entry for work order creation.
     */
    public static function logWorkOrderCreated(Inspection $inspection, WorkOrder $workOrder, User $user): self
    {
        return static::create([
            'inspection_id' => $inspection->id,
            'user_id' => $user->id,
            'action' => 'work_order_created',
            'field_name' => 'work_orders',
            'description' => "Work order created: {$workOrder->title}",
            'metadata' => [
                'work_order_id' => $workOrder->id,
                'work_order_type' => $workOrder->type->value,
                'work_order_priority' => $workOrder->priority->value,
            ],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
