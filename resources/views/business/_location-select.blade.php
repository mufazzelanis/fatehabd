{{--
    Chained District → Thana → Union selects (options loaded from the business.locations.* JSON routes).
    Params: $districts (collection), $selected (['district_id','thana_id','union_id']), $inputClass,
            $labelClass, $unionRequired (bool), $showDistrict (bool, false = district/thana fixed & hidden).
--}}
@php
    $selected = $selected ?? [];
    $sel = fn ($k) => old($k, $selected[$k] ?? '');
    $unionRequired = $unionRequired ?? false;
    $showDistrict = $showDistrict ?? true;
@endphp
<div x-data="{
        district: @js((string) $sel('district_id')),
        thana: @js((string) $sel('thana_id')),
        union: @js((string) $sel('union_id')),
        thanas: [], unions: [],
        async loadThanas(reset) {
            if (reset) { this.thana = ''; this.union = ''; this.unions = []; }
            this.thanas = this.district ? await (await fetch(@js(url('business/locations/districts')) + '/' + this.district + '/thanas')).json() : [];
            const keep = this.thana; this.thana = ''; await this.$nextTick(); this.thana = keep;
        },
        async loadUnions(reset) {
            if (reset) { this.union = ''; }
            this.unions = this.thana ? await (await fetch(@js(url('business/locations/thanas')) + '/' + this.thana + '/unions')).json() : [];
            const keep = this.union; this.union = ''; await this.$nextTick(); this.union = keep;
        },
        async init() { if (this.district) await this.loadThanas(false); if (this.thana) await this.loadUnions(false); }
    }" class="grid grid-cols-1 {{ $showDistrict ? 'sm:grid-cols-3' : '' }} gap-4">

    @if($showDistrict)
    <div>
        <label class="{{ $labelClass }}">District *</label>
        <select name="district_id" x-model="district" @change="loadThanas(true)" required class="{{ $inputClass }}">
            <option value="">Select district</option>
            @foreach($districts as $d)
                <option value="{{ $d->id }}">{{ $d->name }}{{ $d->bn_name ? ' · ' . $d->bn_name : '' }}</option>
            @endforeach
        </select>
        @error('district_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="{{ $labelClass }}">Thana *</label>
        <select name="thana_id" x-model="thana" @change="loadUnions(true)" required :disabled="!district" class="{{ $inputClass }}">
            <option value="">Select thana</option>
            <template x-for="t in thanas" :key="t.id">
                <option :value="String(t.id)" x-text="t.name + (t.bn_name ? ' · ' + t.bn_name : '')"></option>
            </template>
        </select>
        @error('thana_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
    </div>
    @endif
    <div>
        <label class="{{ $labelClass }}">Union {{ $unionRequired ? '*' : '(optional)' }}</label>
        <select name="union_id" x-model="union" {{ $unionRequired ? 'required' : '' }} :disabled="!thana" class="{{ $inputClass }}">
            <option value="">{{ $unionRequired ? 'Select union' : 'Select union (optional)' }}</option>
            <template x-for="u in unions" :key="u.id">
                <option :value="String(u.id)" x-text="u.name + (u.bn_name ? ' · ' + u.bn_name : '')"></option>
            </template>
        </select>
        @error('union_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
    </div>
</div>
