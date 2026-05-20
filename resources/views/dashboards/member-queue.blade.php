@extends('layout')

@section('title', 'Member Approval Queue')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="flex justify-between items-center mb-8 border-b border-white/10 pb-6">
            <div>
                <h1 class="text-3xl font-extrabold text-white">Member Approval Queue</h1>
                <p class="text-gray-400 mt-1">Review, assign roles, and authorize pending team members.</p>
            </div>
            <a href="{{ route('executive.dashboard') }}" class="text-blue-400 hover:text-blue-300 font-semibold flex items-center transition">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Dashboard
            </a>
        </div>

        <!-- Session Status & Errors -->
        @if (session('success'))
            <div class="bg-green-500/10 border border-green-500/30 text-green-400 px-4 py-3 rounded-xl mb-6 text-sm">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="bg-red-500/10 border border-red-500/30 text-red-400 px-4 py-3 rounded-xl mb-6 text-sm">
                {{ session('error') }}
            </div>
        @endif

        <!-- Pending Members List -->
        @if($pendingUsers->count() > 0)
            <div class="space-y-6">
                @foreach($pendingUsers as $pendingUser)
                    <div class="backdrop-blur-xl bg-white/5 border border-white/10 p-6 rounded-2xl shadow-xl flex flex-col md:flex-row md:items-center justify-between gap-6 transition hover:border-white/20">
                        <!-- User details -->
                        <div class="flex-1 space-y-3">
                            <div class="flex items-center space-x-3">
                                <div class="w-12 h-12 bg-gradient-to-br from-blue-400 to-purple-600 rounded-xl flex items-center justify-center text-white font-bold text-lg shadow-lg">
                                    {{ strtoupper(substr($pendingUser->name, 0, 1)) }}
                                </div>
                                <div>
                                    <h2 class="text-xl font-bold text-white">{{ $pendingUser->name }}</h2>
                                    <p class="text-sm text-gray-400">{{ $pendingUser->email }}</p>
                                </div>
                            </div>
                            
                            <div class="flex flex-wrap gap-4 text-xs text-gray-400 pt-2">
                                <span class="flex items-center bg-white/5 px-3 py-1 rounded-full border border-white/5">
                                    <svg class="w-4 h-4 mr-1 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                    Requested: <strong class="text-white ml-1">{{ $pendingUser->requested_role ?? 'Staff' }}</strong>
                                </span>
                                <span class="flex items-center bg-white/5 px-3 py-1 rounded-full border border-white/5">
                                    <svg class="w-4 h-4 mr-1 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    Applied: {{ $pendingUser->created_at->diffForHumans() }}
                                </span>
                            </div>
                        </div>

                        <!-- Decision and assignment form -->
                        <div class="flex items-center gap-4 flex-wrap md:flex-nowrap border-t border-white/5 pt-4 md:border-t-0 md:pt-0">
                            <form action="{{ route('executive.members.approve', $pendingUser) }}" method="POST" class="flex items-center gap-2 flex-wrap">
                                @csrf
                                <div class="relative">
                                    <select name="role_id" required
                                            class="appearance-none bg-slate-900 border border-white/10 rounded-xl px-4 py-2.5 pr-8 text-sm text-white focus:outline-none focus:border-blue-500 transition">
                                        @foreach($availableRoles as $role)
                                            <option value="{{ $role->id }}" 
                                                {{ (strtolower($pendingUser->requested_role) === strtolower($role->name) || 
                                                    ($pendingUser->requested_role === 'Staff' && $role->name === 'Staff')) ? 'selected' : '' }}>
                                                Assign as {{ $role->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-400">
                                        <svg class="fill-current h-4 w-4" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
                                    </div>
                                </div>
                                
                                <button type="submit" 
                                        class="py-2.5 px-4 bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white font-semibold text-sm rounded-xl shadow-lg transition">
                                    Approve
                                </button>
                            </form>

                            <form action="{{ route('executive.members.reject', $pendingUser) }}" method="POST" onsubmit="return confirm('Are you sure you want to reject this member request?');">
                                @csrf
                                <button type="submit" 
                                        class="py-2.5 px-4 bg-red-500/10 border border-red-500/30 hover:bg-red-500 hover:text-white text-red-400 font-semibold text-sm rounded-xl transition">
                                    Reject
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <!-- Empty state -->
            <div class="backdrop-blur-xl bg-white/5 border border-white/10 rounded-2xl p-12 text-center">
                <div class="w-16 h-16 bg-blue-500/10 border border-blue-500/20 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <p class="text-white text-lg font-bold">All caught up!</p>
                <p class="text-gray-400 mt-2">There are no pending member approvals for your company at this time.</p>
            </div>
        @endif
    </div>
</div>
@endsection
