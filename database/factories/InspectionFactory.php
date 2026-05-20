<?php

namespace Database\Factories;

use App\Models\Inspection;
use App\Models\Asset;
use App\Models\User;
use App\Models\UserRole;
use App\Models\ChecklistTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Inspection>
 */
class InspectionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $asset = Asset::inRandomOrder()->first() ?? Asset::factory()->create();
        $inspector = User::where('role', UserRole::TECHNICIAN)->inRandomOrder()->first() ?? User::factory()->create(['role' => UserRole::TECHNICIAN]);
        $creator = User::inRandomOrder()->first() ?? User::factory()->create(['role' => UserRole::MANAGER]);
        $template = ChecklistTemplate::inRandomOrder()->first() ?? ChecklistTemplate::factory()->create();

        return [
            'asset_id' => $asset->id,
            'title' => $this->faker->sentence(4) . ' Inspection',
            'description' => $this->faker->paragraph(3),
            'inspection_type' => $this->faker->randomElement(['routine', 'periodic', 'special', 'preventive', 'compliance', 'safety', 'environmental', 'quality', 'operational']),
            'scheduled_date' => $this->faker->dateTimeBetween('now', '+3 months'),
            'performed_date' => $this->faker->boolean(60) ? $this->faker->dateTimeBetween('-1 month', 'now') : null,
            'inspector_id' => $inspector->id,
            'supervisor_id' => $this->faker->boolean(70) ? User::where('role', UserRole::MANAGER)->inRandomOrder()->first()?->id : null,
            'status' => $this->faker->randomElement(['scheduled', 'in_progress', 'completed', 'cancelled']),
            'priority' => $this->faker->randomElement(['low', 'normal', 'high', 'urgent']),
            'duration_minutes' => $this->faker->boolean(70) ? $this->faker->numberBetween(15, 240) : null,
            'checklist_template_id' => $this->faker->boolean(80) ? $template->id : null,
            'checklist_items' => $this->faker->boolean(70) ? $this->generateChecklistItems() : null,
            'checklist_results' => $this->faker->boolean(50) ? $this->generateChecklistResults() : null,
            'overall_score' => $this->faker->boolean(60) ? $this->faker->randomFloat(2, 0, 100) : null,
            'max_score' => $this->faker->boolean(60) ? $this->faker->randomFloat(2, 50, 100) : null,
            'passing_score' => $this->faker->boolean(60) ? $this->faker->randomFloat(2, 60, 80) : null,
            'findings' => $this->faker->boolean(40) ? $this->generateFindings() : null,
            'recommendations' => $this->faker->boolean(30) ? $this->generateRecommendations() : null,
            'deficiencies' => $this->faker->boolean(25) ? $this->generateDeficiencies() : null,
            'corrective_actions_required' => $this->faker->boolean(20),
            'next_inspection_date' => $this->faker->boolean(40) ? $this->faker->dateTimeBetween('now', '+1 year') : null,
            'follow_up_required' => $this->faker->boolean(15),
            'follow_up_date' => $this->faker->boolean(10) ? $this->faker->dateTimeBetween('now', '+6 months') : null,
            'compliance_status' => $this->faker->boolean(60) ? $this->generateComplianceStatus() : null,
            'risk_assessment' => $this->faker->boolean(50) ? $this->generateRiskAssessment() : null,
            'safety_concerns' => $this->faker->boolean(30) ? $this->generateSafetyConcerns() : null,
            'environmental_concerns' => $this->faker->boolean(20) ? $this->generateEnvironmentalConcerns() : null,
            'notes' => $this->faker->boolean(50) ? $this->faker->paragraph(2) : null,
            'internal_notes' => $this->faker->boolean(25) ? $this->faker->paragraph(2) : null,
            'created_by' => $creator->id,
        ];
    }

    /**
     * Generate checklist items.
     */
    private function generateChecklistItems(): array
    {
        $items = [];
        $itemCount = $this->faker->numberBetween(3, 8);
        
        for ($i = 0; $i < $itemCount; $i++) {
            $type = $this->faker->randomElement(['checkbox', 'rating', 'text', 'number', 'photo']);
            
            $item = [
                'id' => 'item_' . uniqid(),
                'title' => $this->faker->sentence(3),
                'description' => $this->faker->boolean(60) ? $this->faker->sentence(2) : '',
                'type' => $type,
                'required' => $this->faker->boolean(30),
                'max_points' => $this->faker->numberBetween(5, 20),
                'help_text' => $this->faker->boolean(40) ? $this->faker->sentence(2) : '',
                'category' => $this->faker->randomElement(['general', 'safety', 'operational', 'environmental']),
                'order' => $i,
            ];

            switch ($type) {
                case 'rating':
                    $item['options'] = [1, 2, 3, 4, 5];
                    break;
                case 'text':
                    $item['validation_rules'] = [
                        'min_length' => $this->faker->numberBetween(10, 50),
                    ];
                    break;
                case 'number':
                    $item['validation_rules'] = [
                        'min' => $this->faker->numberBetween(0, 10),
                        'max' => $this->faker->numberBetween(50, 100),
                        'target' => $this->faker->numberBetween(20, 40),
                    ];
                    break;
                case 'photo':
                    $item['validation_rules'] = [
                        'min_photos' => $this->faker->numberBetween(1, 3),
                    ];
                    break;
            }

            $items[] = $item;
        }

        return $items;
    }

    /**
     * Generate checklist results.
     */
    private function generateChecklistResults(): array
    {
        $results = [];
        $itemCount = $this->faker->numberBetween(3, 8);
        
        for ($i = 0; $i < $itemCount; $i++) {
            $type = $this->faker->randomElement(['checkbox', 'rating', 'text', 'number', 'photo']);
            $completed = $this->faker->boolean(80);
            
            $result = [
                'item_id' => 'item_' . uniqid(),
                'completed' => $completed,
                'notes' => $completed && $this->faker->boolean(60) ? $this->faker->sentence(3) : '',
                'attachments' => $type === 'photo' && $completed ? ['photo_' . uniqid() . '.jpg'] : [],
            ];

            if ($completed) {
                switch ($type) {
                    case 'checkbox':
                        $result['result'] = $this->faker->boolean(70);
                        break;
                    case 'rating':
                        $result['result'] = $this->faker->numberBetween(1, 5);
                        break;
                    case 'text':
                        $result['result'] = $this->faker->sentence(4);
                        break;
                    case 'number':
                        $result['result'] = $this->faker->randomFloat(2, 10, 50);
                        break;
                }
            }

            $results[] = $result;
        }

        return $results;
    }

    /**
     * Generate findings.
     */
    private function generateFindings(): array
    {
        $findings = [];
        $count = $this->faker->numberBetween(1, 4);
        
        for ($i = 0; $i < $count; $i++) {
            $findings[] = [
                'description' => $this->faker->sentence(4),
                'severity' => $this->faker->randomElement(['low', 'medium', 'high', 'critical']),
                'category' => $this->faker->randomElement(['safety', 'operational', 'environmental', 'quality']),
                'identified_during' => $this->faker->randomElement(['visual_inspection', 'testing', 'measurement', 'documentation_review']),
                'location' => $this->faker->boolean(60) ? $this->faker->sentence(2) : null,
            ];
        }

        return $findings;
    }

    /**
     * Generate recommendations.
     */
    private function generateRecommendations(): array
    {
        $recommendations = [];
        $count = $this->faker->numberBetween(1, 3);
        
        for ($i = 0; $i < $count; $i++) {
            $recommendations[] = [
                'description' => $this->faker->sentence(4),
                'priority' => $this->faker->randomElement(['low', 'normal', 'high', 'urgent']),
                'timeline' => $this->faker->randomElement(['immediate', 'within_week', 'within_month', 'next_inspection']),
                'responsible_party' => $this->faker->randomElement(['maintenance', 'operations', 'management', 'external_contractor']),
                'estimated_cost' => $this->faker->boolean(60) ? $this->faker->randomFloat(2, 100, 5000) : null,
            ];
        }

        return $recommendations;
    }

    /**
     * Generate deficiencies.
     */
    private function generateDeficiencies(): array
    {
        $deficiencies = [];
        $count = $this->faker->numberBetween(1, 3);
        
        for ($i = 0; $i < $count; $i++) {
            $deficiencies[] = [
                'description' => $this->faker->sentence(4),
                'severity' => $this->faker->randomElement(['low', 'medium', 'high', 'critical']),
                'category' => $this->faker->randomElement(['safety', 'operational', 'environmental', 'compliance']),
                'reference' => $this->faker->boolean(70) ? $this->faker->sentence(2) : null,
                'corrective_action' => $this->faker->sentence(3),
                'estimated_cost' => $this->faker->boolean(70) ? $this->faker->randomFloat(2, 200, 10000) : null,
                'timeline' => $this->faker->randomElement(['immediate', '24_hours', '48_hours', '1_week', '1_month']),
            ];
        }

        return $deficiencies;
    }

    /**
     * Generate compliance status.
     */
    private function generateComplianceStatus(): array
    {
        return [
            'overall_compliance' => $this->faker->randomElement(['compliant', 'non_compliant', 'partially_compliant']),
            'compliance_percentage' => $this->faker->randomFloat(2, 0, 100),
            'violations' => $this->faker->boolean(30) ? $this->faker->numberBetween(1, 5) : 0,
            'last_audit_date' => $this->faker->dateTimeBetween('-2 years', 'now')->format('Y-m-d'),
            'next_audit_due' => $this->faker->dateTimeBetween('now', '+2 years')->format('Y-m-d'),
        ];
    }

    /**
     * Generate risk assessment.
     */
    private function generateRiskAssessment(): array
    {
        return [
            'overall_score' => $this->faker->numberBetween(1, 10),
            'safety_risk' => $this->faker->randomElement(['low', 'medium', 'high', 'critical']),
            'operational_risk' => $this->faker->randomElement(['low', 'medium', 'high', 'critical']),
            'environmental_risk' => $this->faker->randomElement(['low', 'medium', 'high']),
            'financial_risk' => $this->faker->randomElement(['low', 'medium', 'high']),
            'mitigation_measures' => $this->faker->boolean(70) ? [
                $this->faker->sentence(3),
                $this->faker->sentence(3),
            ] : [],
        ];
    }

    /**
     * Generate safety concerns.
     */
    private function generateSafetyConcerns(): array
    {
        $concerns = [];
        $count = $this->faker->numberBetween(0, 3);
        
        for ($i = 0; $i < $count; $i++) {
            $concerns[] = [
                'description' => $this->faker->sentence(4),
                'severity' => $this->faker->randomElement(['low', 'medium', 'high', 'critical']),
                'immediate_action_required' => $this->faker->boolean(30),
                'protective_equipment_needed' => $this->faker->boolean(40) ? [
                    $this->faker->word,
                    $this->faker->word,
                ] : [],
            ];
        }

        return $concerns;
    }

    /**
     * Generate environmental concerns.
     */
    private function generateEnvironmentalConcerns(): array
    {
        $concerns = [];
        $count = $this->faker->numberBetween(0, 2);
        
        for ($i = 0; $i < $count; $i++) {
            $concerns[] = [
                'description' => $this->faker->sentence(4),
                'type' => $this->faker->randomElement(['air_quality', 'water_quality', 'noise', 'waste', 'energy_consumption']),
                'severity' => $this->faker->randomElement(['low', 'medium', 'high']),
                'regulatory_impact' => $this->faker->boolean(30),
                'mitigation_required' => $this->faker->boolean(60),
            ];
        }

        return $concerns;
    }

    /**
     * Indicate that the inspection is scheduled.
     */
    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'scheduled',
            'performed_date' => null,
            'overall_score' => null,
            'max_score' => null,
        ]);
    }

    /**
     * Indicate that the inspection is in progress.
     */
    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'in_progress',
            'performed_date' => now()->subHours(rand(1, 24)),
            'overall_score' => null,
            'max_score' => null,
        ]);
    }

    /**
     * Indicate that the inspection is completed and passed.
     */
    public function passed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'passed',
            'performed_date' => now()->subDays(rand(1, 7)),
            'overall_score' => $this->faker->randomFloat(2, 75, 100),
            'max_score' => $this->faker->randomFloat(2, 80, 100),
            'passing_score' => $this->faker->randomFloat(2, 60, 70),
            'duration_minutes' => $this->faker->randomFloat(2, 30, 180),
            'deficiencies' => [],
            'corrective_actions_required' => false,
        ]);
    }

    /**
     * Indicate that the inspection is completed and failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'performed_date' => now()->subDays(rand(1, 7)),
            'overall_score' => $this->faker->randomFloat(2, 0, 60),
            'max_score' => $this->faker->randomFloat(2, 80, 100),
            'passing_score' => $this->faker->randomFloat(2, 70, 80),
            'duration_minutes' => $this->faker->randomFloat(2, 45, 240),
            'deficiencies' => $this->generateDeficiencies(),
            'corrective_actions_required' => true,
            'follow_up_required' => true,
        ]);
    }

    /**
     * Indicate that the inspection is overdue.
     */
    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'scheduled_date' => now()->subDays(rand(1, 30)),
            'status' => 'scheduled',
            'priority' => 'high',
        ]);
    }

    /**
     * Indicate that the inspection is due today.
     */
    public function dueToday(): static
    {
        return $this->state(fn (array $attributes) => [
            'scheduled_date' => today(),
            'status' => 'scheduled',
        ]);
    }

    /**
     * Indicate that the inspection requires follow-up.
     */
    public function requiresFollowUp(): static
    {
        return $this->state(fn (array $attributes) => [
            'follow_up_required' => true,
            'follow_up_date' => now()->addDays(rand(1, 30)),
            'deficiencies' => $this->generateDeficiencies(),
            'corrective_actions_required' => true,
        ]);
    }

    /**
     * Indicate that the inspection is a safety inspection.
     */
    public function safety(): static
    {
        return $this->state(fn (array $attributes) => [
            'inspection_type' => 'safety',
            'priority' => $this->faker->randomElement(['normal', 'high']),
            'checklist_items' => $this->generateSafetyChecklistItems(),
            'safety_concerns' => $this->generateSafetyConcerns(),
        ]);
    }

    /**
     * Indicate that the inspection is a compliance inspection.
     */
    public function compliance(): static
    {
        return $this->state(fn (array $attributes) => [
            'inspection_type' => 'compliance',
            'priority' => 'normal',
            'compliance_status' => $this->generateComplianceStatus(),
            'checklist_items' => $this->generateComplianceChecklistItems(),
        ]);
    }

    /**
     * Indicate that the inspection is an emergency inspection.
     */
    public function emergency(): static
    {
        return $this->state(fn (array $attributes) => [
            'inspection_type' => 'emergency',
            'priority' => 'critical',
            'scheduled_date' => now()->addHours(rand(1, 24)),
            'status' => 'in_progress',
        ]);
    }

    /**
     * Generate safety-specific checklist items.
     */
    private function generateSafetyChecklistItems(): array
    {
        return [
            [
                'id' => 'safety_item_1',
                'title' => 'Personal Protective Equipment Check',
                'type' => 'checkbox',
                'required' => true,
                'max_points' => 10,
                'category' => 'safety',
                'order' => 0,
            ],
            [
                'id' => 'safety_item_2',
                'title' => 'Emergency Equipment Inspection',
                'type' => 'rating',
                'required' => true,
                'max_points' => 15,
                'options' => [1, 2, 3, 4, 5],
                'category' => 'safety',
                'order' => 1,
            ],
            [
                'id' => 'safety_item_3',
                'title' => 'Safety Signage Verification',
                'type' => 'photo',
                'required' => true,
                'max_points' => 10,
                'validation_rules' => ['min_photos' => 2],
                'category' => 'safety',
                'order' => 2,
            ],
        ];
    }

    /**
     * Generate compliance-specific checklist items.
     */
    private function generateComplianceChecklistItems(): array
    {
        return [
            [
                'id' => 'compliance_item_1',
                'title' => 'Regulatory Documentation Review',
                'type' => 'checkbox',
                'required' => true,
                'max_points' => 20,
                'category' => 'compliance',
                'order' => 0,
            ],
            [
                'id' => 'compliance_item_2',
                'title' => 'Compliance Metrics Measurement',
                'type' => 'number',
                'required' => true,
                'max_points' => 25,
                'validation_rules' => ['target' => 100, 'tolerance' => 0.1],
                'category' => 'compliance',
                'order' => 1,
            ],
        ];
    }
}
