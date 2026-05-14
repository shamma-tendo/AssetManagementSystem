<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Asset;
use App\Models\WorkOrder;
use Carbon\Carbon;

class GeneratePMSchedules extends Command
{
    protected $signature = 'pm:generate {--days=7 : Look ahead days for next PM}';
    protected $description = 'Generate preventive maintenance schedules for active assets';

    public function handle(): void
    {
        $days = $this->option('days');
        $activeAssets = Asset::where('status', 'Active')->get();

        $this->info("Generating PM schedules for {$activeAssets->count()} active assets...");

        $created = 0;
        foreach ($activeAssets as $asset) {
            // Simple PM schedule: every 30 days
            $lastMaintenance = $asset->maintenanceRecords()
                ->orderBy('maintenance_date', 'desc')
                ->first();

            $nextDueDate = $lastMaintenance
                ? $lastMaintenance->maintenance_date->copy()->addDays(30)
                : $asset->purchase_date->copy()->addDays(30);

            if ($nextDueDate->lte(now()->addDays($days))) {
                $existingWO = WorkOrder::where('asset_id', $asset->id)
                    ->where('type', 'Preventive')
                    ->whereIn('status', ['Open', 'In Progress'])
                    ->exists();

                if (!$existingWO) {
                    WorkOrder::create([
                        'work_order_number' => 'WO-' . strtoupper(uniqid()),
                        'asset_id' => $asset->id,
                        'type' => 'Preventive',
                        'status' => 'Open',
                        'scheduled_date' => $nextDueDate,
                        'description' => "Routine preventive maintenance for {$asset->name}",
                    ]);

                    $created++;
                    $this->line("✓ Created PM schedule for {$asset->name}");
                }
            }
        }

        $this->info("Generated {$created} PM schedules successfully!");
    }
}
