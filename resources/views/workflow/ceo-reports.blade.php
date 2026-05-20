@extends('layout')
@section('title', 'Asset Condition Reports')

@section('content')
<div class="mb-6 flex justify-between items-center">
    <div>
        <h1 class="text-3xl font-bold text-gray-900">Asset Condition Reports</h1>
        <p class="text-gray-500 mt-1">Staff-reported issues — repairs needed, stolen assets</p>
    </div>
    @if($unreviewedCount > 0)
        <span class="px-4 py-2 bg-red-100 text-red-800 text-sm font-semibold rounded-full">
            {{ $unreviewedCount }} unreviewed
        </span>
    @endif
</div>

@if(session('success'))
    <div class="mb-4 bg-green-50 border border-green-200 text-green-800 rounded-lg px-4 py-3 text-sm">{{ session('success') }}</div>
@endif

<div class="space-y-4">
    @forelse($reports as $report)
    @php
        $conditionColors = [
            'stolen'          => ['bg' => 'red',   'label' => '🚨 Stolen'],
            'needs_repair'    => ['bg' => 'orange', 'label' => '🔧 Needs Repair'],
            'broken'          => ['bg' => 'orange', 'label' => '💔 Broken'],
            'damaged'         => ['bg' => 'orange', 'label' => '⚠️ Damaged'],
            'in_use'          => ['bg' => 'green',  'label' => '✅ In Use'],
            'not_effective'   => ['bg' => 'yellow', 'label' => '❌ Not Effective'],
            'ready_for_return'=> ['bg' => 'blue',   'label' => '↩️ Ready to Return'],
            'lost'            => ['bg' => 'red',    'label' => '🔍 Lost'],
        ];
        $cond   = $conditionColors[$report->condition] ?? ['bg' => 'gray', 'label' => ucfirst($report->condition)];
        $isUrgent = in_array($report->condition, ['stolen','broken','damaged','lost']);
        $reviewed = $report->reviewed_at !== null;
    @endphp
    <div class="bg-white rounded-xl shadow border-l-4 {{ $isUrgent && !$reviewed ? 'border-red-500' : ($reviewed ? 'border-gray-300' : 'border-yellow-400') }} p-5">
        <div class="flex justify-between items-start mb-3">
            <div>
                <div class="flex items-center gap-3">
                    <h3 class="font-bold text-gray-900">{{ $report->asset?->name ?? 'Unknown Asset' }}</h3>
                    <span class="px-2 py-0.5 bg-{{ $cond['bg'] }}-100 text-{{ $cond['bg'] }}-800 text-xs font-semibold rounded-full">
                        {{ $cond['label'] }}
                    </span>
                    @if($reviewed)
                        <span class="px-2 py-0.5 bg-gray-100 text-gray-600 text-xs rounded-full">Reviewed</span>
                    @endif
                </div>
                <p class="text-sm text-gray-500 mt-1">
                    Reported by <strong>{{ $report->reportedBy->name }}</strong> · {{ $report->created_at->diffForHumans() }}
                </p>
            </div>
        </div>

        @if($report->description)
        <div class="bg-gray-50 rounded-lg p-3 text-sm text-gray-700 mb-3">
            {{ $report->description }}
        </div>
        @endif

        @if($reviewed)
        <div class="text-xs text-gray-500 bg-green-50 rounded p-2">
            Reviewed {{ $report->reviewed_at->format('M j, Y') }}
            @if($report->review_notes) · {{ $report->review_notes }} @endif
        </div>
        @else
        <form method="POST" action="{{ route('ceo.reports.reviewed', $report) }}" class="flex gap-3 items-end">
            @csrf
            <div class="flex-1">
                <label class="block text-xs font-medium text-gray-600 mb-1">Review Notes (optional)</label>
                <input type="text" name="review_notes" placeholder="Action taken, notes..."
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500">
            </div>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 whitespace-nowrap">
                Mark Reviewed
            </button>
        </form>
        @endif
    </div>
    @empty
    <div class="bg-white rounded-xl shadow text-center py-16">
        <p class="text-4xl mb-3">📋</p>
        <p class="text-gray-500 font-medium">No condition reports yet</p>
    </div>
    @endforelse
</div>

<div class="mt-6">{{ $reports->links() }}</div>
@endsection
