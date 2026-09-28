@extends('layouts.app')
@section('title', 'Business Registration')

@php
    $input = 'w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 disabled:opacity-60';
    $label = 'block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1';
@endphp

@section('content')
<div class="max-w-3xl mx-auto px-4 py-10">
    <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-6 sm:p-8">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Dealer Registration</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 mb-6">Fill in your details. Your account will be activated after the admin reviews your documents.</p>

        @include('business._flash')
        @if($errors->any())
            <div class="mb-5 rounded-xl border border-red-200 dark:border-red-900 bg-red-50 dark:bg-red-950/40 p-4">
                <ul class="text-sm text-red-600 dark:text-red-400 space-y-1">@foreach($errors->all() as $e)<li>• {{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <form method="POST" action="{{ route('business.register.store') }}" enctype="multipart/form-data" class="space-y-6">
            @csrf
            <section>
                <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-100 uppercase tracking-wide mb-3">Area</h2>
                @include('business._location-select', ['districts' => $districts, 'inputClass' => $input, 'labelClass' => $label])
            </section>
            <section>
                <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-100 uppercase tracking-wide mb-3">Your Details &amp; Documents</h2>
                @include('business._dealer-fields', ['inputClass' => $input, 'labelClass' => $label])
            </section>
            <button type="submit" class="w-full sm:w-auto bg-orange-500 hover:bg-orange-600 text-white font-semibold px-8 py-2.5 rounded-lg transition">Submit Registration</button>
        </form>

        <p class="text-sm text-gray-600 dark:text-gray-400 mt-6">
            Already registered? <a href="{{ route('business.login') }}" class="text-orange-600 font-semibold hover:underline">Log in</a>
        </p>
    </div>
</div>
@endsection
