@extends('layout')

@section('title', 'Maintenance Reminders')

@section('content')
<div class="mb-6 flex justify-between items-center">
    <div>
        <h1 class="text-3xl font-bold text-gray-900">⏰ Maintenance Reminders</h1>
        <p class="text-gray-500 mt-1">Schedule and track service reminders for your assets</p>
    </div>
    <button onclick="document.getElementById('add-reminder-modal').classList.remove('hidden')"
        class="px-5 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition">
        + Add Reminder
    </button>
</div>

<div class="grid grid-cols-1 gap-4">
    @forelse($reminders as $reminder)
    @php
        $daysUntil = now()->diffInDays($reminder->next_service_date, false);
        $isOverdue = $daysUntil < 0;
        $isSoon    = $daysUntil >= 0 && $daysUntil <= 7;
    @endphp
    <div class="bg-white rounded-xl shadow p-5 flex justify-between items-start
        {{ $isOverdue ? 'border-l-4 border-red-500' : ($isSoon ? 'border-l-4 border-orange-400' : 'border-l-4 border-green-400') }}">
        <div class="flex-1">
            <div class="flex items-center gap-3 mb-1">
                <h3 class="font-semibold text-gray-900">{{ $reminder->service_type }}</h3>
                @if($isOverdue)
                    <span class="text-xs px-2 py-0.5 bg-red-100 text-red-700 rounded-full font-medium">Overdue</span>
                @elseif($isSoon)
                    <span class="text-xs px-2 py-0.5 bg-orange-100 text-orange-700 rounded-full font-medium">Due soon</span>
                @else
                    <span class="text-xs px-2 py-0.5 bg-green-100 text-green-700 rounded-full font-medium">Upcoming</span>
                @endif
            </div>
            <p class="text-sm text-gray-600">
                Asset: <span class="font-medium">{{ $reminder->asset?->name ?? '—' }}</span>
                · Due: <span class="font-medium">{{ \Carbon\Carbon::parse($reminder->next_service_date)->format('M j, Y') }}</span>
                @if($reminder->service_provider) · By: <span class="font-medium">{{ $reminder->service_provider }}</span> @endif
                @if($reminder->estimated_cost) · Est. cost: <span class="font-medium">${{ number_format($reminder->estimated_cost, 2) }}</span> @endif
            </p>
            @if($reminder->notes)
                <p class="text-sm text-gray-400 mt-1">{{ $reminder->notes }}</p>
            @endif
            @if($reminder->service_interval_days)
                <p class="text-xs text-gray-400 mt-1">Repeats every {{ $reminder->service_interval_days }} days</p>
            @endif
        </div>
        <form method="POST" action="{{ route('household.reminders.delete', $reminder) }}" class="ml-4"
            onsubmit="return confirm('Delete this reminder?')">
            @csrf @method('DELETE')
            <button type="submit" class="text-red-400 hover:text-red-600 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
            </button>
        </form>
    </div>
    @empty
    <div class="bg-white rounded-xl shadow p-16 text-center">
        <p class="text-4xl mb-4">⏰</p>
        <p class="text-gray-500 font-medium text-lg">No reminders yet</p>
        <p class="text-gray-400 text-sm mt-1">Add a reminder to track maintenance for your assets</p>
        <button onclick="document.getElementById('add-reminder-modal').classList.remove('hidden')"
            class="mt-6 px-5 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700">
            + Add Reminder
        </button>
    </div>
    @endforelse
</div>

<!-- Add Reminder Modal -->
<div id="add-reminder-modal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-xl shadow-2xl max-w-lg w-full mx-4">
        <div class="flex justify-between items-center px-6 py-4 border-b">
            <h2 class="text-lg font-bold text-gray-900">Add Maintenance Reminder</h2>
            <button onclick="document.getElementById('add-reminder-modal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <form method="POST" action="{{ route('household.reminders.store') }}" class="p-6 space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Asset *</label>
                <select name="asset_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                    <option value="">Select asset</option>
                    @foreach($assets as $asset)
                        <option value="{{ $asset->id }}">{{ $asset->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Service Type *</label>
                <input type="text" name="service_type" required placeholder="e.g., Oil Change, Annual Service"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Due Date *</label>
                    <input type="date" name="next_service_date" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Repeat Every (days)</label>
                    <input type="number" name="service_interval_days" min="1" placeholder="e.g., 90"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Service Provider</label>
                    <input type="text" name="service_provider" placeholder="Mechanic, clinic..."
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Est. Cost ($)</label>
                    <input type="number" name="estimated_cost" step="0.01" min="0"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                <textarea name="notes" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"></textarea>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="flex-1 py-2 bg-green-600 text-white font-medium rounded-lg hover:bg-green-700">Save Reminder</button>
                <button type="button" onclick="document.getElementById('add-reminder-modal').classList.add('hidden')"
                    class="flex-1 py-2 bg-gray-100 text-gray-700 font-medium rounded-lg hover:bg-gray-200">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Auto-open modal if there were validation errors
    @if($errors->any())
        document.getElementById('add-reminder-modal').classList.remove('hidden');
    @endif
</script>
@endsection
