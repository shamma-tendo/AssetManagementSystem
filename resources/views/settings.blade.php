@extends('layouts.app')

@section('title', 'Settings')

@section('content')
<div>
    <!-- Page Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Settings</h1>
        <p class="text-gray-600">System configuration and user management.</p>
    </div>

    <!-- User Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-8">
        <div class="bg-white/10 backdrop-blur-xl border border-white/20 rounded-2xl p-6 shadow-xl">
            <p class="text-sm text-gray-600 font-medium mb-1">Total Users</p>
            <p class="text-3xl font-bold text-gray-900">{{ $stats['totalUsers'] }}</p>
        </div>
        <div class="bg-white/10 backdrop-blur-xl border border-white/20 rounded-2xl p-6 shadow-xl">
            <p class="text-sm text-gray-600 font-medium mb-1">Administrators</p>
            <p class="text-3xl font-bold text-blue-600">{{ $stats['adminUsers'] }}</p>
        </div>
        <div class="bg-white/10 backdrop-blur-xl border border-white/20 rounded-2xl p-6 shadow-xl">
            <p class="text-sm text-gray-600 font-medium mb-1">Technicians</p>
            <p class="text-3xl font-bold text-green-600">{{ $stats['technicianUsers'] }}</p>
        </div>
    </div>

    <!-- Settings Sections -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white/10 backdrop-blur-xl border border-white/20 rounded-2xl p-6 shadow-xl">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">General Settings</h2>
            <div class="space-y-4">
                <div class="flex items-center justify-between py-3 border-b border-white/10">
                    <div>
                        <p class="text-sm font-medium text-gray-900">System Name</p>
                        <p class="text-xs text-gray-500">AssetManager Industrial Systems</p>
                    </div>
                </div>
                <div class="flex items-center justify-between py-3 border-b border-white/10">
                    <div>
                        <p class="text-sm font-medium text-gray-900">Database</p>
                        <p class="text-xs text-gray-500">SQLite (Local)</p>
                    </div>
                </div>
                <div class="flex items-center justify-between py-3">
                    <div>
                        <p class="text-sm font-medium text-gray-900">Laravel Version</p>
                        <p class="text-xs text-gray-500">{{ app()->version() }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white/10 backdrop-blur-xl border border-white/20 rounded-2xl p-6 shadow-xl">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Quick Links</h2>
            <div class="space-y-3">
                <a href="{{ route('asset-registry') }}" class="flex items-center px-4 py-3 bg-white/10 hover:bg-white/20 rounded-xl transition-all text-sm font-medium text-gray-900">
                    Asset Registry
                </a>
                <a href="{{ route('maintenance') }}" class="flex items-center px-4 py-3 bg-white/10 hover:bg-white/20 rounded-xl transition-all text-sm font-medium text-gray-900">
                    Maintenance
                </a>
                <a href="{{ route('inventory') }}" class="flex items-center px-4 py-3 bg-white/10 hover:bg-white/20 rounded-xl transition-all text-sm font-medium text-gray-900">
                    Inventory
                </a>
                <a href="{{ route('analytics') }}" class="flex items-center px-4 py-3 bg-white/10 hover:bg-white/20 rounded-xl transition-all text-sm font-medium text-gray-900">
                    Analytics
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
