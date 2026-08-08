<?php

namespace App\Http\Controllers\Hospital;

use App\Http\Controllers\Controller;
use App\Models\BloodRequest;
use App\Models\BloodUnit;
use App\Models\Hospital;
use App\Services\BlockchainService;
use App\Services\DonorNotificationService;
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
        $bloodGroup = trim((string) $request->query('blood_group', ''));
        $view = $request->query('view', 'incoming') === 'outgoing' ? 'outgoing' : 'incoming';
        $allowedGroups = config('tarrlok.blood_groups');

        if ($bloodGroup !== '' && ! in_array($bloodGroup, $allowedGroups, true)) {
            $bloodGroup = '';
        }

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
            ->when($bloodGroup !== '', fn ($query) => $query->where('blood_group', $bloodGroup))
            ->latest();

        if ($view === 'outgoing') {
            $requests = (clone $query)
                ->with([
                    'fulfillingHospital',
                    'approvedByUser',
                    'rejectedByUser',
                    'fulfilledByUser',
                    'reversedByUser',
                ])
                ->where('requesting_hospital_id', $hospital->id)
                ->get();
        } else {
            $requests = (clone $query)
                ->with([
                    'requestingHospital',
                    'approvedByUser',
                    'rejectedByUser',
                    'fulfilledByUser',
                    'reversedByUser',
                ])
                ->where('fulfilling_hospital_id', $hospital->id)
                ->get()
                ->map(function (BloodRequest $req) use ($hospital) {
                    $onHand = $req->availableStockAt($hospital);
                    $free = $req->freeStockAt($hospital);
                    $req->setAttribute('stock_on_hand', $onHand);
                    $req->setAttribute('stock_available', $free);
                    $req->setAttribute('stock_sufficient', $free >= $req->quantity);
                    $req->setAttribute('stock_shortfall', max(0, $req->quantity - $free));
                    $req->setAttribute('stock_reserved_elsewhere', max(0, $onHand - $free - ($req->status === 'approved' ? $req->quantity : 0)));

                    return $req;
                });
        }

        $availableByGroup = $hospital->bloodUnits()
            ->available()
            ->selectRaw('blood_group, count(*) as total')
            ->groupBy('blood_group')
            ->pluck('total', 'blood_group');

        return view('hospital.requests.index', [
            'hospital' => $hospital,
            'requests' => $requests,
            'search' => $search,
            'bloodGroup' => $bloodGroup,
            'bloodGroups' => $allowedGroups,
            'view' => $view,
            'availableByGroup' => $availableByGroup,
            'inventoryNote' => $hospital->availableUnitsCount(),
            'incomingPending' => $hospital->incomingBloodRequests()->whereIn('status', ['pending', 'approved'])->count(),
            'outgoingPending' => $hospital->outgoingBloodRequests()->whereIn('status', ['pending', 'approved'])->count(),
            'insufficientIncoming' => $view === 'incoming'
                ? $requests->filter(fn (BloodRequest $req) => $req->isActionable() && ! $req->stock_sufficient)->count()
                : 0,
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

        $hospital = auth()->user()->hospital;
        $free = $bloodRequest->freeStockAt($hospital);

        if ($free < $bloodRequest->quantity) {
            $onHand = $bloodRequest->availableStockAt($hospital);
            $reserved = max(0, $onHand - $free);

            return back()->withErrors([
                'stock' => $bloodRequest->request_code.' cannot be approved: only '.$free.' free cleared '
                    .$bloodRequest->blood_group.' unit(s) ('.$onHand.' on hand'
                    .($reserved > 0 ? ', '.$reserved.' already reserved by other approved requests' : '')
                    .'), but '.$bloodRequest->quantity.' were requested. Reject the request or wait until lab clears more stock.',
            ]);
        }

        $bloodRequest->update([
            'status' => 'approved',
            'rejection_reason' => null,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'rejected_by' => null,
            'rejected_at' => null,
        ]);

        return back()->with('status', $bloodRequest->request_code.' approved ('.$free.' '.$bloodRequest->blood_group.' free). Issue units when ready.');
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

        $defaultReason = $bloodRequest->hasSufficientStockAt(auth()->user()->hospital)
            ? 'Rejected by fulfilling hospital.'
            : 'Insufficient cleared '.$bloodRequest->blood_group.' stock at fulfilling hospital.';

        $bloodRequest->update([
            'status' => 'rejected',
            'rejection_reason' => $validated['rejection_reason'] ?: $defaultReason,
            'rejected_by' => auth()->id(),
            'rejected_at' => now(),
            'approved_by' => $bloodRequest->approved_by,
            'approved_at' => $bloodRequest->approved_at,
        ]);

        return back()->with('status', $bloodRequest->request_code.' rejected.');
    }

    public function reverse(Request $request, BloodRequest $bloodRequest): RedirectResponse
    {
        $this->ensureIncoming($bloodRequest);

        $validated = $request->validate([
            'reverse_reason' => ['nullable', 'string', 'max:500'],
        ]);

        if ($bloodRequest->status !== 'approved') {
            return back()->with('status', 'Only approved (not yet issued) requests can be reversed.');
        }

        $bloodRequest->update([
            'status' => 'pending',
            'reversed_by' => auth()->id(),
            'reversed_at' => now(),
            'reverse_reason' => $validated['reverse_reason'] ?: 'Approval reversed before issue.',
        ]);

        return back()->with('status', $bloodRequest->request_code.' reversed to pending. You can approve again when stock is ready, or reject.');
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

        $hospital = auth()->user()->hospital;
        $free = $bloodRequest->freeStockAt($hospital);

        if ($free < $bloodRequest->quantity) {
            return back()->withErrors([
                'stock' => 'Not enough free cleared '.$bloodRequest->blood_group.' units (have '.$free.' free, need '.$bloodRequest->quantity.'). Reject or reverse other approvals first, or wait for lab stock.',
            ]);
        }

        if ($bloodRequest->status === 'pending') {
            $bloodRequest->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);
        }

        try {
            $anchorFailures = DB::transaction(function () use ($bloodRequest, $hospital) {
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
                $actor = auth()->user();
                $blockchainEnabled = $blockchain->isEnabled();
                $anchorFailures = [];

                foreach ($units as $unit) {
                    $txHash = $blockchain->recordIssue(
                        $unit->unit_code,
                        $hospital->id,
                        $bloodRequest->requesting_hospital_id,
                        $bloodRequest->request_code,
                        $actor->id,
                        $actor->name
                    );

                    if ($blockchainEnabled && ! $txHash) {
                        $anchorFailures[] = $unit->unit_code;
                    }

                    $unit->update([
                        'hospital_id' => $bloodRequest->requesting_hospital_id,
                        'status' => 'available',
                        ...($txHash ? ['blockchain_issue_tx' => $txHash] : []),
                    ]);

                    app(DonorNotificationService::class)->notifyStatusChange($unit->fresh(), 'issued');
                }

                $bloodRequest->bloodUnits()->syncWithoutDetaching($units->pluck('id'));
                $bloodRequest->update([
                    'status' => 'fulfilled',
                    'fulfilled_at' => now(),
                    'fulfilled_by' => auth()->id(),
                ]);

                return $anchorFailures;
            });
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'insufficient_stock') {
                return back()->withErrors([
                    'stock' => 'Not enough cleared '.$bloodRequest->blood_group.' units in inventory. Lab staff must register units and complete screening first.',
                ]);
            }

            throw $e;
        }

        $bloodRequest->loadMissing('requestingHospital');

        $message = $bloodRequest->request_code.' fulfilled — '.$bloodRequest->quantity.' unit(s) transferred to '.$bloodRequest->requestingHospital->name.'.';

        if (! empty($anchorFailures)) {
            $message .= ' Warning: blockchain anchor failed for '.implode(', ', $anchorFailures).'. Inventory transfer still completed.';
        }

        return back()->with('status', $message);
    }

    public function cancel(Request $request, BloodRequest $bloodRequest): RedirectResponse
    {
        abort_unless(
            $bloodRequest->requesting_hospital_id === auth()->user()->hospital_id,
            403
        );

        $validated = $request->validate([
            'rejection_reason' => ['nullable', 'string', 'max:500'],
        ]);

        if (! in_array($bloodRequest->status, ['pending', 'approved'], true)) {
            return back()->with('status', 'Only pending or approved outgoing requests can be cancelled.');
        }

        $bloodRequest->update([
            'status' => 'rejected',
            'rejection_reason' => $validated['rejection_reason'] ?: 'Cancelled by requesting hospital.',
            'rejected_by' => auth()->id(),
            'rejected_at' => now(),
        ]);

        return back()->with('status', $bloodRequest->request_code.' cancelled.');
    }

    private function ensureIncoming(BloodRequest $bloodRequest): void
    {
        abort_unless(
            $bloodRequest->fulfilling_hospital_id === auth()->user()->hospital_id,
            403
        );
    }
}
