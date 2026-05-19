<!DOCTYPE html>
<html lang="en" x-data="{ sidebarOpen: false, darkMode: false }">
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
    
    <!-- Custom Styles for Page Transitions -->
    <style>
        .fade-in {
            animation: fadeIn 0.5s ease-in-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .page-transition {
            transition: all 0.3s ease-in-out;
        }
        
        .sidebar-item-active {
            background: rgba(255, 255, 255, 0.2);
            border-left: 3px solid rgb(59, 130, 246);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        
        .glass-card {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
        }
    </style>
    
    @stack('styles')
</head>
<body class="bg-gradient-to-br from-slate-50 via-gray-50 to-slate-100 min-h-screen font-['Inter'] antialiased">
    
    <!-- Background Effects -->
    <div class="fixed inset-0 -z-10 overflow-hidden">
        <div class="absolute -top-40 -right-40 w-80 h-80 bg-blue-400/20 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-40 -left-40 w-80 h-80 bg-purple-400/20 rounded-full blur-3xl"></div>
        <div class="absolute top-1/2 left-1/2 w-96 h-96 bg-indigo-400/10 rounded-full blur-3xl transform -translate-x-1/2 -translate-y-1/2"></div>
    </div>
    
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
