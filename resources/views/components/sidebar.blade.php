<aside class="w-64 bg-gray-900 text-white shadow-lg">
    <nav class="mt-8">
        <div class="px-6 mb-6">
            <p class="text-xs font-semibold text-gray-400 uppercase">Main Menu</p>
        </div>

        <a href="{{ route('dashboard') }}" class="flex items-center px-6 py-3 hover:bg-gray-800 border-l-4 border-transparent hover:border-blue-500">
            <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-3m0 0l7-4 7 4M5 9v10a1 1 0 001 1h12a1 1 0 001-1V9m-9 11l4-4"></path>
            </svg>
            Dashboard
        </a>

        <div class="px-6 mt-6 mb-2">
            <p class="text-xs font-semibold text-gray-400 uppercase">Asset Management</p>
        </div>

        <a href="{{ route('assets.index') }}" class="flex items-center px-6 py-3 hover:bg-gray-800 border-l-4 border-transparent hover:border-blue-500">
            <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
            </svg>
            Asset Registry
        </a>

        <a href="{{ route('work-orders.index') }}" class="flex items-center px-6 py-3 hover:bg-gray-800 border-l-4 border-transparent hover:border-blue-500">
            <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
            </svg>
            Work Orders
        </a>

        <a href="{{ route('inventory.index') }}" class="flex items-center px-6 py-3 hover:bg-gray-800 border-l-4 border-transparent hover:border-blue-500">
            <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path>
            </svg>
            Inventory
        </a>

        <a href="{{ route('inspections.index') }}" class="flex items-center px-6 py-3 hover:bg-gray-800 border-l-4 border-transparent hover:border-blue-500">
            <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            Inspections
        </a>

        <div class="px-6 mt-6 mb-2">
            <p class="text-xs font-semibold text-gray-400 uppercase">Reports</p>
        </div>

        <a href="{{ route('reports.financial') }}" class="flex items-center px-6 py-3 hover:bg-gray-800 border-l-4 border-transparent hover:border-blue-500">
            <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            Financial Reports
        </a>

        <a href="{{ route('reports.analytics') }}" class="flex items-center px-6 py-3 hover:bg-gray-800 border-l-4 border-transparent hover:border-blue-500">
            <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
            </svg>
            Analytics
        </a>

        <div class="px-6 mt-6 mb-2">
            <p class="text-xs font-semibold text-gray-400 uppercase">Settings</p>
        </div>

        <a href="{{ route('settings.categories') }}" class="flex items-center px-6 py-3 hover:bg-gray-800 border-l-4 border-transparent hover:border-blue-500">
            <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path>
            </svg>
            Categories
        </a>

        <a href="{{ route('settings.locations') }}" class="flex items-center px-6 py-3 hover:bg-gray-800 border-l-4 border-transparent hover:border-blue-500">
            <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
            </svg>
            Locations
        </a>

        <a href="{{ route('settings.audit-log') }}" class="flex items-center px-6 py-3 hover:bg-gray-800 border-l-4 border-transparent hover:border-blue-500">
            <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            Audit Log
        </a>
    </nav>
</aside>
