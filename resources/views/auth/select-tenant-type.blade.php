<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Asset Management Platform</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 min-h-screen">
    <!-- Navigation -->
    <nav class="backdrop-blur-md bg-white/5 border-b border-white/10 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-20">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-blue-400 to-purple-600 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m0 0l8 4m-8-4v10l8 4m0-10l8-4m-8 4v10l8-4m0 0l-8-4"></path>
                        </svg>
                    </div>
                    <span class="text-white font-bold text-xl">AssetFlow</span>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="#features" class="text-gray-300 hover:text-white transition">Features</a>
                    <a href="#" class="text-gray-300 hover:text-white transition">Pricing</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="relative overflow-hidden">
        <!-- Animated background elements -->
        <div class="absolute top-0 -left-4 w-72 h-72 bg-purple-500 rounded-full mix-blend-multiply filter blur-xl opacity-20 animate-blob"></div>
        <div class="absolute top-0 -right-4 w-72 h-72 bg-yellow-500 rounded-full mix-blend-multiply filter blur-xl opacity-20 animate-blob animation-delay-2000"></div>
        <div class="absolute -bottom-8 left-20 w-72 h-72 bg-pink-500 rounded-full mix-blend-multiply filter blur-xl opacity-20 animate-blob animation-delay-4000"></div>

        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-20 pb-24">
            <div class="text-center mb-16">
                <h1 class="text-5xl sm:text-6xl font-bold text-white mb-6">
                    Smart Asset Management <br>
                    <span class="bg-gradient-to-r from-blue-400 via-purple-400 to-pink-400 text-transparent bg-clip-text">For Every Need</span>
                </h1>
                <p class="text-xl text-gray-300 max-w-3xl mx-auto mb-12">
                    Whether you're managing corporate assets or personal belongings, AssetFlow provides intelligent tracking, reporting, and lifecycle management all in one platform.
                </p>
            </div>

            <!-- Tenant Type Selection -->
            <div class="grid md:grid-cols-2 gap-8 max-w-5xl mx-auto">
                <!-- Company Option -->
                <form method="POST" action="{{ route('tenant.store-type') }}" class="group">
                    @csrf
                    <input type="hidden" name="tenant_type" value="company">
                    <button type="submit" class="w-full h-full">
                        <div class="h-full p-8 bg-gradient-to-br from-blue-500/10 via-blue-500/5 to-transparent border-2 border-blue-500/30 rounded-2xl backdrop-blur-xl hover:border-blue-400/60 hover:from-blue-500/20 transition-all duration-300 transform hover:scale-105 hover:shadow-2xl hover:shadow-blue-500/20 group-hover:shadow-2xl group-hover:shadow-blue-500/20">
                            <div class="flex flex-col h-full">
                                <!-- Icon -->
                                <div class="w-16 h-16 bg-gradient-to-br from-blue-400 to-blue-600 rounded-2xl flex items-center justify-center mb-6 group-hover:shadow-lg group-hover:shadow-blue-500/50 transition-shadow">
                                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5.5m0 0H9m0 0h5.5m0 0V7.413c0-.573.465-1.043 1.038-1.035a.999.999 0 011.042.1l5.338 4.381c.546.45.546 1.291 0 1.742l-5.338 4.38a.999.999 0 01-1.042.098 1.01 1.01 0 01-.038-1.035V7"></path>
                                    </svg>
                                </div>

                                <h3 class="text-2xl font-bold text-white mb-3 text-left">For Companies</h3>
                                <p class="text-gray-300 text-left mb-6 flex-grow">
                                    Complete enterprise asset management with multi-level approval workflows, team assignments, and executive dashboards.
                                </p>

                                <!-- Features List -->
                                <ul class="space-y-3 mb-6 text-left">
                                    <li class="flex items-center space-x-3 text-gray-200 text-sm">
                                        <svg class="w-5 h-5 text-blue-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                        </svg>
                                        <span>Request & Approval Workflows</span>
                                    </li>
                                    <li class="flex items-center space-x-3 text-gray-200 text-sm">
                                        <svg class="w-5 h-5 text-blue-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                        </svg>
                                        <span>Employee Asset Assignments</span>
                                    </li>
                                    <li class="flex items-center space-x-3 text-gray-200 text-sm">
                                        <svg class="w-5 h-5 text-blue-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                        </svg>
                                        <span>Executive Oversight Dashboard</span>
                                    </li>
                                    <li class="flex items-center space-x-3 text-gray-200 text-sm">
                                        <svg class="w-5 h-5 text-blue-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                        </svg>
                                        <span>Advanced Analytics & Reports</span>
                                    </li>
                                </ul>

                                <div class="w-full py-3 px-4 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white font-semibold rounded-lg transition-all transform group-hover:shadow-lg text-center">
                                    Get Started
                                </div>
                            </div>
                        </div>
                    </button>
                </form>

                <!-- Household Option -->
                <form method="POST" action="{{ route('tenant.store-type') }}" class="group">
                    @csrf
                    <input type="hidden" name="tenant_type" value="household">
                    <button type="submit" class="w-full h-full">
                        <div class="h-full p-8 bg-gradient-to-br from-purple-500/10 via-purple-500/5 to-transparent border-2 border-purple-500/30 rounded-2xl backdrop-blur-xl hover:border-purple-400/60 hover:from-purple-500/20 transition-all duration-300 transform hover:scale-105 hover:shadow-2xl hover:shadow-purple-500/20 group-hover:shadow-2xl group-hover:shadow-purple-500/20">
                            <div class="flex flex-col h-full">
                                <!-- Icon -->
                                <div class="w-16 h-16 bg-gradient-to-br from-purple-400 to-purple-600 rounded-2xl flex items-center justify-center mb-6 group-hover:shadow-lg group-hover:shadow-purple-500/50 transition-shadow">
                                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-3m0 0l7-4 7 4M5 9v10a1 1 0 001 1h12a1 1 0 001-1V9m-9 11l-4-4m0 0l-4 4m4-4v4"></path>
                                    </svg>
                                </div>

                                <h3 class="text-2xl font-bold text-white mb-3 text-left">For Households</h3>
                                <p class="text-gray-300 text-left mb-6 flex-grow">
                                    Personal asset management with insurance tracking, loan history, and maintenance reminders for your belongings.
                                </p>

                                <!-- Features List -->
                                <ul class="space-y-3 mb-6 text-left">
                                    <li class="flex items-center space-x-3 text-gray-200 text-sm">
                                        <svg class="w-5 h-5 text-purple-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                        </svg>
                                        <span>Insurance Policy Tracking</span>
                                    </li>
                                    <li class="flex items-center space-x-3 text-gray-200 text-sm">
                                        <svg class="w-5 h-5 text-purple-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                        </svg>
                                        <span>Loan & Rental History</span>
                                    </li>
                                    <li class="flex items-center space-x-3 text-gray-200 text-sm">
                                        <svg class="w-5 h-5 text-purple-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                        </svg>
                                        <span>Maintenance Reminders</span>
                                    </li>
                                    <li class="flex items-center space-x-3 text-gray-200 text-sm">
                                        <svg class="w-5 h-5 text-purple-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                        </svg>
                                        <span>Receipt & Warranty Storage</span>
                                    </li>
                                </ul>

                                <div class="w-full py-3 px-4 bg-gradient-to-r from-purple-500 to-purple-600 hover:from-purple-600 hover:to-purple-700 text-white font-semibold rounded-lg transition-all transform group-hover:shadow-lg text-center">
                                    Get Started
                                </div>
                            </div>
                        </div>
                    </button>
                </form>
            </div>

            <!-- Social Proof -->
            <div class="mt-24 text-center">
                <p class="text-gray-400 text-sm mb-8">Trusted by thousands of organizations and individuals worldwide</p>
                <div class="flex items-center justify-center space-x-8 opacity-60">
                    <div class="text-gray-400 font-semibold">Global 500</div>
                    <div class="w-px h-6 bg-gray-600"></div>
                    <div class="text-gray-400 font-semibold">10,000+ Users</div>
                    <div class="w-px h-6 bg-gray-600"></div>
                    <div class="text-gray-400 font-semibold">99.9% Uptime</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Features Section -->
    <section id="features" class="py-24 bg-slate-800/50 backdrop-blur-xl border-t border-white/10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-white mb-4">Powerful Features</h2>
                <p class="text-xl text-gray-300 max-w-2xl mx-auto">Everything you need for comprehensive asset management</p>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                <!-- Feature 1 -->
                <div class="p-6 rounded-xl bg-white/5 border border-white/10 hover:border-white/20 transition-all">
                    <div class="w-12 h-12 bg-blue-500/20 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Real-time Tracking</h3>
                    <p class="text-gray-400">Barcode/QR code scanning for instant asset updates and verification</p>
                </div>

                <!-- Feature 2 -->
                <div class="p-6 rounded-xl bg-white/5 border border-white/10 hover:border-white/20 transition-all">
                    <div class="w-12 h-12 bg-purple-500/20 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Smart Analytics</h3>
                    <p class="text-gray-400">Comprehensive dashboards with utilization, loss rates, and ROI metrics</p>
                </div>

                <!-- Feature 3 -->
                <div class="p-6 rounded-xl bg-white/5 border border-white/10 hover:border-white/20 transition-all">
                    <div class="w-12 h-12 bg-pink-500/20 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Smart Alerts</h3>
                    <p class="text-gray-400">Automated notifications for maintenance, losses, and critical issues</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-slate-900/50 border-t border-white/10 py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center">
                <div class="text-gray-400 text-sm">
                    &copy; 2026 AssetFlow. All rights reserved.
                </div>
                <div class="flex space-x-6">
                    <a href="#" class="text-gray-400 hover:text-white transition">Privacy</a>
                    <a href="#" class="text-gray-400 hover:text-white transition">Terms</a>
                    <a href="#" class="text-gray-400 hover:text-white transition">Contact</a>
                </div>
            </div>
        </div>
    </footer>

    <style>
        @keyframes blob {
            0%, 100% {
                transform: translate(0, 0) scale(1);
            }
            33% {
                transform: translate(30px, -50px) scale(1.1);
            }
            66% {
                transform: translate(-20px, 20px) scale(0.9);
            }
        }

        .animate-blob {
            animation: blob 7s infinite;
        }

        .animation-delay-2000 {
            animation-delay: 2s;
        }

        .animation-delay-4000 {
            animation-delay: 4s;
        }
    </style>
</body>
</html>
