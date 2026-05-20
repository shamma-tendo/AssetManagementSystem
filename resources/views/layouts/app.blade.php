<!DOCTYPE html>
<html lang="en" x-data="{ sidebarOpen: false }">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'AssetManager - Industrial Systems') | Asset Management System</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <!-- EcoTrack Dark Theme -->
    <style>
        /* ── Variables ─────────────────────────────────────────── */
        :root {
            --eco-bg:        #020817;
            --eco-surface:   rgba(255,255,255,.05);
            --eco-surface-2: rgba(255,255,255,.08);
            --eco-border:    rgba(255,255,255,.09);
            --eco-green:     #10b981;
            --eco-blue:      #3b82f6;
            --eco-text:      #f1f5f9;
            --eco-text-2:    #94a3b8;
            --eco-text-3:    #64748b;
        }

        /* ── Base ───────────────────────────────────────────────── */
        *, *::before, *::after { box-sizing: border-box; }
        body { background-color: var(--eco-bg) !important; color: var(--eco-text) !important; }

        /* ── Fade-in animation ──────────────────────────────────── */
        .fade-in { animation: fadeIn .4s ease; }
        @keyframes fadeIn { from { opacity:0; transform:translateY(8px); } to { opacity:1; transform:translateY(0); } }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* ── Background overrides ───────────────────────────────── */
        .bg-white          { background-color: var(--eco-surface)   !important; }
        .bg-gray-50        { background-color: rgba(255,255,255,.03) !important; }
        .bg-gray-100       { background-color: rgba(255,255,255,.05) !important; }
        .bg-gray-200       { background-color: rgba(255,255,255,.08) !important; }
        .bg-slate-50       { background-color: rgba(255,255,255,.03) !important; }
        .bg-slate-100      { background-color: rgba(255,255,255,.05) !important; }
        .bg-white\/10,
        .bg-white\/15,
        .bg-white\/20      { background-color: rgba(255,255,255,.06) !important; }

        /* ── Text overrides ─────────────────────────────────────── */
        .text-gray-900, .text-gray-800, .text-slate-900, .text-slate-800 { color: var(--eco-text)   !important; }
        .text-gray-700, .text-slate-700                                   { color: #cbd5e1           !important; }
        .text-gray-600, .text-slate-600                                   { color: var(--eco-text-2) !important; }
        .text-gray-500, .text-gray-400, .text-slate-500, .text-slate-400 { color: var(--eco-text-3) !important; }
        .placeholder-gray-500::placeholder, .placeholder-gray-400::placeholder { color: var(--eco-text-3) !important; }

        /* ── Border overrides ───────────────────────────────────── */
        .border-gray-100, .border-gray-200, .border-slate-100, .border-slate-200 { border-color: var(--eco-border) !important; }
        .border-gray-300, .border-slate-300                                       { border-color: rgba(255,255,255,.14) !important; }
        .divide-gray-50  > * + *, .divide-gray-100 > * + *                        { border-color: rgba(255,255,255,.06) !important; }

        /* ── Status badge backgrounds ───────────────────────────── */
        .bg-green-100  { background-color: rgba(16,185,129,.15)  !important; }
        .bg-yellow-100 { background-color: rgba(234,179,8,.15)   !important; }
        .bg-blue-100   { background-color: rgba(59,130,246,.15)  !important; }
        .bg-red-100    { background-color: rgba(239,68,68,.15)   !important; }
        .bg-purple-100 { background-color: rgba(168,85,247,.15)  !important; }
        .bg-orange-100 { background-color: rgba(249,115,22,.15)  !important; }
        .bg-indigo-100 { background-color: rgba(99,102,241,.15)  !important; }
        .bg-pink-100   { background-color: rgba(236,72,153,.15)  !important; }

        /* ── Status badge text ──────────────────────────────────── */
        .text-green-600,  .text-green-700,  .text-green-800  { color: #34d399 !important; }
        .text-yellow-600, .text-yellow-700, .text-yellow-800 { color: #fbbf24 !important; }
        .text-blue-600,   .text-blue-700,   .text-blue-800   { color: #60a5fa !important; }
        .text-red-600,    .text-red-700,    .text-red-800    { color: #f87171 !important; }
        .text-purple-600, .text-purple-700, .text-purple-800 { color: #c084fc !important; }
        .text-orange-600, .text-orange-700, .text-orange-800 { color: #fb923c !important; }
        .text-indigo-600, .text-indigo-700, .text-indigo-800 { color: #818cf8 !important; }
        .text-pink-600,   .text-pink-700,   .text-pink-800   { color: #f472b6 !important; }

        /* ── Ring/focus ─────────────────────────────────────────── */
        .focus\:ring-blue-500\/50:focus { box-shadow: 0 0 0 3px rgba(16,185,129,.2) !important; }

        /* ── Input / form controls ──────────────────────────────── */
        input:not([type="checkbox"]):not([type="radio"]):not([type="range"]):not([type="submit"]):not([type="button"]),
        select, textarea {
            background-color: rgba(255,255,255,.05) !important;
            border-color:     rgba(255,255,255,.1)  !important;
            color:            #f1f5f9               !important;
        }
        input::placeholder, textarea::placeholder { color: #475569 !important; }
        input:focus, select:focus, textarea:focus {
            border-color: rgba(16,185,129,.5) !important;
            box-shadow:   0 0 0 3px rgba(16,185,129,.1) !important;
            outline: none !important;
        }
        select option { background-color: #0f172a; color: #f1f5f9; }

        /* ── Shadow overrides ───────────────────────────────────── */
        .shadow, .shadow-sm, .shadow-md { box-shadow: 0 4px 16px rgba(0,0,0,.35) !important; }
        .shadow-lg, .shadow-xl          { box-shadow: 0 8px 32px  rgba(0,0,0,.45) !important; }
        .shadow-2xl                     { box-shadow: 0 20px 60px rgba(0,0,0,.55) !important; }

        /* ── Hover overrides ────────────────────────────────────── */
        .hover\:bg-gray-50:hover  { background-color: rgba(255,255,255,.04) !important; }
        .hover\:bg-gray-100:hover { background-color: rgba(255,255,255,.07) !important; }
        .hover\:bg-blue-50:hover  { background-color: rgba(59,130,246,.08)  !important; }
        .hover\:bg-red-50:hover   { background-color: rgba(239,68,68,.08)   !important; }

        /* ── Card / glass helpers ───────────────────────────────── */
        .glass-card {
            background:         rgba(255,255,255,.05) !important;
            backdrop-filter:    blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border:             1px solid rgba(255,255,255,.09) !important;
            box-shadow:         0 8px 32px rgba(0,0,0,.4) !important;
        }
        .sidebar-item-active {
            background:    rgba(16,185,129,.15) !important;
            border-left:   3px solid #10b981    !important;
            box-shadow:    0 0 12px rgba(16,185,129,.1) !important;
        }

        /* ── Table overrides ────────────────────────────────────── */
        table { color: var(--eco-text) !important; }
        th    { background-color: rgba(255,255,255,.04) !important; color: var(--eco-text-3) !important; }
        tr:hover td { background-color: rgba(255,255,255,.03) !important; }
        td    { border-color: rgba(255,255,255,.06) !important; }

        /* ── Scrollbar ──────────────────────────────────────────── */
        ::-webkit-scrollbar       { width:6px; height:6px; }
        ::-webkit-scrollbar-track { background: rgba(255,255,255,.02); }
        ::-webkit-scrollbar-thumb { background: rgba(255,255,255,.12); border-radius:4px; }
        ::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,.2); }
    </style>
    
    @stack('styles')
</head>
<body class="min-h-screen font-['Inter'] antialiased" style="background:#020817;color:#f1f5f9;">

    <!-- Background: grid + radial glows -->
    <div class="fixed inset-0 -z-10" style="background-image:linear-gradient(rgba(255,255,255,.013) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.013) 1px,transparent 1px);background-size:60px 60px;"></div>
    <div class="fixed inset-0 -z-10" style="background:radial-gradient(ellipse 800px 500px at 20% 0%,rgba(16,185,129,.055),transparent 70%),radial-gradient(ellipse 600px 400px at 80% 100%,rgba(59,130,246,.04),transparent 70%);pointer-events:none;"></div>
    
    <!-- Main Layout -->
    <div class="flex h-screen overflow-hidden">
        
        <!-- Sidebar -->
        @include('components.sidebar')
        
        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            
            <!-- Top Navigation -->
            @include('components.navbar')
            
            <!-- Page Content -->
            <main class="flex-1 overflow-y-auto p-6 transition-all duration-300 ease-in-out">
                <div class="fade-in">
                    @yield('content')
                </div>
            </main>
            
        </div>
    </div>
    
    <!-- Enhanced Navigation JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add smooth page transitions
    const links = document.querySelectorAll('a[href^="/"]');
    links.forEach(link => {
        link.addEventListener('click', function(e) {
            // Skip if it's an external link or has target="_blank"
            if (this.hostname !== window.location.hostname || this.target === '_blank') {
                return;
            }
            
            e.preventDefault();
            const href = this.getAttribute('href');
            
            // Add fade-out effect
            document.body.style.opacity = '0.8';
            document.body.style.transform = 'scale(0.98)';
            
            setTimeout(() => {
                window.location.href = href;
            }, 200);
        });
    });
    
    // Enhanced sidebar interactions
    const sidebarItems = document.querySelectorAll('nav a');
    sidebarItems.forEach(item => {
        item.addEventListener('mouseenter', function() {
            if (!this.classList.contains('sidebar-item-active')) {
                this.style.transform = 'translateX(4px)';
            }
        });
        
        item.addEventListener('mouseleave', function() {
            if (!this.classList.contains('sidebar-item-active')) {
                this.style.transform = 'translateX(0)';
            }
        });
    });
    
    // Mobile sidebar improvements
    const mobileMenuButton = document.querySelector('[data-mobile-menu]');
    if (mobileMenuButton) {
        mobileMenuButton.addEventListener('click', function() {
            document.body.classList.toggle('sidebar-open');
        });
    }
    
    // Add page load animation
    setTimeout(() => {
        document.querySelectorAll('.fade-in').forEach(el => {
            el.style.opacity = '1';
            el.style.transform = '';
        });
    }, 100);
});

// Alpine.js navigation component
function navigationManager() {
    return {
        sidebarOpen: false,
        currentPage: window.location.pathname,
        
        toggleSidebar() {
            this.sidebarOpen = !this.sidebarOpen;
        },
        
        closeSidebar() {
            this.sidebarOpen = false;
        },
        
        isActive(route) {
            return this.currentPage.includes(route);
        },
        
        navigate(url) {
            // Add navigation transition
            document.body.style.opacity = '0.8';
            document.body.style.transform = 'scale(0.98)';
            
            setTimeout(() => {
                window.location.href = url;
            }, 200);
        }
    }
}
</script>

@stack('scripts')
</body>
</html>
