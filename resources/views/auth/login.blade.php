<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sign in — AEMS</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-100 min-h-screen flex items-center justify-center px-4">
    <div class="w-full max-w-md bg-white rounded-xl shadow-lg p-8">
        <h1 class="text-2xl font-bold text-slate-900 text-center">AEMS</h1>
        <p class="text-sm text-slate-600 text-center mt-1">Asset &amp; Equipment Management</p>
        @if (!empty($intent))
            <p class="text-xs text-center mt-3 px-3 py-2 rounded-lg {{ $intent === 'household' ? 'bg-amber-50 text-amber-900 border border-amber-200' : 'bg-indigo-50 text-indigo-900 border border-indigo-200' }}">
                {{ $intent === 'household' ? 'Household lens — next of kin & personal valuables after sign-in.' : 'Organization lens — custody, approvals, and leadership pulse.' }}
            </p>
        @endif
        @if (!empty($banner))
            <p class="text-xs text-slate-600 text-center mt-3">{{ $banner }}</p>
        @endif

        <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-5">
            @csrf
            <div>
                <label for="email" class="block text-sm font-medium text-slate-700">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                    class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div>
                <label for="password" class="block text-sm font-medium text-slate-700">Password</label>
                <input id="password" name="password" type="password" required
                    class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div class="flex items-center">
                <input id="remember" name="remember" type="checkbox" value="1" class="rounded border-slate-300 text-blue-600">
                <label for="remember" class="ml-2 text-sm text-slate-600">Remember me</label>
            </div>
            @error('email')
                <p class="text-sm text-red-600">{{ $message }}</p>
            @enderror
            <button type="submit" class="w-full bg-blue-600 text-white py-2.5 rounded-lg font-medium hover:bg-blue-700">
                Sign in
            </button>
        </form>
        <p class="text-xs text-slate-500 text-center mt-6">
            <a href="{{ route('welcome') }}" class="text-blue-600 hover:underline">Change lens</a>
            @if($tenant_type === 'household')
                · <a href="{{ route('household.register') }}" class="text-blue-600 hover:underline">Create account</a>
            @endif
            · Demo: admin@aems.local / password
        </p>
    </div>
</body>
</html>
