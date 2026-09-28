@extends('layouts.app')
@section('title', 'Business Dashboard')

@php
    $taka = fn ($v) => '৳' . number_format((float) $v, 0);
    $monthLabel = \Carbon\Carbon::parse($month)->format('F Y');
    $input = 'border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500';
    $canEditMonth = $month >= $currentMonth;
@endphp

@section('content')
<div class="max-w-6xl mx-auto px-4 py-8">
    @include('business._flash')

    {{-- Profile --}}
    <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-5 sm:p-6 flex flex-col sm:flex-row sm:items-center gap-5">
        @if($dealer->photoUrl())
            <img src="{{ $dealer->photoUrl() }}" alt="{{ $dealer->name }}" class="w-20 h-20 rounded-full object-cover border-2 border-orange-200">
        @else
            <div class="w-20 h-20 rounded-full bg-orange-100 dark:bg-orange-900/40 text-orange-600 flex items-center justify-center text-2xl font-bold">{{ strtoupper(mb_substr($dealer->name, 0, 1)) }}</div>
        @endif
        <div class="flex-1 min-w-0">
            <div class="flex flex-wrap items-center gap-2">
                <h1 class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ $dealer->name }}</h1>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-orange-100 dark:bg-orange-900/40 text-orange-700 dark:text-orange-300">{{ $dealer->levelLabel() }}</span>
            </div>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $dealer->locationLabel() }}</p>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $dealer->phone }}{{ $dealer->email ? ' · ' . $dealer->email : '' }}</p>
            <p class="text-xs text-gray-400 mt-1">{{ $dealer->address }}</p>
        </div>
        <form method="POST" action="{{ route('business.logout') }}">
            @csrf
            <button class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition">Log out</button>
        </form>
    </div>

    {{-- Own target --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-5">
        <div class="rounded-2xl p-5 bg-gradient-to-br from-orange-500 to-red-500 text-white shadow-sm">
            <p class="text-sm opacity-90">My target — {{ now()->format('F Y') }}</p>
            <p class="text-3xl font-bold mt-1">{{ $myTarget ? $taka($myTarget->amount) : 'Not set yet' }}</p>
            @if($myTarget?->note)<p class="text-xs opacity-90 mt-2">{{ $myTarget->note }}</p>@endif
        </div>
        <div class="rounded-2xl p-5 bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 shadow-sm">
            <p class="text-sm text-gray-500 dark:text-gray-400">Next month — {{ now()->addMonthNoOverflow()->format('F Y') }}</p>
            <p class="text-3xl font-bold text-gray-900 dark:text-gray-100 mt-1">{{ $nextTarget ? $taka($nextTarget->amount) : '—' }}</p>
            <p class="text-xs text-gray-400 mt-2">For sales &amp; settlement details, please contact the admin.</p>
        </div>
    </div>

    {{-- Dealers one level below --}}
    @if(in_array($dealer->level, ['district', 'thana']))
    <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 mt-5">
        <div class="p-5 border-b border-gray-100 dark:border-gray-800 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                    {{ $dealer->level === 'district' ? 'Thanas in ' . $dealer->district->name : 'Union Dealers in ' . $dealer->thana->name }}
                </h2>
                <p class="text-xs text-gray-500 dark:text-gray-400">Targets for {{ $monthLabel }} · assigned {{ $taka($assignedTotal) }}</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <form method="GET" class="flex items-center gap-2">
                    <input type="month" name="month" value="{{ substr($month, 0, 7) }}" class="{{ $input }}">
                    <button class="px-3 py-1.5 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800">Show</button>
                </form>
                @if($dealer->level === 'thana')
                    <a href="{{ route('business.union-dealers.create') }}" class="px-4 py-1.5 bg-orange-500 hover:bg-orange-600 text-white rounded-lg text-sm font-medium">+ Add Union Dealer</a>
                @endif
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800/60 text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                    <tr>
                        <th class="px-5 py-3 text-left">{{ $dealer->level === 'district' ? 'Thana' : 'Union' }}</th>
                        <th class="px-5 py-3 text-left">Dealer</th>
                        @if($dealer->level === 'district')<th class="px-5 py-3 text-center">Union Dealers</th>@endif
                        <th class="px-5 py-3 text-left">Target — {{ $monthLabel }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($rows as $row)
                        @php $target = $row->dealer?->targetFor($month); @endphp
                        <tr>
                            <td class="px-5 py-3 font-medium text-gray-900 dark:text-gray-100">{{ $row->place }}</td>
                            <td class="px-5 py-3">
                                @if($row->dealer)
                                    <p class="text-gray-900 dark:text-gray-100">{{ $row->dealer->name }}</p>
                                    <p class="text-xs text-gray-500">{{ $row->dealer->phone }}</p>
                                @else
                                    <span class="text-xs text-gray-400">No dealer yet</span>
                                @endif
                            </td>
                            @if($dealer->level === 'district')<td class="px-5 py-3 text-center text-gray-700 dark:text-gray-300">{{ $row->unionDealers }}</td>@endif
                            <td class="px-5 py-3">
                                @if(!$row->dealer)
                                    <span class="text-gray-400">—</span>
                                @elseif($canEditMonth && !$target?->isSettled())
                                    <form method="POST" action="{{ route('business.targets.set', $row->dealer) }}" class="flex items-center gap-2">
                                        @csrf
                                        <input type="hidden" name="month" value="{{ substr($month, 0, 7) }}">
                                        <input type="number" name="amount" min="0" step="1" value="{{ $target ? (int) $target->amount : '' }}" placeholder="Amount ৳" required class="{{ $input }} w-36">
                                        <button class="px-3 py-1.5 bg-orange-500 hover:bg-orange-600 text-white rounded-lg text-xs font-medium">{{ $target ? 'Update' : 'Set' }}</button>
                                    </form>
                                @else
                                    <span class="font-semibold text-gray-900 dark:text-gray-100">{{ $target ? $taka($target->amount) : '—' }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-5 py-10 text-center text-gray-400">
                            {{ $dealer->level === 'thana' ? 'No union dealers yet — add one with “Add Union Dealer”.' : 'No thanas found.' }}
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
@endsection
