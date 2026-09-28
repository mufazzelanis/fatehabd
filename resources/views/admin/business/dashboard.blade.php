@extends('layouts.admin')
@section('title', 'Business Dashboard')

@php
    $taka = fn ($v) => '৳' . number_format((float) $v, 0);
    $pct = fn ($a, $t) => $t > 0 ? round($a / $t * 100) . '%' : '—';
    $ym = substr($month, 0, 7);
    $monthLabel = \Carbon\Carbon::parse($month)->format('F Y');
    $approved = (int) ($statusCounts['approved'] ?? 0);
@endphp

@section('content')
<div class="flex flex-wrap items-center justify-between gap-3 mb-6">
    <p class="text-sm text-gray-500">Dealership overview — sales are the achieved amounts recorded when a month is settled.</p>
    <form method="GET" class="flex items-center gap-2">
        @if($selectedDistrict)<input type="hidden" name="district" value="{{ $selectedDistrict->id }}">@endif
        <input type="month" name="month" value="{{ $ym }}" class="border border-gray-200 rounded-xl px-4 py-2 text-sm">
        <button class="bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm hover:bg-indigo-700">Show</button>
    </form>
</div>

{{-- Dealer counts --}}
<div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-4 mb-6">
    <a href="{{ route('admin.business.dealers.index') }}" class="bg-white rounded-2xl shadow-sm p-4 hover:ring-2 hover:ring-indigo-100">
        <p class="text-xs text-gray-500">Total Dealers</p>
        <p class="text-2xl font-bold text-gray-900">{{ $statusCounts->sum() }}</p>
    </a>
    <a href="{{ route('admin.business.dealers.index', ['status' => 'approved']) }}" class="bg-white rounded-2xl shadow-sm p-4 hover:ring-2 hover:ring-indigo-100">
        <p class="text-xs text-gray-500">Active Dealers</p>
        <p class="text-2xl font-bold text-green-600">{{ $approved }}</p>
    </a>
    <a href="{{ route('admin.business.dealers.index', ['status' => 'pending']) }}" class="bg-white rounded-2xl shadow-sm p-4 hover:ring-2 hover:ring-indigo-100">
        <p class="text-xs text-gray-500">Pending Approval</p>
        <p class="text-2xl font-bold text-yellow-600">{{ (int) ($statusCounts['pending'] ?? 0) }}</p>
    </a>
    <a href="{{ route('admin.business.dealers.index', ['status' => 'approved', 'level' => 'district']) }}" class="bg-white rounded-2xl shadow-sm p-4 hover:ring-2 hover:ring-indigo-100">
        <p class="text-xs text-gray-500">District Dealers</p>
        <p class="text-2xl font-bold text-gray-900">{{ (int) ($levelCounts['district'] ?? 0) }} <span class="text-sm font-normal text-gray-400">/ {{ $coverage['districts'] }}</span></p>
    </a>
    <a href="{{ route('admin.business.dealers.index', ['status' => 'approved', 'level' => 'thana']) }}" class="bg-white rounded-2xl shadow-sm p-4 hover:ring-2 hover:ring-indigo-100">
        <p class="text-xs text-gray-500">Thana Dealers</p>
        <p class="text-2xl font-bold text-gray-900">{{ (int) ($levelCounts['thana'] ?? 0) }} <span class="text-sm font-normal text-gray-400">/ {{ $coverage['thanas'] }}</span></p>
    </a>
    <a href="{{ route('admin.business.dealers.index', ['status' => 'approved', 'level' => 'union']) }}" class="bg-white rounded-2xl shadow-sm p-4 hover:ring-2 hover:ring-indigo-100">
        <p class="text-xs text-gray-500">Union Dealers</p>
        <p class="text-2xl font-bold text-gray-900">{{ (int) ($levelCounts['union'] ?? 0) }}</p>
    </a>
</div>

<div class="grid grid-cols-1 xl:grid-cols-5 gap-6 mb-6">
    {{-- Target vs sales per level --}}
    <div class="xl:col-span-2 bg-white rounded-2xl shadow-sm overflow-x-auto">
        <div class="px-5 py-4 border-b border-gray-100">
            <h3 class="text-sm font-semibold text-gray-900">Target vs Sales — {{ $monthLabel }}</h3>
            <p class="text-xs text-gray-400">Per level; thana targets are part of district targets, so levels aren't added together.</p>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wider">
                <tr><th class="px-5 py-2 text-left">Level</th><th class="px-5 py-2 text-right">Target</th><th class="px-5 py-2 text-right">Sales</th><th class="px-5 py-2 text-right">%</th><th class="px-5 py-2 text-right">Settled</th></tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach(\App\Models\Dealer::LEVELS as $key => $label)
                    @php $s = $levelStats->get($key); @endphp
                    <tr>
                        <td class="px-5 py-3 font-medium text-gray-800">{{ str_replace(' Dealer', '', $label) }}</td>
                        <td class="px-5 py-3 text-right">{{ $taka($s->target_sum ?? 0) }}</td>
                        <td class="px-5 py-3 text-right text-green-700">{{ $taka($s->achieved_sum ?? 0) }}</td>
                        <td class="px-5 py-3 text-right">{{ $pct($s->achieved_sum ?? 0, $s->target_sum ?? 0) }}</td>
                        <td class="px-5 py-3 text-right text-xs text-gray-500">{{ (int) ($s->settled ?? 0) }}/{{ (int) ($s->targets ?? 0) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Top 10 --}}
    <div class="xl:col-span-3 bg-white rounded-2xl shadow-sm overflow-x-auto">
        <div class="px-5 py-4 border-b border-gray-100 flex flex-wrap items-center justify-between gap-2">
            <h3 class="text-sm font-semibold text-gray-900">Top 10 Dealers by Sales</h3>
            <div class="flex text-xs rounded-lg border border-gray-200 overflow-hidden">
                <a href="{{ request()->fullUrlWithQuery(['top' => null]) }}" class="px-3 py-1.5 {{ $topPeriod === 'month' ? 'bg-indigo-600 text-white' : 'text-gray-600 hover:bg-gray-50' }}">{{ $monthLabel }}</a>
                <a href="{{ request()->fullUrlWithQuery(['top' => 'all']) }}" class="px-3 py-1.5 {{ $topPeriod === 'all' ? 'bg-indigo-600 text-white' : 'text-gray-600 hover:bg-gray-50' }}">All time</a>
            </div>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wider">
                <tr><th class="px-5 py-2 text-left">#</th><th class="px-5 py-2 text-left">Dealer</th><th class="px-5 py-2 text-left">Level / Location</th><th class="px-5 py-2 text-right">Sales</th><th class="px-5 py-2 text-right">Target</th><th class="px-5 py-2 text-right">%</th></tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($topDealers as $i => $d)
                    <tr>
                        <td class="px-5 py-2.5 text-gray-400">{{ $i + 1 }}</td>
                        <td class="px-5 py-2.5"><a href="{{ route('admin.business.dealers.show', $d) }}" class="font-medium text-gray-900 hover:text-indigo-600">{{ $d->name }}</a></td>
                        <td class="px-5 py-2.5 text-xs text-gray-500">{{ $d->levelLabel() }}<br>{{ $d->locationLabel() }}</td>
                        <td class="px-5 py-2.5 text-right font-semibold text-green-700">{{ $taka($d->achieved) }}</td>
                        <td class="px-5 py-2.5 text-right">{{ $taka($d->target) }}</td>
                        <td class="px-5 py-2.5 text-right">{{ $pct($d->achieved, $d->target) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-5 py-8 text-center text-gray-400">No settled sales yet for this period.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- District table / thana drill-down --}}
<div class="bg-white rounded-2xl shadow-sm overflow-x-auto">
    <div class="px-5 py-4 border-b border-gray-100 flex flex-wrap items-center justify-between gap-2">
        <div>
            <h3 class="text-sm font-semibold text-gray-900">
                @if($selectedDistrict)
                    {{ $selectedDistrict->name }} District — Thanas
                @else
                    All Districts
                @endif
                <span class="font-normal text-gray-400">· {{ $monthLabel }}</span>
            </h3>
            <p class="text-xs text-gray-400">{{ $selectedDistrict ? 'Thana dealer target and settled sales for each thana.' : 'District dealer target and settled sales. Click a district to see its thanas.' }}</p>
        </div>
        @if($selectedDistrict)
            <a href="{{ route('admin.business.dashboard', ['month' => $ym]) }}" class="text-sm text-indigo-600 hover:underline">← All districts</a>
        @endif
    </div>
    <table class="w-full text-sm">
        <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wider">
            <tr>
                <th class="px-5 py-3 text-left">{{ $selectedDistrict ? 'Thana' : 'District' }}</th>
                <th class="px-5 py-3 text-left">{{ $selectedDistrict ? 'Thana Dealer' : 'District Dealer' }}</th>
                @unless($selectedDistrict)<th class="px-5 py-3 text-center">Thana Dealers</th>@endunless
                <th class="px-5 py-3 text-center">Union Dealers</th>
                <th class="px-5 py-3 text-right">Target</th>
                <th class="px-5 py-3 text-right">Sales</th>
                <th class="px-5 py-3 text-right">%</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @foreach($rows as $row)
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-3">
                        @if($selectedDistrict)
                            <span class="font-medium text-gray-900">{{ $row->name }}</span>
                        @else
                            <a href="{{ route('admin.business.dashboard', ['month' => $ym, 'district' => $row->id]) }}" class="font-medium text-gray-900 hover:text-indigo-600">{{ $row->name }}</a>
                        @endif
                        <p class="text-xs text-gray-400">{{ $row->places }} {{ $selectedDistrict ? 'unions' : 'thanas' }}</p>
                    </td>
                    <td class="px-5 py-3">
                        @if($row->dealer)
                            <a href="{{ route('admin.business.dealers.show', $row->dealer) }}" class="text-gray-800 hover:text-indigo-600">{{ $row->dealer->name }}</a>
                            <p class="text-xs text-gray-400">{{ $row->dealer->phone }}</p>
                        @else
                            <span class="text-xs text-gray-400">No dealer</span>
                        @endif
                    </td>
                    @unless($selectedDistrict)<td class="px-5 py-3 text-center text-gray-700">{{ $row->thanaDealers }}</td>@endunless
                    <td class="px-5 py-3 text-center text-gray-700">{{ $row->unionDealers }}</td>
                    <td class="px-5 py-3 text-right">{{ $row->target !== null ? $taka($row->target) : '—' }}</td>
                    <td class="px-5 py-3 text-right {{ $row->settled ? 'text-green-700' : 'text-gray-400' }}">{{ $row->settled ? $taka($row->achieved) : ($row->target !== null ? 'Open' : '—') }}</td>
                    <td class="px-5 py-3 text-right">{{ $row->settled ? $pct($row->achieved, $row->target) : '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
