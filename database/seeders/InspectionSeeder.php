<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Inspection;
use App\Models\Asset;
use App\Models\User;
use Illuminate\Support\Str;
use Carbon\Carbon;

class InspectionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Disable foreign key constraints temporarily for seeding
        \DB::statement('PRAGMA foreign_keys = OFF');
        
        $assets = Asset::all();
        $users = User::all();
        
        if ($assets->isEmpty() || $users->isEmpty()) {
            $this->command->info('No assets or users found. Please run AssetSeeder and UserSeeder first.');
            return;
        }

        $inspections = [];
        $batchSize = 100;
        $totalInspections = 1500;

        // Inspection types and their characteristics
        $inspectionTypes = [
            'routine' => [
                'priority_distribution' => ['low' => 40, 'normal' => 40, 'high' => 15, 'critical' => 5],
                'frequency_days' => [30, 90],
                'score_range' => [70, 95]
            ],
            'periodic' => [
                'priority_distribution' => ['low' => 20, 'normal' => 40, 'high' => 30, 'critical' => 10],
                'frequency_days' => [90, 180],
                'score_range' => [60, 85]
            ],
            'safety' => [
                'priority_distribution' => ['low' => 10, 'normal' => 30, 'high' => 40, 'critical' => 20],
                'frequency_days' => [60, 120],
                'score_range' => [50, 80]
            ],
            'compliance' => [
                'priority_distribution' => ['low' => 30, 'normal' => 35, 'high' => 25, 'critical' => 10],
                'frequency_days' => [180, 365],
                'score_range' => [65, 90]
            ]
        ];

        for ($i = 1; $i <= $totalInspections; $i++) {
            $asset = $assets->random();
            $type = array_rand($inspectionTypes);
            $typeConfig = $inspectionTypes[$type];
            
            $priority = $this->getWeightedPriority($typeConfig['priority_distribution']);
            $inspectionDate = Carbon::now()->subDays(rand(1, 365));
            $score = rand($typeConfig['score_range'][0], $typeConfig['score_range'][1]);
            
            // Determine status based on score
            if ($score >= 90) {
                $status = 'passed';
            } elseif ($score >= 70) {
                $status = 'passed';
            } elseif ($score >= 50) {
                $status = 'failed';
            } else {
                $status = 'failed';
            }

            $inspector = $users->random();
            $creator = $users->random();

            $inspection = [
                'id' => Str::uuid(),
                'asset_id' => $asset->id,
                'title' => $this->generateInspectionTitle($type, $asset),
                'description' => $this->generateInspectionDescription($type, $asset),
                'inspection_type' => $type,
                'priority' => $priority,
                'status' => $status,
                'scheduled_date' => $inspectionDate->copy()->subDays(rand(1, 7)),
                'performed_date' => $inspectionDate,
                'inspector_id' => $inspector->id,
                'created_by' => $creator->id,
                'overall_score' => $score,
                'max_score' => 100,
                'findings' => $this->generateFindings($status),
                'recommendations' => $this->generateRecommendations($status),
                'next_inspection_date' => $inspectionDate->copy()->addDays(rand(30, 180)),
                'checklist_template_id' => null, // Will be set after ChecklistTemplateSeeder runs
                'notes' => $this->generateInspectionNotes($type),
                'created_at' => $inspectionDate->copy()->subDays(rand(1, 7)),
                'updated_at' => $inspectionDate,
            ];

            $inspections[] = $inspection;

            // Insert in batches
            if (count($inspections) >= $batchSize) {
                Inspection::insert($inspections);
                $inspections = [];
            }
        }

        // Insert remaining inspections
        if (!empty($inspections)) {
            Inspection::insert($inspections);
        }
    }

    private function getWeightedPriority($distribution): string
    {
        $rand = rand(1, 100);
        $cumulative = 0;

        foreach ($distribution as $priority => $percentage) {
            $cumulative += $percentage;
            if ($rand <= $cumulative) {
                return $priority;
            }
        }

        return 'medium';
    }

    private function generateInspectionTitle($type, $asset): string
    {
        $titles = [
            'routine' => [
                'Routine Inspection - ' . $asset->name,
                'Regular Check - ' . $asset->name,
                'Standard Inspection - ' . $asset->name,
                'Periodic Check - ' . $asset->name
            ],
            'periodic' => [
                'Detailed Inspection - ' . $asset->name,
                'Comprehensive Check - ' . $asset->name,
                'Thorough Inspection - ' . $asset->name,
                'Complete Assessment - ' . $asset->name
            ],
            'safety' => [
                'Safety Inspection - ' . $asset->name,
                'Safety Check - ' . $asset->name,
                'Safety Assessment - ' . $asset->name,
                'Safety Audit - ' . $asset->name
            ],
            'compliance' => [
                'Compliance Inspection - ' . $asset->name,
                'Regulatory Check - ' . $asset->name,
                'Compliance Audit - ' . $asset->name,
                'Standards Inspection - ' . $asset->name
            ]
        ];

        return $titles[$type][array_rand($titles[$type])];
    }

    private function generateInspectionDescription($type, $asset): string
    {
        $descriptions = [
            'routine' => 'Routine inspection to verify operational status and identify potential issues.',
            'periodic' => 'Comprehensive inspection covering all aspects of equipment condition and performance.',
            'safety' => 'Safety inspection to ensure compliance with safety standards and regulations.',
            'compliance' => 'Compliance inspection to verify adherence to industry standards and regulations.'
        ];

        return $descriptions[$type] . ' Asset: ' . $asset->name . ' (Serial: ' . $asset->serial_number . ')';
    }

    private function generateFindings($status): string
    {
        $findings = [
            'passed' => 'Equipment operating within normal parameters. No issues found.',
            'passed_with_notes' => 'Equipment operating normally with minor observations noted.',
            'failed' => 'Equipment not meeting standards. Several issues identified requiring attention.',
            'critical' => 'Critical issues found. Equipment requires immediate attention.'
        ];

        return $findings[$status];
    }

    private function generateRecommendations($status): string
    {
        $recommendations = [
            'passed' => 'Continue routine maintenance schedule. No immediate action required.',
            'passed_with_notes' => 'Monitor identified issues. Schedule follow-up inspection.',
            'failed' => 'Schedule corrective maintenance. Address identified issues promptly.',
            'critical' => 'Immediate action required. Equipment may need to be taken out of service.'
        ];

        return $recommendations[$status];
    }

    private function generateInspectionNotes($type): string
    {
        $notes = [
            'routine' => 'Standard inspection procedures followed. All checkpoints verified.',
            'periodic' => 'Comprehensive inspection completed. All systems tested thoroughly.',
            'safety' => 'Safety protocols followed. All safety features verified.',
            'compliance' => 'Compliance standards checked. All regulatory requirements verified.'
        ];

        return $notes[$type];
    }
    
    /**
     * Re-enable foreign key constraints after seeding
     */
    public function __destruct()
    {
        // Re-enable foreign key constraints
        \DB::statement('PRAGMA foreign_keys = ON');
    }
}
