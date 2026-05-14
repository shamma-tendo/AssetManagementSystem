@extends('layout')

@section('title', 'Inventory Management')

@section('content')
    <div class="mb-8 flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Inventory Management</h1>
            <p class="mt-2 text-gray-600">Manage spare parts and consumables</p>
        </div>
        <a href="{{ route('spare-parts.create') }}" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 flex items-center space-x-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            <span>Add Part</span>
        </a>
    </div>

    <!-- Inventory Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-gray-500 text-sm font-medium">Total Inventory Value</p>
            <p class="text-3xl font-bold text-purple-600 mt-2" id="inventory-value">$0</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-gray-500 text-sm font-medium">Low Stock Items</p>
            <p class="text-3xl font-bold text-yellow-600 mt-2" id="low-stock-count">0</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-gray-500 text-sm font-medium">Out of Stock</p>
            <p class="text-3xl font-bold text-red-600 mt-2" id="out-of-stock-count">0</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-4 mb-6">
        <form class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                <input type="text" id="search" placeholder="Search parts..." class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Filter</label>
                <select id="filter" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="">All Items</option>
                    <option value="low">Low Stock</option>
                    <option value="out">Out of Stock</option>
                </select>
            </div>
            <div class="flex items-end">
                <button type="button" onclick="loadInventory()" class="w-full bg-gray-800 text-white px-4 py-2 rounded-lg hover:bg-gray-900">Search</button>
            </div>
        </form>
    </div>

    <!-- Parts Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Part Number</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Part Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Unit Cost</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stock</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reorder</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200" id="parts-table-body">
                <tr>
                    <td colspan="7" class="px-6 py-4 text-center text-gray-500">Loading parts...</td>
                </tr>
            </tbody>
        </table>
    </div>

    <script>
        function loadInventory() {
            const filter = document.getElementById('filter').value;
            const params = new URLSearchParams();

            if (filter === 'low') params.append('low_stock', '1');
            if (filter === 'out') params.append('out_stock', '1');

            aemsFetch(`/api/spare-parts?${params}`)
                .then(response => response.json())
                .then(data => {
                    const tbody = document.getElementById('parts-table-body');
                    tbody.innerHTML = '';

                    if (!data.success || !data.data.data || data.data.data.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="7" class="px-6 py-4 text-center text-gray-500">No parts found</td></tr>';
                        return;
                    }

                    data.data.data.forEach(part => {
                        let statusClass = 'bg-green-100 text-green-800';
                        let statusText = 'In Stock';

                        if (part.stock_quantity === 0) {
                            statusClass = 'bg-red-100 text-red-800';
                            statusText = 'Out of Stock';
                        } else if (part.stock_quantity <= part.reorder_point) {
                            statusClass = 'bg-yellow-100 text-yellow-800';
                            statusText = 'Low Stock';
                        }

                        const row = `<tr>
                            <td class="px-6 py-4 whitespace-nowrap"><strong>${part.part_number}</strong></td>
                            <td class="px-6 py-4 whitespace-nowrap">${part.part_name}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-gray-600">$${parseFloat(part.unit_cost).toFixed(2)}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-gray-600">${part.stock_quantity}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-gray-600">${part.reorder_point}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-block px-3 py-1 text-xs rounded-full font-medium ${statusClass}">
                                    ${statusText}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm space-x-2">
                                <a href="/spare-parts/${part.id}/edit" class="text-blue-600 hover:text-blue-800">Edit</a>
                                <button onclick="updateStock('${part.id}')" class="text-green-600 hover:text-green-800">Update Stock</button>
                            </td>
                        </tr>`;

                        tbody.innerHTML += row;
                    });
                })
                .catch(error => console.error('Error loading parts:', error));
        }

        function updateStock(partId) {
            const quantity = prompt('Enter quantity change:');
            if (!quantity) return;

            aemsFetch(`/api/spare-parts/${partId}/add-stock`, {
                method: 'POST',
                body: JSON.stringify({quantity: parseInt(quantity)})
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Stock updated successfully');
                        loadInventory();
                    } else {
                        alert('Error updating stock');
                    }
                });
        }

        // Load stats and parts on page load
        aemsFetch('/api/spare-parts/stats')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('inventory-value').textContent = '$' + parseFloat(data.data.inventory_value).toFixed(2);
                    document.getElementById('low-stock-count').textContent = data.data.low_stock_parts;
                    document.getElementById('out-of-stock-count').textContent = data.data.out_of_stock_parts;
                }
            });

        loadInventory();
    </script>
@endsection
