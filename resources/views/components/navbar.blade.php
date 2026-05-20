<nav class="bg-white dark:bg-slate-800 shadow transition-colors duration-300">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex items-center">
                <a href="{{ route('dashboard') }}" class="text-2xl font-bold text-blue-600 dark:text-blue-400">AEMS</a>
            </div>
            <div class="flex items-center space-x-4">
                @auth
                <!-- Dark Mode Toggle -->
                <button id="theme-toggle" class="p-2 rounded-lg text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-700 transition" aria-label="Toggle theme">
                    <svg id="sun-icon" class="w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                    <svg id="moon-icon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
                    </svg>
                </button>

                <div class="relative">
                    <button type="button" id="notificationBtn" class="relative p-2 text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white transition" aria-label="Notifications">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                        </svg>
                    </button>
                </div>

                <!-- Logout Button with Confirmation -->
                <button onclick="showLogoutModal()" class="text-sm text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white transition">
                    Log out
                </button>

                <div class="flex items-center space-x-2 text-gray-700 dark:text-gray-300">
                    <img class="w-8 h-8 rounded-full" src="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()->name) }}" alt="">
                    <span class="text-sm">{{ auth()->user()->name }}</span>
                </div>
                @endauth
            </div>
        </div>
    </div>
</nav>

<!-- Logout Confirmation Modal -->
<div id="logout-modal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeLogoutModal()"></div>
    <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-full max-w-md">
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl p-6 m-4 border border-gray-200 dark:border-slate-700">
            <div class="text-center">
                <div class="w-16 h-16 bg-amber-100 dark:bg-amber-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Confirm Logout</h3>
                <p class="text-gray-500 dark:text-gray-400 mb-6">Tap "Logout" again to confirm you want to exit.</p>
                
                <div class="flex space-x-3">
                    <button onclick="closeLogoutModal()" class="flex-1 px-4 py-2 bg-gray-100 dark:bg-slate-700 hover:bg-gray-200 dark:hover:bg-slate-600 text-gray-700 dark:text-gray-300 rounded-lg transition">
                        Cancel
                    </button>
                    <form id="logout-form" method="POST" action="{{ route('logout') }}" class="flex-1">
                        @csrf
                        <button type="submit" class="w-full px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg transition">
                            Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    (function() {
        // Apply theme immediately on load (also set in layout.blade.php head for FOUC prevention)
        var html = document.documentElement;
        var saved = localStorage.getItem('theme');
        if (saved === 'dark') {
            html.classList.add('dark');
        } else if (saved === 'light') {
            html.classList.remove('dark');
        } else {
            // Default to light if no preference saved
            html.classList.remove('dark');
        }
    })();

    function syncThemeIcons() {
        var isDark = document.documentElement.classList.contains('dark');
        var sunIcon = document.getElementById('sun-icon');
        var moonIcon = document.getElementById('moon-icon');
        if (!sunIcon || !moonIcon) return;
        if (isDark) {
            // Dark mode active: show sun (to switch to light)
            sunIcon.classList.remove('hidden');
            moonIcon.classList.add('hidden');
        } else {
            // Light mode active: show moon (to switch to dark)
            sunIcon.classList.add('hidden');
            moonIcon.classList.remove('hidden');
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        syncThemeIcons();

        var themeToggle = document.getElementById('theme-toggle');
        if (themeToggle) {
            themeToggle.addEventListener('click', function() {
                var html = document.documentElement;
                if (html.classList.contains('dark')) {
                    html.classList.remove('dark');
                    localStorage.setItem('theme', 'light');
                } else {
                    html.classList.add('dark');
                    localStorage.setItem('theme', 'dark');
                }
                syncThemeIcons();
            });
        }
    });

    // Logout Modal
    function showLogoutModal() {
        document.getElementById('logout-modal').classList.remove('hidden');
    }

    function closeLogoutModal() {
        document.getElementById('logout-modal').classList.add('hidden');
    }
</script>
