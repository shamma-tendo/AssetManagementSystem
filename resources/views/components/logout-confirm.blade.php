<!-- Logout Confirmation Modal -->
<div id="logout-modal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm transition-opacity" onclick="closeLogoutModal()"></div>
    <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-full max-w-md">
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl p-6 m-4 border border-gray-200 dark:border-slate-700">
            <div class="text-center">
                <div class="w-16 h-16 bg-amber-100 dark:bg-amber-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Confirm Logout</h3>
                <p class="text-gray-500 dark:text-gray-400 mb-6">Are you sure you want to exit? Tap "Logout" again to confirm.</p>
                
                <div class="flex space-x-3">
                    <button onclick="closeLogoutModal()" class="flex-1 px-4 py-2 bg-gray-100 dark:bg-slate-700 hover:bg-gray-200 dark:hover:bg-slate-600 text-gray-700 dark:text-gray-300 rounded-lg transition">
                        Cancel
                    </button>
                    <form method="POST" action="{{ route('logout') }}" class="flex-1">
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
    let logoutClickCount = 0;
    let logoutTimer = null;

    function handleLogoutClick() {
        logoutClickCount++;
        
        if (logoutClickCount === 1) {
            // First click - show modal
            document.getElementById('logout-modal').classList.remove('hidden');
            // Reset after 5 seconds if no second click
            logoutTimer = setTimeout(() => {
                logoutClickCount = 0;
                closeLogoutModal();
            }, 5000);
        } else if (logoutClickCount === 2) {
            // Second click - submit form
            clearTimeout(logoutTimer);
            document.getElementById('logout-form').submit();
        }
    }

    function closeLogoutModal() {
        document.getElementById('logout-modal').classList.add('hidden');
        logoutClickCount = 0;
        if (logoutTimer) clearTimeout(logoutTimer);
    }
</script>
