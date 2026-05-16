<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Choose Your Industry - AssetFlow</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }
        .animate-float {
            animation: float 3s ease-in-out infinite;
        }
        .industry-card {
            transition: all 0.3s ease;
        }
        .industry-card:hover {
            transform: translateY(-8px);
        }
    </style>
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
                <a href="{{ route('select-tenant-type') }}" class="text-gray-300 hover:text-white transition text-sm">
                    ← Back
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="relative overflow-hidden">
        <!-- Animated background elements -->
        <div class="absolute top-0 -left-4 w-72 h-72 bg-blue-500 rounded-full mix-blend-multiply filter blur-xl opacity-20 animate-blob"></div>
        <div class="absolute top-0 -right-4 w-72 h-72 bg-purple-500 rounded-full mix-blend-multiply filter blur-xl opacity-20 animate-blob animation-delay-2000"></div>
        <div class="absolute -bottom-8 left-20 w-72 h-72 bg-pink-500 rounded-full mix-blend-multiply filter blur-xl opacity-20 animate-blob animation-delay-4000"></div>

        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
            <!-- Header -->
            <div class="text-center mb-16">
                <h1 class="text-5xl sm:text-6xl font-bold text-white mb-4">
                    Choose Your Industry
                </h1>
                <p class="text-xl text-gray-300 max-w-2xl mx-auto">
                    Select the type of organization you manage. We'll customize your experience with industry-specific features.
                </p>
            </div>

            <!-- Industry Cards Grid -->
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6 mb-12">
                @foreach($industries as $type => $industry)
                <form method="POST" action="{{ route('industry.store-type') }}" class="group">
                    @csrf
                    <input type="hidden" name="industry_type" value="{{ $type }}">
                    <button type="submit" class="w-full h-full">
                        <div class="industry-card h-full p-6 bg-gradient-to-br from-white/10 to-white/5 border-2 border-white/20 rounded-2xl backdrop-blur-xl hover:border-white/40 hover:from-white/20 transition-all duration-300 group-hover:shadow-2xl group-hover:shadow-blue-500/20">
                            <!-- Icon -->
                            <div class="text-6xl mb-4">{{ $industry['icon'] }}</div>

                            <!-- Content -->
                            <h3 class="text-2xl font-bold text-white mb-2 text-left">{{ $industry['name'] }}</h3>
                            <p class="text-gray-300 text-sm text-left mb-4">{{ $industry['description'] }}</p>

                            <!-- Features List -->
                            <ul class="space-y-2 mb-6">
                                @foreach($industry['features'] as $feature)
                                <li class="flex items-center space-x-2 text-gray-200 text-sm">
                                    <svg class="w-4 h-4 text-blue-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                    <span>{{ $feature }}</span>
                                </li>
                                @endforeach
                            </ul>

                            <!-- Button -->
                            <div class="w-full py-3 px-4 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white font-semibold rounded-lg transition-all transform group-hover:shadow-lg text-center">
                                Select
                            </div>
                        </div>
                    </button>
                </form>
                @endforeach
            </div>

            <!-- Info Box -->
            <div class="max-w-3xl mx-auto bg-white/5 border border-white/10 rounded-xl p-6 backdrop-blur-xl">
                <div class="flex items-start space-x-4">
                    <svg class="w-6 h-6 text-blue-400 flex-shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div>
                        <h4 class="text-lg font-semibold text-white mb-2">Can't find your industry?</h4>
                        <p class="text-gray-300 text-sm">
                            Select "General Company" to get a standard asset management setup. You can customize features later in your organization settings.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="border-t border-white/10 backdrop-blur-md bg-white/5 py-6 mt-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <p class="text-gray-400 text-sm">Need help choosing? <a href="#" class="text-blue-400 hover:text-blue-300">Contact our sales team</a></p>
        </div>
    </div>
</body>
</html>
