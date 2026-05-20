@extends('layout')

@section('title', 'Edit ' . $asset->name)

@section('content')
    <div class="mb-8">
        <a href="{{ route('assets.show', $asset) }}" class="text-blue-600 hover:text-blue-800 text-sm">← Back to asset</a>
        <h1 class="text-3xl font-bold text-gray-900 mt-2">Edit Asset</h1>
    </div>

    <div class="bg-white rounded-lg shadow p-8 max-w-2xl">
        <form action="{{ route('assets.update', $asset) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-2">
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Lifecycle status</label>
                    <select id="status" name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        @foreach (['Ordered', 'Received', 'Active', 'Under Maintenance', 'Retired', 'Disposed'] as $st)
                            <option value="{{ $st }}" @selected(old('status', $asset->status) === $st)>{{ $st }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Asset Name *</label>
                    <input type="text" id="name" name="name" required value="{{ old('name', $asset->name) }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label for="serial_number" class="block text-sm font-medium text-gray-700 mb-2">Serial Number *</label>
                    <input type="text" id="serial_number" name="serial_number" required value="{{ old('serial_number', $asset->serial_number) }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label for="manufacturer" class="block text-sm font-medium text-gray-700 mb-2">Manufacturer</label>
                    <input type="text" id="manufacturer" name="manufacturer" value="{{ old('manufacturer', $asset->manufacturer) }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label for="model" class="block text-sm font-medium text-gray-700 mb-2">Model</label>
                    <input type="text" id="model" name="model" value="{{ old('model', $asset->model) }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label for="category_id" class="block text-sm font-medium text-gray-700 mb-2">Category *</label>
                    <select id="category_id" name="category_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected(old('category_id', $asset->category_id) === $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Location</label>
                    @include('components.location-picker', [
                        'locations'   => $locations,
                        'fieldName'   => 'location_id',
                        'oldValue'    => old('location_id', $asset->location_id),
                        'accentColor' => 'blue',
                    ])
                </div>

                <div>
                    <label for="department_id" class="block text-sm font-medium text-gray-700 mb-2">Department</label>
                    <select id="department_id" name="department_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="">—</option>
                        @foreach ($departments as $department)
                            <option value="{{ $department->id }}" @selected(old('department_id', $asset->department_id) === $department->id)>{{ $department->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="purchase_date" class="block text-sm font-medium text-gray-700 mb-2">Purchase Date *</label>
                    <input type="date" id="purchase_date" name="purchase_date" required value="{{ old('purchase_date', $asset->purchase_date?->format('Y-m-d')) }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label for="purchase_cost" class="block text-sm font-medium text-gray-700 mb-2">Purchase Cost *</label>
                    <input type="number" id="purchase_cost" name="purchase_cost" step="0.01" min="0" required value="{{ old('purchase_cost', $asset->purchase_cost) }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label for="salvage_value" class="block text-sm font-medium text-gray-700 mb-2">Salvage Value</label>
                    <input type="number" id="salvage_value" name="salvage_value" step="0.01" min="0" value="{{ old('salvage_value', $asset->salvage_value) }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label for="useful_life_years" class="block text-sm font-medium text-gray-700 mb-2">Useful Life (Years)</label>
                    <input type="number" id="useful_life_years" name="useful_life_years" min="1" max="50" value="{{ old('useful_life_years', $asset->useful_life_years) }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            <div class="mt-6">
                <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                <textarea id="description" name="description" rows="4" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">{{ old('description', $asset->description) }}</textarea>
            </div>

            <div class="mt-8 flex space-x-4">
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 font-medium">Save changes</button>
                <a href="{{ route('assets.show', $asset) }}" class="bg-gray-200 text-gray-800 px-6 py-2 rounded-lg hover:bg-gray-300 font-medium">Cancel</a>
            </div>
        </form>
    </div>
@endsection
