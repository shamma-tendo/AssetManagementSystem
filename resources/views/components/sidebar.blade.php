<!-- Sidebar -->
<aside class="hidden lg:flex lg:flex-shrink-0">
    <div class="flex flex-col w-64 h-full">
        <!-- Sidebar Content -->
        <div class="flex flex-col h-full bg-white/10 backdrop-blur-xl border-r border-white/20">
            
            <!-- Logo Section -->
            <div class="flex flex-col items-center px-6 py-8 border-b border-white/10">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center shadow-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-gray-900">AssetManager</h1>
                        <p class="text-xs text-gray-600 font-medium">INDUSTRIAL SYSTEMS</p>
                    </div>
                </div>
            </div>
            
            <!-- Navigation -->
            <nav class="flex-1 px-4 py-6 space-y-1">
                <a href="{{ route('dashboard') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-xl {{ request()->routeIs('dashboard*') ? 'bg-white/20 border border-white/30 text-gray-900 shadow-lg' : 'text-gray-700 hover:bg-white/10 hover:text-gray-900' }} transition-all duration-200">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                    </svg>
                    Dashboard
                </a>
                
                <a href="{{ route('asset-registry') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-xl {{ request()->routeIs('asset-registry*') ? 'bg-white/20 border border-white/30 text-gray-900 shadow-lg' : 'text-gray-700 hover:bg-white/10 hover:text-gray-900' }} transition-all duration-200">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                    </svg>
                    Asset Registry
                </a>
                
                <a href="{{ route('maintenance') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-xl {{ request()->routeIs('maintenance*') ? 'bg-white/20 border border-white/30 text-gray-900 shadow-lg' : 'text-gray-700 hover:bg-white/10 hover:text-gray-900' }} transition-all duration-200">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    Maintenance
                </a>
                
                <a href="{{ route('inventory') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-xl {{ request()->routeIs('inventory*') ? 'bg-white/20 border border-white/30 text-gray-900 shadow-lg' : 'text-gray-700 hover:bg-white/10 hover:text-gray-900' }} transition-all duration-200">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                    </svg>
                    Inventory
                </a>
                
                <a href="{{ route('analytics') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-xl {{ request()->routeIs('analytics*') ? 'bg-white/20 border border-white/30 text-gray-900 shadow-lg' : 'text-gray-700 hover:bg-white/10 hover:text-gray-900' }} transition-all duration-200">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                    Analytics
                </a>
                
                <a href="{{ route('settings') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-xl {{ request()->routeIs('settings*') ? 'bg-white/20 border border-white/30 text-gray-900 shadow-lg' : 'text-gray-700 hover:bg-white/10 hover:text-gray-900' }} transition-all duration-200">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path>
                    </svg>
                    Settings
                </a>
            </nav>
            
            <!-- Bottom Section -->
            <div class="px-4 py-4 space-y-2 border-t border-white/10">
                <a href="{{ route('settings') }}" class="flex items-center px-4 py-2 text-sm font-medium rounded-xl text-gray-700 hover:bg-white/10 hover:text-gray-900 transition-all duration-200">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Help Center
                </a>
                
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full flex items-center px-4 py-2 text-sm font-medium rounded-xl text-gray-700 hover:bg-white/10 hover:text-gray-900 transition-all duration-200">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                        </svg>
                        Logout
                    </button>
                </form>
                
                <!-- New Work Order Button -->
                <a href="{{ route('maintenance.work-orders.create') }}" class="w-full mt-4 px-4 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-medium rounded-xl shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all duration-200 flex items-center justify-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    New Work Order
                </a>
            </div>
        </div>
    </div>
</aside>

<!-- Mobile Sidebar -->
<div x-show="sidebarOpen" class="fixed inset-0 z-50 lg:hidden">
    <div class="fixed inset-0 bg-black/50" @click="sidebarOpen = false"></div>
    <div class="fixed left-0 top-0 h-full w-64 bg-white/95 backdrop-blur-xl border-r border-white/20">
        <div class="flex flex-col h-full">
            <!-- Mobile Header -->
            <div class="flex items-center justify-between p-4 border-b border-white/10">
                <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-lg font-bold text-gray-900">AssetManager</h1>
                        <p class="text-xs text-gray-600">INDUSTRIAL</p>
                    </div>
                </div>
                <button @click="sidebarOpen = false" class="p-2 rounded-lg hover:bg-white/10">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <!-- Mobile Navigation -->
            <nav class="flex-1 p-4 space-y-2">
                <a href="{{ route('dashboard') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-xl {{ request()->routeIs('dashboard*') ? 'bg-white/20 border border-white/30 text-gray-900' : 'text-gray-700 hover:bg-white/10' }} transition-all duration-200">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                    </svg>
                    Dashboard
                </a>
                <a href="{{ route('asset-registry') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-xl {{ request()->routeIs('asset-registry*') ? 'bg-white/20 border border-white/30 text-gray-900' : 'text-gray-700 hover:bg-white/10' }} transition-all duration-200">Asset Registry</a>
                <a href="{{ route('maintenance') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-xl {{ request()->routeIs('maintenance*') ? 'bg-white/20 border border-white/30 text-gray-900' : 'text-gray-700 hover:bg-white/10' }} transition-all duration-200">Maintenance</a>
                <a href="{{ route('inventory') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-xl {{ request()->routeIs('inventory*') ? 'bg-white/20 border border-white/30 text-gray-900' : 'text-gray-700 hover:bg-white/10' }} transition-all duration-200">Inventory</a>
                <a href="{{ route('analytics') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-xl text-gray-700 hover:bg-white/10">Analytics</a>
                <a href="{{ route('settings') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-xl text-gray-700 hover:bg-white/10">Settings</a>
            </nav>
        </div>
    </div>
</div>
