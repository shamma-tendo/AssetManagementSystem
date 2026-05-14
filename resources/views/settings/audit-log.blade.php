@extends('layout')

@section('title', 'Audit log')

@section('content')
    <h1 class="text-3xl font-bold text-gray-900 mb-6">Activity audit log</h1>
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b">
                <tr>
                    <th class="text-left px-4 py-3 font-medium text-gray-500">When</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-500">User</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-500">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($logs as $log)
                    <tr class="border-b border-gray-100">
                        <td class="px-4 py-3 whitespace-nowrap">{{ $log->created_at->format('Y-m-d H:i') }}</td>
                        <td class="px-4 py-3">{{ $log->user?->name ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $log->action }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="px-4 py-8 text-center text-gray-500">No activity yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
