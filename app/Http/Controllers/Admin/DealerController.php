<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dealer;
use App\Models\District;
use App\Services\DealerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DealerController extends Controller
{
    public function index(Request $request)
    {
        $query = Dealer::with(['district', 'thana', 'union']);

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('phone', 'like', '%' . $request->search . '%')
                  ->orWhere('nid_number', 'like', '%' . $request->search . '%');
            });
        }
        foreach (['status', 'level', 'district_id', 'thana_id'] as $filter) {
            if ($request->filled($filter)) {
                $query->where($filter, $request->$filter);
            }
        }

        $dealers = $query->latest()->paginate(25);
        $statusCounts = Dealer::selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status');
        $districts = District::orderBy('name')->get(['id', 'name']);

        return view('admin.business.dealers.index', compact('dealers', 'statusCounts', 'districts'));
    }

    public function create()
    {
        $districts = District::orderBy('name')->get(['id', 'name']);

        return view('admin.business.dealers.create', compact('districts'));
    }

    public function store(Request $request, DealerService $service)
    {
        $request->validate(['level' => ['required', Rule::in(array_keys(Dealer::LEVELS))]]);
        // Slot check before validateAndStore() so a rejected request leaves no orphaned uploads
        $this->ensureSlotFree($request->level, $request->only(['district_id', 'thana_id', 'union_id']));
        $data = $service->validateAndStore($request, requireDocuments: false);

        $dealer = Dealer::create($data + ['level' => $request->level, 'status' => 'approved', 'approved_at' => now()]);

        return redirect()->route('admin.business.dealers.show', $dealer)->with('success', 'Dealer created and activated.');
    }

    public function show(Dealer $dealer)
    {
        $dealer->load(['district', 'thana', 'union', 'createdBy']);
        $targets = $dealer->targets()->with('settledByUser')->orderByDesc('month')->limit(24)->get();
        $subordinateCount = $dealer->subordinates()->count();

        return view('admin.business.dealers.show', compact('dealer', 'targets', 'subordinateCount'));
    }

    public function edit(Dealer $dealer)
    {
        $districts = District::orderBy('name')->get(['id', 'name']);

        return view('admin.business.dealers.edit', compact('dealer', 'districts'));
    }

    public function update(Request $request, Dealer $dealer, DealerService $service)
    {
        $request->validate(['level' => ['nullable', Rule::in(array_keys(Dealer::LEVELS))]]);
        if ($dealer->isApproved()) {
            $this->ensureSlotFree($request->level, $request->only(['district_id', 'thana_id', 'union_id']), $dealer->id);
        }
        $data = $service->validateAndStore($request, requireDocuments: false, dealer: $dealer, requirePassword: false);

        $dealer->update($data + ['level' => $request->level]);

        return redirect()->route('admin.business.dealers.show', $dealer)->with('success', 'Dealer updated.');
    }

    public function approve(Request $request, Dealer $dealer)
    {
        $request->validate(['level' => ['required', Rule::in(array_keys(Dealer::LEVELS))]]);
        $this->ensureSlotFree($request->level, $dealer->only(['district_id', 'thana_id', 'union_id']), $dealer->id);

        $dealer->update([
            'level' => $request->level,
            'status' => 'approved',
            'approved_at' => $dealer->approved_at ?? now(),
            'admin_note' => null,
        ]);

        return back()->with('success', $dealer->name . ' approved as ' . $dealer->levelLabel() . '.');
    }

    public function reject(Request $request, Dealer $dealer)
    {
        $request->validate(['admin_note' => 'nullable|string|max:1000']);
        $dealer->update(['status' => 'rejected', 'admin_note' => $request->admin_note]);

        return back()->with('success', 'Application rejected.');
    }

    public function suspend(Request $request, Dealer $dealer)
    {
        $request->validate(['admin_note' => 'nullable|string|max:1000']);
        $dealer->update(['status' => 'suspended', 'admin_note' => $request->admin_note]);

        return back()->with('success', $dealer->name . ' suspended.');
    }

    public function activate(Dealer $dealer)
    {
        if (! $dealer->level) {
            return back()->with('error', 'Assign a dealer level first (use Approve).');
        }
        $this->ensureSlotFree($dealer->level, $dealer->only(['district_id', 'thana_id', 'union_id']), $dealer->id);
        $dealer->update(['status' => 'approved', 'approved_at' => $dealer->approved_at ?? now(), 'admin_note' => null]);

        return back()->with('success', $dealer->name . ' re-activated.');
    }

    public function destroy(Dealer $dealer, DealerService $service)
    {
        $service->deleteFiles($dealer);
        $dealer->delete();

        return redirect()->route('admin.business.dealers.index')->with('success', 'Dealer deleted.');
    }

    public function document(Dealer $dealer, string $field)
    {
        abort_unless(in_array($field, DealerService::DOCUMENTS, true), 404);
        $path = $dealer->$field;
        abort_if(! $path || ! Storage::disk('private')->exists($path), 404);

        return Storage::disk('private')->response($path);
    }

    /**
     * One approved district dealer per district, one approved thana dealer per thana, and a
     * union dealer needs a union.
     */
    private function ensureSlotFree(?string $level, array $location, ?int $exceptId = null): void
    {
        if ($level === 'union' && empty($location['union_id'])) {
            throw ValidationException::withMessages(['level' => 'Select a union before making this a union dealer.']);
        }

        $existing = Dealer::levelConflict($level, $location['district_id'] ?? null, $location['thana_id'] ?? null, $exceptId);
        if ($existing) {
            throw ValidationException::withMessages([
                'level' => "{$existing->name} ({$existing->phone}) is already the " . strtolower(Dealer::LEVELS[$level])
                    . ' for this ' . ($level === 'district' ? 'district' : 'thana') . '. Suspend or change them first.',
            ]);
        }
    }
}
