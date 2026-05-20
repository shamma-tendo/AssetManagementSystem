@extends('layout')

@section('title', 'My Assets')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100">
    <!-- Header -->
    <div class="bg-white border-b border-gray-200 sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">My Assets</h1>
                <p class="text-gray-500 mt-1">View and report on assets assigned to you</p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('staff.requests') }}" class="px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition">
                    My Requests
                </a>
                <a href="{{ route('staff.request.form') }}" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition">
                    + Request Asset
                </a>
            </div>
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

        <!-- Quick Actions -->
        <div class="grid md:grid-cols-4 gap-4 mb-8">
            <a href="{{ route('staff.request.form') }}" class="flex flex-col gap-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl p-4 transition shadow-md">
                <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                </div>
                <p class="font-bold">Request Asset</p>
                <p class="text-blue-100 text-xs">Submit a request to the CEO</p>
            </a>
            <a href="{{ route('staff.asset-registry') }}" class="flex flex-col gap-2 bg-white hover:bg-gray-50 border border-gray-200 rounded-xl p-4 transition shadow-md">
                <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                </div>
                <p class="font-bold text-gray-800">Asset Registry</p>
                <p class="text-gray-400 text-xs">View available &amp; assigned assets</p>
            </a>
            <a href="{{ route('staff.report') }}" class="flex flex-col gap-2 bg-white hover:bg-gray-50 border border-gray-200 rounded-xl p-4 transition shadow-md">
                <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
                <p class="font-bold text-gray-800">Report Issue</p>
                <p class="text-gray-400 text-xs">Stolen, repair, outdated</p>
            </a>
            <a href="{{ route('staff.leave') }}" class="flex flex-col gap-2 bg-white hover:bg-gray-50 border border-gray-200 rounded-xl p-4 transition shadow-md">
                <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </div>
                <p class="font-bold text-gray-800">Request Leave</p>
                <p class="text-gray-400 text-xs">Annual, sick, emergency</p>
            </a>
        </div>

        <!-- Available Assets (from CEO inventory) -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-lg font-bold text-gray-900">Available Assets in Organisation</h2>
                <a href="{{ route('staff.asset-registry') }}" class="text-sm text-blue-600 hover:underline">View all →</a>
            </div>
            @if($availableAssets->count())
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs font-semibold text-gray-500 uppercase">
                        <tr>
                            <th class="px-4 py-2 text-left">Asset</th>
                            <th class="px-4 py-2 text-left">Category</th>
                            <th class="px-4 py-2 text-left">Location</th>
                            <th class="px-4 py-2 text-left">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($availableAssets->take(5) as $asset)
                        <tr>
                            <td class="px-4 py-2 font-medium text-gray-900">{{ $asset->name }}</td>
                            <td class="px-4 py-2 text-gray-500">{{ $asset->category->name ?? '—' }}</td>
                            <td class="px-4 py-2 text-gray-500">{{ $asset->location->name ?? '—' }}</td>
                            <td class="px-4 py-2"><a href="{{ route('staff.request.form') }}" class="text-blue-600 hover:underline text-xs">Request →</a></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($availableAssets->count() > 5)
                <p class="text-xs text-gray-400 mt-2 text-center">Showing 5 of {{ $availableAssets->count() }}. <a href="{{ route('staff.asset-registry') }}" class="text-blue-600 hover:underline">View all →</a></p>
            @endif
            @else
            <p class="text-gray-400 text-sm text-center py-4">No assets logged by the CEO yet.</p>
            @endif
        </div>

        <!-- My Recent Requests -->
        @if($myRequests->count())
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-lg font-bold text-gray-900">My Recent Requests</h2>
                <a href="{{ route('staff.requests') }}" class="text-sm text-blue-600 hover:underline">View all →</a>
            </div>
            <div class="space-y-3">
                @foreach($myRequests as $r)
                @php $badge = match($r->status){ 'approved','fulfilled'=>'bg-green-100 text-green-800','rejected'=>'bg-red-100 text-red-800',default=>'bg-yellow-100 text-yellow-800' }; @endphp
                <div class="flex items-center justify-between p-3 border border-gray-200 rounded-lg">
                    <div>
                        <p class="font-semibold text-gray-900 text-sm">{{ $r->title }}</p>
                        <p class="text-xs text-gray-400">Qty: {{ $r->quantity }} &bull; {{ $r->created_at->format('M d, Y') }}</p>
                    </div>
                    <span class="px-2 py-1 text-xs rounded-full font-semibold {{ $badge }}">{{ ucfirst($r->status) }}</span>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- My Assets -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-bold text-gray-900">My Current Assets</h2>
                @if($myAssets->count() > 0)
                    <a href="{{ route('staff.request.form') }}" class="text-sm text-blue-600 hover:text-blue-700 font-medium">+ Request more →</a>
                @endif
            </div>

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
                                    Report Issue
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-10 border-2 border-dashed border-gray-200 rounded-xl">
                    <div class="text-4xl mb-3">📦</div>
                    <p class="text-gray-600 font-semibold text-lg">No assets assigned to you yet</p>
                    <p class="text-gray-400 text-sm mt-1 mb-5">Submit a request to the CEO to get the equipment you need.</p>
                    <a href="{{ route('staff.request.form') }}"
                        class="inline-flex items-center gap-2 px-6 py-2.5 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-blue-700 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Request Your First Asset
                    </a>
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
