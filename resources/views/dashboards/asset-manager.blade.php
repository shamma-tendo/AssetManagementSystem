@extends('layout')

@section('title', 'Asset Manager Dashboard')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100">
    <!-- Header -->
    <div class="bg-white border-b border-gray-200 sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Asset Manager Dashboard</h1>
                    <p class="text-gray-500 mt-1">{{ $organization->getIndustryTypeLabel() }}</p>
                </div>
                <a href="{{ route('manager.requests.create') }}" class="px-6 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition">
                    + New Asset Request
                </a>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <!-- Key Metrics -->
        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
            <!-- My Requests Card -->
            <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-blue-500">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-gray-500 text-sm font-medium">My Requests</p>
                        <p class="text-3xl font-bold text-gray-900 mt-2">{{ $requestStats['total'] }}</p>
                        <p class="text-xs text-gray-400 mt-2">Total submitted</p>
                    </div>
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Pending Requests -->
            <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-yellow-500">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-gray-500 text-sm font-medium">Pending</p>
                        <p class="text-3xl font-bold text-yellow-600 mt-2">{{ $requestStats['pending'] }}</p>
                        <p class="text-xs text-gray-400 mt-2">Awaiting approval</p>
                    </div>
                    <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4v.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Approved -->
            <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-green-500">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-gray-500 text-sm font-medium">Approved</p>
                        <p class="text-3xl font-bold text-green-600 mt-2">{{ $requestStats['approved'] }}</p>
                        <p class="text-xs text-gray-400 mt-2">Ready to distribute</p>
                    </div>
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Active Distributions -->
            <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-purple-500">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-gray-500 text-sm font-medium">Distributed</p>
                        <p class="text-3xl font-bold text-purple-600 mt-2">{{ $distributionStats['active'] }}</p>
                        <p class="text-xs text-gray-400 mt-2">Currently with staff</p>
                    </div>
                    <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid lg:grid-cols-3 gap-8">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-8">
                <!-- Recent Requests -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-bold text-gray-900">My Recent Requests</h2>
                    </div>

                    @if($myRequests->count() > 0)
                        <div class="space-y-4">
                            @foreach($myRequests as $request)
                                <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition">
                                    <div class="flex justify-between items-start mb-2">
                                        <h3 class="font-semibold text-gray-900">{{ $request->title }}</h3>
                                        <span class="px-3 py-1 bg-gray-100 text-gray-800 text-xs font-medium rounded-full">
                                            {{ ucfirst($request->status) }}
                                        </span>
                                    </div>
                                    <div class="text-sm text-gray-600 space-y-1">
                                        <p><span class="font-semibold">{{ $request->quantity }}</span> items requested</p>
                                        <p>Est. Cost: <span class="font-semibold">${{ number_format($request->estimated_cost, 2) }}</span></p>
                                        @if($request->approvedBy)
                                            <p class="text-green-600">Approved by {{ $request->approvedBy->name }}</p>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-8">
                            <p class="text-gray-500 mb-4">No requests yet</p>
                            <a href="{{ route('manager.requests.create') }}" class="text-blue-600 hover:text-blue-700 font-medium">
                                Create your first request →
                            </a>
                        </div>
                    @endif
                </div>

                <!-- Pending Staff Reports -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-6">Staff Condition Reports (Pending)</h2>
                    
                    @if($pendingReports->count() > 0)
                        <div class="space-y-4">
                            @foreach($pendingReports as $report)
                                <div class="border-2 border-orange-200 rounded-lg p-4 bg-orange-50">
                                    <div class="flex justify-between items-start mb-2">
                                        <div>
                                            <h3 class="font-semibold text-gray-900">{{ $report->asset->name }}</h3>
                                            <p class="text-sm text-gray-600">Reported by {{ $report->reportedBy->name }}</p>
                                        </div>
                                        <span class="px-3 py-1 bg-orange-200 text-orange-900 text-xs font-medium rounded-full">
                                            {{ $report->condition_status }}
                                        </span>
                                    </div>
                                    <p class="text-sm text-gray-700 mb-3">{{ $report->notes }}</p>
                                    <button class="px-3 py-2 bg-orange-600 text-white text-sm font-medium rounded hover:bg-orange-700 transition">
                                        Review & Respond
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-8">
                            <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <p class="text-gray-500">No pending reports</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Distribution Summary -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="font-semibold text-gray-900 mb-4">Distribution Summary</h3>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Total Distributed</span>
                            <span class="font-bold text-gray-900">{{ $distributionStats['total_distributed'] }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Currently Active</span>
                            <span class="font-bold text-green-600">{{ $distributionStats['active'] }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Returned</span>
                            <span class="font-bold text-gray-900">{{ $distributionStats['returned'] }}</span>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg p-6 border border-blue-200">
                    <h3 class="font-semibold text-gray-900 mb-4">Quick Actions</h3>
                    <div class="space-y-2">
                        <a href="{{ route('manager.requests.create') }}" class="block w-full py-2 px-4 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition text-center">
                            New Request
                        </a>
                        <a href="{{ route('manager.distribute') }}" class="block w-full py-2 px-4 bg-white text-blue-600 text-sm font-medium rounded-lg border border-blue-200 hover:bg-blue-50 transition text-center">
                            Distribute Assets
                        </a>
                    </div>
                </div>

                <!-- Tips Box -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <p class="text-sm text-blue-900 font-medium mb-2">💡 Pro Tip</p>
                    <p class="text-xs text-blue-800">
                        Create bulk requests to streamline approvals. Group similar items together for faster processing.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
