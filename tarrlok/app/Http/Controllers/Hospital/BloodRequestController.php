<?php

namespace App\Http\Controllers\Hospital;

use App\Http\Controllers\Controller;
use App\Models\BloodRequest;
use App\Models\BloodUnit;
use App\Models\Hospital;
use App\Services\BlockchainService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BloodRequestController extends Controller
{
    public function index(Request $request): View
    {
        $hospital = auth()->user()->hospital;
        $search = trim((string) $request->query('q', ''));
        $view = $request->query('view', 'incoming') === 'outgoing' ? 'outgoing' : 'incoming';

        $query = BloodRequest::query()
            ->when($search !== '', function ($query) use ($search, $view) {
                $query->where(function ($q) use ($search, $view) {
                    $q->where('request_code', 'like', "%{$search}%");
                    if ($view === 'incoming') {
                        $q->orWhereHas('requestingHospital', fn ($h) => $h->where('name', 'like', "%{$search}%"));
                    } else {
                        $q->orWhereHas('fulfillingHospital', fn ($h) => $h->where('name', 'like', "%{$search}%"));
                    }
                });
            })
            ->latest();

        if ($view === 'outgoing') {
            $requests = (clone $query)
                ->with('fulfillingHospital')
                ->where('requesting_hospital_id', $hospital->id)
                ->get();
        } else {
            $requests = (clone $query)
                ->with('requestingHospital')
                ->where('fulfilling_hospital_id', $hospital->id)
                ->get();
        }

        return view('hospital.requests.index', [
            'hospital' => $hospital,
            'requests' => $requests,
            'search' => $search,
            'view' => $view,
            'inventoryNote' => $hospital->availableUnitsCount(),
            'incomingPending' => $hospital->incomingBloodRequests()->whereIn('status', ['pending', 'approved'])->count(),
            'outgoingPending' => $hospital->outgoingBloodRequests()->whereIn('status', ['pending', 'approved'])->count(),
        ]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        $hospital = auth()->user()->hospital;
        $partnerId = $request->integer('partner');

        $partners = Hospital::query()
            ->where('status', 'approved')
            ->where('id', '!=', $hospital->id)
            ->orderBy('name')
            ->get();

        if ($partners->isEmpty()) {
            return redirect()
                ->route('hospital.partners')
                ->with('status', 'No partner hospitals on the network yet. Another facility must register and be approved first.');
        }

        $selectedPartner = $partnerId
            ? $partners->firstWhere('id', $partnerId)
            : null;

        if ($partnerId && ! $selectedPartner) {
            return redirect()
                ->route('hospital.requests.create')
                ->withErrors(['partner' => 'That partner hospital is not available.']);
        }

        return view('hospital.requests.create', [
            'hospital' => $hospital,
            'partners' => $partners,
            'selectedPartner' => $selectedPartner,
            'bloodGroups' => config('tarrlok.blood_groups'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $hospital = auth()->user()->hospital;

        $validated = $request->validate([
            'fulfilling_hospital_id' => [
                'required',
                'integer',
                Rule::exists('hospitals', 'id')->where('status', 'approved'),
                Rule::notIn([$hospital->id]),
            ],
            'blood_group' => ['required', 'string', 'in:'.implode(',', config('tarrlok.blood_groups'))],
            'quantity' => ['required', 'integer', 'min:1', 'max:50'],
            'urgency' => ['required', 'in:emergency,routine'],
        ]);

        $partner = Hospital::query()
            ->where('id', $validated['fulfilling_hospital_id'])
            ->where('status', 'approved')
            ->where('id', '!=', $hospital->id)
            ->firstOrFail();

        $bloodRequest = BloodRequest::create([
            'requesting_hospital_id' => $hospital->id,
            'fulfilling_hospital_id' => $partner->id,
            'blood_group' => $validated['blood_group'],
            'quantity' => $validated['quantity'],
            'urgency' => $validated['urgency'],
            'status' => 'pending',
        ]);

        return redirect()
            ->route('hospital.requests', ['view' => 'outgoing'])
            ->with('status', 'Request '.$bloodRequest->request_code.' sent to '.$partner->name.'.');
    }

    public function approve(BloodRequest $bloodRequest): RedirectResponse
    {
        $this->ensureIncoming($bloodRequest);

        if ($bloodRequest->status !== 'pending') {
            return back()->with('status', 'Only pending requests can be approved.');
        }

        $bloodRequest->update(['status' => 'approved', 'rejection_reason' => null]);

        return back()->with('status', $bloodRequest->request_code.' approved. Issue units when ready.');
    }

    public function reject(Request $request, BloodRequest $bloodRequest): RedirectResponse
    {
        $this->ensureIncoming($bloodRequest);

        $validated = $request->validate([
            'rejection_reason' => ['nullable', 'string', 'max:500'],
        ]);

        if (! in_array($bloodRequest->status, ['pending', 'approved'], true)) {
            return back()->with('status', 'This request can no longer be rejected.');
        }

        $bloodRequest->update([
            'status' => 'rejected',
            'rejection_reason' => $validated['rejection_reason'] ?? 'Rejected by fulfilling hospital.',
        ]);

        return back()->with('status', $bloodRequest->request_code.' rejected.');
    }

    public function issue(BloodRequest $bloodRequest): RedirectResponse
    {
        $this->ensureIncoming($bloodRequest);

        if ($bloodRequest->status === 'fulfilled') {
            return back()->with('status', 'This request is already fulfilled.');
        }

        if ($bloodRequest->status === 'rejected') {
            return back()->with('status', 'Rejected requests cannot be fulfilled.');
        }

        if ($bloodRequest->status === 'pending') {
            $bloodRequest->update(['status' => 'approved']);
        }

        $hospital = auth()->user()->hospital;

        if ($hospital->availableUnitsCount($bloodRequest->blood_group) < $bloodRequest->quantity) {
            return back()->withErrors([
                'stock' => 'Not enough cleared '.$bloodRequest->blood_group.' units in inventory. Lab staff must register units and complete screening first.',
            ]);
        }

        DB::transaction(function () use ($bloodRequest, $hospital) {
            $units = BloodUnit::query()
                ->where('hospital_id', $hospital->id)
                ->where('blood_group', $bloodRequest->blood_group)
                ->available()
                ->orderBy('collected_at')
                ->limit($bloodRequest->quantity)
                ->lockForUpdate()
                ->get();

            if ($units->count() < $bloodRequest->quantity) {
                throw new \RuntimeException('insufficient_stock');
            }

            $blockchain = app(BlockchainService::class);

            foreach ($units as $unit) {
                $unit->update([
                    'hospital_id' => $bloodRequest->requesting_hospital_id,
                    'status' => 'available',
                ]);

                $txHash = $blockchain->recordIssue(
                    $unit->unit_code,
                    $hospital->id,
                    $bloodRequest->requesting_hospital_id,
                    $bloodRequest->request_code
                );

                if ($txHash) {
                    $unit->update(['blockchain_issue_tx' => $txHash]);
                }
            }

            $bloodRequest->bloodUnits()->syncWithoutDetaching($units->pluck('id'));
            $bloodRequest->update([
                'status' => 'fulfilled',
                'fulfilled_at' => now(),
            ]);
        });

        $bloodRequest->loadMissing('requestingHospital');

        return back()->with('status', $bloodRequest->request_code.' fulfilled — '.$bloodRequest->quantity.' unit(s) transferred to '.$bloodRequest->requestingHospital->name.'.');
    }

    private function ensureIncoming(BloodRequest $bloodRequest): void
    {
        abort_unless(
            $bloodRequest->fulfilling_hospital_id === auth()->user()->hospital_id,
            403
        );
    }
}
