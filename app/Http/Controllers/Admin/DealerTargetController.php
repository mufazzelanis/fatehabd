<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dealer;
use App\Models\DealerTarget;
use App\Models\District;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Monthly targets and settlement. Sales are tracked outside the system (phone etc.); at
 * month end the admin records each dealer's achieved amount here, which closes ("settles")
 * that month. Settlement is admin-only — never shown to dealers.
 */
class DealerTargetController extends Controller
{
    public function index(Request $request)
    {
        $month = DealerTarget::monthFrom($request->input('month'));

        $query = Dealer::approved()->with(['district', 'thana', 'union', 'targets' => fn ($q) => $q->where('month', $month)]);
        if ($request->filled('level')) {
            $query->where('level', $request->level);
        }
        if ($request->filled('district_id')) {
            $query->where('district_id', $request->district_id);
        }
        if ($request->filled('search')) {
            $query->where(fn ($q) => $q->where('name', 'like', '%' . $request->search . '%')->orWhere('phone', 'like', '%' . $request->search . '%'));
        }
        if ($request->input('state') === 'unsettled') {
            $query->whereHas('targets', fn ($q) => $q->where('month', $month)->whereNull('settled_at'));
        } elseif ($request->input('state') === 'settled') {
            $query->whereHas('targets', fn ($q) => $q->where('month', $month)->whereNotNull('settled_at'));
        } elseif ($request->input('state') === 'no_target') {
            $query->whereDoesntHave('targets', fn ($q) => $q->where('month', $month));
        }

        $dealers = $query->orderByRaw("FIELD(level, 'district', 'thana', 'union')")
            ->orderBy('district_id')->orderBy('thana_id')->orderBy('name')
            ->paginate(50)->withQueryString();

        $monthTotals = DealerTarget::where('month', $month)
            ->selectRaw('count(*) as targets, sum(amount) as target_sum, sum(settled_at is not null) as settled, sum(achieved_amount) as achieved_sum')
            ->first();
        $districts = District::orderBy('name')->get(['id', 'name']);

        return view('admin.business.targets.index', compact('dealers', 'month', 'monthTotals', 'districts'));
    }

    public function save(Request $request, Dealer $dealer)
    {
        $request->validate([
            'month' => 'required|date_format:Y-m',
            'amount' => 'required|numeric|min:0|max:999999999999',
            'note' => 'nullable|string|max:255',
        ]);

        $target = DealerTarget::firstOrNew(['dealer_id' => $dealer->id, 'month' => DealerTarget::monthFrom($request->month)]);
        $target->fill([
            'amount' => $request->amount,
            'note' => $request->note,
            'set_by_type' => 'admin',
            'set_by_id' => auth()->id(),
        ])->save();

        return back()->with('success', 'Target saved for ' . $dealer->name . '.');
    }

    public function settle(Request $request, DealerTarget $target)
    {
        $request->validate([
            'achieved_amount' => 'required|numeric|min:0|max:999999999999',
            'settlement_note' => 'nullable|string|max:1000',
        ]);

        $target->update([
            'achieved_amount' => $request->achieved_amount,
            'settlement_note' => $request->settlement_note,
            'settled_at' => now(),
            'settled_by' => auth()->id(),
        ]);

        return back()->with('success', 'Settled ' . $target->month->format('F Y') . ' for ' . $target->dealer->name . '.');
    }

    public function unsettle(DealerTarget $target)
    {
        $target->update(['achieved_amount' => null, 'settlement_note' => null, 'settled_at' => null, 'settled_by' => null]);

        return back()->with('success', 'Settlement reopened.');
    }

    /** Copies last month's target amounts to this month for approved dealers who have none yet. */
    public function copyPrevious(Request $request)
    {
        $request->validate(['month' => 'required|date_format:Y-m']);
        $month = DealerTarget::monthFrom($request->month);
        $previous = Carbon::parse($month)->subMonthNoOverflow()->toDateString();

        $existing = DealerTarget::where('month', $month)->pluck('dealer_id');
        $source = DealerTarget::where('month', $previous)
            ->whereNotIn('dealer_id', $existing)
            ->whereHas('dealer', fn ($q) => $q->approved())
            ->get();

        foreach ($source as $old) {
            DealerTarget::create([
                'dealer_id' => $old->dealer_id,
                'month' => $month,
                'amount' => $old->amount,
                'set_by_type' => 'admin',
                'set_by_id' => auth()->id(),
            ]);
        }

        return back()->with('success', $source->count() . ' target(s) copied from ' . Carbon::parse($previous)->format('F Y') . '.');
    }
}
