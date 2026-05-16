@extends('layout')

@section('title', 'My Assets - Household')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100">
    <!-- Header -->
    <div class="bg-white border-b border-gray-200 sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">🏠 My Assets</h1>
                    <p class="text-gray-500 mt-1">Personal asset inventory and management</p>
                </div>
                <a href="{{ route('household.assets.create') }}" class="px-6 py-3 bg-green-600 text-white font-medium rounded-lg hover:bg-green-700 transition">
                    + Add Asset
                </a>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
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
                                    <p class="text-xs text-orange-700">Expires: {{ $warranty->expiry_date->format('M d, Y') }}</p>
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
                                    <p class="text-xs text-blue-700">Scheduled: {{ $maintenance->scheduled_date->format('M d, Y') }}</p>
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
@endsection
