<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Support\Str;
use Carbon\Carbon;

class PurchaseOrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $suppliers = Supplier::all();
        $users = User::all();
        
        if ($suppliers->isEmpty() || $users->isEmpty()) {
            $this->command->info('No suppliers or users found. Please run SupplierSeeder and UserSeeder first.');
            return;
        }

        $purchaseOrders = [];
        $batchSize = 100;
        $totalOrders = 800;

        for ($i = 1; $i <= $totalOrders; $i++) {
            $supplier = $suppliers->random();
            $creator = $users->random();
            
            $orderDate = Carbon::now()->subDays(rand(1, 365));
            $expectedDate = $orderDate->copy()->addDays(rand(7, 60));
            $totalAmount = rand(500, 50000);
            
            // Determine order status
            $status = $this->determineOrderStatus($orderDate, $expectedDate);

            $order = [
                'id' => Str::uuid(),
                'order_number' => $this->generateOrderNumber($orderDate, $i),
                'supplier_id' => $supplier->id,
                'created_by' => $creator->id,
                'order_date' => $orderDate,
                'expected_delivery_date' => $expectedDate,
                'status' => $status,
                'priority' => $this->getPriority($totalAmount),
                'total_amount' => $totalAmount,
                'tax_amount' => $totalAmount * 0.08,
                'shipping_cost' => rand(25, 500),
                'notes' => $this->generateOrderNotes($status),
                'payment_terms' => 'Net 30',
                'delivery_terms' => 'FOB Destination',
                'approved_by' => $status !== 'pending' ? $users->random()->id : null,
                'approved_at' => $status !== 'pending' ? $orderDate->copy()->addDays(rand(1, 3)) : null,
                'actual_delivery_date' => $status === 'received' ? $expectedDate->copy()->addDays(rand(-2, 2)) : null,
                'created_at' => $orderDate,
                'updated_at' => $status === 'received' ? $expectedDate : now(),
            ];

            $purchaseOrders[] = $order;

            // Insert in batches
            if (count($purchaseOrders) >= $batchSize) {
                PurchaseOrder::insert($purchaseOrders);
                $purchaseOrders = [];
            }
        }

        // Insert remaining orders
        if (!empty($purchaseOrders)) {
            PurchaseOrder::insert($purchaseOrders);
        }
    }

    private function generateOrderNumber($date, $index): string
    {
        return 'PO-' . $date->format('Y') . '-' . str_pad($index, 6, '0', STR_PAD_LEFT);
    }

    private function determineOrderStatus($orderDate, $expectedDate): string
    {
        $now = now();
        
        if ($expectedDate < $now) {
            return rand(0, 1) ? 'received' : 'cancelled';
        } elseif ($expectedDate <= $now->addDays(7)) {
            return 'shipped';
        } elseif ($orderDate <= $now->subDays(30)) {
            return rand(0, 1) ? 'received' : 'shipped';
        } else {
            return rand(0, 1) ? 'approved' : 'pending';
        }
    }

    private function getPriority($totalAmount): string
    {
        if ($totalAmount > 25000) {
            return 'high';
        } elseif ($totalAmount > 10000) {
            return 'normal';
        } else {
            return 'low';
        }
    }

    private function generateOrderNotes($status): string
    {
        $notes = [
            'pending' => 'Order submitted for approval. Awaiting manager review.',
            'approved' => 'Order approved. Supplier notified of shipment requirements.',
            'in_transit' => 'Order shipped. Tracking number available.',
            'received' => 'Order received and inspected. Items added to inventory.',
            'overdue' => 'Order overdue. Follow up with supplier required.'
        ];

        return $notes[$status] ?? 'Standard order processed.';
    }

    private function getShippingMethod($totalAmount): string
    {
        if ($totalAmount > 20000) {
            return 'freight';
        } elseif ($totalAmount > 5000) {
            return 'ground';
        } else {
            return 'standard';
        }
    }

    private function getShippingAddress(): string
    {
        return '123 Industrial Way, Manufacturing City, MC 12345, USA';
    }

    private function getBillingAddress(): string
    {
        return '456 Corporate Blvd, Business Park, BP 67890, USA';
    }
}
