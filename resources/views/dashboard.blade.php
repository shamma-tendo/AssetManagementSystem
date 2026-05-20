@extends('layout')

@section('title', 'Dashboard')

@section('content')
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Dashboard</h1>
        <p class="mt-2 text-gray-600">Welcome to Asset & Equipment Management System</p>
    </div>

    <!-- KPI Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm font-medium">Total Assets</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2" id="total-assets">0</p>
                </div>
                <div class="bg-blue-100 p-3 rounded-full">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm font-medium">Active Assets</p>
                    <p class="text-3xl font-bold text-green-600 mt-2" id="active-assets">0</p>
                </div>
                <div class="bg-green-100 p-3 rounded-full">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm font-medium">Total Asset Value</p>
                    <p class="text-3xl font-bold text-purple-600 mt-2" id="total-value">$0</p>
                </div>
                <div class="bg-purple-100 p-3 rounded-full">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Secondary KPI Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-gray-500 text-sm font-medium mb-2">Assets Under Maintenance</p>
            <p class="text-2xl font-bold text-red-600" id="under-maintenance">0</p>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-gray-500 text-sm font-medium mb-2">Retired Assets</p>
            <p class="text-2xl font-bold text-red-600" id="retired-assets">0</p>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-gray-500 text-sm font-medium mb-2">Low Stock Items</p>
            <p class="text-2xl font-bold text-yellow-600" id="low-stock">0</p>
        </div>
    </div>

    <!-- Charts and Tables -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Assets by Status -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Assets by Status</h2>
            <div class="space-y-3">
                <div class="flex items-center">
                    <span class="text-sm text-gray-600 min-w-20">Active</span>
                    <div class="flex-1 bg-gray-200 rounded h-2 mx-3">
                        <div class="bg-green-500 h-2 rounded" id="status-active-bar" style="width: 60%"></div>
                    </div>
                    <span class="text-sm font-semibold text-gray-900" id="status-active-count">0</span>
                </div>
                <div class="flex items-center">
                    <span class="text-sm text-gray-600 min-w-20">Maintenance</span>
                    <div class="flex-1 bg-gray-200 rounded h-2 mx-3">
                        <div class="bg-orange-500 h-2 rounded" id="status-maint-bar" style="width: 20%"></div>
                    </div>
                    <span class="text-sm font-semibold text-gray-900" id="status-maint-count">0</span>
                </div>
                <div class="flex items-center">
                    <span class="text-sm text-gray-600 min-w-20">Retired</span>
                    <div class="flex-1 bg-gray-200 rounded h-2 mx-3">
                        <div class="bg-red-500 h-2 rounded" id="status-retired-bar" style="width: 10%"></div>
                    </div>
                    <span class="text-sm font-semibold text-gray-900" id="status-retired-count">0</span>
                </div>
            </div>
        </div>

        <!-- Placeholder: Assets by Category or other overview can go here -->
    </div>

    <!-- Low Stock -->
    <div class="grid grid-cols-1 gap-6">
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Low Stock Items</h2>
            <div class="space-y-3">
                <div class="flex items-center justify-between py-2 border-b border-gray-100">
                    <div>
                        <p class="text-sm font-medium text-gray-900">Hydraulic Oil</p>
                        <p class="text-xs text-gray-500">Stock: 3 / Reorder: 10</p>
                    </div>
                    <span class="text-xs px-2 py-1 rounded-full bg-red-100 text-red-800">Critical</span>
                </div>
            </div>
            <a href="{{ route('inventory.index') }}" class="inline-block mt-4 text-blue-600 hover:text-blue-700 text-sm font-medium">View all →</a>
        </div>
    </div>

    <script>
        aemsFetch('/api/dashboard')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data.summary) {
                    const summary = data.data.summary;
                    const tv = summary.total_asset_value != null ? Number(summary.total_asset_value) : 0;
                    document.getElementById('total-assets').textContent = summary.total_assets;
                    document.getElementById('active-assets').textContent = summary.active_assets;
                    document.getElementById('under-maintenance').textContent = summary.under_maintenance;
                    document.getElementById('total-value').textContent = '$' + tv.toLocaleString('en-US', {minimumFractionDigits: 2});
                }
            })
            .catch(error => console.error('Error fetching dashboard data:', error));

        aemsFetch('/api/spare-parts/stats')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('low-stock').textContent = data.data.low_stock_parts;
                }
            })
            .catch(error => console.error('Error fetching inventory stats:', error));

        aemsFetch('/api/assets/stats')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('retired-assets').textContent = data.data.retired_assets ?? 0;
                }
            })
            .catch(error => console.error('Error fetching asset stats:', error));
    </script>
@endsection
