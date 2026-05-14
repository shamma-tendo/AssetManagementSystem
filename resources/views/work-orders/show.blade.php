@extends('layout')

@section('title', $workOrder->work_order_number)

@section('content')
    <div class="mb-6">
        <a href="{{ route('work-orders.index') }}" class="text-blue-600 hover:text-blue-800 text-sm">← Work orders</a>
        <h1 class="text-3xl font-bold text-gray-900 mt-2">{{ $workOrder->work_order_number }}</h1>
        <p class="text-gray-600 mt-1">{{ $workOrder->asset?->name ?? 'Asset' }}</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-white rounded-lg shadow p-6 space-y-4">
            <div class="flex flex-wrap gap-2 items-center">
                <span class="text-sm text-gray-500">Status</span>
                <span class="px-3 py-1 rounded-full text-sm font-medium bg-gray-100">{{ $workOrder->status }}</span>
                <span class="text-sm text-gray-500 ml-4">Type</span>
                <span class="px-3 py-1 rounded-full text-sm font-medium bg-blue-50 text-blue-800">{{ $workOrder->type }}</span>
            </div>
            @if ($workOrder->description)
                <p class="text-gray-700 text-sm">{{ $workOrder->description }}</p>
            @endif
            <dl class="grid grid-cols-2 gap-4 text-sm pt-4 border-t">
                <div><dt class="text-gray-500">Scheduled</dt><dd class="font-medium">{{ $workOrder->scheduled_date?->format('M j, Y g:i A') ?? '—' }}</dd></div>
                <div><dt class="text-gray-500">Assigned to</dt><dd class="font-medium">{{ $workOrder->assignedTo?->name ?? 'Unassigned' }}</dd></div>
                <div><dt class="text-gray-500">Started</dt><dd class="font-medium">{{ $workOrder->started_date?->format('M j, Y') ?? '—' }}</dd></div>
                <div><dt class="text-gray-500">Completed</dt><dd class="font-medium">{{ $workOrder->completed_date?->format('M j, Y') ?? '—' }}</dd></div>
            </dl>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="font-semibold text-gray-900 mb-3">Parts used</h2>
            @forelse ($workOrder->spareParts as $p)
                <p class="text-sm border-b border-gray-100 py-2">{{ $p->part_name }} × {{ $p->pivot->quantity_used }}</p>
            @empty
                <p class="text-sm text-gray-500">No parts recorded.</p>
            @endforelse
        </div>
    </div>
@endsection
