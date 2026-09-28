{{-- Edit form for the page's own location (district or thana). Params: $location, $updateUrl, $label. --}}
<div class="bg-white rounded-2xl shadow-sm p-4 mb-4" x-data="{ open: false }">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-gray-900">{{ $location->name }}
                @if($location->bn_name)<span class="text-gray-400 font-normal">· {{ $location->bn_name }}</span>@endif
            </h2>
            <p class="text-xs text-gray-500">{{ $label }} ·
                @if($location->is_active)<span class="text-green-600">Active</span>@else<span class="text-gray-500">Inactive</span>@endif
            </p>
        </div>
        <button type="button" @click="open = !open" class="px-4 py-2 border border-gray-200 rounded-xl text-sm text-gray-700 hover:bg-gray-50 transition">Edit {{ $label }}</button>
    </div>
    <form x-show="open" x-cloak action="{{ $updateUrl }}" method="POST" class="flex flex-wrap gap-3 items-center mt-4 pt-4 border-t border-gray-100">
        @csrf @method('PUT')
        <input type="text" name="name" value="{{ $location->name }}" required
            class="border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
        <input type="text" name="bn_name" value="{{ $location->bn_name }}" placeholder="বাংলা নাম"
            class="border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
        <label class="flex items-center gap-2 text-sm text-gray-700">
            <input type="checkbox" name="is_active" value="1" @checked($location->is_active) class="rounded border-gray-300 text-indigo-600"> Active
        </label>
        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-indigo-700 transition">Save</button>
    </form>
</div>
