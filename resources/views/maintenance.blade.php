@extends('layouts.app')

@section('title', 'Maintenance Pipeline')

@section('content')
<div x-data="maintenanceManagement()" x-init="init()">

    @if(session('success'))
    <div class="mb-6 px-4 py-3 bg-green-50 border border-green-200 rounded-xl flex items-center space-x-3">
        <svg class="w-5 h-5 text-green-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <span class="text-sm font-medium text-green-800">{{ session('success') }}</span>
    </div>
    @endif
    
    <!-- Page Header -->
    <div class="mb-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Maintenance Pipeline</h1>
                <p class="text-gray-600">Manage lifecycle tasks and urgent repairs across facility.</p>
            </div>
            <div class="flex items-center space-x-3 mt-4 sm:mt-0">
                <button @click="showNewWorkOrderModal = true"
                        class="px-4 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-medium rounded-xl shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all duration-200 flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    New Work Order
                </button>
                <!-- View Toggle Buttons -->
                <div class="flex items-center space-x-2 bg-white/10 backdrop-blur-sm border border-white/20 rounded-xl p-1">
                    <button @click="activeView = 'kanban'" 
                            :class="activeView === 'kanban' ? 'bg-white/30 text-gray-900 shadow-sm' : 'text-gray-600 hover:text-gray-900 hover:bg-white/10'" 
                            class="px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200">
                        <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                        Kanban
                    </button>
                    <button @click="activeView = 'list'" 
                            :class="activeView === 'list' ? 'bg-white/30 text-gray-900 shadow-sm' : 'text-gray-600 hover:text-gray-900 hover:bg-white/10'" 
                            class="px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200">
                        <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
                        </svg>
                        List
                    </button>
                    <button @click="activeView = 'schedule'" 
                            :class="activeView === 'schedule' ? 'bg-white/30 text-gray-900 shadow-sm' : 'text-gray-600 hover:text-gray-900 hover:bg-white/10'" 
                            class="px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200">
                        <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        Schedule
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- KPI Cards Section -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-8">
        <!-- Active Orders Card -->
        <div class="bg-white/10 backdrop-blur-xl border border-white/20 rounded-2xl p-6 shadow-xl hover:shadow-2xl transform hover:-translate-y-1 transition-all duration-300 fade-in">
            <div class="flex items-center justify-between mb-4">
                <div class="p-3 bg-blue-500/20 rounded-xl">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                    </svg>
                </div>
                <span class="text-xs {{ $stats['trends']['activeOrders']['color'] }} font-medium">{{ $stats['trends']['activeOrders']['label'] }}</span>
            </div>
            <div class="space-y-1">
                <p class="text-sm text-gray-600 font-medium">Active Orders</p>
                <p id="stat-active-orders" class="text-3xl font-bold text-gray-900" x-text="formatNumber(stats.activeOrders)"></p>
            </div>
        </div>
        
        <!-- Overdue Card -->
        <div class="bg-white/10 backdrop-blur-xl border border-white/20 rounded-2xl p-6 shadow-xl hover:shadow-2xl transform hover:-translate-y-1 transition-all duration-300 fade-in" style="animation-delay: 0.1s">
            <div class="flex items-center justify-between mb-4">
                <div class="p-3 bg-red-500/20 rounded-xl">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 15.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                </div>
                <span class="text-xs {{ $stats['trends']['overdue']['color'] }} font-medium">{{ $stats['trends']['overdue']['label'] }}</span>
            </div>
            <div class="space-y-1">
                <p class="text-sm text-gray-600 font-medium">Overdue</p>
                <p id="stat-overdue" class="text-3xl font-bold text-gray-900" x-text="formatNumber(stats.overdue)"></p>
            </div>
        </div>
        
        <!-- Preventive Compliance Card -->
        <div class="bg-gradient-to-br from-gray-800 to-black border border-white/20 rounded-2xl p-6 shadow-xl hover:shadow-2xl transform hover:-translate-y-1 transition-all duration-300 fade-in relative overflow-hidden" style="animation-delay: 0.2s">
            <!-- Industrial texture overlay -->
            <div class="absolute inset-0 opacity-5" style="background-image: url('data:image/svg+xml,%3Csvg width="60" height="60" xmlns="http://www.w3.org/2000/svg"%3E%3Cdefs%3E%3Cpattern id="grid" width="60" height="60" patternUnits="userSpaceOnUse"%3E%3Cpath d="M 60 0 L 0 0 0 60" fill="none" stroke="white" stroke-width="1"/%3E%3C/pattern%3E%3C/defs%3E%3Crect width="100%25" height="100%25" fill="url(%23grid)" /%3E%3C/svg%3E');"></div>
            
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-white/10 rounded-xl">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <span class="text-xs {{ $stats['trends']['preventiveCompliance']['color'] }} font-medium">{{ $stats['trends']['preventiveCompliance']['label'] }}</span>
                </div>
                <div class="space-y-1">
                    <p class="text-sm text-gray-300 font-medium">Preventive Compliance</p>
                    <p class="text-3xl font-bold text-white" x-text="stats.preventiveCompliance + '%'">98.2%</p>
                </div>
                <div class="mt-4">
                    <div class="w-full bg-white/10 rounded-full h-2">
                        <div class="bg-gradient-to-r from-green-400 to-green-500 h-2 rounded-full transition-all duration-500" :style="'width: ' + stats.preventiveCompliance + '%'"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Kanban Board View -->
    <div x-show="activeView === 'kanban'" x-transition class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Pending Column -->
        <div class="bg-white/10 backdrop-blur-xl border border-white/20 rounded-2xl p-6 shadow-xl">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center space-x-3">
                    <div class="w-3 h-3 bg-blue-500 rounded-full pulse-glow"></div>
                    <h3 class="text-lg font-bold text-gray-900">Pending</h3>
                    <span class="px-2 py-1 bg-blue-100/80 text-blue-700 border border-blue-200/50 rounded-full text-xs font-medium" x-text="tasks.pending.length">8</span>
                </div>
            </div>
            
            <div class="space-y-4">
                <template x-for="task in tasks.pending" :key="task.id">
                    <div class="bg-white/15 backdrop-blur-sm border border-white/20 rounded-xl p-4 hover:bg-white/25 hover:shadow-lg transform hover:-translate-y-1 transition-all duration-200 cursor-pointer" @click="selectTask(task)">
                        <!-- Task Header -->
                        <div class="flex items-start justify-between mb-3">
                            <div class="flex items-center space-x-2">
                                <span class="px-2 py-1 text-xs font-medium rounded-full"
                                      :class="task.priority === 'LOW' ? 'bg-blue-100/80 text-blue-700 border border-blue-200/50' :
                                              task.priority === 'MEDIUM' ? 'bg-gray-100/80 text-gray-700 border border-gray-200/50' :
                                              task.priority === 'HIGH' ? 'bg-orange-100/80 text-orange-700 border border-orange-200/50' :
                                              'bg-red-100/80 text-red-700 border border-red-200/50 pulse-glow'"
                                      x-text="task.priority"></span>
                                <span class="text-xs text-gray-500" x-text="task.id"></span>
                            </div>
                            <div class="relative" x-data="{ dropdownOpen: false }">
                                <button @click.stop="dropdownOpen = !dropdownOpen" class="p-1 rounded-lg hover:bg-white/20 transition-colors">
                                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path>
                                    </svg>
                                </button>
                                <div x-show="dropdownOpen" @click.away="dropdownOpen = false" x-transition class="absolute right-0 mt-1 w-40 bg-white/90 backdrop-blur-xl border border-white/30 rounded-xl shadow-xl z-50">
                                    <a href="#" @click.prevent="editTask(task); dropdownOpen = false" class="block px-4 py-2 text-sm text-gray-700 hover:bg-white/50">Edit</a>
                                    <hr class="border-white/20 my-1">
                                    <a href="#" @click.prevent="deleteTask(task); dropdownOpen = false" class="block px-4 py-2 text-sm text-red-600 hover:bg-white/50">Delete</a>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Task Title -->
                        <h4 class="text-sm font-semibold text-gray-900 mb-2" x-text="task.title"></h4>
                        
                        <!-- Task Description -->
                        <p class="text-xs text-gray-600 mb-3 line-clamp-2" x-text="task.description"></p>
                        
                        <!-- Task Meta -->
                        <div class="flex items-center justify-between text-xs text-gray-500">
                            <div class="flex items-center space-x-2">
                                <span x-text="task.asset"></span>
                                <span>•</span>
                                <span x-text="task.technician"></span>
                            </div>
                            <span x-text="task.dueDate"></span>
                        </div>
                        
                        <!-- Progress Bar (if applicable) -->
                        <div x-show="task.progress > 0" class="mt-3">
                            <div class="flex items-center justify-between text-xs text-gray-600 mb-1">
                                <span>Progress</span>
                                <span x-text="task.progress + '%'"></span>
                            </div>
                            <div class="w-full bg-white/20 rounded-full h-1.5">
                                <div class="h-1.5 rounded-full transition-all duration-300"
                                     :class="task.progress >= 75 ? 'bg-green-500' : task.progress >= 50 ? 'bg-yellow-500' : 'bg-blue-500'"
                                     :style="`width: ${task.progress}%`"></div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
        
        <!-- In Progress Column -->
        <div class="bg-white/10 backdrop-blur-xl border border-white/20 rounded-2xl p-6 shadow-xl">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center space-x-3">
                    <div class="w-3 h-3 bg-yellow-500 rounded-full pulse-glow"></div>
                    <h3 class="text-lg font-bold text-gray-900">In Progress</h3>
                    <span class="px-2 py-1 bg-yellow-100/80 text-yellow-700 border border-yellow-200/50 rounded-full text-xs font-medium" x-text="tasks.inProgress.length">5</span>
                </div>
            </div>
            
            <div class="space-y-4">
                <template x-for="task in tasks.inProgress" :key="task.id">
                    <div class="bg-white/15 backdrop-blur-sm border border-white/20 rounded-xl p-4 hover:bg-white/25 hover:shadow-lg transform hover:-translate-y-1 transition-all duration-200 cursor-pointer" @click="selectTask(task)">
                        <!-- Task Header -->
                        <div class="flex items-start justify-between mb-3">
                            <div class="flex items-center space-x-2">
                                <span class="px-2 py-1 text-xs font-medium rounded-full"
                                      :class="task.priority === 'LOW' ? 'bg-blue-100/80 text-blue-700 border border-blue-200/50' :
                                              task.priority === 'MEDIUM' ? 'bg-gray-100/80 text-gray-700 border border-gray-200/50' :
                                              task.priority === 'HIGH' ? 'bg-orange-100/80 text-orange-700 border border-orange-200/50' :
                                              'bg-red-100/80 text-red-700 border border-red-200/50'"
                                      x-text="task.priority"></span>
                                <span class="text-xs text-gray-500" x-text="task.id"></span>
                            </div>
                            <div class="relative" x-data="{ dropdownOpen: false }">
                                <button @click.stop="dropdownOpen = !dropdownOpen" class="p-1 rounded-lg hover:bg-white/20 transition-colors">
                                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path>
                                    </svg>
                                </button>
                                <div x-show="dropdownOpen" @click.away="dropdownOpen = false" x-transition class="absolute right-0 mt-1 w-40 bg-white/90 backdrop-blur-xl border border-white/30 rounded-xl shadow-xl z-50">
                                    <a href="#" @click.prevent="editTask(task); dropdownOpen = false" class="block px-4 py-2 text-sm text-gray-700 hover:bg-white/50">Edit</a>
                                    <hr class="border-white/20 my-1">
                                    <a href="#" @click.prevent="deleteTask(task); dropdownOpen = false" class="block px-4 py-2 text-sm text-red-600 hover:bg-white/50">Delete</a>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Task Title -->
                        <h4 class="text-sm font-semibold text-gray-900 mb-2" x-text="task.title"></h4>
                        
                        <!-- Task Description -->
                        <p class="text-xs text-gray-600 mb-3 line-clamp-2" x-text="task.description"></p>
                        
                        <!-- Task Meta -->
                        <div class="flex items-center justify-between text-xs text-gray-500">
                            <div class="flex items-center space-x-2">
                                <span x-text="task.asset"></span>
                                <span>•</span>
                                <span x-text="task.technician"></span>
                            </div>
                            <span x-text="task.dueDate"></span>
                        </div>
                        
                        <!-- Progress Bar -->
                        <div class="mt-3">
                            <div class="flex items-center justify-between text-xs text-gray-600 mb-1">
                                <span>Progress</span>
                                <span x-text="task.progress + '%'"></span>
                            </div>
                            <div class="w-full bg-white/20 rounded-full h-1.5">
                                <div class="h-1.5 rounded-full transition-all duration-300"
                                     :class="task.progress >= 75 ? 'bg-green-500' : task.progress >= 50 ? 'bg-yellow-500' : 'bg-blue-500'"
                                     :style="`width: ${task.progress}%`"></div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
        
        <!-- Overdue Column -->
        <div class="bg-white/10 backdrop-blur-xl border border-white/20 rounded-2xl p-6 shadow-xl">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center space-x-3">
                    <div class="w-3 h-3 bg-red-500 rounded-full pulse-glow"></div>
                    <h3 class="text-lg font-bold text-gray-900">Overdue</h3>
                    <span class="px-2 py-1 bg-red-100/80 text-red-700 border border-red-200/50 rounded-full text-xs font-medium" x-text="tasks.overdue.length">4</span>
                </div>
            </div>
            
            <div class="space-y-4">
                <template x-for="task in tasks.overdue" :key="task.id">
                    <div class="bg-white/15 backdrop-blur-sm border border-red-200/30 rounded-xl p-4 hover:bg-white/25 hover:shadow-lg transform hover:-translate-y-1 transition-all duration-200 cursor-pointer" @click="selectTask(task)">
                        <!-- Task Header -->
                        <div class="flex items-start justify-between mb-3">
                            <div class="flex items-center space-x-2">
                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-red-100/80 text-red-700 border border-red-200/50 pulse-glow" x-text="task.priority"></span>
                                <span class="text-xs text-gray-500" x-text="task.id"></span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <div class="text-xs text-red-600 font-medium">
                                    <span x-text="task.overdueDays"></span> days overdue
                                </div>
                                <div class="relative" x-data="{ dropdownOpen: false }">
                                    <button @click.stop="dropdownOpen = !dropdownOpen" class="p-1 rounded-lg hover:bg-white/20 transition-colors">
                                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path>
                                        </svg>
                                    </button>
                                    <div x-show="dropdownOpen" @click.away="dropdownOpen = false" x-transition class="absolute right-0 mt-1 w-40 bg-white/90 backdrop-blur-xl border border-white/30 rounded-xl shadow-xl z-50">
                                        <a href="#" @click.prevent="editTask(task); dropdownOpen = false" class="block px-4 py-2 text-sm text-gray-700 hover:bg-white/50">Edit</a>
                                        <hr class="border-white/20 my-1">
                                        <a href="#" @click.prevent="deleteTask(task); dropdownOpen = false" class="block px-4 py-2 text-sm text-red-600 hover:bg-white/50">Delete</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Task Title -->
                        <h4 class="text-sm font-semibold text-gray-900 mb-2" x-text="task.title"></h4>
                        
                        <!-- Task Description -->
                        <p class="text-xs text-gray-600 mb-3 line-clamp-2" x-text="task.description"></p>
                        
                        <!-- Task Meta -->
                        <div class="flex items-center justify-between text-xs text-gray-500">
                            <div class="flex items-center space-x-2">
                                <span x-text="task.asset"></span>
                                <span>•</span>
                                <span x-text="task.technician"></span>
                            </div>
                            <span class="text-red-600" x-text="task.dueDate"></span>
                        </div>
                        
                        <!-- SLA Warning -->
                        <div class="mt-3 p-2 bg-red-50/50 border border-red-200/30 rounded-lg">
                            <p class="text-xs text-red-700 font-medium">
                                <svg class="w-3 h-3 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 15.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                </svg>
                                SLA breach risk - Immediate action required
                            </p>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
    
    <!-- List View (placeholder) -->
    <div x-show="activeView === 'list'" x-transition class="bg-white/10 backdrop-blur-xl border border-white/20 rounded-2xl p-6 shadow-xl">
        <div class="text-center py-12">
            <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
            </svg>
            <h3 class="text-lg font-medium text-gray-900 mb-2">List View</h3>
            <p class="text-sm text-gray-600">Detailed list view of all maintenance tasks</p>
        </div>
    </div>
    
    <!-- Schedule View (placeholder) -->
    <div x-show="activeView === 'schedule'" x-transition class="bg-white/10 backdrop-blur-xl border border-white/20 rounded-2xl p-6 shadow-xl">
        <div class="text-center py-12">
            <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
            </svg>
            <h3 class="text-lg font-medium text-gray-900 mb-2">Schedule View</h3>
            <p class="text-sm text-gray-600">Calendar-based maintenance scheduling interface</p>
        </div>
    </div>
    
    <!-- Maintenance Analytics Section -->
    <div class="mt-8 grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Completion Rate Chart -->
        <div class="bg-white/10 backdrop-blur-xl border border-white/20 rounded-2xl p-6 shadow-xl">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Completion Rate</h3>
            <div class="relative h-48">
                <canvas id="completionChart"></canvas>
            </div>
        </div>
        
        <!-- Response Time Chart -->
        <div class="bg-white/10 backdrop-blur-xl border border-white/20 rounded-2xl p-6 shadow-xl">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Response Time</h3>
            <div class="relative h-48">
                <canvas id="responseChart"></canvas>
            </div>
        </div>
        
        <!-- Downtime Chart -->
        <div class="bg-white/10 backdrop-blur-xl border border-white/20 rounded-2xl p-6 shadow-xl">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Downtime Hours</h3>
            <div class="relative h-48">
                <canvas id="downtimeChart"></canvas>
            </div>
        </div>
    </div>
    
    <!-- New Work Order Modal -->
    <template x-teleport="body">
    <div x-show="showNewWorkOrderModal" x-transition class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-black/50" @click="showNewWorkOrderModal = false"></div>
        <div class="relative bg-white/95 backdrop-blur-xl border border-white/20 rounded-2xl shadow-2xl max-w-lg w-full max-h-[90vh] flex flex-col">
            <div class="flex items-center justify-between p-6 border-b border-gray-200">
                <h2 class="text-xl font-bold text-gray-900">New Work Order</h2>
                <button @click="showNewWorkOrderModal = false" class="p-2 rounded-lg hover:bg-gray-100">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div class="overflow-y-auto flex-1 p-6">
                @if($errors->any())
                <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg">
                    <ul class="text-sm text-red-700 space-y-1">
                        @foreach($errors->all() as $error)
                            <li>• {{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif
                <form id="newWorkOrderForm" method="POST" action="{{ route('maintenance.create-work-order') }}">
                    @csrf
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Title <span class="text-red-500">*</span></label>
                            <input type="text" name="title" required value="{{ old('title') }}"
                                class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Description <span class="text-red-500">*</span></label>
                            <textarea name="description" required rows="2"
                                class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">{{ old('description') }}</textarea>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Type <span class="text-red-500">*</span></label>
                                <select name="type" required class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    <option value="">Select type</option>
                                    <option value="preventive_maintenance" {{ old('type')=='preventive_maintenance'?'selected':'' }}>Preventive Maintenance</option>
                                    <option value="corrective_maintenance" {{ old('type')=='corrective_maintenance'?'selected':'' }}>Corrective Maintenance</option>
                                    <option value="emergency_maintenance"  {{ old('type')=='emergency_maintenance'?'selected':'' }}>Emergency Maintenance</option>
                                    <option value="inspection"             {{ old('type')=='inspection'?'selected':'' }}>Inspection</option>
                                    <option value="calibration"            {{ old('type')=='calibration'?'selected':'' }}>Calibration</option>
                                    <option value="installation"           {{ old('type')=='installation'?'selected':'' }}>Installation</option>
                                    <option value="repair"                 {{ old('type')=='repair'?'selected':'' }}>Repair</option>
                                    <option value="upgrade"                {{ old('type')=='upgrade'?'selected':'' }}>Upgrade</option>
                                    <option value="other"                  {{ old('type')=='other'?'selected':'' }}>Other</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Priority <span class="text-red-500">*</span></label>
                                <select name="priority" required class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    <option value="low"       {{ old('priority')=='low'?'selected':'' }}>Low</option>
                                    <option value="normal"    {{ old('priority')=='normal'?'selected':'' }}>Normal</option>
                                    <option value="high"      {{ old('priority')=='high'?'selected':'' }}>High</option>
                                    <option value="urgent"    {{ old('priority')=='urgent'?'selected':'' }}>Urgent</option>
                                    <option value="emergency" {{ old('priority')=='emergency'?'selected':'' }}>Emergency</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Asset <span class="text-red-500">*</span></label>
                            <select name="asset_id" required class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="">Select asset</option>
                                @foreach($assets as $asset)
                                    <option value="{{ $asset->id }}" {{ old('asset_id')==$asset->id?'selected':'' }}>
                                        {{ $asset->serial_number }} — {{ $asset->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Assign To</label>
                            <select name="assigned_to" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="">Unassigned</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" {{ old('assigned_to')==$user->id?'selected':'' }}>{{ $user->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Scheduled Date</label>
                                <input type="date" name="scheduled_date" value="{{ old('scheduled_date') }}"
                                    class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Est. Hours</label>
                                <input type="number" name="estimated_hours" min="0" step="0.5" value="{{ old('estimated_hours') }}"
                                    placeholder="0"
                                    class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="flex space-x-3 p-6 border-t border-gray-200">
                <button type="button" @click="showNewWorkOrderModal = false"
                    class="flex-1 px-4 py-2 bg-gray-200 text-gray-700 rounded-lg font-medium hover:bg-gray-300 transition-all">
                    Cancel
                </button>
                <button type="submit" form="newWorkOrderForm"
                    class="flex-1 px-4 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-lg font-medium hover:shadow-lg transition-all">
                    Create Work Order
                </button>
            </div>
        </div>
    </div>
    </template>

</div>
@endsection

@push('scripts')
<script>
function maintenanceManagement() {
    return {
        stats: @json($stats),
        tasks: @json($tasks),
        analytics: @json($analytics),
        selectedTask: null,
        activeView: 'kanban',
        showNewWorkOrderModal: {{ ($openModal || $errors->any()) ? 'true' : 'false' }},
        
        init() {
            this.animateNumbers();
            this.initCharts();
        },
        
        formatNumber(num) {
            return num.toLocaleString();
        },
        
        animateNumbers() {
            const animateValue = (element, start, end, duration) => {
                if (!element) return;
                const startTimestamp = Date.now();
                const step = () => {
                    const timestamp = Date.now();
                    const progress = Math.min((timestamp - startTimestamp) / duration, 1);
                    const value = Math.floor(progress * (end - start) + start);
                    element.textContent = this.formatNumber(value);
                    if (progress < 1) {
                        requestAnimationFrame(step);
                    }
                };
                requestAnimationFrame(step);
            };

            setTimeout(() => {
                animateValue(document.getElementById('stat-active-orders'), 0, this.stats.activeOrders, 1500);
                animateValue(document.getElementById('stat-overdue'),        0, this.stats.overdue,       1200);
            }, 500);
        },
        
        selectTask(task) {
            this.selectedTask = task;
        },

        async editTask(task) {
            const title = prompt('Update work order title:', task.title);
            if (title === null || title.trim() === '') return;
            try {
                const res = await fetch(`/maintenance/${task.id}`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                    body: JSON.stringify({ title: title.trim() })
                });
                const data = await res.json();
                if (data.success) {
                    this.tasks = this.refreshTasks();
                } else {
                    alert(data.message || 'Failed to update work order');
                }
            } catch (e) {
                alert('Network error updating work order');
            }
        },

        async deleteTask(task) {
            if (!confirm('Are you sure you want to delete this work order? This action cannot be undone.')) return;
            try {
                const res = await fetch(`/maintenance/${task.id}`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (data.success) {
                    this.tasks = this.refreshTasks();
                } else {
                    alert(data.message || 'Failed to delete work order');
                }
            } catch (e) {
                alert('Network error deleting work order');
            }
        },
        
        initCharts() {
            // Completion Rate Chart
            const completionCtx = document.getElementById('completionChart').getContext('2d');
            const completionGradient = completionCtx.createLinearGradient(0, 0, 0, 200);
            completionGradient.addColorStop(0, 'rgba(34, 197, 94, 0.4)');
            completionGradient.addColorStop(1, 'rgba(34, 197, 94, 0.1)');
            
            new Chart(completionCtx, {
                type: 'line',
                data: {
                    labels: this.analytics.completionRate.labels,
                    datasets: [{
                        label: 'Completion Rate %',
                        data: this.analytics.completionRate.data,
                        borderColor: 'rgba(34, 197, 94, 1)',
                        backgroundColor: completionGradient,
                        borderWidth: 2,
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: false,
                            min: 80,
                            max: 100,
                            grid: { color: 'rgba(255, 255, 255, 0.1)' },
                            ticks: { color: '#6B7280', callback: value => value + '%' }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { color: '#6B7280' }
                        }
                    }
                }
            });
            
            // Response Time Chart
            const responseCtx = document.getElementById('responseChart').getContext('2d');
            const responseGradient = responseCtx.createLinearGradient(0, 0, 0, 200);
            responseGradient.addColorStop(0, 'rgba(59, 130, 246, 0.4)');
            responseGradient.addColorStop(1, 'rgba(59, 130, 246, 0.1)');
            
            new Chart(responseCtx, {
                type: 'line',
                data: {
                    labels: this.analytics.responseTime.labels,
                    datasets: [{
                        label: 'Response Time (hours)',
                        data: this.analytics.responseTime.data,
                        borderColor: 'rgba(59, 130, 246, 1)',
                        backgroundColor: responseGradient,
                        borderWidth: 2,
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: 'rgba(255, 255, 255, 0.1)' },
                            ticks: { color: '#6B7280', callback: value => value + 'h' }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { color: '#6B7280' }
                        }
                    }
                }
            });
            
            // Downtime Chart
            const downtimeCtx = document.getElementById('downtimeChart').getContext('2d');
            const downtimeGradient = downtimeCtx.createLinearGradient(0, 0, 0, 200);
            downtimeGradient.addColorStop(0, 'rgba(239, 68, 68, 0.4)');
            downtimeGradient.addColorStop(1, 'rgba(239, 68, 68, 0.1)');
            
            new Chart(downtimeCtx, {
                type: 'bar',
                data: {
                    labels: this.analytics.downtime.labels,
                    datasets: [{
                        label: 'Downtime Hours',
                        data: this.analytics.downtime.data,
                        backgroundColor: downtimeGradient,
                        borderColor: 'rgba(239, 68, 68, 1)',
                        borderWidth: 2,
                        borderRadius: 8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: 'rgba(255, 255, 255, 0.1)' },
                            ticks: { color: '#6B7280', callback: value => value + 'h' }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { color: '#6B7280' }
                        }
                    }
                }
            });
        }
    }
}
</script>
@endpush
