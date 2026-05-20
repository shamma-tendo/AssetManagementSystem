@extends('layout')

@section('title', 'Report Asset Condition')

@section('content')
    <div class="mb-6">
        <a href="{{ route('staff.asset.view', $asset->id) }}" class="text-blue-600 hover:text-blue-800 text-sm">← Back to Asset</a>
        <h1 class="text-3xl font-bold text-gray-900 mt-2">Report Condition</h1>
        <p class="text-gray-600 mt-1">{{ $asset->name }} — {{ $asset->serial_number }}</p>
    </div>

    <div class="max-w-2xl">
        <div class="bg-white rounded-lg shadow p-8">
            <form method="POST" action="{{ route('staff.asset.report.submit', $asset->id) }}">
                @csrf

                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-3">How is the asset?</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3" id="condition-grid">
                        @foreach ($statusOptions as $value => $option)
                            <button type="button"
                                data-value="{{ $value }}"
                                onclick="selectCondition(this)"
                                class="condition-card p-4 border-2 border-gray-200 rounded-lg text-left hover:border-blue-300 transition-all cursor-pointer">
                                <div class="text-2xl mb-2">{{ $option['icon'] }}</div>
                                <p class="font-medium text-gray-900 text-sm">{{ $option['label'] }}</p>
                                <p class="text-xs text-gray-500 mt-1">{{ $option['description'] }}</p>
                            </button>
                        @endforeach
                    </div>
                    <input type="hidden" name="condition" id="condition-input" required>
                    @error('condition') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                    <p class="mt-1 text-xs text-red-500 hidden" id="condition-error">Please select a condition</p>
                </div>

                <div class="mb-6">
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Additional Notes (optional)</label>
                    <textarea name="description" id="description" rows="3"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="Describe the issue in more detail...">{{ old('description') }}</textarea>
                </div>

                @error('error')
                    <p class="mb-4 text-sm text-red-500">{{ $message }}</p>
                @enderror

                <div class="flex gap-3">
                    <button type="submit" class="flex-1 bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 font-medium">
                        Submit Report
                    </button>
                    <a href="{{ route('staff.asset.view', $asset->id) }}" class="flex-1 text-center bg-gray-100 text-gray-700 py-2 px-4 rounded-lg hover:bg-gray-200 font-medium">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
        function selectCondition(btn) {
            document.querySelectorAll('.condition-card').forEach(el => {
                el.classList.remove('border-blue-500', 'bg-blue-50');
                el.classList.add('border-gray-200');
            });
            btn.classList.add('border-blue-500', 'bg-blue-50');
            btn.classList.remove('border-gray-200');
            document.getElementById('condition-input').value = btn.dataset.value;
            document.getElementById('condition-error').classList.add('hidden');
        }

        document.querySelector('form').addEventListener('submit', function(e) {
            if (!document.getElementById('condition-input').value) {
                document.getElementById('condition-error').classList.remove('hidden');
                e.preventDefault();
            }
        });
    </script>
@endsection
