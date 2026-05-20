<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\AlertPreference;
use App\Models\AssetMetrics;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * Service for managing alerts and notifications
 */
class AlertService
{
    /**
     * Get active unresolved alerts for an organization
     */
    public function getActiveAlerts(string $organizationId, int $limit = 50): Collection
    {
        return Alert::where('organization_id', $organizationId)
            ->where('is_resolved', false)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get critical alerts requiring immediate attention
     */
    public function getCriticalAlerts(string $organizationId): Collection
    {
        return Alert::where('organization_id', $organizationId)
            ->where('is_resolved', false)
            ->where('severity', 'critical')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get alerts of a specific type
     */
    public function getAlertsByType(string $organizationId, string $alertType): Collection
    {
        return Alert::where('organization_id', $organizationId)
            ->where('alert_type', $alertType)
            ->where('is_resolved', false)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get alerts for specific user based on preferences
     */
    public function getUserAlerts(User $user, string $organizationId, int $limit = 20): Collection
    {
        $preferences = AlertPreference::where('user_id', $user->id)
            ->where('organization_id', $organizationId)
            ->first();

        $query = Alert::where('organization_id', $organizationId)
            ->where('is_resolved', false)
            ->orderBy('created_at', 'desc');

        // Filter based on preferences if they exist
        if ($preferences) {
            if (!$preferences->maintenance_alerts) {
                $query->whereNotIn('alert_type', ['maintenance_due', 'maintenance_overdue']);
            }
            if (!$preferences->damage_alerts) {
                $query->whereNotIn('alert_type', ['asset_damaged', 'asset_broken']);
            }
            if (!$preferences->high_value_alerts) {
                $query->whereNotIn('alert_type', ['high_value_item_moved']);
            }
        }

        return $query->limit($limit)->get();
    }

    /**
     * Resolve an alert
     */
    public function resolveAlert(Alert $alert, ?string $notes = null): Alert
    {
        return $alert->resolve($notes);
    }

    /**
     * Create a custom alert
     */
    public function createAlert(
        string $organizationId,
        string $alertType,
        string $title,
        string $message,
        string $severity = 'medium',
        ?string $assetId = null
    ): Alert {
        return Alert::create([
            'organization_id' => $organizationId,
            'asset_id' => $assetId,
            'alert_type' => $alertType,
            'title' => $title,
            'message' => $message,
            'severity' => $severity,
        ]);
    }

    /**
     * Get alert statistics
     */
    public function getAlertStats(string $organizationId): array
    {
        return [
            'total_unresolved' => Alert::where('organization_id', $organizationId)
                ->where('is_resolved', false)
                ->count(),
            'critical_count' => Alert::where('organization_id', $organizationId)
                ->where('is_resolved', false)
                ->where('severity', 'critical')
                ->count(),
            'high_count' => Alert::where('organization_id', $organizationId)
                ->where('is_resolved', false)
                ->where('severity', 'high')
                ->count(),
            'medium_count' => Alert::where('organization_id', $organizationId)
                ->where('is_resolved', false)
                ->where('severity', 'medium')
                ->count(),
            'low_count' => Alert::where('organization_id', $organizationId)
                ->where('is_resolved', false)
                ->where('severity', 'low')
                ->count(),
        ];
    }

    /**
     * Send notifications based on user preferences
     */
    public function notifyUser(User $user, Alert $alert, string $organizationId): bool
    {
        $preferences = AlertPreference::where('user_id', $user->id)
            ->where('organization_id', $organizationId)
            ->first();

        if (!$preferences) {
            return false;
        }

        $shouldNotify = false;

        if ($preferences->email_alerts) {
            // Send email notification
            try {
                \Mail::to($user->email)->send(new \App\Mail\AlertNotification($alert, $user));
                $shouldNotify = true;
            } catch (\Exception $e) {
                \Log::error('Failed to send email notification: ' . $e->getMessage());
            }
        }

        if ($preferences->push_notifications) {
            // Send push notification - placeholder for future implementation
            // This would integrate with services like Firebase Cloud Messaging, OneSignal, etc.
            try {
                // Placeholder: Store notification for future push delivery
                \Log::info("Push notification queued for user {$user->id}: {$alert->title}");
                $shouldNotify = true;
            } catch (\Exception $e) {
                \Log::error('Failed to queue push notification: ' . $e->getMessage());
            }
        }

        return $shouldNotify;
    }

    /**
     * Set alert preferences for user
     */
    public function setAlertPreferences(
        User $user,
        string $organizationId,
        array $preferences
    ): AlertPreference {
        return AlertPreference::updateOrCreate(
            [
                'user_id' => $user->id,
                'organization_id' => $organizationId,
            ],
            $preferences
        );
    }
}
