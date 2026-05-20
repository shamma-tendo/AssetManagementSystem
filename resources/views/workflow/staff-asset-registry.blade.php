@extends('layout')
@section('title', 'Asset Registry')
@section('content')
<div class="mb-6 flex justify-between items-center">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Asset Registry</h1>
        <p class="text-gray-500 mt-1">Assets available in your organisation and what's assigned to you</p>
    </div>
    <a href="{{ route('staff.request.form') }}" class="px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-blue-700 transition">+ Request an Asset</a>
</div>

{{-- My Assigned Assets --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-200 mb-8">
    <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
        <h2 class="font-bold text-gray-900 text-lg">My Assigned Assets</h2>
        <span class="text-sm text-gray-500">{{ $myAssignments->count() }} asset(s)</span>
    </div>
    @if($myAssignments->count())
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-xs font-semibold text-gray-500 uppercase">
                <tr>
                    <th class="px-5 py-3 text-left">Asset</th>
                    <th class="px-5 py-3 text-left">Category</th>
                    <th class="px-5 py-3 text-left">Location</th>
                    <th class="px-5 py-3 text-left">Assigned On</th>
                    <th class="px-5 py-3 text-left">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($myAssignments as $a)
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-3 font-semibold text-gray-900">{{ $a->asset->name }}<br><span class="text-xs text-gray-400 font-mono">{{ $a->asset->serial_number }}</span></td>
                    <td class="px-5 py-3 text-gray-600">{{ $a->asset->category->name ?? '—' }}</td>
                    <td class="px-5 py-3 text-gray-600">{{ $a->asset->location->name ?? '—' }}</td>
                    <td class="px-5 py-3 text-gray-600">{{ $a->assigned_at->format('M d, Y') }}</td>
                    <td class="px-5 py-3">
                        <a href="{{ route('staff.report') }}" class="text-orange-600 hover:underline text-xs font-medium">Report Issue</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <div class="text-center py-10 text-gray-400">No assets assigned to you yet.</div>
    @endif
</div>

{{-- My Requests --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-200 mb-8">
    <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
        <h2 class="font-bold text-gray-900 text-lg">My Asset Requests</h2>
        <a href="{{ route('staff.requests') }}" class="text-sm text-blue-600 hover:underline">View all →</a>
    </div>
    @if($myRequests->count())
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-xs font-semibold text-gray-500 uppercase">
                <tr>
                    <th class="px-5 py-3 text-left">Asset Requested</th>
                    <th class="px-5 py-3 text-left">Qty</th>
                    <th class="px-5 py-3 text-left">Purpose</th>
                    <th class="px-5 py-3 text-left">Date</th>
                    <th class="px-5 py-3 text-left">Status</th>
                    <th class="px-5 py-3 text-left">CEO Response</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($myRequests as $r)
                @php
                    $badge = match($r->status) {
                        'approved','fulfilled' => 'bg-green-100 text-green-800',
                        'rejected'             => 'bg-red-100 text-red-800',
                        default                => 'bg-yellow-100 text-yellow-800',
                    };
                @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-3 font-medium text-gray-900">{{ $r->title }}</td>
                    <td class="px-5 py-3 text-gray-600">{{ $r->quantity }}</td>
                    <td class="px-5 py-3 text-gray-600 max-w-xs truncate">{{ $r->purpose }}</td>
                    <td class="px-5 py-3 text-gray-500 text-xs">{{ $r->created_at->format('M d, Y') }}</td>
                    <td class="px-5 py-3"><span class="px-2 py-1 text-xs rounded-full font-semibold {{ $badge }}">{{ ucfirst($r->status) }}</span></td>
                    <td class="px-5 py-3 text-gray-500 text-xs">{{ $r->ceo_notes ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <div class="text-center py-8 text-gray-400">No requests yet. <a href="{{ route('staff.request.form') }}" class="text-blue-600 hover:underline">Make a request →</a></div>
    @endif
</div>

{{-- Available Assets (CEO Inventory) --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-200">
    <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
        <h2 class="font-bold text-gray-900 text-lg">Available Assets in Organisation</h2>
        <span class="text-sm text-gray-500">{{ $availableAssets->count() }} available</span>
    </div>
    @if($availableAssets->count())
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-xs font-semibold text-gray-500 uppercase">
                <tr>
                    <th class="px-5 py-3 text-left">Asset Name</th>
                    <th class="px-5 py-3 text-left">Category</th>
                    <th class="px-5 py-3 text-left">Location</th>
                    <th class="px-5 py-3 text-left">Status</th>
                    <th class="px-5 py-3 text-left">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($availableAssets as $asset)
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-3 font-medium text-gray-900">{{ $asset->name }}</td>
                    <td class="px-5 py-3 text-gray-600">{{ $asset->category->name ?? '—' }}</td>
                    <td class="px-5 py-3 text-gray-600">{{ $asset->location->name ?? '—' }}</td>
                    <td class="px-5 py-3"><span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full font-semibold">{{ $asset->status }}</span></td>
                    <td class="px-5 py-3">
                        <a href="{{ route('staff.request.form') }}" class="text-blue-600 hover:underline text-xs font-medium">Request this →</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <div class="text-center py-10 text-gray-400">No assets logged by the CEO yet.</div>
    @endif
</div>
@endsection
