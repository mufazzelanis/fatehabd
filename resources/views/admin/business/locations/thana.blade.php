@extends('layouts.admin')
@section('title', $thana->name . ' — Unions')

@section('content')
<a href="{{ route('admin.business.locations.district', $thana->district) }}" class="text-indigo-600 hover:text-indigo-700 text-sm flex items-center space-x-2 mb-4">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
    <span>{{ $thana->district->name }} District</span>
</a>

@if(session('success'))<div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl text-sm">{{ session('success') }}</div>@endif
@if(session('error'))<div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm">{{ session('error') }}</div>@endif
@if($errors->any())<div class="mb-4 bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-xl text-sm">{{ $errors->first() }}</div>@endif

@include('admin.business.locations._parent-form', [
    'location' => $thana,
    'updateUrl' => route('admin.business.locations.thana.update', $thana),
    'label' => 'Thana · ' . $thana->district->name . ' District',
])

@include('admin.business.locations._children', [
    'items' => $unions,
    'label' => 'Union',
    'storeUrl' => route('admin.business.locations.union.store', $thana),
    'updateRoute' => 'admin.business.locations.union.update',
    'destroyRoute' => 'admin.business.locations.union.destroy',
    'showRoute' => null,
    'countAttr' => null,
    'countLabel' => null,
])
@endsection
