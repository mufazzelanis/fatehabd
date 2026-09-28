@extends('layouts.admin')
@section('title', 'Targets & Settlement')

@php
    $taka = fn ($v) => '৳' . number_format((float) $v, 0);
    $ym = substr($month, 0, 7);
    $monthLabel = \Carbon\Carbon::parse($month)->format('F Y');
@endphp

@section('content')
<div class="flex flex-wrap items-center justify-between gap-3 mb-6">
    <p class="text-sm text-gray-500">Set each dealer's monthly target, then settle the month with the achieved sales. Settlement is visible to admins only.</p>
    <form method="POST" action="{{ route('admin.business.targets.copy-previous') }}" onsubmit="return confirm('Copy last month\'s targets to {{ $monthLabel }} for dealers who have no target yet?')">
        @csrf
        <input type="hidden" name="month" value="{{ $ym }}">
        <button class="px-4 py-2 border border-gray-200 bg-white rounded-xl text-sm text-gray-700 hover:bg-gray-50">Copy last month's targets →</button>
    </form>
</div>

@if(session('success'))<div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl text-sm">{{ session('success') }}</div>@endif
@if(session('error'))<div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm">{{ session('error') }}</div>@endif
@if($errors->any())<div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm">{{ $errors->first() }}</div>@endif

<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
    <div class="bg-white rounded-2xl shadow-sm p-4"><p class="text-xs text-gray-500">Targets set · {{ $monthLabel }}</p><p class="text-2xl font-bold text-gray-900">{{ (int) $monthTotals->targets }}</p></div>
    <div class="bg-white rounded-2xl shadow-sm p-4"><p class="text-xs text-gray-500">Settled</p><p class="text-2xl font-bold text-gray-900">{{ (int) $monthTotals->settled }} <span class="text-sm font-normal text-gray-400">/ {{ (int) $monthTotals->targets }}</span></p></div>
    <div class="bg-white rounded-2xl shadow-sm p-4"><p class="text-xs text-gray-500">Sum of all targets <span title="Includes every level, so nested targets are counted more than once">ⓘ</span></p><p class="text-2xl font-bold text-gray-900">{{ $taka($monthTotals->target_sum) }}</p></div>
    <div class="bg-white rounded-2xl shadow-sm p-4"><p class="text-xs text-gray-500">Sum of achieved (settled)</p><p class="text-2xl font-bold text-gray-900">{{ $taka($monthTotals->achieved_sum) }}</p></div>
</div>

<div class="bg-white rounded-2xl shadow-sm p-4 mb-4">
    <form method="GET" class="flex flex-wrap gap-3">
        <input type="month" name="month" value="{{ $ym }}" class="border border-gray-200 rounded-xl px-4 py-2 text-sm">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Name or phone…" class="border border-gray-200 rounded-xl px-4 py-2 text-sm flex-1 max-w-xs">
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
        <select name="state" class="border border-gray-200 rounded-xl px-4 py-2 text-sm">
            <option value="">Any state</option>
            <option value="no_target" @selected(request('state') === 'no_target')>No target yet</option>
            <option value="unsettled" @selected(request('state') === 'unsettled')>Not settled</option>
            <option value="settled" @selected(request('state') === 'settled')>Settled</option>
        </select>
        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm hover:bg-indigo-700 transition">Show</button>
    </form>
</div>

<div class="bg-white rounded-2xl shadow-sm overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-100">
            <tr class="text-xs text-gray-500 uppercase tracking-wider">
                <th class="px-5 py-3 text-left">Dealer</th>
                <th class="px-5 py-3 text-left">Level / Location</th>
                <th class="px-5 py-3 text-left">Target — {{ $monthLabel }}</th>
                <th class="px-5 py-3 text-left">Settlement</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse($dealers as $dealer)
                @php $target = $dealer->targetFor($month); @endphp
                <tr class="align-top" x-data="{ settling: false }">
                    <td class="px-5 py-4">
                        <a href="{{ route('admin.business.dealers.show', $dealer) }}" class="font-medium text-gray-900 hover:text-indigo-600">{{ $dealer->name }}</a>
                        <p class="text-xs text-gray-400">{{ $dealer->phone }}</p>
                    </td>
                    <td class="px-5 py-4">
                        <p class="text-gray-700">{{ $dealer->levelLabel() }}</p>
                        <p class="text-xs text-gray-400">{{ $dealer->locationLabel() }}</p>
                    </td>
                    <td class="px-5 py-4">
                        @if($target?->isSettled())
                            <p class="font-semibold text-gray-900">{{ $taka($target->amount) }}</p>
                        @else
                            <form method="POST" action="{{ route('admin.business.targets.save', $dealer) }}" class="flex flex-wrap items-center gap-2">
                                @csrf
                                <input type="hidden" name="month" value="{{ $ym }}">
                                <input type="number" name="amount" min="0" step="1" required value="{{ $target ? (int) $target->amount : '' }}" placeholder="Amount ৳" class="border border-gray-200 rounded-lg px-3 py-1.5 text-sm w-32">
                                <input type="text" name="note" value="{{ $target?->note }}" placeholder="Note (optional)" class="border border-gray-200 rounded-lg px-3 py-1.5 text-sm w-36">
                                <button class="bg-indigo-600 text-white px-3 py-1.5 rounded-lg text-xs font-medium hover:bg-indigo-700">{{ $target ? 'Update' : 'Set' }}</button>
                            </form>
                        @endif
                        @if($target)
                            <p class="text-xs text-gray-400 mt-1">Set by {{ $target->set_by_type === 'dealer' ? 'upper dealer' : 'admin' }} · {{ $target->updated_at->format('d M') }}</p>
                        @endif
                    </td>
                    <td class="px-5 py-4">
                        @if(!$target)
                            <span class="text-xs text-gray-400">Set a target first</span>
                        @elseif($target->isSettled())
                            <p class="text-green-700 font-medium">{{ $taka($target->achieved_amount) }}
                                @if($target->amount > 0)<span class="text-xs text-gray-500">({{ round($target->achieved_amount / $target->amount * 100) }}%)</span>@endif
                            </p>
                            <p class="text-xs text-gray-400">Settled {{ $target->settled_at->format('d M Y') }}{{ $target->settlement_note ? ' · ' . $target->settlement_note : '' }}</p>
                            <form method="POST" action="{{ route('admin.business.targets.unsettle', $target) }}" onsubmit="return confirm('Reopen this settlement?')" class="mt-1">
                                @csrf
                                <button class="text-xs text-red-500 hover:underline">Reopen</button>
                            </form>
                        @else
                            <button type="button" x-show="!settling" @click="settling = true" class="px-3 py-1.5 bg-green-600 text-white rounded-lg text-xs font-medium hover:bg-green-700">Settle month</button>
                            <form x-show="settling" x-cloak method="POST" action="{{ route('admin.business.targets.settle', $target) }}" class="space-y-2">
                                @csrf
                                <input type="number" name="achieved_amount" min="0" step="1" required placeholder="Achieved sales ৳" class="border border-gray-200 rounded-lg px-3 py-1.5 text-sm w-40">
                                <input type="text" name="settlement_note" placeholder="Note (optional)" class="border border-gray-200 rounded-lg px-3 py-1.5 text-sm w-40 block">
                                <div class="flex gap-2">
                                    <button class="px-3 py-1.5 bg-green-600 text-white rounded-lg text-xs font-medium hover:bg-green-700">Save settlement</button>
                                    <button type="button" @click="settling = false" class="px-3 py-1.5 border border-gray-200 rounded-lg text-xs text-gray-600">Cancel</button>
                                </div>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="px-6 py-12 text-center text-gray-400">No approved dealers match these filters.</td></tr>
            @endforelse
        </tbody>
    </table>
    @if($dealers->hasPages())
    <div class="px-6 py-4 border-t border-gray-100">{{ $dealers->links() }}</div>
    @endif
</div>
@endsection
