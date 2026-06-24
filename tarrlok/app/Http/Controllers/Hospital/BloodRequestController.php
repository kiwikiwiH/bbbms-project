<?php

namespace App\Http\Controllers\Hospital;

use App\Http\Controllers\Controller;
use App\Models\BloodRequest;
use App\Models\BloodUnit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BloodRequestController extends Controller
{
    public function index(Request $request): View
    {
        $hospital = auth()->user()->hospital;
        $search = trim((string) $request->query('q', ''));

        $requests = BloodRequest::query()
            ->with('requestingHospital')
            ->where('fulfilling_hospital_id', $hospital->id)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('request_code', 'like', "%{$search}%")
                        ->orWhereHas('requestingHospital', fn ($h) => $h->where('name', 'like', "%{$search}%"));
                });
            })
            ->latest()
            ->get();

        return view('hospital.requests.index', [
            'hospital' => $hospital,
            'requests' => $requests,
            'search' => $search,
            'inventoryNote' => $hospital->availableUnitsCount(),
        ]);
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
                'stock' => 'Not enough '.$bloodRequest->blood_group.' units in inventory. Blood must be recorded by lab staff first (Blood Inventory).',
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

            foreach ($units as $unit) {
                $unit->update(['status' => 'issued']);
            }

            $bloodRequest->bloodUnits()->syncWithoutDetaching($units->pluck('id'));
            $bloodRequest->update([
                'status' => 'fulfilled',
                'fulfilled_at' => now(),
            ]);
        });

        return back()->with('status', $bloodRequest->request_code.' fulfilled — '.$bloodRequest->quantity.' unit(s) issued from your inventory.');
    }

    private function ensureIncoming(BloodRequest $bloodRequest): void
    {
        abort_unless(
            $bloodRequest->fulfilling_hospital_id === auth()->user()->hospital_id,
            403
        );
    }
}
