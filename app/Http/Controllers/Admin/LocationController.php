<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dealer;
use App\Models\District;
use App\Models\Division;
use App\Models\Thana;
use App\Models\Union;
use Illuminate\Http\Request;

/**
 * District → Thana → Union management for the dealership system. The 64 districts are
 * fixed (edit/activate only); thanas and unions can be added, renamed or removed since
 * the admin's working list (e.g. Dhaka metro thanas) differs from the official upazilas.
 */
class LocationController extends Controller
{
    public function index(Request $request)
    {
        $query = District::with('division')->withCount('thanas');

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('bn_name', 'like', '%' . $request->search . '%');
            });
        }
        if ($request->filled('division')) {
            $query->where('division_id', $request->division);
        }

        $districts = $query->orderBy('name')->get();
        $divisions = Division::orderBy('name')->get();

        return view('admin.business.locations.index', compact('districts', 'divisions'));
    }

    public function district(District $district)
    {
        $district->load('division');
        $thanas = $district->thanas()->withCount('unions')->orderBy('name')->get();

        return view('admin.business.locations.district', compact('district', 'thanas'));
    }

    public function updateDistrict(Request $request, District $district)
    {
        $district->update($this->validated($request) + ['is_active' => $request->boolean('is_active')]);

        return back()->with('success', 'District updated.');
    }

    public function storeThana(Request $request, District $district)
    {
        $district->thanas()->create($this->validated($request));

        return back()->with('success', 'Thana added.');
    }

    public function thana(Thana $thana)
    {
        $thana->load('district');
        $unions = $thana->unions()->orderBy('name')->get();

        return view('admin.business.locations.thana', compact('thana', 'unions'));
    }

    public function updateThana(Request $request, Thana $thana)
    {
        $thana->update($this->validated($request) + ['is_active' => $request->boolean('is_active')]);

        return back()->with('success', 'Thana updated.');
    }

    public function destroyThana(Thana $thana)
    {
        if (Dealer::where('thana_id', $thana->id)->exists()) {
            return back()->with('error', 'This thana has dealers assigned — move or delete them first.');
        }
        $district = $thana->district;
        $thana->delete();

        return redirect()->route('admin.business.locations.district', $district)->with('success', 'Thana deleted.');
    }

    public function storeUnion(Request $request, Thana $thana)
    {
        $thana->unions()->create($this->validated($request));

        return back()->with('success', 'Union added.');
    }

    public function updateUnion(Request $request, Union $union)
    {
        $union->update($this->validated($request) + ['is_active' => $request->boolean('is_active')]);

        return back()->with('success', 'Union updated.');
    }

    public function destroyUnion(Union $union)
    {
        if (Dealer::where('union_id', $union->id)->exists()) {
            return back()->with('error', 'This union has dealers assigned — move or delete them first.');
        }
        $union->delete();

        return back()->with('success', 'Union deleted.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name'    => 'required|string|max:255',
            'bn_name' => 'nullable|string|max:255',
        ]);
    }
}
