@extends('layout')

@section('title', 'My Assets - Household')

@section('content')
<div>
    <!-- Page Header -->
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">🏠 My Assets</h1>
            <p class="text-gray-500 mt-1">Personal asset inventory — {{ auth()->user()->name }}</p>
        </div>
        <div class="flex gap-3">
            <button onclick="openPrintModal()"
                class="px-4 py-2 bg-slate-700 text-white text-sm font-medium rounded-lg hover:bg-slate-800 transition flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                Print Report
            </button>
            <a href="{{ route('household.assets.create') }}"
                class="px-5 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition">
                + Add Asset
            </a>
        </div>
    </div>

    <div>
        <!-- Overview Stats -->
        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-blue-500">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-gray-500 text-sm font-medium">Total Assets</p>
                        <p class="text-3xl font-bold text-gray-900 mt-2">{{ $assetStats['total'] }}</p>
                    </div>
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center text-2xl">📦</div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-purple-500">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-gray-500 text-sm font-medium">Total Value</p>
                        <p class="text-3xl font-bold text-purple-600 mt-2">${{ number_format($totalValue, 2) }}</p>
                    </div>
                    <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center text-2xl">💰</div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-green-500">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-gray-500 text-sm font-medium">Insured Assets</p>
                        <p class="text-3xl font-bold text-green-600 mt-2">{{ $assetStats['with_insurance'] }}</p>
                    </div>
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center text-2xl">🛡️</div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-orange-500">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-gray-500 text-sm font-medium">Under Warranty</p>
                        <p class="text-3xl font-bold text-orange-600 mt-2">{{ $assetStats['with_warranty'] }}</p>
                    </div>
                    <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center text-2xl">✅</div>
                </div>
            </div>
        </div>

        <div class="grid lg:grid-cols-3 gap-8">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-8">
                <!-- My Assets -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-6">My Asset Collection</h2>

                    @if($assets->count() > 0)
                        <div class="space-y-4">
                            @foreach($assets as $asset)
                                <a href="{{ route('household.assets.view', $asset->id) }}" class="block border border-gray-200 rounded-lg p-4 hover:bg-gray-50 hover:border-blue-300 transition">
                                    <div class="flex justify-between items-start">
                                        <div class="flex-1">
                                            <h3 class="font-bold text-gray-900">{{ $asset->name }}</h3>
                                            <p class="text-sm text-gray-600">{{ $asset->category->name ?? 'Uncategorized' }}</p>
                                            <p class="text-xs text-gray-500 mt-2">
                                                @if($asset->estimated_value)
                                                    Value: <span class="font-semibold">${{ number_format($asset->estimated_value, 2) }}</span>
                                                @endif
                                                @if($asset->location)
                                                    · Location: <span class="font-semibold">{{ $asset->location->name }}</span>
                                                @endif
                                            </p>
                                        </div>
                                        <div class="text-right">
                                            @if($asset->status === 'active')
                                                <span class="px-3 py-1 bg-green-100 text-green-800 text-xs font-medium rounded-full">Active</span>
                                            @else
                                                <span class="px-3 py-1 bg-gray-100 text-gray-800 text-xs font-medium rounded-full">{{ ucfirst($asset->status) }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </a>
                            @endforeach
                        </div>

                        <!-- Pagination -->
                        <div class="mt-6">
                            {{ $assets->links() }}
                        </div>
                    @else
                        <div class="text-center py-12">
                            <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m0 0l8 4m-8-4v10l8 4m0-10l8-4m-8 4v10l8-4m0 0l-8-4"></path>
                            </svg>
                            <p class="text-gray-500 text-lg mb-4">No assets yet</p>
                            <a href="{{ route('household.assets.create') }}" class="text-green-600 hover:text-green-700 font-medium">
                                Add your first asset →
                            </a>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Expiring Warranties Alert -->
                @if($expiringWarranties->count() > 0)
                    <div class="bg-orange-50 border-2 border-orange-200 rounded-lg p-4">
                        <h3 class="font-bold text-orange-900 mb-3">⚠️ Warranties Expiring Soon</h3>
                        <div class="space-y-2">
                            @foreach($expiringWarranties->take(5) as $warranty)
                                <div class="text-sm">
                                    <p class="font-medium text-orange-900">{{ $warranty->asset->name }}</p>
                                    <p class="text-xs text-orange-700">Expires: {{ $warranty->warranty_end_date->format('M d, Y') }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Upcoming Maintenance -->
                @if($upcomingMaintenance->count() > 0)
                    <div class="bg-blue-50 border-2 border-blue-200 rounded-lg p-4">
                        <h3 class="font-bold text-blue-900 mb-3">🔧 Upcoming Maintenance</h3>
                        <div class="space-y-2">
                            @foreach($upcomingMaintenance->take(5) as $maintenance)
                                <div class="text-sm">
                                    <p class="font-medium text-blue-900">{{ $maintenance->asset->name }}</p>
                                    <p class="text-xs text-blue-700">Scheduled: {{ $maintenance->next_service_date->format('M d, Y') }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Active Loans -->
                @if($activeLoans->count() > 0)
                    <div class="bg-purple-50 border-2 border-purple-200 rounded-lg p-4">
                        <h3 class="font-bold text-purple-900 mb-3">📤 Loaned Out</h3>
                        <div class="space-y-2">
                            @foreach($activeLoans as $loan)
                                <div class="text-sm">
                                    <p class="font-medium text-purple-900">{{ $loan->asset->name }}</p>
                                    <p class="text-xs text-purple-700">To: {{ $loan->loaned_to }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Insurance Policies -->
                @if($insurancePolicies->count() > 0)
                    <div class="bg-green-50 border-2 border-green-200 rounded-lg p-4">
                        <h3 class="font-bold text-green-900 mb-3">🛡️ Active Policies</h3>
                        <p class="text-2xl font-bold text-green-600 mb-2">{{ $insurancePolicies->count() }}</p>
                        <a href="{{ route('household.insurance') }}" class="text-sm text-green-700 hover:text-green-900 font-medium">
                            View all →
                        </a>
                    </div>
                @endif

                <!-- Quick Actions -->
                <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-lg p-6 border border-green-200">
                    <h3 class="font-semibold text-gray-900 mb-4">Quick Actions</h3>
                    <div class="space-y-2">
                        <a href="{{ route('household.assets.create') }}" class="block w-full py-2 px-4 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition text-center">
                            + Add Asset
                        </a>
                        <a href="{{ route('household.insurance') }}" class="block w-full py-2 px-4 bg-white text-green-600 text-sm font-medium rounded-lg border border-green-200 hover:bg-green-50 transition text-center">
                            Manage Insurance
                        </a>
                    </div>
                </div>

                <!-- Info Box -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <p class="text-sm text-blue-900">
                        <span class="font-bold">💡 Tip:</span> Keep your asset information up-to-date for better insurance coverage and claims.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Print Modal -->
<div id="print-modal" class="fixed inset-0 bg-black/60 z-50 hidden items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-3xl flex flex-col" style="max-height: calc(100vh - 2rem);">

        <!-- Modal Header — always visible -->
        <div class="flex justify-between items-center px-5 py-3 border-b bg-white rounded-t-xl flex-shrink-0">
            <div class="flex items-center gap-3">
                <button onclick="closePrintModal()"
                    class="flex items-center gap-1 px-3 py-1.5 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    Back
                </button>
                <h2 class="text-base font-bold text-gray-900">Asset Report — {{ auth()->user()->name }}</h2>
            </div>
            <button onclick="printReport()"
                class="flex items-center gap-2 px-4 py-1.5 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                Print / Save PDF
            </button>
        </div>

        <!-- Scrollable content -->
        <div id="print-content" class="overflow-y-auto p-5 flex-1">
            <!-- Report heading (print only shown via CSS) -->
            <div class="no-print-heading mb-4">
                <p class="text-xs text-gray-400">Preview below. Click <strong>Print / Save PDF</strong> to send to your printer or save as PDF.</p>
            </div>

            <!-- Summary Stats -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-5">
                <div class="text-center p-3 bg-blue-50 rounded-lg">
                    <p class="text-xl font-bold text-blue-700">{{ $assetStats['total'] }}</p>
                    <p class="text-xs text-gray-600 mt-0.5">Total Assets</p>
                </div>
                <div class="text-center p-3 bg-purple-50 rounded-lg">
                    <p class="text-xl font-bold text-purple-700">${{ number_format($totalValue, 2) }}</p>
                    <p class="text-xs text-gray-600 mt-0.5">Total Value</p>
                </div>
                <div class="text-center p-3 bg-green-50 rounded-lg">
                    <p class="text-xl font-bold text-green-700">{{ $assetStats['with_insurance'] }}</p>
                    <p class="text-xs text-gray-600 mt-0.5">Insured</p>
                </div>
                <div class="text-center p-3 bg-orange-50 rounded-lg">
                    <p class="text-xl font-bold text-orange-700">{{ $assetStats['with_warranty'] }}</p>
                    <p class="text-xs text-gray-600 mt-0.5">Under Warranty</p>
                </div>
            </div>

            <!-- Asset Table -->
            <table class="w-full text-sm border-collapse">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="text-left px-3 py-2 border border-gray-200 font-semibold">#</th>
                        <th class="text-left px-3 py-2 border border-gray-200 font-semibold">Asset Name</th>
                        <th class="text-left px-3 py-2 border border-gray-200 font-semibold">Category</th>
                        <th class="text-left px-3 py-2 border border-gray-200 font-semibold">Location</th>
                        <th class="text-left px-3 py-2 border border-gray-200 font-semibold">Status</th>
                        <th class="text-right px-3 py-2 border border-gray-200 font-semibold">Est. Value</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($allAssets as $i => $asset)
                    <tr class="{{ $i % 2 === 0 ? 'bg-white' : 'bg-gray-50' }}">
                        <td class="px-3 py-2 border border-gray-200 text-gray-500">{{ $i + 1 }}</td>
                        <td class="px-3 py-2 border border-gray-200 font-medium">{{ $asset->name }}</td>
                        <td class="px-3 py-2 border border-gray-200 text-gray-600">{{ $asset->category?->name ?? '—' }}</td>
                        <td class="px-3 py-2 border border-gray-200 text-gray-600">{{ $asset->location?->name ?? '—' }}</td>
                        <td class="px-3 py-2 border border-gray-200">{{ $asset->status }}</td>
                        <td class="px-3 py-2 border border-gray-200 text-right font-semibold">
                            {{ $asset->estimated_value ? '$' . number_format($asset->estimated_value, 2) : '—' }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-3 py-6 text-center text-gray-400 border border-gray-200">No assets to display</td>
                    </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="bg-gray-100 font-bold">
                        <td colspan="5" class="px-3 py-2 border border-gray-200 text-right">Total Portfolio Value:</td>
                        <td class="px-3 py-2 border border-gray-200 text-right text-green-700">${{ number_format($totalValue, 2) }}</td>
                    </tr>
                </tfoot>
            </table>

            <p class="text-xs text-gray-400 mt-3 text-right">Generated: {{ now()->format('F j, Y \a\t g:i A') }}</p>
        </div>
    </div>
</div>

<style>
@media print {
    /* Hide everything except the printable report */
    body > * { display: none !important; }
    #printable-report { display: block !important; }
}
#printable-report { display: none; }
</style>

<!-- Hidden printable version (used by window.print()) -->
<div id="printable-report">
    <style>
        body { font-family: Arial, sans-serif; color: #111; margin: 0; padding: 24px; }
        h1 { font-size: 18px; margin-bottom: 4px; }
        p.sub { font-size: 12px; color: #666; margin-bottom: 16px; }
        .stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-bottom: 18px; }
        .stat-box { padding: 10px; border-radius: 6px; text-align: center; border: 1px solid #e5e7eb; }
        .stat-box .num { font-size: 20px; font-weight: 700; }
        .stat-box .lbl { font-size: 11px; color: #6b7280; margin-top: 2px; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        th, td { border: 1px solid #d1d5db; padding: 6px 10px; }
        th { background: #f3f4f6; font-weight: 600; text-align: left; }
        tr:nth-child(even) td { background: #f9fafb; }
        tfoot td { background: #f3f4f6; font-weight: 700; }
        .footer { font-size: 10px; color: #9ca3af; text-align: right; margin-top: 10px; }
    </style>
    <h1>Asset Report — {{ auth()->user()->name }}</h1>
    <p class="sub">{{ auth()->user()->organization?->name ?? '' }} · Generated {{ now()->format('F j, Y \a\t g:i A') }}</p>
    <div class="stats">
        <div class="stat-box"><div class="num" style="color:#1d4ed8">{{ $assetStats['total'] }}</div><div class="lbl">Total Assets</div></div>
        <div class="stat-box"><div class="num" style="color:#7c3aed">${{ number_format($totalValue, 2) }}</div><div class="lbl">Total Value</div></div>
        <div class="stat-box"><div class="num" style="color:#16a34a">{{ $assetStats['with_insurance'] }}</div><div class="lbl">Insured</div></div>
        <div class="stat-box"><div class="num" style="color:#ea580c">{{ $assetStats['with_warranty'] }}</div><div class="lbl">Under Warranty</div></div>
    </div>
    <table>
        <thead>
            <tr>
                <th>#</th><th>Asset Name</th><th>Category</th><th>Location</th><th>Status</th><th style="text-align:right">Est. Value</th>
            </tr>
        </thead>
        <tbody>
            @forelse($allAssets as $i => $asset)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $asset->name }}</td>
                <td>{{ $asset->category?->name ?? '—' }}</td>
                <td>{{ $asset->location?->name ?? '—' }}</td>
                <td>{{ $asset->status }}</td>
                <td style="text-align:right">{{ $asset->estimated_value ? '$'.number_format($asset->estimated_value, 2) : '—' }}</td>
            </tr>
            @empty
            <tr><td colspan="6" style="text-align:center;color:#9ca3af">No assets</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5" style="text-align:right">Total Portfolio Value:</td>
                <td style="text-align:right;color:#16a34a">${{ number_format($totalValue, 2) }}</td>
            </tr>
        </tfoot>
    </table>
    <p class="footer">AssetFlow Household Report · {{ now()->format('F j, Y') }}</p>
</div>

<script>
    function openPrintModal() {
        const modal = document.getElementById('print-modal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }
    function closePrintModal() {
        const modal = document.getElementById('print-modal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
    function printReport() {
        // Show the hidden printable div, trigger print, then hide it again
        const report = document.getElementById('printable-report');
        report.style.display = 'block';
        window.print();
        report.style.display = 'none';
    }
</script>
@endsection
