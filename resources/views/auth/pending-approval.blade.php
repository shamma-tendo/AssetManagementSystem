<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Awaiting Approval - AssetFlow</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 min-h-screen flex items-center justify-center font-sans antialiased px-4">
    <div class="max-w-md w-full backdrop-blur-xl bg-white/5 border border-white/10 p-8 rounded-2xl shadow-2xl relative overflow-hidden text-center">
        <!-- Background accents -->
        <div class="absolute -top-12 -left-12 w-24 h-24 bg-yellow-500 rounded-full mix-blend-multiply filter blur-xl opacity-20 animate-pulse"></div>
        <div class="absolute -bottom-12 -right-12 w-24 h-24 bg-blue-500 rounded-full mix-blend-multiply filter blur-xl opacity-20 animate-pulse"></div>

        <div class="w-20 h-20 bg-gradient-to-br from-yellow-400 to-orange-500 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-lg shadow-yellow-500/20">
            <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
        </div>

        <h1 class="text-3xl font-extrabold text-white mb-4">Awaiting Approval</h1>
        
        <p class="text-gray-300 mb-6 leading-relaxed">
            {{ $message ?? 'Your request to join the company has been submitted and is currently pending approval.' }}
        </p>

        <div class="bg-white/5 border border-white/10 rounded-xl p-4 mb-8 text-left">
            <h3 class="text-sm font-semibold text-yellow-400 mb-2 flex items-center">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                What happens next?
            </h3>
            <ul class="text-xs text-gray-400 space-y-2 list-disc list-inside">
                <li>Your company's CEO or CFO has been notified of your request.</li>
                <li>They will review your request and confirm your Staff role.</li>
                <li>Once approved, you will be able to log in and access your workspace dashboard.</li>
            </ul>
        </div>

        <div class="space-y-4">
            <a href="{{ route('login') }}" class="block w-full py-3 px-4 bg-gradient-to-r from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 text-white font-semibold rounded-xl transition-all shadow-lg shadow-blue-500/25 text-center">
                Return to Login
            </a>
            
            <form method="POST" action="{{ route('logout') }}" class="inline-block w-full">
                @csrf
                <button type="submit" class="w-full text-sm text-gray-400 hover:text-white transition font-medium">
                    Cancel & Sign Out
                </button>
            </form>
        </div>
    </div>
</body>
</html>
