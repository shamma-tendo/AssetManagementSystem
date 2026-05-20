<?php

namespace App\Services;

use App\Models\Inspection;
use App\Models\ChecklistTemplate;
use App\Models\InspectionHistory;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class InspectionService
{
    /**
     * Process checklist results and calculate scores.
     */
    public function processChecklistResults(Inspection $inspection, array $checklistResults): array
    {
        $checklistItems = $inspection->checklist_items ?? [];
        $totalScore = 0;
        $maxScore = 0;
        $processedResults = [];

        foreach ($checklistResults as $result) {
            if (!isset($result['item_id'])) {
                continue;
            }

            // Find the corresponding checklist item
            $checklistItem = $this->findChecklistItem($checklistItems, $result['item_id']);
            if (!$checklistItem) {
                continue;
            }

            $itemMaxPoints = $checklistItem['max_points'] ?? 10;
            $itemScore = $this->calculateItemScore($checklistItem, $result);
            
            $totalScore += $itemScore;
            $maxScore += $itemMaxPoints;

            $processedResults[] = [
                'item_id' => $result['item_id'],
                'title' => $checklistItem['title'],
                'type' => $checklistItem['type'],
                'max_points' => $itemMaxPoints,
                'score' => $itemScore,
                'result' => $result['result'] ?? null,
                'notes' => $result['notes'] ?? '',
                'completed' => $result['completed'] ?? false,
                'attachments' => $result['attachments'] ?? [],
            ];
        }

        // Update inspection with processed results
        $inspection->update([
            'checklist_results' => $processedResults,
            'overall_score' => $totalScore,
            'max_score' => $maxScore,
        ]);

        return [
            'overall_score' => $totalScore,
            'max_score' => $maxScore,
            'processed_results' => $processedResults,
        ];
    }

    /**
     * Find checklist item by ID.
     */
    private function findChecklistItem(array $checklistItems, string $itemId): ?array
    {
        foreach ($checklistItems as $item) {
            if (($item['id'] ?? '') === $itemId) {
                return $item;
            }
        }
        return null;
    }

    /**
     * Calculate score for a checklist item.
     */
    private function calculateItemScore(array $checklistItem, array $result): float
    {
        $type = $checklistItem['type'] ?? 'checkbox';
        $maxPoints = $checklistItem['max_points'] ?? 10;

        return match($type) {
            'checkbox' => $this->calculateCheckboxScore($checklistItem, $result, $maxPoints),
            'rating' => $this->calculateRatingScore($checklistItem, $result, $maxPoints),
            'text' => $this->calculateTextScore($checklistItem, $result, $maxPoints),
            'number' => $this->calculateNumberScore($checklistItem, $result, $maxPoints),
            'photo' => $this->calculatePhotoScore($checklistItem, $result, $maxPoints),
            default => 0,
        };
    }

    /**
     * Calculate score for checkbox items.
     */
    private function calculateCheckboxScore(array $checklistItem, array $result, float $maxPoints): float
    {
        $completed = $result['completed'] ?? false;
        return $completed ? $maxPoints : 0;
    }

    /**
     * Calculate score for rating items.
     */
    private function calculateRatingScore(array $checklistItem, array $result, float $maxPoints): float
    {
        $rating = $result['result'] ?? 0;
        $options = $checklistItem['options'] ?? [];
        
        if (empty($options)) {
            return 0;
        }

        $maxRating = max(array_keys($options));
        $ratingPercentage = ($rating / $maxRating) * 100;
        
        return ($ratingPercentage / 100) * $maxPoints;
    }

    /**
     * Calculate score for text items.
     */
    private function calculateTextScore(array $checklistItem, array $result, float $maxPoints): float
    {
        $text = trim($result['result'] ?? '');
        $required = $checklistItem['required'] ?? false;
        
        if ($required && empty($text)) {
            return 0;
        }
        
        if (!$required) {
            return empty($text) ? 0 : $maxPoints;
        }

        // For required text items, check if it meets minimum length requirements
        $minLength = $checklistItem['validation_rules']['min_length'] ?? 10;
        $actualLength = strlen($text);
        
        if ($actualLength >= $minLength) {
            return $maxPoints;
        } elseif ($actualLength > 0) {
            // Partial credit for incomplete text
            return ($actualLength / $minLength) * $maxPoints;
        }
        
        return 0;
    }

    /**
     * Calculate score for number items.
     */
    private function calculateNumberScore(array $checklistItem, array $result, float $maxPoints): float
    {
        $number = $result['result'] ?? null;
        $validationRules = $checklistItem['validation_rules'] ?? [];
        
        if ($number === null) {
            $required = $checklistItem['required'] ?? false;
            return $required ? 0 : $maxPoints;
        }

        // Check if number is within acceptable range
        if (isset($validationRules['min']) && $number < $validationRules['min']) {
            return 0;
        }
        
        if (isset($validationRules['max']) && $number > $validationRules['max']) {
            return 0;
        }

        // For items with target values, calculate based on proximity
        if (isset($validationRules['target'])) {
            $target = $validationRules['target'];
            $tolerance = $validationRules['tolerance'] ?? 0.1; // 10% tolerance by default
            
            $difference = abs($number - $target);
            $toleranceRange = $target * $tolerance;
            
            if ($difference <= $toleranceRange) {
                return $maxPoints;
            } else {
                // Partial credit based on how close to target
                $scoreRatio = max(0, 1 - ($difference / ($target * 0.5))); // 50% deviation = 0 score
                return $scoreRatio * $maxPoints;
            }
        }

        return $maxPoints;
    }

    /**
     * Calculate score for photo items.
     */
    private function calculatePhotoScore(array $checklistItem, array $result, float $maxPoints): float
    {
        $required = $checklistItem['required'] ?? false;
        $attachments = $result['attachments'] ?? [];
        
        if ($required && empty($attachments)) {
            return 0;
        }
        
        if (!$required) {
            return empty($attachments) ? 0 : $maxPoints;
        }

        // For required photos, check minimum number of photos
        $minPhotos = $checklistItem['validation_rules']['min_photos'] ?? 1;
        $actualPhotos = count($attachments);
        
        if ($actualPhotos >= $minPhotos) {
            return $maxPoints;
        } elseif ($actualPhotos > 0) {
            // Partial credit for insufficient photos
            return ($actualPhotos / $minPhotos) * $maxPoints;
        }
        
        return 0;
    }

    /**
     * Format checklist results for reporting.
     */
    public function formatChecklistResults(Inspection $inspection): array
    {
        $results = $inspection->checklist_results ?? [];
        $formatted = [];

        foreach ($results as $result) {
            $formatted[] = [
                'title' => $result['title'],
                'type' => $result['type'],
                'score' => $result['score'],
                'max_points' => $result['max_points'],
                'score_percentage' => $result['max_points'] > 0 ? ($result['score'] / $result['max_points']) * 100 : 0,
                'completed' => $result['completed'],
                'result' => $result['result'],
                'notes' => $result['notes'],
                'attachments' => $result['attachments'],
            ];
        }

        return $formatted;
    }

    /**
     * Generate inspection checklist from template.
     */
    public function generateChecklistFromTemplate(ChecklistTemplate $template): array
    {
        $checklistItems = $template->checklist_items ?? [];
        $generatedChecklist = [];

        foreach ($checklistItems as $item) {
            $generatedChecklist[] = [
                'id' => uniqid('item_'),
                'title' => $item['title'],
                'description' => $item['description'] ?? '',
                'type' => $item['type'] ?? 'checkbox',
                'required' => $item['required'] ?? false,
                'max_points' => $item['max_points'] ?? 10,
                'options' => $item['options'] ?? null,
                'validation_rules' => $item['validation_rules'] ?? null,
                'help_text' => $item['help_text'] ?? '',
                'category' => $item['category'] ?? 'general',
                'order' => $item['order'] ?? 0,
            ];
        }

        return $generatedChecklist;
    }

    /**
     * Validate checklist completion.
     */
    public function validateChecklistCompletion(Inspection $inspection): array
    {
        $errors = [];
        $warnings = [];
        $checklistItems = $inspection->checklist_items ?? [];
        $checklistResults = $inspection->checklist_results ?? [];

        // Check if all required items are completed
        foreach ($checklistItems as $item) {
            if ($item['required'] ?? false) {
                $result = $this->findChecklistResult($checklistResults, $item['id']);
                if (!$result || !($result['completed'] ?? false)) {
                    $errors[] = "Required item '{$item['title']}' is not completed";
                }
            }
        }

        // Check for incomplete items that should have results
        foreach ($checklistResults as $result) {
            if ($result['completed'] ?? false) {
                $item = $this->findChecklistItem($checklistItems, $result['item_id']);
                if ($item) {
                    $type = $item['type'] ?? 'checkbox';
                    
                    switch ($type) {
                        case 'rating':
                            if (!isset($result['result']) || empty($result['result'])) {
                                $warnings[] = "Rating item '{$item['title']}' is marked as completed but has no rating";
                            }
                            break;
                        
                        case 'text':
                            if (!isset($result['result']) || empty(trim($result['result']))) {
                                $warnings[] = "Text item '{$item['title']}' is marked as completed but has no text";
                            }
                            break;
                        
                        case 'number':
                            if (!isset($result['result']) || $result['result'] === null) {
                                $warnings[] = "Number item '{$item['title']}' is marked as completed but has no value";
                            }
                            break;
                        
                        case 'photo':
                            if (!isset($result['attachments']) || empty($result['attachments'])) {
                                $warnings[] = "Photo item '{$item['title']}' is marked as completed but has no photos";
                            }
                            break;
                    }
                }
            }
        }

        return [
            'errors' => $errors,
            'warnings' => $warnings,
            'is_valid' => empty($errors),
        ];
    }

    /**
     * Find checklist result by item ID.
     */
    private function findChecklistResult(array $checklistResults, string $itemId): ?array
    {
        foreach ($checklistResults as $result) {
            if (($result['item_id'] ?? '') === $itemId) {
                return $result;
            }
        }
        return null;
    }

    /**
     * Calculate compliance metrics.
     */
    public function calculateComplianceMetrics(Inspection $inspection): array
    {
        $checklistResults = $inspection->checklist_results ?? [];
        $totalItems = count($checklistResults);
        $completedItems = 0;
        $totalScore = 0;
        $maxScore = 0;

        foreach ($checklistResults as $result) {
            if ($result['completed'] ?? false) {
                $completedItems++;
            }
            $totalScore += $result['score'] ?? 0;
            $maxScore += $result['max_points'] ?? 0;
        }

        return [
            'completion_rate' => $totalItems > 0 ? ($completedItems / $totalItems) * 100 : 100,
            'score_rate' => $maxScore > 0 ? ($totalScore / $maxScore) * 100 : 100,
            'passed' => $inspection->isPassed(),
            'compliance_level' => $this->getComplianceLevel($totalScore, $maxScore),
        ];
    }

    /**
     * Get compliance level based on score.
     */
    private function getComplianceLevel(float $score, float $maxScore): string
    {
        if ($maxScore == 0) {
            return 'unknown';
        }

        $percentage = ($score / $maxScore) * 100;

        return match(true) {
            $percentage >= 95 => 'excellent',
            $percentage >= 85 => 'good',
            $percentage >= 70 => 'acceptable',
            $percentage >= 50 => 'needs_improvement',
            default => 'poor',
        };
    }

    /**
     * Generate follow-up recommendations.
     */
    public function generateFollowUpRecommendations(Inspection $inspection): array
    {
        $recommendations = [];
        $deficiencies = $inspection->deficiencies ?? [];

        // Analyze deficiencies and generate recommendations
        foreach ($deficiencies as $deficiency) {
            $severity = $deficiency['severity'] ?? 'medium';
            $category = $deficiency['category'] ?? 'general';

            switch ($severity) {
                case 'critical':
                    $recommendations[] = [
                        'priority' => 'urgent',
                        'action' => 'Immediate corrective action required',
                        'timeline' => 'Within 24 hours',
                        'description' => "Critical deficiency identified: {$deficiency['description']}",
                    ];
                    break;

                case 'high':
                    $recommendations[] = [
                        'priority' => 'high',
                        'action' => 'Schedule corrective maintenance',
                        'timeline' => 'Within 7 days',
                        'description' => "High priority deficiency: {$deficiency['description']}",
                    ];
                    break;

                case 'medium':
                    $recommendations[] = [
                        'priority' => 'normal',
                        'action' => 'Plan corrective action',
                        'timeline' => 'Within 30 days',
                        'description' => "Medium priority deficiency: {$deficiency['description']}",
                    ];
                    break;

                case 'low':
                    $recommendations[] = [
                        'priority' => 'low',
                        'action' => 'Monitor and address during next maintenance',
                        'timeline' => 'Next scheduled maintenance',
                        'description' => "Low priority deficiency: {$deficiency['description']}",
                    ];
                    break;
            }
        }

        // Add general recommendations based on overall score
        if ($inspection->overall_score && $inspection->max_score) {
            $scorePercentage = ($inspection->overall_score / $inspection->max_score) * 100;

            if ($scorePercentage < 70) {
                $recommendations[] = [
                    'priority' => 'high',
                    'action' => 'Comprehensive asset review',
                    'timeline' => 'Within 14 days',
                    'description' => 'Overall inspection score indicates need for comprehensive review',
                ];
            } elseif ($scorePercentage < 85) {
                $recommendations[] = [
                    'priority' => 'normal',
                    'action' => 'Targeted improvements',
                    'timeline' => 'Within 30 days',
                    'description' => 'Address specific areas identified in inspection',
                ];
            }
        }

        return $recommendations;
    }

    /**
     * Schedule next inspection based on current results.
     */
    public function scheduleNextInspection(Inspection $inspection): ?Carbon
    {
        $inspectionType = $inspection->inspection_type;
        $score = $inspection->overall_score;
        $maxScore = $inspection->max_score;

        // Base intervals by inspection type
        $baseIntervals = [
            'routine' => 90, // 3 months
            'periodic' => 180, // 6 months
            'special' => 30, // 1 month
            'emergency' => 7, // 1 week
            'preventive' => 60, // 2 months
            'compliance' => 365, // 1 year
            'safety' => 30, // 1 month
            'environmental' => 180, // 6 months
            'quality' => 90, // 3 months
            'operational' => 30, // 1 month
            'acceptance' => null, // One-time
        ];

        $baseInterval = $baseIntervals[$inspectionType->value] ?? 90;

        if ($baseInterval === null) {
            return null; // No next inspection for one-time inspections
        }

        // Adjust interval based on score
        if ($score && $maxScore) {
            $scorePercentage = ($score / $maxScore) * 100;

            if ($scorePercentage < 70) {
                // Poor score - more frequent inspections
                $baseInterval = intval($baseInterval * 0.5);
            } elseif ($scorePercentage > 90) {
                // Excellent score - less frequent inspections
                $baseInterval = intval($baseInterval * 1.5);
            }
        }

        return now()->addDays($baseInterval);
    }

    /**
     * Export inspection data for reporting.
     */
    public function exportInspectionData(array $filters = []): array
    {
        $query = Inspection::with(['asset', 'inspector', 'checklistTemplate']);

        // Apply filters
        if (isset($filters['date_from'])) {
            $query->whereDate('performed_date', '>=', $filters['date_from']);
        }
        if (isset($filters['date_to'])) {
            $query->whereDate('performed_date', '<=', $filters['date_to']);
        }
        if (isset($filters['asset_id'])) {
            $query->where('asset_id', $filters['asset_id']);
        }
        if (isset($filters['inspection_type'])) {
            $query->where('inspection_type', $filters['inspection_type']);
        }
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $inspections = $query->get();

        $exportData = [];
        foreach ($inspections as $inspection) {
            $exportData[] = [
                'inspection_id' => $inspection->id,
                'title' => $inspection->title,
                'inspection_type' => $inspection->inspection_type->getDisplayName(),
                'status' => $inspection->status->getDisplayName(),
                'priority' => $inspection->priority->getDisplayName(),
                'asset_name' => $inspection->asset->name,
                'asset_serial' => $inspection->asset->serial_number,
                'inspector' => $inspection->inspector->full_name,
                'scheduled_date' => $inspection->scheduled_date->format('Y-m-d'),
                'performed_date' => $inspection->performed_date?->format('Y-m-d'),
                'duration_minutes' => $inspection->duration_minutes,
                'overall_score' => $inspection->overall_score,
                'max_score' => $inspection->max_score,
                'passing_score' => $inspection->passing_score,
                'compliance_percentage' => $inspection->compliance_percentage,
                'deficiency_count' => $inspection->deficiency_count,
                'finding_count' => $inspection->finding_count,
                'recommendation_count' => $inspection->recommendation_count,
                'risk_level' => $inspection->risk_level,
                'follow_up_required' => $inspection->follow_up_required,
                'created_at' => $inspection->created_at->format('Y-m-d H:i'),
            ];
        }

        return $exportData;
    }
}
