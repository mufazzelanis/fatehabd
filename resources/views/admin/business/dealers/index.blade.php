@extends('layouts.admin')
@section('title', 'Dealers')

@section('content')
<div class="flex flex-wrap items-center justify-between gap-3 mb-6">
    <div class="flex flex-wrap gap-2 text-sm">
        @foreach(['' => 'All', 'pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected', 'suspended' => 'Suspended'] as $key => $label)
            <a href="{{ route('admin.business.dealers.index', array_filter(['status' => $key])) }}"
               class="px-3 py-1.5 rounded-full border {{ request('status', '') === $key ? 'bg-indigo-600 text-white border-indigo-600' : 'border-gray-200 text-gray-600 hover:bg-gray-50' }}">
                {{ $label }} <span class="opacity-70">({{ $key === '' ? $statusCounts->sum() : ($statusCounts[$key] ?? 0) }})</span>
            </a>
        @endforeach
    </div>
    <a href="{{ route('admin.business.dealers.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-indigo-700 transition flex items-center space-x-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        <span>Add Dealer</span>
    </a>
</div>

@if(session('success'))<div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl text-sm">{{ session('success') }}</div>@endif
@if(session('error'))<div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm">{{ session('error') }}</div>@endif

<div class="bg-white rounded-2xl shadow-sm p-4 mb-4">
    <form method="GET" class="flex flex-wrap gap-3">
        @if(request('status'))<input type="hidden" name="status" value="{{ request('status') }}">@endif
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name, phone, NID…"
            class="border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 flex-1 max-w-xs">
        <select name="level" class="border border-gray-200 rounded-xl px-4 py-2 text-sm">
            <option value="">All Levels</option>
            @foreach(\App\Models\Dealer::LEVELS as $key => $label)
                <option value="{{ $key }}" @selected(request('level') === $key)>{{ $label }}</option>
            @endforeach
        </select>
        <select name="district_id" class="border border-gray-200 rounded-xl px-4 py-2 text-sm">
            <option value="">All Districts</option>
            @foreach($districts as $d)
                <option value="{{ $d->id }}" @selected(request('district_id') == $d->id)>{{ $d->name }}</option>
            @endforeach
        </select>
        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm hover:bg-indigo-700 transition">Filter</button>
        @if(request()->hasAny(['search', 'level', 'district_id']))<a href="{{ route('admin.business.dealers.index', array_filter(['status' => request('status')])) }}" class="px-4 py-2 border border-gray-200 rounded-xl text-sm text-gray-600 hover:bg-gray-50 transition">Clear</a>@endif
    </form>
</div>

<div class="bg-white rounded-2xl shadow-sm overflow-x-auto">
    <table class="w-full">
        <thead class="bg-gray-50 border-b border-gray-100">
            <tr class="text-xs text-gray-500 uppercase tracking-wider">
                <th class="px-6 py-3 text-left">Dealer</th>
                <th class="px-6 py-3 text-left">Level</th>
                <th class="px-6 py-3 text-left">Location</th>
                <th class="px-6 py-3 text-center">Status</th>
                <th class="px-6 py-3 text-left">Registered</th>
                <th class="px-6 py-3 text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse($dealers as $dealer)
            <tr class="hover:bg-gray-50 transition">
                <td class="px-6 py-4">
                    <div class="flex items-center gap-3">
                        @if($dealer->photoUrl())
                            <img src="{{ $dealer->photoUrl() }}" class="w-9 h-9 rounded-full object-cover" alt="">
                        @else
                            <div class="w-9 h-9 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-sm font-bold">{{ strtoupper(mb_substr($dealer->name, 0, 1)) }}</div>
                        @endif
                        <div>
                            <a href="{{ route('admin.business.dealers.show', $dealer) }}" class="text-sm font-medium text-gray-900 hover:text-indigo-600">{{ $dealer->name }}</a>
                            <p class="text-xs text-gray-400">{{ $dealer->phone }}</p>
                        </div>
                    </div>
                </td>
                <td class="px-6 py-4 text-sm text-gray-700">{{ $dealer->level ? $dealer->levelLabel() : '—' }}</td>
                <td class="px-6 py-4 text-sm text-gray-600">{{ $dealer->locationLabel() }}</td>
                <td class="px-6 py-4 text-center">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $dealer->statusBadge() }}">{{ ucfirst($dealer->status) }}</span>
                </td>
                <td class="px-6 py-4 text-sm text-gray-500">{{ $dealer->created_at->format('d M Y') }}</td>
                <td class="px-6 py-4 text-right">
                    <a href="{{ route('admin.business.dealers.show', $dealer) }}" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">{{ $dealer->status === 'pending' ? 'Review' : 'View' }}</a>
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="px-6 py-12 text-center text-gray-400 text-sm">No dealers found.</td></tr>
            @endforelse
        </tbody>
    </table>
    @if($dealers->hasPages())
    <div class="px-6 py-4 border-t border-gray-100">{{ $dealers->withQueryString()->links() }}</div>
    @endif
</div>
@endsection
