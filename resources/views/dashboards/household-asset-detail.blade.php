@extends('layout')

@section('title', $asset->name)

@section('content')
<div class="mb-6">
    <a href="{{ route('household.dashboard') }}" class="text-green-600 hover:text-green-700 text-sm">← Back to Dashboard</a>
    <h1 class="text-3xl font-bold text-gray-900 mt-2">{{ $asset->name }}</h1>
    <p class="text-gray-500 mt-1">{{ $asset->category?->name ?? 'Uncategorized' }}</p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-6">
        <!-- Details -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Asset Details</h2>
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                <div><dt class="text-gray-500">Status</dt><dd class="font-medium mt-1">{{ $asset->status }}</dd></div>
                <div><dt class="text-gray-500">Location</dt><dd class="font-medium mt-1">{{ $asset->location?->name ?? '—' }}</dd></div>
                <div><dt class="text-gray-500">Manufacturer</dt><dd class="font-medium mt-1">{{ $asset->manufacturer ?? '—' }}</dd></div>
                <div><dt class="text-gray-500">Serial Number</dt><dd class="font-medium mt-1">{{ $asset->serial_number ?? '—' }}</dd></div>
                <div><dt class="text-gray-500">Estimated Value</dt><dd class="font-medium mt-1 text-green-700">{{ $asset->estimated_value ? '$' . number_format($asset->estimated_value, 2) : '—' }}</dd></div>
                <div><dt class="text-gray-500">Purchase Date</dt><dd class="font-medium mt-1">{{ $asset->purchase_date?->format('M j, Y') ?? '—' }}</dd></div>
            </dl>
            @if($asset->description)
                <p class="mt-4 text-sm text-gray-600">{{ $asset->description }}</p>
            @endif
        </div>

        <!-- Warranties -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-3">Warranties</h2>
            @forelse($warranties as $w)
                <div class="border-b border-gray-100 py-3 text-sm flex justify-between">
                    <div>
                        <p class="font-medium">{{ $w->warranty_type ?? 'Warranty' }}</p>
                        <p class="text-gray-500">Expires: {{ $w->warranty_end_date?->format('M j, Y') }}</p>
                    </div>
                    @if($w->warranty_end_date && $w->warranty_end_date->isFuture())
                        <span class="text-xs px-2 py-1 bg-green-100 text-green-700 rounded-full self-start">Active</span>
                    @else
                        <span class="text-xs px-2 py-1 bg-red-100 text-red-700 rounded-full self-start">Expired</span>
                    @endif
                </div>
            @empty
                <p class="text-sm text-gray-500">No warranties recorded.</p>
            @endforelse
        </div>

        <!-- Insurance -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-3">Insurance Policies</h2>
            @forelse($insurancePolicies as $policy)
                <div class="border-b border-gray-100 py-3 text-sm flex justify-between">
                    <div>
                        <p class="font-medium">{{ $policy->policy_number ?? 'Policy' }}</p>
                        <p class="text-gray-500">{{ $policy->insurer ?? '' }} · Expires: {{ $policy->end_date?->format('M j, Y') }}</p>
                    </div>
                    @if($policy->end_date && $policy->end_date->isFuture())
                        <span class="text-xs px-2 py-1 bg-green-100 text-green-700 rounded-full self-start">Active</span>
                    @else
                        <span class="text-xs px-2 py-1 bg-red-100 text-red-700 rounded-full self-start">Expired</span>
                    @endif
                </div>
            @empty
                <p class="text-sm text-gray-500">No insurance policies recorded.</p>
            @endforelse
        </div>

        <!-- Maintenance Records -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-3">Maintenance History</h2>
            @forelse($maintenanceRecords as $rec)
                <div class="border-b border-gray-100 py-3 text-sm">
                    <p class="font-medium">{{ $rec->title ?? $rec->type ?? 'Service' }}</p>
                    <p class="text-gray-500">{{ $rec->date?->format('M j, Y') }} {{ $rec->notes ? '· ' . $rec->notes : '' }}</p>
                </div>
            @empty
                <p class="text-sm text-gray-500">No maintenance records.</p>
            @endforelse
        </div>
    </div>

    <div class="space-y-6">
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-3">Value</h2>
            <p class="text-3xl font-bold text-green-700">${{ number_format($asset->estimated_value ?? 0, 2) }}</p>
            <p class="text-xs text-gray-500 mt-1">Estimated current value</p>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-sm font-semibold text-gray-700 mb-3">Quick Actions</h2>
            <a href="{{ route('household.dashboard') }}"
                class="block w-full text-center py-2 px-4 bg-gray-100 text-gray-700 text-sm rounded-lg hover:bg-gray-200 mb-2">
                Back to Dashboard
            </a>
        </div>
    </div>
</div>
@endsection
