@php
    $currentRoute = request()->route()?->getName() ?? '';
    $navLink = function(string $route, string $label) use ($currentRoute) {
        $isActive = $currentRoute === $route || str_starts_with($currentRoute, explode('.', $route)[0]);
        $base = 'flex items-center px-4 py-2.5 mx-2 rounded-lg text-sm font-medium transition-all duration-150 group';
        $active = $base . ' bg-green-600 text-white shadow-md';
        $inactive = $base . ' text-slate-300 hover:bg-slate-700 hover:text-white';
        return [$isActive ? $active : $inactive, $isActive];
    };

    $org = auth()->user()->organization;
    $totalAssets = \App\Models\Asset::where('organization_id', $org?->id)->count();
    $totalValue  = \App\Models\Asset::where('organization_id', $org?->id)->sum('estimated_value');
    $expiringWarranties = \App\Models\AssetWarranty::where('organization_id', $org?->id)
        ->where('warranty_end_date', '>', now())
        ->where('warranty_end_date', '<=', now()->addDays(30))
        ->count();
@endphp

<aside class="w-64 min-h-screen bg-slate-800 flex flex-col shadow-xl" style="min-height:100vh">
    <!-- Logo -->
    <div class="px-6 py-5 border-b border-slate-700">
        <div class="flex items-center space-x-3">
            <div class="w-8 h-8 bg-green-600 rounded-lg flex items-center justify-center">
                <span class="text-white text-lg">🏠</span>
            </div>
            <span class="text-white font-bold text-lg">My Home</span>
        </div>
    </div>

    <nav class="flex-1 py-4 overflow-y-auto">
        <!-- Overview -->
        <div class="px-4 mb-2">
            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Overview</p>
        </div>

        @php [$cls, $active] = $navLink('household.dashboard', 'Dashboard'); @endphp
        <a href="{{ route('household.dashboard') }}" class="{{ $cls }}">
            <svg class="w-5 h-5 mr-3 {{ $active ? 'text-white' : 'text-slate-400 group-hover:text-white' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            <span>Dashboard</span>
        </a>

        <!-- My Assets -->
        <div class="px-4 mt-6 mb-2">
            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">My Assets</p>
        </div>

        @php [$cls, $active] = $navLink('household.assets.create', 'Add Asset'); @endphp
        <a href="{{ route('household.assets.create') }}" class="{{ $cls }}">
            <svg class="w-5 h-5 mr-3 {{ $active ? 'text-white' : 'text-slate-400 group-hover:text-white' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            <span>Add Asset</span>
        </a>

        @php [$cls, $active] = $navLink('household.insurance', 'Insurance'); @endphp
        <a href="{{ route('household.insurance') }}" class="{{ $cls }}">
            <svg class="w-5 h-5 mr-3 {{ $active ? 'text-white' : 'text-slate-400 group-hover:text-white' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
            </svg>
            <span>Insurance</span>
        </a>

        @php [$cls, $active] = $navLink('household.reminders', 'Reminders'); @endphp
        <a href="{{ route('household.reminders') }}" class="{{ $cls }}">
            <svg class="w-5 h-5 mr-3 {{ $active ? 'text-white' : 'text-slate-400 group-hover:text-white' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span>Maintenance Reminders</span>
        </a>

        @php [$cls, $active] = $navLink('household.photos', 'Photos'); @endphp
        <a href="{{ route('household.photos') }}" class="{{ $cls }}">
            <svg class="w-5 h-5 mr-3 {{ $active ? 'text-white' : 'text-slate-400 group-hover:text-white' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <span>Photo Storage</span>
        </a>

        <!-- Portfolio Stats -->
        <div class="px-4 mt-6 mb-2">
            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Portfolio</p>
        </div>

        <div class="mx-2 px-4 py-3 bg-slate-700/50 rounded-lg space-y-2">
            <div class="flex justify-between items-center text-sm">
                <span class="text-slate-400">Total Assets</span>
                <span class="text-white font-bold">{{ $totalAssets }}</span>
            </div>
            <div class="flex justify-between items-center text-sm">
                <span class="text-slate-400">Est. Value</span>
                <span class="text-green-400 font-bold">${{ number_format($totalValue, 0) }}</span>
            </div>
            @if($expiringWarranties > 0)
            <div class="flex justify-between items-center text-sm">
                <span class="text-slate-400">Expiring Warranties</span>
                <span class="text-orange-400 font-bold">{{ $expiringWarranties }}</span>
            </div>
            @endif
        </div>

        <!-- Print -->
        <div class="px-4 mt-6 mb-2">
            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Reports</p>
        </div>

        <button onclick="window.location.href='{{ route('household.dashboard') }}?print=1'" class="flex items-center px-4 py-2.5 mx-2 rounded-lg text-sm font-medium text-slate-300 hover:bg-slate-700 hover:text-white transition-all duration-150 group w-full">
            <svg class="w-5 h-5 mr-3 text-slate-400 group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
            </svg>
            <span>Print Asset Report</span>
        </button>
    </nav>

    <!-- User info -->
    @auth
    <div class="px-4 py-4 border-t border-slate-700">
        <div class="flex items-center space-x-3">
            <img class="w-8 h-8 rounded-full ring-2 ring-green-500"
                src="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()->name) }}&background=16a34a&color=fff" alt="">
            <div class="min-w-0">
                <p class="text-sm font-medium text-white truncate">{{ auth()->user()->name }}</p>
                <p class="text-xs text-slate-400 truncate">Household</p>
            </div>
        </div>
    </div>
    @endauth
</aside>
