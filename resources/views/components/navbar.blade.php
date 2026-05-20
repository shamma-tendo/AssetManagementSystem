<!-- Top Navigation -->
<header x-data="navbarManager()" class="sticky top-0 z-40" style="background:rgba(2,8,23,.8);backdrop-filter:blur(20px);-webkit-backdrop-filter:blur(20px);border-bottom:1px solid rgba(255,255,255,.07);">
    <div class="flex items-center justify-between px-6 py-4">

        <!-- Mobile Menu Button -->
        <button @click="sidebarOpen = true" class="lg:hidden" style="background:none;border:none;cursor:pointer;padding:8px;border-radius:8px;color:#64748b;">
            <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>

        <!-- Search Bar -->
        <div class="flex-1 max-w-xl mx-4 lg:mx-8 relative" @click.away="showSearch = false">
            <div style="position:relative;">
                <div style="position:absolute;left:12px;top:50%;transform:translateY(-50%);pointer-events:none;">
                    <svg width="16" height="16" fill="none" stroke="#475569" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <input type="text"
                       x-model="searchQuery"
                       @keydown.enter="goSearch()"
                       @input.debounce.300ms="liveSearch()"
                       @focus="showSearch = true"
                       placeholder="Search assets, IDs, or orders…"
                       style="width:100%;padding:9px 14px 9px 38px;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.09);border-radius:10px;color:#f1f5f9;font-size:.875rem;font-family:inherit;outline:none;"
                       onfocus="this.style.borderColor='rgba(16,185,129,.5)';this.style.boxShadow='0 0 0 3px rgba(16,185,129,.1)';"
                       onblur="this.style.borderColor='rgba(255,255,255,.09)';this.style.boxShadow='none';">
            </div>

            <!-- Live Results Dropdown -->
            <div x-show="showSearch && searchQuery.length >= 2"
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0 -translate-y-1"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 style="position:absolute;top:100%;margin-top:8px;width:100%;background:#0d1829;border:1px solid rgba(255,255,255,.1);border-radius:14px;box-shadow:0 20px 60px rgba(0,0,0,.6);z-index:50;overflow:hidden;">

                <template x-if="searchResults.length > 0">
                    <div>
                        <div style="padding:8px 16px;font-size:.72rem;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:.07em;border-bottom:1px solid rgba(255,255,255,.06);">Assets</div>
                        <template x-for="result in searchResults" :key="result.uuid">
                            <a :href="'/asset-registry?q=' + encodeURIComponent(searchQuery)"
                               style="display:flex;align-items:center;padding:12px 16px;text-decoration:none;border-bottom:1px solid rgba(255,255,255,.04);transition:background .15s;"
                               onmouseover="this.style.background='rgba(16,185,129,.06)';" onmouseout="this.style.background='transparent';">
                                <div style="width:32px;height:32px;background:rgba(59,130,246,.15);border-radius:8px;display:flex;align-items:center;justify-content:center;margin-right:12px;flex-shrink:0;">
                                    <svg width="14" height="14" fill="none" stroke="#60a5fa" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                </div>
                                <div style="min-width:0;">
                                    <div style="font-size:.875rem;font-weight:600;color:#f1f5f9;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" x-text="result.name"></div>
                                    <div style="font-size:.75rem;color:#64748b;" x-text="result.id + ' · ' + result.category"></div>
                                </div>
                            </a>
                        </template>
                    </div>
                </template>

                <template x-if="searchResults.length === 0 && !searching">
                    <div style="padding:20px;text-align:center;font-size:.875rem;color:#475569;">
                        No results for "<span x-text="searchQuery"></span>"
                    </div>
                </template>

                <div style="padding:8px 16px;border-top:1px solid rgba(255,255,255,.06);">
                    <button @click="goSearch()" style="background:none;border:none;cursor:pointer;font-size:.78rem;color:#10b981;font-weight:600;font-family:inherit;">
                        View all results for "<span x-text="searchQuery"></span>" →
                    </button>
                </div>
            </div>
        </div>

        <!-- Right Side Actions -->
        <div class="flex items-center space-x-2">

            <!-- Notifications -->
            <div class="relative" @click.away="showNotifications = false">
                <button @click="toggleNotifications()" style="position:relative;background:none;border:none;cursor:pointer;padding:8px;border-radius:8px;color:#64748b;display:flex;transition:color .2s;" onmouseover="this.style.color='#94a3b8';" onmouseout="this.style.color='#64748b';">
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                    <span x-show="unreadCount > 0" style="position:absolute;top:6px;right:6px;width:8px;height:8px;background:#ef4444;border-radius:50%;border:2px solid #020817;"></span>
                </button>

                <!-- Notifications Dropdown -->
                <div x-show="showNotifications"
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0 -translate-y-1"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     style="position:absolute;right:0;margin-top:8px;width:320px;background:#0d1829;border:1px solid rgba(255,255,255,.1);border-radius:16px;box-shadow:0 20px 60px rgba(0,0,0,.6);z-index:50;overflow:hidden;">

                    <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 16px;border-bottom:1px solid rgba(255,255,255,.07);">
                        <span style="font-size:.875rem;font-weight:700;color:#f1f5f9;">Notifications</span>
                        <span x-show="unreadCount > 0" x-text="unreadCount + ' new'" style="padding:2px 8px;background:rgba(239,68,68,.15);color:#f87171;font-size:.72rem;font-weight:700;border-radius:20px;"></span>
                    </div>

                    <div style="max-height:288px;overflow-y:auto;">
                        <template x-if="notifications.length === 0">
                            <div style="padding:32px 16px;text-align:center;">
                                <svg width="28" height="28" fill="none" stroke="#334155" stroke-width="2" viewBox="0 0 24 24" style="margin:0 auto 8px;"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                                <p style="font-size:.85rem;color:#475569;">No recent activity</p>
                            </div>
                        </template>

                        <template x-for="notif in notifications" :key="notif.id">
                            <div style="padding:12px 16px;border-bottom:1px solid rgba(255,255,255,.04);transition:background .15s;"
                                 onmouseover="this.style.background='rgba(255,255,255,.03)';" onmouseout="this.style.background='transparent';">
                                <div style="display:flex;align-items:flex-start;gap:10px;">
                                    <div style="width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;"
                                         :style="{
                                             background: ['completed','closed'].includes(notif.type) ? 'rgba(16,185,129,.15)'
                                                       : ['in_progress','on_hold'].includes(notif.type) ? 'rgba(234,179,8,.15)'
                                                       : ['requested','approved'].includes(notif.type) ? 'rgba(59,130,246,.15)'
                                                       : notif.type === 'cancelled' ? 'rgba(239,68,68,.15)' : 'rgba(255,255,255,.06)'
                                         }">
                                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"
                                             :style="{
                                                 color: ['completed','closed'].includes(notif.type) ? '#34d399'
                                                      : ['in_progress','on_hold'].includes(notif.type) ? '#fbbf24'
                                                      : ['requested','approved'].includes(notif.type) ? '#60a5fa'
                                                      : notif.type === 'cancelled' ? '#f87171' : '#64748b'
                                             }">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                        </svg>
                                    </div>
                                    <div style="flex:1;min-width:0;">
                                        <p style="font-size:.85rem;font-weight:600;color:#f1f5f9;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" x-text="notif.message"></p>
                                        <p style="font-size:.75rem;color:#64748b;margin-top:2px;" x-text="notif.asset + ' · ' + notif.time"></p>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div style="padding:10px 16px;border-top:1px solid rgba(255,255,255,.07);">
                        <a href="{{ route('maintenance') }}" style="font-size:.78rem;color:#10b981;font-weight:600;text-decoration:none;">View all work orders →</a>
                    </div>
                </div>
            </div>

            <!-- Settings -->
            <a href="{{ route('settings') }}" title="Settings"
               style="background:{{ request()->routeIs('settings') ? 'rgba(16,185,129,.12)' : 'none' }};border:none;padding:8px;border-radius:8px;color:{{ request()->routeIs('settings') ? '#34d399' : '#64748b' }};display:flex;text-decoration:none;transition:color .2s;"
               onmouseover="this.style.color='#94a3b8';" onmouseout="this.style.color='{{ request()->routeIs('settings') ? '#34d399' : '#64748b' }}';">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            </a>

            <!-- User Profile -->
            <div class="relative" @click.away="showProfile = false">
                <div style="display:flex;align-items:center;gap:10px;padding-left:12px;margin-left:4px;border-left:1px solid rgba(255,255,255,.07);cursor:pointer;user-select:none;"
                     @click="showProfile = !showProfile">
                    <div class="hidden sm:block" style="text-align:right;">
                        <p style="font-size:.82rem;font-weight:700;color:#f1f5f9;line-height:1.2;">{{ auth()->user()?->full_name ?? 'User' }}</p>
                        <p style="font-size:.72rem;color:#10b981;font-weight:600;letter-spacing:.04em;text-transform:uppercase;">{{ auth()->user()?->role?->getDisplayName() ?? 'User' }}</p>
                    </div>
                    <div style="width:36px;height:36px;background:linear-gradient(135deg,#10b981,#3b82f6);border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 0 14px rgba(16,185,129,.3);">
                        <span style="color:#fff;font-weight:700;font-size:.82rem;">
                            {{ strtoupper(substr(auth()->user()?->first_name??'A',0,1).substr(auth()->user()?->last_name??'U',0,1)) }}
                        </span>
                    </div>
                </div>

                <!-- Profile Dropdown -->
                <div x-show="showProfile"
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0 -translate-y-1"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     style="position:absolute;right:0;margin-top:8px;width:220px;background:#0d1829;border:1px solid rgba(255,255,255,.1);border-radius:16px;box-shadow:0 20px 60px rgba(0,0,0,.6);z-index:50;overflow:hidden;">

                    <div style="padding:14px 16px;background:linear-gradient(135deg,rgba(16,185,129,.08),rgba(59,130,246,.06));border-bottom:1px solid rgba(255,255,255,.07);">
                        <p style="font-size:.875rem;font-weight:700;color:#f1f5f9;">{{ auth()->user()?->full_name ?? 'User' }}</p>
                        <p style="font-size:.75rem;color:#64748b;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ auth()->user()?->email ?? '' }}</p>
                    </div>

                    <div style="padding:6px;">
                        <a href="{{ route('settings') }}"
                           style="display:flex;align-items:center;gap:10px;padding:9px 12px;border-radius:10px;font-size:.875rem;color:#94a3b8;text-decoration:none;transition:all .15s;"
                           onmouseover="this.style.background='rgba(255,255,255,.05)';this.style.color='#f1f5f9';" onmouseout="this.style.background='transparent';this.style.color='#94a3b8';">
                            <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            Settings
                        </a>
                    </div>

                    <div style="padding:6px;border-top:1px solid rgba(255,255,255,.07);">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                    style="display:flex;align-items:center;gap:10px;width:100%;padding:9px 12px;border-radius:10px;font-size:.875rem;color:#f87171;background:none;border:none;cursor:pointer;font-family:inherit;transition:all .15s;"
                                    onmouseover="this.style.background='rgba(239,68,68,.08)';" onmouseout="this.style.background='transparent';">
                                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
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
