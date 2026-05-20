<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class InspectionComment extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $keyType = 'string';
    protected $primaryKey = 'id';
    public $incrementing = false;

    protected $fillable = [
        'inspection_id',
        'user_id',
        'comment',
        'comment_type',
        'is_internal',
        'is_private',
        'attachment_references',
    ];

    protected $casts = [
        'is_internal' => 'boolean',
        'is_private' => 'boolean',
        'attachment_references' => 'array',
        'comment_type' => CommentType::class,
    ];

    /**
     * Get the inspection that owns the comment.
     */
    public function inspection()
    {
        return $this->belongsTo(Inspection::class);
    }

    /**
     * Get the user who wrote the comment.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include public comments.
     */
    public function scopePublic($query)
    {
        return $query->where('is_internal', false)->where('is_private', false);
    }

    /**
     * Scope a query to only include internal comments.
     */
    public function scopeInternal($query)
    {
        return $query->where('is_internal', true);
    }

    /**
     * Scope a query to only include private comments.
     */
    public function scopePrivate($query)
    {
        return $query->where('is_private', true);
    }

    /**
     * Get the comment type display name.
     */
    public function getCommentTypeDisplayNameAttribute(): string
    {
        return $this->comment_type->getDisplayName();
    }

    /**
     * Get the comment type color.
     */
    public function getCommentTypeColorAttribute(): string
    {
        return $this->comment_type->getColor();
    }
}

/**
 * Comment Type Enum
 */
enum CommentType: string
{
    case GENERAL = 'general';
    case FINDING = 'finding';
    case RECOMMENDATION = 'recommendation';
    case CORRECTION = 'correction';
    case QUESTION = 'question';
    case APPROVAL = 'approval';
    case REJECTION = 'rejection';
    case NOTE = 'note';

    public function getDisplayName(): string
    {
        return match($this) {
            self::GENERAL => 'General',
            self::FINDING => 'Finding',
            self::RECOMMENDATION => 'Recommendation',
            self::CORRECTION => 'Correction',
            self::QUESTION => 'Question',
            self::APPROVAL => 'Approval',
            self::REJECTION => 'Rejection',
            self::NOTE => 'Note',
        };
    }

    public function getColor(): string
    {
        return match($this) {
            self::GENERAL => 'gray',
            self::FINDING => 'red',
            self::RECOMMENDATION => 'blue',
            self::CORRECTION => 'orange',
            self::QUESTION => 'purple',
            self::APPROVAL => 'green',
            self::REJECTION => 'red',
            self::NOTE => 'yellow',
        };
    }

    public function getIcon(): string
    {
        return match($this) {
            self::GENERAL => 'message-circle',
            self::FINDING => 'alert-triangle',
            self::RECOMMENDATION => 'lightbulb',
            self::CORRECTION => 'edit-3',
            self::QUESTION => 'help-circle',
            self::APPROVAL => 'check-circle',
            self::REJECTION => 'x-circle',
            self::NOTE => 'file-text',
        };
    }
}
