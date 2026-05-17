@extends('layouts.app')

@section('title', 'System Asset Registry')

@section('content')
<div x-data="assetRegistry()" x-init="init()">
    
    <!-- Page Header -->
    <div class="mb-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">System Asset Registry</h1>
                <p class="text-gray-600">Manage and track <span x-text="formatNumber(stats.totalAssets)">1,248</span> critical industrial units.</p>
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
                <button onclick="exportAssets()" class="px-4 py-2 bg-white/20 backdrop-blur-sm border border-white/30 rounded-xl text-sm font-medium text-gray-900 hover:bg-white/30 transition-all duration-200 flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Export CSV
                </button>
                
                <!-- New Asset Button -->
                <button @click="showNewAssetModal = true" class="px-4 py-2 bg-gradient-to-r from-gray-900 to-black text-white rounded-xl font-medium shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all duration-200 flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    + New Asset
                </button>
            </div>
        </div>
    </div>
    
    <!-- Filters Panel -->
    <div x-show="showFilters" x-transition class="mb-6">
        <div class="bg-white/10 backdrop-blur-xl border border-white/20 rounded-2xl p-6 shadow-xl">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                    <select class="w-full px-3 py-2 bg-white/20 backdrop-blur-sm border border-white/30 rounded-lg text-sm text-gray-900 placeholder-gray-500">
                        <option>All Categories</option>
                        <option>Material Handling</option>
                        <option>Automation</option>
                        <option>Fluid Systems</option>
                        <option>Power Systems</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                    <select class="w-full px-3 py-2 bg-white/20 backdrop-blur-sm border border-white/30 rounded-lg text-sm text-gray-900 placeholder-gray-500">
                        <option>All Status</option>
                        <option>ACTIVE</option>
                        <option>IN REPAIR</option>
                        <option>RETIRED</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Location</label>
                    <select class="w-full px-3 py-2 bg-white/20 backdrop-blur-sm border border-white/30 rounded-lg text-sm text-gray-900 placeholder-gray-500">
                        <option>All Locations</option>
                        <option>Factory Floor A</option>
                        <option>Assembly Line B</option>
                        <option>Pumping Station C</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Health Range</label>
                    <select class="w-full px-3 py-2 bg-white/20 backdrop-blur-sm border border-white/30 rounded-lg text-sm text-gray-900 placeholder-gray-500">
                        <option>All Health</option>
                        <option>Excellent (90-100%)</option>
                        <option>Good (70-89%)</option>
                        <option>Fair (50-69%)</option>
                        <option>Poor (<50%)</option>
                    </select>
                </div>
            </div>
        </div>
    </div>
    
    <!-- KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Total Assets Card -->
        <div class="bg-white/10 backdrop-blur-xl border border-white/20 rounded-2xl p-6 shadow-xl hover:shadow-2xl transform hover:-translate-y-1 transition-all duration-300 fade-in">
            <div class="flex items-center justify-between mb-4">
                <div class="p-3 bg-blue-500/20 rounded-xl">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                    </svg>
                </div>
                <span class="text-xs text-gray-500 font-medium">+2.3%</span>
            </div>
            <div class="space-y-1">
                <p class="text-sm text-gray-600 font-medium">Total Assets</p>
                <p class="text-3xl font-bold text-gray-900" x-text="formatNumber(stats.totalAssets)">1,248</p>
            </div>
            <div class="mt-4">
                <div class="w-full bg-white/20 rounded-full h-2">
                    <div class="bg-gradient-to-r from-blue-500 to-blue-600 h-2 rounded-full" style="width: 85%"></div>
                </div>
            </div>
        </div>
        
        <!-- Operational Assets Card -->
        <div class="bg-white/10 backdrop-blur-xl border border-white/20 rounded-2xl p-6 shadow-xl hover:shadow-2xl transform hover:-translate-y-1 transition-all duration-300 fade-in" style="animation-delay: 0.1s">
            <div class="flex items-center justify-between mb-4">
                <div class="p-3 bg-green-500/20 rounded-xl">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <span class="text-xs text-green-500 font-medium">+1.8%</span>
            </div>
            <div class="space-y-1">
                <p class="text-sm text-gray-600 font-medium">Operational</p>
                <p class="text-3xl font-bold text-gray-900" x-text="formatNumber(stats.operational)">1,182</p>
            </div>
            <div class="mt-4">
                <div class="w-full bg-white/20 rounded-full h-2">
                    <div class="bg-gradient-to-r from-green-500 to-green-600 h-2 rounded-full" style="width: 95%"></div>
                </div>
            </div>
        </div>
        
        <!-- In Repair Card -->
        <div class="bg-white/10 backdrop-blur-xl border border-white/20 rounded-2xl p-6 shadow-xl hover:shadow-2xl transform hover:-translate-y-1 transition-all duration-300 fade-in" style="animation-delay: 0.2s">
            <div class="flex items-center justify-between mb-4">
                <div class="p-3 bg-red-500/20 rounded-xl">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 15.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                </div>
                <span class="text-xs text-red-500 font-medium">-12%</span>
            </div>
            <div class="space-y-1">
                <p class="text-sm text-gray-600 font-medium">In Repair</p>
                <p class="text-3xl font-bold text-gray-900" x-text="formatNumber(stats.inRepair)">42</p>
            </div>
            <div class="mt-4">
                <div class="w-full bg-white/20 rounded-full h-2">
                    <div class="bg-gradient-to-r from-red-500 to-red-600 h-2 rounded-full" style="width: 25%"></div>
                </div>
            </div>
        </div>
        
        <!-- Uptime Average Card -->
        <div class="bg-white/10 backdrop-blur-xl border border-white/20 rounded-2xl p-6 shadow-xl hover:shadow-2xl transform hover:-translate-y-1 transition-all duration-300 fade-in" style="animation-delay: 0.3s">
            <div class="flex items-center justify-between mb-4">
                <div class="p-3 bg-purple-500/20 rounded-xl">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
                <span class="text-xs text-purple-500 font-medium">+0.3%</span>
            </div>
            <div class="space-y-1">
                <p class="text-sm text-gray-600 font-medium">Uptime Avg.</p>
                <p class="text-3xl font-bold text-gray-900" x-text="stats.uptimeAvg + '%'">99.2%</p>
            </div>
            <div class="mt-4">
                <div class="w-full bg-white/20 rounded-full h-2">
                    <div class="bg-gradient-to-r from-purple-500 to-purple-600 h-2 rounded-full" style="width: 99%"></div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 xl:grid-cols-4 gap-6">
        
        <!-- Asset Table Section -->
        <div class="xl:col-span-3">
            <div class="bg-white/10 backdrop-blur-xl border border-white/20 rounded-2xl p-6 shadow-xl">
                
                <!-- Tabs -->
                <div class="flex space-x-1 mb-6 border-b border-white/10">
                    <button @click="activeTab = 'all'" 
                            :class="activeTab === 'all' ? 'bg-white/20 border border-white/30 text-gray-900' : 'text-gray-600 hover:text-gray-900 hover:bg-white/10'" 
                            class="px-4 py-2 rounded-t-lg text-sm font-medium transition-all duration-200">
                        All Assets
                    </button>
                    <button @click="activeTab = 'critical'" 
                            :class="activeTab === 'critical' ? 'bg-white/20 border border-white/30 text-gray-900' : 'text-gray-600 hover:text-gray-900 hover:bg-white/10'" 
                            class="px-4 py-2 rounded-t-lg text-sm font-medium transition-all duration-200">
                        Critical
                    </button>
                    <button @click="activeTab = 'in-repair'" 
                            :class="activeTab === 'in-repair' ? 'bg-white/20 border border-white/30 text-gray-900' : 'text-gray-600 hover:text-gray-900 hover:bg-white/10'" 
                            class="px-4 py-2 rounded-t-lg text-sm font-medium transition-all duration-200">
                        In Repair
                    </button>
                    <button @click="activeTab = 'retired'" 
                            :class="activeTab === 'retired' ? 'bg-white/20 border border-white/30 text-gray-900' : 'text-gray-600 hover:text-gray-900 hover:bg-white/10'" 
                            class="px-4 py-2 rounded-t-lg text-sm font-medium transition-all duration-200">
                        Retired
                    </button>
                </div>
                
                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-white/10">
                                <th class="text-left py-3 px-4 text-sm font-medium text-gray-700">Asset ID</th>
                                <th class="text-left py-3 px-4 text-sm font-medium text-gray-700">Asset Name</th>
                                <th class="text-left py-3 px-4 text-sm font-medium text-gray-700">Category</th>
                                <th class="text-left py-3 px-4 text-sm font-medium text-gray-700">Location</th>
                                <th class="text-left py-3 px-4 text-sm font-medium text-gray-700">Health</th>
                                <th class="text-left py-3 px-4 text-sm font-medium text-gray-700">Status</th>
                                <th class="text-left py-3 px-4 text-sm font-medium text-gray-700">Last Maintenance</th>
                                <th class="text-left py-3 px-4 text-sm font-medium text-gray-700">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="asset in filteredAssets" :key="asset.id">
                                <tr class="border-b border-white/5 hover:bg-white/5 transition-all duration-200 cursor-pointer" @click="selectAsset(asset)">
                                    <td class="py-3 px-4">
                                        <div class="flex items-center space-x-2">
                                            <div class="w-2 h-2 rounded-full" 
                                                 :class="asset.health >= 90 ? 'bg-green-500' : asset.health >= 70 ? 'bg-yellow-500' : asset.health >= 50 ? 'bg-orange-500' : 'bg-red-500'"></div>
                                            <span class="text-sm font-medium text-gray-900" x-text="asset.id"></span>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4 text-sm text-gray-700" x-text="asset.name"></td>
                                    <td class="py-3 px-4 text-sm text-gray-700" x-text="asset.category"></td>
                                    <td class="py-3 px-4 text-sm text-gray-700" x-text="asset.location"></td>
                                    <td class="py-3 px-4">
                                        <div class="flex items-center space-x-2">
                                            <div class="w-20 bg-white/20 rounded-full h-2">
                                                <div class="h-2 rounded-full transition-all duration-300"
                                                     :class="asset.health >= 90 ? 'bg-green-500' : asset.health >= 70 ? 'bg-yellow-500' : asset.health >= 50 ? 'bg-orange-500' : 'bg-red-500'"
                                                     :style="`width: ${asset.health}%`"></div>
                                            </div>
                                            <span class="text-xs text-gray-600" x-text="asset.health + '%'"></span>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="px-2 py-1 text-xs font-medium rounded-full"
                                              :class="asset.status === 'ACTIVE' ? 'bg-green-100/80 text-green-700 border border-green-200/50' : 
                                                      asset.status === 'IN REPAIR' ? 'bg-red-100/80 text-red-700 border border-red-200/50' : 
                                                      'bg-gray-100/80 text-gray-700 border border-gray-200/50'"
                                              x-text="asset.status"></span>
                                    </td>
                                    <td class="py-3 px-4 text-sm text-gray-700" x-text="asset.lastMaintenance"></td>
                                    <td class="py-3 px-4">
                                        <div class="relative" x-data="{ dropdownOpen: false }">
                                            <button @click="dropdownOpen = !dropdownOpen" class="p-1 rounded-lg hover:bg-white/10 transition-colors">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path>
                                                </svg>
                                            </button>
                                            <div x-show="dropdownOpen" @click.away="dropdownOpen = false" x-transition class="absolute right-0 mt-1 w-48 bg-white/90 backdrop-blur-xl border border-white/30 rounded-xl shadow-xl z-50">
                                                <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-white/50">View Details</a>
                                                <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-white/50">Edit Asset</a>
                                                <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-white/50">Schedule Maintenance</a>
                                                <hr class="border-white/20 my-1">
                                                <a href="#" class="block px-4 py-2 text-sm text-red-600 hover:bg-white/50">Delete Asset</a>
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
                        <select class="px-3 py-1 bg-white/20 backdrop-blur-sm border border-white/30 rounded-lg text-sm text-gray-900">
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
        </div>
        
        <!-- Asset Detail Panel -->
        <div class="xl:col-span-1">
            <div class="bg-black/20 backdrop-blur-xl border border-white/20 rounded-2xl p-6 shadow-xl sticky top-6">
                <h2 class="text-xl font-bold text-gray-900 mb-6">Selected Asset Detail</h2>
                
                <div x-show="selectedAsset" x-transition>
                    <div class="space-y-4">
                        <!-- Asset ID -->
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Asset ID</p>
                            <p class="text-lg font-bold text-gray-900" x-text="selectedAsset?.id || 'CNV-9821-X'"></p>
                        </div>
                        
                        <!-- Separator -->
                        <div class="border-t border-white/10"></div>
                        
                        <!-- Manufacturer -->
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Manufacturer</p>
                            <p class="text-sm text-gray-900" x-text="selectedAsset?.manufacturer || 'TechConveyor Inc.'"></p>
                        </div>
                        
                        <!-- Installed Date -->
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Installed Date</p>
                            <p class="text-sm text-gray-900" x-text="selectedAsset?.installedDate || '2022-03-15'"></p>
                        </div>
                        
                        <!-- Warranty End -->
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Warranty End</p>
                            <p class="text-sm text-gray-900" x-text="selectedAsset?.warrantyEnd || '2025-03-15'"></p>
                        </div>
                        
                        <!-- Power Requirement -->
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Power Requirement</p>
                            <p class="text-sm text-gray-900" x-text="selectedAsset?.powerRequirement || '15kW'"></p>
                        </div>
                        
                        <!-- Separator -->
                        <div class="border-t border-white/10"></div>
                        
                        <!-- Health Indicator -->
                        <div>
                            <p class="text-sm text-gray-500 mb-2">Health Status</p>
                            <div class="flex items-center space-x-2">
                                <div class="flex-1 bg-white/20 rounded-full h-3">
                                    <div class="h-3 rounded-full transition-all duration-300"
                                         :class="selectedAsset?.health >= 90 ? 'bg-green-500' : selectedAsset?.health >= 70 ? 'bg-yellow-500' : selectedAsset?.health >= 50 ? 'bg-orange-500' : 'bg-red-500'"
                                         :style="`width: ${selectedAsset?.health || 92}%`"></div>
                                </div>
                                <span class="text-sm font-medium text-gray-900" x-text="(selectedAsset?.health || 92) + '%'"></span>
                            </div>
                        </div>
                        
                        <!-- Full Telemetry Button -->
                        <button class="w-full px-4 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-xl font-medium shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all duration-200 flex items-center justify-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                            Full Telemetry Data
                        </button>
                    </div>
                </div>
                
                <div x-show="!selectedAsset" class="text-center py-8">
                    <svg class="w-12 h-12 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                    <p class="text-sm text-gray-500">Select an asset to view details</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Maintenance History Chart -->
    <div class="mt-8 bg-white/10 backdrop-blur-xl border border-white/20 rounded-2xl p-6 shadow-xl">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-xl font-bold text-gray-900 mb-2">Maintenance History Trend</h2>
                <p class="text-sm text-gray-600">Monthly maintenance activities across all asset categories</p>
            </div>
            <div class="px-3 py-1 bg-green-100/80 text-green-700 border border-green-200/50 rounded-full text-xs font-medium">
                <span class="inline-block w-2 h-2 bg-green-500 rounded-full mr-2 pulse-glow"></span>
                Predictive Health Analysis Active
            </div>
        </div>
        <div class="relative h-80">
            <canvas id="maintenanceChart"></canvas>
        </div>
    </div>
    
    <!-- New Asset Modal -->
    <div x-show="showNewAssetModal" x-transition class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-black/50" @click="showNewAssetModal = false"></div>
        <div class="relative bg-white/95 backdrop-blur-xl border border-white/20 rounded-2xl shadow-2xl max-w-lg w-full p-6">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-bold text-gray-900">Add New Asset</h2>
                <button @click="showNewAssetModal = false" class="p-2 rounded-lg hover:bg-gray-100">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <form method="POST" action="{{ route('asset-registry.store') }}">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Asset Name</label>
                        <input type="text" name="name" required class="w-full px-3 py-2 bg-white/50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Serial Number</label>
                        <input type="text" name="serial_number" required class="w-full px-3 py-2 bg-white/50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                        <select name="category" required class="w-full px-3 py-2 bg-white/50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Select Category</option>
                            <option value="Material Handling">Material Handling</option>
                            <option value="Production Equipment">Production Equipment</option>
                            <option value="HVAC">HVAC</option>
                            <option value="Electrical">Electrical</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                        <select name="status" required class="w-full px-3 py-2 bg-white/50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="active">Active</option>
                            <option value="under_maintenance">Under Maintenance</option>
                            <option value="retired">Retired</option>
                        </select>
                    </div>
                    <div class="flex space-x-3 pt-4">
                        <button type="button" @click="showNewAssetModal = false" class="flex-1 px-4 py-2 bg-gray-200 text-gray-700 rounded-lg font-medium hover:bg-gray-300 transition-all">
                            Cancel
                        </button>
                        <button type="submit" class="flex-1 px-4 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-lg font-medium hover:shadow-lg transition-all">
                            Create Asset
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function assetRegistry() {
    return {
        stats: @json($stats),
        assets: @json($assets),
        maintenanceHistory: @json($maintenanceHistory),
        selectedAsset: null,
        activeTab: 'all',
        showFilters: false,
        showNewAssetModal: false,
        
        get filteredAssets() {
            if (this.activeTab === 'all') return this.assets;
            if (this.activeTab === 'critical') return this.assets.filter(a => a.health < 50);
            if (this.activeTab === 'in-repair') return this.assets.filter(a => a.status === 'IN REPAIR');
            if (this.activeTab === 'retired') return this.assets.filter(a => a.status === 'RETIRED');
            return this.assets;
        },
        
        init() {
            this.animateNumbers();
            this.initMaintenanceChart();
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
                // Animate the numbers
                animateValue(elements[0], 0, this.stats.totalAssets, 2000);
                animateValue(elements[1], 0, this.stats.operational, 1800);
                animateValue(elements[2], 0, this.stats.inRepair, 1600);
            }, 500);
        },
        
        selectAsset(asset) {
            this.selectedAsset = asset;
        },
        
        initMaintenanceChart() {
            const ctx = document.getElementById('maintenanceChart').getContext('2d');
            
            const gradient1 = ctx.createLinearGradient(0, 0, 0, 300);
            gradient1.addColorStop(0, 'rgba(34, 197, 94, 0.4)');
            gradient1.addColorStop(1, 'rgba(34, 197, 94, 0.1)');
            
            const gradient2 = ctx.createLinearGradient(0, 0, 0, 300);
            gradient2.addColorStop(0, 'rgba(239, 68, 68, 0.4)');
            gradient2.addColorStop(1, 'rgba(239, 68, 68, 0.1)');
            
            const gradient3 = ctx.createLinearGradient(0, 0, 0, 300);
            gradient3.addColorStop(0, 'rgba(59, 130, 246, 0.4)');
            gradient3.addColorStop(1, 'rgba(59, 130, 246, 0.1)');
            
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: this.maintenanceHistory.labels,
                    datasets: [
                        {
                            label: 'Preventive',
                            data: this.maintenanceHistory.preventive,
                            backgroundColor: gradient1,
                            borderColor: 'rgba(34, 197, 94, 1)',
                            borderWidth: 2,
                            borderRadius: 8,
                            barThickness: 30
                        },
                        {
                            label: 'Corrective',
                            data: this.maintenanceHistory.corrective,
                            backgroundColor: gradient2,
                            borderColor: 'rgba(239, 68, 68, 1)',
                            borderWidth: 2,
                            borderRadius: 8,
                            barThickness: 30
                        },
                        {
                            label: 'Predictive',
                            data: this.maintenanceHistory.predictive,
                            backgroundColor: gradient3,
                            borderColor: 'rgba(59, 130, 246, 1)',
                            borderWidth: 2,
                            borderRadius: 8,
                            barThickness: 30
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                            labels: {
                                usePointStyle: true,
                                padding: 20,
                                font: {
                                    size: 12,
                                    family: 'Inter'
                                },
                                color: '#6B7280'
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(255, 255, 255, 0.9)',
                            backdropFilter: 'blur(10px)',
                            borderColor: 'rgba(255, 255, 255, 0.2)',
                            borderWidth: 1,
                            titleColor: '#111827',
                            bodyColor: '#6B7280',
                            padding: 12,
                            cornerRadius: 8,
                            displayColors: true
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                color: '#6B7280',
                                font: {
                                    size: 11,
                                    family: 'Inter'
                                }
                            }
                        },
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(255, 255, 255, 0.1)',
                                borderColor: 'rgba(255, 255, 255, 0.1)'
                            },
                            ticks: {
                                color: '#6B7280',
                                font: {
                                    size: 11,
                                    family: 'Inter'
                                }
                            }
                        }
                    },
                    animation: {
                        duration: 2000,
                        easing: 'easeInOutQuart'
                    }
                }
            });
        }
    }
}

function exportAssets() {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/asset-registry/export';
    form.innerHTML = '<input type="hidden" name="_token" value="{{ csrf_token() }}"><input type="hidden" name="format" value="csv">';
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}
</script>
@endpush
