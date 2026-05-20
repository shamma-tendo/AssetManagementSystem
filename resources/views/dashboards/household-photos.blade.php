@extends('layout')

@section('title', 'Photo Storage')

@section('content')
<div class="mb-6 flex justify-between items-center">
    <div>
        <h1 class="text-3xl font-bold text-gray-900">🖼️ Photo Storage</h1>
        <p class="text-gray-500 mt-1">Store photos of your assets — receipts, condition, documentation</p>
    </div>
    <button onclick="document.getElementById('upload-modal').classList.remove('hidden')"
        class="px-5 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition">
        + Upload Photo
    </button>
</div>

@if($photos->count() > 0)
<div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
    @foreach($photos as $photo)
    <div class="bg-white rounded-xl shadow overflow-hidden group relative">
        <a href="{{ asset('storage/' . $photo->file_path) }}" target="_blank">
            <img src="{{ asset('storage/' . $photo->file_path) }}"
                alt="{{ $photo->file_name }}"
                class="w-full h-36 object-cover group-hover:opacity-90 transition">
        </a>
        <div class="p-2">
            <p class="text-xs font-medium text-gray-800 truncate">{{ $photo->asset?->name ?? '—' }}</p>
            @if($photo->notes)
                <p class="text-xs text-gray-400 truncate">{{ $photo->notes }}</p>
            @endif
            <p class="text-xs text-gray-300 mt-1">{{ $photo->created_at->format('M j, Y') }}</p>
        </div>
        <form method="POST" action="{{ route('household.photos.delete', $photo) }}"
            class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition"
            onsubmit="return confirm('Delete this photo?')">
            @csrf @method('DELETE')
            <button type="submit" class="w-7 h-7 bg-red-500 text-white rounded-full flex items-center justify-center hover:bg-red-600 shadow">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </form>
    </div>
    @endforeach
</div>
@else
<div class="bg-white rounded-xl shadow p-16 text-center">
    <p class="text-5xl mb-4">🖼️</p>
    <p class="text-gray-500 font-medium text-lg">No photos yet</p>
    <p class="text-gray-400 text-sm mt-1">Upload photos of your assets — receipts, damage, documentation</p>
    <button onclick="document.getElementById('upload-modal').classList.remove('hidden')"
        class="mt-6 px-5 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700">
        + Upload Photo
    </button>
</div>
@endif

<!-- Upload Modal -->
<div id="upload-modal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-xl shadow-2xl max-w-md w-full mx-4">
        <div class="flex justify-between items-center px-6 py-4 border-b">
            <h2 class="text-lg font-bold text-gray-900">Upload Asset Photo</h2>
            <button onclick="document.getElementById('upload-modal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <form method="POST" action="{{ route('household.photos.store') }}" enctype="multipart/form-data" class="p-6 space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Asset *</label>
                <select name="asset_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                    <option value="">Select asset</option>
                    @foreach($assets as $asset)
                        <option value="{{ $asset->id }}">{{ $asset->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Photo *</label>
                <input type="file" name="photo" accept="image/*" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-500">
                <p class="text-xs text-gray-400 mt-1">JPG, PNG, GIF, WebP — max 5 MB</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Notes (optional)</label>
                <input type="text" name="notes" placeholder="e.g., Purchase receipt, Front view..."
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="flex-1 py-2 bg-green-600 text-white font-medium rounded-lg hover:bg-green-700">Upload</button>
                <button type="button" onclick="document.getElementById('upload-modal').classList.add('hidden')"
                    class="flex-1 py-2 bg-gray-100 text-gray-700 font-medium rounded-lg hover:bg-gray-200">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
    @if($errors->any())
        document.getElementById('upload-modal').classList.remove('hidden');
    @endif
</script>
@endsection
