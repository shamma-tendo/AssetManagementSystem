@extends('layout')

@section('title', 'Add Asset')

@section('content')
<div class="mb-6">
    <a href="{{ route('household.dashboard') }}" class="text-green-600 hover:text-green-700 text-sm">← Back to Dashboard</a>
    <h1 class="text-3xl font-bold text-gray-900 mt-2">Add New Asset</h1>
    <p class="text-gray-500 mt-1">Track a new item in your personal inventory</p>
</div>

<div class="max-w-2xl bg-white rounded-xl shadow p-8">
    <form method="POST" action="{{ route('household.assets.store') }}">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="md:col-span-2">
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Asset Name *</label>
                <input type="text" name="name" id="name" required value="{{ old('name') }}"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
                    placeholder="e.g., MacBook Pro, Car, TV">
                @error('name') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="category_id" class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                <select name="category_id" id="category_id"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                    <option value="">Select category</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Location</label>
                @include('components.location-picker', [
                    'locations'   => $locations,
                    'fieldName'   => 'location_id',
                    'accentColor' => 'green',
                ])
            </div>

            <div>
                <label for="estimated_value" class="block text-sm font-medium text-gray-700 mb-1">Estimated Value ($)</label>
                <input type="number" name="estimated_value" id="estimated_value" step="0.01" min="0"
                    value="{{ old('estimated_value') }}"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                    placeholder="0.00">
            </div>

            <div>
                <label for="purchase_date" class="block text-sm font-medium text-gray-700 mb-1">Purchase Date</label>
                <input type="date" name="purchase_date" id="purchase_date" value="{{ old('purchase_date') }}"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
            </div>

            <div>
                <label for="serial_number" class="block text-sm font-medium text-gray-700 mb-1">Serial Number</label>
                <input type="text" name="serial_number" id="serial_number" value="{{ old('serial_number') }}"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                    placeholder="Optional">
            </div>

            <div>
                <label for="manufacturer" class="block text-sm font-medium text-gray-700 mb-1">Brand / Manufacturer</label>
                <input type="text" name="manufacturer" id="manufacturer" value="{{ old('manufacturer') }}"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                    placeholder="e.g., Apple, Samsung">
            </div>

            <div class="md:col-span-2">
                <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Notes / Description</label>
                <textarea name="description" id="description" rows="3"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                    placeholder="Any extra notes about this asset...">{{ old('description') }}</textarea>
            </div>
        </div>

        @error('error')
            <p class="mt-4 text-sm text-red-500">{{ $message }}</p>
        @enderror

        <div class="mt-8 flex gap-4">
            <button type="submit"
                class="flex-1 py-3 bg-green-600 text-white font-semibold rounded-lg hover:bg-green-700 transition">
                Save Asset
            </button>
            <a href="{{ route('household.dashboard') }}"
                class="flex-1 py-3 text-center bg-gray-100 text-gray-700 font-medium rounded-lg hover:bg-gray-200 transition">
                Cancel
            </a>
        </div>
    </form>
</div>
@endsection
