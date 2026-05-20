@extends('layouts.app')

@section('title', 'New Work Order')

@section('content')
<div class="max-w-2xl mx-auto">

    <!-- Page Header -->
    <div class="mb-8 flex items-center space-x-4">
        <a href="{{ route('maintenance') }}"
           class="p-2 bg-white/10 backdrop-blur-sm border border-white/20 rounded-xl hover:bg-white/20 transition-all duration-200">
            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
        </a>
        <div>
            <h1 class="text-3xl font-bold text-gray-900">New Work Order</h1>
            <p class="text-gray-600 mt-1">Create a work order and assign it to an asset.</p>
        </div>
    </div>

    @if($errors->any())
    <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl">
        <ul class="text-sm text-red-700 space-y-1">
            @foreach($errors->all() as $error)
                <li class="flex items-start space-x-2">
                    <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 15.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                    <span>{{ $error }}</span>
                </li>
            @endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="{{ route('maintenance.create-work-order') }}">
        @csrf

        <div class="bg-white/10 backdrop-blur-xl border border-white/20 rounded-2xl shadow-xl p-8 space-y-6">

            <!-- Title -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    Title <span class="text-red-500">*</span>
                </label>
                <input type="text" name="title" required value="{{ old('title') }}"
                    placeholder="e.g. Quarterly HVAC Inspection"
                    class="w-full px-4 py-2.5 bg-white border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all @error('title') border-red-400 @enderror">
            </div>

            <!-- Description -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    Description <span class="text-red-500">*</span>
                </label>
                <textarea name="description" required rows="3"
                    placeholder="Describe what needs to be done..."
                    class="w-full px-4 py-2.5 bg-white border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all resize-none @error('description') border-red-400 @enderror">{{ old('description') }}</textarea>
            </div>

            <!-- Type & Priority -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        Type <span class="text-red-500">*</span>
                    </label>
                    <select name="type" required
                        class="w-full px-4 py-2.5 bg-white border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('type') border-red-400 @enderror">
                        <option value="">Select type…</option>
                        <option value="preventive_maintenance" @selected(old('type')=='preventive_maintenance')>Preventive Maintenance</option>
                        <option value="corrective_maintenance" @selected(old('type')=='corrective_maintenance')>Corrective Maintenance</option>
                        <option value="emergency_maintenance"  @selected(old('type')=='emergency_maintenance')>Emergency Maintenance</option>
                        <option value="inspection"             @selected(old('type')=='inspection')>Inspection</option>
                        <option value="calibration"            @selected(old('type')=='calibration')>Calibration</option>
                        <option value="installation"           @selected(old('type')=='installation')>Installation</option>
                        <option value="repair"                 @selected(old('type')=='repair')>Repair</option>
                        <option value="upgrade"                @selected(old('type')=='upgrade')>Upgrade</option>
                        <option value="other"                  @selected(old('type')=='other')>Other</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        Priority <span class="text-red-500">*</span>
                    </label>
                    <select name="priority" required
                        class="w-full px-4 py-2.5 bg-white border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('priority') border-red-400 @enderror">
                        <option value="low"       @selected(old('priority','normal')=='low')>Low</option>
                        <option value="normal"    @selected(old('priority','normal')=='normal')>Normal</option>
                        <option value="high"      @selected(old('priority')=='high')>High</option>
                        <option value="urgent"    @selected(old('priority')=='urgent')>Urgent</option>
                        <option value="emergency" @selected(old('priority')=='emergency')>Emergency</option>
                    </select>
                </div>
            </div>

            <!-- Asset -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    Asset <span class="text-red-500">*</span>
                </label>
                <select name="asset_id" required
                    class="w-full px-4 py-2.5 bg-white border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('asset_id') border-red-400 @enderror">
                    <option value="">Select asset…</option>
                    @foreach($assets as $asset)
                        <option value="{{ $asset->id }}" @selected(old('asset_id')==$asset->id)>
                            {{ $asset->serial_number }} — {{ $asset->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Assign To -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Assign To</label>
                <select name="assigned_to"
                    class="w-full px-4 py-2.5 bg-white border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">Unassigned</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" @selected(old('assigned_to')==$user->id)>{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Scheduled Date & Est. Hours -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Scheduled Date</label>
                    <input type="date" name="scheduled_date" value="{{ old('scheduled_date') }}"
                        class="w-full px-4 py-2.5 bg-white border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Estimated Hours</label>
                    <input type="number" name="estimated_hours" min="0" step="0.5" value="{{ old('estimated_hours') }}"
                        placeholder="0"
                        class="w-full px-4 py-2.5 bg-white border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
            </div>

        </div>

        <!-- Actions -->
        <div class="flex items-center space-x-4 mt-6">
            <a href="{{ route('maintenance') }}"
               class="flex-1 text-center px-6 py-3 bg-white/10 backdrop-blur-sm border border-white/20 text-gray-700 font-medium rounded-xl hover:bg-white/20 transition-all duration-200">
                Cancel
            </a>
            <button type="submit"
                class="flex-1 px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all duration-200">
                Create Work Order
            </button>
        </div>

    </form>

</div>
@endsection
