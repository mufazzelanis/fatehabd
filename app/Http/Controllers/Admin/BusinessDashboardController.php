<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dealer;
use App\Models\DealerTarget;
use App\Models\District;
use App\Models\Thana;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Business (dealership) overview. Targets nest — a thana target is part of its district's
 * target — so totals are reported per level rather than summed across levels, which would
 * double-count. "Sales" means settled achieved amounts.
 */
class BusinessDashboardController extends Controller
{
    public function index(Request $request)
    {
        $month = DealerTarget::monthFrom($request->input('month'));

        $statusCounts = Dealer::selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status');
        $levelCounts = Dealer::approved()->selectRaw('level, count(*) as c')->groupBy('level')->pluck('c', 'level');
        $coverage = [
            'districts' => District::active()->count(),
            'thanas' => Thana::active()->count(),
        ];

        $levelStats = DealerTarget::join('dealers', 'dealers.id', '=', 'dealer_targets.dealer_id')
            ->where('dealer_targets.month', $month)
            ->groupBy('dealers.level')
            ->selectRaw('dealers.level, count(*) as targets, sum(dealer_targets.amount) as target_sum,
                sum(dealer_targets.settled_at is not null) as settled, sum(dealer_targets.achieved_amount) as achieved_sum')
            ->get()->keyBy('level');

        $topPeriod = $request->input('top') === 'all' ? 'all' : 'month';
        $topDealers = Dealer::with(['district', 'thana', 'union'])
            ->joinSub(
                DealerTarget::whereNotNull('settled_at')
                    ->when($topPeriod === 'month', fn ($q) => $q->where('month', $month))
                    ->groupBy('dealer_id')
                    ->selectRaw('dealer_id, sum(achieved_amount) as achieved, sum(amount) as target'),
                'sales', 'sales.dealer_id', '=', 'dealers.id'
            )
            ->orderByDesc('sales.achieved')
            ->limit(10)
            ->get(['dealers.*', 'sales.achieved', 'sales.target']);

        $selectedDistrict = $request->filled('district') ? District::find($request->district) : null;

        return view('admin.business.dashboard', [
            'month' => $month,
            'statusCounts' => $statusCounts,
            'levelCounts' => $levelCounts,
            'coverage' => $coverage,
            'levelStats' => $levelStats,
            'topDealers' => $topDealers,
            'topPeriod' => $topPeriod,
            'selectedDistrict' => $selectedDistrict,
            'rows' => $selectedDistrict ? $this->thanaRows($selectedDistrict, $month) : $this->districtRows($month),
        ]);
    }

    /** One row per district: its district dealer, dealer counts below it, and the district dealer's target/sales. */
    private function districtRows(string $month)
    {
        $districtDealers = Dealer::approved()->where('level', 'district')
            ->with(['targets' => fn ($q) => $q->where('month', $month)])->get()->keyBy('district_id');
        $counts = Dealer::approved()->whereIn('level', ['thana', 'union'])
            ->groupBy('district_id')
            ->selectRaw("district_id, sum(level = 'thana') as thana_dealers, sum(level = 'union') as union_dealers")
            ->get()->keyBy('district_id');

        return District::withCount('thanas')->orderBy('name')->get()->map(function ($district) use ($districtDealers, $counts, $month) {
            $dealer = $districtDealers->get($district->id);
            $target = $dealer?->targetFor($month);

            return (object) [
                'id' => $district->id,
                'name' => $district->name,
                'places' => $district->thanas_count,
                'dealer' => $dealer,
                'thanaDealers' => (int) ($counts[$district->id]->thana_dealers ?? 0),
                'unionDealers' => (int) ($counts[$district->id]->union_dealers ?? 0),
                'target' => $target?->amount,
                'achieved' => $target?->achieved_amount,
                'settled' => $target?->isSettled() ?? false,
            ];
        });
    }

    /** Drill-down: one row per thana of the district, with its thana dealer's target/sales. */
    private function thanaRows(District $district, string $month)
    {
        $thanaDealers = Dealer::approved()->where('level', 'thana')->where('district_id', $district->id)
            ->with(['targets' => fn ($q) => $q->where('month', $month)])->get()->keyBy('thana_id');
        $unionCounts = Dealer::approved()->where('level', 'union')->where('district_id', $district->id)
            ->groupBy('thana_id')->select('thana_id', DB::raw('count(*) as c'))->pluck('c', 'thana_id');

        return $district->thanas()->withCount('unions')->orderBy('name')->get()->map(function ($thana) use ($thanaDealers, $unionCounts, $month) {
            $dealer = $thanaDealers->get($thana->id);
            $target = $dealer?->targetFor($month);

            return (object) [
                'id' => $thana->id,
                'name' => $thana->name,
                'places' => $thana->unions_count,
                'dealer' => $dealer,
                'thanaDealers' => null,
                'unionDealers' => (int) ($unionCounts[$thana->id] ?? 0),
                'target' => $target?->amount,
                'achieved' => $target?->achieved_amount,
                'settled' => $target?->isSettled() ?? false,
            ];
        });
    }
}
