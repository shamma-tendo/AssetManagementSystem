@extends('layout')

@section('title', 'Categories')

@section('content')
    <h1 class="text-3xl font-bold text-gray-900 mb-6">Asset categories</h1>
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b">
                <tr>
                    <th class="text-left px-6 py-3 font-medium text-gray-500">Name</th>
                    <th class="text-left px-6 py-3 font-medium text-gray-500">Code</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($categories as $c)
                    <tr class="border-b border-gray-100">
                        <td class="px-6 py-3 font-medium">{{ $c->name }}</td>
                        <td class="px-6 py-3 text-gray-600">{{ $c->code ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
