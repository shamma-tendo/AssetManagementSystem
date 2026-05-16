@extends('layout')

@section('title', 'My Assets')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100">
    <!-- Header -->
    <div class="bg-white border-b border-gray-200 sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <h1 class="text-3xl font-bold text-gray-900">My Assets</h1>
            <p class="text-gray-500 mt-1">View and report on assets assigned to you</p>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <!-- Stats -->
        <div class="grid md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-blue-500">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-gray-500 text-sm font-medium">Assets Assigned</p>
                        <p class="text-3xl font-bold text-gray-900 mt-2">{{ $myAssetStats['total_assigned'] }}</p>
                    </div>
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m0 0l8 4m-8-4v10l8 4m0-10l8-4m-8 4v10l8-4m0 0l-8-4"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-green-500">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-gray-500 text-sm font-medium">Currently Using</p>
                        <p class="text-3xl font-bold text-green-600 mt-2">{{ $myAssetStats['active'] }}</p>
                    </div>
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-orange-500">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-gray-500 text-sm font-medium">Issues Reported</p>
                        <p class="text-3xl font-bold text-orange-600 mt-2">{{ $myAssetStats['reported_issues'] }}</p>
                    </div>
                    <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4v2m0 4v.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- My Assets -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h2 class="text-xl font-bold text-gray-900 mb-6">My Current Assets</h2>
            
            @if($myAssets->count() > 0)
                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($myAssets as $assignment)
                        <div class="border border-gray-200 rounded-lg overflow-hidden hover:shadow-lg transition">
                            <!-- Asset Header -->
                            <div class="bg-gradient-to-r from-blue-50 to-blue-100 p-4 border-b border-gray-200">
                                <h3 class="font-bold text-gray-900">{{ $assignment->asset->name }}</h3>
                                <p class="text-sm text-gray-600">{{ $assignment->asset->category->name ?? 'N/A' }}</p>
                            </div>

                            <!-- Asset Details -->
                            <div class="p-4 space-y-3">
                                <div>
                                    <p class="text-xs text-gray-500 font-medium">Asset ID</p>
                                    <p class="text-sm font-mono text-gray-900">{{ $assignment->asset->id }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 font-medium">Serial Number</p>
                                    <p class="text-sm text-gray-900">{{ $assignment->asset->serial_number ?? 'N/A' }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 font-medium">Assigned On</p>
                                    <p class="text-sm text-gray-900">{{ $assignment->assigned_at->format('M d, Y') }}</p>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="bg-gray-50 p-4 border-t border-gray-200 flex gap-2">
                                <a href="{{ route('staff.asset.view', $assignment->asset->id) }}" class="flex-1 py-2 px-3 bg-blue-600 text-white text-sm font-medium rounded hover:bg-blue-700 transition text-center">
                                    View
                                </a>
                                <a href="{{ route('staff.asset.report', $assignment->asset->id) }}" class="flex-1 py-2 px-3 bg-orange-600 text-white text-sm font-medium rounded hover:bg-orange-700 transition text-center">
                                    Report
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-12">
                    <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m0 0l8 4m-8-4v10l8 4m0-10l8-4m-8 4v10l8-4m0 0l-8-4"></path>
                    </svg>
                    <p class="text-gray-500 text-lg">No assets assigned yet</p>
                </div>
            @endif
        </div>

        <!-- My Reports -->
        @if($myReports->count() > 0)
            <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                <h2 class="text-xl font-bold text-gray-900 mb-6">My Recent Reports</h2>
                
                <div class="space-y-4">
                    @foreach($myReports as $report)
                        <div class="border-2 border-{{ $report->condition_status === 'in_use' ? 'green' : 'orange' }}-200 rounded-lg p-4 bg-{{ $report->condition_status === 'in_use' ? 'green' : 'orange' }}-50">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h3 class="font-semibold text-gray-900">{{ $report->asset->name }}</h3>
                                    <p class="text-sm text-gray-600 mt-1">{{ $report->notes }}</p>
                                </div>
                                <span class="px-3 py-1 bg-{{ $report->condition_status === 'in_use' ? 'green' : 'orange' }}-200 text-{{ $report->condition_status === 'in_use' ? 'green' : 'orange' }}-900 text-xs font-medium rounded-full">
                                    {{ ucfirst(str_replace('_', ' ', $report->condition_status)) }}
                                </span>
                            </div>
                            <p class="text-xs text-gray-500 mt-3">Reported {{ $report->created_at->diffForHumans() }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Assignment History -->
        @if($assignmentHistory->count() > 0)
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-6">Past Assignments</h2>
                
                <div class="space-y-3">
                    @foreach($assignmentHistory as $history)
                        <div class="flex items-center justify-between p-3 border border-gray-200 rounded-lg">
                            <div>
                                <p class="font-semibold text-gray-900">{{ $history->asset->name }}</p>
                                <p class="text-xs text-gray-500">Returned {{ $history->returned_at->format('M d, Y') }}</p>
                            </div>
                            <span class="text-xs text-gray-500">{{ $history->assigned_at->diff($history->returned_at)->format('%d days') }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
