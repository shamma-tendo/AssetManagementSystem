@extends('layout')

@section('title', 'Inspections & Compliance')

@section('content')
    <div class="mb-8 flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Inspections & Compliance</h1>
            <p class="mt-2 text-gray-600">Schedule and track asset inspections</p>
        </div>
        <button onclick="openScheduleModal()" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 flex items-center space-x-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            <span>Schedule Inspection</span>
        </button>
    </div>

    <!-- Compliance Status Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-gray-500 text-sm font-medium">Scheduled</p>
            <p class="text-3xl font-bold text-blue-600 mt-2" id="scheduled-count">0</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-gray-500 text-sm font-medium">Completed</p>
            <p class="text-3xl font-bold text-green-600 mt-2" id="completed-count">0</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-gray-500 text-sm font-medium">Overdue</p>
            <p class="text-3xl font-bold text-red-600 mt-2" id="overdue-count">0</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-gray-500 text-sm font-medium">Compliance Met</p>
            <p class="text-3xl font-bold text-green-600 mt-2" id="compliance-met">0</p>
        </div>
    </div>

    <!-- Tabs -->
    <div class="border-b border-gray-200 mb-6">
        <nav class="flex space-x-8">
            <button onclick="switchTab('upcoming')" class="py-2 px-1 border-b-2 border-blue-600 font-medium text-blue-600 active-tab" id="tab-upcoming">
                Upcoming Inspections
            </button>
            <button onclick="switchTab('overdue')" class="py-2 px-1 border-b-2 border-transparent font-medium text-gray-600 hover:text-gray-900" id="tab-overdue">
                Overdue Inspections
            </button>
            <button onclick="switchTab('all')" class="py-2 px-1 border-b-2 border-transparent font-medium text-gray-600 hover:text-gray-900" id="tab-all">
                All Inspections
            </button>
        </nav>
    </div>

    <!-- Inspections List -->
    <div class="space-y-4" id="inspections-list">
        <div class="text-center text-gray-500 py-8">Loading inspections...</div>
    </div>

    <script>
        let currentTab = 'upcoming';

        function switchTab(tab) {
            currentTab = tab;

            // Update tab styling
            document.querySelectorAll('[id^="tab-"]').forEach(el => {
                el.classList.remove('border-blue-600', 'text-blue-600', 'font-medium');
                el.classList.add('border-transparent', 'text-gray-600');
            });

            document.getElementById(`tab-${tab}`).classList.add('border-blue-600', 'text-blue-600', 'font-medium');
            document.getElementById(`tab-${tab}`).classList.remove('border-transparent', 'text-gray-600');

            loadInspections();
        }

        function loadInspections() {
            let url = '/api/inspections';

            if (currentTab === 'upcoming') {
                url = '/api/inspections/upcoming';
            } else if (currentTab === 'overdue') {
                url = '/api/inspections/overdue';
            }

            aemsFetch(url)
                .then(response => response.json())
                .then(data => {
                    const list = document.getElementById('inspections-list');
                    list.innerHTML = '';

                    if (!data.success || !data.data || (data.data.data && data.data.data.length === 0)) {
                        list.innerHTML = '<div class="text-center text-gray-500 py-8">No inspections found</div>';
                        return;
                    }

                    const inspections = data.data.data || data.data;
                    const isArray = Array.isArray(inspections);
                    const items = isArray ? inspections : [inspections];

                    items.forEach(inspection => {
                        const statusColors = {
                            'Scheduled': 'bg-blue-100 text-blue-800',
                            'Completed': 'bg-green-100 text-green-800',
                            'In Progress': 'bg-orange-100 text-orange-800'
                        };

                        const card = `<div class="bg-white rounded-lg shadow p-6 border-l-4 border-blue-500">
                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="font-semibold text-gray-900">${inspection.inspection_type}</p>
                                    <p class="text-sm text-gray-600 mt-1">Asset: ${inspection.asset?.name || 'Unknown'}</p>
                                    <p class="text-sm text-gray-600">Scheduled: ${new Date(inspection.scheduled_date).toLocaleDateString()}</p>
                                    ${inspection.next_due_date ? `<p class="text-sm text-gray-600">Next Due: ${new Date(inspection.next_due_date).toLocaleDateString()}</p>` : ''}
                                </div>
                                <div class="text-right">
                                    <span class="inline-block px-3 py-1 text-xs rounded-full font-medium ${statusColors[inspection.status] || 'bg-gray-100 text-gray-800'}">
                                        ${inspection.status}
                                    </span>
                                    ${inspection.compliance_met !== null ? `
                                        <p class="mt-2 text-sm font-medium ${inspection.compliance_met ? 'text-green-600' : 'text-red-600'}">
                                            ${inspection.compliance_met ? '✓ Compliant' : '✗ Non-Compliant'}
                                        </p>
                                    ` : ''}
                                </div>
                            </div>
                        </div>`;

                        list.innerHTML += card;
                    });
                })
                .catch(error => console.error('Error loading inspections:', error));
        }

        function openScheduleModal() {
            alert('Schedule inspection modal would open here');
        }

        // Load stats
        aemsFetch('/api/inspections/stats')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('scheduled-count').textContent = data.data.scheduled;
                    document.getElementById('completed-count').textContent = data.data.completed;
                    document.getElementById('overdue-count').textContent = data.data.overdue;
                    document.getElementById('compliance-met').textContent = data.data.compliance_met;
                }
            });

        loadInspections();
    </script>
@endsection
