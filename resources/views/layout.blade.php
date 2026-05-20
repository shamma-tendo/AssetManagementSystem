<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'AEMS - Asset & Equipment Management System')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>
        // Apply dark/light theme before render to prevent flash
        if (localStorage.getItem('theme') === 'dark') {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }

        // Global authenticated fetch helper used across all pages
        function aemsFetch(url, options = {}) {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
            return fetch(url, {
                ...options,
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                    ...(options.headers || {}),
                },
                credentials: 'same-origin',
            });
        }
    </script>
    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1e40af;
            --secondary: #64748b;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
        }
    </style>
</head>
<body class="bg-gray-50 dark:bg-slate-900 transition-colors duration-300">
    @include('components.navbar')
    
    <div class="flex">
        @auth
            @if(auth()->user()->isHouseholdOwner())
                @include('components.household-sidebar')
            @else
                @include('components.sidebar')
            @endif
        @endauth
        
        <main class="flex-1">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                @if ($errors->any())
                    <div class="mb-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
                        <h3 class="text-red-800 dark:text-red-200 font-semibold mb-2">There were some errors:</h3>
                        <ul class="list-disc list-inside text-red-700 dark:text-red-300">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if (session('success'))
                    <div class="mb-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4 text-green-800 dark:text-green-200">
                        {{ session('success') }}
                    </div>
                @endif

                @yield('content')
            </div>
        </main>
    </div>
</body>
</html>
