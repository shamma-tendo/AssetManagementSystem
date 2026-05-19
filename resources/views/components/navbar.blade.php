<!-- Top Navigation -->
<header x-data="navbarManager()" class="sticky top-0 z-40 bg-white/10 backdrop-blur-xl border-b border-white/20 shadow-lg">
    <div class="flex items-center justify-between px-6 py-4">

        <!-- Mobile Menu Button -->
        <button @click="sidebarOpen = true" class="lg:hidden p-2 rounded-lg hover:bg-white/10">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
            </svg>
        </button>

        <!-- Search Bar -->
        <div class="flex-1 max-w-xl mx-4 lg:mx-8 relative" @click.away="showSearch = false">
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
                <input type="text"
                       x-model="searchQuery"
                       @keydown.enter="goSearch()"
                       @input.debounce.300ms="liveSearch()"
                       @focus="showSearch = true"
                       placeholder="Search assets, IDs, or orders..."
                       class="w-full pl-10 pr-4 py-2 bg-white/20 border border-white/30 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-transparent placeholder-gray-500 text-gray-900 backdrop-blur-sm">
            </div>

            <!-- Live Results Dropdown -->
            <div x-show="showSearch && searchQuery.length >= 2"
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0 -translate-y-1"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 class="absolute top-full mt-2 w-full bg-white rounded-xl shadow-2xl border border-gray-100 z-50 overflow-hidden">

                <template x-if="searchResults.length > 0">
                    <div>
                        <div class="px-4 py-2 bg-gray-50 border-b text-xs font-semibold text-gray-500 uppercase tracking-wide">Assets</div>
                        <template x-for="result in searchResults" :key="result.uuid">
                            <a :href="'/asset-registry?q=' + encodeURIComponent(searchQuery)"
                               class="flex items-center px-4 py-3 hover:bg-blue-50 transition-colors border-b border-gray-50 last:border-0">
                                <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center mr-3 flex-shrink-0">
                                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <div class="text-sm font-medium text-gray-900 truncate" x-text="result.name"></div>
                                    <div class="text-xs text-gray-500" x-text="result.id + ' · ' + result.category"></div>
                                </div>
                            </a>
                        </template>
                    </div>
                </template>

                <template x-if="searchResults.length === 0 && !searching">
                    <div class="px-4 py-4 text-sm text-gray-500 text-center">
                        No results for "<span x-text="searchQuery"></span>"
                    </div>
                </template>

                <div class="px-4 py-2 bg-gray-50 border-t">
                    <button @click="goSearch()" class="text-xs text-blue-600 hover:text-blue-800 font-medium">
                        View all results for "<span x-text="searchQuery"></span>" →
                    </button>
                </div>
            </div>
        </div>

        <!-- Right Side Actions -->
        <div class="flex items-center space-x-2">

            <!-- Notifications -->
            <div class="relative" @click.away="showNotifications = false">
                <button @click="toggleNotifications()"
                        class="relative p-2 rounded-lg hover:bg-white/10 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                    </svg>
                    <span x-show="unreadCount > 0" class="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full"></span>
                </button>

                <!-- Notifications Dropdown -->
                <div x-show="showNotifications"
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0 -translate-y-1"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     class="absolute right-0 mt-2 w-80 bg-white rounded-2xl shadow-2xl border border-gray-100 z-50 overflow-hidden">
                    <div class="flex items-center justify-between px-4 py-3 border-b bg-gray-50">
                        <h3 class="font-semibold text-gray-900 text-sm">Notifications</h3>
                        <span x-show="unreadCount > 0"
                              class="px-2 py-0.5 bg-red-100 text-red-700 text-xs rounded-full font-medium"
                              x-text="unreadCount + ' new'"></span>
                    </div>

                    <div class="max-h-72 overflow-y-auto divide-y divide-gray-50">
                        <template x-if="notifications.length === 0">
                            <div class="px-4 py-8 text-center">
                                <svg class="w-8 h-8 text-gray-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                                </svg>
                                <p class="text-sm text-gray-500">No recent activity</p>
                            </div>
                        </template>

                        <template x-for="notif in notifications" :key="notif.id">
                            <div class="px-4 py-3 hover:bg-gray-50 transition-colors">
                                <div class="flex items-start space-x-3">
                                    <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5"
                                         :class="{
                                             'bg-green-100': notif.type === 'completed' || notif.type === 'closed',
                                             'bg-yellow-100': notif.type === 'in_progress' || notif.type === 'on_hold',
                                             'bg-blue-100': notif.type === 'requested' || notif.type === 'approved',
                                             'bg-red-100': notif.type === 'cancelled',
                                             'bg-gray-100': !['completed','closed','in_progress','on_hold','requested','approved','cancelled'].includes(notif.type)
                                         }">
                                        <svg class="w-4 h-4"
                                             :class="{
                                                 'text-green-600': notif.type === 'completed' || notif.type === 'closed',
                                                 'text-yellow-600': notif.type === 'in_progress' || notif.type === 'on_hold',
                                                 'text-blue-600': notif.type === 'requested' || notif.type === 'approved',
                                                 'text-red-600': notif.type === 'cancelled',
                                                 'text-gray-600': !['completed','closed','in_progress','on_hold','requested','approved','cancelled'].includes(notif.type)
                                             }"
                                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                        </svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-900 truncate" x-text="notif.message"></p>
                                        <p class="text-xs text-gray-500 mt-0.5" x-text="notif.asset + ' · ' + notif.time"></p>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div class="px-4 py-2 border-t bg-gray-50">
                        <a href="{{ route('maintenance') }}" class="text-xs text-blue-600 hover:text-blue-800 font-medium">
                            View all work orders →
                        </a>
                    </div>
                </div>
            </div>

            <!-- Settings -->
            <a href="{{ route('settings') }}"
               title="Settings"
               class="p-2 rounded-lg hover:bg-white/10 transition-colors {{ request()->routeIs('settings') ? 'bg-white/20' : '' }}">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
            </a>

            <!-- User Profile -->
            <div class="relative" @click.away="showProfile = false">
                <div class="flex items-center space-x-3 pl-3 ml-1 border-l border-white/20 cursor-pointer select-none"
                     @click="showProfile = !showProfile">
                    <div class="text-right hidden sm:block">
                        <p class="text-sm font-medium text-gray-900">{{ auth()->user()?->full_name ?? 'Admin User' }}</p>
                        <p class="text-xs text-gray-500">{{ strtoupper(auth()->user()?->role?->getDisplayName() ?? 'USER') }}</p>
                    </div>
                    <div class="w-9 h-9 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-full flex items-center justify-center shadow-lg flex-shrink-0">
                        <span class="text-white font-semibold text-sm">
                            {{ strtoupper(substr(auth()->user()?->first_name ?? 'A', 0, 1) . substr(auth()->user()?->last_name ?? 'U', 0, 1)) }}
                        </span>
                    </div>
                </div>

                <!-- Profile Dropdown -->
                <div x-show="showProfile"
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0 -translate-y-1"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     class="absolute right-0 mt-2 w-52 bg-white rounded-2xl shadow-2xl border border-gray-100 z-50 overflow-hidden">
                    <div class="px-4 py-3 bg-gradient-to-br from-blue-50 to-indigo-50 border-b">
                        <p class="text-sm font-semibold text-gray-900">{{ auth()->user()?->full_name ?? 'Admin User' }}</p>
                        <p class="text-xs text-gray-500 truncate">{{ auth()->user()?->email ?? '' }}</p>
                    </div>
                    <div class="py-1">
                        <a href="{{ route('settings') }}"
                           class="flex items-center px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                            <svg class="w-4 h-4 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            Settings
                        </a>
                    </div>
                    <div class="py-1 border-t">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                    class="flex items-center w-full px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 transition-colors">
                                <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                                </svg>
                                Sign Out
                            </button>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div>
</header>

<script>
function navbarManager() {
    return {
        searchQuery: '',
        searchResults: [],
        showSearch: false,
        searching: false,
        showNotifications: false,
        notifications: [],
        unreadCount: 0,
        showProfile: false,

        goSearch() {
            const q = this.searchQuery.trim();
            if (q) {
                window.location.href = '/asset-registry?q=' + encodeURIComponent(q);
            }
        },

        async liveSearch() {
            if (this.searchQuery.length < 2) {
                this.searchResults = [];
                return;
            }
            this.searching = true;
            try {
                const resp = await fetch('/search?q=' + encodeURIComponent(this.searchQuery));
                this.searchResults = await resp.json();
            } catch (e) {
                this.searchResults = [];
            } finally {
                this.searching = false;
            }
        },

        async toggleNotifications() {
            this.showNotifications = !this.showNotifications;
            this.showProfile = false;
            if (this.showNotifications && this.notifications.length === 0) {
                await this.fetchNotifications();
            }
        },

        async fetchNotifications() {
            try {
                const resp = await fetch('/notifications/recent');
                this.notifications = await resp.json();
                this.unreadCount = this.notifications.length;
            } catch (e) {
                this.notifications = [];
            }
        }
    };
}
</script>
