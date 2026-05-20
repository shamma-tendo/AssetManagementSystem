@extends('layout')

@section('title', 'Insurance Policies')

@section('content')
<div class="mb-6">
    <a href="{{ route('household.dashboard') }}" class="text-green-600 hover:text-green-700 text-sm">← Back to Dashboard</a>
    <h1 class="text-3xl font-bold text-gray-900 mt-2">🛡️ Insurance Policies</h1>
    <p class="text-gray-500 mt-1">All insurance policies linked to your assets</p>
</div>

<div class="bg-white rounded-xl shadow overflow-hidden">
    @if($insurancePolicies->count() > 0)
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="text-left px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Policy</th>
                    <th class="text-left px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Asset</th>
                    <th class="text-left px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Insurer</th>
                    <th class="text-left px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Expires</th>
                    <th class="text-left px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($insurancePolicies as $policy)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 font-medium">{{ $policy->policy_number ?? '—' }}</td>
                    <td class="px-6 py-4 text-gray-600">{{ $policy->asset?->name ?? '—' }}</td>
                    <td class="px-6 py-4 text-gray-600">{{ $policy->insurer ?? '—' }}</td>
                    <td class="px-6 py-4 text-gray-600">{{ $policy->end_date?->format('M j, Y') ?? '—' }}</td>
                    <td class="px-6 py-4">
                        @if($policy->end_date && $policy->end_date->isFuture())
                            <span class="px-2 py-1 bg-green-100 text-green-700 text-xs rounded-full font-medium">Active</span>
                        @else
                            <span class="px-2 py-1 bg-red-100 text-red-700 text-xs rounded-full font-medium">Expired</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="px-6 py-4 border-t">
            {{ $insurancePolicies->links() }}
        </div>
    @else
        <div class="text-center py-16">
            <p class="text-4xl mb-4">🛡️</p>
            <p class="text-gray-500 font-medium text-lg">No insurance policies yet</p>
            <p class="text-gray-400 text-sm mt-1">Add insurance when creating or editing an asset</p>
            <a href="{{ route('household.dashboard') }}"
                class="inline-block mt-6 px-5 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700">
                Back to Dashboard
            </a>
        </div>
    @endif
</div>
@endsection
