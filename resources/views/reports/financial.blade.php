@extends('layout')

@section('title', 'Financial reports')

@section('content')
    <h1 class="text-3xl font-bold text-gray-900 mb-2">Financial reports</h1>
    <p class="text-gray-600 mb-8">Portfolio-level depreciation and book value (live from API).</p>

    <div class="bg-white rounded-lg shadow p-6 max-w-xl">
        <h2 class="text-lg font-semibold mb-4">Portfolio value</h2>
        <dl class="space-y-2 text-sm" id="portfolio-block">
            <div class="flex justify-between"><dt class="text-gray-500">Original cost</dt><dd id="pv-original">—</dd></div>
            <div class="flex justify-between"><dt class="text-gray-500">Current book value</dt><dd id="pv-current" class="font-semibold">—</dd></div>
            <div class="flex justify-between"><dt class="text-gray-500">Total depreciation</dt><dd id="pv-dep">—</dd></div>
            <div class="flex justify-between"><dt class="text-gray-500">Depreciation %</dt><dd id="pv-pct">—</dd></div>
        </dl>
    </div>

    <script>
        aemsFetch('/api/financial/portfolio-value')
            .then(r => r.json())
            .then(data => {
                if (!data.success) return;
                const d = data.data;
                document.getElementById('pv-original').textContent = '$' + Number(d.original_value).toLocaleString(undefined, { minimumFractionDigits: 2 });
                document.getElementById('pv-current').textContent = '$' + Number(d.current_value).toLocaleString(undefined, { minimumFractionDigits: 2 });
                document.getElementById('pv-dep').textContent = '$' + Number(d.total_depreciation).toLocaleString(undefined, { minimumFractionDigits: 2 });
                document.getElementById('pv-pct').textContent = Number(d.depreciation_percentage).toFixed(1) + '%';
            });
    </script>
@endsection
