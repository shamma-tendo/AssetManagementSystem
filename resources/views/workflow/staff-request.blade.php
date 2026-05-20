@extends('layout')
@section('title', 'Request an Asset')

@section('content')
<div class="mb-6">
    <a href="{{ route('staff.dashboard') }}" class="text-blue-600 hover:text-blue-700 text-sm">← Back to Dashboard</a>
    <h1 class="text-3xl font-bold text-gray-900 mt-2">Request an Asset</h1>
    <p class="text-gray-500 mt-1">Submit a request to the CEO for an asset you need</p>
</div>

<div class="max-w-2xl bg-white rounded-xl shadow p-8">
    @if($errors->any())
    <div class="mb-4 bg-red-50 border border-red-200 rounded-lg p-4">
        <ul class="list-disc list-inside text-sm text-red-700 space-y-1">
            @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="{{ route('staff.request.submit') }}">
        @csrf
        <div class="space-y-5">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Request Title *</label>
                <input type="text" name="title" required value="{{ old('title') }}"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                    placeholder="e.g., Laptop for data analysis work">
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Asset Type *</label>
                    <input type="text" name="asset_type" required value="{{ old('asset_type') }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                        placeholder="e.g., Laptop, Printer, Camera">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Quantity *</label>
                    <input type="number" name="quantity" required min="1" max="50" value="{{ old('quantity', 1) }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Purpose / How will you use it? *</label>
                <textarea name="purpose" required rows="3"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                    placeholder="Describe what you will use this asset for and why it is needed...">{{ old('purpose') }}</textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Location Where You'll Use It *</label>
                @include('components.location-picker', [
                    'locations'   => $locations,
                    'fieldName'   => 'use_location',
                    'accentColor' => 'blue',
                ])
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Additional Notes</label>
                <textarea name="description" rows="2"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                    placeholder="Any other information...">{{ old('description') }}</textarea>
            </div>
        </div>

        <div class="mt-8 flex gap-4">
            <button type="submit"
                class="flex-1 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition">
                Submit Request
            </button>
            <a href="{{ route('staff.requests') }}"
                class="flex-1 py-3 text-center bg-gray-100 text-gray-700 font-medium rounded-lg hover:bg-gray-200 transition">
                View My Requests
            </a>
        </div>
    </form>
</div>
@endsection
