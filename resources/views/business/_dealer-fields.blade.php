{{--
    Dealer account fields shared by registration, the union-dealer form and the admin form.
    Params: $inputClass, $labelClass, $dealer (nullable, for edit), $documentsRequired (bool),
            $passwordRequired (bool), $documentUrl (nullable closure field => url, admin only).
--}}
@php
    $dealer = $dealer ?? null;
    $req = ($documentsRequired ?? true) ? ' *' : '';
    $pwReq = $passwordRequired ?? true;
    $fileClass = 'block w-full text-sm text-gray-600 dark:text-gray-300 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-gray-100 dark:file:bg-gray-700 file:text-gray-700 dark:file:text-gray-200 hover:file:bg-gray-200';
@endphp
<div class="space-y-4">
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label class="{{ $labelClass }}">Full Name *</label>
            <input type="text" name="name" value="{{ old('name', $dealer?->name) }}" required class="{{ $inputClass }}">
            @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="{{ $labelClass }}">Phone Number *</label>
            <input type="tel" name="phone" value="{{ old('phone', $dealer?->phone) }}" required placeholder="01XXXXXXXXX" class="{{ $inputClass }}">
            @error('phone')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label class="{{ $labelClass }}">Email (optional)</label>
            <input type="email" name="email" value="{{ old('email', $dealer?->email) }}" class="{{ $inputClass }}">
            @error('email')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="{{ $labelClass }}">NID Number *</label>
            <input type="text" name="nid_number" value="{{ old('nid_number', $dealer?->nid_number) }}" required class="{{ $inputClass }}">
            @error('nid_number')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label class="{{ $labelClass }}">Password{{ $pwReq ? ' *' : ' (leave blank to keep current)' }}</label>
            <input type="password" name="password" {{ $pwReq ? 'required' : '' }} autocomplete="new-password" class="{{ $inputClass }}">
            @error('password')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="{{ $labelClass }}">Confirm Password{{ $pwReq ? ' *' : '' }}</label>
            <input type="password" name="password_confirmation" {{ $pwReq ? 'required' : '' }} autocomplete="new-password" class="{{ $inputClass }}">
        </div>
    </div>
    <div>
        <label class="{{ $labelClass }}">Full Address *</label>
        <textarea name="address" rows="2" required class="{{ $inputClass }}">{{ old('address', $dealer?->address) }}</textarea>
        @error('address')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label class="{{ $labelClass }}">Your Photo{{ $req }}</label>
            <input type="file" name="photo" accept="image/*" {{ $req && !$dealer ? 'required' : '' }} class="{{ $fileClass }}">
            @if($dealer?->photo)<p class="text-xs text-gray-500 mt-1">Current: <a href="{{ $dealer->photoUrl() }}" target="_blank" class="text-indigo-600 underline">view photo</a></p>@endif
            @error('photo')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="{{ $labelClass }}">Bank Slip{{ $req }} <span class="text-xs font-normal text-gray-400">(image or PDF)</span></label>
            <input type="file" name="bank_slip" accept="image/*,application/pdf" {{ $req && !$dealer ? 'required' : '' }} class="{{ $fileClass }}">
            @if($dealer?->bank_slip && isset($documentUrl))<p class="text-xs text-gray-500 mt-1">Current: <a href="{{ $documentUrl('bank_slip') }}" target="_blank" class="text-indigo-600 underline">view</a></p>@endif
            @error('bank_slip')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="{{ $labelClass }}">NID Front{{ $req }}</label>
            <input type="file" name="nid_front" accept="image/*" {{ $req && !$dealer ? 'required' : '' }} class="{{ $fileClass }}">
            @if($dealer?->nid_front && isset($documentUrl))<p class="text-xs text-gray-500 mt-1">Current: <a href="{{ $documentUrl('nid_front') }}" target="_blank" class="text-indigo-600 underline">view</a></p>@endif
            @error('nid_front')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="{{ $labelClass }}">NID Back (optional)</label>
            <input type="file" name="nid_back" accept="image/*" class="{{ $fileClass }}">
            @if($dealer?->nid_back && isset($documentUrl))<p class="text-xs text-gray-500 mt-1">Current: <a href="{{ $documentUrl('nid_back') }}" target="_blank" class="text-indigo-600 underline">view</a></p>@endif
            @error('nid_back')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>
    </div>
    <p class="text-xs text-gray-400">Max 4 MB per file.</p>
</div>
