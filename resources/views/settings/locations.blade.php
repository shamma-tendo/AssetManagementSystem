@extends('layout')

@section('title', 'Locations')

@section('content')
    <h1 class="text-3xl font-bold text-gray-900 mb-6">Locations</h1>
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b">
                <tr>
                    <th class="text-left px-6 py-3 font-medium text-gray-500">Name</th>
                    <th class="text-left px-6 py-3 font-medium text-gray-500">Address</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($locations as $l)
                    <tr class="border-b border-gray-100">
                        <td class="px-6 py-3 font-medium">{{ $l->name }}</td>
                        <td class="px-6 py-3 text-gray-600">{{ $l->address ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
