@extends('layout')

@section('title', $asset->name)

@section('content')
    <div class="mb-6 flex flex-wrap justify-between gap-4 items-start">
        <div>
            <a href="{{ route('assets.index') }}" class="text-blue-600 hover:text-blue-800 text-sm">← Asset Registry</a>
            <h1 class="text-3xl font-bold text-gray-900 mt-2">{{ $asset->name }}</h1>
            <p class="text-gray-600 mt-1">Serial: {{ $asset->serial_number }}</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('assets.edit', $asset) }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 text-sm font-medium">Edit</a>
        </div>
    </div>

    <div class="border-b border-gray-200 mb-6">
        <nav class="flex flex-wrap gap-4" aria-label="Tabs">
            <span class="px-1 py-2 border-b-2 border-blue-600 text-blue-600 font-medium text-sm">Overview</span>
        </nav>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Details</h2>
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div><dt class="text-gray-500">Status</dt><dd class="font-medium mt-1">{{ $asset->status }}</dd></div>
                    <div><dt class="text-gray-500">Category</dt><dd class="font-medium mt-1">{{ $asset->category?->name ?? '—' }}</dd></div>
                    <div><dt class="text-gray-500">Location</dt><dd class="font-medium mt-1">{{ $asset->location?->name ?? '—' }}</dd></div>
                    <div><dt class="text-gray-500">Department</dt><dd class="font-medium mt-1">{{ $asset->department?->name ?? '—' }}</dd></div>
                    <div><dt class="text-gray-500">Purchase date</dt><dd class="font-medium mt-1">{{ $asset->purchase_date?->format('M j, Y') }}</dd></div>
                    <div><dt class="text-gray-500">Purchase cost</dt><dd class="font-medium mt-1">${{ number_format($asset->purchase_cost, 2) }}</dd></div>
                    <div><dt class="text-gray-500">Current book value</dt><dd class="font-medium mt-1">${{ number_format($asset->current_value, 2) }}</dd></div>
                    <div><dt class="text-gray-500">Useful life</dt><dd class="font-medium mt-1">{{ $asset->useful_life_years }} years</dd></div>
                </dl>
                @if ($asset->description)
                    <p class="mt-4 text-sm text-gray-700">{{ $asset->description }}</p>
                @endif
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-3">Custody &amp; field signals</h2>
                @if ($asset->currentAssignment)
                    <p class="text-sm text-gray-700">Assigned to <strong>{{ $asset->currentAssignment->assignedTo?->name }}</strong></p>
                    <p class="text-xs text-gray-500 mt-1">Since {{ $asset->currentAssignment->created_at->format('M j, Y') }}
                        @if ($asset->currentAssignment->acknowledged_at)
                            · Acknowledged {{ $asset->currentAssignment->acknowledged_at->diffForHumans() }}
                        @else
                            · <span class="text-amber-700">Awaiting colleague receipt</span>
                        @endif
                    </p>
                    @if ($asset->currentAssignment->staff_condition)
                        <p class="mt-3 text-sm">Latest field report: <span class="font-semibold text-gray-900">{{ str_replace('_', ' ', $asset->currentAssignment->staff_condition) }}</span></p>
                        @if ($asset->currentAssignment->condition_note)
                            <p class="text-sm text-gray-600 mt-1">{{ $asset->currentAssignment->condition_note }}</p>
                        @endif
                    @endif
                @else
                    <p class="text-sm text-gray-600">No active assignment — this asset is still in the shared pool or storeroom story.</p>
                @endif
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Recent work orders</h2>
                @forelse ($asset->workOrders as $wo)
                    <div class="border-b border-gray-100 py-3 flex justify-between items-center text-sm">
                        <div>
                            <a href="{{ route('work-orders.show', $wo) }}" class="font-medium text-blue-600 hover:underline">{{ $wo->work_order_number }}</a>
                            <span class="text-gray-500 ml-2">{{ $wo->type }}</span>
                        </div>
                        <span class="text-xs px-2 py-1 rounded-full bg-gray-100">{{ $wo->status }}</span>
                    </div>
                @empty
                    <p class="text-gray-500 text-sm">No work orders yet.</p>
                @endforelse
            </div>

        </div>

        <div class="space-y-6">
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-3">Financial snapshot</h2>
                <p class="text-sm text-gray-600">Run depreciation from the API or extend this view to call <code class="text-xs bg-gray-100 px-1 rounded">POST /api/financial/depreciation/{id}</code>.</p>
                <p class="mt-3 text-2xl font-bold text-gray-900">${{ number_format($asset->current_value, 2) }}</p>
                <p class="text-xs text-gray-500">Current value (book)</p>
            </div>
        </div>
    </div>
@endsection
