@extends('layout')
@section('title', 'Report Asset Issue')
@section('content')
<div class="max-w-2xl mx-auto">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Report Asset Issue</h1>
        <p class="text-gray-500 mt-1">Report if an asset is stolen, needs repair, or is outdated</p>
    </div>

    @if(session('success'))
        <div class="mb-4 bg-green-50 border border-green-200 text-green-800 rounded-lg px-4 py-3 text-sm">{{ session('success') }}</div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
        <h2 class="font-bold text-gray-900 mb-4">Submit a Report</h2>

        @if($myAssignments->count())
        <form method="POST" action="{{ route('staff.report.submit') }}">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Select Asset *</label>
                <select name="asset_assignment_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="">— Choose an asset —</option>
                    @foreach($myAssignments as $a)
                        <option value="{{ $a->id }}">{{ $a->asset->name }} ({{ $a->asset->serial_number ?? 'no serial' }})</option>
                    @endforeach
                </select>
                @error('asset_assignment_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Issue Type *</label>
                <div class="grid grid-cols-2 gap-3">
                    @foreach([
                        ['value'=>'stolen',       'label'=>'Stolen',        'icon'=>'⚠️', 'color'=>'red'],
                        ['value'=>'needs_repair', 'label'=>'Needs Repair',  'icon'=>'🔧', 'color'=>'yellow'],
                        ['value'=>'damaged',      'label'=>'Damaged',       'icon'=>'💔', 'color'=>'orange'],
                        ['value'=>'outdated',     'label'=>'Outdated',      'icon'=>'🗓️', 'color'=>'gray'],
                    ] as $opt)
                    <label class="cursor-pointer">
                        <input type="radio" name="condition" value="{{ $opt['value'] }}" class="sr-only peer" required>
                        <div class="p-3 border-2 border-gray-200 rounded-lg text-center peer-checked:border-{{ $opt['color'] }}-500 peer-checked:bg-{{ $opt['color'] }}-50 transition">
                            <div class="text-2xl mb-1">{{ $opt['icon'] }}</div>
                            <p class="text-sm font-semibold text-gray-800">{{ $opt['label'] }}</p>
                        </div>
                    </label>
                    @endforeach
                </div>
                @error('condition')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="mb-5">
                <label class="block text-sm font-medium text-gray-700 mb-1">Description *</label>
                <textarea name="description" rows="4" required placeholder="Describe the issue in detail..." class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">{{ old('description') }}</textarea>
                @error('description')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <button type="submit" class="w-full py-2.5 bg-red-600 text-white font-semibold rounded-lg hover:bg-red-700 transition">
                Submit Report
            </button>
        </form>
        @else
        <div class="text-center py-8 text-gray-400">
            <p class="text-lg">No assets assigned to you yet.</p>
            <p class="text-sm mt-1">You can only report issues for assets assigned to you.</p>
        </div>
        @endif
    </div>

    {{-- Past Reports --}}
    @if($myReports->count())
    <div class="bg-white rounded-xl shadow-sm border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-100">
            <h2 class="font-bold text-gray-900">My Previous Reports</h2>
        </div>
        <div class="divide-y divide-gray-100">
            @foreach($myReports as $r)
            @php
                $colors = ['stolen'=>'red','needs_repair'=>'yellow','damaged'=>'orange','outdated'=>'gray'];
                $c = $colors[$r->condition] ?? 'gray';
            @endphp
            <div class="px-6 py-4 flex justify-between items-start">
                <div>
                    <p class="font-semibold text-gray-900">{{ $r->asset->name ?? '—' }}</p>
                    <p class="text-sm text-gray-500 mt-0.5">{{ $r->description }}</p>
                    <p class="text-xs text-gray-400 mt-1">{{ $r->created_at->format('M d, Y') }}</p>
                </div>
                <div class="text-right ml-4 flex-shrink-0">
                    <span class="px-2 py-1 bg-{{ $c }}-100 text-{{ $c }}-800 text-xs rounded-full font-semibold">{{ ucfirst(str_replace('_',' ',$r->condition)) }}</span>
                    @if($r->reviewed_at)
                        <p class="text-xs text-green-600 mt-1">✓ Reviewed</p>
                    @else
                        <p class="text-xs text-yellow-600 mt-1">Pending review</p>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif
</div>
@endsection
