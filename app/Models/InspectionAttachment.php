<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class InspectionAttachment extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $keyType = 'string';
    protected $primaryKey = 'id';
    public $incrementing = false;

    protected $fillable = [
        'inspection_id',
        'file_name',
        'original_name',
        'file_path',
        'file_size',
        'mime_type',
        'uploaded_by',
        'description',
        'attachment_type',
        'is_public',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'is_public' => 'boolean',
        'attachment_type' => AttachmentType::class,
    ];

    /**
     * Get the inspection that owns the attachment.
     */
    public function inspection()
    {
        return $this->belongsTo(Inspection::class);
    }

    /**
     * Get the user who uploaded the attachment.
     */
    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Get the file size in human readable format.
     */
    public function getFileSizeHumanAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get the attachment type display name.
     */
    public function getAttachmentTypeDisplayNameAttribute(): string
    {
        return $this->attachment_type->getDisplayName();
    }

    /**
     * Get the attachment type color.
     */
    public function getAttachmentTypeColorAttribute(): string
    {
        return $this->attachment_type->getColor();
    }
}

/**
 * Attachment Type Enum
 */
enum AttachmentType: string
{
    case PHOTO = 'photo';
    case VIDEO = 'video';
    case DOCUMENT = 'document';
    case REPORT = 'report';
    case CERTIFICATE = 'certificate';
    case DIAGRAM = 'diagram';
    case DRAWING = 'drawing';
    case SIGNATURE = 'signature';
    case OTHER = 'other';

    public function getDisplayName(): string
    {
        return match($this) {
            self::PHOTO => 'Photo',
            self::VIDEO => 'Video',
            self::DOCUMENT => 'Document',
            self::REPORT => 'Report',
            self::CERTIFICATE => 'Certificate',
            self::DIAGRAM => 'Diagram',
            self::DRAWING => 'Drawing',
            self::SIGNATURE => 'Signature',
            self::OTHER => 'Other',
        };
    }

    public function getColor(): string
    {
        return match($this) {
            self::PHOTO => 'blue',
            self::VIDEO => 'purple',
            self::DOCUMENT => 'gray',
            self::REPORT => 'green',
            self::CERTIFICATE => 'yellow',
            self::DIAGRAM => 'orange',
            self::DRAWING => 'pink',
            self::SIGNATURE => 'indigo',
            self::OTHER => 'gray',
        };
    }

    public function getIcon(): string
    {
        return match($this) {
            self::PHOTO => 'camera',
            self::VIDEO => 'video',
            self::DOCUMENT => 'file-text',
            self::REPORT => 'file-check',
            self::CERTIFICATE => 'award',
            self::DIAGRAM => 'git-branch',
            self::DRAWING => 'edit-3',
            self::SIGNATURE => 'pen-tool',
            self::OTHER => 'file',
        };
    }
}
