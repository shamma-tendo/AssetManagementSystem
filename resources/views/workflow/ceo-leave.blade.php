@extends('layout')
@section('title', 'Leave Requests')
@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Staff Leave Requests</h1>
    <p class="text-gray-500 mt-1">Review and approve or reject leave requests from staff</p>
</div>

@if(session('success'))
    <div class="mb-4 bg-green-50 border border-green-200 text-green-800 rounded-lg px-4 py-3 text-sm">{{ session('success') }}</div>
@endif

{{-- Pending --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-200 mb-8">
    <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
        <h2 class="font-bold text-gray-900">Pending Requests</h2>
        <span class="text-sm font-semibold text-yellow-700 bg-yellow-100 px-2 py-1 rounded-full">{{ $pending->count() }} pending</span>
    </div>

    @if($pending->count())
    <div class="divide-y divide-gray-100">
        @foreach($pending as $leave)
        <div class="px-6 py-5">
            <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
                <div>
                    <p class="font-bold text-gray-900">{{ $leave->requestedBy->name }}</p>
                    <p class="text-sm text-gray-500 capitalize mt-0.5">{{ str_replace('_',' ',$leave->leave_type) }} Leave &mdash; {{ $leave->start_date->format('M d') }} to {{ $leave->end_date->format('M d, Y') }} ({{ $leave->start_date->diffInDays($leave->end_date) + 1 }} day(s))</p>
                    <p class="text-sm text-gray-600 mt-2 bg-gray-50 rounded-lg p-3 border border-gray-200">{{ $leave->reason }}</p>
                    <p class="text-xs text-gray-400 mt-2">Submitted {{ $leave->created_at->diffForHumans() }}</p>
                </div>
                <div class="flex flex-col gap-2 min-w-[220px]">
                    <form method="POST" action="{{ route('ceo.leave.approve', $leave) }}" class="flex gap-2">
                        @csrf
                        <input type="text" name="review_notes" placeholder="Optional note" class="flex-1 px-2 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                        <button type="submit" class="px-4 py-1.5 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition whitespace-nowrap">Approve</button>
                    </form>
                    <form method="POST" action="{{ route('ceo.leave.reject', $leave) }}" class="flex gap-2">
                        @csrf
                        <input type="text" name="review_notes" required placeholder="Reason for rejection" class="flex-1 px-2 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500">
                        <button type="submit" class="px-4 py-1.5 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition whitespace-nowrap">Reject</button>
                    </form>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @else
    <div class="text-center py-10 text-gray-400">No pending leave requests.</div>
    @endif
</div>

{{-- Reviewed --}}
@if($reviewed->count())
<div class="bg-white rounded-xl shadow-sm border border-gray-200">
    <div class="px-6 py-4 border-b border-gray-100">
        <h2 class="font-bold text-gray-900">Reviewed Requests</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-xs font-semibold text-gray-500 uppercase">
                <tr>
                    <th class="px-5 py-3 text-left">Staff</th>
                    <th class="px-5 py-3 text-left">Type</th>
                    <th class="px-5 py-3 text-left">Dates</th>
                    <th class="px-5 py-3 text-left">Status</th>
                    <th class="px-5 py-3 text-left">Note</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($reviewed as $l)
                @php $badge = $l->status === 'approved' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-3 font-medium text-gray-900">{{ $l->requestedBy->name }}</td>
                    <td class="px-5 py-3 text-gray-600 capitalize">{{ str_replace('_',' ',$l->leave_type) }}</td>
                    <td class="px-5 py-3 text-gray-600">{{ $l->start_date->format('M d') }} – {{ $l->end_date->format('M d, Y') }}</td>
                    <td class="px-5 py-3"><span class="px-2 py-1 text-xs rounded-full font-semibold {{ $badge }}">{{ ucfirst($l->status) }}</span></td>
                    <td class="px-5 py-3 text-gray-500">{{ $l->review_notes ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
@endsection
