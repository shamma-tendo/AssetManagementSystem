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
                <button @click="triggerExport()" class="px-4 py-2 bg-white/20 backdrop-blur-sm border border-white/30 rounded-xl text-sm font-medium text-gray-900 hover:bg-white/30 transition-all duration-200 flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Export CSV
                </button>
                
                <!-- New Item Button -->
                <button @click="showNewItemModal = true" class="px-4 py-2 bg-gradient-to-r from-gray-900 to-black text-white rounded-xl font-medium shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all duration-200 flex items-center">
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
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
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
                        @foreach($locations as $loc)
                            <option value="{{ $loc }}">{{ $loc }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Supplier</label>
                    <select class="w-full px-3 py-2 glass-input rounded-lg text-sm text-gray-900 placeholder-gray-500">
                        <option>All Suppliers</option>
                        @foreach($suppliers as $sup)
                            <option value="{{ $sup->id }}">{{ $sup->name }}</option>
                        @endforeach
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
                <span class="text-xs {{ $stats['trends']['totalItems']['color'] }} font-medium">{{ $stats['trends']['totalItems']['label'] }}</span>
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
                <span class="text-xs {{ $stats['trends']['lowStock']['color'] }} font-medium">{{ $stats['trends']['lowStock']['label'] }}</span>
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
                <span class="text-xs {{ $stats['trends']['outOfStock']['color'] }} font-medium">{{ $stats['trends']['outOfStock']['label'] }}</span>
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
                    <span class="text-xs {{ $stats['trends']['totalValue']['color'] }} font-medium">{{ $stats['trends']['totalValue']['label'] }}</span>
                </div>
                <div class="space-y-1">
                    <p class="text-sm text-gray-300 font-medium">Total Value</p>
                    <p class="text-3xl font-bold text-white" x-text="'UGX ' + formatNumber(stats.totalValue)">UGX 284,750</p>
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
                    <template x-for="item in paginatedItems" :key="item.id">
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
                            <td class="py-3 px-4 text-sm text-gray-700" x-text="'UGX ' + item.unitPrice.toFixed(2)"></td>
                            <td class="py-3 px-4 text-sm text-gray-700" x-text="'UGX ' + item.totalValue.toFixed(2)"></td>
                            <td class="py-3 px-4 text-sm text-gray-700" x-text="item.location"></td>
                            <td class="py-3 px-4">
                                <div class="relative" x-data="{ dropdownOpen: false }">
                                    <button @click="dropdownOpen = !dropdownOpen" class="p-1 rounded-lg hover:bg-white/10 transition-colors">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path>
                                        </svg>
                                    </button>
                                    <div x-show="dropdownOpen" @click.away="dropdownOpen = false" x-transition class="absolute right-0 mt-1 w-48 glass-card z-50">
                                        <a href="#" @click.prevent="openDetailsModal(item); dropdownOpen = false" class="block px-4 py-2 text-sm text-gray-700 hover:bg-white/50">View Details</a>
                                        <a href="#" @click.prevent="openEditModal(item); dropdownOpen = false" class="block px-4 py-2 text-sm text-gray-700 hover:bg-white/50">Edit Item</a>
                                        <a href="#" @click.prevent="openStockModal(item); dropdownOpen = false" class="block px-4 py-2 text-sm text-gray-700 hover:bg-white/50">Update Stock</a>
                                        <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-white/50">Order More</a>
                                        <hr class="border-white/20 my-1">
                                        <a href="#" @click.prevent="deleteItem(item); dropdownOpen = false" class="block px-4 py-2 text-sm text-red-600 hover:bg-white/50">Delete Item</a>
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
            <div class="flex items-center space-x-4">
                <div class="flex items-center space-x-2">
                    <span class="text-sm text-gray-600">Rows per page:</span>
                    <select x-model="rowsPerPage" class="px-3 py-1 glass-input rounded-lg text-sm text-gray-900">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>
                <span class="text-sm text-gray-500"
                      x-text="filteredItems.length === 0 ? 'No results' : 'Showing ' + (Math.min((currentPage - 1) * parseInt(rowsPerPage) + 1, filteredItems.length)) + '\u2013' + Math.min(currentPage * parseInt(rowsPerPage), filteredItems.length) + ' of ' + filteredItems.length">
                </span>
            </div>
            <div class="flex items-center space-x-2">
                <button @click="goToPage(currentPage - 1)" :disabled="currentPage === 1"
                        class="p-2 rounded-lg hover:bg-white/10 transition-colors disabled:opacity-40 disabled:cursor-not-allowed">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </button>
                <template x-for="page in pageNumbers" :key="page">
                    <button @click="goToPage(page)"
                            class="px-3 py-1 rounded-lg text-sm transition-colors"
                            :class="page === currentPage ? 'bg-white/20 backdrop-blur-sm border border-white/30 text-gray-900 font-medium' : 'text-gray-700 hover:bg-white/10'"
                            x-text="page">
                    </button>
                </template>
                <button @click="goToPage(currentPage + 1)" :disabled="currentPage >= totalPages"
                        class="p-2 rounded-lg hover:bg-white/10 transition-colors disabled:opacity-40 disabled:cursor-not-allowed">
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
    
    <!-- Update Stock Modal -->
    <div x-show="showStockModal" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center p-4" style="background:rgba(0,0,0,0.55)" x-cloak>
        <div x-show="showStockModal" x-transition class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6" @click.away="showStockModal = false">
            <div class="flex items-center justify-between mb-5">
                <h3 class="text-lg font-bold text-gray-900">Update Stock</h3>
                <button @click="showStockModal = false" class="p-1 rounded-lg hover:bg-gray-100">
                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <template x-if="stockItem">
                <div>
                    <div class="bg-gray-50 rounded-xl p-4 mb-5">
                        <p class="text-sm font-semibold text-gray-900" x-text="stockItem.name"></p>
                        <p class="text-xs text-gray-500 mt-1">SKU: <span x-text="stockItem.sku"></span> &bull; Current stock: <span class="font-medium" x-text="stockItem.quantity + ' units'"></span></p>
                    </div>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Transaction Type</label>
                            <select x-model="stockForm.transaction_type" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm text-gray-900 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="receive">Receive Stock (add)</option>
                                <option value="purchase">Purchase (add + update cost)</option>
                                <option value="return">Return (add)</option>
                                <option value="adjustment">Stock Adjustment (+/&minus;)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Quantity <span class="text-xs text-gray-400">(negative to reduce)</span></label>
                            <input type="number" x-model="stockForm.quantity" step="1"
                                   class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   placeholder="e.g. 50">
                        </div>
                        <div x-show="['receive','purchase'].includes(stockForm.transaction_type)">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Unit Cost (optional)</label>
                            <input type="number" x-model="stockForm.unit_cost" step="0.01" min="0"
                                   class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   placeholder="e.g. 45.00">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Notes (optional)</label>
                            <textarea x-model="stockForm.notes" rows="2"
                                      class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"
                                      placeholder="e.g. Received from supplier XYZ"></textarea>
                        </div>
                    </div>
                    <div x-show="stockError" class="mt-3 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700" x-text="stockError"></div>
                    <div x-show="stockSuccess" class="mt-3 p-3 bg-green-50 border border-green-200 rounded-lg text-sm text-green-700" x-text="stockSuccess"></div>
                    <div class="flex items-center justify-end space-x-3 mt-5">
                        <button @click="showStockModal = false" class="px-4 py-2 text-sm text-gray-700 border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors">Cancel</button>
                        <button @click="submitStockUpdate()" :disabled="stockLoading"
                                class="px-5 py-2 bg-gradient-to-r from-gray-900 to-black text-white text-sm font-medium rounded-xl hover:opacity-90 transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center">
                            <svg x-show="stockLoading" class="w-4 h-4 mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            <span x-text="stockLoading ? 'Saving...' : 'Update Stock'"></span>
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- ── NEW ITEM MODAL ────────────────────────────────────────────── -->
    <div x-show="showNewItemModal" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display:none;">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="showNewItemModal = false"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto" @click.stop>
            <div class="flex items-center justify-between p-6 border-b border-gray-100">
                <div>
                    <h3 class="text-lg font-bold text-gray-900" x-text="isEditingItem ? 'Edit Inventory Item' : 'Add New Inventory Item'"></h3>
                    <p class="text-sm text-gray-500 mt-0.5" x-text="isEditingItem ? 'Update the inventory item details' : 'Create a new spare part or supply record'"></p>
                </div>
                <button @click="showNewItemModal = false" class="p-2 rounded-xl hover:bg-gray-100 transition-colors">
                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="p-6 space-y-5">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Item Name <span class="text-red-500">*</span></label>
                        <input type="text" x-model="newItemForm.name" placeholder="e.g. Hydraulic Filter #A200"
                               class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-gray-900">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Part Number / SKU</label>
                        <input type="text" x-model="newItemForm.part_number" placeholder="e.g. HYD-A200"
                               class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-gray-900">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Unit of Measure</label>
                        <input type="text" x-model="newItemForm.unit_of_measure" placeholder="e.g. units, litres, kg"
                               class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-gray-900">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                        <select x-model="newItemForm.category_id" class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-gray-900">
                            <option value="">— Select category —</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Supplier</label>
                        <select x-model="newItemForm.supplier_id" class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-gray-900">
                            <option value="">— Select supplier —</option>
                            @foreach($suppliers as $sup)
                                <option value="{{ $sup->id }}">{{ $sup->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Initial Stock Qty</label>
                        <input type="number" x-model="newItemForm.current_stock" min="0" placeholder="0"
                               class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-gray-900">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Minimum Stock</label>
                        <input type="number" x-model="newItemForm.minimum_stock" min="0" placeholder="0"
                               class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-gray-900">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Reorder Point</label>
                        <input type="number" x-model="newItemForm.reorder_point" min="0" placeholder="0"
                               class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-gray-900">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Unit Cost (UGX)</label>
                        <input type="number" x-model="newItemForm.unit_cost" min="0" step="0.01" placeholder="0.00"
                               class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-gray-900">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Storage Location</label>
                        <input type="text" x-model="newItemForm.storage_location" placeholder="e.g. Warehouse A, Shelf 3"
                               class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-gray-900">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea x-model="newItemForm.description" rows="2" placeholder="Optional description..."
                                  class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-gray-900 resize-none"></textarea>
                    </div>
                </div>
                <div x-show="newItemError" class="p-3 bg-red-50 border border-red-200 rounded-xl text-sm text-red-700" x-text="newItemError"></div>
                <div x-show="newItemSuccess" class="p-3 bg-green-50 border border-green-200 rounded-xl text-sm text-green-700" x-text="newItemSuccess"></div>
            </div>
            <div class="flex items-center justify-end space-x-3 px-6 pb-6">
                <button @click="showNewItemModal = false" class="px-4 py-2 text-sm text-gray-700 border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors">Cancel</button>
                <button @click="submitNewItem()" :disabled="newItemLoading"
                        class="px-5 py-2 bg-gradient-to-r from-gray-900 to-black text-white text-sm font-medium rounded-xl hover:opacity-90 transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center">
                    <svg x-show="newItemLoading" class="w-4 h-4 mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    <span x-text="newItemLoading ? (isEditingItem ? 'Saving...' : 'Saving...') : (isEditingItem ? 'Update Item' : 'Create Item')"></span>
                </button>
            </div>
        </div>
    </div>

    <!-- ── VIEW DETAILS MODAL ─────────────────────────────────────────── -->
    <div x-show="showDetailsModal" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display:none;">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="showDetailsModal = false"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto" @click.stop>
            <div class="flex items-center justify-between p-6 border-b border-gray-100">
                <div>
                    <h3 class="text-lg font-bold text-gray-900" x-text="detailsItem ? detailsItem.name : 'Item Details'"></h3>
                    <p class="text-sm text-gray-500 mt-0.5" x-text="detailsItem ? 'SKU: ' + detailsItem.sku : ''"></p>
                </div>
                <button @click="showDetailsModal = false" class="p-2 rounded-xl hover:bg-gray-100 transition-colors">
                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <template x-if="detailsLoading">
                <div class="flex items-center justify-center py-16">
                    <svg class="w-8 h-8 animate-spin text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                </div>
            </template>
            <template x-if="!detailsLoading && detailsItem">
                <div class="p-6 space-y-6">
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                        <div class="bg-gray-50 rounded-xl p-3">
                            <p class="text-xs text-gray-500 mb-1">Current Stock</p>
                            <p class="text-xl font-bold text-gray-900" x-text="detailsItem.quantity"></p>
                            <p class="text-xs text-gray-500" x-text="detailsItem.unitOfMeasure"></p>
                        </div>
                        <div class="bg-gray-50 rounded-xl p-3">
                            <p class="text-xs text-gray-500 mb-1">Unit Cost</p>
                            <p class="text-xl font-bold text-gray-900" x-text="'UGX ' + detailsItem.unitCost.toFixed(2)"></p>
                        </div>
                        <div class="bg-gray-50 rounded-xl p-3">
                            <p class="text-xs text-gray-500 mb-1">Total Value</p>
                            <p class="text-xl font-bold text-gray-900" x-text="'UGX ' + detailsItem.totalValue.toFixed(2)"></p>
                        </div>
                        <div class="bg-gray-50 rounded-xl p-3">
                            <p class="text-xs text-gray-500 mb-1">Min Stock</p>
                            <p class="text-lg font-semibold text-gray-900" x-text="detailsItem.minStock"></p>
                        </div>
                        <div class="bg-gray-50 rounded-xl p-3">
                            <p class="text-xs text-gray-500 mb-1">Reorder Point</p>
                            <p class="text-lg font-semibold text-gray-900" x-text="detailsItem.reorderPoint"></p>
                        </div>
                        <div class="bg-gray-50 rounded-xl p-3">
                            <p class="text-xs text-gray-500 mb-1">Lead Time</p>
                            <p class="text-lg font-semibold text-gray-900" x-text="detailsItem.leadTimeDays + ' days'"></p>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                        <div><span class="text-gray-500">Category:</span> <span class="font-medium text-gray-900 ml-1" x-text="detailsItem.category"></span></div>
                        <div><span class="text-gray-500">Supplier:</span> <span class="font-medium text-gray-900 ml-1" x-text="detailsItem.supplier"></span></div>
                        <div><span class="text-gray-500">Location:</span> <span class="font-medium text-gray-900 ml-1" x-text="detailsItem.location"></span></div>
                        <div><span class="text-gray-500">Last Updated:</span> <span class="font-medium text-gray-900 ml-1" x-text="detailsItem.lastRestocked"></span></div>
                        <div><span class="text-gray-500">Avg Cost:</span> <span class="font-medium text-gray-900 ml-1" x-text="'UGX ' + detailsItem.avgCost.toFixed(2)"></span></div>
                        <div>
                            <span class="text-gray-500">Status:</span>
                            <span class="ml-1 px-2 py-0.5 text-xs font-medium rounded-full"
                                  :class="detailsItem.status === 'IN_STOCK' ? 'bg-green-100 text-green-700' : detailsItem.status === 'LOW_STOCK' ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700'"
                                  x-text="detailsItem.status.replace('_',' ')"></span>
                        </div>
                    </div>
                    <template x-if="detailsItem.description">
                        <div>
                            <p class="text-xs font-medium text-gray-500 mb-1">Description</p>
                            <p class="text-sm text-gray-700" x-text="detailsItem.description"></p>
                        </div>
                    </template>
                    <div>
                        <p class="text-sm font-semibold text-gray-900 mb-3">Recent Transactions</p>
                        <template x-if="detailsItem.recentTransactions.length === 0">
                            <p class="text-sm text-gray-400 italic">No transactions recorded yet.</p>
                        </template>
                        <template x-if="detailsItem.recentTransactions.length > 0">
                            <div class="divide-y divide-gray-100 border border-gray-100 rounded-xl overflow-hidden">
                                <template x-for="tx in detailsItem.recentTransactions" :key="tx.date + tx.type">
                                    <div class="flex items-center justify-between px-4 py-2.5 bg-white hover:bg-gray-50">
                                        <div class="flex items-center space-x-3">
                                            <span class="px-2 py-0.5 text-xs font-medium rounded-full capitalize"
                                                  :class="tx.quantity >= 0 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
                                                  x-text="tx.type"></span>
                                            <span class="text-xs text-gray-500" x-text="tx.date"></span>
                                        </div>
                                        <span class="text-sm font-semibold"
                                              :class="tx.quantity >= 0 ? 'text-green-700' : 'text-red-600'"
                                              x-text="(tx.quantity >= 0 ? '+' : '') + tx.quantity"></span>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>
            </template>
            <div class="flex justify-end px-6 pb-6">
                <button @click="showDetailsModal = false" class="px-4 py-2 text-sm text-gray-700 border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors">Close</button>
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
        currentPage: 1,
        rowsPerPage: 10,
        showStockModal: false,
        stockItem: null,
        stockForm: { quantity: '', transaction_type: 'receive', unit_cost: '', notes: '' },
        stockLoading: false,
        stockError: '',
        stockSuccess: '',

        showNewItemModal: false,
        isEditingItem: false,
        editItemUuid: null,
        newItemForm: { name: '', part_number: '', category_id: '', supplier_id: '', unit_of_measure: '', current_stock: '', minimum_stock: '', reorder_point: '', unit_cost: '', storage_location: '', description: '' },
        newItemLoading: false,
        newItemError: '',
        newItemSuccess: '',

        showDetailsModal: false,
        detailsItem: null,
        detailsLoading: false,

        get filteredItems() {
            if (!this.searchQuery) return this.items;
            const q = this.searchQuery.toLowerCase();
            return this.items.filter(item =>
                item.name.toLowerCase().includes(q) ||
                item.sku.toLowerCase().includes(q) ||
                item.category.toLowerCase().includes(q)
            );
        },

        get paginatedItems() {
            const rpp  = parseInt(this.rowsPerPage);
            const start = (this.currentPage - 1) * rpp;
            return this.filteredItems.slice(start, start + rpp);
        },

        get totalPages() {
            return Math.max(1, Math.ceil(this.filteredItems.length / parseInt(this.rowsPerPage)));
        },

        get pageNumbers() {
            const total   = this.totalPages;
            const current = this.currentPage;
            const delta   = 2;
            const pages   = [];
            for (let i = Math.max(1, current - delta); i <= Math.min(total, current + delta); i++) {
                pages.push(i);
            }
            return pages;
        },

        goToPage(page) {
            if (page >= 1 && page <= this.totalPages) this.currentPage = page;
        },

        openStockModal(item) {
            this.stockItem = item;
            this.stockForm = { quantity: '', transaction_type: 'receive', unit_cost: '', notes: '' };
            this.stockError = '';
            this.stockSuccess = '';
            this.showStockModal = true;
        },

        async submitStockUpdate() {
            if (!this.stockForm.quantity || parseFloat(this.stockForm.quantity) === 0) {
                this.stockError = 'Please enter a non-zero quantity.';
                return;
            }
            this.stockLoading = true;
            this.stockError = '';
            this.stockSuccess = '';
            let res;
            try {
                res = await fetch(`/inventory/${this.stockItem.uuid}/stock`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        quantity: parseFloat(this.stockForm.quantity),
                        transaction_type: this.stockForm.transaction_type,
                        unit_cost: this.stockForm.unit_cost ? parseFloat(this.stockForm.unit_cost) : null,
                        notes: this.stockForm.notes || null,
                    }),
                });
            } catch (networkErr) {
                this.stockError = 'Network error — check your connection.';
                this.stockLoading = false;
                return;
            }

            let data;
            try {
                data = await res.json();
            } catch {
                this.stockError = `Server error (HTTP ${res.status}). Please refresh and try again.`;
                this.stockLoading = false;
                return;
            }

            try {
                if (data.success) {
                    const idx = this.items.findIndex(i => i.uuid === this.stockItem.uuid);
                    if (idx !== -1) {
                        this.items[idx].quantity   = data.new_quantity;
                        this.items[idx].status     = data.new_status;
                        this.items[idx].totalValue = parseFloat((data.new_quantity * this.items[idx].unitPrice).toFixed(2));
                    }
                    this.stats.totalItems  = this.items.length;
                    this.stats.lowStock    = this.items.filter(i => i.status === 'LOW_STOCK').length;
                    this.stats.outOfStock  = this.items.filter(i => i.status === 'OUT_OF_STOCK').length;
                    this.stats.totalValue  = parseFloat(this.items.reduce((s, i) => s + (i.totalValue || 0), 0).toFixed(2));
                    this.stockSuccess = 'Stock updated successfully!';
                    setTimeout(() => { this.showStockModal = false; }, 1200);
                } else {
                    this.stockError = data.message || 'Failed to update stock.';
                }
            } catch (e) {
                this.stockError = 'Unexpected client error: ' + e.message;
            } finally {
                this.stockLoading = false;
            }
        },

        init() {
            this.$watch('searchQuery',  () => { this.currentPage = 1; });
            this.$watch('rowsPerPage',  () => { this.currentPage = 1; });
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
                        label: 'Usage Value (UGX)',
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
                                callback: value => 'UGX ' + value.toLocaleString()
                            }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { color: '#6B7280' }
                        }
                    }
                }
            });
        },

        triggerExport() {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route('inventory.export') }}';
            const csrf = document.createElement('input');
            csrf.type = 'hidden'; csrf.name = '_token'; csrf.value = '{{ csrf_token() }}';
            form.appendChild(csrf);
            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
        },

        async openDetailsModal(item) {
            this.detailsItem = null;
            this.detailsLoading = true;
            this.showDetailsModal = true;
            try {
                const res = await fetch(`/inventory/${item.uuid}/details`, {
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                });
                const data = await res.json();
                if (data.success) {
                    this.detailsItem = data;
                } else {
                    this.showDetailsModal = false;
                    alert('Could not load item details.');
                }
            } catch (e) {
                this.showDetailsModal = false;
                alert('Network error loading details.');
            } finally {
                this.detailsLoading = false;
            }
        },

        openEditModal(item) {
            this.isEditingItem = true;
            this.editItemUuid = item.uuid;
            this.newItemForm = {
                name: item.name || '',
                part_number: item.sku || '',
                category_id: '',
                supplier_id: '',
                unit_of_measure: '',
                current_stock: '',
                minimum_stock: item.minStock || '',
                reorder_point: item.reorderLevel || '',
                unit_cost: item.unitPrice || '',
                storage_location: item.location || '',
                description: ''
            };
            this.showNewItemModal = true;
        },

        async deleteItem(item) {
            if (!confirm('Are you sure you want to delete this item? This action cannot be undone.')) return;
            try {
                const res = await fetch(`/inventory/${item.uuid}`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (data.success) {
                    this.items = this.items.filter(i => i.uuid !== item.uuid);
                    this.stats.totalItems = this.items.length;
                } else {
                    alert(data.message || 'Failed to delete item.');
                }
            } catch (e) {
                alert('Network error deleting item.');
            }
        },

        async submitNewItem() {
            if (!this.newItemForm.name.trim()) {
                this.newItemError = 'Item name is required.';
                return;
            }
            this.newItemLoading = true;
            this.newItemError = '';
            this.newItemSuccess = '';
            try {
                const payload = {};
                Object.entries(this.newItemForm).forEach(([k, v]) => { if (v !== '' && v !== null) payload[k] = v; });
                const url = this.isEditingItem ? `/inventory/${this.editItemUuid}` : '{{ route('inventory.create-item') }}';
                const method = this.isEditingItem ? 'PUT' : 'POST';
                const res = await fetch(url, {
                    method: method,
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify(payload),
                });
                const data = await res.json();
                if (data.success) {
                    if (this.isEditingItem) {
                        const idx = this.items.findIndex(i => i.uuid === this.editItemUuid);
                        if (idx !== -1) this.items[idx] = data.item;
                    } else {
                        this.items.unshift(data.item);
                    }
                    this.stats.totalItems = this.items.length;
                    this.stats.outOfStock = this.items.filter(i => i.status === 'OUT_OF_STOCK').length;
                    this.stats.lowStock   = this.items.filter(i => i.status === 'LOW_STOCK').length;
                    this.stats.totalValue = parseFloat(this.items.reduce((s, i) => s + (i.totalValue || 0), 0).toFixed(2));
                    this.newItemSuccess = (this.isEditingItem ? 'Item updated successfully!' : 'Item created successfully!');
                    this.newItemForm = { name: '', part_number: '', category_id: '', supplier_id: '', unit_of_measure: '', current_stock: '', minimum_stock: '', reorder_point: '', unit_cost: '', storage_location: '', description: '' };
                    this.isEditingItem = false;
                    this.editItemUuid = null;
                    setTimeout(() => { this.showNewItemModal = false; this.newItemSuccess = ''; }, 1500);
                } else {
                    this.newItemError = data.message || (this.isEditingItem ? 'Failed to update item.' : 'Failed to create item.');
                    if (data.errors) {
                        this.newItemError = Object.values(data.errors).flat().join(' ');
                    }
                }
            } catch (e) {
                this.newItemError = 'Network error: ' + e.message;
            } finally {
                this.newItemLoading = false;
            }
        },
    };
}
</script>
@endpush
