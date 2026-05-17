<?php

namespace Database\Factories;

use App\Models\MaintenanceRecommendation;
use App\Models\Prediction;
use App\Models\Asset;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

/**
 * @extends Factory<MaintenanceRecommendation>
 */
class MaintenanceRecommendationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $prediction = Prediction::inRandomOrder()->first() ?? Prediction::factory()->create();
        $asset = Asset::inRandomOrder()->first() ?? Asset::factory()->create();
        $creator = User::factory()->create(['role' => UserRole::MANAGER]);

        $recommendationType = $this->faker->randomElement([
            'inspection', 'preventive_maintenance', 'corrective_maintenance', 'replacement',
            'upgrade', 'calibration', 'cleaning', 'lubrication', 'adjustment', 'repair', 'overhaul'
        ]);

        $urgency = $this->faker->randomElement(['routine', 'low', 'medium', 'high', 'critical']);

        return [
            'prediction_id' => $prediction->id,
            'asset_id' => $asset->id,
            'recommendation_type' => $recommendationType,
            'urgency' => $urgency,
            'description' => $this->generateDescription($recommendationType, $asset),
            'detailed_description' => $this->faker->paragraph(3),
            'estimated_cost' => $this->generateEstimatedCost($recommendationType, $asset),
            'estimated_duration_hours' => $this->generateEstimatedDuration($recommendationType),
            'recommended_date' => $this->generateRecommendedDate($urgency),
            'deadline_date' => $this->generateDeadlineDate($urgency),
            'required_parts' => $this->generateRequiredParts($recommendationType),
            'required_skills' => $this->generateRequiredSkills($recommendationType),
            'safety_requirements' => $this->generateSafetyRequirements($recommendationType),
            'impact_assessment' => $this->generateImpactAssessment($recommendationType),
            'cost_benefit_analysis' => $this->generateCostBenefitAnalysis($recommendationType, $asset),
            'alternative_options' => $this->generateAlternativeOptions($recommendationType),
            'implementation_plan' => $this->generateImplementationPlan($recommendationType),
            'status' => $this->faker->randomElement(['pending', 'approved', 'rejected', 'in_progress', 'completed', 'cancelled']),
            'assigned_to' => $this->faker->boolean(60) ? User::factory()->create(['role' => UserRole::TECHNICIAN])->id : null,
            'approved_by' => $this->faker->boolean(40) ? User::factory()->create(['role' => UserRole::MANAGER])->id : null,
            'approved_at' => $this->faker->boolean(40) ? $this->faker->dateTimeBetween('-30 days', 'now') : null,
            'rejected_by' => $this->faker->boolean(20) ? User::factory()->create(['role' => UserRole::MANAGER])->id : null,
            'rejected_at' => $this->faker->boolean(20) ? $this->faker->dateTimeBetween('-30 days', 'now') : null,
            'rejection_reason' => $this->faker->boolean(20) ? $this->faker->sentence(3) : null,
            'work_order_id' => $this->faker->boolean(30) ? $this->faker->uuid() : null,
            'completed_at' => $this->faker->boolean(30) ? $this->faker->dateTimeBetween('-30 days', 'now') : null,
            'completion_notes' => $this->faker->boolean(50) ? $this->faker->paragraph(2) : null,
            'actual_cost' => $this->faker->boolean(30) ? $this->faker->randomFloat(2, 50, 5000) : null,
            'actual_duration_hours' => $this->faker->boolean(30) ? $this->faker->randomFloat(2, 0.5, 40) : null,
            'effectiveness_rating' => $this->faker->boolean(30) ? $this->faker->randomFloat(2, 1, 5) : null,
            'created_by' => $creator->id,
        ];
    }

    /**
     * Generate description for recommendation type.
     */
    private function generateDescription(string $recommendationType, Asset $asset): string
    {
        return match($recommendationType) {
            'inspection' => "Schedule detailed inspection for {$asset->name}",
            'preventive_maintenance' => "Perform preventive maintenance on {$asset->name}",
            'corrective_maintenance' => "Execute corrective maintenance for {$asset->name}",
            'replacement' => "Plan replacement of {$asset->name}",
            'upgrade' => "Upgrade {$asset->name} to latest specifications",
            'calibration' => "Calibrate sensors and instruments on {$asset->name}",
            'cleaning' => "Perform thorough cleaning of {$asset->name}",
            'lubrication' => "Apply lubrication to moving parts of {$asset->name}",
            'adjustment' => "Adjust settings and parameters for {$asset->name}",
            'repair' => "Repair identified issues with {$asset->name}",
            'overhaul' => "Complete overhaul of {$asset->name}",
            default => "Maintenance action required for {$asset->name}",
        };
    }

    /**
     * Generate estimated cost.
     */
    private function generateEstimatedCost(string $recommendationType, Asset $asset): float
    {
        $baseCost = $asset->purchase_cost;
        
        $multiplier = match($recommendationType) {
            'inspection' => 0.01,
            'preventive_maintenance' => 0.05,
            'corrective_maintenance' => 0.15,
            'replacement' => 0.80,
            'upgrade' => 0.60,
            'calibration' => 0.02,
            'cleaning' => 0.005,
            'lubrication' => 0.003,
            'adjustment' => 0.01,
            'repair' => 0.10,
            'overhaul' => 0.40,
            default => 0.05,
        };

        return $baseCost * $multiplier * $this->faker->randomFloat(2, 0.8, 1.2);
    }

    /**
     * Generate estimated duration.
     */
    private function generateEstimatedDuration(string $recommendationType): float
    {
        return match($recommendationType) {
            'inspection' => $this->faker->randomFloat(2, 1, 4),
            'preventive_maintenance' => $this->faker->randomFloat(2, 2, 8),
            'corrective_maintenance' => $this->faker->randomFloat(2, 4, 16),
            'replacement' => $this->faker->randomFloat(2, 8, 32),
            'upgrade' => $this->faker->randomFloat(2, 12, 48),
            'calibration' => $this->faker->randomFloat(2, 1, 3),
            'cleaning' => $this->faker->randomFloat(2, 0.5, 2),
            'lubrication' => $this->faker->randomFloat(2, 0.5, 1.5),
            'adjustment' => $this->faker->randomFloat(2, 0.5, 2),
            'repair' => $this->faker->randomFloat(2, 3, 12),
            'overhaul' => $this->faker->randomFloat(2, 16, 64),
            default => $this->faker->randomFloat(2, 2, 8),
        };
    }

    /**
     * Generate recommended date.
     */
    private function generateRecommendedDate(string $urgency): Carbon
    {
        return match($urgency) {
            'critical' => now()->addDays($this->faker->numberBetween(0, 1)),
            'high' => now()->addDays($this->faker->numberBetween(1, 3)),
            'medium' => now()->addDays($this->faker->numberBetween(3, 14)),
            'low' => now()->addDays($this->faker->numberBetween(7, 30)),
            'routine' => now()->addDays($this->faker->numberBetween(14, 60)),
            default => now()->addDays(7),
        };
    }

    /**
     * Generate deadline date.
     */
    private function generateDeadlineDate(string $urgency): Carbon
    {
        return match($urgency) {
            'critical' => now()->addDays($this->faker->numberBetween(1, 3)),
            'high' => now()->addDays($this->faker->numberBetween(3, 7)),
            'medium' => now()->addDays($this->faker->numberBetween(14, 30)),
            'low' => now()->addDays($this->faker->numberBetween(30, 90)),
            'routine' => now()->addDays($this->faker->numberBetween(60, 120)),
            default => now()->addDays(14),
        };
    }

    /**
     * Generate required parts.
     */
    private function generateRequiredParts(string $recommendationType): array
    {
        return match($recommendationType) {
            'inspection' => [
                'inspection_checklist',
                'testing_equipment',
                'safety_gear',
            ],
            'preventive_maintenance' => [
                'filters',
                'lubricants',
                'gaskets',
                'seals',
            ],
            'corrective_maintenance' => [
                'replacement_parts',
                'repair_materials',
                'consumables',
            ],
            'replacement' => [
                'new_equipment',
                'mounting_hardware',
                'connection_kits',
            ],
            'upgrade' => [
                'upgrade_kits',
                'new_components',
                'software_licenses',
            ],
            'calibration' => [
                'calibration_standards',
                'reference_materials',
                'test_equipment',
            ],
            'cleaning' => [
                'cleaning_supplies',
                'solvents',
                'protective_equipment',
            ],
            'lubrication' => [
                'lubricants',
                'grease',
                'applicators',
            ],
            'adjustment' => [
                'adjustment_tools',
                'measurement_devices',
                'reference_materials',
            ],
            'repair' => [
                'spare_parts',
                'repair_materials',
                'fasteners',
            ],
            'overhaul' => [
                'overhaul_kit',
                'replacement_components',
                'seals_and_gaskets',
            ],
            default => ['basic_tools', 'safety_equipment'],
        };
    }

    /**
     * Generate required skills.
     */
    private function generateRequiredSkills(string $recommendationType): array
    {
        return match($recommendationType) {
            'inspection' => [
                'visual_inspection',
                'diagnostic_testing',
                'safety_procedures',
            ],
            'preventive_maintenance' => [
                'preventive_maintenance',
                'lubrication_techniques',
                'equipment_handling',
            ],
            'corrective_maintenance' => [
                'troubleshooting',
                'repair_techniques',
                'component_replacement',
            ],
            'replacement' => [
                'installation',
                'commissioning',
                'system_integration',
            ],
            'upgrade' => [
                'technical_upgrades',
                'system_modification',
                'software_updates',
            ],
            'calibration' => [
                'precision_measurement',
                'calibration_techniques',
                'quality_assurance',
            ],
            'cleaning' => [
                'cleaning_procedures',
                'chemical_handling',
                'equipment_care',
            ],
            'lubrication' => [
                'lubrication_methods',
                'grease_application',
                'maintenance_schedules',
            ],
            'adjustment' => [
                'precision_adjustment',
                'measurement_techniques',
                'equipment_tuning',
            ],
            'repair' => [
                'diagnostic_skills',
                'repair_methods',
                'parts_replacement',
            ],
            'overhaul' => [
                'complete_disassembly',
                'component_rebuilding',
                'system_reassembly',
            ],
            default => ['basic_maintenance', 'safety_awareness'],
        };
    }

    /**
     * Generate safety requirements.
     */
    private function generateSafetyRequirements(string $recommendationType): array
    {
        return match($recommendationType) {
            'inspection' => [
                'personal_protective_equipment',
                'lockout_tagout_procedures',
                'hazard_assessment',
            ],
            'preventive_maintenance' => [
                'ppe_requirements',
                'energy_isolation',
                'chemical_safety',
            ],
            'corrective_maintenance' => [
                'advanced_pp',
                'confined_space_entry',
                'hot_work_permits',
            ],
            'replacement' => [
                'heavy_equipment_safety',
                'rigging_procedures',
                'fall_protection',
            ],
            'upgrade' => [
                'electrical_safety',
                'software_safety',
                'change_management',
            ],
            'calibration' => [
                'precision_safety',
                'measurement_safety',
                'quality_safety',
            ],
            'cleaning' => [
                'chemical_safety',
                'ventilation_requirements',
                'waste_disposal',
            ],
            'lubrication' => [
                'chemical_handling',
                'spill_prevention',
                'fire_safety',
            ],
            'adjustment' => [
                'precision_safety',
                'measurement_safety',
                'tool_safety',
            ],
            'repair' => [
                'repair_safety',
                'parts_safety',
                'tool_safety',
            ],
            'overhaul' => [
                'comprehensive_safety',
                'multi_discipline_safety',
                'project_safety',
            ],
            default => ['basic_safety', 'ppe_required'],
        };
    }

    /**
     * Generate impact assessment.
     */
    private function generateImpactAssessment(string $recommendationType): array
    {
        return [
            'operational_impact' => $this->faker->randomElement(['low', 'medium', 'high']),
            'safety_impact' => $this->faker->randomElement(['none', 'low', 'medium', 'high']),
            'environmental_impact' => $this->faker->randomElement(['none', 'low', 'medium']),
            'cost_impact' => $this->faker->randomElement(['low', 'medium', 'high']),
            'downtime_required' => $this->faker->randomFloat(2, 0, 48),
            'resource_requirements' => [
                'technicians' => $this->faker->numberBetween(1, 4),
                'specialized_tools' => $this->faker->boolean(60),
                'external_contractors' => $this->faker->boolean(20),
            ],
        ];
    }

    /**
     * Generate cost benefit analysis.
     */
    private function generateCostBenefitAnalysis(string $recommendationType, Asset $asset): array
    {
        $estimatedCost = $this->generateEstimatedCost($recommendationType, $asset);
        $potentialSavings = $estimatedCost * $this->faker->randomFloat(2, 1.5, 5.0);
        
        return [
            'estimated_cost' => $estimatedCost,
            'potential_savings' => $potentialSavings,
            'roi_percentage' => (($potentialSavings - $estimatedCost) / $estimatedCost) * 100,
            'payback_period_months' => $this->faker->numberBetween(1, 24),
            'risk_mitigation_value' => $estimatedCost * $this->faker->randomFloat(2, 0.5, 2.0),
            'efficiency_gains' => $this->faker->randomFloat(2, 5, 25),
            'extended_equipment_life' => $this->faker->numberBetween(6, 36),
        ];
    }

    /**
     * Generate alternative options.
     */
    private function generateAlternativeOptions(string $recommendationType): array
    {
        return [
            [
                'option' => 'delay_maintenance',
                'description' => 'Delay maintenance to next scheduled window',
                'cost_impact' => $this->faker->randomFloat(2, -20, 50),
                'risk_impact' => $this->faker->randomElement(['low', 'medium', 'high']),
            ],
            [
                'option' => 'partial_maintenance',
                'description' => 'Perform partial maintenance now, complete later',
                'cost_impact' => $this->faker->randomFloat(2, -10, 30),
                'risk_impact' => $this->faker->randomElement(['low', 'medium']),
            ],
            [
                'option' => 'alternative_method',
                'description' => 'Use alternative maintenance method',
                'cost_impact' => $this->faker->randomFloat(2, -30, 20),
                'risk_impact' => $this->faker->randomElement(['low', 'medium']),
            ],
        ];
    }

    /**
     * Generate implementation plan.
     */
    private function generateImplementationPlan(string $recommendationType): array
    {
        return [
            'preparation_phase' => [
                'duration_hours' => $this->faker->numberBetween(1, 8),
                'activities' => [
                    'equipment_preparation',
                    'parts_gathering',
                    'safety_review',
                    'team_coordination',
                ],
            ],
            'execution_phase' => [
                'duration_hours' => $this->faker->numberBetween(2, 24),
                'activities' => [
                    'maintenance_execution',
                    'quality_checks',
                    'testing_procedures',
                    'documentation',
                ],
            ],
            'follow_up_phase' => [
                'duration_hours' => $this->faker->numberBetween(1, 4),
                'activities' => [
                    'performance_verification',
                    'cleanup_procedures',
                    'report_completion',
                    'lessons_learned',
                ],
            ],
        ];
    }

    /**
     * Create an inspection recommendation.
     */
    public function inspection(): static
    {
        return $this->state(fn (array $attributes) => [
            'recommendation_type' => 'inspection',
            'urgency' => $this->faker->randomElement(['low', 'medium', 'high']),
            'estimated_duration_hours' => $this->faker->randomFloat(2, 1, 4),
        ]);
    }

    /**
     * Create a preventive maintenance recommendation.
     */
    public function preventiveMaintenance(): static
    {
        return $this->state(fn (array $attributes) => [
            'recommendation_type' => 'preventive_maintenance',
            'urgency' => $this->faker->randomElement(['routine', 'low', 'medium']),
            'estimated_duration_hours' => $this->faker->randomFloat(2, 2, 8),
        ]);
    }

    /**
     * Create a corrective maintenance recommendation.
     */
    public function correctiveMaintenance(): static
    {
        return $this->state(fn (array $attributes) => [
            'recommendation_type' => 'corrective_maintenance',
            'urgency' => $this->faker->randomElement(['medium', 'high']),
            'estimated_duration_hours' => $this->faker->randomFloat(2, 4, 16),
        ]);
    }

    /**
     * Create a replacement recommendation.
     */
    public function replacement(): static
    {
        return $this->state(fn (array $attributes) => [
            'recommendation_type' => 'replacement',
            'urgency' => $this->faker->randomElement(['medium', 'high', 'critical']),
            'estimated_duration_hours' => $this->faker->randomFloat(2, 8, 32),
        ]);
    }

    /**
     * Create a critical urgency recommendation.
     */
    public function critical(): static
    {
        return $this->state(fn (array $attributes) => [
            'urgency' => 'critical',
            'recommended_date' => now()->addDays($this->faker->numberBetween(0, 1)),
            'deadline_date' => now()->addDays($this->faker->numberBetween(1, 3)),
            'estimated_cost' => $this->faker->randomFloat(2, 500, 10000),
        ]);
    }

    /**
     * Create a high urgency recommendation.
     */
    public function high(): static
    {
        return $this->state(fn (array $attributes) => [
            'urgency' => 'high',
            'recommended_date' => now()->addDays($this->faker->numberBetween(1, 3)),
            'deadline_date' => now()->addDays($this->faker->numberBetween(3, 7)),
            'estimated_cost' => $this->faker->randomFloat(2, 300, 5000),
        ]);
    }

    /**
     * Create a medium urgency recommendation.
     */
    public function medium(): static
    {
        return $this->state(fn (array $attributes) => [
            'urgency' => 'medium',
            'recommended_date' => now()->addDays($this->faker->numberBetween(3, 14)),
            'deadline_date' => now()->addDays($this->faker->numberBetween(14, 30)),
            'estimated_cost' => $this->faker->randomFloat(2, 200, 3000),
        ]);
    }

    /**
     * Create a low urgency recommendation.
     */
    public function low(): static
    {
        return $this->state(fn (array $attributes) => [
            'urgency' => 'low',
            'recommended_date' => now()->addDays($this->faker->numberBetween(7, 30)),
            'deadline_date' => now()->addDays($this->faker->numberBetween(30, 90)),
            'estimated_cost' => $this->faker->randomFloat(2, 100, 2000),
        ]);
    }

    /**
     * Create a routine urgency recommendation.
     */
    public function routine(): static
    {
        return $this->state(fn (array $attributes) => [
            'urgency' => 'routine',
            'recommended_date' => now()->addDays($this->faker->numberBetween(14, 60)),
            'deadline_date' => now()->addDays($this->faker->numberBetween(60, 120)),
            'estimated_cost' => $this->faker->randomFloat(2, 50, 1000),
        ]);
    }

    /**
     * Create a pending recommendation.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'approved_at' => null,
            'rejected_at' => null,
            'completed_at' => null,
        ]);
    }

    /**
     * Create an approved recommendation.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'approved_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'approved_by' => User::factory()->create(['role' => UserRole::MANAGER]),
        ]);
    }

    /**
     * Create a rejected recommendation.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'rejected_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'rejected_by' => User::factory()->create(['role' => UserRole::MANAGER]),
            'rejection_reason' => $this->faker->sentence(3),
        ]);
    }

    /**
     * Create an in-progress recommendation.
     */
    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'in_progress',
            'approved_at' => $this->faker->dateTimeBetween('-30 days', '-7 days'),
            'approved_by' => User::factory()->create(['role' => UserRole::MANAGER]),
        ]);
    }

    /**
     * Create a completed recommendation.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'completed_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'actual_cost' => $this->faker->randomFloat(2, 50, 5000),
            'actual_duration_hours' => $this->faker->randomFloat(2, 0.5, 40),
            'effectiveness_rating' => $this->faker->randomFloat(2, 1, 5),
            'completion_notes' => $this->faker->paragraph(2),
        ]);
    }

    /**
     * Create an overdue recommendation.
     */
    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'deadline_date' => now()->subDays($this->faker->numberBetween(1, 30)),
            'status' => $this->faker->randomElement(['pending', 'approved']),
        ]);
    }

    /**
     * Create a recommendation for specific prediction.
     */
    public function forPrediction(Prediction $prediction): static
    {
        return $this->state(fn (array $attributes) => [
            'prediction_id' => $prediction->id,
            'asset_id' => $prediction->asset_id,
            'urgency' => $this->mapRiskToUrgency($prediction->risk_level),
        ]);
    }

    /**
     * Create a recommendation for specific asset.
     */
    public function forAsset(Asset $asset): static
    {
        return $this->state(fn (array $attributes) => [
            'asset_id' => $asset->id,
        ]);
    }

    /**
     * Create a recommendation with work order.
     */
    public function withWorkOrder(): static
    {
        return $this->state(fn (array $attributes) => [
            'work_order_id' => $this->faker->uuid(),
            'status' => $this->faker->randomElement(['approved', 'in_progress']),
        ]);
    }

    /**
     * Create a recommendation assigned to user.
     */
    public function assignedTo(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'assigned_to' => $user->id,
        ]);
    }

    /**
     * Create a recommendation created by user.
     */
    public function createdBy(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'created_by' => $user->id,
        ]);
    }

    /**
     * Create a recent recommendation.
     */
    public function recent(): static
    {
        return $this->state(fn (array $attributes) => [
            'recommended_date' => now()->addDays($this->faker->numberBetween(0, 7)),
            'deadline_date' => now()->addDays($this->faker->numberBetween(7, 30)),
        ]);
    }

    /**
     * Create an old recommendation.
     */
    public function old(): static
    {
        return $this->state(fn (array $attributes) => [
            'recommended_date' => now()->subDays($this->faker->numberBetween(30, 90)),
            'deadline_date' => now()->subDays($this->faker->numberBetween(1, 30)),
        ]);
    }

    /**
     * Map risk level to urgency.
     */
    private function mapRiskToUrgency(string $riskLevel): string
    {
        return match($riskLevel) {
            'critical' => 'critical',
            'high' => 'high',
            'medium' => 'medium',
            'low' => 'low',
            'very_low' => 'routine',
            default => 'medium',
        };
    }
}
