@extends('layouts.app')

@section('title', 'Analytics')

@section('content')
<div>
    <!-- Page Header -->
    <div class="mb-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Analytics</h1>
                <p class="text-gray-600">System-wide performance metrics and insights.</p>
            </div>
            <form method="POST" action="{{ route('analytics.export') }}">
                @csrf
                <button type="submit" class="mt-4 sm:mt-0 px-4 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-xl font-medium shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all duration-200">
                    <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Export Report
                </button>
            </form>
        </div>
    </div>

    <!-- KPI Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Assets -->
        <a href="{{ route('asset-registry') }}" class="bg-white/10 backdrop-blur-xl border border-white/20 rounded-2xl p-6 shadow-xl hover:shadow-2xl transform hover:-translate-y-1 transition-all cursor-pointer">
            <p class="text-sm text-gray-600 font-medium mb-1">Total Assets</p>
            <p class="text-3xl font-bold text-gray-900">{{ number_format($stats['totalAssets']) }}</p>
            <div class="mt-3 flex items-center space-x-4 text-xs text-gray-500">
                <span class="text-green-600 font-medium">{{ $stats['assetsActive'] }} active</span>
                <span class="text-yellow-600 font-medium">{{ $stats['assetsInMaintenance'] }} in maintenance</span>
            </div>
        </a>

        <!-- Work Orders -->
        <a href="{{ route('maintenance') }}" class="bg-white/10 backdrop-blur-xl border border-white/20 rounded-2xl p-6 shadow-xl hover:shadow-2xl transform hover:-translate-y-1 transition-all cursor-pointer">
            <p class="text-sm text-gray-600 font-medium mb-1">Total Work Orders</p>
            <p class="text-3xl font-bold text-gray-900">{{ number_format($stats['totalWorkOrders']) }}</p>
            <div class="mt-3 flex items-center space-x-4 text-xs">
                <span class="text-green-600 font-medium">{{ $stats['completedWorkOrders'] }} completed</span>
                <span class="text-blue-600 font-medium">{{ $stats['inProgressWorkOrders'] }} in progress</span>
            </div>
        </a>

        <!-- Inspections -->
        <a href="{{ route('maintenance') }}" class="bg-white/10 backdrop-blur-xl border border-white/20 rounded-2xl p-6 shadow-xl hover:shadow-2xl transform hover:-translate-y-1 transition-all cursor-pointer">
            <p class="text-sm text-gray-600 font-medium mb-1">Inspections</p>
            <p class="text-3xl font-bold text-gray-900">{{ number_format($stats['totalInspections']) }}</p>
            <div class="mt-3 flex items-center space-x-4 text-xs">
                <span class="text-green-600 font-medium">{{ $stats['passedInspections'] }} passed</span>
                <span class="text-red-600 font-medium">{{ $stats['failedInspections'] }} failed</span>
            </div>
        </a>

        <!-- Sensor Readings -->
        <a href="{{ route('dashboard') }}" class="bg-white/10 backdrop-blur-xl border border-white/20 rounded-2xl p-6 shadow-xl hover:shadow-2xl transform hover:-translate-y-1 transition-all cursor-pointer">
            <p class="text-sm text-gray-600 font-medium mb-1">Sensor Readings</p>
            <p class="text-3xl font-bold text-gray-900">{{ number_format($stats['totalSensorReadings']) }}</p>
            <div class="mt-3 flex items-center space-x-4 text-xs">
                <span class="text-red-600 font-medium">{{ $stats['poorQualityReadings'] }} poor quality</span>
            </div>
        </a>
    </div>

    <!-- Secondary Stats with Actions -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-8">
        <a href="{{ route('maintenance') }}" class="bg-white/10 backdrop-blur-xl border border-white/20 rounded-2xl p-6 shadow-xl hover:shadow-2xl transform hover:-translate-y-1 transition-all cursor-pointer">
            <p class="text-sm text-gray-600 font-medium mb-1">Pending Work Orders</p>
            <p class="text-2xl font-bold text-yellow-600">{{ $stats['pendingWorkOrders'] }}</p>
            <p class="text-xs text-gray-500 mt-2">Click to view work orders</p>
        </a>
        <a href="{{ route('maintenance') }}" class="bg-white/10 backdrop-blur-xl border border-white/20 rounded-2xl p-6 shadow-xl hover:shadow-2xl transform hover:-translate-y-1 transition-all cursor-pointer">
            <p class="text-sm text-gray-600 font-medium mb-1">Overdue Maintenances</p>
            <p class="text-2xl font-bold text-red-600">{{ $stats['overdueMaintenances'] }}</p>
            <p class="text-xs text-gray-500 mt-2">Click to view maintenance</p>
        </a>
        <a href="{{ route('inventory') }}" class="bg-white/10 backdrop-blur-xl border border-white/20 rounded-2xl p-6 shadow-xl hover:shadow-2xl transform hover:-translate-y-1 transition-all cursor-pointer">
            <p class="text-sm text-gray-600 font-medium mb-1">Low Stock Parts</p>
            <p class="text-2xl font-bold text-orange-600">{{ $stats['lowStockParts'] }}</p>
            <p class="text-xs text-gray-500 mt-2">Click to view inventory</p>
        </a>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white/10 backdrop-blur-xl border border-white/20 rounded-2xl p-6 shadow-xl">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Asset Status Distribution</h3>
            <div class="h-64">
                <canvas id="assetChart"></canvas>
            </div>
        </div>
        <div class="bg-white/10 backdrop-blur-xl border border-white/20 rounded-2xl p-6 shadow-xl">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Work Order Status</h3>
            <div class="h-64">
                <canvas id="workOrderChart"></canvas>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Asset Status Chart
    const assetCtx = document.getElementById('assetChart').getContext('2d');
    new Chart(assetCtx, {
        type: 'doughnut',
        data: {
            labels: ['Active', 'In Maintenance', 'Retired'],
            datasets: [{
                data: [{{ $stats['assetsActive'] }}, {{ $stats['assetsInMaintenance'] }}, {{ $stats['assetsRetired'] }}],
                backgroundColor: [
                    'rgba(34, 197, 94, 0.8)',
                    'rgba(234, 179, 8, 0.8)',
                    'rgba(107, 114, 128, 0.8)'
                ],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        color: '#6B7280'
                    }
                }
            }
        }
    });

    // Work Order Status Chart
    const workOrderCtx = document.getElementById('workOrderChart').getContext('2d');
    new Chart(workOrderCtx, {
        type: 'bar',
        data: {
            labels: ['Completed', 'In Progress', 'Pending'],
            datasets: [{
                label: 'Work Orders',
                data: [{{ $stats['completedWorkOrders'] }}, {{ $stats['inProgressWorkOrders'] }}, {{ $stats['pendingWorkOrders'] }}],
                backgroundColor: [
                    'rgba(34, 197, 94, 0.8)',
                    'rgba(59, 130, 246, 0.8)',
                    'rgba(234, 179, 8, 0.8)'
                ],
                borderWidth: 0,
                borderRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(255, 255, 255, 0.1)'
                    },
                    ticks: {
                        color: '#6B7280'
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        color: '#6B7280'
                    }
                }
            }
        }
    });
});
</script>
@endpush
