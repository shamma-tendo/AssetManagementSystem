@php
    $currentRoute = request()->route()?->getName() ?? '';
    $navLink = function(string $route, string $icon, string $label) use ($currentRoute) {
        $isActive = $currentRoute === $route || str_starts_with($currentRoute, explode('.', $route)[0]);
        $base = 'flex items-center px-4 py-2.5 mx-2 rounded-lg text-sm font-medium transition-all duration-150 group';
        $active = $base . ' bg-blue-600 text-white shadow-md';
        $inactive = $base . ' text-slate-300 hover:bg-slate-700 hover:text-white';
        return [$isActive ? $active : $inactive, $isActive];
    };
@endphp

<aside class="w-64 min-h-screen bg-slate-800 flex flex-col shadow-xl" style="min-height: 100vh">
    <!-- Logo Area -->
    <div class="px-6 py-5 border-b border-slate-700">
        <div class="flex items-center space-x-3">
            <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m0 0l8 4m-8-4v10l8 4m0-10l8-4m-8 4v10l8-4m0 0l-8-4"></path>
                </svg>
            </div>
            <span class="text-white font-bold text-lg">AssetFlow</span>
        </div>
    </div>

    <nav class="flex-1 py-4 overflow-y-auto">
        <!-- Main Menu -->
        <div class="px-4 mb-2">
            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Main Menu</p>
        </div>

        @php [$cls, $active] = $navLink('dashboard', '', 'Dashboard'); @endphp
        <a href="{{ route('dashboard') }}" class="{{ $cls }}">
            <svg class="w-5 h-5 mr-3 {{ $active ? 'text-white' : 'text-slate-400 group-hover:text-white' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
            </svg>
            <span>Dashboard</span>
        </a>

        @auth
        @if(auth()->user()->isExecutive())
        <!-- CEO Workflow -->
        <div class="px-4 mt-6 mb-2">
            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">CEO Workflow</p>
        </div>

        @php [$cls, $active] = $navLink('ceo.inventory', '', 'Inventory'); @endphp
        <a href="{{ route('ceo.inventory') }}" class="{{ $cls }}">
            <svg class="w-5 h-5 mr-3 {{ $active ? 'text-white' : 'text-slate-400 group-hover:text-white' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
            </svg>
            <span>Manage Inventory</span>
        </a>

        @php
            $pendingCount = \App\Models\AssetRequest::where('organization_id', auth()->user()->organization_id)->where('status','pending')->count();
            [$cls, $active] = $navLink('ceo.requests', '', 'Requests');
        @endphp
        <a href="{{ route('ceo.requests') }}" class="{{ $cls }}">
            <svg class="w-5 h-5 mr-3 {{ $active ? 'text-white' : 'text-slate-400 group-hover:text-white' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            <span class="flex-1">Staff Requests</span>
            @if($pendingCount > 0)
                <span class="ml-auto bg-yellow-400 text-yellow-900 text-xs font-bold px-1.5 py-0.5 rounded-full">{{ $pendingCount }}</span>
            @endif
        </a>

        @php
            $unreviewedReports = \App\Models\AssetConditionReport::where('organization_id', auth()->user()->organization_id)->whereNull('reviewed_at')->count();
            [$cls, $active] = $navLink('ceo.reports', '', 'Reports');
        @endphp
        <a href="{{ route('ceo.reports') }}" class="{{ $cls }}">
            <svg class="w-5 h-5 mr-3 {{ $active ? 'text-white' : 'text-slate-400 group-hover:text-white' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <span class="flex-1">Condition Reports</span>
            @if($unreviewedReports > 0)
                <span class="ml-auto bg-red-400 text-white text-xs font-bold px-1.5 py-0.5 rounded-full">{{ $unreviewedReports }}</span>
            @endif
        </a>

        @php
            $pendingLeave = \App\Models\LeaveRequest::where('organization_id', auth()->user()->organization_id)->where('status','pending')->count();
            [$cls, $active] = $navLink('ceo.leave', '', 'Leave');
        @endphp
        <a href="{{ route('ceo.leave') }}" class="{{ $cls }}">
            <svg class="w-5 h-5 mr-3 {{ $active ? 'text-white' : 'text-slate-400 group-hover:text-white' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <span class="flex-1">Leave Requests</span>
            @if($pendingLeave > 0)
                <span class="ml-auto bg-purple-400 text-white text-xs font-bold px-1.5 py-0.5 rounded-full">{{ $pendingLeave }}</span>
            @endif
        </a>

        @elseif(auth()->user()->isStaff())
        <!-- Staff Workflow -->
        <div class="px-4 mt-6 mb-2">
            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">My Workspace</p>
        </div>

        @php [$cls, $active] = $navLink('staff.asset-registry', '', 'Asset Registry'); @endphp
        <a href="{{ route('staff.asset-registry') }}" class="{{ $cls }}">
            <svg class="w-5 h-5 mr-3 {{ $active ? 'text-white' : 'text-slate-400 group-hover:text-white' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
            </svg>
            <span>Asset Registry</span>
        </a>

        @php [$cls, $active] = $navLink('staff.request.form', '', 'Request Asset'); @endphp
        <a href="{{ route('staff.request.form') }}" class="{{ $cls }}">
            <svg class="w-5 h-5 mr-3 {{ $active ? 'text-white' : 'text-slate-400 group-hover:text-white' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            <span>Request an Asset</span>
        </a>

        @php [$cls, $active] = $navLink('staff.requests', '', 'My Requests'); @endphp
        <a href="{{ route('staff.requests') }}" class="{{ $cls }}">
            <svg class="w-5 h-5 mr-3 {{ $active ? 'text-white' : 'text-slate-400 group-hover:text-white' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            <span>My Requests</span>
        </a>

        @php [$cls, $active] = $navLink('staff.report', '', 'Asset Report'); @endphp
        <a href="{{ route('staff.report') }}" class="{{ $cls }}">
            <svg class="w-5 h-5 mr-3 {{ $active ? 'text-white' : 'text-slate-400 group-hover:text-white' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <span>Report Asset Issue</span>
        </a>

        @php [$cls, $active] = $navLink('staff.leave', '', 'Leave'); @endphp
        <a href="{{ route('staff.leave') }}" class="{{ $cls }}">
            <svg class="w-5 h-5 mr-3 {{ $active ? 'text-white' : 'text-slate-400 group-hover:text-white' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <span>Request Leave</span>
        </a>
        @endif
        @endauth

        <!-- Reports -->
        <div class="px-4 mt-6 mb-2">
            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Reports</p>
        </div>

        @php [$cls, $active] = $navLink('reports.financial', '', 'Financial'); @endphp
        <a href="{{ route('reports.financial') }}" class="{{ $cls }}">
            <svg class="w-5 h-5 mr-3 {{ $active ? 'text-white' : 'text-slate-400 group-hover:text-white' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <span>Financial Reports</span>
        </a>

        @php [$cls, $active] = $navLink('reports.analytics', '', 'Analytics'); @endphp
        <a href="{{ route('reports.analytics') }}" class="{{ $cls }}">
            <svg class="w-5 h-5 mr-3 {{ $active ? 'text-white' : 'text-slate-400 group-hover:text-white' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
            </svg>
            <span>Analytics</span>
        </a>

        <!-- Settings -->
        <div class="px-4 mt-6 mb-2">
            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Settings</p>
        </div>

        @php [$cls, $active] = $navLink('settings.categories', '', 'Categories'); @endphp
        <a href="{{ route('settings.categories') }}" class="{{ $cls }}">
            <svg class="w-5 h-5 mr-3 {{ $active ? 'text-white' : 'text-slate-400 group-hover:text-white' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
            </svg>
            <span>Categories</span>
        </a>

        @php [$cls, $active] = $navLink('settings.locations', '', 'Locations'); @endphp
        <a href="{{ route('settings.locations') }}" class="{{ $cls }}">
            <svg class="w-5 h-5 mr-3 {{ $active ? 'text-white' : 'text-slate-400 group-hover:text-white' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
            </svg>
            <span>Locations</span>
        </a>

        @php [$cls, $active] = $navLink('settings.audit-log', '', 'Audit Log'); @endphp
        <a href="{{ route('settings.audit-log') }}" class="{{ $cls }}">
            <svg class="w-5 h-5 mr-3 {{ $active ? 'text-white' : 'text-slate-400 group-hover:text-white' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
            </svg>
            <span>Audit Log</span>
        </a>
    </nav>

    <!-- Bottom user info -->
    @auth
    <div class="px-4 py-4 border-t border-slate-700">
        <div class="flex items-center space-x-3">
            <img class="w-8 h-8 rounded-full ring-2 ring-blue-500" src="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()->name) }}&background=3b82f6&color=fff" alt="">
            <div class="min-w-0">
                <p class="text-sm font-medium text-white truncate">{{ auth()->user()->name }}</p>
                <p class="text-xs text-slate-400 truncate">{{ auth()->user()->role?->name ?? 'User' }}</p>
            </div>
        </div>
    </div>
    @endauth
</aside>
