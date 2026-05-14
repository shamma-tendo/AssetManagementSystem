<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Asset;
use App\Services\DepreciationService;
use Carbon\Carbon;

class CalculateDepreciation extends Command
{
    protected $signature = 'depreciation:calculate {--method=straight_line : Depreciation method}';
    protected $description = 'Calculate depreciation for all assets';

    public function handle(DepreciationService $service): void
    {
        $method = $this->option('method');
        $assets = Asset::where('status', '!=', 'Disposed')->get();

        $this->info("Calculating {$method} depreciation for {$assets->count()} assets...");

        $updated = 0;
        foreach ($assets as $asset) {
            try {
                $service->calculate($asset, $method);
                $updated++;
                $this->line("✓ Depreciation calculated for {$asset->name}");
            } catch (\Exception $e) {
                $this->error("✗ Error for {$asset->name}: {$e->getMessage()}");
            }
        }

        $this->info("Depreciation calculation completed for {$updated} assets!");
    }
}
