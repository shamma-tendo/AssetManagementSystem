@extends('layout')

@section('title', 'Asset Detail')

@section('content')
    <div class="mb-6">
        <a href="{{ route('staff.dashboard') }}" class="text-blue-600 hover:text-blue-800 text-sm">← Back to Dashboard</a>
        <h1 class="text-3xl font-bold text-gray-900 mt-2">{{ $asset->name }}</h1>
        <p class="text-gray-600 mt-1">Serial: {{ $asset->serial_number }}</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <!-- Asset Details -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Asset Details</h2>
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div><dt class="text-gray-500">Status</dt><dd class="font-medium mt-1">{{ $asset->status }}</dd></div>
                    <div><dt class="text-gray-500">Category</dt><dd class="font-medium mt-1">{{ $asset->category?->name ?? '—' }}</dd></div>
                    <div><dt class="text-gray-500">Model</dt><dd class="font-medium mt-1">{{ $asset->model ?? '—' }}</dd></div>
                    <div><dt class="text-gray-500">Manufacturer</dt><dd class="font-medium mt-1">{{ $asset->manufacturer ?? '—' }}</dd></div>
                    <div><dt class="text-gray-500">Assigned Since</dt><dd class="font-medium mt-1">{{ $assignment->assigned_at ? \Carbon\Carbon::parse($assignment->assigned_at)->format('M j, Y') : '—' }}</dd></div>
                </dl>
            </div>

            <!-- My Reports -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-semibold text-gray-900">My Condition Reports</h2>
                    <a href="{{ route('staff.asset.report', $asset->id) }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 text-sm">Report Condition</a>
                </div>
                @forelse ($conditionReports as $report)
                    <div class="border-b border-gray-100 py-3 text-sm">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="font-medium capitalize">{{ str_replace('_', ' ', $report->condition) }}</p>
                                @if ($report->description)
                                    <p class="text-gray-500 mt-1">{{ $report->description }}</p>
                                @endif
                            </div>
                            <span class="text-xs text-gray-400">{{ $report->created_at->diffForHumans() }}</span>
                        </div>
                        @if ($report->reviewed_at)
                            <span class="inline-block mt-1 text-xs px-2 py-0.5 rounded-full bg-green-100 text-green-700">Reviewed</span>
                        @else
                            <span class="inline-block mt-1 text-xs px-2 py-0.5 rounded-full bg-yellow-100 text-yellow-700">Pending review</span>
                        @endif
                    </div>
                @empty
                    <p class="text-gray-500 text-sm">No reports filed yet for this asset.</p>
                @endforelse
            </div>
        </div>

        <div class="space-y-6">
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-3">Quick Actions</h2>
                <a href="{{ route('staff.asset.report', $asset->id) }}"
                    class="block w-full text-center bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 text-sm font-medium mb-3">
                    Report Asset Condition
                </a>
                <a href="{{ route('staff.dashboard') }}"
                    class="block w-full text-center bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 text-sm font-medium">
                    Back to Dashboard
                </a>
            </div>
        </div>
    </div>
@endsection
