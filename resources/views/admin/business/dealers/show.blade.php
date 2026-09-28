@extends('layouts.admin')
@section('title', $dealer->name)

@php $taka = fn ($v) => '৳' . number_format((float) $v, 0); @endphp

@section('content')
<a href="{{ route('admin.business.dealers.index') }}" class="text-indigo-600 hover:text-indigo-700 text-sm flex items-center space-x-2 mb-4">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
    <span>Back to Dealers</span>
</a>

@if(session('success'))<div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl text-sm">{{ session('success') }}</div>@endif
@if(session('error'))<div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm">{{ session('error') }}</div>@endif
@if($errors->any())<div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm">{{ $errors->first() }}</div>@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    {{-- Details --}}
    <div class="lg:col-span-2 space-y-6">
        <div class="bg-white rounded-2xl shadow-sm p-6">
            <div class="flex flex-wrap items-start gap-5">
                @if($dealer->photoUrl())
                    <a href="{{ $dealer->photoUrl() }}" target="_blank"><img src="{{ $dealer->photoUrl() }}" class="w-24 h-24 rounded-2xl object-cover" alt=""></a>
                @else
                    <div class="w-24 h-24 rounded-2xl bg-indigo-100 text-indigo-600 flex items-center justify-center text-3xl font-bold">{{ strtoupper(mb_substr($dealer->name, 0, 1)) }}</div>
                @endif
                <div class="flex-1 min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-xl font-semibold text-gray-900">{{ $dealer->name }}</h2>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $dealer->statusBadge() }}">{{ ucfirst($dealer->status) }}</span>
                        @if($dealer->level)<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-700">{{ $dealer->levelLabel() }}</span>@endif
                    </div>
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2 mt-3 text-sm">
                        <div><dt class="text-gray-400 text-xs">Phone</dt><dd class="text-gray-800">{{ $dealer->phone }}</dd></div>
                        <div><dt class="text-gray-400 text-xs">Email</dt><dd class="text-gray-800">{{ $dealer->email ?: '—' }}</dd></div>
                        <div><dt class="text-gray-400 text-xs">NID Number</dt><dd class="text-gray-800">{{ $dealer->nid_number ?: '—' }}</dd></div>
                        <div><dt class="text-gray-400 text-xs">Location</dt><dd class="text-gray-800">{{ $dealer->locationLabel() }}</dd></div>
                        <div class="sm:col-span-2"><dt class="text-gray-400 text-xs">Address</dt><dd class="text-gray-800">{{ $dealer->address }}</dd></div>
                        <div><dt class="text-gray-400 text-xs">Registered</dt><dd class="text-gray-800">{{ $dealer->created_at->format('d M Y, h:i A') }}</dd></div>
                        <div><dt class="text-gray-400 text-xs">Last login</dt><dd class="text-gray-800">{{ $dealer->last_login_at?->diffForHumans() ?? 'Never' }}</dd></div>
                        @if($dealer->createdBy)
                            <div class="sm:col-span-2"><dt class="text-gray-400 text-xs">Added by</dt><dd class="text-gray-800"><a href="{{ route('admin.business.dealers.show', $dealer->createdBy) }}" class="text-indigo-600 hover:underline">{{ $dealer->createdBy->name }}</a> ({{ $dealer->createdBy->levelLabel() }})</dd></div>
                        @endif
                        @if(in_array($dealer->level, ['district', 'thana']))
                            <div><dt class="text-gray-400 text-xs">{{ $dealer->level === 'district' ? 'Thana dealers under' : 'Union dealers under' }}</dt><dd class="text-gray-800">{{ $subordinateCount }}</dd></div>
                        @endif
                    </dl>
                    @if($dealer->admin_note)
                        <p class="mt-3 text-sm bg-yellow-50 border border-yellow-200 text-yellow-800 rounded-xl px-3 py-2">Note: {{ $dealer->admin_note }}</p>
                    @endif
                </div>
            </div>
        </div>

        {{-- Documents --}}
        <div class="bg-white rounded-2xl shadow-sm p-6">
            <h3 class="text-sm font-semibold text-gray-900 mb-4">Documents</h3>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                @foreach(['nid_front' => 'NID Front', 'nid_back' => 'NID Back', 'bank_slip' => 'Bank Slip'] as $field => $label)
                    <div>
                        <p class="text-xs text-gray-500 mb-1.5">{{ $label }}</p>
                        @if($dealer->$field)
                            @php $url = route('admin.business.dealers.document', [$dealer, $field]); @endphp
                            @if(str_ends_with(strtolower($dealer->$field), '.pdf'))
                                <a href="{{ $url }}" target="_blank" class="flex items-center justify-center h-32 rounded-xl border border-gray-200 bg-gray-50 text-sm text-indigo-600 hover:bg-gray-100">Open PDF</a>
                            @else
                                <a href="{{ $url }}" target="_blank"><img src="{{ $url }}" class="w-full h-32 object-cover rounded-xl border border-gray-200" alt="{{ $label }}"></a>
                            @endif
                        @else
                            <div class="flex items-center justify-center h-32 rounded-xl border border-dashed border-gray-200 text-xs text-gray-400">Not uploaded</div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Targets & settlement history (admin only) --}}
        <div class="bg-white rounded-2xl shadow-sm overflow-x-auto">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-900">Monthly Targets &amp; Settlement</h3>
                @if($dealer->isApproved())
                    <a href="{{ route('admin.business.targets.index', ['search' => $dealer->phone]) }}" class="text-sm text-indigo-600 hover:underline">Set / settle target →</a>
                @endif
            </div>
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wider">
                    <tr>
                        <th class="px-6 py-3 text-left">Month</th>
                        <th class="px-6 py-3 text-right">Target</th>
                        <th class="px-6 py-3 text-right">Achieved</th>
                        <th class="px-6 py-3 text-left">Settlement</th>
                        <th class="px-6 py-3 text-left">Set by</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($targets as $t)
                    <tr>
                        <td class="px-6 py-3 font-medium text-gray-900">{{ $t->month->format('F Y') }}</td>
                        <td class="px-6 py-3 text-right">{{ $taka($t->amount) }}</td>
                        <td class="px-6 py-3 text-right">
                            @if($t->isSettled())
                                {{ $taka($t->achieved_amount) }}
                                @if($t->amount > 0)<span class="text-xs text-gray-400">({{ round($t->achieved_amount / $t->amount * 100) }}%)</span>@endif
                            @else — @endif
                        </td>
                        <td class="px-6 py-3">
                            @if($t->isSettled())
                                <span class="text-green-700 text-xs font-medium">Settled {{ $t->settled_at->format('d M Y') }}</span>
                                @if($t->settlement_note)<p class="text-xs text-gray-400">{{ $t->settlement_note }}</p>@endif
                            @else
                                <span class="text-yellow-700 text-xs font-medium">Open</span>
                            @endif
                        </td>
                        <td class="px-6 py-3 text-xs text-gray-500">{{ $t->set_by_type === 'dealer' ? 'Upper dealer' : 'Admin' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="px-6 py-8 text-center text-gray-400">No targets yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Actions --}}
    <div class="space-y-4">
        @if(in_array($dealer->status, ['pending', 'rejected']))
        <div class="bg-white rounded-2xl shadow-sm p-5">
            <h3 class="text-sm font-semibold text-gray-900 mb-3">Approve</h3>
            <form method="POST" action="{{ route('admin.business.dealers.approve', $dealer) }}" class="space-y-3">
                @csrf
                <label class="block text-xs text-gray-500">Assign dealer level</label>
                <select name="level" required class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm">
                    @foreach(\App\Models\Dealer::LEVELS as $key => $text)
                        <option value="{{ $key }}" @selected(old('level', $dealer->level ?? ($dealer->union_id ? 'union' : 'thana')) === $key)>{{ $text }}</option>
                    @endforeach
                </select>
                <p class="text-xs text-gray-400">Location: {{ $dealer->locationLabel() }}. To change it, use Edit first.</p>
                <button class="w-full bg-green-600 text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-green-700">Approve</button>
            </form>
        </div>
        @endif

        @if($dealer->status === 'pending')
        <div class="bg-white rounded-2xl shadow-sm p-5">
            <h3 class="text-sm font-semibold text-gray-900 mb-3">Reject</h3>
            <form method="POST" action="{{ route('admin.business.dealers.reject', $dealer) }}" class="space-y-3">
                @csrf
                <textarea name="admin_note" rows="2" placeholder="Reason (shown to the applicant at login)" class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm"></textarea>
                <button class="w-full bg-red-600 text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-red-700">Reject</button>
            </form>
        </div>
        @endif

        @if($dealer->status === 'approved')
        <div class="bg-white rounded-2xl shadow-sm p-5">
            <h3 class="text-sm font-semibold text-gray-900 mb-3">Suspend</h3>
            <form method="POST" action="{{ route('admin.business.dealers.suspend', $dealer) }}" class="space-y-3" onsubmit="return confirm('Suspend {{ addslashes($dealer->name) }}? They will be logged out.')">
                @csrf
                <textarea name="admin_note" rows="2" placeholder="Reason (optional)" class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm"></textarea>
                <button class="w-full bg-gray-800 text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-gray-900">Suspend</button>
            </form>
        </div>
        @endif

        @if($dealer->status === 'suspended')
        <div class="bg-white rounded-2xl shadow-sm p-5">
            <h3 class="text-sm font-semibold text-gray-900 mb-3">Re-activate</h3>
            <form method="POST" action="{{ route('admin.business.dealers.activate', $dealer) }}">
                @csrf
                <button class="w-full bg-green-600 text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-green-700">Activate again as {{ $dealer->levelLabel() }}</button>
            </form>
        </div>
        @endif

        <div class="bg-white rounded-2xl shadow-sm p-5 space-y-3">
            <a href="{{ route('admin.business.dealers.edit', $dealer) }}" class="block text-center w-full border border-gray-200 text-gray-700 px-4 py-2 rounded-xl text-sm font-medium hover:bg-gray-50">Edit Details / Reset Password</a>
            <form method="POST" action="{{ route('admin.business.dealers.destroy', $dealer) }}" onsubmit="return confirm('Permanently delete {{ addslashes($dealer->name) }} and all their targets? This cannot be undone.')">
                @csrf @method('DELETE')
                <button class="w-full text-red-600 px-4 py-2 rounded-xl text-sm font-medium hover:bg-red-50">Delete Dealer</button>
            </form>
        </div>
    </div>
</div>
@endsection
