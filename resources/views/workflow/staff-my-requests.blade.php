@extends('layout')
@section('title', 'My Asset Requests')

@section('content')
<div class="mb-6 flex justify-between items-center">
    <div>
        <h1 class="text-3xl font-bold text-gray-900">My Asset Requests</h1>
        <p class="text-gray-500 mt-1">Track the status of your asset requests</p>
    </div>
    <a href="{{ route('staff.request.form') }}"
        class="px-5 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition">
        + New Request
    </a>
</div>

@if(session('success'))
    <div class="mb-4 bg-green-50 border border-green-200 text-green-800 rounded-lg px-4 py-3 text-sm">{{ session('success') }}</div>
@endif

<div class="space-y-4">
    @forelse($requests as $req)
    @php
        $statusConfig = [
            'pending'   => ['color' => 'yellow', 'label' => '⏳ Pending Review',    'desc' => 'Awaiting CEO decision'],
            'approved'  => ['color' => 'green',  'label' => '✅ Approved',           'desc' => 'Your request was approved'],
            'fulfilled' => ['color' => 'blue',   'label' => '📦 Fulfilled',          'desc' => 'Asset has been assigned to you'],
            'rejected'  => ['color' => 'red',    'label' => '❌ Rejected',            'desc' => 'Request was declined'],
        ];
        $s = $statusConfig[$req->status] ?? ['color' => 'gray', 'label' => ucfirst($req->status), 'desc' => ''];
    @endphp
    <div class="bg-white rounded-xl shadow border-l-4 border-{{ $s['color'] }}-400 p-5">
        <div class="flex justify-between items-start mb-2">
            <div>
                <h3 class="font-bold text-gray-900">{{ $req->title }}</h3>
                <p class="text-sm text-gray-500 mt-0.5">{{ $req->asset_type }} · Qty: {{ $req->quantity }} · {{ $req->created_at->format('M j, Y') }}</p>
            </div>
            <div class="text-right">
                <span class="inline-block px-3 py-1 bg-{{ $s['color'] }}-100 text-{{ $s['color'] }}-800 text-xs font-semibold rounded-full">
                    {{ $s['label'] }}
                </span>
                <p class="text-xs text-gray-400 mt-1">{{ $s['desc'] }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-3 text-sm">
            <div class="bg-gray-50 rounded-lg p-3">
                <p class="text-xs text-gray-500 font-medium">Location to Use</p>
                <p class="mt-0.5 text-gray-800">{{ $req->use_location ?? '—' }}</p>
            </div>
            <div class="bg-blue-50 rounded-lg p-3">
                <p class="text-xs text-blue-600 font-medium">Purpose</p>
                <p class="mt-0.5 text-gray-800">{{ \Str::limit($req->purpose, 120) }}</p>
            </div>
        </div>

        @if($req->approval_notes)
        <div class="mt-3 bg-{{ $req->status === 'rejected' ? 'red' : 'green' }}-50 rounded-lg p-3 text-sm">
            <p class="text-xs font-semibold text-{{ $req->status === 'rejected' ? 'red' : 'green' }}-700 mb-1">
                CEO Response @if($req->approvedBy) — {{ $req->approvedBy->name }} @endif
            </p>
            <p class="text-gray-800">{{ $req->approval_notes }}</p>
        </div>
        @endif
    </div>
    @empty
    <div class="bg-white rounded-xl shadow text-center py-16">
        <p class="text-4xl mb-3">📋</p>
        <p class="text-gray-500 font-medium text-lg">No requests yet</p>
        <p class="text-gray-400 text-sm mt-1">Submit a request when you need an asset from the company</p>
        <a href="{{ route('staff.request.form') }}"
            class="inline-block mt-5 px-5 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">
            + Make a Request
        </a>
    </div>
    @endforelse
</div>

@if($requests->hasPages())
<div class="mt-6">{{ $requests->links() }}</div>
@endif
@endsection
