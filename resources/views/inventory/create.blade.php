@extends('layout')

@section('title', 'Add spare part')

@section('content')
    <div class="mb-8">
        <a href="{{ route('inventory.index') }}" class="text-blue-600 hover:text-blue-800 text-sm">← Inventory</a>
        <h1 class="text-3xl font-bold text-gray-900 mt-2">Add spare part</h1>
    </div>

    <div class="bg-white rounded-lg shadow p-8 max-w-2xl">
        <form action="{{ route('spare-parts.store') }}" method="POST">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Part number *</label>
                    <input name="part_number" required value="{{ old('part_number') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Part name *</label>
                    <input name="part_name" required value="{{ old('part_name') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                    <textarea name="description" rows="2" class="w-full px-4 py-2 border border-gray-300 rounded-lg">{{ old('description') }}</textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Supplier</label>
                    <input name="supplier" value="{{ old('supplier') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Unit cost *</label>
                    <input type="number" name="unit_cost" step="0.01" min="0" required value="{{ old('unit_cost') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Stock quantity *</label>
                    <input type="number" name="stock_quantity" min="0" required value="{{ old('stock_quantity', 0) }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Reorder point *</label>
                    <input type="number" name="reorder_point" min="0" required value="{{ old('reorder_point', 10) }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Reorder quantity *</label>
                    <input type="number" name="reorder_quantity" min="1" required value="{{ old('reorder_quantity', 20) }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                    <select name="category_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        <option value="">—</option>
                        @foreach ($categories as $c)
                            <option value="{{ $c->id }}" @selected(old('category_id') === $c->id)>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Storage location</label>
                    <select name="location_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        <option value="">—</option>
                        @foreach ($locations as $l)
                            <option value="{{ $l->id }}" @selected(old('location_id') === $l->id)>{{ $l->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="mt-8 flex gap-4">
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 font-medium">Save part</button>
                <a href="{{ route('inventory.index') }}" class="bg-gray-200 text-gray-800 px-6 py-2 rounded-lg">Cancel</a>
            </div>
        </form>
    </div>
@endsection
