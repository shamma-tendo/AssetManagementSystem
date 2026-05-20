@extends('layout')
@section('title', 'Asset Inventory')

@section('content')
<div class="mb-6 flex justify-between items-center">
    <div>
        <h1 class="text-3xl font-bold text-gray-900">Asset Inventory</h1>
        <p class="text-gray-500 mt-1">All assets registered under {{ $org->name }}</p>
    </div>
    <button onclick="document.getElementById('add-asset-modal').style.display='flex'"
        class="px-5 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition">
        + Add Asset
    </button>
</div>

@if(session('success'))
    <div class="mb-4 bg-green-50 border border-green-200 text-green-800 rounded-lg px-4 py-3 text-sm">{{ session('success') }}</div>
@endif

<!-- Stats strip -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    @php
        $total = $assets->total();
        $active = $assets->getCollection()->where('status', 'Active')->count();
    @endphp
    <div class="bg-white rounded-lg shadow p-4 border-l-4 border-blue-500">
        <p class="text-sm text-gray-500">Total Assets</p>
        <p class="text-2xl font-bold text-gray-900">{{ $assets->total() }}</p>
    </div>
    <div class="bg-white rounded-lg shadow p-4 border-l-4 border-green-500">
        <p class="text-sm text-gray-500">Active</p>
        <p class="text-2xl font-bold text-green-700">{{ $assets->getCollection()->where('status', 'Active')->count() }}</p>
    </div>
    <div class="bg-white rounded-lg shadow p-4 border-l-4 border-yellow-500">
        <p class="text-sm text-gray-500">Under Maintenance</p>
        <p class="text-2xl font-bold text-yellow-700">{{ $assets->getCollection()->where('status', 'Under Maintenance')->count() }}</p>
    </div>
    <div class="bg-white rounded-lg shadow p-4 border-l-4 border-red-500">
        <p class="text-sm text-gray-500">Damaged / Stolen</p>
        <p class="text-2xl font-bold text-red-700">{{ $assets->getCollection()->whereIn('status', ['Damaged','Stolen','damaged','stolen'])->count() }}</p>
    </div>
</div>

<!-- Asset Table -->
<div class="bg-white rounded-xl shadow overflow-hidden">
    @if($assets->count())
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b">
            <tr>
                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Asset</th>
                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Category</th>
                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Location</th>
                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Status</th>
                <th class="text-right px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Value</th>
                <th class="px-5 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @foreach($assets as $asset)
            <tr class="hover:bg-gray-50">
                <td class="px-5 py-3">
                    <p class="font-semibold text-gray-900">{{ $asset->name }}</p>
                    <p class="text-xs text-gray-400">{{ $asset->serial_number }}</p>
                </td>
                <td class="px-5 py-3 text-gray-600">{{ $asset->category?->name ?? '—' }}</td>
                <td class="px-5 py-3 text-gray-600">{{ $asset->location?->name ?? '—' }}</td>
                <td class="px-5 py-3">
                    @php
                        $colors = ['Active'=>'green','Under Maintenance'=>'yellow','Damaged'=>'orange','Stolen'=>'red','Retired'=>'gray'];
                        $c = $colors[$asset->status] ?? 'gray';
                    @endphp
                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-{{ $c }}-100 text-{{ $c }}-800">{{ $asset->status }}</span>
                </td>
                <td class="px-5 py-3 text-right font-medium text-gray-800">
                    {{ $asset->current_value ? '$'.number_format($asset->current_value,0) : '—' }}
                </td>
                <td class="px-5 py-3 text-right">
                    <a href="{{ route('assets.show', $asset) }}" class="text-blue-600 hover:text-blue-800 text-xs font-medium">View →</a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div class="px-5 py-4 border-t">{{ $assets->links() }}</div>
    @else
    <div class="text-center py-16">
        <p class="text-4xl mb-3">📦</p>
        <p class="text-gray-500 font-medium">No assets yet. Add your first asset.</p>
        <button onclick="document.getElementById('add-asset-modal').style.display='flex'"
            class="mt-4 px-5 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">
            + Add Asset
        </button>
    </div>
    @endif
</div>

<!-- Add Asset Modal -->
<div id="add-asset-modal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl" style="display:flex; flex-direction:column; max-height:calc(100vh - 2rem); overflow:hidden;">

        <!-- Header -->
        <div style="flex-shrink:0;" class="flex justify-between items-center px-6 py-4 border-b bg-white rounded-t-xl">
            <h2 class="text-lg font-bold text-gray-900">Add New Asset</h2>
            <button onclick="document.getElementById('add-asset-modal').style.display='none'" class="text-gray-400 hover:text-gray-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <!-- Scrollable form fields -->
        <form id="add-asset-form" method="POST" action="{{ route('ceo.inventory.store') }}">
            @csrf
            <div style="overflow-y:auto; flex:1; padding:1.25rem 1.5rem;">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Asset Name *</label>
                        <input type="text" name="name" required value="{{ old('name') }}"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" placeholder="e.g., Dell Laptop, Office Chair">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Serial Number</label>
                        <input type="text" name="serial_number" value="{{ old('serial_number') }}"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" placeholder="Auto-generated if blank">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Manufacturer</label>
                        <input type="text" name="manufacturer" value="{{ old('manufacturer') }}"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" placeholder="e.g., Dell, HP, Samsung">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Model</label>
                        <input type="text" name="model" value="{{ old('model') }}"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                        <select name="category_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            <option value="">Select category</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Location</label>
                        @include('components.location-picker', ['locations' => $locations, 'fieldName' => 'location_id', 'accentColor' => 'blue'])
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Purchase Date</label>
                        <input type="date" name="purchase_date" value="{{ old('purchase_date') }}"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Purchase Cost ($)</label>
                        <input type="number" name="purchase_cost" step="0.01" min="0" value="{{ old('purchase_cost') }}"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" placeholder="0.00">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Current Value ($)</label>
                        <input type="number" name="current_value" step="0.01" min="0" value="{{ old('current_value') }}"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" placeholder="0.00">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            @foreach(['Active','Ordered','Received','Under Maintenance','Retired','Disposed'] as $s)
                                <option value="{{ $s }}" {{ old('status','Active') === $s ? 'selected' : '' }}>{{ $s }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea name="description" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" placeholder="Notes about this asset...">{{ old('description') }}</textarea>
                    </div>
                </div>
            </div>
        </form>

        <!-- Footer buttons — always pinned at bottom -->
        <div style="flex-shrink:0;" class="flex gap-3 px-6 py-4 border-t bg-gray-50 rounded-b-xl">
            <button type="submit" form="add-asset-form"
                class="flex-1 py-2.5 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition">
                Save Asset
            </button>
            <button type="button" onclick="document.getElementById('add-asset-modal').style.display='none'"
                class="flex-1 py-2.5 bg-gray-100 text-gray-700 font-medium rounded-lg hover:bg-gray-200 transition">
                Cancel
            </button>
        </div>

    </div>
</div>

<script>
@if($errors->any() || old('name'))
    document.getElementById('add-asset-modal').style.display = 'flex';
@endif
</script>
@endsection
