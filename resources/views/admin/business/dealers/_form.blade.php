@php
    $input = 'w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:bg-gray-50';
    $label = 'block text-sm font-medium text-gray-700 mb-1';
@endphp
<div class="space-y-6">
    <div>
        <label class="{{ $label }}">Dealer Level{{ $dealer ? '' : ' *' }}</label>
        <select name="level" class="{{ $input }} sm:max-w-xs" {{ $dealer ? '' : 'required' }}>
            <option value="">{{ $dealer ? 'Not assigned' : 'Select level' }}</option>
            @foreach(\App\Models\Dealer::LEVELS as $key => $text)
                <option value="{{ $key }}" @selected(old('level', $dealer?->level) === $key)>{{ $text }}</option>
            @endforeach
        </select>
        @error('level')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        <p class="text-xs text-gray-400 mt-1">One district dealer per district and one thana dealer per thana. Union dealers need a union selected.</p>
    </div>
    @include('business._location-select', [
        'districts' => $districts, 'inputClass' => $input, 'labelClass' => $label,
        'selected' => $dealer?->only(['district_id', 'thana_id', 'union_id']) ?? [],
    ])
    @include('business._dealer-fields', [
        'inputClass' => $input, 'labelClass' => $label, 'dealer' => $dealer,
        'documentsRequired' => false, 'passwordRequired' => !$dealer,
        'documentUrl' => $dealer ? fn ($field) => route('admin.business.dealers.document', [$dealer, $field]) : null,
    ])
</div>
