@extends('layouts.app')
@section('title', 'Add Union Dealer')

@php
    $input = 'w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 disabled:opacity-60';
    $label = 'block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1';
@endphp

@section('content')
<div class="max-w-3xl mx-auto px-4 py-10">
    <a href="{{ route('business.dashboard') }}" class="text-sm text-orange-600 hover:underline">&larr; Back to dashboard</a>
    <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-6 sm:p-8 mt-3">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Add Union Dealer</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 mb-6">{{ $dealer->thana->name }} Thana, {{ $dealer->district->name }} District</p>

        @if($errors->any())
            <div class="mb-5 rounded-xl border border-red-200 dark:border-red-900 bg-red-50 dark:bg-red-950/40 p-4">
                <ul class="text-sm text-red-600 dark:text-red-400 space-y-1">@foreach($errors->all() as $e)<li>• {{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <form method="POST" action="{{ route('business.union-dealers.store') }}" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @include('business._location-select', [
                'districts' => collect(), 'inputClass' => $input, 'labelClass' => $label,
                'selected' => ['district_id' => $dealer->district_id, 'thana_id' => $dealer->thana_id],
                'unionRequired' => true, 'showDistrict' => false,
            ])
            @include('business._dealer-fields', ['inputClass' => $input, 'labelClass' => $label])
            <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white font-semibold px-8 py-2.5 rounded-lg transition">Add Union Dealer</button>
        </form>
    </div>
</div>
@endsection
