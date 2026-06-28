<?php

namespace App\Http\Controllers;

use App\Models\BloodUnit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DonationTrackController extends Controller
{
    public function index(): View
    {
        return view('track.index');
    }

    public function lookup(Request $request): RedirectResponse|View
    {
        $validated = $request->validate([
            'unit_code' => ['required', 'string', 'max:32'],
        ]);

        $code = strtoupper(trim($validated['unit_code']));

        $unit = BloodUnit::query()->where('unit_code', $code)->first();

        if (! $unit) {
            return back()
                ->withInput()
                ->withErrors(['unit_code' => 'No donation found with that unit ID. Check the code on your donation slip.']);
        }

        return redirect()->route('track.show', $unit);
    }

    public function show(BloodUnit $bloodUnit): View
    {
        $bloodUnit->load([
            'hospital',
            'bloodRequests.requestingHospital',
            'bloodRequests.fulfillingHospital',
        ]);

        return view('track.show', [
            'unit' => $bloodUnit,
        ]);
    }
}
