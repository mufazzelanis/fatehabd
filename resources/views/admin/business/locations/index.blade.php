@extends('layouts.admin')
@section('title', 'Locations')

@section('content')
<div class="flex items-center justify-between mb-6">
    <p class="text-sm text-gray-500">{{ $districts->count() }} districts · {{ $districts->sum('thanas_count') }} thanas — click a district to manage its thanas and unions</p>
</div>

@if(session('success'))<div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl text-sm">{{ session('success') }}</div>@endif

<div class="bg-white rounded-2xl shadow-sm p-4 mb-4">
    <form method="GET" class="flex flex-wrap gap-3">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search district…"
            class="border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 flex-1 max-w-xs">
        <select name="division" class="border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <option value="">All Divisions</option>
            @foreach($divisions as $division)
                <option value="{{ $division->id }}" @selected(request('division') == $division->id)>{{ $division->name }}</option>
            @endforeach
        </select>
        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm hover:bg-indigo-700 transition">Filter</button>
        @if(request('search') || request('division'))<a href="{{ route('admin.business.locations.index') }}" class="px-4 py-2 border border-gray-200 rounded-xl text-sm text-gray-600 hover:bg-gray-50 transition">Clear</a>@endif
    </form>
</div>

<div class="bg-white rounded-2xl shadow-sm overflow-x-auto">
    <table class="w-full">
        <thead class="bg-gray-50 border-b border-gray-100">
            <tr class="text-xs text-gray-500 uppercase tracking-wider">
                <th class="px-6 py-3 text-left">District</th>
                <th class="px-6 py-3 text-left">Division</th>
                <th class="px-6 py-3 text-center">Thanas</th>
                <th class="px-6 py-3 text-center">Status</th>
                <th class="px-6 py-3 text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse($districts as $district)
            <tr class="hover:bg-gray-50 transition">
                <td class="px-6 py-4">
                    <a href="{{ route('admin.business.locations.district', $district) }}" class="text-sm font-medium text-gray-900 hover:text-indigo-600">{{ $district->name }}</a>
                    @if($district->bn_name)<p class="text-xs text-gray-400">{{ $district->bn_name }}</p>@endif
                </td>
                <td class="px-6 py-4 text-sm text-gray-600">{{ $district->division?->name }}</td>
                <td class="px-6 py-4 text-center text-sm text-gray-700">{{ $district->thanas_count }}</td>
                <td class="px-6 py-4 text-center">
                    @if($district->is_active)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Active</span>
                    @else
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500">Inactive</span>
                    @endif
                </td>
                <td class="px-6 py-4 text-right">
                    <a href="{{ route('admin.business.locations.district', $district) }}" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">Manage</a>
                </td>
            </tr>
            @empty
            <tr><td colspan="5" class="px-6 py-12 text-center text-gray-400 text-sm">No districts found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
