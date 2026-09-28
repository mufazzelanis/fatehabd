@extends('layouts.admin')
@section('title', $district->name . ' — Thanas')

@section('content')
<a href="{{ route('admin.business.locations.index') }}" class="text-indigo-600 hover:text-indigo-700 text-sm flex items-center space-x-2 mb-4">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
    <span>All Districts</span>
</a>

@if(session('success'))<div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl text-sm">{{ session('success') }}</div>@endif
@if(session('error'))<div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm">{{ session('error') }}</div>@endif
@if($errors->any())<div class="mb-4 bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-xl text-sm">{{ $errors->first() }}</div>@endif

@include('admin.business.locations._parent-form', [
    'location' => $district,
    'updateUrl' => route('admin.business.locations.district.update', $district),
    'label' => 'District' . ($district->division ? ' · ' . $district->division->name . ' Division' : ''),
])

@include('admin.business.locations._children', [
    'items' => $thanas,
    'label' => 'Thana',
    'storeUrl' => route('admin.business.locations.thana.store', $district),
    'updateRoute' => 'admin.business.locations.thana.update',
    'destroyRoute' => 'admin.business.locations.thana.destroy',
    'showRoute' => 'admin.business.locations.thana',
    'countAttr' => 'unions_count',
    'countLabel' => 'Unions',
])
@endsection
