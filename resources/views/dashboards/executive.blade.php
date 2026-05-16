@extends('layout')

@section('title', 'Executive Dashboard')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100">
    <!-- Header -->
    <div class="bg-white border-b border-gray-200 sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Executive Dashboard</h1>
                    <p class="text-gray-500 mt-1">{{ $organization->getIndustryTypeLabel() }}</p>
                </div>
                <div class="text-right">
                    <p class="text-sm text-gray-500">{{ auth()->user()->name }}</p>
                    <p class="text-xs text-gray-400">CEO / CFO</p>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <!-- Key Metrics Row -->
        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
            <!-- Total Assets Card -->
            <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-blue-500">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-gray-500 text-sm font-medium">Total Assets</p>
                        <p class="text-3xl font-bold text-gray-900 mt-2">{{ $assetStats['total'] }}</p>
                        <p class="text-xs text-gray-400 mt-2">Organization-wide</p>
                    </div>
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Active Assets Card -->
            <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-green-500">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-gray-500 text-sm font-medium">Active Assets</p>
                        <p class="text-3xl font-bold text-green-600 mt-2">{{ $assetStats['active'] }}</p>
                        <p class="text-xs text-gray-400 mt-2">In use</p>
                    </div>
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Pending Requests Card -->
            <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-yellow-500">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-gray-500 text-sm font-medium">Pending Approvals</p>
                        <p class="text-3xl font-bold text-yellow-600 mt-2">{{ $requestStats['pending'] ?? 0 }}</p>
                        <p class="text-xs text-gray-400 mt-2">Awaiting decision</p>
                    </div>
                    <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4v.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Issues Card -->
            <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-red-500">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-gray-500 text-sm font-medium">Issues Reported</p>
                        <p class="text-3xl font-bold text-red-600 mt-2">{{ $assetStats['damaged'] + $assetStats['stolen'] }}</p>
                        <p class="text-xs text-gray-400 mt-2">Damaged or stolen</p>
                    </div>
                    <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4v2m0 4v.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid lg:grid-cols-3 gap-8">
            <!-- Main Content Area -->
            <div class="lg:col-span-2 space-y-8">
                <!-- Pending Requests Section -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-bold text-gray-900">Pending Asset Requests</h2>
                        <a href="{{ route('executive.approvals') }}" class="text-blue-600 hover:text-blue-700 text-sm font-medium">
                            View All →
                        </a>
                    </div>

                    @if($pendingRequests->count() > 0)
                        <div class="space-y-4">
                            @foreach($pendingRequests as $request)
                                <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition">
                                    <div class="flex justify-between items-start mb-3">
                                        <div>
                                            <h3 class="font-semibold text-gray-900">{{ $request->title }}</h3>
                                            <p class="text-sm text-gray-500">Requested by {{ $request->requestedBy->name }}</p>
                                        </div>
                                        <span class="px-3 py-1 bg-yellow-100 text-yellow-800 text-xs font-medium rounded-full">
                                            Pending
                                        </span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <div class="text-sm text-gray-600">
                                            <span class="font-semibold">{{ $request->quantity }}</span> items · 
                                            <span class="font-semibold">${{ number_format($request->estimated_cost, 2) }}</span>
                                        </div>
                                        <button class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition">
                                            Review
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-8">
                            <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <p class="text-gray-500">No pending requests</p>
                        </div>
                    @endif
                </div>

                <!-- Recent Activities Section -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-6">Recent Activities</h2>
                    
                    <div class="space-y-4 max-h-96 overflow-y-auto">
                        @forelse($recentActivities as $activity)
                            <div class="flex items-start space-x-4 pb-4 border-b border-gray-100 last:border-0">
                                <div class="w-2 h-2 rounded-full bg-blue-600 mt-2 flex-shrink-0"></div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm text-gray-900">
                                        <span class="font-medium">{{ $activity->user->name }}</span> {{ $activity->description }}
                                    </p>
                                    <p class="text-xs text-gray-500 mt-1">{{ $activity->created_at->diffForHumans() }}</p>
                                </div>
                            </div>
                        @empty
                            <p class="text-center text-gray-500 py-4">No recent activities</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Quick Stats -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="font-semibold text-gray-900 mb-4">Assignment Summary</h3>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Total Distributed</span>
                            <span class="font-bold text-gray-900">{{ $assignmentStats['total_assigned'] }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Currently Active</span>
                            <span class="font-bold text-green-600">{{ $assignmentStats['active_assignments'] }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Returned</span>
                            <span class="font-bold text-gray-900">{{ $assignmentStats['returned_assignments'] }}</span>
                        </div>
                    </div>
                </div>

                <!-- Asset Status Breakdown -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="font-semibold text-gray-900 mb-4">Asset Status Breakdown</h3>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-2">
                                <div class="w-3 h-3 rounded-full bg-green-500"></div>
                                <span class="text-sm text-gray-600">Active</span>
                            </div>
                            <span class="font-semibold text-gray-900">{{ $assetStats['active'] }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-2">
                                <div class="w-3 h-3 rounded-full bg-yellow-500"></div>
                                <span class="text-sm text-gray-600">Maintenance</span>
                            </div>
                            <span class="font-semibold text-gray-900">{{ $assetStats['maintenance'] }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-2">
                                <div class="w-3 h-3 rounded-full bg-orange-500"></div>
                                <span class="text-sm text-gray-600">Damaged</span>
                            </div>
                            <span class="font-semibold text-gray-900">{{ $assetStats['damaged'] }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-2">
                                <div class="w-3 h-3 rounded-full bg-red-500"></div>
                                <span class="text-sm text-gray-600">Stolen</span>
                            </div>
                            <span class="font-semibold text-gray-900">{{ $assetStats['stolen'] }}</span>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg p-6 border border-blue-200">
                    <h3 class="font-semibold text-gray-900 mb-4">Quick Actions</h3>
                    <div class="space-y-2">
                        <a href="{{ route('executive.approvals') }}" class="block w-full py-2 px-4 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition text-center">
                            Review Requests
                        </a>
                        <a href="{{ route('assets.index') }}" class="block w-full py-2 px-4 bg-white text-blue-600 text-sm font-medium rounded-lg border border-blue-200 hover:bg-blue-50 transition text-center">
                            View All Assets
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
