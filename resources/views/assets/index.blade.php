@extends('layout')

@section('title', 'Asset Registry')

@section('content')
    <div class="mb-8 flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Asset Registry</h1>
            <p class="mt-2 text-gray-600">Manage all organizational assets</p>
        </div>
        <a href="{{ route('assets.create') }}" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 flex items-center space-x-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            <span>Add Asset</span>
        </a>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-4 mb-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                <input type="text" name="search" placeholder="Search by name..." class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" value="{{ request('search') }}">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                <select name="category_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="">All Categories</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="">All Statuses</option>
                    <option value="Active">Active</option>
                    <option value="Under Maintenance">Under Maintenance</option>
                    <option value="Retired">Retired</option>
                </select>
            </div>
            <div class="flex items-end space-x-2">
                <button type="submit" class="flex-1 bg-gray-800 text-white px-4 py-2 rounded-lg hover:bg-gray-900">Filter</button>
                <a href="{{ route('assets.index') }}" class="flex-1 bg-gray-200 text-gray-800 px-4 py-2 rounded-lg hover:bg-gray-300 text-center">Reset</a>
            </div>
        </form>
    </div>

    <!-- Assets Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Asset Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Serial Number</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Current Value</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200" id="assets-table-body">
                <tr>
                    <td colspan="7" class="px-6 py-4 text-center text-gray-500">Loading assets...</td>
                </tr>
            </tbody>
        </table>
    </div>

    <script>
        // Fetch and display assets
        function loadAssets() {
            aemsFetch('/api/assets')
                .then(response => response.json())
                .then(data => {
                    const tbody = document.getElementById('assets-table-body');
                    tbody.innerHTML = '';

                    if (!data.success || !data.data.data || data.data.data.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="7" class="px-6 py-4 text-center text-gray-500">No assets found</td></tr>';
                        return;
                    }

                    data.data.data.forEach(asset => {
                        const statusColors = {
                            'Active': 'bg-green-100 text-green-800',
                            'Under Maintenance': 'bg-orange-100 text-orange-800',
                            'Retired': 'bg-red-100 text-red-800',
                            'Ordered': 'bg-blue-100 text-blue-800'
                        };

                        const row = `<tr>
                            <td class="px-6 py-4 whitespace-nowrap"><strong>${asset.name}</strong></td>
                            <td class="px-6 py-4 whitespace-nowrap text-gray-600">${asset.serial_number}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-gray-600">${asset.category?.name || 'N/A'}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-gray-600">${asset.location?.name || 'N/A'}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-block px-3 py-1 text-xs rounded-full font-medium ${statusColors[asset.status] || 'bg-gray-100 text-gray-800'}">
                                    ${asset.status}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-gray-600">$${parseFloat(asset.current_value).toFixed(2)}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm space-x-2">
                                <a href="/assets/${asset.id}" class="text-blue-600 hover:text-blue-800">View</a>
                                <a href="/assets/${asset.id}/edit" class="text-blue-600 hover:text-blue-800">Edit</a>
                            </td>
                        </tr>`;

                        tbody.innerHTML += row;
                    });
                })
                .catch(error => {
                    console.error('Error loading assets:', error);
                    document.getElementById('assets-table-body').innerHTML = '<tr><td colspan="7" class="px-6 py-4 text-center text-red-500">Error loading assets</td></tr>';
                });
        }

        loadAssets();
    </script>
@endsection
