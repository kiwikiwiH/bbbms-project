<?php

namespace App\Http\Controllers\Lab;

use App\Http\Controllers\Controller;
use App\Models\BloodUnit;
use App\Services\BlockchainService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BloodUnitController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();
        $hospital = $user->hospital;

        $units = $hospital->bloodUnits()
            ->with(['recorder', 'screener'])
            ->latest('collected_at')
            ->paginate(20);

        $recordedByYou = $hospital->bloodUnits()
            ->where('recorded_by', $user->id)
            ->count();

        return view('lab.units.index', [
            'user' => $user,
            'hospital' => $hospital,
            'units' => $units,
            'recordedByYou' => $recordedByYou,
            'availableCount' => $hospital->availableUnitsCount(),
            'pendingScreening' => $hospital->bloodUnits()->pendingScreening()->count(),
        ]);
    }

    public function create(): View
    {
        return view('lab.units.create', [
            'bloodGroups' => config('tarrlok.blood_groups'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'blood_group' => ['required', 'string', 'in:'.implode(',', config('tarrlok.blood_groups'))],
            'collected_at' => ['required', 'date', 'before_or_equal:today'],
        ]);

        $user = auth()->user();
        $hospital = $user->hospital;

        $unit = $hospital->bloodUnits()->create([
            'unit_code' => BloodUnit::generateUnitCode($hospital->id),
            'blood_group' => $validated['blood_group'],
            'status' => 'quarantine',
            'screening_status' => 'pending',
            'recorded_by' => $user->id,
            'collected_at' => $validated['collected_at'],
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
            ->with('status', 'Unit '.$unit->unit_code.' registered. Complete the lab screening report to release it to inventory.');
    }
}
