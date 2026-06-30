<?php

namespace App\Http\Controllers\Lab;

use App\Http\Controllers\Controller;
use App\Models\BloodUnit;
use App\Models\Donor;
use App\Services\BlockchainService;
use App\Services\ExpiryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BloodUnitController extends Controller
{
    public function index(ExpiryService $expiry): View
    {
        $expiry->discardExpiredUnits();

        $user = auth()->user();
        $hospital = $user->hospital;

        $units = $hospital->bloodUnits()
            ->with(['recorder', 'screener', 'donor'])
            ->latest('collected_at')
            ->paginate(20);

        return view('lab.units.index', [
            'user' => $user,
            'hospital' => $hospital,
            'units' => $units,
            'recordedByYou' => $hospital->bloodUnits()->where('recorded_by', $user->id)->count(),
            'availableCount' => $hospital->availableUnitsCount(),
            'pendingScreening' => $hospital->bloodUnits()->pendingScreening()->count(),
            'expiringSoon' => $hospital->bloodUnits()->where('status', 'available')->expiringSoon()->count(),
            'expiredCount' => $hospital->bloodUnits()
                ->where('status', 'discarded')
                ->whereNotNull('expires_at')
                ->where('expires_at', '<=', now())
                ->count(),
        ]);
    }

    public function slip(BloodUnit $bloodUnit): View
    {
        abort_unless($bloodUnit->hospital_id === auth()->user()->hospital_id, 403);

        return view('lab.units.slip', [
            'unit' => $bloodUnit->load('hospital', 'donor'),
            'hospital' => auth()->user()->hospital,
            'trackUrl' => route('track.show', $bloodUnit),
        ]);
    }

    public function create(): View
    {
        return view('lab.units.create', [
            'bloodGroups' => config('tarrlok.blood_groups'),
            'shelfLifeDays' => config('tarrlok.blood_shelf_life_days', 35),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'blood_group' => ['required', 'string', 'in:'.implode(',', config('tarrlok.blood_groups'))],
            'collected_at' => ['required', 'date', 'before_or_equal:today'],
            'donor_phone' => ['required', 'string', 'max:30'],
            'donor_name' => ['required', 'string', 'max:255'],
            'donor_email' => ['nullable', 'email', 'max:255'],
        ]);

        $user = auth()->user();
        $hospital = $user->hospital;
        $phone = Donor::normalizePhone($validated['donor_phone']);
        $donor = Donor::query()->where('phone', $phone)->first();

        if (! $donor) {
            $donor = Donor::create([
                'donor_code' => Donor::generateDonorCode(),
                'name' => $validated['donor_name'],
                'phone' => $phone,
                'email' => $validated['donor_email'] ?? null,
                'blood_group' => $validated['blood_group'],
                'registered_at_hospital_id' => $hospital->id,
                'tracking_consent' => true,
            ]);
        } else {
            $donor->update([
                'name' => $validated['donor_name'],
                'email' => $validated['donor_email'] ?? $donor->email,
                'blood_group' => $validated['blood_group'],
            ]);
        }

        if ($donor->last_donation_at && ! $donor->isEligibleToDonate()) {
            return back()
                ->withInput()
                ->withErrors([
                    'donor_phone' => 'This donor is not yet eligible to donate again. Next eligible date: '.$donor->nextEligibleDate()?->format('M j, Y').'.',
                ]);
        }

        $collectedAt = $validated['collected_at'];

        $unit = $hospital->bloodUnits()->create([
            'donor_id' => $donor->id,
            'unit_code' => BloodUnit::generateUnitCode($hospital->id),
            'blood_group' => $validated['blood_group'],
            'status' => 'quarantine',
            'screening_status' => 'pending',
            'recorded_by' => $user->id,
            'collected_at' => $collectedAt,
            'expires_at' => BloodUnit::calculateExpiresAt($collectedAt),
        ]);

        $donor->update([
            'last_donation_at' => $collectedAt,
            'blood_group' => $validated['blood_group'],
        ]);

        $txHash = app(BlockchainService::class)->registerUnit(
            $unit->unit_code,
            $hospital->id,
            $unit->blood_group
        );

        if ($txHash) {
            $unit->update(['blockchain_register_tx' => $txHash]);
        }

        return redirect()
            ->route('lab.units.screening.show', $unit)
            ->with('status', 'Unit '.$unit->unit_code.' registered for donor '.$donor->donor_code.'. Print the donation slip for the donor. Complete the lab screening report.')
            ->with('slip_unit', $unit->unit_code);
    }
}
