{{--
    Child-location list shared by the district page (its thanas) and the thana page (its unions).
    Params: $items, $label ('Thana'|'Union'), $storeUrl, $updateRoute, $destroyRoute,
            $showRoute (nullable — row links to its own page), $countAttr/$countLabel (nullable).
--}}
<div class="bg-white rounded-2xl shadow-sm p-4 mb-4">
    <form action="{{ $storeUrl }}" method="POST" class="flex flex-wrap gap-3 items-start">
        @csrf
        <input type="text" name="name" required placeholder="New {{ strtolower($label) }} name (English)"
            class="border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 flex-1 min-w-[180px]">
        <input type="text" name="bn_name" placeholder="বাংলা নাম (optional)"
            class="border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 flex-1 min-w-[180px]">
        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-indigo-700 transition">Add {{ $label }}</button>
    </form>
</div>

<div class="bg-white rounded-2xl shadow-sm overflow-x-auto">
    <table class="w-full">
        <thead class="bg-gray-50 border-b border-gray-100">
            <tr class="text-xs text-gray-500 uppercase tracking-wider">
                <th class="px-6 py-3 text-left">{{ $label }}</th>
                @if($countAttr)<th class="px-6 py-3 text-center">{{ $countLabel }}</th>@endif
                <th class="px-6 py-3 text-center">Status</th>
                <th class="px-6 py-3 text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse($items as $item)
            <tr class="hover:bg-gray-50 transition" x-data="{ editing: false }">
                <td class="px-6 py-4">
                    <div x-show="!editing">
                        @if($showRoute)
                            <a href="{{ route($showRoute, $item) }}" class="text-sm font-medium text-gray-900 hover:text-indigo-600">{{ $item->name }}</a>
                        @else
                            <p class="text-sm font-medium text-gray-900">{{ $item->name }}</p>
                        @endif
                        @if($item->bn_name)<p class="text-xs text-gray-400">{{ $item->bn_name }}</p>@endif
                    </div>
                    <form x-show="editing" x-cloak action="{{ route($updateRoute, $item) }}" method="POST" class="flex flex-wrap gap-2 items-center">
                        @csrf @method('PUT')
                        <input type="text" name="name" value="{{ $item->name }}" required
                            class="border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <input type="text" name="bn_name" value="{{ $item->bn_name }}" placeholder="বাংলা নাম"
                            class="border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <label class="flex items-center gap-1.5 text-xs text-gray-600">
                            <input type="checkbox" name="is_active" value="1" @checked($item->is_active) class="rounded border-gray-300 text-indigo-600"> Active
                        </label>
                        <button type="submit" class="bg-indigo-600 text-white px-3 py-1.5 rounded-lg text-xs font-medium hover:bg-indigo-700">Save</button>
                        <button type="button" @click="editing = false" class="px-3 py-1.5 border border-gray-200 rounded-lg text-xs text-gray-600 hover:bg-gray-50">Cancel</button>
                    </form>
                </td>
                @if($countAttr)<td class="px-6 py-4 text-center text-sm text-gray-700">{{ $item->$countAttr }}</td>@endif
                <td class="px-6 py-4 text-center">
                    @if($item->is_active)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Active</span>
                    @else
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500">Inactive</span>
                    @endif
                </td>
                <td class="px-6 py-4 text-right">
                    <div class="flex justify-end space-x-3">
                        @if($showRoute)<a href="{{ route($showRoute, $item) }}" class="text-gray-600 hover:text-gray-900 text-sm font-medium">Open</a>@endif
                        <button type="button" @click="editing = !editing" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">Edit</button>
                        <form action="{{ route($destroyRoute, $item) }}" method="POST"
                            onsubmit="return confirm('Delete {{ strtolower($label) }} “{{ addslashes($item->name) }}”?{{ $countAttr ? ' All its ' . strtolower($countLabel) . ' will be deleted too.' : '' }}')">
                            @csrf @method('DELETE')
                            <button class="text-red-500 hover:text-red-700 text-sm font-medium">Delete</button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="4" class="px-6 py-12 text-center text-gray-400 text-sm">No {{ strtolower($label) }}s yet — add one above.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
