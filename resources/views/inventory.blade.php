@extends('layouts.app')

@section('title', 'Inventory Management')

@section('content')
<div x-data="inventoryManagement()" x-init="init()">
    
    <!-- Page Header -->
    <div class="mb-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Inventory Management</h1>
                <p class="text-gray-600">Track and manage <span x-text="formatNumber(stats.totalItems)">342</span> inventory items across all warehouses.</p>
            </div>
            <div class="flex items-center space-x-3 mt-4 sm:mt-0">
                <!-- Filter Button -->
                <button @click="showFilters = !showFilters" class="px-4 py-2 bg-white/20 backdrop-blur-sm border border-white/30 rounded-xl text-sm font-medium text-gray-900 hover:bg-white/30 transition-all duration-200 flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                    </svg>
                    Filter
                </button>
                
                <!-- Export CSV Button -->
                <button onclick="exportInventory()" class="px-4 py-2 bg-white/20 backdrop-blur-sm border border-white/30 rounded-xl text-sm font-medium text-gray-900 hover:bg-white/30 transition-all duration-200 flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Export CSV
                </button>
                
                <!-- New Item Button -->
                <button class="px-4 py-2 bg-gradient-to-r from-gray-900 to-black text-white rounded-xl font-medium shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all duration-200 flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    + New Item
                </button>
            </div>
        </div>
    </div>
    
    <!-- Filters Panel -->
    <div x-show="showFilters" x-transition class="mb-6">
        <div class="glass-card rounded-2xl p-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                    <select class="w-full px-3 py-2 glass-input rounded-lg text-sm text-gray-900 placeholder-gray-500">
                        <option>All Categories</option>
                        <option>Mechanical Parts</option>
                        <option>Fluids & Lubricants</option>
                        <option>Electronics</option>
                        <option>Motors & Drives</option>
                        <option>Valves & Fittings</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                    <select class="w-full px-3 py-2 glass-input rounded-lg text-sm text-gray-900 placeholder-gray-500">
                        <option>All Status</option>
                        <option>IN_STOCK</option>
                        <option>LOW_STOCK</option>
                        <option>OUT_OF_STOCK</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Location</label>
                    <select class="w-full px-3 py-2 glass-input rounded-lg text-sm text-gray-900 placeholder-gray-500">
                        <option>All Locations</option>
                        <option>Warehouse A</option>
                        <option>Warehouse B</option>
                        <option>Storage Tank B</option>
                        <option>Electronics Cabinet C</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Supplier</label>
                    <select class="w-full px-3 py-2 glass-input rounded-lg text-sm text-gray-900 placeholder-gray-500">
                        <option>All Suppliers</option>
                        <option>TechParts Inc.</option>
                        <option>FluidTech Solutions</option>
                        <option>SensorTech Pro</option>
                        <option>BeltMaster Corp</option>
                    </select>
                </div>
            </div>
        </div>
    </div>
    
    <!-- KPI Cards Section -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Total Items Card -->
        <div class="bg-white/10 backdrop-blur-xl border border-white/20 rounded-2xl p-6 shadow-xl hover:shadow-2xl transform hover:-translate-y-1 transition-all duration-300 fade-in">
            <div class="flex items-center justify-between mb-4">
                <div class="p-3 bg-blue-500/20 rounded-xl">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                    </svg>
                </div>
                <span class="text-xs text-blue-500 font-medium">+12</span>
            </div>
            <div class="space-y-1">
                <p class="text-sm text-gray-600 font-medium">Total Items</p>
                <p class="text-3xl font-bold text-gray-900" x-text="formatNumber(stats.totalItems)">342</p>
            </div>
        </div>
        
        <!-- Low Stock Card -->
        <div class="bg-white/10 backdrop-blur-xl border border-white/20 rounded-2xl p-6 shadow-xl hover:shadow-2xl transform hover:-translate-y-1 transition-all duration-300 fade-in" style="animation-delay: 0.1s">
            <div class="flex items-center justify-between mb-4">
                <div class="p-3 bg-yellow-500/20 rounded-xl">
                    <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 15.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                </div>
                <span class="text-xs text-yellow-500 font-medium">-3</span>
            </div>
            <div class="space-y-1">
                <p class="text-sm text-gray-600 font-medium">Low Stock</p>
                <p class="text-3xl font-bold text-gray-900" x-text="formatNumber(stats.lowStock)">12</p>
            </div>
        </div>
        
        <!-- Out of Stock Card -->
        <div class="bg-white/10 backdrop-blur-xl border border-white/20 rounded-2xl p-6 shadow-xl hover:shadow-2xl transform hover:-translate-y-1 transition-all duration-300 fade-in" style="animation-delay: 0.2s">
            <div class="flex items-center justify-between mb-4">
                <div class="p-3 bg-red-500/20 rounded-xl">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 15.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                </div>
                <span class="text-xs text-red-500 font-medium">+1</span>
            </div>
            <div class="space-y-1">
                <p class="text-sm text-gray-600 font-medium">Out of Stock</p>
                <p class="text-3xl font-bold text-gray-900" x-text="formatNumber(stats.outOfStock)">3</p>
            </div>
        </div>
        
        <!-- Total Value Card -->
        <div class="bg-gradient-to-br from-gray-800 to-black border border-white/20 rounded-2xl p-6 shadow-xl hover:shadow-2xl transform hover:-translate-y-1 transition-all duration-300 fade-in relative overflow-hidden" style="animation-delay: 0.3s">
            <!-- Industrial texture overlay -->
            <div class="absolute inset-0 opacity-5" style="background-image: url('data:image/svg+xml,%3Csvg width='60' height='60' xmlns='http://www.w3.org/2000/svg'%3E%3Cdefs%3E%3Cpattern id='grid' width='60' height='60' patternUnits='userSpaceOnUse'%3E%3Cpath d='M 60 0 L 0 0 0 60' fill='none' stroke='white' stroke-width='1'/%3E%3C/pattern%3E%3C/defs%3E%3Crect width='100%25' height='100%25' fill='url(%23grid)' /%3E%3C/svg%3E');"></div>
            
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-white/10 rounded-xl">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <span class="text-xs text-green-400 font-medium">+5.2%</span>
                </div>
                <div class="space-y-1">
                    <p class="text-sm text-gray-300 font-medium">Total Value</p>
                    <p class="text-3xl font-bold text-white" x-text="'$' + formatNumber(stats.totalValue)">$284,750</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Inventory Table Section -->
    <div class="glass-card rounded-2xl p-6 shadow-xl mb-8">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-bold text-gray-900">Inventory Items</h2>
            <div class="flex items-center space-x-2">
                <input type="text" placeholder="Search items..." x-model="searchQuery" 
                       class="px-3 py-2 glass-input rounded-lg text-sm text-gray-900 placeholder-gray-500 w-64">
            </div>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-white/10">
                        <th class="text-left py-3 px-4 text-sm font-medium text-gray-700">Item ID</th>
                        <th class="text-left py-3 px-4 text-sm font-medium text-gray-700">Name</th>
                        <th class="text-left py-3 px-4 text-sm font-medium text-gray-700">Category</th>
                        <th class="text-left py-3 px-4 text-sm font-medium text-gray-700">SKU</th>
                        <th class="text-left py-3 px-4 text-sm font-medium text-gray-700">Quantity</th>
                        <th class="text-left py-3 px-4 text-sm font-medium text-gray-700">Stock Status</th>
                        <th class="text-left py-3 px-4 text-sm font-medium text-gray-700">Unit Price</th>
                        <th class="text-left py-3 px-4 text-sm font-medium text-gray-700">Total Value</th>
                        <th class="text-left py-3 px-4 text-sm font-medium text-gray-700">Location</th>
                        <th class="text-left py-3 px-4 text-sm font-medium text-gray-700">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="item in filteredItems" :key="item.id">
                        <tr class="border-b border-white/5 hover:bg-white/5 transition-all duration-200 cursor-pointer" @click="selectItem(item)">
                            <td class="py-3 px-4">
                                <span class="text-sm font-medium text-gray-900" x-text="item.id"></span>
                            </td>
                            <td class="py-3 px-4 text-sm text-gray-700" x-text="item.name"></td>
                            <td class="py-3 px-4 text-sm text-gray-700" x-text="item.category"></td>
                            <td class="py-3 px-4 text-sm text-gray-700" x-text="item.sku"></td>
                            <td class="py-3 px-4">
                                <div class="flex items-center space-x-2">
                                    <span class="text-sm font-medium text-gray-900" x-text="item.quantity"></span>
                                    <span class="text-xs text-gray-500" x-text="'/' + item.minStock"></span>
                                </div>
                            </td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-1 text-xs font-medium rounded-full"
                                      :class="item.status === 'IN_STOCK' ? 'bg-green-100/80 text-green-700 border border-green-200/50' : 
                                                  item.status === 'LOW_STOCK' ? 'bg-yellow-100/80 text-yellow-700 border border-yellow-200/50' : 
                                                  'bg-red-100/80 text-red-700 border border-red-200/50'"
                                      x-text="item.status.replace('_', ' ')"></span>
                            </td>
                            <td class="py-3 px-4 text-sm text-gray-700" x-text="'$' + item.unitPrice.toFixed(2)"></td>
                            <td class="py-3 px-4 text-sm text-gray-700" x-text="'$' + item.totalValue.toFixed(2)"></td>
                            <td class="py-3 px-4 text-sm text-gray-700" x-text="item.location"></td>
                            <td class="py-3 px-4">
                                <div class="relative" x-data="{ dropdownOpen: false }">
                                    <button @click="dropdownOpen = !dropdownOpen" class="p-1 rounded-lg hover:bg-white/10 transition-colors">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path>
                                        </svg>
                                    </button>
                                    <div x-show="dropdownOpen" @click.away="dropdownOpen = false" x-transition class="absolute right-0 mt-1 w-48 glass-card z-50">
                                        <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-white/50">View Details</a>
                                        <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-white/50">Edit Item</a>
                                        <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-white/50">Update Stock</a>
                                        <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-white/50">Order More</a>
                                        <hr class="border-white/20 my-1">
                                        <a href="#" class="block px-4 py-2 text-sm text-red-600 hover:bg-white/50">Delete Item</a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <div class="flex items-center justify-between mt-6 pt-4 border-t border-white/10">
            <div class="flex items-center space-x-2">
                <span class="text-sm text-gray-600">Rows per page:</span>
                <select class="px-3 py-1 glass-input rounded-lg text-sm text-gray-900">
                    <option>10</option>
                    <option>25</option>
                    <option>50</option>
                    <option>100</option>
                </select>
            </div>
            <div class="flex items-center space-x-2">
                <button class="p-2 rounded-lg hover:bg-white/10 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </button>
                <button class="px-3 py-1 bg-white/20 backdrop-blur-sm border border-white/30 rounded-lg text-sm text-gray-900">1</button>
                <button class="px-3 py-1 rounded-lg text-sm text-gray-700 hover:bg-white/10">2</button>
                <button class="px-3 py-1 rounded-lg text-sm text-gray-700 hover:bg-white/10">3</button>
                <button class="p-2 rounded-lg hover:bg-white/10 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </button>
            </div>
        </div>
    </div>
    
    <!-- Inventory Analytics Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Stock Levels Chart -->
        <div class="glass-card rounded-2xl p-6 shadow-xl">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Stock Levels Trend</h3>
            <div class="relative h-48">
                <canvas id="stockLevelsChart"></canvas>
            </div>
        </div>
        
        <!-- Value by Category Chart -->
        <div class="glass-card rounded-2xl p-6 shadow-xl">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Value by Category</h3>
            <div class="relative h-48">
                <canvas id="valueByCategoryChart"></canvas>
            </div>
        </div>
        
        <!-- Monthly Usage Chart -->
        <div class="glass-card rounded-2xl p-6 shadow-xl">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Monthly Usage</h3>
            <div class="relative h-48">
                <canvas id="monthlyUsageChart"></canvas>
            </div>
        </div>
    </div>
    
</div>
@endsection

@push('scripts')
<script>
function inventoryManagement() {
    return {
        stats: @json($stats),
        items: @json($items),
        analytics: @json($analytics),
        selectedItem: null,
        searchQuery: '',
        showFilters: false,
        
        get filteredItems() {
            if (!this.searchQuery) return this.items;
            
            return this.items.filter(item => 
                item.name.toLowerCase().includes(this.searchQuery.toLowerCase()) ||
                item.sku.toLowerCase().includes(this.searchQuery.toLowerCase()) ||
                item.category.toLowerCase().includes(this.searchQuery.toLowerCase())
            );
        },
        
        init() {
            this.animateNumbers();
            this.initCharts();
        },
        
        formatNumber(num) {
            return num.toLocaleString();
        },
        
        animateNumbers() {
            const animateValue = (element, start, end, duration) => {
                const startTimestamp = Date.now();
                const step = () => {
                    const timestamp = Date.now();
                    const progress = Math.min((timestamp - startTimestamp) / duration, 1);
                    const value = Math.floor(progress * (end - start) + start);
                    element.textContent = this.formatNumber(value);
                    if (progress < 1) {
                        requestAnimationFrame(step);
                    }
                };
                requestAnimationFrame(step);
            };
            
            setTimeout(() => {
                const elements = document.querySelectorAll('[x-text*="formatNumber"]');
                animateValue(elements[0], 0, this.stats.totalItems, 1500);
                animateValue(elements[1], 0, this.stats.lowStock, 1200);
                animateValue(elements[2], 0, this.stats.outOfStock, 1000);
            }, 500);
        },
        
        selectItem(item) {
            this.selectedItem = item;
            console.log('Selected item:', item);
        },
        
        initCharts() {
            // Stock Levels Chart
            const stockCtx = document.getElementById('stockLevelsChart').getContext('2d');
            const stockGradient = stockCtx.createLinearGradient(0, 0, 0, 200);
            stockGradient.addColorStop(0, 'rgba(59, 130, 246, 0.4)');
            stockGradient.addColorStop(1, 'rgba(59, 130, 246, 0.1)');
            
            new Chart(stockCtx, {
                type: 'line',
                data: {
                    labels: this.analytics.stockLevels.labels,
                    datasets: [{
                        label: 'Stock Count',
                        data: this.analytics.stockLevels.data,
                        borderColor: 'rgba(59, 130, 246, 1)',
                        backgroundColor: stockGradient,
                        borderWidth: 2,
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: false,
                            grid: { color: 'rgba(255, 255, 255, 0.1)' },
                            ticks: { color: '#6B7280' }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { color: '#6B7280' }
                        }
                    }
                }
            });
            
            // Value by Category Chart
            const categoryCtx = document.getElementById('valueByCategoryChart').getContext('2d');
            const categoryGradient = categoryCtx.createLinearGradient(0, 0, 0, 200);
            categoryGradient.addColorStop(0, 'rgba(34, 197, 94, 0.4)');
            categoryGradient.addColorStop(1, 'rgba(34, 197, 94, 0.1)');
            
            new Chart(categoryCtx, {
                type: 'doughnut',
                data: {
                    labels: this.analytics.valueByCategory.labels,
                    datasets: [{
                        data: this.analytics.valueByCategory.data,
                        backgroundColor: [
                            'rgba(59, 130, 246, 0.8)',
                            'rgba(34, 197, 94, 0.8)',
                            'rgba(245, 158, 11, 0.8)',
                            'rgba(239, 68, 68, 0.8)',
                            'rgba(139, 92, 246, 0.8)',
                            'rgba(107, 114, 128, 0.8)'
                        ],
                        borderWidth: 2,
                        borderColor: 'rgba(255, 255, 255, 0.2)'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                color: '#6B7280',
                                padding: 15,
                                font: { size: 11 }
                            }
                        }
                    }
                }
            });
            
            // Monthly Usage Chart
            const usageCtx = document.getElementById('monthlyUsageChart').getContext('2d');
            const usageGradient = usageCtx.createLinearGradient(0, 0, 0, 200);
            usageGradient.addColorStop(0, 'rgba(245, 158, 11, 0.4)');
            usageGradient.addColorStop(1, 'rgba(245, 158, 11, 0.1)');
            
            new Chart(usageCtx, {
                type: 'bar',
                data: {
                    labels: this.analytics.monthlyUsage.labels,
                    datasets: [{
                        label: 'Usage Value ($)',
                        data: this.analytics.monthlyUsage.data,
                        backgroundColor: usageGradient,
                        borderColor: 'rgba(245, 158, 11, 1)',
                        borderWidth: 2,
                        borderRadius: 8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: 'rgba(255, 255, 255, 0.1)' },
                            ticks: { 
                                color: '#6B7280',
                                callback: value => '$' + value.toLocaleString()
                            }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { color: '#6B7280' }
                        }
                    }
                }
            });
        }
    }
}

function exportInventory() {
    // In a real application, this would trigger a download
    alert('Export functionality would download a CSV file with all inventory data');
}
</script>
@endpush
