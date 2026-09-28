<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\Dealer;
use App\Models\DealerTarget;
use App\Services\DealerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Dealer panel. Shows the dealer's own monthly target and lets district/thana dealers set
 * targets for the dealers one level below them. Settlement data (achieved amount etc.) is
 * deliberately never exposed here — dealers must contact the admin for that.
 */
class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $dealer = $this->dealer()->load(['district', 'thana', 'union']);
        $month = DealerTarget::monthFrom($request->input('month'));
        $currentMonth = DealerTarget::currentMonth();

        $myTarget = $dealer->targets()->where('month', $currentMonth)->first();
        $nextTarget = $dealer->targets()->where('month', now()->addMonthNoOverflow()->startOfMonth()->toDateString())->first();

        $rows = collect();
        if ($dealer->level === 'district') {
            // Every thana in the district, with its thana dealer (if one is assigned)
            $dealers = $dealer->subordinates()->with(['targets' => fn ($q) => $q->where('month', $month)])->get()->keyBy('thana_id');
            $unionCounts = Dealer::approved()->where('level', 'union')->where('district_id', $dealer->district_id)
                ->selectRaw('thana_id, count(*) as c')->groupBy('thana_id')->pluck('c', 'thana_id');
            $rows = $dealer->district->thanas()->active()->orderBy('name')->get()->map(fn ($thana) => (object) [
                'place' => $thana->name,
                'dealer' => $dealers->get($thana->id),
                'unionDealers' => $unionCounts[$thana->id] ?? 0,
            ]);
        } elseif ($dealer->level === 'thana') {
            $rows = $dealer->subordinates()->with(['union', 'targets' => fn ($q) => $q->where('month', $month)])
                ->orderBy('name')->get()->map(fn ($d) => (object) [
                    'place' => $d->union?->name ?? '—',
                    'dealer' => $d,
                    'unionDealers' => null,
                ]);
        }

        $assignedTotal = $rows->sum(fn ($r) => $r->dealer?->targetFor($month)?->amount ?? 0);

        return view('business.dashboard', compact('dealer', 'month', 'currentMonth', 'myTarget', 'nextTarget', 'rows', 'assignedTotal'));
    }

    public function setTarget(Request $request, Dealer $subordinate)
    {
        $dealer = $this->dealer();
        abort_unless($dealer->manages($subordinate), 403);

        $request->validate([
            'month' => 'required|date_format:Y-m',
            'amount' => 'required|numeric|min:0|max:999999999999',
        ]);
        $month = DealerTarget::monthFrom($request->month);

        if ($month < DealerTarget::currentMonth()) {
            return back()->with('error', 'Targets can only be set for the current or a future month.');
        }

        $target = DealerTarget::firstOrNew(['dealer_id' => $subordinate->id, 'month' => $month]);
        if ($target->isSettled()) {
            return back()->with('error', 'This month is already closed for ' . $subordinate->name . '. Please contact the admin.');
        }

        $target->fill(['amount' => $request->amount, 'set_by_type' => 'dealer', 'set_by_id' => $dealer->id])->save();

        return back()->with('success', 'Target set for ' . $subordinate->name . '.');
    }

    public function createUnionDealer()
    {
        $dealer = $this->dealer();
        abort_unless($dealer->level === 'thana', 403);

        return view('business.union-dealer-create', compact('dealer'));
    }

    /**
     * A thana dealer adds union dealers in their own thana. These go live immediately
     * (the thana dealer vouches for them); the admin can still suspend any account.
     */
    public function storeUnionDealer(Request $request, DealerService $service)
    {
        $dealer = $this->dealer();
        abort_unless($dealer->level === 'thana', 403);

        // Location is fixed to the creating dealer's own thana, whatever the form posted.
        $request->merge(['district_id' => $dealer->district_id, 'thana_id' => $dealer->thana_id]);
        $request->validate(['union_id' => 'required']);

        $data = $service->validateAndStore($request, requireDocuments: true);

        Dealer::create($data + [
            'level' => 'union',
            'status' => 'approved',
            'approved_at' => now(),
            'created_by_dealer_id' => $dealer->id,
        ]);

        return redirect()->route('business.dashboard')->with('success', 'Union dealer ' . $data['name'] . ' added.');
    }

    private function dealer(): Dealer
    {
        return Auth::guard('dealer')->user();
    }
}
