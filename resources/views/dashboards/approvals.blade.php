<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Team Approvals - {{ $organization->name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-900 min-h-screen">
    <!-- Navigation -->
    <nav class="bg-slate-800 border-b border-slate-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <span class="text-xl font-bold text-white">AssetFlow</span>
                    <span class="ml-4 text-slate-400">|</span>
                    <span class="ml-4 text-slate-300">{{ $organization->name }}</span>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-slate-400">CEO Dashboard</span>
                    <a href="{{ route('executive.dashboard') }}" class="text-blue-400 hover:text-blue-300">Dashboard</a>
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="text-slate-400 hover:text-white">Logout</button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-white mb-2">Team Member Approvals</h1>
            <p class="text-slate-400">Review and approve pending join requests</p>
        </div>

        <!-- Company Code Card -->
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 rounded-xl p-6 mb-8 shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-100 text-sm mb-1">Share this code with your team:</p>
                    <h2 class="text-3xl font-mono font-bold text-white tracking-wider">{{ $organization->code ?? 'N/A' }}</h2>
                </div>
                <div class="text-right">
                    <p class="text-blue-100 text-xs">Company Code</p>
                    <button onclick="navigator.clipboard.writeText('{{ $organization->code }}')" class="mt-2 px-4 py-2 bg-white/20 hover:bg-white/30 text-white rounded-lg text-sm transition">
                        Copy Code
                    </button>
                </div>
            </div>
        </div>

        <!-- Flash Messages -->
        @if(session('success'))
            <div class="mb-6 p-4 bg-green-500/20 border border-green-500/40 rounded-lg text-green-400">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="mb-6 p-4 bg-red-500/20 border border-red-500/40 rounded-lg text-red-400">
                {{ $errors->first() }}
            </div>
        @endif

        <!-- Pending Users -->
        <div class="bg-slate-800 rounded-xl border border-slate-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-700">
                <h2 class="text-lg font-semibold text-white">Pending Requests ({{ $pendingUsers->count() }})</h2>
            </div>

            @if($pendingUsers->isEmpty())
                <div class="p-12 text-center">
                    <svg class="w-16 h-16 text-slate-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <p class="text-slate-400">No pending approval requests</p>
                    <p class="text-slate-500 text-sm mt-1">Share your company code to invite team members</p>
                </div>
            @else
                <div class="divide-y divide-slate-700">
                    @foreach($pendingUsers as $pendingUser)
                    <div class="px-6 py-4 flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <div class="w-12 h-12 bg-slate-700 rounded-full flex items-center justify-center">
                                <span class="text-lg">👤</span>
                            </div>
                            <div>
                                <h3 class="text-white font-medium">{{ $pendingUser->name }}</h3>
                                <p class="text-slate-400 text-sm">{{ $pendingUser->email }}</p>
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-500/20 text-amber-400 mt-1">
                                    Requesting: {{ $pendingUser->requested_role }}
                                </span>
                            </div>
                        </div>

                        <div class="flex items-center space-x-3">
                            <!-- Approve Form -->
                            <form method="POST" action="{{ route('admin.approvals.approve', $pendingUser) }}" class="flex items-center space-x-2">
                                @csrf
                                <select name="role" class="bg-slate-700 border border-slate-600 text-white text-sm rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="Staff" selected>Staff</option>
                                </select>
                                <button type="submit" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm font-medium transition">
                                    Approve
                                </button>
                            </form>

                            <!-- Reject Form -->
                            <form method="POST" action="{{ route('admin.approvals.reject', $pendingUser) }}" class="inline">
                                @csrf
                                <button type="submit" class="px-4 py-2 bg-red-600/50 hover:bg-red-600 text-white rounded-lg text-sm font-medium transition">
                                    Reject
                                </button>
                            </form>
                        </div>
                    </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Active Team Members -->
        <div class="mt-8 bg-slate-800 rounded-xl border border-slate-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-700">
                <h2 class="text-lg font-semibold text-white">Active Team Members</h2>
            </div>
            @php
                $activeUsers = \App\Models\User::where('organization_id', $organization->id)
                    ->where('status', 'active')
                    ->where('is_approved', true)
                    ->get();
            @endphp
            <div class="divide-y divide-slate-700">
                @forelse($activeUsers as $activeUser)
                <div class="px-6 py-4 flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <div class="w-10 h-10 bg-slate-700 rounded-full flex items-center justify-center">
                            <span class="text-sm">👤</span>
                        </div>
                        <div>
                            <h3 class="text-white font-medium">{{ $activeUser->name }}</h3>
                            <p class="text-slate-400 text-sm">{{ $activeUser->email }}</p>
                        </div>
                    </div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $activeUser->role?->name == 'CEO' ? 'bg-purple-500/20 text-purple-400' : 'bg-green-500/20 text-green-400' }}">
                        {{ $activeUser->role?->name ?? 'No Role' }}
                    </span>
                </div>
                @empty
                <div class="p-6 text-center text-slate-500">
                    No active team members yet
                </div>
                @endforelse
            </div>
        </div>
    </div>
</body>
</html>
