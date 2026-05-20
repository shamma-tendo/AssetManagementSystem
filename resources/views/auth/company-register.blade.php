<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register Company - AssetFlow</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gradient-to-br from-blue-900 via-slate-900 to-slate-900 min-h-screen">
    <div class="min-h-screen py-12 px-4">
        <div class="max-w-2xl mx-auto">
            <!-- Logo -->
            <div class="text-center mb-8">
                <div class="w-14 h-14 bg-gradient-to-br from-blue-400 to-blue-600 rounded-2xl flex items-center justify-center mx-auto mb-3 shadow-lg shadow-blue-500/30">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5.5m0 0H9m0 0h5.5m0 0V7.413c0-.573.465-1.043 1.038-1.035a.999.999 0 011.042.1l5.338 4.381c.546.45.546 1.291 0 1.742l-5.338 4.38a.999.999 0 01-1.042.098 1.01 1.01 0 01-.038-1.035V7"></path>
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-white">Register Your Company</h1>
                <p class="text-gray-400 text-sm mt-1">Become the CEO and set up your organization</p>
            </div>

            <!-- Registration Form -->
            <div class="bg-white/10 backdrop-blur-xl border border-white/20 rounded-2xl p-8 shadow-2xl">
                <form method="POST" action="{{ route('company.register.submit') }}">
                    @csrf

                    <!-- Industry Selection -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-300 mb-3">Select Industry</label>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-3" id="industry-grid">
                            @foreach([
                                'generic'       => ['name' => 'General',       'icon' => '📦'],
                                'hospital'      => ['name' => 'Hospital',      'icon' => '🏥'],
                                'school'        => ['name' => 'School',        'icon' => '🎓'],
                                'retail'        => ['name' => 'Retail',        'icon' => '🏪'],
                                'manufacturing' => ['name' => 'Manufacturing', 'icon' => '🏭'],
                                'corporate'     => ['name' => 'Corporate',     'icon' => '🏢'],
                            ] as $type => $industry)
                            <button type="button"
                                data-group="industry"
                                data-value="{{ $type }}"
                                onclick="selectOption(this)"
                                class="option-card p-3 border-2 rounded-lg text-center transition-all cursor-pointer
                                    {{ old('industry') == $type ? 'border-blue-500 bg-blue-500/30 text-white' : 'border-white/20 bg-slate-800/50 text-gray-300 hover:border-white/40' }}">
                                <div class="text-2xl mb-1">{{ $industry['icon'] }}</div>
                                <span class="text-sm font-medium">{{ $industry['name'] }}</span>
                            </button>
                            @endforeach
                        </div>
                        <input type="hidden" name="industry" id="industry-input" value="{{ old('industry') }}" required>
                        @error('industry')
                            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-xs text-red-400 hidden" id="industry-error">Please select an industry</p>
                    </div>

                    <!-- Company Size -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-300 mb-3">How many employees?</label>
                        <div class="grid grid-cols-3 md:grid-cols-5 gap-3" id="size-grid">
                            @foreach(['1–50' => '1-50', '51–200' => '51-200', '201–500' => '201-500', '501–1000' => '501-1000', '1000+' => '1000+'] as $label => $val)
                            <button type="button"
                                data-group="size"
                                data-value="{{ $val }}"
                                onclick="selectOption(this)"
                                class="option-card px-2 py-3 border-2 rounded-lg text-center transition-all cursor-pointer
                                    {{ old('size') == $val ? 'border-blue-500 bg-blue-500/30 text-white' : 'border-white/20 bg-slate-800/50 text-gray-300 hover:border-white/40' }}">
                                <span class="text-sm font-medium">{{ $label }}</span>
                            </button>
                            @endforeach
                        </div>
                        <input type="hidden" name="size" id="size-input" value="{{ old('size') }}" required>
                        @error('size')
                            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-xs text-red-400 hidden" id="size-error">Please select a company size</p>
                    </div>

                    <!-- Company Name -->
                    <div class="mb-4">
                        <label for="company_name" class="block text-sm font-medium text-gray-300 mb-2">Company Name</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5.5m0 0H9m0 0h5.5m0 0V7.413c0-.573.465-1.043 1.038-1.035a.999.999 0 011.042.1l5.338 4.381c.546.45.546 1.291 0 1.742l-5.338 4.38a.999.999 0 01-1.042.098 1.01 1.01 0 01-.038-1.035V7"></path>
                                </svg>
                            </div>
                            <input type="text" name="company_name" id="company_name" value="{{ old('company_name') }}" required
                                class="w-full pl-10 pr-4 py-3 bg-slate-800/50 border border-white/20 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition"
                                placeholder="Your company name">
                        </div>
                        @error('company_name')
                            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <hr class="border-white/10 my-6">

                    <!-- Your Details -->
                    <h3 class="text-lg font-semibold text-white mb-4">Your Details (CEO Account)</h3>

                    <!-- Name -->
                    <div class="mb-4">
                        <label for="name" class="block text-sm font-medium text-gray-300 mb-2">Full Name</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                            </div>
                            <input type="text" name="name" id="name" value="{{ old('name') }}" required
                                class="w-full pl-10 pr-4 py-3 bg-slate-800/50 border border-white/20 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition"
                                placeholder="Your full name">
                        </div>
                        @error('name')
                            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Email -->
                    <div class="mb-4">
                        <label for="email" class="block text-sm font-medium text-gray-300 mb-2">Email Address</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"></path>
                                </svg>
                            </div>
                            <input type="email" name="email" id="email" value="{{ old('email') }}" required
                                class="w-full pl-10 pr-4 py-3 bg-slate-800/50 border border-white/20 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition"
                                placeholder="you@company.com">
                        </div>
                        @error('email')
                            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Password -->
                    <div class="mb-4">
                        <label for="password" class="block text-sm font-medium text-gray-300 mb-2">Password</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                </svg>
                            </div>
                            <input type="password" name="password" id="password" required
                                class="w-full pl-10 pr-4 py-3 bg-slate-800/50 border border-white/20 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition"
                                placeholder="Minimum 8 characters">
                        </div>
                        @error('password')
                            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Confirm Password -->
                    <div class="mb-6">
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-300 mb-2">Confirm Password</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <input type="password" name="password_confirmation" id="password_confirmation" required
                                class="w-full pl-10 pr-4 py-3 bg-slate-800/50 border border-white/20 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition"
                                placeholder="Confirm your password">
                        </div>
                    </div>

                    @error('error')
                        <p class="mb-4 text-sm text-red-400">{{ $message }}</p>
                    @enderror

                    <!-- Submit Button -->
                    <button type="submit" 
                        class="w-full py-3 px-4 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white font-semibold rounded-lg transition-all transform hover:scale-[1.02] shadow-lg shadow-blue-500/30">
                        Create Company & Become CEO
                    </button>
                </form>

                <!-- Back Link -->
                <div class="mt-6 text-center">
                    <a href="{{ route('company.auth-choice') }}" class="text-gray-500 hover:text-gray-400 text-sm transition inline-flex items-center space-x-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        <span>Back</span>
                    </a>
                </div>
            </div>

            <!-- Info -->
            <div class="mt-6 p-4 bg-white/5 rounded-xl border border-white/10">
                <div class="flex items-start space-x-3">
                    <svg class="w-5 h-5 text-blue-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p class="text-gray-400 text-xs">
                        As the first registrant, you'll become CEO with full oversight. A unique company code will be generated for your team members to join.
                    </p>
                </div>
            </div>
        </div>
    </div>
<script>
    function selectOption(btn) {
        const group = btn.dataset.group;
        const value = btn.dataset.value;

        // Deselect all in same group
        document.querySelectorAll(`[data-group="${group}"]`).forEach(el => {
            el.classList.remove('border-blue-500', 'bg-blue-500/30', 'text-white');
            el.classList.add('border-white/20', 'bg-slate-800/50', 'text-gray-300');
        });

        // Select this one
        btn.classList.add('border-blue-500', 'bg-blue-500/30', 'text-white');
        btn.classList.remove('border-white/20', 'bg-slate-800/50', 'text-gray-300');

        // Set hidden input
        document.getElementById(`${group}-input`).value = value;

        // Hide error
        const err = document.getElementById(`${group}-error`);
        if (err) err.classList.add('hidden');
    }

    // Validate before submit
    document.querySelector('form').addEventListener('submit', function(e) {
        let valid = true;

        if (!document.getElementById('industry-input').value) {
            document.getElementById('industry-error').classList.remove('hidden');
            valid = false;
        }
        if (!document.getElementById('size-input').value) {
            document.getElementById('size-error').classList.remove('hidden');
            valid = false;
        }

        if (!valid) e.preventDefault();
    });
</script>
</body>
</html>
