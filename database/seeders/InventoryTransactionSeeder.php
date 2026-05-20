<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\InventoryTransaction;
use App\Models\InventoryItem;
use App\Models\User;
use App\Models\Location;
use Illuminate\Support\Str;
use Carbon\Carbon;

class InventoryTransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $inventoryItems = InventoryItem::all();
        $users = User::all();
        $locations = Location::all();
        
        if ($inventoryItems->isEmpty() || $users->isEmpty()) {
            $this->command->info('No inventory items or users found. Please run InventoryItemSeeder and UserSeeder first.');
            return;
        }

        $transactions = [];
        $batchSize = 200;
        $totalTransactions = 5000;

        // Transaction types and their characteristics
        $transactionTypes = [
            'receive' => [
                'quantity_range' => [10, 100],
                'reason_distribution' => ['purchase' => 60, 'return' => 25, 'transfer' => 15]
            ],
            'issue' => [
                'quantity_range' => [1, 50],
                'reason_distribution' => ['maintenance' => 40, 'production' => 35, 'emergency' => 15, 'consumption' => 10]
            ],
            'adjust' => [
                'quantity_range' => [-20, 20],
                'reason_distribution' => ['count' => 40, 'damage' => 25, 'expiry' => 20, 'correction' => 15]
            ],
            'transfer' => [
                'quantity_range' => [5, 50],
                'reason_distribution' => ['relocation' => 50, 'reassignment' => 30, 'consolidation' => 20]
            ]
        ];

        for ($i = 1; $i <= $totalTransactions; $i++) {
            $item = $inventoryItems->random();
            $type = array_rand($transactionTypes);
            $typeConfig = $transactionTypes[$type];
            
            $reason = $this->getWeightedReason($typeConfig['reason_distribution']);
            $quantity = $type === 'adjust' ? rand($typeConfig['quantity_range'][0], $typeConfig['quantity_range'][1]) : rand(1, $typeConfig['quantity_range'][1]);
            $transactionDate = Carbon::now()->subDays(rand(1, 365));
            
            $user = $users->random();
            $location = $locations->random();

            $transaction = [
                'id' => Str::uuid(),
                'inventory_item_id' => $item->id,
                'transaction_type' => $type,
                'quantity' => $quantity,
                'unit_cost' => $item->unit_price,
                'total_cost' => abs($quantity) * $item->unit_price,
                'reason' => $reason,
                'user_id' => $user->id,
                'location_id' => $location->id,
                'reference_number' => $this->generateReferenceNumber($type),
                'notes' => $this->generateTransactionNotes($type, $reason),
                'created_at' => $transactionDate,
                'updated_at' => $transactionDate,
            ];

            $transactions[] = $transaction;

            // Insert in batches
            if (count($transactions) >= $batchSize) {
                InventoryTransaction::insert($transactions);
                $transactions = [];
            }
        }

        // Insert remaining transactions
        if (!empty($transactions)) {
            InventoryTransaction::insert($transactions);
        }
    }

    private function getWeightedReason($distribution): string
    {
        $rand = rand(1, 100);
        $cumulative = 0;

        foreach ($distribution as $reason => $percentage) {
            $cumulative += $percentage;
            if ($rand <= $cumulative) {
                return $reason;
            }
        }

        return 'other';
    }

    private function generateReferenceNumber($type): string
    {
        $prefixes = [
            'receive' => 'REC',
            'issue' => 'ISS',
            'adjust' => 'ADJ',
            'transfer' => 'TRF'
        ];

        return $prefixes[$type] . '-' . date('Y') . '-' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
    }

    private function generateTransactionNotes($type, $reason): string
    {
        $notes = [
            'receive' => [
                'purchase' => 'Items received from purchase order. Quality checked and approved.',
                'return' => 'Items returned from maintenance/production. Reconditioned and restocked.',
                'transfer' => 'Items transferred from another location. Documentation verified.'
            ],
            'issue' => [
                'maintenance' => 'Items issued for maintenance work. Work order referenced.',
                'production' => 'Items issued for production use. Production order referenced.',
                'emergency' => 'Emergency issue. Approval obtained from supervisor.',
                'consumption' => 'Items consumed during regular operations.'
            ],
            'adjust' => [
                'count' => 'Stock count adjustment. Physical count verified.',
                'damage' => 'Items damaged during handling. Written off.',
                'expiry' => 'Items expired. Disposed according to procedures.',
                'correction' => 'System correction. Previous transaction error identified.'
            ],
            'transfer' => [
                'relocation' => 'Items relocated to different storage area.',
                'reassignment' => 'Items reassigned to different department.',
                'consolidation' => 'Items consolidated from multiple locations.'
            ]
        ];

        return $notes[$type][$reason] ?? 'Standard transaction processed.';
    }
}
