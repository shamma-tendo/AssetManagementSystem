@extends('layout')
@section('title', 'Asset Requests')

@section('content')
<div class="mb-6">
    <h1 class="text-3xl font-bold text-gray-900">Staff Asset Requests</h1>
    <p class="text-gray-500 mt-1">Review, approve or reject staff requests and assign assets</p>
</div>

@if(session('success'))
    <div class="mb-4 bg-green-50 border border-green-200 text-green-800 rounded-lg px-4 py-3 text-sm">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="mb-4 bg-red-50 border border-red-200 text-red-800 rounded-lg px-4 py-3 text-sm">{{ session('error') }}</div>
@endif

<!-- Pending Requests -->
<div class="bg-white rounded-xl shadow mb-8">
    <div class="px-6 py-4 border-b flex items-center justify-between">
        <h2 class="text-lg font-bold text-gray-900">Pending Requests
            @if($pendingRequests->count())
                <span class="ml-2 px-2 py-0.5 bg-yellow-100 text-yellow-800 text-sm rounded-full">{{ $pendingRequests->count() }}</span>
            @endif
        </h2>
    </div>

    @if($pendingRequests->count())
    <div class="divide-y divide-gray-100">
        @foreach($pendingRequests as $req)
        <div class="px-6 py-5">
            <div class="flex justify-between items-start mb-3">
                <div>
                    <h3 class="font-semibold text-gray-900 text-base">{{ $req->title }}</h3>
                    <p class="text-sm text-gray-500 mt-0.5">
                        By <strong>{{ $req->requestedBy->name }}</strong> · {{ $req->created_at->diffForHumans() }}
                    </p>
                </div>
                <span class="px-3 py-1 bg-yellow-100 text-yellow-800 text-xs font-medium rounded-full">Pending</span>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4 text-sm">
                <div class="bg-gray-50 rounded-lg p-3">
                    <p class="text-gray-500 text-xs">Asset Type</p>
                    <p class="font-medium mt-0.5">{{ $req->asset_type }}</p>
                </div>
                <div class="bg-gray-50 rounded-lg p-3">
                    <p class="text-gray-500 text-xs">Quantity</p>
                    <p class="font-medium mt-0.5">{{ $req->quantity }}</p>
                </div>
                <div class="bg-gray-50 rounded-lg p-3">
                    <p class="text-gray-500 text-xs">Location to Use</p>
                    <p class="font-medium mt-0.5">{{ $req->use_location ?? '—' }}</p>
                </div>
                <div class="bg-gray-50 rounded-lg p-3">
                    <p class="text-gray-500 text-xs">Est. Cost</p>
                    <p class="font-medium mt-0.5">{{ $req->estimated_cost ? '$'.number_format($req->estimated_cost,0) : '—' }}</p>
                </div>
            </div>

            @if($req->purpose)
            <div class="mb-4 bg-blue-50 rounded-lg p-3 text-sm">
                <p class="text-blue-700 font-medium text-xs uppercase tracking-wide mb-1">Purpose</p>
                <p class="text-gray-800">{{ $req->purpose }}</p>
            </div>
            @endif

            @if($req->description)
            <p class="text-sm text-gray-600 mb-4">{{ $req->description }}</p>
            @endif

            <!-- Approve Form -->
            <details class="group">
                <summary class="cursor-pointer inline-flex items-center gap-2 px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition list-none">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Approve & Assign
                </summary>
                <div class="mt-3 p-4 bg-green-50 rounded-lg border border-green-200">
                    <form method="POST" action="{{ route('ceo.requests.approve', $req) }}">
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Select Asset to Assign *</label>
                                <select name="asset_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 text-sm">
                                    <option value="">-- Choose available asset --</option>
                                    @foreach($availableAssets as $asset)
                                        <option value="{{ $asset->id }}">{{ $asset->name }} ({{ $asset->serial_number }}) {{ $asset->category ? '· '.$asset->category->name : '' }}</option>
                                    @endforeach
                                </select>
                                @if($availableAssets->isEmpty())
                                    <p class="text-xs text-orange-600 mt-1">⚠ No unassigned active assets. <a href="{{ route('ceo.inventory') }}" class="underline">Add assets first →</a></p>
                                @endif
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Approval Notes</label>
                                <input type="text" name="approval_notes" placeholder="Optional notes..."
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 text-sm">
                            </div>
                        </div>
                        <button type="submit" class="mt-3 px-5 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700">
                            Confirm Approval &amp; Assign
                        </button>
                    </form>
                </div>
            </details>

            <!-- Reject Form -->
            <details class="group mt-2">
                <summary class="cursor-pointer inline-flex items-center gap-2 px-4 py-2 bg-red-50 text-red-700 border border-red-200 text-sm font-medium rounded-lg hover:bg-red-100 transition list-none">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    Reject Request
                </summary>
                <div class="mt-3 p-4 bg-red-50 rounded-lg border border-red-200">
                    <form method="POST" action="{{ route('ceo.requests.reject', $req) }}">
                        @csrf
                        <label class="block text-sm font-medium text-gray-700 mb-1">Rejection Reason *</label>
                        <input type="text" name="rejection_reason" required placeholder="Reason for rejection..."
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 text-sm mb-3">
                        <button type="submit" class="px-5 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700">
                            Confirm Rejection
                        </button>
                    </form>
                </div>
            </details>
        </div>
        @endforeach
    </div>
    @else
    <div class="text-center py-12">
        <p class="text-3xl mb-3">✅</p>
        <p class="text-gray-500">No pending requests</p>
    </div>
    @endif
</div>

<!-- Reviewed Requests History -->
@if($reviewedRequests->count())
<div class="bg-white rounded-xl shadow">
    <div class="px-6 py-4 border-b">
        <h2 class="text-lg font-bold text-gray-900">Decision History</h2>
    </div>
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b">
            <tr>
                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Request</th>
                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Staff Member</th>
                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Qty</th>
                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Status</th>
                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Reviewed</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @foreach($reviewedRequests as $req)
            @php
                $colors = ['approved'=>'green','fulfilled'=>'blue','rejected'=>'red'];
                $c = $colors[$req->status] ?? 'gray';
            @endphp
            <tr class="hover:bg-gray-50">
                <td class="px-5 py-3 font-medium text-gray-900">{{ $req->title }}</td>
                <td class="px-5 py-3 text-gray-600">{{ $req->requestedBy->name }}</td>
                <td class="px-5 py-3 text-gray-600">{{ $req->quantity }}</td>
                <td class="px-5 py-3">
                    <span class="px-2 py-1 bg-{{ $c }}-100 text-{{ $c }}-800 text-xs font-medium rounded-full">{{ ucfirst($req->status) }}</span>
                </td>
                <td class="px-5 py-3 text-gray-500 text-xs">{{ $req->reviewed_at?->format('M j, Y') ?? '—' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif
@endsection
