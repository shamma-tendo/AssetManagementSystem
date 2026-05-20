@extends('layout')
@section('title', 'Request Leave')
@section('content')
<div class="max-w-2xl mx-auto">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Request Leave</h1>
        <p class="text-gray-500 mt-1">Submit a leave request for CEO approval</p>
    </div>

    @if(session('success'))
        <div class="mb-4 bg-green-50 border border-green-200 text-green-800 rounded-lg px-4 py-3 text-sm">{{ session('success') }}</div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
        <h2 class="font-bold text-gray-900 mb-4">New Leave Request</h2>
        <form method="POST" action="{{ route('staff.leave.submit') }}">
            @csrf

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Leave Type *</label>
                <select name="leave_type" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="">— Select type —</option>
                    <option value="annual"    {{ old('leave_type')=='annual'    ? 'selected':'' }}>Annual Leave</option>
                    <option value="sick"      {{ old('leave_type')=='sick'      ? 'selected':'' }}>Sick Leave</option>
                    <option value="emergency" {{ old('leave_type')=='emergency' ? 'selected':'' }}>Emergency Leave</option>
                    <option value="other"     {{ old('leave_type')=='other'     ? 'selected':'' }}>Other</option>
                </select>
                @error('leave_type')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Start Date *</label>
                    <input type="date" name="start_date" value="{{ old('start_date') }}" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    @error('start_date')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">End Date *</label>
                    <input type="date" name="end_date" value="{{ old('end_date') }}" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    @error('end_date')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="mb-5">
                <label class="block text-sm font-medium text-gray-700 mb-1">Reason *</label>
                <textarea name="reason" rows="4" required placeholder="Explain your reason for leave..."
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">{{ old('reason') }}</textarea>
                @error('reason')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <button type="submit" class="w-full py-2.5 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition">
                Submit Leave Request
            </button>
        </form>
    </div>

    {{-- My leave history --}}
    @if($myLeaves->count())
    <div class="bg-white rounded-xl shadow-sm border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-100">
            <h2 class="font-bold text-gray-900">My Leave History</h2>
        </div>
        <div class="divide-y divide-gray-100">
            @foreach($myLeaves as $l)
            @php
                $badge = match($l->status) {
                    'approved' => 'bg-green-100 text-green-800',
                    'rejected' => 'bg-red-100 text-red-800',
                    default    => 'bg-yellow-100 text-yellow-800',
                };
            @endphp
            <div class="px-6 py-4 flex justify-between items-start">
                <div>
                    <p class="font-semibold text-gray-900 capitalize">{{ str_replace('_',' ',$l->leave_type) }} Leave</p>
                    <p class="text-sm text-gray-500 mt-0.5">{{ $l->start_date->format('M d') }} – {{ $l->end_date->format('M d, Y') }}</p>
                    <p class="text-xs text-gray-400 mt-1 max-w-sm">{{ $l->reason }}</p>
                </div>
                <div class="text-right ml-4 flex-shrink-0">
                    <span class="px-2 py-1 text-xs rounded-full font-semibold {{ $badge }}">{{ ucfirst($l->status) }}</span>
                    @if($l->review_notes)
                        <p class="text-xs text-gray-500 mt-1 max-w-xs">{{ $l->review_notes }}</p>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif
</div>
@endsection
