<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AssetRegistryController extends Controller
{
    /**
     * Display the asset registry page.
     */
    public function index()
    {
        // Get asset registry statistics
        $stats = $this->getAssetStats();
        
        // Get assets data
        $assets = $this->getAssets();
        
        // Get maintenance history data
        $maintenanceHistory = $this->getMaintenanceHistory();
        
        return view('asset-registry', compact('stats', 'assets', 'maintenanceHistory'));
    }
    
    /**
     * Get asset statistics.
     */
    private function getAssetStats()
    {
        return [
            'totalAssets' => 1248,
            'operational' => 1182,
            'inRepair' => 42,
            'uptimeAvg' => 99.2,
        ];
    }
    
    /**
     * Get assets data for the registry.
     */
    private function getAssets()
    {
        return [
            [
                'id' => 'CNV-9821-X',
                'name' => 'Conveyor System Alpha',
                'category' => 'Material Handling',
                'location' => 'Factory Floor A',
                'health' => 92,
                'status' => 'ACTIVE',
                'lastMaintenance' => '2024-05-10',
                'manufacturer' => 'TechConveyor Inc.',
                'installedDate' => '2022-03-15',
                'warrantyEnd' => '2025-03-15',
                'powerRequirement' => '15kW'
            ],
            [
                'id' => 'RBT-4412-M',
                'name' => 'Robotic Arm Unit 12',
                'category' => 'Automation',
                'location' => 'Assembly Line B',
                'health' => 78,
                'status' => 'IN REPAIR',
                'lastMaintenance' => '2024-05-08',
                'manufacturer' => 'RoboTech Systems',
                'installedDate' => '2021-11-20',
                'warrantyEnd' => '2024-11-20',
                'powerRequirement' => '8kW'
            ],
            [
                'id' => 'PMP-0034-L',
                'name' => 'Industrial Pump Lambda',
                'category' => 'Fluid Systems',
                'location' => 'Pumping Station C',
                'health' => 95,
                'status' => 'ACTIVE',
                'lastMaintenance' => '2024-05-12',
                'manufacturer' => 'PumpMaster Pro',
                'installedDate' => '2023-01-10',
                'warrantyEnd' => '2026-01-10',
                'powerRequirement' => '22kW'
            ],
            [
                'id' => 'GEN-7722-H',
                'name' => 'Generator Unit H',
                'category' => 'Power Systems',
                'location' => 'Power House D',
                'health' => 88,
                'status' => 'ACTIVE',
                'lastMaintenance' => '2024-05-05',
                'manufacturer' => 'PowerGen Corp',
                'installedDate' => '2020-08-15',
                'warrantyEnd' => '2023-08-15',
                'powerRequirement' => '500kW'
            ],
            [
                'id' => 'SEN-2201-T',
                'name' => 'Temperature Sensor Array',
                'category' => 'Monitoring',
                'location' => 'Control Room E',
                'health' => 99,
                'status' => 'ACTIVE',
                'lastMaintenance' => '2024-05-11',
                'manufacturer' => 'SenseTech Inc.',
                'installedDate' => '2023-06-01',
                'warrantyEnd' => '2026-06-01',
                'powerRequirement' => '0.5kW'
            ],
            [
                'id' => 'MOT-5533-P',
                'name' => 'Motor Drive System P',
                'category' => 'Drives',
                'location' => 'Motor Room F',
                'health' => 65,
                'status' => 'IN REPAIR',
                'lastMaintenance' => '2024-04-28',
                'manufacturer' => 'DriveTech Ltd',
                'installedDate' => '2019-12-10',
                'warrantyEnd' => '2022-12-10',
                'powerRequirement' => '35kW'
            ],
            [
                'id' => 'VAL-8844-V',
                'name' => 'Control Valve V',
                'category' => 'Control Systems',
                'location' => 'Process Line G',
                'health' => 91,
                'status' => 'ACTIVE',
                'lastMaintenance' => '2024-05-09',
                'manufacturer' => 'ValveControl Pro',
                'installedDate' => '2022-09-20',
                'warrantyEnd' => '2025-09-20',
                'powerRequirement' => '2kW'
            ],
            [
                'id' => 'HLT-9966-H',
                'name' => 'Hydraulic Lift H',
                'category' => 'Lifting Systems',
                'location' => 'Warehouse H',
                'health' => 45,
                'status' => 'RETIRED',
                'lastMaintenance' => '2024-03-15',
                'manufacturer' => 'LiftTech Systems',
                'installedDate' => '2018-05-10',
                'warrantyEnd' => '2021-05-10',
                'powerRequirement' => '18kW'
            ]
        ];
    }
    
    /**
     * Get maintenance history data for charts.
     */
    private function getMaintenanceHistory()
    {
        return [
            'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            'preventive' => [45, 52, 48, 58, 62, 55],
            'corrective' => [12, 15, 18, 14, 20, 16],
            'predictive' => [8, 10, 12, 15, 18, 22]
        ];
    }
    
    /**
     * Get asset details via API.
     */
    public function getAssetDetails($assetId)
    {
        $assets = $this->getAssets();
        $asset = collect($assets)->firstWhere('id', $assetId);
        
        if (!$asset) {
            return response()->json([
                'success' => false,
                'message' => 'Asset not found'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => $asset
        ]);
    }
    
    /**
     * Export assets data.
     */
    public function exportAssets(Request $request)
    {
        $format = $request->input('format', 'csv');
        $assets = $this->getAssets();
        
        switch ($format) {
            case 'csv':
                return $this->exportCsv($assets);
            case 'excel':
                return $this->exportExcel($assets);
            default:
                return response()->json($assets);
        }
    }
    
    /**
     * Export data as CSV.
     */
    private function exportCsv($assets)
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="asset_registry.csv"'
        ];
        
        $callback = function() use ($assets) {
            $file = fopen('php://output', 'w');
            
            // Header
            fputcsv($file, ['Asset ID', 'Name', 'Category', 'Location', 'Health', 'Status', 'Last Maintenance']);
            
            // Data
            foreach ($assets as $asset) {
                fputcsv($file, [
                    $asset['id'],
                    $asset['name'],
                    $asset['category'],
                    $asset['location'],
                    $asset['health'] . '%',
                    $asset['status'],
                    $asset['lastMaintenance']
                ]);
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }
    
    /**
     * Export data as Excel.
     */
    private function exportExcel($assets)
    {
        return response()->json([
            'message' => 'Excel export not implemented yet',
            'data' => $assets
        ]);
    }

    /**
     * Store a new asset.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'serial_number' => 'required|string|max:100|unique:assets,serial_number',
            'category' => 'required|string',
            'status' => 'required|in:active,under_maintenance,retired',
        ]);

        // For now, return success message
        // In a real implementation, you would save to the database
        return redirect()->route('asset-registry')->with('success', 'Asset created successfully!');
    }
}
