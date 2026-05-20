@extends('layouts.app')

@section('title', 'Operations Overview')

@section('content')
<div x-data="dashboard()" x-init="init()">
    
    <!-- Dashboard Header -->
    <div class="mb-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Operations Overview</h1>
                <p class="text-gray-600">Real-time status of industrial assets and maintenance cycles.</p>
            </div>
            <div class="flex items-center space-x-3 mt-4 sm:mt-0">
                <!-- Date Range Dropdown -->
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open" class="px-4 py-2 bg-white/20 backdrop-blur-sm border border-white/30 rounded-xl text-sm font-medium text-gray-900 hover:bg-white/30 transition-all duration-200 flex items-center">
                        <span>Last {{ $period }} Days</span>
                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <div x-show="open" @click.away="open = false" x-transition class="absolute right-0 mt-2 w-48 bg-white/90 backdrop-blur-xl border border-white/30 rounded-xl shadow-xl z-50">
                        <a href="{{ route('dashboard', ['period' => '7']) }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-white/50">Last 7 Days</a>
                        <a href="{{ route('dashboard', ['period' => '30']) }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-white/50">Last 30 Days</a>
                        <a href="{{ route('dashboard', ['period' => '90']) }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-white/50">Last 90 Days</a>
                        <a href="{{ route('analytics') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-white/50">Custom Range</a>
                    </div>
                </div>
                
                <!-- Export Button -->
                <form method="POST" action="{{ route('dashboard.export') }}">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-xl font-medium shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all duration-200">
                        <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        Export Report
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Total Assets Card -->
        <a href="{{ route('asset-registry') }}" class="bg-white/10 backdrop-blur-xl border border-white/20 rounded-2xl p-6 shadow-xl hover:shadow-2xl transform hover:-translate-y-1 transition-all duration-300 cursor-pointer">
            <div class="flex items-center justify-between mb-4">
                <div class="p-3 bg-blue-500/20 rounded-xl">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                    </svg>
                </div>
                <span class="text-xs {{ $stats['trends']['totalAssets']['color'] }} font-medium">{{ $stats['trends']['totalAssets']['label'] }}</span>
            </div>
            <div class="space-y-1">
                <p class="text-sm text-gray-600 font-medium">Total Assets</p>
                <p class="text-3xl font-bold text-gray-900" x-text="formatNumber(kpis.totalAssets)">1,284</p>
            </div>
            <div class="mt-4">
                <div class="w-full bg-white/20 rounded-full h-2">
                    <div class="bg-gradient-to-r from-blue-500 to-blue-600 h-2 rounded-full" style="width: {{ $stats['bars']['totalAssets'] }}%"></div>
                </div>
            </div>
        </a>
        
        <!-- Critical Alerts Card -->
        <a href="{{ route('dashboard') }}" class="bg-white/10 backdrop-blur-xl border border-white/20 rounded-2xl p-6 shadow-xl hover:shadow-2xl transform hover:-translate-y-1 transition-all duration-300 cursor-pointer">
            <div class="flex items-center justify-between mb-4">
                <div class="p-3 bg-red-500/20 rounded-xl">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 15.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                </div>
                <span class="text-xs {{ $stats['trends']['criticalAlerts']['color'] }} font-medium">{{ $stats['trends']['criticalAlerts']['label'] }}</span>
            </div>
            <div class="space-y-1">
                <p class="text-sm text-gray-600 font-medium">Critical Alerts</p>
                <p class="text-3xl font-bold text-gray-900" x-text="kpis.criticalAlerts">12</p>
            </div>
            <div class="mt-4">
                <div class="w-full bg-white/20 rounded-full h-2">
                    <div class="bg-gradient-to-r from-red-500 to-red-600 h-2 rounded-full" style="width: {{ $stats['bars']['criticalAlerts'] }}%"></div>
                </div>
            </div>
        </a>
        
        <!-- Active Work Orders Card -->
        <a href="{{ route('maintenance') }}" class="bg-white/10 backdrop-blur-xl border border-white/20 rounded-2xl p-6 shadow-xl hover:shadow-2xl transform hover:-translate-y-1 transition-all duration-300 cursor-pointer">
            <div class="flex items-center justify-between mb-4">
                <div class="p-3 bg-green-500/20 rounded-xl">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                    </svg>
                </div>
                <span class="text-xs {{ $stats['trends']['activeWorkOrders']['color'] }} font-medium">{{ $stats['trends']['activeWorkOrders']['label'] }}</span>
            </div>
            <div class="space-y-1">
                <p class="text-sm text-gray-600 font-medium">Active Work Orders</p>
                <p class="text-3xl font-bold text-gray-900" x-text="kpis.activeWorkOrders">48</p>
            </div>
            <div class="mt-4">
                <div class="w-full bg-white/20 rounded-full h-2">
                    <div class="bg-gradient-to-r from-green-500 to-green-600 h-2 rounded-full" style="width: {{ $stats['bars']['activeWorkOrders'] }}%"></div>
                </div>
            </div>
        </a>
        
        <!-- Low Stock SKUs Card -->
        <a href="{{ route('inventory') }}" class="bg-white/10 backdrop-blur-xl border border-white/20 rounded-2xl p-6 shadow-xl hover:shadow-2xl transform hover:-translate-y-1 transition-all duration-300 cursor-pointer">
            <div class="flex items-center justify-between mb-4">
                <div class="p-3 bg-yellow-500/20 rounded-xl">
                    <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                    </svg>
                </div>
                <span class="text-xs {{ $stats['trends']['lowStockSkus']['color'] }} font-medium">{{ $stats['trends']['lowStockSkus']['label'] }}</span>
            </div>
            <div class="space-y-1">
                <p class="text-sm text-gray-600 font-medium">Low Stock SKUs</p>
                <p class="text-3xl font-bold text-gray-900" x-text="kpis.lowStockSkus">26</p>
            </div>
            <div class="mt-4">
                <div class="w-full bg-white/20 rounded-full h-2">
                    <div class="bg-gradient-to-r from-yellow-500 to-yellow-600 h-2 rounded-full" style="width: {{ $stats['bars']['lowStockSkus'] }}%"></div>
                </div>
            </div>
        </a>
    </div>
    
    <!-- Chart Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <!-- Asset Utilization Trends -->
        <div class="lg:col-span-2 bg-white/10 backdrop-blur-xl border border-white/20 rounded-2xl p-6 shadow-xl">
            <div class="mb-6">
                <h2 class="text-xl font-bold text-gray-900 mb-2">Asset Utilization Trends</h2>
                <p class="text-sm text-gray-600">Aggregate uptime across all facility lines</p>
            </div>
            <div class="relative h-80">
                <canvas id="utilizationChart"></canvas>
            </div>
        </div>
        
        <!-- Recent Activity -->
        <div class="bg-white/10 backdrop-blur-xl border border-white/20 rounded-2xl p-6 shadow-xl">
            <h2 class="text-xl font-bold text-gray-900 mb-4">Recent Activity</h2>
            <div class="space-y-4">
                @forelse($stats['recentActivity'] as $activity)
                @php
                    $iconPath = match($activity['color']) {
                        'green'  => 'M5 13l4 4L19 7',
                        'yellow' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 15.5c-.77.833.192 2.5 1.732 2.5z',
                        'red'    => 'M6 18L18 6M6 6l12 12',
                        default  => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
                    };
                    $bgClass   = 'bg-' . $activity['color'] . '-500/20';
                    $textClass = 'text-' . $activity['color'] . '-600';
                @endphp
                <div class="flex items-start space-x-3">
                    <div class="p-2 {{ $bgClass }} rounded-full flex-shrink-0">
                        <svg class="w-4 h-4 {{ $textClass }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $iconPath }}"></path>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900">{{ $activity['title'] }}</p>
                        <p class="text-xs text-gray-600 truncate">{{ $activity['description'] }}</p>
                        <p class="text-xs text-gray-500 mt-1">{{ $activity['time'] }}</p>
                    </div>
                </div>
                @empty
                <div class="text-center py-6">
                    <svg class="w-10 h-10 text-gray-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                    <p class="text-sm text-gray-500">No recent work order activity</p>
                </div>
                @endforelse
            </div>
            <a href="{{ route('maintenance') }}" class="block mt-6 text-center text-sm font-medium text-blue-600 hover:text-blue-700">View All Activity</a>
        </div>
    </div>
    
    <!-- High Priority Maintenance Table -->
    <div class="bg-white/10 backdrop-blur-xl border border-white/20 rounded-2xl p-6 shadow-xl">
        <div class="mb-6">
            <h2 class="text-xl font-bold text-gray-900 mb-2">High-Priority Maintenance Tasks</h2>
            <p class="text-sm text-gray-600">Critical maintenance requiring immediate attention</p>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-white/10">
                        <th class="text-left py-3 px-4 text-sm font-medium text-gray-700">Asset ID</th>
                        <th class="text-left py-3 px-4 text-sm font-medium text-gray-700">Type</th>
                        <th class="text-left py-3 px-4 text-sm font-medium text-gray-700">Condition</th>
                        <th class="text-left py-3 px-4 text-sm font-medium text-gray-700">Due Date</th>
                        <th class="text-left py-3 px-4 text-sm font-medium text-gray-700">Status</th>
                        <th class="text-left py-3 px-4 text-sm font-medium text-gray-700">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($stats['highPriorityMaintenance'] as $task)
                    <tr class="border-b border-white/5 hover:bg-white/5 transition-colors">
                        <td class="py-3 px-4">
                            <div class="flex items-center space-x-2">
                                <div class="w-2 h-2 bg-{{ $task['dot_color'] }}-500 rounded-full"></div>
                                <span class="text-sm font-medium text-gray-900">{{ $task['asset_id'] }}</span>
                            </div>
                        </td>
                        <td class="py-3 px-4 text-sm text-gray-700">{{ $task['type'] }}</td>
                        <td class="py-3 px-4">
                            <div class="flex items-center space-x-2">
                                <div class="w-16 bg-white/20 rounded-full h-2">
                                    <div class="bg-{{ $task['dot_color'] }}-500 h-2 rounded-full" style="width: {{ $task['health'] }}%"></div>
                                </div>
                                <span class="text-xs text-gray-600">{{ $task['health'] }}%</span>
                            </div>
                        </td>
                        <td class="py-3 px-4 text-sm text-gray-700">{{ $task['due_date'] }}</td>
                        <td class="py-3 px-4">
                            <span class="px-2 py-1 text-xs font-medium rounded-full border {{ $task['badge_class'] }}">{{ $task['status'] }}</span>
                        </td>
                        <td class="py-3 px-4">
                            <a href="{{ route('maintenance') }}"
                               class="px-3 py-1 text-xs font-medium bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">Inspect</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-10 text-center text-sm text-gray-500">
                            No high-priority work orders at this time.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    
</div>
@endsection

@push('scripts')
<script>
function dashboard() {
    return {
        kpis: {
            totalAssets: {{ $stats['totalAssets'] ?? 0 }},
            criticalAlerts: {{ $stats['criticalAlerts'] ?? 0 }},
            activeWorkOrders: {{ $stats['activeWorkOrders'] ?? 0 }},
            lowStockSkus: {{ $stats['lowStockSkus'] ?? 0 }}
        },
        
        init() {
            this.animateNumbers();
            this.initChart();
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
            
            // Animate KPI numbers
            setTimeout(() => {
                animateValue(document.querySelector('[x-text*="totalAssets"]'), 0, this.kpis.totalAssets, 2000);
                animateValue(document.querySelector('[x-text*="criticalAlerts"]'), 0, this.kpis.criticalAlerts, 1500);
                animateValue(document.querySelector('[x-text*="activeWorkOrders"]'), 0, this.kpis.activeWorkOrders, 1800);
                animateValue(document.querySelector('[x-text*="lowStockSkus"]'), 0, this.kpis.lowStockSkus, 1600);
            }, 500);
        },
        
        initChart() {
            const ctx = document.getElementById('utilizationChart').getContext('2d');
            
            const gradient1 = ctx.createLinearGradient(0, 0, 0, 300);
            gradient1.addColorStop(0, 'rgba(59, 130, 246, 0.4)');
            gradient1.addColorStop(1, 'rgba(59, 130, 246, 0.1)');
            
            const gradient2 = ctx.createLinearGradient(0, 0, 0, 300);
            gradient2.addColorStop(0, 'rgba(147, 51, 234, 0.4)');
            gradient2.addColorStop(1, 'rgba(147, 51, 234, 0.1)');
            
            const utilization = @json($stats['assetUtilization']);

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: utilization.labels,
                    datasets: [{
                        label: 'Current',
                        data: utilization.current,
                        backgroundColor: gradient1,
                        borderColor: 'rgba(59, 130, 246, 1)',
                        borderWidth: 2,
                        borderRadius: 8,
                        barThickness: 40
                    }, {
                        label: 'Previous',
                        data: utilization.previous,
                        backgroundColor: gradient2,
                        borderColor: 'rgba(147, 51, 234, 1)',
                        borderWidth: 2,
                        borderRadius: 8,
                        barThickness: 40
                    }]
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
                            displayColors: true,
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': ' + context.parsed.y + '%';
                                }
                            }
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
                            max: 100,
                            grid: {
                                color: 'rgba(255, 255, 255, 0.1)',
                                borderColor: 'rgba(255, 255, 255, 0.1)'
                            },
                            ticks: {
                                color: '#6B7280',
                                font: {
                                    size: 11,
                                    family: 'Inter'
                                },
                                callback: function(value) {
                                    return value + '%';
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
</script>
@endpush
