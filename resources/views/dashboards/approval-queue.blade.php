@extends('layout')

@section('title', 'Approval Queue')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100">
    <!-- Header -->
    <div class="bg-white border-b border-gray-200 sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Approval Queue</h1>
                    <p class="text-gray-500 mt-1">Review and approve asset requests</p>
                </div>
                <a href="{{ route('executive.dashboard') }}" class="text-blue-600 hover:text-blue-700">
                    ← Back to Dashboard
                </a>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <!-- Filters -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <div class="grid md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status Filter</label>
                    <select class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="all">All Statuses</option>
                        <option value="pending">Pending</option>
                        <option value="under_review">Under Review</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Sort By</label>
                    <select class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="newest">Newest First</option>
                        <option value="oldest">Oldest First</option>
                        <option value="highest_cost">Highest Cost</option>
                        <option value="lowest_cost">Lowest Cost</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                    <input type="text" placeholder="Search requests..." class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
            </div>
        </div>

        <!-- Pending Requests -->
        @if($requests->count() > 0)
            <div class="space-y-6">
                @foreach($requests as $request)
                    <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition">
                        <!-- Request Header -->
                        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border-l-4 border-blue-500 p-6">
                            <div class="flex justify-between items-start mb-4">
                                <div class="flex-1">
                                    <h2 class="text-2xl font-bold text-gray-900">{{ $request->title }}</h2>
                                    <p class="text-sm text-gray-600 mt-1">Requested by <span class="font-semibold">{{ $request->requestedBy->name }}</span></p>
                                </div>
                                <div class="text-right">
                                    <div class="text-3xl font-bold text-blue-600">{{ $request->quantity }}</div>
                                    <p class="text-sm text-gray-600">Items</p>
                                </div>
                            </div>

                            <p class="text-gray-700">{{ $request->description }}</p>
                        </div>

                        <!-- Request Details -->
                        <div class="p-6 border-b border-gray-200">
                            <div class="grid md:grid-cols-4 gap-6">
                                <div>
                                    <p class="text-xs text-gray-500 font-medium uppercase">Asset Type</p>
                                    <p class="text-lg font-semibold text-gray-900">{{ $request->asset_type }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 font-medium uppercase">Estimated Cost</p>
                                    <p class="text-lg font-semibold text-gray-900">${{ number_format($request->estimated_cost, 2) }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 font-medium uppercase">Cost Per Unit</p>
                                    <p class="text-lg font-semibold text-gray-900">${{ number_format($request->estimated_cost / $request->quantity, 2) }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 font-medium uppercase">Status</p>
                                    <span class="inline-block px-3 py-1 bg-yellow-100 text-yellow-800 text-sm font-medium rounded-full">
                                        {{ ucfirst(str_replace('_', ' ', $request->status)) }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Assets Associated -->
                        @if($request->assets && $request->assets->count() > 0)
                            <div class="p-6 bg-gray-50 border-b border-gray-200">
                                <h3 class="font-semibold text-gray-900 mb-3">Associated Assets</h3>
                                <div class="grid md:grid-cols-2 gap-3">
                                    @foreach($request->assets as $asset)
                                        <div class="bg-white p-3 rounded border border-gray-200">
                                            <p class="font-medium text-gray-900">{{ $asset->name }}</p>
                                            <p class="text-sm text-gray-600">{{ $asset->category->name ?? 'N/A' }}</p>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <!-- Approval Decision Section -->
                        <div class="p-6">
                            <h3 class="font-semibold text-gray-900 mb-4">Your Decision</h3>

                            <form class="space-y-4">
                                @csrf

                                <!-- Comment Field -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Comments (Optional)</label>
                                    <textarea rows="4" placeholder="Add any notes about this approval decision..." class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none"></textarea>
                                    <p class="text-xs text-gray-500 mt-1">These comments will be visible to the requester</p>
                                </div>

                                <!-- Decision Buttons -->
                                <div class="flex gap-3">
                                    <!-- Approve Button -->
                                    <button type="submit" name="action" value="approve" class="flex-1 py-3 px-4 bg-green-600 text-white font-semibold rounded-lg hover:bg-green-700 transition flex items-center justify-center gap-2">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                        Approve Request
                                    </button>

                                    <!-- Reject Button -->
                                    <button type="submit" name="action" value="reject" class="flex-1 py-3 px-4 bg-red-600 text-white font-semibold rounded-lg hover:bg-red-700 transition flex items-center justify-center gap-2">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                        Reject Request
                                    </button>

                                    <!-- Hold for Review Button -->
                                    <button type="button" class="flex-1 py-3 px-4 bg-gray-300 text-gray-800 font-semibold rounded-lg hover:bg-gray-400 transition flex items-center justify-center gap-2">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        Hold
                                    </button>
                                </div>

                                <!-- Additional Options -->
                                <div class="border-t border-gray-200 pt-4 flex items-center space-x-4 text-sm">
                                    <label class="flex items-center space-x-2 cursor-pointer">
                                        <input type="checkbox" class="w-4 h-4 text-blue-600 rounded focus:ring-2 focus:ring-blue-500">
                                        <span class="text-gray-700">Mark for follow-up</span>
                                    </label>
                                    <label class="flex items-center space-x-2 cursor-pointer">
                                        <input type="checkbox" class="w-4 h-4 text-blue-600 rounded focus:ring-2 focus:ring-blue-500">
                                        <span class="text-gray-700">Notify requester immediately</span>
                                    </label>
                                </div>
                            </form>
                        </div>

                        <!-- Request Metadata -->
                        <div class="bg-gray-50 px-6 py-4 text-xs text-gray-500 flex justify-between">
                            <span>Requested: {{ $request->created_at->format('M d, Y \a\t h:i A') }}</span>
                            <span>Request ID: {{ $request->id }}</span>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Pagination -->
            <div class="mt-8">
                {{ $requests->links() }}
            </div>
        @else
            <!-- Empty State -->
            <div class="bg-white rounded-lg shadow-md p-12 text-center">
                <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <p class="text-gray-500 text-lg mb-4">No pending requests</p>
                <p class="text-gray-400">Great job! Your approval queue is all caught up.</p>
            </div>
        @endif
    </div>
</div>
@endsection
