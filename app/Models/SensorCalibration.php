<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class SensorCalibration extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $keyType = 'string';
    protected $primaryKey = 'id';
    public $incrementing = false;

    protected $fillable = [
        'sensor_id',
        'calibration_date',
        'performed_by',
        'calibration_type',
        'reference_value',
        'measured_value',
        'correction_factor',
        'offset',
        'linearity_error',
        'hysteresis_error',
        'repeatability_error',
        'temperature_coefficient',
        'humidity_coefficient',
        'calibration_certificate',
        'equipment_used',
        'environment_conditions',
        'notes',
        'next_calibration_date',
        'calibration_status',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'calibration_date' => 'date',
        'next_calibration_date' => 'date',
        'reference_value' => 'decimal:15,6',
        'measured_value' => 'decimal:15,6',
        'correction_factor' => 'decimal:10,6',
        'offset' => 'decimal:15,6',
        'linearity_error' => 'decimal:8,4',
        'hysteresis_error' => 'decimal:8,4',
        'repeatability_error' => 'decimal:8,4',
        'temperature_coefficient' => 'decimal:10,8',
        'humidity_coefficient' => 'decimal:10,8',
        'environment_conditions' => 'array',
        'approved_at' => 'datetime',
        'calibration_type' => CalibrationType::class,
        'calibration_status' => CalibrationStatus::class,
    ];

    /**
     * Get the sensor that was calibrated.
     */
    public function sensor()
    {
        return $this->belongsTo(Sensor::class);
    }

    /**
     * Get the user who performed the calibration.
     */
    public function performer()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    /**
     * Get the user who approved the calibration.
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Scope a query to only include calibrations within date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('calibration_date', [$startDate, $endDate]);
    }

    /**
     * Scope a query to only include calibrations of specific type.
     */
    public function scopeByType($query, $type)
    {
        return $query->where('calibration_type', $type);
    }

    /**
     * Scope a query to only include calibrations with specific status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('calibration_status', $status);
    }

    /**
     * Scope a query to only include pending calibrations.
     */
    public function scopePending($query)
    {
        return $query->where('calibration_status', CalibrationStatus::PENDING);
    }

    /**
     * Scope a query to only include approved calibrations.
     */
    public function scopeApproved($query)
    {
        return $query->whereNotNull('approved_at');
    }

    /**
     * Get the calibration type display name.
     */
    public function getCalibrationTypeDisplayNameAttribute(): string
    {
        return $this->calibration_type->getDisplayName();
    }

    /**
     * Get the calibration status display name.
     */
    public function getCalibrationStatusDisplayNameAttribute(): string
    {
        return $this->calibration_status->getDisplayName();
    }

    /**
     * Get the calibration status color.
     */
    public function getCalibrationStatusColorAttribute(): string
    {
        return $this->calibration_status->getColor();
    }

    /**
     * Check if calibration is approved.
     */
    public function isApproved(): bool
    {
        return $this->approved_at !== null;
    }

    /**
     * Check if calibration is pending approval.
     */
    public function isPending(): bool
    {
        return $this->calibration_status === CalibrationStatus::PENDING;
    }

    /**
     * Check if calibration failed.
     */
    public function isFailed(): bool
    {
        return $this->calibration_status === CalibrationStatus::FAILED;
    }

    /**
     * Calculate the calibration error.
     */
    public function getCalibrationErrorAttribute(): float
    {
        if ($this->reference_value === null || $this->measured_value === null) {
            return 0;
        }

        return abs($this->measured_value - $this->reference_value);
    }

    /**
     * Calculate the calibration error percentage.
     */
    public function getCalibrationErrorPercentageAttribute(): float
    {
        if ($this->reference_value === 0) {
            return 0;
        }

        return ($this->calibration_error / abs($this->reference_value)) * 100;
    }

    /**
     * Check if calibration is within acceptable tolerance.
     */
    public function isWithinTolerance(float $tolerancePercentage = 2.0): bool
    {
        return $this->calibration_error_percentage <= $tolerancePercentage;
    }

    /**
     * Approve the calibration.
     */
    public function approve(User $approver, string $notes = null): void
    {
        $this->update([
            'calibration_status' => CalibrationStatus::APPROVED,
            'approved_by' => $approver->id,
            'approved_at' => now(),
            'notes' => $notes ? ($this->notes ? $this->notes . "\n\n" . $notes : $notes) : $this->notes,
        ]);
    }

    /**
     * Reject the calibration.
     */
    public function reject(User $approver, string $reason): void
    {
        $this->update([
            'calibration_status' => CalibrationStatus::FAILED,
            'approved_by' => $approver->id,
            'approved_at' => now(),
            'notes' => ($this->notes ? $this->notes . "\n\n" : '') . "REJECTED: {$reason}",
        ]);
    }

    /**
     * Get calibration summary.
     */
    public function getSummaryAttribute(): array
    {
        return [
            'id' => $this->id,
            'sensor_name' => $this->sensor->name,
            'calibration_date' => $this->calibration_date->format('Y-m-d'),
            'calibration_type' => $this->calibration_type_display_name,
            'performed_by' => $this->performer?->full_name,
            'reference_value' => $this->reference_value,
            'measured_value' => $this->measured_value,
            'calibration_error' => $this->calibration_error,
            'calibration_error_percentage' => round($this->calibration_error_percentage, 4),
            'correction_factor' => $this->correction_factor,
            'status' => $this->calibration_status_display_name,
            'status_color' => $this->calibration_status_color,
            'approved' => $this->isApproved(),
            'approved_by' => $this->approver?->full_name,
            'approved_at' => $this->approved_at?->toISOString(),
            'within_tolerance' => $this->isWithinTolerance(),
            'next_calibration_date' => $this->next_calibration_date?->format('Y-m-d'),
        ];
    }
}

/**
 * Calibration Type Enum
 */
enum CalibrationType: string
{
    case ROUTINE = 'routine';
    case INITIAL = 'initial';
    case REPAIR = 'repair';
    case VERIFICATION = 'verification';
    case FIELD = 'field';
    case LABORATORY = 'laboratory';
    case CERTIFICATION = 'certification';

    public function getDisplayName(): string
    {
        return match($this) {
            self::ROUTINE => 'Routine Calibration',
            self::INITIAL => 'Initial Calibration',
            self::REPAIR => 'Post-Repair Calibration',
            self::VERIFICATION => 'Verification Calibration',
            self::FIELD => 'Field Calibration',
            self::LABORATORY => 'Laboratory Calibration',
            self::CERTIFICATION => 'Certification Calibration',
        };
    }

    public function getColor(): string
    {
        return match($this) {
            self::ROUTINE => 'blue',
            self::INITIAL => 'green',
            self::REPAIR => 'orange',
            self::VERIFICATION => 'purple',
            self::FIELD => 'yellow',
            self::LABORATORY => 'indigo',
            self::CERTIFICATION => 'red',
        };
    }

    public function getFrequency(): string
    {
        return match($this) {
            self::ROUTINE => '6 months',
            self::INITIAL => 'One-time',
            self::REPAIR => 'As needed',
            self::VERIFICATION => '12 months',
            self::FIELD => '3 months',
            self::LABORATORY => '12 months',
            self::CERTIFICATION => '12 months',
        };
    }
}

/**
 * Calibration Status Enum
 */
enum CalibrationStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case FAILED = 'failed';
    case EXPIRED = 'expired';

    public function getDisplayName(): string
    {
        return match($this) {
            self::PENDING => 'Pending Approval',
            self::APPROVED => 'Approved',
            self::FAILED => 'Failed',
            self::EXPIRED => 'Expired',
        };
    }

    public function getColor(): string
    {
        return match($this) {
            self::PENDING => 'yellow',
            self::APPROVED => 'green',
            self::FAILED => 'red',
            self::EXPIRED => 'gray',
        };
    }

    public function isValid(): bool
    {
        return in_array($this, [self::APPROVED]);
    }

    public function requiresAction(): bool
    {
        return in_array($this, [self::PENDING, self::FAILED]);
    }
}
