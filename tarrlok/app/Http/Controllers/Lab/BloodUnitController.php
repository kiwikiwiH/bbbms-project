<?php

namespace App\Http\Controllers\Lab;

use App\Http\Controllers\Controller;
use App\Models\BloodUnit;
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
            ->with('recorder')
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
            'status' => 'available',
            'recorded_by' => $user->id,
            'collected_at' => $validated['collected_at'],
        ]);

        return redirect()
            ->route('lab.units.index')
            ->with('status', 'Unit '.$unit->unit_code.' ('.$unit->blood_group.') registered and added to hospital inventory.');
    }
}
