@extends('layout')

@section('title', 'Work Orders')

@section('content')
    <div class="mb-8 flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Work Orders</h1>
            <p class="mt-2 text-gray-600">Manage asset maintenance work orders</p>
        </div>
        <button onclick="openCreateModal()" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 flex items-center space-x-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            <span>Create Work Order</span>
        </button>
    </div>

    <!-- Kanban Board -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
        <!-- Open Column -->
        <div class="bg-gray-100 rounded-lg p-4">
            <h3 class="font-semibold text-gray-900 mb-4">Open <span class="text-sm text-gray-500" id="count-open">0</span></h3>
            <div class="space-y-3" id="column-open">
                <div class="bg-white rounded-lg p-4 text-center text-gray-500 text-sm">Loading...</div>
            </div>
        </div>

        <!-- In Progress Column -->
        <div class="bg-gray-100 rounded-lg p-4">
            <h3 class="font-semibold text-gray-900 mb-4">In Progress <span class="text-sm text-gray-500" id="count-inprogress">0</span></h3>
            <div class="space-y-3" id="column-inprogress">
                <div class="bg-white rounded-lg p-4 text-center text-gray-500 text-sm">Loading...</div>
            </div>
        </div>

        <!-- On Hold Column -->
        <div class="bg-gray-100 rounded-lg p-4">
            <h3 class="font-semibold text-gray-900 mb-4">On Hold <span class="text-sm text-gray-500" id="count-onhold">0</span></h3>
            <div class="space-y-3" id="column-onhold">
                <div class="bg-white rounded-lg p-4 text-center text-gray-500 text-sm">Loading...</div>
            </div>
        </div>

        <!-- Completed Column -->
        <div class="bg-gray-100 rounded-lg p-4">
            <h3 class="font-semibold text-gray-900 mb-4">Completed <span class="text-sm text-gray-500" id="count-completed">0</span></h3>
            <div class="space-y-3" id="column-completed">
                <div class="bg-white rounded-lg p-4 text-center text-gray-500 text-sm">Loading...</div>
            </div>
        </div>

        <!-- Cancelled Column -->
        <div class="bg-gray-100 rounded-lg p-4">
            <h3 class="font-semibold text-gray-900 mb-4">Cancelled <span class="text-sm text-gray-500" id="count-cancelled">0</span></h3>
            <div class="space-y-3" id="column-cancelled">
                <div class="bg-white rounded-lg p-4 text-center text-gray-500 text-sm">Loading...</div>
            </div>
        </div>
    </div>

    <script>
        const statusMap = {
            'Open': 'column-open',
            'In Progress': 'column-inprogress',
            'On Hold': 'column-onhold',
            'Completed': 'column-completed',
            'Cancelled': 'column-cancelled'
        };

        const typeColors = {
            'Preventive': 'bg-blue-100 text-blue-800',
            'Corrective': 'bg-red-100 text-red-800',
            'Predictive': 'bg-purple-100 text-purple-800'
        };

        function loadWorkOrders() {
            aemsFetch('/api/work-orders')
                .then(response => response.json())
                .then(data => {
                    // Clear all columns
                    Object.values(statusMap).forEach(col => {
                        document.getElementById(col).innerHTML = '';
                    });

                    // Reset counts
                    ['open', 'inprogress', 'onhold', 'completed', 'cancelled'].forEach(status => {
                        document.getElementById(`count-${status}`).textContent = '0';
                    });

                    if (!data.success || !data.data.data) return;

                    const workOrders = data.data.data;
                    const counts = {};

                    // Group by status
                    workOrders.forEach(wo => {
                        const colId = statusMap[wo.status];
                        if (!colId) return;

                        counts[wo.status] = (counts[wo.status] || 0) + 1;

                        const card = `<div class="bg-white rounded-lg p-3 shadow-sm hover:shadow-md transition cursor-pointer" onclick="viewWorkOrder('${wo.id}')">
                            <p class="font-medium text-sm text-gray-900">${wo.work_order_number}</p>
                            <p class="text-xs text-gray-500 mt-1">${wo.asset?.name || 'Unknown Asset'}</p>
                            <span class="inline-block mt-2 text-xs px-2 py-1 rounded-full ${typeColors[wo.type] || 'bg-gray-100 text-gray-800'}">
                                ${wo.type}
                            </span>
                        </div>`;

                        document.getElementById(colId).innerHTML += card;
                    });

                    // Update counts
                    const countMap = {'Open': 'open', 'In Progress': 'inprogress', 'On Hold': 'onhold', 'Completed': 'completed', 'Cancelled': 'cancelled'};
                    Object.entries(countMap).forEach(([status, key]) => {
                        document.getElementById(`count-${key}`).textContent = counts[status] || '0';
                    });
                })
                .catch(error => console.error('Error loading work orders:', error));
        }

        function openCreateModal() {
            alert('Create work order modal would open here');
        }

        function viewWorkOrder(id) {
            window.location.href = `/work-orders/${id}`;
        }

        loadWorkOrders();
    </script>
@endsection
