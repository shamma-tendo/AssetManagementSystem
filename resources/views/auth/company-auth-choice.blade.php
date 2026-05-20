<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Welcome to AssetFlow - Company Asset Management</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gradient-to-br from-blue-900 via-slate-900 to-slate-900 min-h-screen">
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="max-w-md w-full">
            <!-- Logo -->
            <div class="text-center mb-8">
                <div class="w-16 h-16 bg-gradient-to-br from-blue-400 to-blue-600 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg shadow-blue-500/30">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5.5m0 0H9m0 0h5.5m0 0V7.413c0-.573.465-1.043 1.038-1.035a.999.999 0 011.042.1l5.338 4.381c.546.45.546 1.291 0 1.742l-5.338 4.38a.999.999 0 01-1.042.098 1.01 1.01 0 01-.038-1.035V7"></path>
                    </svg>
                </div>
                <h1 class="text-3xl font-bold text-white mb-2">Company Portal</h1>
                <p class="text-gray-400">Multi-tenant asset management for teams</p>
            </div>

            <!-- Cards -->
            <div class="space-y-4">
                <!-- Register New Company -->
                <a href="{{ route('company.register') }}" class="block group">
                    <div class="p-6 bg-gradient-to-br from-blue-500/20 via-blue-500/10 to-transparent border-2 border-blue-500/40 rounded-2xl backdrop-blur-xl hover:border-blue-400/60 hover:from-blue-500/30 transition-all duration-300 transform hover:scale-[1.02]">
                        <div class="flex items-center space-x-4">
                            <div class="w-14 h-14 bg-gradient-to-br from-blue-400 to-blue-600 rounded-xl flex items-center justify-center shadow-lg shadow-blue-500/30">
                                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5.5m0 0H9m0 0h5.5m0 0V7.413c0-.573.465-1.043 1.038-1.035a.999.999 0 011.042.1l5.338 4.381c.546.45.546 1.291 0 1.742l-5.338 4.38a.999.999 0 01-1.042.098 1.01 1.01 0 01-.038-1.035V7"></path>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <h3 class="text-xl font-bold text-white mb-1">Register New Company</h3>
                                <p class="text-gray-400 text-sm">Set up your organization and become CEO</p>
                            </div>
                            <svg class="w-6 h-6 text-blue-400 transform group-hover:translate-x-1 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </div>
                    </div>
                </a>

                <!-- Join Existing Company -->
                <a href="{{ route('company.join') }}" class="block group">
                    <div class="p-6 bg-gradient-to-br from-emerald-500/20 via-emerald-500/10 to-transparent border-2 border-emerald-500/40 rounded-2xl backdrop-blur-xl hover:border-emerald-400/60 hover:from-emerald-500/30 transition-all duration-300 transform hover:scale-[1.02]">
                        <div class="flex items-center space-x-4">
                            <div class="w-14 h-14 bg-gradient-to-br from-emerald-400 to-emerald-600 rounded-xl flex items-center justify-center shadow-lg shadow-emerald-500/30">
                                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <h3 class="text-xl font-bold text-white mb-1">Join Existing Company</h3>
                                <p class="text-gray-400 text-sm">Have a company code? Join your team</p>
                            </div>
                            <svg class="w-6 h-6 text-emerald-400 transform group-hover:translate-x-1 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Sign In Link -->
            <div class="mt-8 text-center">
                <p class="text-gray-400 text-sm">
                    Already have an account? 
                    <a href="{{ route('login') }}" class="text-blue-400 hover:text-blue-300 font-medium transition">Sign in</a>
                </p>
            </div>

            <!-- Back Link -->
            <div class="mt-4 text-center">
                <a href="{{ route('select-tenant-type') }}" class="text-gray-500 hover:text-gray-400 text-sm transition inline-flex items-center space-x-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    <span>Back to selection</span>
                </a>
            </div>

            <!-- Info Box -->
            <div class="mt-8 p-4 bg-white/5 rounded-xl border border-white/10">
                <div class="flex items-start space-x-3">
                    <svg class="w-5 h-5 text-blue-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p class="text-gray-400 text-xs">
                        First person to register becomes CEO with full oversight. Team members can join using the unique company code generated during setup.
                    </p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
