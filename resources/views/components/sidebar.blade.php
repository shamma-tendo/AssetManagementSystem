<!-- Sidebar -->
<aside class="hidden lg:flex lg:flex-shrink-0">
    <div class="flex flex-col w-64 h-full">
        <div class="flex flex-col h-full" style="background:linear-gradient(180deg,#020c1a,#071022);border-right:1px solid rgba(255,255,255,.07);">
            
            <!-- Logo -->
            <div class="flex items-center gap-3 px-5 py-6" style="border-bottom:1px solid rgba(255,255,255,.07);">
                <div style="width:38px;height:38px;background:linear-gradient(135deg,#10b981,#3b82f6);border-radius:11px;display:flex;align-items:center;justify-content:center;box-shadow:0 0 18px rgba(16,185,129,.35);flex-shrink:0;">
                    <svg width="20" height="20" fill="none" stroke="white" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                </div>
                <div>
                    <div style="font-size:16px;font-weight:900;color:#f1f5f9;letter-spacing:-.02em;line-height:1.1;">EcoTrack</div>
                    <div style="font-size:9px;font-weight:700;color:#10b981;letter-spacing:.1em;text-transform:uppercase;">Industrial Intelligence</div>
                </div>
            </div>
            
            <!-- Navigation -->
            <nav class="flex-1 px-3 py-5" style="display:flex;flex-direction:column;gap:2px;">

                @php
                    $navItems = [
                        ['route'=>'dashboard',     'label'=>'Dashboard',     'icon'=>'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
                        ['route'=>'asset-registry','label'=>'Asset Registry','icon'=>'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10'],
                        ['route'=>'maintenance',   'label'=>'Maintenance',   'icon'=>'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z'],
                        ['route'=>'inventory',     'label'=>'Inventory',     'icon'=>'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'],
                        ['route'=>'analytics',     'label'=>'Analytics',     'icon'=>'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
                        ['route'=>'settings',      'label'=>'Settings',      'icon'=>'M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4'],
                    ];
                @endphp

                @foreach($navItems as $item)
                    @php $active = request()->routeIs($item['route'].'*'); @endphp
                    <a href="{{ route($item['route']) }}"
                       style="display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:10px;font-size:.875rem;font-weight:{{ $active ? '700' : '500' }};text-decoration:none;transition:all .18s;
                              {{ $active
                                  ? 'background:rgba(16,185,129,.15);color:#34d399;border-left:3px solid #10b981;box-shadow:0 0 14px rgba(16,185,129,.12);'
                                  : 'color:#64748b;border-left:3px solid transparent;' }}"
                       onmouseover="if(!this.dataset.active)this.style.background='rgba(255,255,255,.05)';if(!this.dataset.active)this.style.color='#94a3b8';"
                       onmouseout="if(!this.dataset.active)this.style.background='transparent';if(!this.dataset.active)this.style.color='#64748b';"
                       {{ $active ? 'data-active=1' : '' }}>
                        <svg style="width:18px;height:18px;flex-shrink:0;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}"/>
                        </svg>
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </nav>
            
            <!-- Bottom: user + actions -->
            <div class="px-3 py-4" style="border-top:1px solid rgba(255,255,255,.07);display:flex;flex-direction:column;gap:8px;">

                @can('role:technician')
                @endcan
                <a href="{{ route('maintenance.work-orders.create') }}"
                   style="display:flex;align-items:center;justify-content:center;gap:8px;padding:10px;background:linear-gradient(135deg,#059669,#10b981,#3b82f6);border:none;border-radius:10px;color:#fff;font-size:.85rem;font-weight:700;text-decoration:none;box-shadow:0 0 20px rgba(16,185,129,.25);transition:all .2s;"
                   onmouseover="this.style.transform='translateY(-1px)';this.style.boxShadow='0 0 30px rgba(16,185,129,.4)';"
                   onmouseout="this.style.transform='translateY(0)';this.style.boxShadow='0 0 20px rgba(16,185,129,.25)';">
                    <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    New Work Order
                </a>

                <!-- User card -->
                <div style="display:flex;align-items:center;gap:10px;padding:10px 12px;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.07);border-radius:10px;">
                    <div style="width:34px;height:34px;background:linear-gradient(135deg,#10b981,#3b82f6);border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <span style="color:#fff;font-weight:700;font-size:.8rem;">
                            {{ strtoupper(substr(auth()->user()?->first_name??'A',0,1).substr(auth()->user()?->last_name??'U',0,1)) }}
                        </span>
                    </div>
                    <div style="flex:1;min-width:0;">
                        <div style="font-size:.82rem;font-weight:700;color:#f1f5f9;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ auth()->user()?->full_name ?? 'User' }}</div>
                        <div style="font-size:.72rem;color:#10b981;font-weight:600;letter-spacing:.04em;text-transform:uppercase;">{{ auth()->user()?->role?->getDisplayName() ?? 'User' }}</div>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" title="Sign out"
                               style="background:none;border:none;cursor:pointer;color:#475569;padding:4px;display:flex;border-radius:6px;transition:color .2s;"
                               onmouseover="this.style.color='#f87171';" onmouseout="this.style.color='#475569';">
                            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</aside>

<!-- Mobile Sidebar -->
<div x-show="sidebarOpen" class="fixed inset-0 z-50 lg:hidden">
    <div class="fixed inset-0 bg-black/70" @click="sidebarOpen = false"></div>
    <div class="fixed left-0 top-0 h-full w-64 flex flex-col" style="background:#020c1a;border-right:1px solid rgba(255,255,255,.07);">
        <!-- Mobile Header -->
        <div class="flex items-center justify-between p-4" style="border-bottom:1px solid rgba(255,255,255,.07);">
            <div class="flex items-center gap-3">
                <div style="width:34px;height:34px;background:linear-gradient(135deg,#10b981,#3b82f6);border-radius:9px;display:flex;align-items:center;justify-content:center;">
                    <svg width="18" height="18" fill="none" stroke="white" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                </div>
                <div style="font-size:15px;font-weight:900;color:#f1f5f9;">EcoTrack</div>
            </div>
            <button @click="sidebarOpen = false" style="background:none;border:none;cursor:pointer;color:#64748b;padding:4px;">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <!-- Mobile Nav -->
        <nav class="flex-1 p-3" style="display:flex;flex-direction:column;gap:2px;overflow-y:auto;">
            @foreach($navItems ?? [] as $item)
                @php $active = request()->routeIs($item['route'].'*'); @endphp
                <a href="{{ route($item['route']) }}"
                   style="display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:10px;font-size:.875rem;font-weight:{{ $active?'700':'500' }};text-decoration:none;
                          {{ $active ? 'background:rgba(16,185,129,.15);color:#34d399;border-left:3px solid #10b981;' : 'color:#64748b;border-left:3px solid transparent;' }}">
                    <svg style="width:17px;height:17px;flex-shrink:0;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}"/></svg>
                    {{ $item['label'] }}
                </a>
            @endforeach
        </nav>
    </div>
</div>
