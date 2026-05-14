@extends('layout')

@section('title', 'Analytics')

@section('content')
    <h1 class="text-3xl font-bold text-gray-900 mb-2">Analytics</h1>
    <p class="text-gray-600 mb-8">Summary metrics from the operational dashboard API.</p>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="font-semibold text-gray-900 mb-4">Assets</h2>
            <ul class="text-sm space-y-2 text-gray-700" id="analytics-assets">
                <li>Loading…</li>
            </ul>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="font-semibold text-gray-900 mb-4">Work orders</h2>
            <ul class="text-sm space-y-2 text-gray-700" id="analytics-wo">
                <li>Loading…</li>
            </ul>
        </div>
    </div>

    <script>
        Promise.all([
            aemsFetch('/api/dashboard').then(r => r.json()),
            aemsFetch('/api/work-orders/stats').then(r => r.json()),
        ]).then(([dash, wo]) => {
            if (dash.success && dash.data.summary) {
                const s = dash.data.summary;
                document.getElementById('analytics-assets').innerHTML =
                    `<li>Total: ${s.total_assets}</li><li>Active: ${s.active_assets}</li><li>Under maintenance: ${s.under_maintenance}</li><li>Retired: ${s.retired_assets}</li>`;
            }
            if (wo.success) {
                const w = wo.data;
                document.getElementById('analytics-wo').innerHTML =
                    `<li>Open: ${w.open}</li><li>In progress: ${w.in_progress}</li><li>On hold: ${w.on_hold}</li><li>Completed: ${w.completed}</li>`;
            }
        });
    </script>
@endsection
