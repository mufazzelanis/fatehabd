@extends('layouts.app')
@section('title', 'Business Login')

@php
    $input = 'w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500';
@endphp

@section('content')
<div class="max-w-md mx-auto px-4 py-12">
    <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-6 sm:p-8">
        <div class="text-center mb-6">
            <div class="w-12 h-12 mx-auto rounded-xl bg-orange-100 dark:bg-orange-900/40 text-orange-600 flex items-center justify-center mb-3">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Business Login</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Dealer panel — district, thana &amp; union dealers</p>
        </div>

        @include('business._flash')

        <form method="POST" action="{{ route('business.login.store') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Phone Number</label>
                <input type="tel" name="phone" value="{{ old('phone') }}" required autofocus placeholder="01XXXXXXXXX" class="{{ $input }}">
                @error('phone')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Password</label>
                <input type="password" name="password" required autocomplete="current-password" class="{{ $input }}">
                @error('password')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <label class="inline-flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                <input type="checkbox" name="remember" class="rounded border-gray-300 text-orange-600 focus:ring-orange-500"> Remember me
            </label>
            <button type="submit" class="w-full bg-orange-500 hover:bg-orange-600 text-white font-semibold py-2.5 rounded-lg transition">Log in</button>
        </form>

        <p class="text-center text-sm text-gray-600 dark:text-gray-400 mt-6">
            Want to become a dealer?
            <a href="{{ route('business.register') }}" class="text-orange-600 font-semibold hover:underline">Register here</a>
        </p>
    </div>
</div>
@endsection
