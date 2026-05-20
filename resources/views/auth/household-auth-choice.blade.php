<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Welcome to AssetFlow - Personal Asset Management</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gradient-to-br from-purple-900 via-slate-900 to-slate-900 min-h-screen">
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="max-w-md w-full">
            <!-- Logo -->
            <div class="text-center mb-8">
                <div class="w-16 h-16 bg-gradient-to-br from-purple-400 to-purple-600 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg shadow-purple-500/30">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-3m0 0l7-4 7 4M5 9v10a1 1 0 001 1h12a1 1 0 001-1V9m-9 11l-4-4m0 0l-4 4m4-4v4"></path>
                    </svg>
                </div>
                <h1 class="text-3xl font-bold text-white mb-2">Welcome Home</h1>
                <p class="text-gray-400">Manage your personal assets with ease</p>
            </div>

            <!-- Cards -->
            <div class="space-y-4">
                <!-- Sign Up Card -->
                <a href="{{ route('household.register') }}" class="block group">
                    <div class="p-6 bg-gradient-to-br from-purple-500/20 via-purple-500/10 to-transparent border-2 border-purple-500/40 rounded-2xl backdrop-blur-xl hover:border-purple-400/60 hover:from-purple-500/30 transition-all duration-300 transform hover:scale-[1.02]">
                        <div class="flex items-center space-x-4">
                            <div class="w-14 h-14 bg-gradient-to-br from-purple-400 to-purple-600 rounded-xl flex items-center justify-center shadow-lg shadow-purple-500/30">
                                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <h3 class="text-xl font-bold text-white mb-1">Create Account</h3>
                                <p class="text-gray-400 text-sm">New to AssetFlow? Sign up and start tracking your assets</p>
                            </div>
                            <svg class="w-6 h-6 text-purple-400 transform group-hover:translate-x-1 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </div>
                    </div>
                </a>

                <!-- Sign In Card -->
                <a href="{{ route('login') }}" class="block group">
                    <div class="p-6 bg-gradient-to-br from-blue-500/20 via-blue-500/10 to-transparent border-2 border-blue-500/40 rounded-2xl backdrop-blur-xl hover:border-blue-400/60 hover:from-blue-500/30 transition-all duration-300 transform hover:scale-[1.02]">
                        <div class="flex items-center space-x-4">
                            <div class="w-14 h-14 bg-gradient-to-br from-blue-400 to-blue-600 rounded-xl flex items-center justify-center shadow-lg shadow-blue-500/30">
                                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <h3 class="text-xl font-bold text-white mb-1">Sign In</h3>
                                <p class="text-gray-400 text-sm">Already have an account? Log in to your dashboard</p>
                            </div>
                            <svg class="w-6 h-6 text-blue-400 transform group-hover:translate-x-1 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Back Link -->
            <div class="mt-8 text-center">
                <a href="{{ route('select-tenant-type') }}" class="text-gray-400 hover:text-white transition inline-flex items-center space-x-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    <span>Back to selection</span>
                </a>
            </div>

        </div>
    </div>
</body>
</html>
